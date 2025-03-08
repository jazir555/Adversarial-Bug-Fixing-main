<?php
require_once plugin_dir_path(__FILE__) . 'CIIntegrationDatabase.php';

class CIIntegration
{
    private $pipelines_dir;
    private $db;
    const CRON_HOOK = 'pipeline_cron_hook_'; // appended with pipeline ID

    public function __construct()
    {
        $upload_dir = wp_upload_dir();
        $this->pipelines_dir = trailingslashit($upload_dir['basedir']) . 'adversarial-code-generator/ci_pipelines';
        wp_mkdir_p($this->pipelines_dir);
        $this->db = CIIntegrationDatabase::get_instance();
    }

    public static function activate()
    {
        // Activation hook logic if needed
    }

    public static function deactivate()
    {
        // Deactivation hook logic if needed
    }

    public function install()
    {
        $this->db->install();
    }

    public function create_pipeline($name, $config)
    {
        $pipeline_id = uniqid('pipeline_');
        $pipeline_dir = "$this->pipelines_dir/$pipeline_id";
        wp_mkdir_p($pipeline_dir);
        
        $sanitized_config = $this->sanitize_pipeline_config($name, $config);
        $sanitized_config['last_run'] = null;
        file_put_contents("$pipeline_dir/config.json", wp_json_encode($sanitized_config, JSON_PRETTY_PRINT));
        
        return $pipeline_id;
    }

    public function update_pipeline($pipeline_id, $config)
    {
        $pipeline_dir = "$this->pipelines_dir/$pipeline_id";
        if (!file_exists($pipeline_dir)) {
            return false;
        }
        
        $sanitized_config = $this->sanitize_pipeline_config(null, $config); // Name not updated here
        file_put_contents("$pipeline_dir/config.json", wp_json_encode($sanitized_config, JSON_PRETTY_PRINT));
        return true;
    }

    private function sanitize_pipeline_config($name = null, $config)
    {
        $sanitized_config = [];
        if ($name !== null) {
            $sanitized_config['name'] = sanitize_text_field($name);
        } else if (isset($config['name'])) {
            $sanitized_config['name'] = sanitize_text_field($config['name']);
        }
        $sanitized_config['repository'] = esc_url_raw($config['repository']);
        $sanitized_config['branch'] = sanitize_text_field($config['branch']);
        $sanitized_config['build_command'] = sanitize_text_field($config['build_command']);
        $sanitized_config['test_command'] = sanitize_text_field($config['test_command']);
        $sanitized_config['deploy_command'] = sanitize_text_field($config['deploy_command']);
        $sanitized_config['schedule'] = sanitize_text_field($config['schedule']); // Assuming schedule is a predefined string
        return $sanitized_config;
    }

    public function delete_pipeline($pipeline_id)
    {
        $pipeline_dir = "$this->pipelines_dir/$pipeline_id";
        if (!file_exists($pipeline_dir)) {
            return false;
        }
        
        // Delete pipeline directory and all its contents
        $this->delete_directory($pipeline_dir);
        return true;
    }

    private function delete_directory($dir_path)
    {
        if (!is_dir($dir_path)) {
            return false;
        }
        $files = glob($dir_path . '/*');
        foreach ($files as $file) {
            is_dir($file) ? $this->delete_directory($file) : unlink($file);
        }
        return rmdir($dir_path);
    }

    public function get_pipeline($pipeline_id)
    {
        $pipeline_dir = "$this->pipelines_dir/$pipeline_id";
        if (!file_exists($pipeline_dir)) {
            return null;
        }
        
        return json_decode(file_get_contents("$pipeline_dir/config.json"), true);
    }

