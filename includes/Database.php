<?php
/**
 * Class Database
 *
 * Centralizes database interactions for the plugin, providing a singleton instance 
 * and wrapper methods for common database operations using $wpdb.
 */
class Database {
    /**
     * @var Database The single instance of the Database class.
     */
    private static $instance = null;

    /**
     * @var wpdb WordPress database abstraction object.
     */
    private $wpdb;

    /**
     * Private constructor to enforce singleton pattern.
     */
    private function __construct() {
        global $wpdb;
        $this->wpdb = $wpdb;
    }

    /**
     * Get the singleton instance of the Database class.
     *
     * @return Database The singleton instance.
     */
    public static function get_instance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Prevents cloning of the singleton instance.
     */
    private function __clone() { }

    /**
     * Prevents unserialization of the singleton instance.
     */
    private function __wakeup() { }

    /**
     * Gets the full table name with WordPress prefix.
     *
     * @param string $table_suffix The suffix of the table name (without prefix).
     * @return string Full table name with WordPress prefix.
     */
    public function get_table_name($table_suffix) {
        return $this->wpdb->prefix . $table_suffix;
    }

    /**
     * Installs a database table using dbDelta.
     *
     * @param string $table_name The full name of the table to create.
     * @param string $sql_schema SQL schema for creating the table.
     */
    public function install_table($table_name, $sql_schema) {
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql_schema);
    }

    /**
     * Inserts data into a database table.
     *
     * @param string $table_name The name of the table.
     * @param array $data An associative array of data to insert.
     * @param array $format An array of formats to be used in sprintf.
     * @return false|int Returns The number of rows inserted, or false on error.
     */
    public function insert($table_name, $data, $format) {
        return $this->wpdb->insert($table_name, $data, $format);
    }

    /**
     * Retrieves a single row from the database.
     *
     * @param string $table_name The name of the table.
     * @param string $query The SQL query to execute.
     * @param array $args Arguments to pass to wpdb::prepare.
     * @param string $output_type The required return type - ARRAY_A, ARRAY_N, or OBJECT.
     * @return object|array|null Returns a single database row, or null if there is no row.
     */
    public function get_row($table_name, $query, $args = [], $output_type = ARRAY_A) {
        $prepared_query = $this->wpdb->prepare($query, $args);
        return $this->wpdb->get_row($prepared_query, $output_type);
    }

    /**
     * Retrieves multiple rows from the database.
     *
     * @param string $table_name The name of the table.
     * @param string $query The SQL query to execute.
     * @param array $args Arguments to pass to wpdb::prepare.
     * @param string $output_type The required return type - ARRAY_A, ARRAY_N, or OBJECT.
     * @return array|null Returns an array of database rows, or null if there are no rows.
     */
    public function get_results($table_name, $query, $args = [], $output_type = ARRAY_A) {
        $prepared_query = $this->wpdb->prepare($query, $args);
        return $this->wpdb->get_results($prepared_query, $output_type);
    }

    /**
     * Updates rows in the database.
     *
     * @param string $table_name The name of the table.
     * @param array $data An associative array of data to update.
     * @param array $where An associative array of WHERE clause parameters.
     * @param array $format An array of formats for the data values.
     * @param array $where_format An array of formats for the WHERE clause values.
     * @return false|int Integer number of rows affected OR false on failure.
     */
    public function update($table_name, $data, $where, $format = null, $where_format = null) {
        return $this->wpdb->update($table_name, $data, $where, $format, $where_format);
    }

    /**
     * Deletes rows from the database.
     *
     * @param string $table_name The name of the table.
     * @param array $where An associative array of WHERE clause parameters.
     * @param array $where_format An array of formats for the WHERE clause values.
     * @return false|int Integer number of rows affected OR false on failure.
     */
    public function delete($table_name, $where, $where_format = null) {
        return $this->wpdb->delete($table_name, $where, $where_format);
    }
}
