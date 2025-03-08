class GitIntegration {
    private $repos_dir;
    
    public function __construct() {
        $upload_dir = wp_upload_dir();
        $this->repos_dir = trailingslashit($upload_dir['basedir']) . 'adversarial-code-generator/git_repos';
        wp_mkdir_p($this->repos_dir);
    }
    
    public function clone_repository($repo_url, $branch = 'main') {
        $repo_name = basename(parse_url($repo_url, PHP_URL_PATH));
        $repo_dir = "$this->repos_dir/$repo_name";
        
        if (!file_exists($repo_dir)) {
            $command = "git clone " . escapeshellarg("--branch " . $branch) . " " . escapeshellarg($repo_url) . " " . escapeshellarg($repo_dir);
            $result = $this->execute_command($command, $this->repos_dir);

            if ($result['code'] !== 0) {
                throw new Exception("Git clone failed: " . $result['stderr'] . " Command: " . $command);
            }
        }
        
        return $repo_dir;
    }
    
    public function commit_changes($repo_dir, $message, $files = []) {
        if (!file_exists($repo_dir)) {
            throw new Exception("Repository directory does not exist");
        }
        
        chdir($repo_dir);
        
        $add_command = "git add ";
        if (!empty($files)) {
            $add_command .= implode(' ', array_map('escapeshellarg', $files));
        } else {
            $add_command .= ".";
        }
        $add_result = $this->execute_command($add_command, $repo_dir);
        if ($add_result['code'] !== 0) {
            throw new Exception("Git add failed: " . $add_result['stderr'] . " Command: " . $add_command);
        }
        
        $commit_command = "git commit -m " . escapeshellarg($message);
        $commit_result = $this->execute_command($commit_command, $repo_dir);
        if ($commit_result['code'] !== 0) {
            throw new Exception("Failed to commit changes: " . $commit_result['stderr'] . " Command: " . $commit_command);
        }
        
        return true;
    }
    
    public function get_repository_status($repo_dir) {
        if (!file_exists($repo_dir)) {
            throw new Exception("Repository directory does not exist");
        }
        
        chdir($repo_dir);
        $status_command = "git status --porcelain";
        $status_result = $this->execute_command($status_command, $repo_dir);
        if ($status_result['code'] !== 0) {
            throw new Exception("Failed to get repository status: " . $status_result['stderr'] . " Command: " . $status_command);
        }
        
        return $status_result['stdout'];
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
}
