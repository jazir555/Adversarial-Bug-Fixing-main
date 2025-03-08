class CodeEnvironment {
    private $environments_dir;
    
    public function __construct() {
        $upload_dir = wp_upload_dir();
        $this->environments_dir = trailingslashit($upload_dir['basedir']) . 'adversarial-code-generator/environments';
        wp_mkdir_p($this->environments_dir);
    }

    public function create_environment($name, $language, $dependencies = []) {
        $env_id = uniqid('env_');
        $env_dir = "$this->environments_dir/$env_id";
        wp_mkdir_p($env_dir);
        
        file_put_contents("$env_dir/config.json", wp_json_encode([
            'name' => $name,
            'language' => $language,
            'dependencies' => $dependencies,
            'created_at' => current_time('mysql')
        ]));
        
        // Install dependencies
        $this->install_dependencies($env_dir, $language, $dependencies);

        return $env_id;
    }

    private function install_dependencies($env_dir, $language, $dependencies) {
        if (empty($dependencies)) {
            return true; // No dependencies to install
        }

        $install_command = '';
        if ($language === 'php') {
            $composer_json = [
                "require" => []
            ];
            foreach ($dependencies as $dep) {
                if (is_array($dep) && isset($dep['name']) && isset($dep['version'])) {
                    $composer_json["require"][$dep['name']] = $dep['version'];
                } else {
                    $composer_json["require"][$dep] = "*"; // Default to latest if version not specified
                }
            }
            file_put_contents("$env_dir/composer.json", json_encode($composer_json, JSON_PRETTY_PRINT));
            $install_command = "composer install --no-dev --no-scripts --no-plugins -d " . escapeshellarg($env_dir);
        } else if ($language === 'javascript') {
            $package_json = [
                "dependencies" => []
            ];
            foreach ($dependencies as $dep) {
                if (is_array($dep) && isset($dep['name']) && isset($dep['version'])) {
                    $package_json["dependencies"][$dep['name']] = $dep['version'];
                } else {
                    $package_json["dependencies"][$dep] = "latest"; // Default to latest if version not specified
                }
            }
            file_put_contents("$env_dir/package.json", json_encode($package_json, JSON_PRETTY_PRINT));
            $install_command = "npm install --prefix " . escapeshellarg($env_dir);
        }

        if (empty($install_command)) {
            return false; // No install command to execute
        }

        try {
            $result = $this->execute_command($install_command, $env_dir);
            if ($result['code'] === 0) {
                $this->log_info("Dependencies installed successfully for environment: {$env_dir}");
                return true;
            } else {
                $this->log_error("Dependency installation failed for environment: {$env_dir}. Error: " . $result['stderr']);
                return false;
            }
        } catch (Exception $e) {
            $this->log_error("Error executing dependency installation command for environment: {$env_dir}. Exception: " . $e->getMessage());
            return false;
        }
    }

    private function execute_command($command, $cwd = null) {
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

    private function log_info($message) {
        if (class_exists('Logger')) {
            $logger = new Logger();
            $logger->log_info($message);
        } else {
            error_log("Adversarial Bug Fixing - " . $message);
        }
    }

    private function log_error($message) {
        if (class_exists('Logger')) {
            $logger = new Logger();
            $logger->log_error($message);
        } else {
            error_log("Adversarial Bug Fixing - " . $message);
        }
    }


    public function get_environment($env_id) {
        $env_dir = "$this->environments_dir/$env_id";
        if (!file_exists($env_dir)) {
            return null;
        }
        
        return json_decode(file_get_contents("$env_dir/config.json"), true);
    }

    public function update_environment($env_id, $dependencies) {
        $env_dir = "$this->environments_dir/$env_id";
        if (!file_exists($env_dir)) {
            return false;
        }
        
        $config = json_decode(file_get_contents("$env_dir/config.json"), true);
        $config['dependencies'] = $dependencies;
        
        file_put_contents("$env_dir/config.json", wp_json_encode($config));

        // Install dependencies after updating config
        return $this->install_dependencies($env_dir, $config['language'], $dependencies);
    }

    public function delete_environment($env_id) {
        $env_dir = "$this->environments_dir/$env_id";
        if (file_exists($env_dir)) {
            wp_delete_dir($env_dir);
            return true;
        }
        return false;
    }

    public function list_environments($limit = 20) {
        $env_dirs = glob("$this->environments_dir/*", GLOB_ONLYDIR);
        usort($env_dirs, function($a, $b) {
            return filemtime($b) - filemtime($a);
        });
        
        $environments = [];
        foreach (array_slice($env_dirs, 0, $limit) as $dir) {
            $config = json_decode(file_get_contents("$dir/config.json"), true);
            $environments[] = [
                'id' => basename($dir),
                'name' => $config['name'],
                'language' => $config['language'],
                'dependencies' => $config['dependencies'],
                'created_at' => $config['created_at']
            ];
        }
        
        return $environments;
    }
}
