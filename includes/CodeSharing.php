<?php
/**
 * Class CodeSharing
 *
 * Handles code sharing functionalities for the plugin, allowing users to share 
 * code snippets with others via unique links or access keys.
 */
class CodeSharing {
    /**
     * @var string $table_name The name of the database table for storing code sharing data.
     */
    private $table_name;

    /**
     * @var string $version The version of the CodeSharing class.
     */
    private $version = '1.0';

    /**
     * Constructor for the CodeSharing class.
     *
     * Sets up the database table name and registers activation hook for database table creation.
     */
    public function __construct() {
        global $wpdb;
        $this->table_name = $wpdb->prefix . 'adversarial_code_sharing';
        register_activation_hook(__FILE__, [$this, 'install']);
    }

    /**
     * Installation function for the CodeSharing module.
     *
     * Creates the database table to store code sharing data.
     * @global wpdb $wpdb WordPress database abstraction object.
     */
    public function install() {
        global $wpdb;
        $charset_collate = $wpdb->get_charset_collate();
        $sql = "CREATE TABLE $this->table_name (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            code_snippet_id BIGINT UNSIGNED NOT NULL,
            sharing_user_id BIGINT UNSIGNED NOT NULL,
            access_key VARCHAR(255) NOT NULL UNIQUE,
            expiry_date TIMESTAMP NULL,
            access_permissions VARCHAR(50) NOT NULL DEFAULT 'read',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY code_snippet_id (code_snippet_id),
            KEY sharing_user_id (sharing_user_id),
            KEY access_key (access_key)
        ) $charset_collate;";
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    }

    /**
     * Shares a code snippet and generates a unique access key.
     *
     * @param int $code_snippet_id The ID of the code snippet to share.
     * @param int $sharing_user_id The ID of the user sharing the code snippet.
     * @param string|null $expiry_date Optional expiry date for the share link.
     * @param string $access_permissions Access permissions for the shared code snippet ('read', 'edit', etc.).
     * @return string|WP_Error Returns the unique access key on success, or WP_Error on failure.
     */
    public function share_code_snippet($code_snippet_id, $sharing_user_id, $expiry_date = null, $access_permissions = 'read') {
        $access_key = wp_generate_password(32, false); // Generate a unique access key
        global $wpdb;
        $result = $wpdb->insert(
            $this->table_name,
            [
                'code_snippet_id' => intval($code_snippet_id),
                'sharing_user_id' => intval($sharing_user_id),
                'access_key' => $access_key,
                'expiry_date' => $expiry_date,
                'access_permissions' => sanitize_key($access_permissions),
            ],
            [
                '%d',
                '%d',
                '%s',
                '%s',
                '%s'
            ]
        );

        if ($result) {
            return $access_key;
        } else {
            return new WP_Error('db_insert_error', 'Failed to share code snippet.', $wpdb->last_error);
        }
    }

    // Implement functions to get, update, delete, and list shared code snippets.
}
new CodeSharing();
