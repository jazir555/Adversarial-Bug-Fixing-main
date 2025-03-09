<?php
/**
 * Class CodeAnalysis
 *
 * Handles code analysis functionality for the plugin, including syntax checking and security analysis.
 */
class CodeAnalysis {
    /**
     * @var string $table_name The name of the database table for storing analysis results.
     */
    private $table_name;

    /**
     * @var string $version The version of the CodeAnalysis class.
     */
    private $version = '1.0';

    /**
     * @var Database Database instance for data operations.
     */
    private $db;

    /**
     * Constructor for the CodeAnalysis class.
     *
     * Initializes the Database instance and sets up the database table name and actions.
     */
    public function __construct() {
        $this->db = Database::get_instance();
        $this->table_name = $this->db->get_table_name('adversarial_code_analysis');
        register_activation_hook(__FILE__, [$this, 'install']);
        add_action('admin_enqueue_scripts', [$this, 'enqueue_scripts']);
        add_action('wp_ajax_adversarial_run_analysis', [$this, 'ajax_run_analysis']);
        add_action('wp_ajax_nopriv_adversarial_run_analysis', [$this, 'ajax_run_analysis']);
        add_action('wp_ajax_adversarial_get_analysis_report', [$this, 'ajax_get_report']);
        add_action('wp_ajax_nopriv_adversarial_get_analysis_report', [$this, 'ajax_get_report']);
    }

    /**
     * Installation function for the CodeAnalysis module.
     *
     * Creates the database table to store code analysis results using Database class.
     */
    public function install() {
        $table_name = $this->table_name;
        $charset_collate = $this->db->wpdb->get_charset_collate();
        $sql = "CREATE TABLE $table_name (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            user_id BIGINT UNSIGNED NOT NULL,
            code_snippet_id BIGINT UNSIGNED NOT NULL,
            analysis_type VARCHAR(50) NOT NULL,
            result LONGTEXT NOT NULL,
            timestamp TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY user_id (user_id),
            KEY code_snippet_id (code_snippet_id)
        ) $charset_collate;";
        $this->db->install_table($table_name, $sql);
    }

    public function enqueue_scripts() {
        wp_enqueue_script('adversarial-code-analysis', plugins_url('assets/js/analysis.js', __FILE__), ['jquery'], $this->version, true);
        wp_localize_script('adversarial-code-analysis', 'analysisSettings', [
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('analysis_nonce')
        ]);
    }

    /**
     * AJAX handler for running code analysis.
     *
     * Retrieves code, language, and analysis type from POST data, performs the analysis,
     * and stores the results in the database using Database class. Responds with success or error in JSON format.
     */
    public function ajax_run_analysis() {
        check_ajax_referer('analysis_nonce', 'nonce');
        if (!isset($_POST['code'], $_POST['language'], $_POST['analysis_type'])) {
            wp_send_json_error(['message' => 'Missing parameters']);
        }
        $code = stripslashes($_POST['code']);
        $language = sanitize_key($_POST['language']);
        $analysis_type = sanitize_key($_POST['analysis_type']);
        $result = $this->perform_analysis($code, $language, $analysis_type);
        if (is_wp_error($result)) {
            wp_send_json_error(['message' => $result->get_error_message()]);
        }
        $this->db->insert(
            $this->table_name,
            [
                'user_id' => get_current_user_id(),
                'code_snippet_id' => isset($_POST['snippet_id']) ? intval($_POST['snippet_id']) : 0,
                'analysis_type' => $analysis_type,
                'result' => maybe_serialize($result)
            ],
            [
                '%d',
                '%d',
                '%s',
                '%s'
            ]
        );
        wp_send_json_success(['analysis_id' => $this->db->wpdb->insert_id]);
    }

    /**
     * AJAX handler for getting a code analysis report.
     *
     * Retrieves the analysis report from the database based on the provided analysis ID using Database class.
     * Responds with the report details or an error message in JSON format.
     */
    public function ajax_get_report() {
        check_ajax_referer('analysis_nonce', 'nonce');
        if (!isset($_POST['analysis_id'])) {
            wp_send_json_error(['message' => 'Missing analysis ID']);
        }
        $analysis_id = intval($_POST['analysis_id']);
        $report = $this->db->get_row(
            $this->table_name,
            $this->db->wpdb->prepare(
                "SELECT result, analysis_type, timestamp FROM $this->table_name WHERE id = %d AND user_id = %d",
                $analysis_id,
                get_current_user_id()
            ),
        );
        if ($report) {
            wp_send_json_success([
                'report' => maybe_unserialize($report->result),
                'analysis_type' => $report->analysis_type,
                'timestamp' => $report->timestamp
            ]);
        } else {
            wp_send_json_error(['message' => 'Report not found']);
        }
    }

    /**
     * Performs code analysis based on the language and analysis type.
     *
     * @param string $code The code to be analyzed.
     * @param string $language The programming language of the code.
     * @param string $type The type of analysis to perform (e.g., 'syntax', 'security', 'lint').
     * @return array|WP_Error Returns the analysis result as an array on success, or WP_Error on failure.
     */
    private function perform_analysis($code, $language, $type) {
        if ($language === 'php') {
            if ($type === 'syntax') {
                return $this->php_syntax_check($code);
            } elseif ($type === 'security') {
                return $this->php_security_analysis($code);
            }
        } elseif ($language === 'javascript') {
            if ($type === 'lint') {
                return $this->javascript_lint($code);
            }
        }
        return new WP_Error('invalid_type', 'Unsupported analysis type for this language');
    }

    /**
     * Performs PHP syntax check using the PHP `lint` command.
     *
     * @param string $code The PHP code to check.
     * @return array Returns the syntax check result as an array.
     */
    private function php_syntax_check($code) {
        $temp_file = tempnam(sys_get_temp_dir(), 'php_analysis_');
        file_put_contents($temp_file, $code);
        $output = shell_exec("php -l $temp_file 2>&1");
        unlink($temp_file);
        if (strpos($output, 'No syntax errors detected') !== false) {
            return ['status' => 'success', 'message' => 'No syntax errors'];
        }
        return ['status' => 'error', 'message' => $output];
    }

    /**
     * Performs basic PHP security analysis (Placeholder).
     *
     * @param string $code The PHP code to analyze for security vulnerabilities.
     * @return array Returns the security analysis result as an array.
     * @todo Implement actual PHP security analysis using tools like RIPS or PHPStan.
     */
    private function php_security_analysis($code) {
        // Placeholder for security checks (e.g., SQLi, XSS)
        // This should be replaced with actual security analysis tools for comprehensive checks.
        return ['status' => 'success', 'message' => 'Security analysis not yet implemented'];
    }

    /**
     * Performs JavaScript linting using JSHint.
     *
     * @param string $code The JavaScript code to lint.
     * @return array Returns the JavaScript linting result as an array.
     */
    private function javascript_lint($code) {
        $jshint_path = plugin_dir_path(__FILE__) . 'assets/js/jshint/jshint.js';
        if (!file_exists($jshint_path) || !function_exists('shell_exec')) {
            return new WP_Error('jshint_not_available', 'JSHint is not available or JSHint path not configured.');
        }
        $temp_file = tempnam(sys_get_temp_dir(), 'js_lint_');
        file_put_contents($temp_file, $code);
        $cmd = 'node ' . escapeshellarg($jshint_path) . ' ' . escapeshellarg($temp_file) . ' 2>&1';
        unlink($temp_file);
        if (empty(trim($output))) {
            return ['status' => 'success', 'message' => 'No lint errors found'];
        }
        return ['status' => 'error', 'message' => $output];
    }
}
new CodeAnalysis();
