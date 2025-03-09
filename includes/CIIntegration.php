<?php
/**
 * Class CIIntegration
 *
 * Handles CI/CD integration functionalities for the plugin, allowing users to manage 
 * and trigger CI/CD pipelines for their code snippets and projects.
 */
class CIIntegration {
    /**
     * @var string $version The version of the CIIntegration class.
     */
    private $version = '1.0';

    /**
     * @var CIIntegrationDatabase $db_handler Database handler for CI pipelines.
     */
    private $db_handler;
    /**
     * @var Database Database instance for data operations.
     */
    private $db;


    /**
     * Constructor for the CIIntegration class.
     *
     * Initializes the CIIntegration database handler.
     */
    public function __construct() {
        $this->db = Database::get_instance();
        $this->db_handler = new CIIntegrationDatabase();
        register_activation_hook(__FILE__, [$this, 'install']);
    }

    /**
     * Installation function for the CIIntegration module.
     *
     * Delegates database table creation to the CIIntegrationDatabase class.
     */
    public function install() {
        $this->db_handler->install();
    }

    /**
     * Creates a new CI/CD pipeline configuration.
     *
     * @param string $name The name of the pipeline.
     * @param array $pipeline_data Array of pipeline configuration data (repository, branch, build_command, test_command).
     * @return int|WP_Error Returns the ID of the newly created pipeline on success, or WP_Error on failure.
     */
    public function create_pipeline($name, $pipeline_data) {
        return $this->db_handler->create_pipeline($name, $pipeline_data);
    }

    /**
     * Retrieves a CI/CD pipeline configuration by ID.
     *
     * @param int $pipeline_id The ID of the pipeline to retrieve.
     * @return array|null Returns the pipeline configuration array on success, or null if not found.
     */
    public function get_pipeline($pipeline_id) {
        return $this->db_handler->get_pipeline($pipeline_id);
    }

    /**
     * Updates an existing CI/CD pipeline configuration.
     *
     * @param int $pipeline_id The ID of the pipeline to update.
     * @param array $pipeline_data Array of pipeline configuration data to update (name, repository, branch, build_command, test_command).
     * @return bool|WP_Error Returns true on success, or WP_Error on failure.
     */
    public function update_pipeline($pipeline_id, $pipeline_data) {
        return $this->db_handler->update_pipeline($pipeline_id, $pipeline_data);
    }

    /**
     * Deletes a CI/CD pipeline configuration by ID.
     *
     * @param int $pipeline_id The ID of the pipeline to delete.
     * @return bool|WP_Error Returns true on success, or WP_Error on failure.
     */
    public function delete_pipeline($pipeline_id) {
        return $this->db_handler->delete_pipeline($pipeline_id);
    }

    /**
     * Lists all CI/CD pipeline configurations.
     *
     * @return array Returns an array of pipeline configuration arrays.
     */
    public function list_pipelines() {
        return $this->db_handler->list_pipelines();
    }

    /**
     * Runs a CI/CD pipeline by ID.
     *
     * @param int $pipeline_id The ID of the pipeline to run.
     * @return bool|WP_Error Returns true on success, or WP_Error on failure.
     */
    public function run_pipeline($pipeline_id) {
        $pipeline = $this->get_pipeline($pipeline_id);
        if (!$pipeline) {
            return new WP_Error('invalid_pipeline_id', 'Invalid pipeline ID.');
        }

        $activity_logger = new ActivityLogging();
        $activity_logger->log_activity(
            get_current_user_id(),
            'ci_pipeline_run',
            sprintf('CI/CD pipeline "%s" (ID: %d) triggered.', $pipeline['name'], $pipeline_id)
        );

        return $this->db_handler->update_pipeline_last_run($pipeline_id);
    }
}
new CIIntegration();