    public function run_pipeline($pipeline_id)
    {
        $pipeline = $this->get_pipeline($pipeline_id);
        if (!$pipeline) {
            return false;
        }

        $this->db->log_pipeline_run($pipeline_id, 'running', 'Pipeline execution started.');
        
        $repository_url = $pipeline['repository'];
        $branch = $pipeline['branch'];
        $pipeline_dir = "$this->pipelines_dir/{$pipeline_id}/repository";
        
        // Ensure the repository directory is empty before cloning
        if (is_dir($pipeline_dir)) {
            $this->delete_directory($pipeline_dir);
        }
        wp_mkdir_p($pipeline_dir);

        $clone_command = "git clone -b {$branch} --depth 1 {$repository_url} {$pipeline_dir}";

        try {
            $clone_result = $this->execute_command($clone_command, $pipeline_dir);
            $this->log_pipeline_info($pipeline_id, "Repository cloned successfully to: {$pipeline_dir}");
            $this->log_pipeline_info($pipeline_id, "Clone Output (stdout): " . $clone_result['stdout'], 'running');
            if (!empty($clone_result['stderr'])) {
                $this->log_pipeline_info($pipeline_id, "Clone Output (stderr): " . $clone_result['stderr'], 'running');
            }
        } catch (Exception $e) {
            $error_message = "Failed to clone repository: " . $e->getMessage();
            $this->log_pipeline_error($pipeline_id, $error_message);
            $this->db->log_pipeline_run(
                $pipeline_id,
                'failed',
                $error_message . ". Full error details: " . json_encode(
                    [
                    'error' => $error_message,
                    'exception_message' => $e->getMessage(),
                    'exception_trace' => $e->getTraceAsString(),
                    ]
                )
            );
            return false;
        }

        // Implement pipeline execution logic
        // 2. Running tests
        // 3. Building the project
        // 4. Deploying if successful
        
        // For demonstration purposes, we'll just return a success status
        $build_command = $pipeline['build_command'];
        $test_command = $pipeline['test_command'];
        
        $build_output = null;
        $test_output = null;

        if (!empty($build_command)) {
            $this->log_pipeline_info($pipeline_id, "Executing build command: {$build_command}");
            try {
                $build_result = $this->execute_command($build_command, $pipeline_dir . '/repository');
                $this->log_pipeline_info($pipeline_id, "Build command executed successfully.");
                $this->log_pipeline_info($pipeline_id, "Build Output (stdout): " . $build_result['stdout']);
                if (!empty($build_result['stderr'])) {
                    $this->log_pipeline_error($pipeline_id, "Build Output (stderr): " . $build_result['stderr']);
                }
            } catch (Exception $e) {
                $error_message = "Build command failed: " . $e->getMessage();
                $this->log_pipeline_error($pipeline_id, $error_message);
                $this->log_pipeline_error($pipeline_id, "Build Output: " . $e->getMessage());
                $this->db->log_pipeline_run(
                    $pipeline_id,
                    'failed',
                    $error_message . ". Full error details: " . json_encode(
                        [
                        'error' => $error_message,
                        'exception_message' => $e->getMessage(),
                        'exception_trace' => $e->getTraceAsString(),
                        ]
                    )
                );
                return false;
            }
        } else {
            $this->log_pipeline_info($pipeline_id, "No build command defined.");
        }

        if (!empty($test_command)) {
            $this->log_pipeline_info($pipeline_id, "Executing test command: {$test_command}");
            try {
                $test_output = $this->execute_command($test_command, $pipeline_dir . '/repository');
                $this->log_pipeline_info($pipeline_id, "Test command executed successfully.");
                $this->log_pipeline_info($pipeline_id, "Test Output (stdout): " . $test_output['stdout']);
                if (!empty($test_output['stderr'])) {
                    $this->log_pipeline_error($pipeline_id, "Test Output (stderr): " . $test_output['stderr']);
                }
            } catch (Exception $e) {
                $this->log_pipeline_error($pipeline_id, "Test command failed: " . $e->getMessage());
                $this->log_pipeline_error($pipeline_id, "Test Output: " . $e->getMessage());
                $this->db->log_pipeline_run($pipeline_id, 'failed', 'Test command failed: ' . $e->getMessage());
                return false;
            }
        } else {
            $this->log_pipeline_info($pipeline_id, "No test command defined.");
        }

        if (!empty($deploy_command)) {
            $this->log_pipeline_info($pipeline_id, "Executing deploy command: {$deploy_command}");
            try {
                $deploy_output = $this->execute_command($deploy_command, $pipeline_dir . '/repository');
                $this->log_pipeline_info($pipeline_id, "Deployment command executed successfully.");
                $this->log_pipeline_info($pipeline_id, "Deployment Output (stdout): " . $deploy_output['stdout']);
                if (!empty($deploy_output['stderr'])) {
                    $this->log_pipeline_error($pipeline_id, "Deployment Output (stderr): " . $deploy_output['stderr']);
                }
            } catch (Exception $e) {
                $error_message = "Deployment command failed: " . $e->getMessage();
                $this->log_pipeline_error($pipeline_id, $error_message);
                $this->log_pipeline_error($pipeline_id, "Deployment Output: " . $e->getMessage());
                $this->db->log_pipeline_run(
                    $pipeline_id,
                    'failed',
                    $error_message . ". Full error details: " . json_encode(
                        [
                        'error' => $error_message,
                        'exception_message' => $e->getMessage(),
                        'exception_trace' => $e->getTraceAsString(),
                        ]
                    )
                );
                return false;
            }
        } else {
            $this->log_pipeline_info($pipeline_id, "No deployment command defined.");
        }

        // 4. Deployment step (placeholder)
        $deploy_command = $pipeline['deploy_command'];
        if (!empty($deploy_command)) {
            $this->log_pipeline_info($pipeline_id, "Executing deploy command: {$deploy_command}");
            try {
                $deploy_output = $this->execute_command($deploy_command, $pipeline_dir . '/repository');
                $this->log_pipeline_info($pipeline_id, "Deployment command executed successfully.");
                $this->log_pipeline_info($pipeline_id, "Deployment Output (stdout): " . $deploy_output['stdout']);
                if (!empty($deploy_output['stderr'])) {
                    $this->log_pipeline_error($pipeline_id, "Deployment Output (stderr): " . $deploy_output['stderr']);
                }
            } catch (Exception $e) {
                $this->log_pipeline_error($pipeline_id, "Deployment command failed: " . $e->getMessage());
                $this->log_pipeline_error($pipeline_id, "Deployment Output: " . $e->getMessage());
                $this->db->log_pipeline_run($pipeline_id, 'failed', 'Deployment command failed: ' . $e->getMessage());
                return false;
            }
        } else {
            $this->log_pipeline_info($pipeline_id, "No deployment command defined.");
        }

        // 4. Deployment step
        $deploy_command = $pipeline['deploy_command'];
        if (!empty($deploy_command)) {
            $this->log_pipeline_info($pipeline_id, "Executing deploy command: {$deploy_command}");
            try {
                $deploy_result = $this->execute_command($deploy_command, $pipeline_dir . '/repository');
                $this->log_pipeline_info($pipeline_id, "Deployment command executed successfully.");
            } catch (Exception $e) {
                $error_message = "Deployment command failed: " . $e->getMessage();
                $this->log_pipeline_error($pipeline_id, $error_message);
                $this->db->log_pipeline_run(
                    $pipeline_id,
                    'failed',
                    $error_message . ". Full error details: " . json_encode(
                        [
                        'error' => $error_message,
                        'exception_message' => $e->getMessage(),
                        'exception_trace' => $e->getTraceAsString(),
                        ]
                    )
                );
                return false;
            }
        } else {
            $this->log_pipeline_info($pipeline_id, "No deployment command defined.");
        }


        $this->log_pipeline_info($pipeline_id, "Pipeline execution finished.");
        $this->db->log_pipeline_run($pipeline_id, 'success', 'Pipeline execution finished successfully.');
        
        $pipeline['last_run'] = current_time('mysql');
        $this->update_pipeline($pipeline_id, $pipeline);

        return true;
    }

