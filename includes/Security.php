/**
 * Class Security
 *
 * Provides basic security-related functionalities for the plugin.
 * WARNING: The security measures implemented in this class are
 *          BASIC and INSUFFICIENT for real-world security.
 *          DO NOT rely on these measures to execute untrusted code in production environments.
 *          The current implementation is easily bypassable and does not provide
 *          adequate protection against sophisticated attacks or common vulnerabilities.
 *          For production use, a robust sandbox environment and comprehensive
 *          security analysis are essential.
 */
class Security {
    private $disallowed_imports = ['os', 'subprocess', 'sys', 'shutil', 'pickle', 'ctypes', 'socket', 'multiprocessing', 'threading', 'zipfile', 'tarfile']; // Added more potentially dangerous imports
    private $disallowed_functions = ['eval', 'exec', 'system', 'popen', 'shell_exec', 'passthru', 'proc_open', 'pcntl_exec', 'unserialize', 'assert']; // Added more dangerous functions
    private $max_line_length = 150;
    private $max_code_length = 10000;
    
    /**
     * Sanitizes user prompts to prevent basic injection attacks.
     * WARNING: This is a basic sanitization and is NOT sufficient for robust security.
     *
     * @param string $prompt The user prompt to sanitize.
     * @return string The sanitized prompt.
     */
    public function sanitize_prompt($prompt) {
        // Basic sanitization to prevent injection attacks
        $prompt = wp_kses_post($prompt);
        $prompt = str_replace(['`', '$', '\\'], '', $prompt);
        return trim($prompt);
    }
    
    /**
     * Sanitizes code to remove potentially dangerous patterns.
     * WARNING: This code sanitization is BASIC and INSUFFICIENT for real security.
     *          It is easily bypassable and should NOT be relied upon to prevent
     *          malicious code execution.
     *
     * @param string $code The code to sanitize.
     * @return string The sanitized code.
     */
    public function sanitize_code($code) {
        // Remove potentially dangerous patterns
        foreach ($this->disallowed_imports as $import) {
            $code = preg_replace('/^import\s+' . $import . '/mi', '', $code);
            $code = preg_replace('/^from\s+' . $import . '\s+import/mi', '', $code);
             $code = preg_replace('/^import\s+[\w\s,]*' . $import . '/mi', '', $code); // cover more import variations
        }
        
        foreach ($this->disallowed_functions as $func) {
            $code = preg_replace('/\b' . $func . '\s*\(/', '', $code);
        }
        
        // Limit code size
        if (strlen($code) > $this->max_code_length) {
            throw new Exception('Code exceeds maximum allowed length');
        }
        
        return $code;
    }
    
    /**
     * Checks code for potential security vulnerabilities.
     * WARNING: These security checks are BASIC and INSUFFICIENT for real security analysis.
     *          They are easily bypassable and should NOT be relied upon for production security.
     *
     * @param string $code The code to check.
     * @return array An array of security issues found in the code.
     */
    public function check_code_security($code) {
        $issues = [];
        
        // Check for dangerous imports
        foreach ($this->disallowed_imports as $import) {
            if (preg_match('/^import\s+' . $import . '/mi', $code) || 
                preg_match('/^from\s+' . $import . '\s+import/mi', $code) ||
                preg_match('/^import\s+[\w\s,]*' . $import . '/mi', $code)) { // cover more import variations
                $issues[] = "Use of disallowed import '$import'";
            }
        }
        
        // Check for dangerous functions
        foreach ($this->disallowed_functions as $func) {
            if (preg_match('/\b' . $func . '\s*\(/', $code)) {
                $issues[] = "Use of dangerous function '$func'";
            }
        }
        
        // Check for shell injections
        if (preg_match('/(os\.system|subprocess\.call|subprocess\.Popen)/', $code)) {
            $issues[] = "Potential shell injection vulnerability";
        }

        // Check for file system access - basic check
        if (preg_match('/(open|file_get_contents|file_put_contents|unlink|rename|mkdir|rmdir)/', $code)) {
            $issues[] = "Potential file system access vulnerability";
        }

        // Check for network access - basic check
         if (preg_match('/(socket\.socket|urllib\.request\.urlopen|requests\.get|fsockopen|curl_init)/', $code)) {
            $issues[] = "Potential network access vulnerability";
        }
        
        // Check for overly long lines
        $lines = explode("\n", $code);
        foreach ($lines as $line) {
            if (strlen(trim($line)) > $this->max_line_length) {
                $issues[] = "Line exceeds maximum allowed length";
                break;
            }
        }
        
        return $issues;
    }
}
