<?php
/**
 * Class CodeSnippets
 *
 * Handles code snippets functionalities for the plugin, allowing users to create, 
 * manage, and reuse code snippets within the code editor.
 */
class CodeSnippets {
    /**
     * @var string $table_name The name of the database table for storing code snippets.
     */
    private $table_name;

    /**
     * @var string $version The version of the CodeSnippets class.
     */
    private $version = '1.0';

    /**
     * Constructor for the CodeSnippets class.
     *
     * Sets up the database table name and registers activation hook for database table creation.
     */
    public function __construct() {
        global $wpdb;
        $this->table_name = $wpdb->prefix . 'adversarial_code_snippets_v2'; // Using v2 to avoid table name conflict with CodeEditor table
        register_activation_hook(__FILE__, [$this, 'install']);
    }

    /**
     * Installation function for the CodeSnippets module.
     *
     * Creates the database table to store code snippets.
     * @global wpdb $wpdb WordPress database abstraction object.
     */
    public function install() {
        global $wpdb;
        $charset_collate = $wpdb->get_charset_collate();
        $sql = "CREATE TABLE $this->table_name (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            user_id BIGINT UNSIGNED NOT NULL,
            name VARCHAR(255) NOT NULL,
            code LONGTEXT NOT NULL,
            language VARCHAR(50) NOT NULL DEFAULT 'python',
            description TEXT NULL,
            tags TEXT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY user_id (user_id),
            KEY language (language),
            FULLTEXT KEY code_content_fulltext (code, description, tags) 
        ) $charset_collate;";
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    }

    /**
     * Creates a new code snippet.
     *
     * @param array $snippet_data Array of code snippet data (name, code, language, description, tags, user_id).
     * @return int|WP_Error Returns the ID of the newly created code snippet on success, or WP_Error on failure.
     */
    public function create_code_snippet($snippet_data) {
        global $wpdb;
        $result = $wpdb->insert(
            $this->table_name,
            [
                'user_id' => intval($snippet_data['user_id']),
                'name' => sanitize_text_field($snippet_data['name']),
                'code' => wp_kses_post($snippet_data['code']),
                'language' => sanitize_key($snippet_data['language']),
                'description' => sanitize_textarea_field($snippet_data['description']),
                'tags' => sanitize_text_field($snippet_data['tags']),
            ],
            [
                '%d',
                '%s',
                '%s',
                '%s',
                '%s',
                '%s'
            ]
        );

        if ($result) {
            return $wpdb->insert_id;
        } else {
            return new WP_Error('db_insert_error', 'Failed to create code snippet.', $wpdb->last_error);
        }
    }

    /**
     * Retrieves a code snippet by ID.
     *
     * @param int $snippet_id The ID of the code snippet to retrieve.
     * @return array|null Returns the code snippet data array on success, or null if not found.
     */
    public function get_code_snippet($snippet_id) {
        global $wpdb;
        return $wpdb->get_row(
            $wpdb->prepare("SELECT * FROM $this->table_name WHERE id = %d", $snippet_id),
            ARRAY_A
        );
    }

    /**
     * Updates an existing code snippet.
     *
     * @param int $snippet_id The ID of the code snippet to update.
     * @param array $snippet_data Array of code snippet data to update (name, code, language, description, tags).
     * @return bool|WP_Error Returns true on success, or WP_Error on failure.
     */
    public function update_code_snippet($snippet_id, $snippet_data) {
        global $wpdb;
        $update_data = [];
        $format = [];
        if (isset($snippet_data['name'])) {
            $update_data['name'] = sanitize_text_field($snippet_data['name']);
            $format[] = '%s';
        }
        if (isset($snippet_data['code'])) {
            $update_data['code'] = wp_kses_post($snippet_data['code']);
            $format[] = '%s';
        }
        if (isset($snippet_data['language'])) {
            $update_data['language'] = sanitize_key($snippet_data['language']);
            $format[] = '%s';
        }
        if (isset($snippet_data['description'])) {
            $update_data['description'] = sanitize_textarea_field($snippet_data['description']);
            $format[] = '%s';
        }
        if (isset($snippet_data['tags'])) {
            $update_data['tags'] = sanitize_text_field($snippet_data['tags']);
            $format[] = '%s';
        }

        if (empty($update_data)) {
            return true; // No data to update
        }
        
        $result = $wpdb->update(
            $this->table_name,
            $update_data,
            ['id' => $snippet_id],
            $format,
            ['%d']
        );

        if ($result !== false) {
            return true;
        } else {
            return new WP_Error('db_update_error', 'Failed to update code snippet.', $wpdb->last_error);
        }
    }

    /**
     * Deletes a code snippet by ID.
     *
     * @param int $snippet_id The ID of the code snippet to delete.
     * @return bool|WP_Error Returns true on success, or WP_Error on failure.
     */
    public function delete_code_snippet($snippet_id) {
        global $wpdb;
        $result = $wpdb->delete(
            $this->table_name,
            ['id' => $snippet_id],
            ['%d']
        );

        if ($result !== false) {
            return true;
        } else {
            return new WP_Error('db_delete_error', 'Failed to delete code snippet.', $wpdb->last_error);
        }
    }

    /**
     * Lists code snippets, with optional filters and pagination.
     *
     * @param array $filters Array of filters (e.g., 'language', 'tags', 'search_term').
     * @param array $pagination Array for pagination (e.g., 'page', 'per_page').
     * @return array Returns an array of code snippet data arrays.
     */
    public function list_code_snippets($filters = [], $pagination = []) {
        global $wpdb;
        $query = "SELECT * FROM $this->table_name WHERE 1=1";
        $prepare_args = [];

        if (!empty($filters['language'])) {
            $query .= " AND language = %s";
            $prepare_args[] = sanitize_key($filters['language']);
        }
        if (!empty($filters['tags'])) {
            $query .= " AND tags LIKE %s";
            $prepare_args[] = '%' . $wpdb->esc_like(sanitize_text_field($filters['tags'])) . '%';
        }
        if (!empty($filters['search_term'])) {
            $query .= " AND (code LIKE %s OR description LIKE %s OR name LIKE %s OR tags LIKE %s)";
            $search_pattern = '%' . $wpdb->esc_like(sanitize_text_field($filters['search_term'])) . '%';
            $prepare_args = array_merge($prepare_args, [$search_pattern, $search_pattern, $search_pattern, $search_pattern]);
        }

        $query .= " ORDER BY updated_at DESC";

        if (!empty($pagination['per_page']) && intval($pagination['per_page']) > 0) {
            $per_page = intval($pagination['per_page']);
            $page = isset($pagination['page']) && intval($pagination['page']) > 0 ? intval($pagination['page']) : 1;
            $offset = ($page - 1) * $per_page;
            $query .= " LIMIT %d OFFSET %d";
            $prepare_args = array_merge($prepare_args, [$per_page, $offset]);
        }

        return $wpdb->get_results(
            $wpdb->prepare($query, $prepare_args),
            ARRAY_A
        );
    }

    // Implement functions for code snippet categories/tags and import/export if needed.
}
new CodeSnippets();
