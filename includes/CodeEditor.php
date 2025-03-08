class CodeEditor {
    public function __construct() {
        add_action('wp_enqueue_scripts', [$this, 'enqueue_assets']);
        add_action('admin_enqueue_scripts', [$this, 'enqueue_assets']);
        add_action('wp_ajax_adversarial_save_code_snippet', [$this, 'ajax_save_code_snippet']);
        add_action('wp_ajax_nopriv_adversarial_save_code_snippet', [$this, 'ajax_save_code_snippet']); // For front-end forms if needed
        add_action('wp_ajax_adversarial_load_code_snippet', [$this, 'ajax_load_code_snippet']);
        add_action('wp_ajax_nopriv_adversarial_load_code_snippet', [$this, 'ajax_load_code_snippet']); // For front-end forms if needed
        add_action('wp_ajax_adversarial_load_code_snippet_list', [$this, 'ajax_load_code_snippet_list']); // Add action for loading snippet list
        add_action('wp_ajax_nopriv_adversarial_load_code_snippet_list', [$this, 'ajax_load_code_snippet_list']); // For front-end
        add_action('wp_ajax_adversarial_delete_code_snippet', [$this, 'ajax_delete_code_snippet']); // Add action for deleting snippet
        add_action('wp_ajax_nopriv_adversarial_delete_code_snippet', [$this, 'ajax_delete_code_snippet']); // For front-end
        add_action('wp_ajax_adversarial_update_code_snippet', [$this, 'ajax_update_code_snippet']);
        add_action('wp_ajax_nopriv_adversarial_update_code_snippet', [$this, 'ajax_update_code_snippet']); // For front-end
    }

    public function enqueue_assets() {
        wp_enqueue_style('adversarial-ace-editor', plugins_url('assets/css/ace-editor.css', __FILE__));
        wp_enqueue_script('jquery'); // Ensure jQuery is enqueued
        wp_enqueue_script('adversarial-ace-editor', plugins_url('assets/js/ace/ace.js', __FILE__), [], ADVERSARIAL_VERSION, true);
        wp_enqueue_script('adversarial-ace-mode-python', plugins_url('assets/js/ace/mode-python.js', __FILE__), ['adversarial-ace-editor'], ADVERSARIAL_VERSION, true);
        wp_enqueue_script('adversarial-ace-mode-javascript', plugins_url('assets/js/ace/mode-javascript.js', __FILE__), ['adversarial-ace-editor'], ADVERSARIAL_VERSION, true);
        wp_enqueue_script('adversarial-ace-mode-java', plugins_url('assets/js/ace/mode-java.js', __FILE__), ['adversarial-ace-editor'], ADVERSARIAL_VERSION, true);
        wp_enqueue_script('adversarial-ace-mode-php', plugins_url('assets/js/ace/mode-php.js', __FILE__), ['adversarial-ace-editor'], ADVERSARIAL_VERSION, true);
        wp_enqueue_script('adversarial-ace-theme-monokai', plugins_url('assets/js/ace/theme-monokai.js', __FILE__), ['adversarial-ace-editor'], ADVERSARIAL_VERSION, true);
        wp_enqueue_script('adversarial-ace-theme-github', plugins_url('assets/js/ace/theme-github.js', __FILE__), ['adversarial-ace-editor'], ADVERSARIAL_VERSION, true);
        wp_enqueue_script('adversarial-ace-theme-dracula', plugins_url('assets/js/ace/theme-dracula.js', __FILE__), ['adversarial-ace-editor'], ADVERSARIAL_VERSION, true);
        wp_enqueue_script('adversarial-ace-theme-eclipse', plugins_url('assets/js/ace/theme-eclipse.js', __FILE__), ['adversarial-ace-editor'], ADVERSARIAL_VERSION, true);
        wp_enqueue_script('adversarial-jshint', plugins_url('assets/js/ace/jshint.js', __FILE__), [], ADVERSARIAL_VERSION, true); // Enqueue JSHint
        wp_enqueue_script('adversarial-ace-language-tools', plugins_url('assets/js/ace/ext-language_tools.js', __FILE__), ['adversarial-ace-editor'], ADVERSARIAL_VERSION, true); // Enqueue language_tools
        wp_enqueue_script('adversarial-code-editor', plugins_url('assets/js/code-editor.js', __FILE__), ['adversarial-ace-editor', 'jquery', 'adversarial-jshint', 'adversarial-ace-language-tools'], ADVERSARIAL_VERSION, true);

        // Pass settings to JavaScript
        $settings = [
            'defaultLanguage' => 'python',
            'theme' => get_user_meta(get_current_user_id(), 'adversarial_editor_theme', true) ?: 'monokai',
            'ajax_url' => admin_url('admin-ajax.php'),
            'save_nonce' => wp_create_nonce('adversarial_save_code_nonce'),
            'load_nonce' => wp_create_nonce('adversarial_load_code_nonce'), // Add nonce for loading
            'load_list_nonce' => wp_create_nonce('adversarial_load_code_list_nonce'), // Nonce for loading list
            'delete_nonce' => wp_create_nonce('adversarial_delete_code_nonce'), // Nonce for delete
            'update_nonce' => wp_create_nonce('adversarial_update_code_nonce'), // Nonce for update
            'modes' => ['python', 'javascript', 'java', 'php'], // Available modes
            'themes' => ['monokai', 'github', 'dracula', 'eclipse'] // Available themes
        ];
        wp_localize_script('adversarial-code-editor', 'adversarialEditorSettings', $settings);
    }

    public static function create_table() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'adversarial_code_snippets';
        $charset_collate = $wpdb->get_charset_collate();

        $sql = "CREATE TABLE $table_name (
            id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            code longtext NOT NULL,
            language varchar(255) NOT NULL,
            user_id bigint(20) UNSIGNED NOT NULL,
            created_at timestamp DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY  (id),
            KEY user_id (user_id),
            KEY language (language)
        ) $charset_collate;";

        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    }


    public function render_editor($editor_id, $language = 'python', $theme = 'monokai', $height = '400px', $snippet_id = null) {
        $initial_code = '';
        if ($snippet_id) {
            $initial_code = $this->load_code_snippet_from_db($snippet_id);
            if ($initial_code === false) {
                $initial_code = '/* Error loading code snippet */'; // Handle loading error
    }
        
        if (isset($_POST['format_code']) && $_POST['format_code'] === '1') {
            $formatted_code = '';
            $enabled_languages = get_option('adversarial_settings')['code_formatter_languages'] ?: ['php', 'javascript', 'css', 'html'];
            if (in_array($language, $enabled_languages)) {
                switch ($language) {
                    case 'php':
                        $formatted_code = $formatter->format_php($code);
                        break;
                    case 'javascript':
                    case 'css':
                    case 'html':
                        $formatted_code = $formatter->{'format_' . $language}($code);
                        break;
                    default:
                        $formatted_code = $code; // No formatting for unknown languages
                }
            } else {
                $formatted_code = $code; // Formatting disabled for this language
            }
            wp_send_json_success(['formatted_code' => $formatted_code]);
        } else {
            wp_send_json_success(['code' => $code]);
        }
    }
}
        ob_start(); ?>
        <div class="adversarial-code-editor-wrapper" id="<?php echo esc_attr($editor_id); ?>" data-snippet-id="<?php echo esc_attr($snippet_id); ?>">
            <div class="code-editor-controls">
                <button class="button button-primary adversarial-save-code" data-editor-id="<?php echo esc_attr($editor_id); ?>">Save Code</button>
                <button class="button adversarial-load-code" data-editor-id="<?php echo esc_attr($editor_id); ?>">Load Code</button>
                <button class="button adversarial-load-list-code" data-editor-id="<?php echo esc_attr($editor_id); ?>">Load List</button>
                <button class="button adversarial-delete-code" data-editor-id="<?php echo esc_attr($editor_id); ?>">Delete Code</button>
                <button class="button adversarial-clear-code" data-editor-id="<?php echo esc_attr($editor_id); ?>">Clear Code</button>
                <button class="button adversarial-copy-code" data-editor-id="<?php echo esc_attr($editor_id); ?>">Copy Code</button>
                <button class="button adversarial-download-code" data-editor-id="<?php echo esc_attr($editor_id); ?>">Download Code</button>
                <button class="button adversarial-find-code" data-editor-id="<?php echo esc_attr($editor_id); ?>">Find</button>
                <button class="button adversarial-replace-code" data-editor-id="<?php echo esc_attr($editor_id); ?>">Replace</button>
                <button class="button adversarial-undo-code" data-editor-id="<?php echo esc_attr($editor_id); ?>">Undo</button>
                <button class="button adversarial-redo-code" data-editor-id="<?php echo esc_attr($editor_id); ?>">Redo</button>
                <button class="button adversarial-lint-code" data-editor-id="<?php echo esc_attr($editor_id); ?>">Lint Code</button>

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
                <!-- Add other controls here later -->
            </div>
            <div class="code-editor-container" data-language="<?php echo esc_attr($language); ?>" data-theme="<?php echo esc_attr($theme); ?>" style="height: <?php echo esc_attr($height); ?>">
                <?php echo esc_html($initial_code); ?>
            </div>
            <input type="hidden" class="code-editor-value" name="<?php echo esc_attr($editor_id); ?>" value="<?php echo esc_attr($initial_code); ?>">
        </div>
        <?php
        return ob_get_clean();
    }

    public function ajax_save_code_snippet() {
        check_ajax_referer('adversarial_save_code_nonce', 'nonce');

        $code = isset($_POST['code']) ? wp_kses_post(stripslashes($_POST['code'])) : ''; // Basic sanitization for now
        $language = isset($_POST['language']) ? sanitize_text_field($_POST['language']) : 'python';
        $snippet_id = isset($_POST['snippet_id']) ? intval($_POST['snippet_id']) : 0; // Check if snippet_id is sent for updates

        if (empty($code)) {
            wp_send_json_error(['message' => 'Code cannot be empty.']);
        }

        if ($snippet_id) {
            // Update existing snippet
            $updated_snippet_id = $this->update_code_snippet_to_db($snippet_id, $code, $language, get_current_user_id());
            if ($updated_snippet_id) {
                wp_send_json_success(['message' => 'Code updated successfully!', 'snippet_id' => $updated_snippet_id]);
            } else {
                wp_send_json_error(['message' => 'Failed to update code.']);
            }
        } else {
            // Save new snippet
            $new_snippet_id = $this->save_code_snippet_to_db($code, $language, get_current_user_id());
            if ($new_snippet_id) {
                wp_send_json_success(['message' => 'Code saved successfully!', 'snippet_id' => $new_snippet_id]);
            } else {
                wp_send_json_error(['message' => 'Failed to save code.']);
            }
        }
    }


    private function save_code_snippet_to_db($code, $language, $user_id) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'adversarial_code_snippets';

        $data = [
            'code' => $code,
            'language' => $language,
            'user_id' => $user_id,
        ];

        $format = [
            '%s', // code (longtext)
            '%s', // language (varchar)
            '%d', // user_id (bigint)
        ];

        $result = $wpdb->insert($table_name, $data, $format);

        if (is_wp_error($result)) {
            error_log('Database error saving code snippet: ' . $result->get_error_message());
            return false;
        }

        return $wpdb->insert_id;
    }

    private function update_code_snippet_to_db($snippet_id, $code, $language, $user_id) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'adversarial_code_snippets';

        $data = [
            'code' => $code,
            'language' => $language,
        ];

        $format = [
            '%s', // code (longtext)
            '%s', // language (varchar)
        ];

        $where = [
            'id' => $snippet_id,
            'user_id' => $user_id,
        ];
        $where_format = [
            '%d', // id
            '%d', // user_id
        ];

        $result = $wpdb->update($table_name, $data, $where, $format, $where_format);

        if (is_wp_error($result)) {
            error_log('Database error updating code snippet: ' . $result->get_error_message());
            return false;
        }

        return $snippet_id; // Return snippet ID on successful update
    }

    public function ajax_load_code_snippet() {
        check_ajax_referer('adversarial_load_code_nonce', 'nonce');

        $snippet_id = isset($_POST['snippet_id']) ? intval($_POST['snippet_id']) : 0;

        if (!$snippet_id) {
            wp_send_json_error(['message' => 'Snippet ID is missing.']);
            return;
        }

        $code = $this->load_code_snippet_from_db($snippet_id);

        if ($code !== false) {
            wp_send_json_success(['code' => $code]);
        } else {
            wp_send_json_error(['message' => 'Failed to load code snippet.']);
        }
    }

    private function load_code_snippet_from_db($snippet_id) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'adversarial_code_snippets';

        $snippet = $wpdb->get_row($wpdb->prepare("SELECT code FROM $table_name WHERE id = %d", $snippet_id));

        if ($snippet && isset($snippet->code)) {
            return $snippet->code;
        } else {
            return false;
        }
    }

    public function ajax_load_code_snippet_list() {
        check_ajax_referer('adversarial_load_code_list_nonce', 'nonce');

        $snippets = $this->load_code_snippet_list_from_db(get_current_user_id());

        if ($snippets !== false) {
            wp_send_json_success(['snippets' => $snippets]);
        } else {
            wp_send_json_error(['message' => 'Failed to load code snippet list.']);
        }
    }

    private function load_code_snippet_list_from_db($user_id) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'adversarial_code_snippets';

        $snippets = $wpdb->get_results($wpdb->prepare("SELECT id, language, created_at FROM $table_name WHERE user_id = %d ORDER BY created_at DESC", $user_id));

        if ($snippets) {
            return $snippets;
        } else {
            return false;
        }
    }

    public function ajax_delete_code_snippet() {
        check_ajax_referer('adversarial_delete_code_nonce', 'nonce');

        $snippet_id = isset($_POST['snippet_id']) ? intval($_POST['snippet_id']) : 0;

        if (!$snippet_id) {
            wp_send_json_error(['message' => 'Snippet ID is missing.']);
            return;
        }

        $deleted = $this->delete_code_snippet_from_db($snippet_id, get_current_user_id());

        if ($deleted) {
            wp_send_json_success(['message' => 'Code snippet deleted successfully!']);
        } else {
            wp_send_json_error(['message' => 'Failed to delete code snippet.']);
        }
    }

    private function delete_code_snippet_from_db($snippet_id, $user_id) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'adversarial_code_snippets';

        $result = $wpdb->delete(
            $table_name,
            ['id' => $snippet_id, 'user_id' => $user_id],
            ['%d', '%d']
        );

        return $result !== false;
    }

    public function ajax_update_code_snippet() {
        check_ajax_referer('adversarial_update_code_nonce', 'nonce');

        $snippet_id = isset($_POST['snippet_id']) ? intval($_POST['snippet_id']) : 0;
        $code = isset($_POST['code']) ? wp_kses_post(stripslashes($_POST['code'])) : ''; // Basic sanitization
        $language = isset($_POST['language']) ? sanitize_text_field($_POST['language']) : 'python';


        if (!$snippet_id) {
            wp_send_json_error(['message' => 'Snippet ID is missing for update.']);
            return;
        }

        if (empty($code)) {
            wp_send_json_error(['message' => 'Code cannot be empty.']);
            return;
        }


        $updated_snippet_id = $this->update_code_snippet_to_db($snippet_id, $code, $language, get_current_user_id());

        if ($updated_snippet_id) {
            wp_send_json_success(['message' => 'Code snippet updated successfully!', 'snippet_id' => $updated_snippet_id]);
        } else {
            wp_send_json_error(['message' => 'Failed to update code snippet.']);
        }
    }
}
