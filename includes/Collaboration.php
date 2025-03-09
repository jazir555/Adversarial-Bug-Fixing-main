<?php
/**
 * Class Collaboration
 *
 * Handles collaboration functionalities for the plugin, allowing multiple users to 
 * collaborate on code snippets and projects in real-time or asynchronously.
 */
class Collaboration {
    /**
     * @var string $table_name The name of the database table for storing collaboration projects.
     */
    private $table_name;

    /**
     * @var string $version The version of the Collaboration class.
     */
    private $version = '1.0';

    /**
     * Constructor for the Collaboration class.
     *
     * Sets up the database table name and registers activation hook for database table creation.
     */
    public function __construct() {
        global $wpdb;
        $this->table_name = $wpdb->prefix . 'adversarial_collaboration_projects';
        register_activation_hook(__FILE__, [$this, 'install']);
    }

    /**
     * Installation function for the Collaboration module.
     *
     * Creates the database tables to store collaboration projects and collaborators.
     * @global wpdb $wpdb WordPress database abstraction object.
     */
    public function install() {
        global $wpdb;
        $charset_collate = $wpdb->get_charset_collate();
        $sql = "CREATE TABLE $this->table_name (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            name VARCHAR(255) NOT NULL,
            description TEXT NULL,
            created_by_user_id BIGINT UNSIGNED NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY created_by_user_id (created_by_user_id)
        ) $charset_collate;";

        $sql_collaborators_table = "CREATE TABLE {$wpdb->prefix}adversarial_collaboration_collaborators (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            project_id BIGINT UNSIGNED NOT NULL,
            user_id BIGINT UNSIGNED NOT NULL,
            permissions VARCHAR(255) NOT NULL DEFAULT 'read',
            added_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY project_user_unique (project_id, user_id),
            KEY project_id (project_id),
            KEY user_id (user_id)
        ) $charset_collate;";


        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
        dbDelta($sql_collaborators_table);
    }

    /**
     * Creates a new collaboration project.
     *
     * @param array $project_data Array of project data (name, description).
     * @param int $user_id ID of the user creating the project.
     * @return int|WP_Error Returns the ID of the newly created project on success, or WP_Error on failure.
     */
    public function create_collaboration_project($project_data, $user_id) {
        global $wpdb;
        $result = $wpdb->insert(
            $this->table_name,
            [
                'name' => sanitize_text_field($project_data['name']),
                'description' => sanitize_textarea_field($project_data['description']),
                'created_by_user_id' => intval($user_id),
            ],
            [
                '%s',
                '%s',
                '%d'
            ]
        );
        if ($result) {
            return $wpdb->insert_id;
        } else {
            return new WP_Error('db_insert_error', 'Failed to create collaboration project.', $wpdb->last_error);
        }
    }

    /**
     * Retrieves a collaboration project by ID.
     *
     * @param int $project_id The ID of the project to retrieve.
     * @return array|null Returns the project data array on success, or null if not found.
     */
    public function get_collaboration_project($project_id) {
        global $wpdb;
        return $wpdb->get_row(
            $wpdb->prepare("SELECT * FROM $this->table_name WHERE id = %d", $project_id),
            ARRAY_A
        );
    }

    /**
     * Updates an existing collaboration project.
     *
     * @param int $project_id The ID of the project to update.
     * @param array $project_data Array of project data to update (name, description).
     * @return bool|WP_Error Returns true on success, or WP_Error on failure.
     */
    public function update_collaboration_project($project_id, $project_data) {
        global $wpdb;
        $update_data = [];
        $format = [];
        if (isset($project_data['name'])) {
            $update_data['name'] = sanitize_text_field($project_data['name']);
            $format[] = '%s';
        }
        if (isset($project_data['description'])) {
            $update_data['description'] = sanitize_textarea_field($project_data['description']);
            $format[] = '%s';
        }

        if (empty($update_data)) {
            return true; // No data to update
        }

        $result = $wpdb->update(
            $this->table_name,
            $update_data,
            ['id' => $project_id],
            $format,
            ['%d']
        );

        if ($result !== false) {
            return true;
        } else {
            return new WP_Error('db_update_error', 'Failed to update collaboration project.', $wpdb->last_error);
        }
    }

    /**
     * Deletes a collaboration project by ID.
     *
     * @param int $project_id The ID of the project to delete.
     * @return bool|WP_Error Returns true on success, or WP_Error on failure.
     */
    public function delete_collaboration_project($project_id) {
        global $wpdb;
        $result = $wpdb->delete(
            $this->table_name,
            ['id' => $project_id],
            ['%d']
        );

        if ($result !== false) {
            return true;
        } else {
            return new WP_Error('db_delete_error', 'Failed to delete collaboration project.', $wpdb->last_error);
        }
    }

    /**
     * Lists collaboration projects, with optional filters and pagination.
     *
     * @param array $filters Array of filters (not used yet).
     * @param array $pagination Array for pagination (not used yet).
     * @return array Returns an array of collaboration project data arrays.
     */
    public function list_collaboration_projects($filters = [], $pagination = []) {
        global $wpdb;
        $query = "SELECT * FROM $this->table_name ORDER BY name ASC";
        return $wpdb->get_results(
            $query,
            ARRAY_A
        );
    }

    /**
     * Adds a collaborator to a collaboration project.
     *
     * @param int $project_id The ID of the project to add the collaborator to.
     * @param int $user_id The ID of the user to add as a collaborator.
     * @param string $permissions Permissions for the collaborator (e.g., 'read', 'edit', 'admin').
     * @return int|WP_Error Returns the ID of the newly added collaborator entry on success, or WP_Error on failure.
     */
    public function add_collaborator_to_project($project_id, $user_id, $permissions = 'read') {
        global $wpdb;
        $result = $wpdb->insert(
            "{$wpdb->prefix}adversarial_collaboration_collaborators",
            [
                'project_id' => intval($project_id),
                'user_id' => intval($user_id),
                'permissions' => sanitize_key($permissions),
            ],
            [
                '%d',
                '%d',
                '%s'
            ]
        );
        if ($result) {
            return $wpdb->insert_id;
        } else {
            return new WP_Error('db_insert_error', 'Failed to add collaborator to project.', $wpdb->last_error);
        }
    }

    /**
     * Removes a collaborator from a collaboration project.
     *
     * @param int $project_id The ID of the project to remove the collaborator from.
     * @param int $user_id The ID of the user to remove as a collaborator.
     * @return bool|WP_Error Returns true on success, or WP_Error on failure.
     */
    public function remove_collaborator_from_project($project_id, $user_id) {
        global $wpdb;
        $result = $wpdb->delete(
            "{$wpdb->prefix}adversarial_collaboration_collaborators",
            [
                'project_id' => intval($project_id),
                'user_id' => intval($user_id),
            ],
            [
                '%d',
                '%d'
            ]
        );

        if ($result !== false) {
            return true;
        } else {
            return new WP_Error('db_delete_error', 'Failed to remove collaborator from project.', $wpdb->last_error);
        }
    }

    /**
     * Lists collaborators for a specific collaboration project.
     *
     * @param int $project_id The ID of the project to list collaborators for.
     * @return array Returns an array of collaborator data arrays for the project.
     */
    public function get_project_collaborators($project_id) {
        global $wpdb;
        return $wpdb->get_results(
            $wpdb->prepare(
                "SELECT * FROM {$wpdb->prefix}adversarial_collaboration_collaborators WHERE project_id = %d",
                $project_id
            ),
            ARRAY_A
        );
    }

    // Implement real-time collaboration functionalities and AJAX actions in future iterations.
}
new Collaboration();