    public function schedule_pipeline($pipeline_id, $schedule)
    {
        if (wp_next_scheduled(self::CRON_HOOK . $pipeline_id)) {
            wp_clear_scheduled_hook(self::CRON_HOOK . $pipeline_id);
        }
        
        if ($schedule !== 'manual') {
            wp_schedule_event(time(), $schedule, self::CRON_HOOK . $pipeline_id, [$pipeline_id]);
        }

        $pipeline = $this->get_pipeline($pipeline_id);
        if ($pipeline) {
            $pipeline['schedule'] = $schedule;
            $this->update_pipeline($pipeline_id, $pipeline);
        }
    }

    public function unschedule_pipeline($pipeline_id)
    {
        wp_clear_scheduled_hook(self::CRON_HOOK . $pipeline_id, [$pipeline_id]);
        $pipeline = $this->get_pipeline($pipeline_id);
        if ($pipeline) {
            $pipeline['schedule'] = 'manual';
            $this->update_pipeline($pipeline_id, $pipeline);
        }
    }

    public function scheduled_run_pipeline($pipeline_id)
    {
        $this->log_pipeline_info($pipeline_id, "Scheduled pipeline execution started.", 'running'); // Log scheduled run
        $this->run_pipeline($pipeline_id);
    }

    private function execute_command($command, $cwd = null)
    {
        $descriptors = [
            0 => ['pipe', 'r'], // stdin
            1 => ['pipe', 'w'], // stdout
            2 => ['pipe', 'w']  // stderr
        ];
        
        $process = proc_open($command, $descriptors, $pipes, $cwd, null);
        
        if (!is_resource($process)) {
            throw new Exception("Failed to execute command: $command");
        }
        
        fclose($pipes[0]); // Close stdin
        $stdout = stream_get_contents($pipes[1]);
        fclose($pipes[1]); // Close stdout
        $stderr = stream_get_contents($pipes[2]);
        fclose($pipes[2]); // Close stderr
        
        $return_value = proc_close($process);
        
        if ($return_value !== 0) {
            throw new Exception("Command failed with exit code $return_value: $command\\nSTDERR: $stderr\\nSTDOUT: $stdout");
        }
        
        return ['stdout' => $stdout, 'stderr' => $stderr, 'code' => $return_value];
    }

