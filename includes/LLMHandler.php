<?php

class LLMHandler
{
    private $api_keys;
    private $models;
    private $rate_limits;
    private $cache;
    private $last_call_timestamps = []; // Track last call timestamp for each model

    public function __construct()
    {
        $this->load_settings();
        $this->initialize_cache();
    }

    private function load_settings()
    {
        $settings = new Settings();
        $this->api_keys = $settings->get('llm_api_keys');
        $this->models = $settings->get('llm_models');
        $this->rate_limits = $settings->get('llm_rate_limits');
    }

    private function initialize_cache()
    {
        $this->cache = new TransientCache('adversarial_llm_cache');
    }

    public function generate_code($prompt, $language = 'python')
    {
        $model = $this->select_model('generation');
        $this->check_rate_limits($model);

        $cache_key = 'generate_code_' . md5($prompt . $language);
        if ($cached_response = $this->cache->get($cache_key)) {
            return $cached_response;
        }

        $response = $this->call_llm_api($model, $prompt, 'generate_code', $language);
        $this->cache->set($cache_key, $response, 300); // Cache for 5 minutes

        return $response;
    }

    public function check_code_security($code)
    {
        $model = $this->select_model('security');
        $this->check_rate_limits($model);

        return $this->call_llm_api($model, $code, 'check_security');
    }

    public function apply_bug_fixes($code, $bug_report)
    {
        $model = $this->select_model('fixing');
        $this->check_rate_limits($model);

        $prompt = "Apply the following bug fixes to the code:\n\nBug Report:\n$bug_report\n\nCode:\n$code";
        return $this->call_llm_api($model, $prompt, 'apply_fixes');
    }

    public function generate_unit_tests($code, $language = 'python')
    {
        $model = $this->select_model('testing');
        $this->check_rate_limits($model);

        $prompt = "Generate comprehensive unit tests for the following $language code:\n\n$code";
        return $this->call_llm_api($model, $prompt, 'generate_tests', $language);
    }

    public function generate_code_review($code, $language = 'python')
    {
        $model = $this->select_model('review');
        $this->check_rate_limits($model);

        $prompt = "Perform a code review for the following $language code:\n\n$code\n\nProvide feedback on code quality, security, performance, and best practices.";
        return $this->call_llm_api($model, $prompt, 'generate_review', $language);
    }

    public function optimize_code_performance($code, $language = 'python')
    {
        $model = $this->select_model('performance');
        $this->check_rate_limits($model);

        $prompt = "Optimize the following $language code for performance:\n\n$code\n\nFocus on algorithmic improvements and resource efficiency.";
        return $this->call_llm_api($model, $prompt, 'optimize_performance', $language);
    }

    public function format_code($code, $language = 'python')
    {
        $model = $this->select_model('formatting');
        $this->check_rate_limits($model);

        return $this->call_llm_api($model, $code, 'format_code', $language);
    }

    public function translate_code($code, $source_lang, $target_lang)
    {
        $model = $this->select_model('translation');
        $this->check_rate_limits($model);

        $prompt = "Translate the following $source_lang code to $target_lang:\n\n$code";
        return $this->call_llm_api($model, $prompt, 'translate_code', $target_lang);
    }

    public function refactor_code($code, $language = 'python', $goal = '')
    {
        $model = $this->select_model('refactoring');
        $this->check_rate_limits($model);

        $prompt = "Refactor the following $language code to achieve the following goal: $goal\n\n$code";
        return $this->call_llm_api($model, $prompt, 'refactor_code', $language);
    }

    private function select_model($task)
    {
        $default_models = [
            'generation' => 'claude',
            'security' => 'claude',
            'fixing' => 'claude',
            'testing' => 'claude',
            'review' => 'claude',
            'performance' => 'claude',
            'formatting' => 'claude',
            'translation' => 'claude',
            'refactoring' => 'claude'
        ];

        return $this->models[$task] ?? $default_models[$task];
    }

    private function check_rate_limits($model)
    {
        if (empty($this->rate_limits[$model])) {
            return; // No rate limits configured for this model
        }

        $limit = intval($this->rate_limits[$model]['limit']);
        $interval = $this->rate_limits[$model]['interval']; // e.g., 'minute', 'hour'
        $now = time();
        $last_call = $this->last_call_timestamps[$model] ?? 0;
        $time_diff = $now - $last_call;
        $wait_time = 0;

        if ($interval === 'minute') {
            $wait_time = 60 / $limit;
        } else if ($interval === 'hour') {
            $wait_time = 3600 / $limit;
        } else {
            $wait_time = 1; // Default to 1 second if interval is not recognized
        }

        if ($time_diff < $wait_time) {
            $wait_seconds = ceil($wait_time - $time_diff);
            error_log("LLMHandler: Rate limit exceeded for model {$model}. Waiting {$wait_seconds} seconds.");
            sleep($wait_seconds); // Simple sleep for rate limiting
        }

        $this->last_call_timestamps[$model] = time(); // Update last call timestamp
    }

    private function call_llm_api($model, $prompt, $action, $language = null)
    {
        $api_endpoint = $this->api_keys[$model]['endpoint'] ?? '';
        $api_key = $this->api_keys[$model]['key'] ?? '';

        if (empty($api_endpoint) || empty($api_key)) {
            $error_message = 'API endpoint or key not configured for model: ' . $model;
            if (class_exists('Logger')) {
                $logger = new Logger();
                $logger->log_error($error_message);
            } else {
                error_log("Adversarial Bug Fixing - " . $error_message);
            }
            return ['error' => $error_message];
        }

        $api_url = $api_endpoint . '/' . $action;

        $args = [
            'headers' => [
                'Content-Type' => 'application/json',
                'Authorization' => 'Bearer ' . $api_key,
            ],
            'body' => wp_json_encode(
                [
                'model' => $model,
                'prompt' => $prompt,
                'language' => $language,
                ]
            ),
            'timeout' => 30,
        ];

        $response = wp_remote_post($api_url, $args);

        if (is_wp_error($response)) {
            $error_message = 'WordPress HTTP Error: ' . $response->get_error_message();
            if (class_exists('Logger')) {
                $logger = new Logger();
                $logger->log_error($error_message);
            } else {
                error_log("Adversarial Bug Fixing - " . $error_message);
            }
            return ['error' => $error_message];
        }

        $response_code = wp_remote_retrieve_response_code($response);
        $response_body = wp_remote_retrieve_body($response);

        if (is_wp_error($response)) {
            $error_message = 'WordPress HTTP Error: ' . $response->get_error_message();
        } elseif ($response_code === 429) {
            $error_message = 'LLM API Rate Limit Exceeded: Status Code ' . $response_code . ', Body: ' . $response_body;
        } elseif ($response_code >= 400) {
            $error_message = 'LLM API Error: Status Code ' . $response_code . ', Body: ' . $response_body;
        } elseif (json_last_error() !== JSON_ERROR_NONE) {
            $error_message = 'Invalid JSON response from LLM API: ' . $response_body . ', JSON error: ' . json_last_error_msg();
        } else {
            $json_response = json_decode($response_body, true);
            if (is_array($json_response)) {
                return $json_response;
            } else {
                 $error_message = 'Unexpected response format from LLM API: ' . $response_body;
            }
        }

        if(isset($error_message)) {
            if (class_exists('Logger')) {
                $logger = new Logger();
                $logger->log_error($error_message);
            } else {
                error_log("Adversarial Bug Fixing - " . $error_message);
            }
            return ['error' => $error_message];
        }
    }
}
