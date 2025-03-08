class CodeAnalysis {
    public function analyze_code_quality($code, $language) {
        $temp_file = tempnam(sys_get_temp_dir(), 'code_analysis_');
        file_put_contents($temp_file, $code);

        $metrics_output = '';
        $metrics_error = '';

        $command = "vendor/bin/phpmetrics --report-json=" . escapeshellarg("{$temp_file}.json") . " " . escapeshellarg($temp_file);

        try {
            $metrics_result = $this->execute_command($command);
            $metrics_output = $metrics_result['stdout'];
            $metrics_error = $metrics_result['stderr'];

            if (file_exists("{$temp_file}.json")) {
                $metrics_data = json_decode(file_get_contents("{$temp_file}.json"), true);
                $metrics_output = $this->format_metrics_output($metrics_data);
                unlink("{$temp_file}.json"); // Delete metrics json report
            }

        } catch (Exception $e) {
            $metrics_error = "Error running phpmetrics: " . $e->getMessage();
            $metrics_output = "Code metrics analysis failed.";
            error_log("CodeAnalysis: phpmetrics error - " . $metrics_error);
        }


        $prompt = "Perform a comprehensive quality analysis of the following " . $language . " code:\n\n" . $code . 
                  "\n\nCode Metrics:\n" . $metrics_output .
                  "\n\nErrors from metrics tool:\n" . $metrics_error . 
                  "\n\nEvaluate code readability, maintainability, adherence to best practices, and potential improvements.";
        
        $llm_handler = new LLMHandler();
        $llm_response = $llm_handler->call_llm_api($llm_handler->select_model('analysis'), $prompt, 'analyze_code', $language);

        unlink($temp_file); // Delete temp file
        return $llm_response;
    }

    private function execute_command($command) {
        $descriptors = [
            0 => ['pipe', 'r'], // stdin
            1 => ['pipe', 'w'], // stdout
            2 => ['pipe', 'w']  // stderr
        ];
        
        $process = proc_open($command, $descriptors, $pipes, null, null);
        
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

    private function format_metrics_output($metrics_data) {
        if (empty($metrics_data) || !is_array($metrics_data)) {
            return "No metrics data available or invalid format.";
        }

        $formatted_output = "Code Metrics Summary:\n";
        $formatted_output .= "-------------------------\n";

        // Example metrics - customize based on phpmetrics output
        $formatted_output .= "Lines of Code (LOC): " . ($metrics_data['loc'] ?? 'N/A') . "\n";
        $formatted_output .= "Cyclomatic Complexity (CCN): " . ($metrics_data['ccn'] ?? 'N/A') . "\n";
        $formatted_output .= " количество классов: " . ($metrics_data[' количество классов'] ?? 'N/A') . "\n";
        $formatted_output .= " количество методов: " . ($metrics_data[' количество методов'] ?? 'N/A') . "\n";
        // Add more metrics as needed

        $formatted_output .= "-------------------------\n";
        $formatted_output .= "For detailed metrics, refer to the phpmetrics JSON report.\n";

        return $formatted_output;
    }
}
