<?php
/**
 * Class CodeComparison
 *
 * Handles code comparison functionality for the plugin, allowing users to compare 
 * two different versions of code snippets and view the differences.
 */
class CodeComparison {
    /**
     * @var string $version The version of the CodeComparison class.
     */
    private $version = '1.0';

    /**
     * Constructor for the CodeComparison class.
     *
     * @var Database Database instance for data operations.
     */
    private $db;

    /**
     * Constructor for the CodeComparison class.
     *
     * Initializes the Database instance, enqueues scripts and adds AJAX actions for handling code comparison requests.
     */
    public function __construct() {
        $this->db = Database::get_instance();
        add_action('admin_enqueue_scripts', [$this, 'enqueue_scripts']);
        add_action('wp_ajax_adversarial_compare_code', [$this, 'ajax_compare_code']);
        add_action('wp_ajax_nopriv_adversarial_compare_code', [$this, 'ajax_compare_code']);
    }

    /**
     * Enqueue scripts for the admin area.
     *
     * Registers and enqueues the javascript file for code comparison functionality,
     * including the diff2html library.
     */
    public function enqueue_scripts() {
        wp_enqueue_style('adversarial-diff2html', plugins_url('assets/js/diff2html.min.css', __FILE__), [], $this->version);
        wp_enqueue_script('adversarial-diff2html-lib', plugins_url('assets/js/diff2html-lib.js', __FILE__), [], $this->version, true);
        wp_enqueue_script('adversarial-code-comparison', plugins_url('assets/js/code-comparison.js', __FILE__), ['jquery', 'adversarial-diff2html-lib'], $this->version, true);
        wp_localize_script('adversarial-code-comparison', 'codeComparisonSettings', [
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('code_comparison_nonce')
        ]);
    }

    /**
     * Compares two code snippets and returns the HTML diff using diff2html.js.
     *
     * @param string $code1 The first code snippet for comparison.
     * @param string $code2 The second code snippet for comparison.
     * @return string HTML formatted diff output.
     */
    private function compare_code_snippets($code1, $code2) {
        // For now, using a basic PHP diff function as diff2html is primarily a frontend library
        return $this->generate_diff($code1, $code2);
    }


    private function generate_diff($code1, $code2) {
        $lines1 = explode("\n", $code1);
        $lines2 = explode("\n", $code2);
        $diff = '';
        $maxLength = max(count($lines1), count($lines2));
        foreach ($lines1 as $k => $line) {
            if (!isset($lines2[$k])) {
                $diff .= "- " . $line . "\n";
            } elseif ($line != $lines2[$k]) {
                $diff .= "- " . $line . "\n";
                $diff .= "+ " . $lines2[$k] . "\n";
            } else {
                $diff .= "  " . $line . "\n";
            }
        }
         foreach (array_slice($lines2, count($lines1)) as $line) {
            $diff .= "+ " . $line . "\n";
        }
        return $diff;
    }


    /**
     * AJAX handler for comparing code snippets.
     *
     * Handles the AJAX request to compare two code snippets.
     * Retrieves code snippets and language from POST data, performs the comparison,
     * and responds with the diff text output in JSON format.
     */
    public function ajax_compare_code() {
        check_ajax_referer('code_comparison_nonce', 'nonce');
        if (!isset($_POST['code1'], $_POST['code2'])) {
            wp_send_json_error(['message' => 'Missing code snippets for comparison']);
        }
        $code1 = stripslashes($_POST['code1']);
        $code2 = stripslashes($_POST['code2']);

        $diff_output = $this->compare_code_snippets($code1, $code2);

        wp_send_json_success(['diff_text' => $diff_output]);
    }

    // Implement functions to enhance code comparison using diff2html.js on the frontend for richer UI.
}
new CodeComparison();
