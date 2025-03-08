class CodeReview {
    public function __construct() {
        add_shortcode('adversarial_code_review', [$this, 'code_review_shortcode']);
    }

    public function generate_review($code, $language = 'python') {
        $prompt = "Perform a comprehensive code review for the following " . $language . " code:\n\n" . $code . 
                  "\n\nProvide feedback on code quality, security, performance, and adherence to best practices.";
        
        $llm_handler = new LLMHandler();
        return $llm_handler->call_llm_api($llm_handler->select_model('review'), $prompt, 'generate_review', $language);
    }

    public function code_review_shortcode($atts) {
        ob_start(); ?>
        <div class="adversarial-code-review">
            <h2><?php esc_html_e('Code Review', 'adversarial-code-generator'); ?></h2>
            <form method="post" class="code-review-form">
                <?php wp_nonce_field('adversarial_code_review', 'adversarial_nonce'); ?>
                <div class="form-group">
                    <label for="code_to_review"><?php esc_html_e('Code to Review:', 'adversarial-code-generator'); ?></label>
                    <div class="code-editor-wrapper code-to-review" data-language="python" data-theme="monokai" style="height: 300px;">
                        <textarea id="code_to_review" name="code_to_review_value" class="code-editor code-to-review-editor" rows="10" style="height: 300px;"></textarea>
                    </div>
                    <input type="hidden" id="code_to_review_value" name="code_to_review_value" class="code-editor-value" value="">
                </div>
                <script>
                jQuery(document).ready(function($) {
                    var codeToReviewEditor = ace.edit($('.code-to-review-editor-wrapper .code-editor')[0]);
                    codeToReviewEditor.setTheme("ace/theme/monokai");
                    codeToReviewEditor.session.setMode("ace/mode/python");
                    
                    // Update hidden field on form submit - important for form submission to work with Ace editor
                    $('.code-review-form').on('submit', function(e) {
                        $('#code_to_review_value').val(codeToReviewEditor.getValue());
                    });
                });
                </script>
                <div class="form-group">
                    <label for="language"><?php esc_html_e('Programming Language:', 'adversarial-code-generator'); ?></label>
                    <select id="language" name="language" class="regular-text">
                        <option value="python" selected>Python</option>
                        <option value="javascript">JavaScript</option>
                        <option value="java">Java</option>
                        <option value="php">PHP</option>
                    </select>
                </div>
                <button type="submit" class="button button-primary"><?php esc_html_e('Generate Review', 'adversarial-code-generator'); ?></button>
                <div class="loading-indicator" style="display: none;">
                    <p><?php esc_html_e('Generating code review... Please wait.', 'adversarial-code-generator'); ?></p>
                </div>
            </form>
            
            <?php
            if (isset($_POST['code_to_review_value']) && wp_verify_nonce($_POST['adversarial_nonce'], 'adversarial_code_review')) {
                try {
                    $code = sanitize_textarea_field($_POST['code_to_review_value']);
                    $language = isset($_POST['language']) ? sanitize_text_field($_POST['language']) : 'python';
                    
                    $review = $this->generate_review($code, $language);
                    
                    echo '<div class="notice notice-success"><p>' . esc_html__('Code review generated successfully!', 'adversarial-code-generator') . '</p></div>';
                    echo '<div class="review-results"><pre>' . esc_html($review) . '</pre></div>';
                } catch (Exception $e) {
                    echo '<div class="notice notice-error"><p>' . esc_html__('Review generation failed: ', 'adversarial-code-generator') . esc_html($e->getMessage()) . '</p></div>';
                }
            }
            ?>
        </div>
        <?php
        return ob_get_clean();
        }
        }
