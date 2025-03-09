<?php
/**
 * Class CodeDiffViewer
 *
 * Handles displaying code differences in a user-friendly viewer in the admin area.
 * Utilizes the diff2html.js library for rendering HTML diff output.
 */
class CodeDiffViewer {
    /**
     * @var string $version The version of the CodeDiffViewer class.
     */
    private $version = '1.0';

    /**
     * Constructor for the CodeDiffViewer class.
     *
     * @var Database Database instance for data operations.
     */
    private $db;

    /**
     * Constructor for the CodeDiffViewer class.
     *
     * Initializes the Database instance and enqueues necessary scripts for the code diff viewer functionality.
     */
    public function __construct() {
        $this->db = Database::get_instance();
        add_action('admin_enqueue_scripts', [$this, 'enqueue_scripts']);
    }

    /**
     * Enqueue scripts for the admin area.
     *
     * Registers and enqueues the javascript and CSS files required for the code diff viewer,
     * including the diff2html library.
     */
    public function enqueue_scripts() {
        wp_enqueue_style('adversarial-diff2html', plugins_url('assets/js/diff2html.min.css', __FILE__), [], $this->version);
        wp_enqueue_script('adversarial-diff2html-lib', plugins_url('assets/js/diff2html-lib.js', __FILE__), [], $this->version, true);
        wp_enqueue_script('adversarial-code-diff-viewer', plugins_url('assets/js/code-diff-viewer.js', __FILE__), ['jquery', 'adversarial-diff2html-lib'], $this->version, true);
    }

    /**
     * Renders the code diff viewer in the admin area.
     *
     * @param string $diff_html The HTML formatted diff output to display.
     */
    public function render_diff_viewer($diff_html = '') {
        ?>
        <div class="code-diff-viewer-container">
            <div class="diff-output-display comparison-diff-output">
                <?php 
                    // Display diff HTML if provided
                    echo $diff_html; 
                ?>
            </div>
        </div>
        <?php
    }
}
new CodeDiffViewer();
