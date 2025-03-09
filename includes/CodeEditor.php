<?php
class CodeEditor {
    /**
     * @var Database Database instance for data operations.
     */
    private $db;
    private $version = '1.0';

    public function __construct() {
        $this->db = Database::get_instance();
        add_action('wp_enqueue_scripts', [$this, 'enqueue_assets']);
        add_action('admin_enqueue_scripts', [$this, 'enqueue_assets']);
        add_action('wp_ajax_adversarial_save_code_snippet', [$this, 'ajax_save_code_snippet']);
        add_action('wp_ajax_nopriv_adversarial_save_code_snippet', [$this, 'ajax_save_code_snippet']);
        add_action('wp_ajax_adversarial_load_code_snippet', [$this, 'ajax_load_code_snippet']);
        add_action('wp_ajax_nopriv_adversarial_load_code_snippet', [$this, 'ajax_load_code_snippet']);
        add_action('wp_ajax_adversarial_load_code_snippet_list', [$this, 'ajax_load_code_snippet_list']);
        add_action('wp_ajax_nopriv_adversarial_load_code_snippet_list', [$this, 'ajax_load_code_snippet_list']);
        add_action('wp_ajax_adversarial_delete_code_snippet', [$this, 'ajax_delete_code_snippet']);
        add_action('wp_ajax_nopriv_adversarial_delete_code_snippet', [$this, 'ajax_delete_code_snippet']);
        add_action('wp_ajax_adversarial_update_code_snippet', [$this, 'ajax_update_code_snippet']);
        add_action('wp_ajax_nopriv_adversarial_update_code_snippet', [$this, 'ajax_update_code_snippet']);
        add_action('wp_ajax_adversarial_format_code_snippet', [$this, 'ajax_format_code_snippet']);
        add_action('wp_ajax_nopriv_adversarial_format_code_snippet', [$this, 'ajax_format_code_snippet']);
        add_action('wp_ajax_adversarial_code_autocomplete', [$this, 'ajax_code_autocomplete']);
        add_action('wp_ajax_nopriv_adversarial_code_autocomplete', [$this, 'ajax_code_autocomplete']);
        add_action('wp_ajax_adversarial_lint_code', [$this, 'ajax_lint_code']);
        add_action('wp_ajax_nopriv_adversarial_lint_code', [$this, 'ajax_lint_code']);
    }

    public function enqueue_assets() {
        wp_enqueue_style('adversarial-ace-editor', plugins_url('assets/css/ace-editor.css', __FILE__));
        wp_enqueue_script('jquery');
        wp_enqueue_script('adversarial-ace-editor', plugins_url('assets/js/ace/ace.js', __FILE__), [], ADVERSARIAL_VERSION, true);
        wp_enqueue_script('adversarial-code-editor', plugins_url('assets/js/code-editor.js', __FILE__), ['adversarial-ace-editor', 'jquery', 'adversarial-jshint', 'adversarial-ace-language-tools'], ADVERSARIAL_VERSION, true);

        $settings = [
            'defaultLanguage' => 'python',
            'theme' => get_user_meta(get_current_user_id(), 'adversarial_editor_theme', true) ?: 'monokai',
            'ajax_url' => admin_url('admin-ajax.php'),
            'save_nonce' => wp_create_nonce('adversarial_save_code_nonce'),
            'load_nonce' => wp_create_nonce('adversarial_load_code_nonce'),
            'load_list_nonce' => wp_create_nonce('adversarial_load_code_list_nonce'),
            'delete_nonce' => wp_create_nonce('adversarial_delete_code_nonce'),
            'update_nonce' => wp_create_nonce('adversarial_update_code_nonce'),
            'format_nonce' => wp_create_nonce('adversarial_format_code_nonce'),
            'autocomplete_nonce' => wp_create_nonce('adversarial_code_autocomplete_nonce'),
            'lint_nonce' => wp_create_nonce('adversarial_lint_code_nonce'),
            'modes' => ['python', 'javascript', 'java', 'php'],
            'themes' => ['monokai', 'github', 'dracula', 'eclipse']
        ];
        wp_localize_script('adversarial-code-editor', 'adversarialEditorSettings', $settings);
    }

