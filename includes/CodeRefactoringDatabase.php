<?php
/**
 * Class CodeRefactoringDatabase
 *
 * Handles database interactions for the CodeRefactoring module, providing methods to 
 * manage refactoring data in the database.
 */
class CodeRefactoringDatabase {
    /**
     * @var string $table_name The name of the database table for storing code refactoring data.
     */
    private $table_name;

    /**
     * @var Database Database instance for data operations.
     */
    private $db;

    /**
     * Constructor for the CodeRefactoringDatabase class.
     *
     * Initializes the Database instance and sets up the database table name.
     */
    public function __construct() {
        $this->db = Database::get_instance();
        $this->table_name = $this->db->get_table_name('adversarial_code_refactoring_log');
    }

    /**
     * Installation function for the CodeRefactoringDatabase module.
     *
     * Creates the database table to store code refactoring log using Database class.
     * @global wpdb $wpdb WordPress database abstraction object.
     */
    public function install() {
        $table_name = $this->table_name;
        $charset_collate = $this->db->wpdb->get_charset_collate();
        $sql = "CREATE TABLE $table_name (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            user_id BIGINT UNSIGNED NOT NULL,
            code_snippet_id BIGINT UNSIGNED NULL,
            refactoring_type VARCHAR(255) NOT NULL,
            original_code LONGTEXT NULL,
            refactored_code LONGTEXT NULL,
            timestamp TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY user_id (user_id),
            KEY code_snippet_id (code_snippet_id),
            KEY refactoring_type (refactoring_type)
        ) $charset_collate;";
        $this->db->install_table($table_name, $sql);
    }

    // Implement database interaction methods for CodeRefactoring module if needed (e.g., logging refactoring operations, storing settings).
}
