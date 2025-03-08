<?php
class CodeDiffViewer
{
    public function __construct()
    {
        add_shortcode('adversarial_code_diff', [$this, 'code_diff_shortcode']);
        add_action('wp_enqueue_scripts', [$this, 'enqueue_assets']);
    }

    public function enqueue_assets()
    {
        wp_enqueue_style('adversarial-ace-editor', plugins_url('assets/css/ace-editor.css', __FILE__));
        wp_enqueue_style('diff2html-css', plugins_url('assets/js/diff2html.min.css', __FILE__), [], ADVERSARIAL_VERSION);
        wp_enqueue_script('jquery');
        wp_enqueue_script('adversarial-ace-editor', plugins_url('assets/js/ace/ace.js', __FILE__), [], ADVERSARIAL_VERSION, true);
        wp_enqueue_script('adversarial-ace-mode-python', plugins_url('assets/js/ace/mode-python.js', __FILE__), ['adversarial-ace-editor'], ADVERSARIAL_VERSION, true);
        wp_enqueue_script('diff2html-lib', plugins_url('assets/js/diff2html-lib.js', __FILE__), [], ADVERSARIAL_VERSION, true);
        wp_enqueue_script('adversarial-code-diff-viewer', plugin_dir_url(__FILE__) . '../Assets/js/code-diff-viewer.js', ['jquery', 'adversarial-ace-editor', 'diff2html-lib'], '1.0', true);
    }

    public function code_diff_shortcode($atts)
    {
        ob_start();
        ?>
        <div class="adversarial-code-diff-viewer">
            <h2><?php esc_html_e('Code Diff Viewer', 'adversarial-code-generator'); ?></h2>
            <div class="code-comparison-container">
                <div class="code-pane original-code-pane">
                    <h3><?php esc_html_e('Original Code', 'adversarial-code-generator'); ?></h3>
                    <div class="code-editor-wrapper original-code-editor-wrapper" data-language="python" data-theme="monokai">
                        <textarea class="code-editor original-code-editor"></textarea>
                    </div>
                </div>
                <div class="code-pane modified-code-pane">
                    <h3><?php esc_html_e('Modified Code', 'adversarial-code-generator'); ?></h3>
                    <div class="code-editor-wrapper modified-code-editor-wrapper" data-language="python" data-theme="monokai">
                        <textarea class="code-editor modified-code-editor"></textarea>
                    </div>
                </div>
            </div>
            <div class="diff-controls">
                <button class="button button-primary generate-diff-button"><?php esc_html_e('Generate Diff', 'adversarial-code-generator'); ?></button>
            </div>
            <div class="diff-output-container">
                <!-- Diff output will be rendered here -->
            </div>
        </div>
        <?php
        return ob_get_clean();
    }
}
?>
