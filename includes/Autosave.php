<?php
class Autosave {
    private $table_name;
    private $version = '1.0';
    private $autosave_interval = 60; // seconds
    /**
     * @var Database Database instance for data operations.
     */
    private $db;

    public function __construct() {
        $this->db = Database::get_instance();
        $this->table_name = $this->db->get_table_name('adversarial_autosaves');
        register_activation_hook(__FILE__, [$this, 'install']);
        add_action('admin_enqueue_scripts', [$this, 'enqueue_scripts']);
        add_action('wp_ajax_adversarial_save_autosave', [$this, 'ajax_save_autosave']);
        add_action('wp_ajax_nopriv_adversarial_save_autosave', [$this, 'ajax_save_autosave']);
        add_action('wp_ajax_adversarial_restore_autosave', [$this, 'ajax_restore_autosave']);
        add_action('wp_ajax_nopriv_adversarial_restore_autosave', [$this, 'ajax_restore_autosave']);
        add_action('user_register', [$this, 'create_user_autosave']);
        add_action('wp_logout', [$this, 'cleanup_user_autosaves']);
    }

    public function install() {
        $table_name = $this->table_name;
        $charset_collate = $this->db->wpdb->get_charset_collate();
        $sql = "CREATE TABLE $table_name (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            user_id BIGINT UNSIGNED NOT NULL,
            editor_id VARCHAR(255) NOT NULL,
            code LONGTEXT NOT NULL,
            language VARCHAR(50) NOT NULL,
            timestamp TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY user_id (user_id),
            KEY editor_id (editor_id)
        ) $charset_collate;";
        $this->db->install_table($table_name, $sql);
    }

    public function enqueue_scripts() {
        wp_enqueue_script('adversarial-autosave', plugins_url('assets/js/autosave.js', __FILE__), ['jquery'], $this->version, true);
        wp_localize_script('adversarial-autosave', 'autosaveSettings', [
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce_save' => wp_create_nonce('autosave_nonce')
        ]);
    }

    public function ajax_save_autosave() {
        check_ajax_referer('autosave_nonce', 'nonce');
        if (!isset($_POST['editor_id']) || !isset($_POST['code']) || !isset($_POST['language'])) {
            wp_send_json_error(['message' => 'Missing required parameters']);
        }
        $editor_id = sanitize_text_field($_POST['editor_id']);
        $code = wp_kses_post(stripslashes($_POST['code']));
        $language = sanitize_key($_POST['language']);
        $this->db->insert(
            $this->table_name,
            [
                'user_id' => get_current_user_id(),
                'editor_id' => $editor_id,
                'code' => $code,
                'language' => $language
            ],
            [
                '%d',
                '%s',
                '%s',
                '%s'
            ]
        );
        wp_send_json_success();
    }

    public function ajax_restore_autosave() {
        check_ajax_referer('autosave_nonce', 'nonce');
        $editor_id = sanitize_text_field($_POST['editor_id']);
        $result = $this->db->get_row(
            $this->table_name,
            $this->db->wpdb->prepare(
                "SELECT code, language FROM $this->table_name 
                WHERE user_id = %d AND editor_id = %s 
                ORDER BY timestamp DESC LIMIT 1",
                get_current_user_id(),
                $editor_id
            )
        );
        if ($result) {
            wp_send_json_success([
                'code' => $result->code,
                'language' => $result->language
            ]);
        } else {
            wp_send_json_error(['message' => 'No autosave found']);
        }
    }

    public function ajax_delete_autosave() { // Add delete autosave functionality
        check_ajax_referer('autosave_nonce', 'nonce');
        if (!isset($_POST['editor_id'])) {
            wp_send_json_error(['message' => 'Missing required parameters']);
        }
        $editor_id = sanitize_text_field($_POST['editor_id']);
        $result = $this->db->delete(
            $this->table_name,
            [
                'user_id' => get_current_user_id(),
                'editor_id' => $editor_id,
            ],
            [
                '%d',
                '%s'
            ]
        );
        if ($result) {
            wp_send_json_success(['message' => 'Autosave deleted successfully']);
        } else {
            wp_send_json_error(['message' => 'Failed to delete autosave']);
        }
    }


    public function create_user_autosave($user_id) {
        $this->db->insert(
            $this->table_name,
            [
                'user_id' => $user_id,
                'editor_id' => 'default',
                'code' => '',
                'language' => 'php'
            ],
            [
                '%d',
                '%s',
                '%s',
                '%s'
            ]
        );
    }

    public function cleanup_user_autosaves() {
        $this->db->delete(
            $this->table_name,
            [
                'user_id' => get_current_user_id(),
            ],
            [
                '%d'
            ],
            "timestamp < DATE_SUB(NOW(), INTERVAL 1 DAY)"
        );
    }
}
new Autosave();
