<?php
/**
 * Class CIIntegrationDatabase
 *
 * Handles database interactions for the CIIntegration module, providing methods to 
 * create, read, update, and delete CI/CD pipeline configurations from the database.
 */
class CIIntegrationDatabase {
    /**
     * @var string $table_name The name of the database table for storing CI/CD pipeline configurations.
     */
    private $table_name;

    /**
     * Constructor for the CIIntegrationDatabase class.
     *
     * Sets up the database table name.
     */
    public function __construct() {
        global $wpdb;
        $this->table_name = $wpdb->prefix . 'adversarial_ci_pipelines';
    }

    /**
     * Installation function for the CIIntegrationDatabase module.
     *
     * Creates the database table to store CI/CD pipeline configurations.
     * @global wpdb $wpdb WordPress database abstraction object.
     */
    public function install() {
        $table_name = $this->table_name;
        $charset_collate = $this->db->wpdb->get_charset_collate();
        $sql = "CREATE TABLE $table_name (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            name VARCHAR(255) NOT NULL,
            repository VARCHAR(255) NOT NULL,
            branch VARCHAR(255) NOT NULL DEFAULT 'main',
            build_command TEXT NULL,
            test_command TEXT NULL,
            last_run TIMESTAMP NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY name (name),
            KEY repository (repository)
        ) $charset_collate;";
        $this->db->install_table($table_name, $sql);
    }

    /**
     * Creates a new CI/CD pipeline configuration in the database.
     *
     * @param string $name The name of the pipeline.
     * @param array $pipeline_data Array of pipeline configuration data (repository, branch, build_command, test_command).
     * @return int|WP_Error Returns the ID of the newly created pipeline on success, or WP_Error on failure.
     */
    public function create_pipeline($name, $pipeline_data) {
        return $this->db->insert(
            $this->table_name,
            [
                'name' => sanitize_text_field($name),
                'repository' => esc_url_raw($pipeline_data['repository']),
                'branch' => sanitize_text_field($pipeline_data['branch']),
                'build_command' => sanitize_textarea_field($pipeline_data['build_command']),
                'test_command' => sanitize_textarea_field($pipeline_data['test_command']),
            ],
            [
                '%s',
                '%s',
                '%s',
                '%s',
                '%s'
            ]
        );
    }

    /**
     * Retrieves a CI/CD pipeline configuration by ID from the database.
     *
     * @param int $pipeline_id The ID of the pipeline to retrieve.
     * @return array|null Returns the pipeline configuration array on success, or null if not found.
     */
    public function get_pipeline($pipeline_id) {
        return $this->db->get_row(
            $this->table_name,
            $this->db->wpdb->prepare("SELECT * FROM $this->table_name WHERE id = %d", $pipeline_id),
            [$pipeline_id]
        );
    }

    /**
     * Updates an existing CI/CD pipeline configuration in the database.
     *
     * @param int $pipeline_id The ID of the pipeline to update.
     * @param array $pipeline_data Array of pipeline configuration data to update (name, repository, branch, build_command, test_command).
     * @return bool|WP_Error Returns true on success, or WP_Error on failure.
     */
    public function update_pipeline($pipeline_id, $pipeline_data) {
        return $this->db->update(
            $this->table_name,
            [
                'name' => sanitize_text_field($pipeline_data['name']),
                'repository' => esc_url_raw($pipeline_data['repository']),
                'branch' => sanitize_text_field($pipeline_data['branch']),
                'build_command' => sanitize_textarea_field($pipeline_data['build_command']),
                'test_command' => sanitize_textarea_field($pipeline_data['test_command']),
            ],
            ['id' => $pipeline_id],
            [
                '%s',
                '%s',
                '%s',
                '%s',
                '%s'
            ],
            ['%d']
        );
    }

    /**
     * Deletes a CI/CD pipeline configuration by ID from the database.
     *
     * @param int $pipeline_id The ID of the pipeline to delete.
     * @return bool|WP_Error Returns true on success, or WP_Error on failure.
     */
    public function delete_pipeline($pipeline_id) {
        return $this->db->delete(
            $this->table_name,
            ['id' => $pipeline_id],
            ['%d']
        );
    }

    /**
     * Lists all CI/CD pipeline configurations from the database.
     *
     * @return array Returns an array of pipeline configuration arrays.
     */
    public function list_pipelines() {
        return $this->db->get_results(
            $this->table_name,
            "SELECT * FROM $this->table_name ORDER BY name ASC"
        );
    }

    /**
     * Updates the last_run timestamp for a CI/CD pipeline in the database.
     *
     * @param int $pipeline_id The ID of the pipeline to update.
     * @return bool|WP_Error Returns true on success, or WP_Error on failure.
     */
    public function update_pipeline_last_run($pipeline_id) {
        return $this->db->update(
            $this->table_name,
            ['last_run' => current_time('mysql')],
            ['id' => $pipeline_id],
            ['%s'],
            ['%d']
        );
    }
}
