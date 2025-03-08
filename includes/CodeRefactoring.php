<?php
require_once plugin_dir_path(__FILE__) . 'CodeRefactoringDatabase.php';
require_once plugin_dir_path(__FILE__) . 'LLMHandler.php';

class CodeRefactoring
{
    private $db;
    private $llm_handler;
    const CRON_HOOK = 'adversarial_code_refactoring_queue_hook';

    public function __construct()
    {
        $this->db = CodeRefactoringDatabase::get_instance();
        $this->llm_handler = new LLMHandler();
    }

    public static function activate()
    {
        if (! wp_next_scheduled(self::CRON_HOOK)) {
            wp_schedule_event(time(), 'hourly', self::CRON_HOOK);
        }
    }

    public static function deactivate()
    {
        wp_clear_scheduled_hook(self::CRON_HOOK);
    }

    public function install()
    {
        $this->db->install();
    }

    public function refactor_code($code, $language, $goal)
    {
        if (empty($code) || trim($code) === '' || empty($language) || trim($language) === '' || empty($goal) || trim($goal) === '') {
            return ['error' => 'Code, language, and goal are required and cannot be empty.'];
        }

        $entry_id = $this->db->create_entry(
            [
            'code' => $code,
            'code' => sanitize_textarea_field($code),
            'language' => sanitize_text_field($language),
            'goal' => sanitize_textarea_field($goal),
            'status' => 'pending'
            ]
        );

        return ['request_id' => $entry_id];
    }

    public function get_refactoring_request($entry_id)
    {
        $request = $this->db->get_entry($entry_id);
        if (!$request) {
            return ['error' => 'Refactoring request not found.'];
        }
        return $request;
    }

    public function get_refactoring_requests($status = null, $limit = 20)
    {
        return $this->db->get_entries($status, $limit);
    }

    public function enqueue_refactoring($code, $language, $goal)
    {
        return $this->refactor_code($code, $language, $goal);
    }

    
    public function process_refactoring_queue()
    {
        try {
            $pending_requests = $this->db->get_entries('pending', 10);
        } catch (Exception $e) {
            $error_message = "Database error fetching pending refactoring requests: " . $e->getMessage();
            $this->log_error($error_message);
            return; 
        }

        if (empty($pending_requests)) {
            $this->log_info("Code Refactoring Queue: No pending requests.");
            return;
        }

        $this->log_info("Code Refactoring Queue: Processing " . count($pending_requests) . " requests.");

        foreach ($pending_requests as $request) {
            try {
                $this->process_single_refactoring($request);
            } catch (Exception $e) {
                $error_message = "Error processing refactoring request ID: {$request->id}. Error: " . $e->getMessage();
                $this->log_error($error_message);
                $this->db->update_entry(
                    $request->id,
                    [
                        'status' => 'error',
                        'error' => $error_message,
                        'completed_at' => current_time('mysql'),
                        'full_error_details' => json_encode(
                            [
                            'error' => $error_message,
                            'exception_message' => $e->getMessage(),
                            'exception_trace' => $e->getTraceAsString(),
                            ]
                        )
                    ]
                );
            }
        }

        $processed_count = count($pending_requests);
        $completion_message = "Code Refactoring Queue: Completed processing of {$processed_count} requests.";
        $this->log_info($completion_message);
    }

    private function log_info($message)
    {
        if (class_exists('Logger')) {
            $logger = new Logger();
            $logger->log_info($message);
        } else {
            error_log("Adversarial Bug Fixing - " . $message);
        }
    }

    private function log_error($message)
    {
        if (class_exists('Logger')) {
            $logger = new Logger();
            $logger->log_error($message);
        } else {
            error_log("Adversarial Bug Fixing - " . $message);
        }
    }

    public function scheduled_process_refactoring_queue()
    {
        $this->log_info("WP Cron triggered code refactoring queue processing.");
        $this->process_refactoring_queue();
    }

    public function process_refactoring_queue_callback()
    {
        $this->process_refactoring_queue();
    }

    private function process_single_refactoring($request)
    {
        $entry_id = $request->id;
        $code = $request->code;
        $language = $request->language;
        $goal = $request->goal;

        $current_request = $this->get_refactoring_request($entry_id);
        if ($current_request && $current_request->status !== 'pending') {
            return;
        }

        if ($request->status !== 'pending') {
            return;
        }

        $prompt = "Refactor the following " . $language . " code to achieve the following goal: " . $goal . "\n\n" . $code;

        $llm_response = $this->llm_handler->call_llm_api($this->llm_handler->select_model('refactoring'), $prompt, 'refactor_code', $language);

        if (isset($llm_response['refactored_code'])) {
            $refactored_code = $llm_response['refactored_code'];
            if (empty($refactored_code)) {
                $error_message = 'Refactored code was empty from LLM response.';
                $this->handle_refactoring_error($entry_id, $error_message, $code, $language, $goal, $llm_response);
                return;
            }

            $validated_code = $this->validate_refactored_code($refactored_code, $language);
            $sanitized_refactored_code = wp_kses_post($validated_code);

            $this->db->update_entry(
                $entry_id, [
                'refactored_code' => $sanitized_refactored_code,
                'status' => 'completed',
                'completed_at' => current_time('mysql')
                ]
            );
        } else {
            $error_message = isset($llm_response['error']) ? $llm_response['error'] : 'Unknown error during code refactoring.';
            $this->handle_refactoring_error($entry_id, $error_message, $code, $language, $goal, $llm_response);
        }
    }

    private function handle_refactoring_error($entry_id, $error_message, $code, $language, $goal, $llm_response)
    {
        $sanitized_error_message = sanitize_text_field($error_message);
        $full_error_details = [
            'error' => $sanitized_error_message,
            'llm_response' => $llm_response,
            'request_details' => ['code' => $code, 'language' => $language, 'goal' => $goal]
        ];
        $log_message = "Code Refactoring Error ID: {$entry_id}, Error: {$sanitized_error_message}, Details: " . json_encode($full_error_details);

        if (class_exists('Logger')) {
            $logger = new Logger();
            $logger->log_error($log_message);
        } else {
            error_log($log_message);
        }

        $this->db->update_entry(
            $entry_id, [
            'status' => 'error',
            'error' => $sanitized_error_message,
            'completed_at' => current_time('mysql'),
            'full_error_details' => json_encode($full_error_details)
            ]
        );
    }

    private function validate_refactored_code($code, $language)
    {
        if (empty(trim($code))) {
            throw new Exception('Refactored code is empty after processing.');
        }
        if (strlen(trim($code)) < 10) {
            throw new Exception('Refactored code is too short or contains only minimal content.');
        }
        return $code;
    }
}

add_action(CodeRefactoring::CRON_HOOK, ['CodeRefactoring', 'scheduled_process_refactoring_queue']);
register_activation_hook(__FILE__, ['CodeRefactoring', 'activate']);
register_deactivation_hook(__FILE__, ['CodeRefactoring', 'deactivate']);
