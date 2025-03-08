class AdvancedSecurity {
    private $vulnerability_db;
    
    public function __construct() {
        $this->load_vulnerability_database();
    }

    private function load_vulnerability_database() {
        $upload_dir = wp_upload_dir();
        $default_db_path = trailingslashit($upload_dir['basedir']) . 'adversarial-code-generator/security/vulnerabilities.json';
        $db_path_option = get_option('adversarial_settings');
        $db_path = isset($db_path_option['vulnerability_db_path']) ? $db_path_option['vulnerability_db_path'] : $default_db_path;


        if (!file_exists($db_path)) {
            // Initialize with default vulnerabilities
            $default_vulnerabilities = [
                [
                    "title" => "SQL Injection",
                    "description" => "Detects potential SQL injection vulnerabilities.",
                    "severity" => "critical",
                    "pattern" => "/\b(SELECT|INSERT|UPDATE|DELETE)\s+.*\s+FROM\s+.*\s+WHERE\s+.*['\"].*\b/",
                    "references" => ["https://owasp.org/www-project-top-ten/OWASP_Top_Ten/A03_2021-Injection/"]
                ],
                [
                    "title" => "Cross-Site Scripting (XSS)",
                    "description" => "Detects potential cross-site scripting vulnerabilities.",
                    "severity" => "high",
                    "pattern" => "/<script\b[^>]*>([\s\S]*?)<\/script>/i",
                    "references" => ["https://owasp.org/www-project-top-ten/OWASP_Top_Ten/A03_2021-Injection/"]
                ],
                [
                    "title" => "Command Injection",
                    "description" => "Detects potential command injection vulnerabilities.",
                    "severity" => "critical",
                    "pattern" => "/\b(system|exec|shell_exec|passthru|popen|proc_open|pcntl_exec)\s*\(.*?\)/",
                    "references" => ["https://owasp.org/www-project-top-ten/OWASP_Top_Ten/A03_2021-Injection/"]
                ],
                [
                    "title" => "Path Traversal",
                    "description" => "Detects potential path traversal vulnerabilities.",
                    "severity" => "high",
                    "pattern" => "/\.\.\//",
                    "references" => ["https://owasp.org/www-project-top-ten/OWASP_Top_Ten/A01_2021-Broken_Access_Control/"]
                ],
                [
                    "title" => "File Inclusion",
                    "description" => "Detects potential file inclusion vulnerabilities.",
                    "severity" => "high",
                    "pattern" => "/(include|require)(_once)?\s*\(.*?\)/",
                    "references" => ["https://owasp.org/www-project-top-ten/OWASP_Top_Ten/A01_2021-Broken_Access_Control/"]
                ],
                [
                    "title" => "Unsafe Deserialization",
                    "description" => "Detects potential unsafe deserialization vulnerabilities.",
                    "severity" => "high",
                    "pattern" => "/(unserialize|php_unserialize)\s*\(.*?\)/",
                    "references" => ["https://owasp.org/www-project-top-ten/OWASP_Top_Ten/A08_2021-Software_and_Data_Integrity_Failures/"]
                ]
            ];
            file_put_contents($db_path, wp_json_encode($default_vulnerabilities, JSON_PRETTY_PRINT));
        }
        
        $this->vulnerability_db = json_decode(file_get_contents($db_path), true);
    }

    public function scan_for_vulnerabilities($code) {
        $findings = [];
        
        foreach ($this->vulnerability_db as $index => $vulnerability) {
            if (isset($vulnerability['pattern'])) {
                if (preg_match($vulnerability['pattern'], $code, $matches)) {
                    $findings[] = [
                        'index' => $index, // Include index for editing/deletion
                        'title' => $vulnerability['title'],
                        'description' => $vulnerability['description'],
                        'severity' => $vulnerability['severity'],
                        'references' => $vulnerability['references'],
                        'match' => $matches[0] // Matched code snippet
                    ];
                }
            }
        }
        
        return $findings;
    }

    public function harden_code($code) {
        $findings = $this->scan_for_vulnerabilities($code);
        $hardened_code = $code;
        
        foreach ($findings as $finding) {
            if (isset($finding['match'])) {
                $replacement = $this->get_safe_replacement($finding);
                $hardened_code = str_replace($finding['match'], $replacement, $hardened_code);
            }
        }
        
        return $hardened_code;
    }

    private function get_safe_replacement($finding) {
        $title = $finding['title'];
        $match = $finding['match'];

        switch ($title) {
            case 'SQL Injection':
                // Stronger warning about SQL Injection
                return "// CRITICAL: SQL Injection vulnerability found: " . $match . ". автоматическое hardening is not possible. обязательно use prepared statements or parameterized queries.";
            case 'Cross-Site Scripting (XSS)':
                // Apply basic HTML escaping for XSS
                return esc_html($match) . " /* XSS vulnerability was found and automatically sanitized using esc_html(). Verify context-appropriate escaping. */";
            case 'Command Injection':
                // Emphasize input sanitization and escaping
                return "// CRITICAL: Command Injection vulnerability found: " . $match . ". автоматическое hardening is not fully reliable. строго avoid system commands. If absolutely necessary, sanitize inputs тщательно with escapeshellarg() or escapeshellcmd().";
            case 'Path Traversal':
                // More detailed suggestion for path traversal
                return "// Path Traversal vulnerability found: " . $match . ". автоматическое hardening requires context. Implement path sanitization using realpath() and строго whitelist allowed directories.";
            case 'File Inclusion':
                // Prevent execution for file inclusion
                return "'/* File Inclusion vulnerability - execution prevented: " . $match . " */'"; 
            case 'Unsafe Deserialization':
                // Stronger warning against unserialize
                return "// CRITICAL: Unsafe Deserialization vulnerability found: " . $match . ". автоматическое hardening is not possible. строго avoid unserialize() особенно for untrusted data. Use JSON or other safer formats.";
            default:
                return "// Vulnerability found: " . $title . ". Review and implement appropriate security measures for: " . $match . ".";
        }
    }

    public function update_vulnerability_database($new_data) {
        $upload_dir = wp_upload_dir();
        $db_path = trailingslashit($upload_dir['basedir']) . 'adversarial-code-generator/security/vulnerabilities.json';
        
        $current_db = $this->vulnerability_db;
        $current_db = array_merge($current_db, $new_data);
        
        file_put_contents($db_path, wp_json_encode($current_db, JSON_PRETTY_PRINT));
        $this->vulnerability_db = $current_db;
    }

    public function add_vulnerability($vulnerability_data) {
        $upload_dir = wp_upload_dir();
        $db_path = trailingslashit($upload_dir['basedir']) . 'adversarial-code-generator/security/vulnerabilities.json';
        
        $current_db = $this->vulnerability_db;
        $current_db[] = $vulnerability_data;
        
        file_put_contents($db_path, wp_json_encode($current_db, JSON_PRETTY_PRINT));
        $this->vulnerability_db = $current_db;
    }

    public function edit_vulnerability($index, $vulnerability_data) {
        $upload_dir = wp_upload_dir();
        $db_path = trailingslashit($upload_dir['basedir']) . 'adversarial-code-generator/security/vulnerabilities.json';
        
        $current_db = $this->vulnerability_db;
        if (isset($current_db[$index])) {
            $current_db[$index] = array_merge($current_db[$index], $vulnerability_data);
            file_put_contents($db_path, wp_json_encode($current_db, JSON_PRETTY_PRINT));
            $this->vulnerability_db = $current_db;
            return true;
        }
        return false;
    }

    public function delete_vulnerability($index) {
        $upload_dir = wp_upload_dir();
        $db_path = trailingslashit($upload_dir['basedir']) . 'adversarial-code-generator/security/vulnerabilities.json';
        
        $current_db = $this->vulnerability_db;
        if (isset($current_db[$index])) {
            array_splice($current_db, $index, 1);
            file_put_contents($db_path, wp_json_encode($current_db, JSON_PRETTY_PRINT));
            $this->vulnerability_db = $current_db;
            return true;
        }
        return false;
    }

    public function sanitize_input($input, $type = 'text') {
        switch ($type) {
            case 'email':
                return sanitize_email($input);
            case 'url':
                return esc_url_raw($input);
            case 'int':
                return absint($input);
            case 'textarea':
                return sanitize_textarea_field($input);
            case 'html':
                return wp_kses_post($input);
            case 'filename':
                return sanitize_file_name($input);
            default: // 'text'
                return sanitize_text_field($input);
        }
    }
}