    public static function create_table() {
        $table_name = $this->db->get_table_name('adversarial_code_snippets');
        $charset_collate = $this->db->wpdb->get_charset_collate();

        $sql = "CREATE TABLE {$table_name} (
            id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            code longtext NOT NULL,
            language varchar(255) NOT NULL,
            user_id bigint(20) UNSIGNED NOT NULL,
            created_at timestamp DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY  (id),
            KEY user_id (user_id),
            KEY language (language)
        ) {$charset_collate};";

        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    }

    public function render_editor($editor_id, $language = 'python', $theme = 'monokai', $height = '400px', $snippet_id = null) {
        $initial_code = '';
        if ($snippet_id) {
            $initial_code_data = $this->load_code_snippet_from_db($snippet_id);
            if ($initial_code_data !== false) {
                $initial_code = $initial_code_data->code;
                $language = $initial_code_data->language;
            } else {
                $initial_code = '/* Error loading code snippet */';
            }
        }

        ob_start();
        ?>
        <div class="adversarial-code-editor-wrapper" id="<?php echo esc_attr($editor_id); ?>" data-snippet-id="<?php echo esc_attr($snippet_id); ?>">
            <div class="code-editor-controls">
                <div class="code-editor-group code-editor-group-save-load">
                    <button class="button button-primary adversarial-save-code" data-editor-id="<?php echo esc_attr($editor_id); ?>"><?php esc_html_e('Save', 'adversarial-code-generator'); ?></button>
                    <button class="button adversarial-load-code" data-editor-id="<?php echo esc_attr($editor_id); ?>"><?php esc_html_e('Load', 'adversarial-code-generator'); ?></button>
                    <button class="button adversarial-load-list-code" data-editor-id="<?php echo esc_attr($editor_id); ?>"><?php esc_html_e('Load List', 'adversarial-code-generator'); ?></button>
                </div>
                <div class="code-editor-group code-editor-group-delete">
                    <button class="button adversarial-delete-code" data-editor-id="<?php echo esc_attr($editor_id); ?>"><?php esc_html_e('Delete', 'adversarial-code-generator'); ?></button>
                </div>
                <div class="code-editor-group code-editor-group-edit">
                    <button class="button adversarial-clear-code" data-editor-id="<?php echo esc_attr($editor_id); ?>"><?php esc_html_e('Clear', 'adversarial-code-generator'); ?></button>
                    <button class="button adversarial-copy-code" data-editor-id="<?php echo esc_attr($editor_id); ?>"><?php esc_html_e('Copy', 'adversarial-code-generator'); ?></button>
                    <button class="button adversarial-download-code" data-editor-id="<?php echo esc_attr($editor_id); ?>"><?php esc_html_e('Download', 'adversarial-code-generator'); ?></button>
                    <button class="button adversarial-find-code" data-editor-id="<?php echo esc_attr($editor_id); ?>"><?php esc_html_e('Find', 'adversarial-code-generator'); ?></button>
                    <button class="button adversarial-replace-code" data-editor-id="<?php echo esc_attr($editor_id); ?>"><?php esc_html_e('Replace', 'adversarial-code-generator'); ?></button>
                </div>
                <div class="code-editor-group code-editor-group-history">
                    <button class="button adversarial-undo-code" data-editor-id="<?php echo esc_attr($editor_id); ?>"><?php esc_html_e('Undo', 'adversarial-code-generator'); ?></button>
                    <button class="button adversarial-redo-code" data-editor-id="<?php echo esc_attr($editor_id); ?>"><?php esc_html_e('Redo', 'adversarial-code-generator'); ?></button>
                </div>
                <div class="code-editor-group code-editor-group-analyze">
                    <button class="button adversarial-lint-code" data-editor-id="<?php echo esc_attr($editor_id); ?>"><?php esc_html_e('Lint', 'adversarial-code-generator'); ?></button>
                    <button class="button adversarial-format-code" data-editor-id="<?php echo esc_attr($editor_id); ?>"><?php esc_html_e('Format', 'adversarial-code-generator'); ?></button>
                </div>

                <div class="code-editor-group code-editor-group-settings">
                    <select class="adversarial-language-select" data-editor-id="<?php echo esc_attr($editor_id); ?>">
                        <?php
                        $modes = ['python', 'javascript', 'java', 'php'];
                        foreach ($modes as $mode) : ?>
                            <option value="<?php echo esc_attr($mode); ?>" <?php selected($language, $mode); ?>><?php echo esc_html(ucfirst($mode)); ?></option>
                        <?php endforeach; ?>
                    </select>
                    <select class="adversarial-theme-select" data-editor-id="<?php echo esc_attr($editor_id); ?>">
                        <?php
                        $themes = ['monokai', 'github', 'dracula', 'eclipse'];
                        foreach ($themes as $available_theme) : ?>
                            <option value="<?php echo esc_attr($available_theme); ?>" <?php selected($theme, $available_theme); ?>><?php echo esc_html(ucfirst($available_theme)); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
            <div class="code-editor-container" data-language="<?php echo esc_attr($language); ?>" data-theme="<?php echo esc_attr($theme); ?>" style="height: <?php echo esc_attr($height); ?>">\n                <textarea class="code-editor"><?php echo esc_textarea($initial_code); ?></textarea>\n            </div>\n            <input type="hidden" class="code-editor-value" name="<?php echo esc_attr($editor_id); ?>" value="<?php echo esc_attr($initial_code); ?>">\n        </div>\n        <?php
        return ob_get_clean();
    }

    private function load_code_snippet_from_db($snippet_id) {
        $table_name = $this->db->get_table_name('adversarial_code_snippets');
        $snippet_id = absint($snippet_id);

        $result = $this->db->get_row(
            $table_name,
            $this->db->wpdb->prepare("SELECT code, language FROM {$table_name} WHERE id = %d", $snippet_id),
            [$snippet_id]
        );

        return $result;
    }

    public function ajax_load_code_snippet() {
        check_ajax_referer('adversarial_load_code_nonce', 'nonce');

        $snippet_id = isset($_POST['snippet_id']) ? intval($_POST['snippet_id']) : 0;

        if (!$snippet_id) {
            wp_send_json_error(['message' => 'No snippet ID provided.']);
            return;
        }

        $code_data = $this->load_code_snippet_from_db($snippet_id);

        if ($code_data) {
            wp_send_json_success(['code' => $code_data->code, 'language' => $code_data->language]);
        } else {
            wp_send_json_error(['message' => 'Failed to load code snippet.']);
        }
    }

    public function ajax_load_code_snippet_list() {
        check_ajax_referer('adversarial_load_code_list_nonce', 'nonce');

        $table_name = $this->db->get_table_name('adversarial_code_snippets');
        $user_id = get_current_user_id();

        $snippets = $this->db->get_results(
            $table_name,
            $this->db->wpdb->prepare("SELECT id, created_at, language FROM {$table_name} WHERE user_id = %d ORDER BY created_at DESC", $user_id),
            [$user_id]
        );

        if ($snippets) {
            wp_send_json_success(['snippets' => $snippets]);
        } else {
            wp_send_json_success(['snippets' => []]);
        }
    }

    public function ajax_delete_code_snippet() {
        check_ajax_referer('adversarial_delete_code_nonce', 'nonce');

        $snippet_id = isset($_POST['snippet_id']) ? intval($_POST['snippet_id']) : 0;

        if (!$snippet_id) {
            wp_send_json_error(['message' => 'No snippet ID provided for deletion.']);
            return;
        }

        $table_name = $this->db->get_table_name('adversarial_code_snippets');
        $result = $this->db->delete(
            $table_name,
            ['id' => $snippet_id],
            ['%d']
        );

        if ($result !== false) {
            wp_send_json_success(['message' => 'Code snippet deleted successfully.']);
        } else {
            wp_send_json_error(['message' => 'Failed to delete code snippet.']);
        }
    }

    public function ajax_update_code_snippet() {
        check_ajax_referer('adversarial_update_code_nonce', 'nonce');

        $snippet_id = isset($_POST['snippet_id']) ? intval($_POST['snippet_id']) : 0;
        $code = isset($_POST['code']) ? wp_kses_post(stripslashes($_POST['code'])) : '';
        $language = isset($_POST['language']) ? sanitize_text_field($_POST['language']) : 'python';

        if (!$snippet_id) {
            wp_send_json_error(['message' => 'No snippet ID provided for update.']);
            return;
        }

        if (empty($code)) {
            wp_send_json_error(['message' => 'Code cannot be empty.']);
            return;
        }

        $table_name = $this->db->get_table_name('adversarial_code_snippets');
        $result = $this->db->update(
            $table_name,
            ['code' => $code, 'language' => $language],
            ['id' => $snippet_id],
            ['%s', '%s'],
            ['%d']
        );

        if ($result !== false) {
            wp_send_json_success(['message' => 'Code snippet updated successfully.']);
        } else {
            wp_send_json_error(['message' => 'Failed to update code snippet.']);
        }
    }


    public function ajax_save_code_snippet() {
        check_ajax_referer('adversarial_save_code_nonce', 'nonce');

        $code = isset($_POST['code']) ? wp_kses_post(stripslashes($_POST['code'])) : '';
        $language = isset($_POST['language']) ? sanitize_text_field($_POST['language']) : 'python';

        if (empty($code)) {
            wp_send_json_error(['message' => 'Code cannot be empty.']);
            return;
        }

        $table_name = $this->db->get_table_name('adversarial_code_snippets');
        $user_id = get_current_user_id();

        $result = $this->db->insert(
            $table_name,
            [
                'code' => $code,
                'language' => $language,
                'user_id' => $user_id,
            ],
            ['%s', '%s', '%d']
        );

        if ($result) {
            wp_send_json_success(['message' => 'Code snippet saved successfully.']);
        } else {
            wp_send_json_error(['message' => 'Failed to save code snippet.']);
        }
    }

    public function ajax_format_code_snippet() {
        check_ajax_referer('adversarial_format_code_nonce', 'nonce');

        $code = isset($_POST['code']) ? wp_kses_post(stripslashes($_POST['code'])) : '';
        $language = isset($_POST['language']) ? sanitize_text_field($_POST['language']) : 'python';

        if (empty($code)) {
            wp_send_json_error(['message' => 'Code cannot be empty for formatting.']);
            return;
        }

        $formatted_code = $this->format_code($code, $language);

        if ($formatted_code) {
            wp_send_json_success(['formatted_code' => $formatted_code]);
        } else {
            wp_send_json_error(['message' => 'Failed to format code snippet.', 'original_code' => $code]);
        }
    }


    public function format_code($code, $language) {
        if ($language === 'php') {
            return $this->format_php_code($code);
        } elseif ($language === 'javascript') {
            return $this->format_javascript_code($code);
        } elseif ($language === 'python') {
            return $this->format_python_code($code);
        } elseif ($language === 'java') {
            return $this->format_java_code($code);
        }
        return $code;
    }


    private function format_php_code($code) {
        if (function_exists('shell_exec') && !empty(shell_exec('which phpcbf'))) {
            $temp_file = tempnam(sys_get_temp_dir(), 'php_code_');
            file_put_contents($temp_file, $code);
            $cmd = 'phpcbf --standard=PSR12 ' . escapeshellarg($temp_file);
            shell_exec($cmd);
            $formatted_code = file_get_contents($temp_file);
            unlink($temp_file);
            return $formatted_code;
        }
        return $code;
    }

    private function format_javascript_code($code) {
        if (function_exists('shell_exec') && !empty(shell_exec('which prettier'))) {
            $temp_file = tempnam(sys_get_temp_dir(), 'js_code_');
            file_put_contents($temp_file, $code);
            $cmd = 'npx prettier --write ' . escapeshellarg($temp_file);
            shell_exec($cmd);
            $formatted_code = file_get_contents($temp_file);
            unlink($temp_file);
            return $formatted_code;
        }
        return $code;
    }

    private function format_python_code($code) {
        if (function_exists('shell_exec') && !empty(shell_exec('which autopep8'))) {
            $temp_file = tempnam(sys_get_temp_dir(), 'py_code_');
            file_put_contents($temp_file, $code);
            $cmd = 'autopep8 --in-place ' . escapeshellarg($temp_file);
            shell_exec($cmd);
            $formatted_code = file_get_contents($temp_file);
            unlink($temp_file);
            return $formatted_code;
        }
        return $code;
    }

    private function format_java_code($code) {
        // This is a placeholder for Java code formatting.
        // In future iterations, integrate a Java formatting tool here.
        // For now, it will just return the unformatted code.
        return $code;
    }


    public function ajax_code_autocomplete() {
        check_ajax_referer('adversarial_code_autocomplete_nonce', 'nonce');

        $code = isset($_POST['code']) ? wp_kses_post(stripslashes($_POST['code'])) : '';
        $language = isset($_POST['language']) ? sanitize_text_field($_POST['language']) : 'python';
        $cursor_pos = isset($_POST['cursor_pos']) ? intval($_POST['cursor_pos']) : 0;

        $suggestions = $this->get_autocomplete_suggestions($code, $language, $cursor_pos);

        if ($suggestions) {
            wp_send_json_success(['suggestions' => $suggestions]);
        } else {
            wp_send_json_success(['suggestions' => []]);
        }
    }


    private function get_autocomplete_suggestions($code, $language, $cursor_pos) {
        $keywords = [];
        if ($language === 'python') {
            $keywords = ['if', 'else', 'for', 'while', 'def', 'class', 'import', 'from', 'try', 'except', 'finally', 'with', 'as', 'assert', 'break', 'continue', 'del', 'elif', 'global', 'in', 'is', 'lambda', 'nonlocal', 'not', 'or', 'pass', 'raise', 'return', 'yield', 'True', 'False', 'None', 'and'];
        } elseif ($language === 'javascript') {
            $keywords = ['function', 'var', 'let', 'const', 'if', 'else', 'for', 'while', 'do', 'switch', 'case', 'break', 'continue', 'debugger', 'return', 'try', 'catch', 'finally', 'throw', 'class', 'extends', 'super', 'import', 'export', 'default', 'this', 'new', 'typeof', 'instanceof', 'in', 'delete', 'void', 'with', 'yield', 'async', 'await', 'true', 'false', 'null', 'undefined'];
        } elseif ($language === 'java') {
            $keywords = ['abstract', 'assert', 'boolean', 'break', 'byte', 'case', 'catch', 'char', 'class', 'const', 'continue', 'default', 'do', 'double', 'else', 'enum', 'extends', 'final', 'finally', 'float', 'for', 'goto', 'if', 'implements', 'import', 'instanceof', 'int', 'interface', 'long', 'native', 'new', 'package', 'private', 'protected', 'public', 'return', 'short', 'static', 'strictfp', 'super', 'switch', 'synchronized', 'this', 'throw', 'throws', 'transient', 'try', 'void', 'volatile', 'while', 'true', 'false', 'null'];
        } elseif ($language === 'php') {
            $keywords = ['__halt_compiler', 'abstract', 'and', 'array', 'as', 'break', 'callable', 'case', 'catch', 'class', 'clone', 'const', 'continue', 'declare', 'default', 'die', 'do', 'echo', 'else', 'elseif', 'empty', 'enddeclare', 'endfor', 'endforeach', 'endif', 'endswitch', 'endwhile', 'eval', 'exit', 'extends', 'final', 'finally', 'for', 'foreach', 'function', 'global', 'goto', 'if', 'implements', 'include', 'include_once', 'instanceof', 'insteadof', 'interface', 'isset', 'list', 'namespace', 'new', 'or', 'print', 'private', 'protected', 'public', 'require', 'require_once', 'return', 'static', 'switch', 'throw', 'trait', 'try', 'unset', 'use', 'var', 'while', 'xor', 'yield', '__CLASS__', '__DIR__', '__FILE__', '__FUNCTION__', '__LINE__', '__METHOD__', '__NAMESPACE__', '__TRAIT__'];
        }
        return $keywords; // For now, just return keywords as suggestions
    }


    public function ajax_lint_code() {
        check_ajax_referer('adversarial_lint_code_nonce', 'nonce');

        $code = isset($_POST['code']) ? wp_kses_post(stripslashes($_POST['code'])) : '';
        $language = isset($_POST['language']) ? sanitize_text_field($_POST['language']) : 'javascript'; // Default to javascript for linting as jshint is included

        $lint_result = $this->lint_code($code, $language);

        if ($lint_result) {
            wp_send_json_success(['lint_errors' => $lint_result]);
        } else {
            wp_send_json_success(['lint_errors' => []]);
        }
    }


    private function lint_code($code, $language) {
        if ($language === 'javascript') {
            return $this->lint_javascript_code($code);
        } elseif ($language === 'php') {
            return $this->lint_php_code($code);
        }
        return []; // No linting for other languages for now
    }


    private function lint_javascript_code($code) {
        if (defined('ADVERSARIAL_JSHINT_PATH') && ADVERSARIAL_JSHINT_PATH != '') {
            $jshint_path = ADVERSARIAL_JSHINT_PATH;
        } else {
            $jshint_path = plugin_dir_path(__FILE__) . 'assets/js/jshint/jshint.js'; // Default path within the plugin
        }


        if (file_exists($jshint_path) && function_exists('shell_exec')) {
            $temp_file = tempnam(sys_get_temp_dir(), 'js_lint_');
            file_put_contents($temp_file, $code);
            $cmd = 'node ' . escapeshellarg($jshint_path) . ' ' . escapeshellarg($temp_file);
             $output = shell_exec($cmd . ' 2>&1'); // Redirect stderr to stdout

            unlink($temp_file);

            if ($output !== null) {
                $lint_errors = [];
                $lines = explode("\n", $output);
                foreach ($lines as $line) {
                    if (strpos($line, 'Error') !== false) {
                        // Example error line: "path/to/file.js:10:5: Error: Unterminated string literal."
                        if (preg_match('/^(.+):(\d+):(\d+): (Error|Warning): (.+)$/', $line, $matches)) {
                            $lint_errors[] = [
                                'row' => intval($matches[2]),    // Line number
                                'column' => intval($matches[3]), // Column number
                                'text' => trim($matches[5]),      // Error message
                                'type' => strtolower($matches[4]) === 'error' ? 'error' : 'warning' // Error type
                            ];
                        }
                    }
                }
                return $lint_errors;
            }
        }
        return [['message' => 'Linting not available or JSHint path not configured.', 'type' => 'warning']];
    }


    private function lint_php_code($code) {
        if (function_exists('shell_exec')) {
            $temp_file = tempnam(sys_get_temp_dir(), 'php_lint_');
            file_put_contents($temp_file, $code);
            $cmd = 'php -l ' . escapeshellarg($temp_file);
            $output = shell_exec($cmd . ' 2>&1'); // Redirect stderr to stdout
            unlink($temp_file);

            if (strpos($output, 'No syntax errors detected') !== false) {
                return [];
            } else {
                $lint_errors = [];
                $lines = explode("\n", $output);
                foreach ($lines as $line) {
                    if (strpos($line, 'Error') !== false) {
                        // Example error line: "path/to/file.js:10:5: Error: Unterminated string literal."
                        if (preg_match('/^(.+):(\d+):(\d+): (Error|Warning): (.+)$/', $line, $matches)) {
                            $lint_errors[] = [
                                'row' => intval($matches[2]),    // Line number
                                'column' => intval($matches[3]), // Column number
                                'text' => trim($matches[5]),      // Error message
                                'type' => strtolower($matches[4]) === 'error' ? 'error' : 'warning' // Error type
                            ];
                        }
                    }
                }
                return $lint_errors;
            }
        }
        return [['message' => 'PHP linting not available (shell_exec disabled).', 'type' => 'warning']];
    }


    public function is_phpcbf_installed() {
        return !empty(shell_exec('which phpcbf'));
    }

    public function is_prettier_installed() {
        return !empty(shell_exec('which prettier'));
    }

    public function is_autopep8_installed() {
        return !empty(shell_exec('which autopep8'));
    }
}

</file_content>
