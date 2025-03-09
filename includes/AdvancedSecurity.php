<?php
/**
 * Class AdvancedSecurity
 *
 * Handles advanced security functionalities for the plugin, including security scans, 
 * vulnerability reporting, and security settings management.
 */
class AdvancedSecurity {
    /**
     * @var string $table_name The name of the database table for storing security related data.
     */
    private $table_name;

    /**
     * @var string $version The version of the AdvancedSecurity class.
     */
    private $version = '1.0';

    /**
     * @var Database Database instance for data operations.
     */
    private $db;

    /**
     * Constructor for the AdvancedSecurity class.
     *
     * Initializes the Database instance and sets up the database table name and actions.
     */
    public function __construct() {
        $this->db = Database::get_instance();
        $this->table_name = $this->db->get_table_name('adversarial_advanced_security');
        register_activation_hook(__FILE__, [$this, 'install']);
        $this->register_admin_hooks();
    }

    /**
     * Installation function for the AdvancedSecurity module.
     *
     * Creates the database table to store security related data, such as scan results and vulnerabilities, using Database class.
     * @global wpdb $wpdb WordPress database abstraction object.
     */
    public function install() {
        $table_name = $this->table_name;
        $charset_collate = $this->db->wpdb->get_charset_collate();
        $sql = "CREATE TABLE $table_name (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            user_id BIGINT UNSIGNED NOT NULL,
            event_type VARCHAR(255) NOT NULL,
            scan_result_summary TEXT NULL,
            scan_details LONGTEXT NULL,
            vulnerability_severity VARCHAR(50) NULL,
            timestamp TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY user_id (user_id),
            KEY scan_type (scan_type),
            KEY vulnerability_severity (vulnerability_severity)
        ) $charset_collate;";
        $this->db->install_table($table_name, $sql);
    }

    /**
     * Logs security events to the database using Database class.
     *
     * @param int $user_id The ID of the user related to the security event.
     * @param string $scan_type Type of security scan performed (e.g., 'php_syntax_check', 'javascript_lint', 'security_scan').
     * @param string $scan_result_summary Summary of the scan result.
     * @param array $scan_details Detailed results of the security scan (e.g., array of vulnerabilities).
     * @param string $vulnerability_severity Severity level of the vulnerability (e.g., 'high', 'medium', 'low').
     */
    public function log_security_scan_result($user_id, $scan_type, $scan_result_summary, $scan_details, $vulnerability_severity = null) {
        $this->db->insert(
            $this->table_name,
            [
                'user_id' => $user_id,
                'scan_type' => sanitize_key($scan_type),
                'scan_result_summary' => sanitize_textarea_field($scan_result_summary),
                'scan_details' => maybe_serialize($scan_details),
                'vulnerability_severity' => sanitize_text_field($vulnerability_severity),
            ],
            [
                '%d',
                '%s',
                '%s',
                '%s',
                '%s'
            ]
        );
    }

    /**
     * Retrieves security scan logs from the database with pagination and filtering.
     *
     * @param int $per_page Number of logs per page.
     * @param int $page_number Current page number.
     * @param string $search_term Search term to filter logs.
     * @param string $scan_type_filter Scan type to filter logs.
     * @param string $vulnerability_severity_filter Vulnerability severity to filter logs.
     * @return array Array of security scan log objects.
     */
    public function get_security_scan_logs( $per_page = 20, $page_number = 1, $search_term = '', $scan_type_filter = '', $vulnerability_severity_filter = '' ) {
        $table_name = $this->table_name;
        $offset = ( $page_number - 1 ) * $per_page;
        $sql = "SELECT * FROM $table_name WHERE 1=1";

        if ( ! empty( $search_term ) ) {
            $search_term = esc_sql( $search_term );
            $sql .= " AND scan_result_summary LIKE '%{$search_term}%'";
        }
        if ( ! empty( $scan_type_filter ) ) {
            $scan_type_filter = esc_sql( $scan_type_filter );
            $sql .= " AND scan_type = '{$scan_type_filter}'";
        }
        if ( ! empty( $vulnerability_severity_filter ) ) {
            $vulnerability_severity_filter = esc_sql( $vulnerability_severity_filter );
            $sql .= " AND vulnerability_severity = '{$vulnerability_severity_filter}'";
        }

        $sql .= " ORDER BY timestamp DESC LIMIT %d OFFSET %d";

        $prepared_query = $this->db->wpdb->prepare( $sql, $per_page, $offset );
        $results = $this->db->wpdb->get_results( $prepared_query );
        return $results;
    }

    /**
     * Gets the total count of security scan logs, considering search and filter terms.
     *
     * @param string $search_term Search term to filter logs.
     * @param string $event_type_filter Scan type to filter logs.
     * @param string $vulnerability_severity_filter Vulnerability severity to filter logs.
     * @return int Total count of security scan logs.
     */
    public function get_total_security_log_count( $search_term = '', $scan_type_filter = '', $vulnerability_severity_filter = '' ) {
        $table_name = $this->table_name;
        $sql = "SELECT COUNT(*) FROM $table_name WHERE 1=1";

        if ( ! empty( $search_term ) ) {
            $search_term = esc_sql( $search_term );
            $sql .= " AND scan_result_summary LIKE '%{$search_term}%'";
        }
        if ( ! empty( $scan_type_filter ) ) {
            $scan_type_filter = esc_sql( $scan_type_filter );
            $sql .= " AND scan_type = '{$scan_type_filter}'";
        }
        if ( ! empty( $vulnerability_severity_filter ) ) {
            $vulnerability_severity_filter = esc_sql( $vulnerability_severity_filter );
            $sql .= " AND vulnerability_severity = '{$vulnerability_severity_filter}'";
        }


        $count = $this->db->wpdb->get_var( $sql );
        return intval( $count );
    }

    /**
     * Registers the admin menu for Advanced Security.
     */
    public function register_admin_menu() {
        add_submenu_page(
            'adversarial-bug-fixing', // parent slug
            'Advanced Security', // page title
            'Advanced Security', // menu title
            'manage_options', // capability
            'advanced-security', // menu slug
            [$this, 'display_advanced_security_page'] // callback function
        );
    }

    /**
     * Displays the advanced security admin page.
     */
    public function display_advanced_security_page() {
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_die( __( 'You do not have sufficient permissions to access this page.', 'adversarial-bug-fixing' ) );
        }

        $per_page = 20;
        $page_number = isset( $_GET['paged'] ) ? absint( $_GET['paged'] ) : 1;
        $search_term = isset( $_GET['s'] ) ? sanitize_text_field( $_GET['s'] ) : '';
        $scan_type_filter = isset( $_GET['scan_type'] ) ? sanitize_text_field( $_GET['scan_type'] ) : '';
        $vulnerability_severity_filter = isset( $_GET['vulnerability_severity'] ) ? sanitize_text_field( $_GET['vulnerability_severity'] ) : '';

        $activity_logs = $this->get_security_scan_logs( $per_page, $page_number, $search_term, $scan_type_filter, $vulnerability_severity_filter );
        $total_logs = $this->get_total_security_log_count( $search_term, $scan_type_filter, $vulnerability_severity_filter );
        $total_pages = ceil( $total_logs / $per_page );

        $scan_types = $this->get_distinct_scan_types();
        $vulnerability_severities = $this->get_distinct_vulnerability_severities();

        ?>
        <div class="wrap">
            <h2><?php _e( 'Advanced Security Logs', 'adversarial-bug-fixing' ); ?></h2>
            <form method="get">
                <input type="hidden" name="page" value="<?php echo esc_attr( $_GET['page'] ); ?>" />
                <div class="tablenav top">
                    <div class="alignleft actions">
                        <select name="scan_type">
                            <option value=""><?php _e( 'All Scan Types', 'adversarial-bug-fixing' ); ?></option>
                            <?php foreach ( $scan_types as $scan_type ) : ?>
                                <option value="<?php echo esc_attr( $scan_type->scan_type ); ?>" <?php selected( $scan_type_filter, $scan_type_filter ); ?>><?php echo esc_html( $scan_type->scan_type ); ?></option>
                            <?php endforeach; ?>
                        </select>
                        <select name="vulnerability_severity">
                            <option value=""><?php _e( 'All Severities', 'adversarial-bug-fixing' ); ?></option>
                            <?php foreach ( $vulnerability_severities as $severity ) : ?>
                                <option value="<?php echo esc_attr( $severity->vulnerability_severity ); ?>" <?php selected( $vulnerability_severity_filter, $vulnerability_severity ); ?>><?php echo esc_html( $severity->vulnerability_severity ); ?></option>
                            <?php endforeach; ?>
                        </select>
                        <input type="submit" id="filter-submit" class="button action" value="<?php _e( 'Filter', 'adversarial-bug-fixing' ); ?>">
                    </div>
                    <div class="tablenav-pages">
                        <span class="displaying-num"><?php echo esc_html( sprintf( __( '%s items', 'adversarial-bug-fixing' ), $total_logs ) ); ?></span>
                        <?php
                        $pagination_args = array(
                            'base' => add_query_arg( 'paged', '%#%' ),
                            'format' => '',
                            'prev_text' => __( '&laquo; Previous', 'adversarial-bug-fixing' ),
                            'next_text' => __( 'Next &raquo;', 'adversarial-bug-fixing' ),
                            'total' => $total_pages,
                            'current' => $page_number,
                        );
                        echo _wp_paginate_links( $pagination_args );
                        ?>
                    </div>
                    <br class="clear">
                </div>
                <p class="search-box">
                    <label class="screen-reader-text" for="post-search-input"><?php _e( 'Search Logs:', 'adversarial-bug-fixing' ); ?></label>
                    <input type="search" id="post-search-input" name="s" value="<?php echo esc_attr( $search_term ); ?>" />
                    <input type="submit" id="search-submit" class="button" value="<?php _e( 'Search Logs', 'adversarial-bug-fixing' ); ?>"  />
                </p>
                <table class="wp-list-table widefat fixed striped logs">
                    <thead>
                        <tr>
                            <th><?php _e( 'ID', 'adversarial-bug-fixing' ); ?></th>
                            <th><?php _e( 'User ID', 'adversarial-bug-fixing' ); ?></th>
                            <th><?php _e( 'Scan Type', 'adversarial-bug-fixing' ); ?></th>
                            <th><?php _e( 'Result Summary', 'adversarial-bug-fixing' ); ?></th>
                            <th><?php _e( 'Severity', 'adversarial-bug-fixing' ); ?></th>
                            <th><?php _e( 'Timestamp', 'adversarial-bug-fixing' ); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ( $activity_logs ) : ?>
                            <?php foreach ( $activity_logs as $log ) : ?>
                                <tr>
                                    <td><?php echo esc_html( $log->id ); ?></td>
                                    <td><?php echo esc_html( $log->user_id ); ?></td>
                                    <td><?php echo esc_html( $log->scan_type ); ?></td>
                                    <td><?php echo esc_html( $log->scan_result_summary ); ?></td>
                                    <td><?php echo esc_html( $log->vulnerability_severity ); ?></td>
                                    <td><?php echo esc_html( $log->timestamp ); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else : ?>
                            <tr>
                                <td colspan="6"><?php _e( 'No security logs found.', 'adversarial-bug-fixing' ); ?></td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                    <tfoot>
                        <tr>
                            <th><?php _e( 'ID', 'adversarial-bug-fixing' ); ?></th>
                            <th><?php _e( 'User ID', 'adversarial-bug-fixing' ); ?></th>
                            <th><?php _e( 'Scan Type', 'adversarial-bug-fixing' ); ?></th>
                            <th><?php _e( 'Result Summary', 'adversarial-bug-fixing' ); ?></th>
                            <th><?php _e( 'Severity', 'adversarial-bug-fixing' ); ?></th>
                            <th><?php _e( 'Timestamp', 'adversarial-bug-fixing' ); ?></th>
                        </tr>
                    </tfoot>
                </table>
                <div class="tablenav bottom">
                    <div class="tablenav-pages">
                        <span class="displaying-num"><?php echo esc_html( sprintf( __( '%s items', 'adversarial-bug-fixing' ), $total_logs ) ); ?></span>
                        <?php echo _wp_paginate_links( $pagination_args ); ?>
                    </div>
                    <br class="clear">
                </div>
            </form>
        </div>
        <?php
    }

    /**
     * Retrieves distinct scan types from the security log.
     *
     * @return array Array of distinct scan types.
     */
    public function get_distinct_scan_types() {
        $table_name = $this->table_name;
        $sql = "SELECT DISTINCT scan_type FROM $table_name";
        $results = $this->db->wpdb->get_results( $sql );
        return $results;
    }
    
    /**
     * Retrieves distinct vulnerability severities from the security log.
     *
     * @return array Array of distinct vulnerability severities.
     */
    public function get_distinct_vulnerability_severities() {
        $table_name = $this->table_name;
        $sql = "SELECT DISTINCT vulnerability_severity FROM $table_name WHERE vulnerability_severity IS NOT NULL";
        $results = $this->db->wpdb->get_results( $sql );
        return $results;
    }

    /**
     * Registers admin hooks for the Advanced Security module.
     */
    public function register_admin_hooks() {
        add_action( 'admin_menu', [$this, 'register_admin_menu'] );
    }

     /**
     * Retrieves distinct scan types from the security log.
     *
     * @return array Array of distinct scan types.
     */
    public function get_distinct_scan_types() {
        $table_name = $this->table_name;
        $sql = "SELECT DISTINCT scan_type FROM $table_name";
        $results = $this->db->wpdb->get_results( $sql );
        return $results;
    }
    
    /**
     * Retrieves distinct vulnerability severities from the security log.
     *
     * @return array Array of distinct vulnerability severities.
     */
    public function get_distinct_vulnerability_severities() {
        $table_name = $this->table_name;
        $sql = "SELECT DISTINCT vulnerability_severity FROM $table_name WHERE vulnerability_severity IS NOT NULL";
        $results = $this->db->wpdb->get_results( $sql );
        return $results;
    }


    // Implement functions to run security scans, apply fixes, and generate security reports if needed.
}
new AdvancedSecurity();
AdvancedSecurity->register_admin_hooks();