    private function log_pipeline_info($pipeline_id, $message, $status = 'running')
    {
        $this->db->log_pipeline_run($pipeline_id, $status, 'INFO: ' . $message);
        error_log("Pipeline ID: {$pipeline_id} - INFO: {$message}");
    }

    private function log_pipeline_error($pipeline_id, $message, $status = 'failed')
    {
        $this->db->log_pipeline_run($pipeline_id, $status, 'ERROR: ' . $message);
        error_log("Pipeline ID: {$pipeline_id} - ERROR: {$message}");
    }

    public function get_pipeline_report($pipeline_id, $limit = 20)
    {
        return $this->db->get_pipeline_logs($pipeline_id, $limit);
    }

    public function list_pipelines($limit = 20)
    {
        $pipeline_dirs = glob("$this->pipelines_dir/*", GLOB_ONLYDIR);
        usort(
            $pipeline_dirs, function ($a, $b) {
                return filemtime($b) - filemtime($a);
            }
        );
        
        $pipelines = [];
        foreach (array_slice($pipeline_dirs, 0, $limit) as $dir) {
            $config = json_decode(file_get_contents("$dir/config.json"), true);
            $pipelines[] = [
                'id' => basename($dir),
                'name' => $config['name'],
                'repository' => $config['repository'],
                'branch' => $config['branch'],
                'last_run' => isset($config['last_run']) ? $config['last_run'] : null,
                'schedule' => isset($config['schedule']) ? $config['schedule'] : 'manual'
            ];
        }
        
        return $pipelines;
    }
}

add_action(CIIntegration::CRON_HOOK . '{pipeline_id}', ['CIIntegration', 'scheduled_run_pipeline']);
register_activation_hook(__FILE__, ['CIIntegration', 'activate']);
register_deactivation_hook(__FILE__, ['CIIntegration', 'deactivate']);
register_activation_hook(__FILE__, ['CIIntegration', 'install']);
