<?php
class CodeComparison
{
    public function __construct()
    {
        add_shortcode('adversarial_code_comparison', [$this, 'render_comparison_shortcode']);
        add_action('wp_enqueue_scripts', [$this, 'enqueue_assets']);
    }

    public function enqueue_scripts()
    {
        wp_enqueue_script('adversarial-code-comparison', plugin_dir_url(__FILE__) . '../Assets/js/code-comparison.js', ['jquery'], '1.0', true);
    }
    
    public function enqueue_assets()
    {
        wp_enqueue_style('adversarial-ace-editor', plugins_url('assets/css/ace-editor.css', __FILE__));
        wp_enqueue_style('diff2html-css', plugins_url('assets/js/diff2html.min.css', __FILE__), [], '1.0'); // Enqueue diff2html CSS
        wp_enqueue_script('jquery'); // Ensure jQuery is enqueued
        wp_enqueue_script('adversarial-ace-editor', plugins_url('assets/js/ace/ace.js', __FILE__), [], ADVERSARIAL_VERSION, true);
        wp_enqueue_script('adversarial-ace-mode-python', plugins_url('assets/js/ace/mode-python.js', __FILE__), ['adversarial-ace-editor'], ADVERSARIAL_VERSION, true);
        wp_enqueue_script('diff2html-lib', plugins_url('assets/js/diff2html-lib.js', __FILE__), [], ADVERSARIAL_VERSION, true); // Enqueue diff2html lib
        wp_enqueue_script('adversarial-ace-mode-javascript', plugins_url('assets/js/ace/mode-javascript.js', __FILE__), ['adversarial-ace-editor'], ADVERSARIAL_VERSION, true);
        wp_enqueue_script('adversarial-ace-mode-java', plugins_url('assets/js/ace/mode-java.js', __FILE__), ['adversarial-ace-editor'], ADVERSARIAL_VERSION, true);
        wp_enqueue_script('adversarial-ace-mode-php', plugins_url('assets/js/ace/mode-php.js', __FILE__), ['adversarial-ace-editor'], ADVERSARIAL_VERSION, true);
        wp_enqueue_script('adversarial-ace-theme-monokai', plugins_url('assets/js/ace/theme-monokai.js', __FILE__), ['adversarial-ace-editor'], ADVERSARIAL_VERSION, true);
        wp_enqueue_script('adversarial-ace-theme-github', plugins_url('assets/js/ace/theme-github.js', __FILE__), ['adversarial-ace-editor'], ADVERSARIAL_VERSION, true);
        wp_enqueue_script('adversarial-ace-theme-dracula', plugins_url('assets/js/ace/theme-dracula.js', __FILE__), ['adversarial-ace-editor'], ADVERSARIAL_VERSION, true);
        wp_enqueue_script('adversarial-ace-theme-eclipse', plugins_url('assets/js/ace/theme-eclipse.js', __FILE__), ['adversarial-ace-editor'], ADVERSARIAL_VERSION, true);
        wp_enqueue_script('adversarial-jshint', plugins_url('assets/js/ace/jshint.js', __FILE__), [], ADVERSARIAL_VERSION, true); // Enqueue JSHint
        wp_enqueue_script('adversarial-ace-language-tools', plugins_url('assets/js/ace/ext-language_tools.js', __FILE__), ['adversarial-ace-editor'], ADVERSARIAL_VERSION, true); // Enqueue language_tools
        wp_enqueue_script('adversarial-code-editor', plugins_url('assets/js/code-editor.js', __FILE__), ['adversarial-ace-editor', 'jquery', 'adversarial-jshint', 'adversarial-ace-language-tools'], '1.0', true);
    }
    
    public function render_comparison_shortcode($atts)
    {
        ob_start(); ?>
        <div class="adversarial-code-comparison">
            <div class="comparison-container">
                <div class="comparison-pane">
                    <h3><?php esc_html_e('Original Code', 'adversarial-code-generator'); ?></h3>
                    <div class="code-editor-wrapper original-code" data-language="python" data-theme="monokai">
                        <textarea class="code-editor original-code-editor"></textarea>
                    </div>
                </div>
                <div class="comparison-pane modified-code">
                    <h3><?php esc_html_e('Modified Code', 'adversarial-code-generator'); ?></h3>
                    <div class="code-editor-wrapper modified-code" data-language="python" data-theme="monokai">
                        <textarea class="code-editor modified-code-editor"></textarea>
                    </div>
                </div>
            </div>
            <script>
            jQuery(document).ready(function($) {
                var originalEditor = ace.edit($('.original-code .code-editor-container')[0]);
                originalEditor.setTheme("ace/theme/monokai");
                originalEditor.session.setMode("ace/mode/python");
                var modifiedEditor = ace.edit($('.modified-code .code-editor-container')[0]);
                modifiedEditor.setTheme("ace/theme/monokai");
                modifiedEditor.session.setMode("ace/mode/python");
            });
            </script>
            </div>
            <div class="comparison-controls">
                <button class="button compare-button"><?php esc_html_e('Compare Codes', 'adversarial-code-generator'); ?></button>
            </div>
            <div class="comparison-diff-output">
                <!-- Diff output will be rendered here -->
            </div>
        </div>
        <?php
        return ob_get_clean();
    }
}
