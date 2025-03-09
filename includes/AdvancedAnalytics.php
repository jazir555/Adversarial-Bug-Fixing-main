<?php
/**
 * Class AdvancedAnalytics
 *
 * Handles advanced analytics functionality for the plugin.
 */
class AdvancedAnalytics {
    /**
     * @var string $table_name The name of the database table for storing advanced analytics data.
     */
    private $table_name;

    /**
     * @var string $version The version of the AdvancedAnalytics class.
     */
    private $version = '1.0';

    /**
     * @var Database Database instance for data operations.
     */
    private $db;

    /**
     * Constructor for the AdvancedAnalytics class.
     *
     * Initializes the Database instance and sets up the database table name and actions.
     */
    public function __construct() {
        $this->db = Database::get_instance();
        $this->table_name = $this->db->get_table_name('adversarial_advanced_analytics');
        register_activation_hook(__FILE__, [$this, 'install']);
        add_action('admin_enqueue_scripts', [$this, 'enqueue_scripts']);
        add_action('wp_ajax_adversarial_run_analysis', [$this, 'ajax_run_analysis']);
        add_action('wp_ajax_nopriv_adversarial_run_analysis', [$this, 'ajax_run_analysis']);
        add_action('wp_ajax_adversarial_get_analysis_report', [$this, 'ajax_get_report']);
        add_action('wp_ajax_nopriv_adversarial_get_analysis_report', [$this, 'ajax_get_report']);
        $this->register_admin_hooks();
        add_action('wp_enqueue_scripts', [$this, 'enqueue_frontend_assets'] );
    }


    /**
     * Installation function for the AdvancedAnalytics module.
     *
     * Creates the database table to store advanced analytics data using Database class.
     */
    public function install() {
        $table_name = $this->table_name;
        $charset_collate = $this->db->wpdb->get_charset_collate();
        $sql = "CREATE TABLE $table_name (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            user_id BIGINT UNSIGNED NOT NULL,
            event_type VARCHAR(255) NOT NULL,
            event_action VARCHAR(255) NULL,
            event_value VARCHAR(255) NULL,
            event_data LONGTEXT NULL,
            timestamp TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY user_id (user_id),
            KEY event_type (event_type),
            KEY event_action (event_action)
        ) $charset_collate;";
        $this->db->install_table($table_name, $sql);
    }

    public function enqueue_scripts() {
        wp_enqueue_script('adversarial-advanced-analytics', plugins_url('assets/js/advanced-analytics.js', __FILE__), ['jquery'], $this->version, true);
        wp_localize_script('adversarial-advanced-analytics', 'advancedAnalyticsSettings', [
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('advanced_analytics_nonce')
        ]);
    }

    /**
     * Tracks code editor usage events.
     *
     * @param int $user_id The ID of the user performing the action.
     * @param string $action The specific action performed in the code editor (e.g., 'save', 'format', 'lint').
     * @param string $language The code language being used in the editor.
     */
    public function track_code_editor_usage($user_id, $action, $language) {
        $this->log_advanced_event(
            $user_id,
            'code_editor_usage',
            $action,
            $language,
            []
        );
    }

    /**
     * Tracks LLM interaction events.
     *
     * @param int $user_id The ID of the user interacting with the LLM.
     * @param string $interaction_type Type of interaction (e.g., 'code_generation', 'bug_fix', 'code_review').
     * @param string $query The query or prompt used for LLM interaction.
     * @param array $response_data Additional data about the LLM response (optional).
     */
    public function track_llm_interaction($user_id, $interaction_type, $query, $response_data = []) {
        $this->log_advanced_event(
            $user_id,
            'llm_interaction',
            $interaction_type,
            $query,
            $response_data
        );
    }

    /**
     * Tracks workflow usage events.
     *
     * @param int $user_id The ID of the user performing the workflow action.
     * @param string $workflow_action The specific workflow action (e.g., 'run_ci_pipeline', 'create_code_review', 'deploy_code').
     * @param string $workflow_name The name or identifier of the workflow being used.
     */
    public function track_workflow_usage($user_id, $workflow_action, $workflow_name) {
        $this->log_advanced_event(
            $user_id,
            'workflow_usage',
            $workflow_action,
            $workflow_name,
            []
        );
    }

    /**
     * Tracks error events within the plugin.
     *
     * @param int $user_id The ID of the user who encountered the error (if applicable).
     * @param string $error_type Type of error (e.g., 'ajax_error', 'php_exception', 'js_error').
     * @param string $error_message Detailed error message.
     * @param array $error_data Additional error-specific data (optional).
     */
    public function track_error_event($user_id, $error_type, $error_message, $error_data = []) {
        $this->log_advanced_event(
            $user_id,
            'error_event',
            $error_type,
            $error_message,
            $error_data
        );
    }

    /**
     * Logs advanced analytics events to the database using Database class.
     *
     * @param int $user_id The ID of the user performing the activity.
     * @param string $event_type A type or category for the event.
     * @param string $event_action A specific action within the event type (optional).
     * @param string $event_value A value associated with the event (optional).
     * @param array $event_data Additional event-specific data (optional).
     */
    private function log_advanced_event($user_id, $event_type, $event_action = null, $event_value = null, $event_data = []) {
        $this->db->insert(
            $this->table_name,
            [
                'user_id' => $user_id,
                'event_type' => sanitize_key($event_type),
                'event_action' => sanitize_key($event_action),
                'event_value' => sanitize_text_field($event_value),
                'event_data' => maybe_serialize($event_data),
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
     * Retrieves advanced analytics logs from the database with pagination and filtering.
     *
     * @param int $per_page Number of logs to display per page.
     * @param int $page_number Current page number.
     * @param string $search_term Search term to filter logs.
     * @param string $event_type_filter Event type to filter logs.
     * @return array Array of advanced analytics log objects.
     */
    public function get_advanced_analytics_logs( $per_page = 20, $page_number = 1, $search_term = '', $event_type_filter = '' ) {
        $table_name = $this->table_name;
        $offset = ( $page_number - 1 ) * $per_page;
        $sql = "SELECT * FROM $table_name WHERE 1=1";

        if ( ! empty( $search_term ) ) {
            $search_term = esc_sql( $search_term );
            $sql .= " AND activity_description LIKE '%{$search_term}%'";
        }
        if ( ! empty( $event_type_filter ) ) {
            $event_type_filter = esc_sql( $event_type_filter );
            $sql .= " AND event_type = '{$event_type_filter}'";
        }

        $sql .= " ORDER BY timestamp DESC LIMIT %d OFFSET %d";

        $prepared_query = $this->db->wpdb->prepare( $sql, $per_page, $offset );
        $results = $this->db->wpdb->get_results( $prepared_query );
        return $results;
    }

    /**
     * Gets the total count of advanced analytics logs, considering search and filter terms.
     *
     * @param string $search_term Search term to filter logs.
     * @param string $event_type_filter Event type to filter logs.
     * @return int Total count of advanced analytics logs.
     */
    public function get_total_advanced_analytics_log_count( $search_term = '', $event_type_filter = '' ) {
        $table_name = $this->table_name;
        $sql = "SELECT COUNT(*) FROM $table_name WHERE 1=1";

        if ( ! empty( $search_term ) ) {
            $search_term = esc_sql( $search_term );
            $sql .= " AND activity_description LIKE '%{$search_term}%'";
        }
        if ( ! empty( $event_type_filter ) ) {
            $event_type_filter = esc_sql( $event_type_filter );
            $sql .= " AND event_type = '{$event_type_filter}'";
        }

        $count = $this->db->wpdb->get_var( $sql );
        return intval( $count );
    }

    /**
     * Registers the admin menu for Advanced Analytics.
     */
    public function register_admin_menu() {
        add_submenu_page(
            'adversarial-bug-fixing', // parent slug
            'Advanced Analytics', // page title
            'Advanced Analytics', // menu title
            'manage_options', // capability
            'advanced-analytics', // menu slug
            [$this, 'display_advanced_analytics_page'] // callback function
        );
    }

    /**
     * Displays the advanced analytics admin page.
     */
    public function display_advanced_analytics_page() {
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_die( __( 'You do not have sufficient permissions to access this page.', 'adversarial-bug-fixing' ) );
        }

        $per_page = 20;
        $page_number = isset( $_GET['paged'] ) ? absint( $_GET['paged'] ) : 1;
        $search_term = isset( $_GET['s'] ) ? sanitize_text_field( $_GET['s'] ) : '';
        $event_type_filter = isset( $_GET['event_type'] ) ? sanitize_text_field( $_GET['event_type'] ) : '';

        $activity_logs = $this->get_advanced_analytics_logs( $per_page, $page_number, $search_term, $event_type_filter );
        $total_logs = $this->get_total_advanced_analytics_log_count( $search_term, $event_type_filter );
        $total_pages = ceil( $total_logs / $per_page );

        $event_types = $this->get_distinct_event_types();

        ?>
        <div class="wrap">
            <h2><?php _e( 'Advanced Analytics Logs', 'adversarial-bug-fixing' ); ?></h2>
            <form method="get">
                <input type="hidden" name="page" value="<?php echo esc_attr( $_GET['page'] ); ?>" />
                <div class="tablenav top">
                    <div class="alignleft actions">
                        <select name="event_type">
                            <option value=""><?php _e( 'All Event Types', 'adversarial-bug-fixing' ); ?></option>
                            <?php foreach ( $event_types as $event_type ) : ?>
                                <option value="<?php echo esc_attr( $event_type->event_type ); ?>" <?php selected( $event_type_filter, $event_type_filter ); ?>><?php echo esc_html( $event_type->event_type ); ?></option>
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
                            <th><?php _e( 'Event Type', 'adversarial-bug-fixing' ); ?></th>
                            <th><?php _e( 'Event Action', 'adversarial-bug-fixing' ); ?></th>
                            <th><?php _e( 'Event Value', 'adversarial-bug-fixing' ); ?></th>
                            <th><?php _e( 'Timestamp', 'adversarial-bug-fixing' ); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ( $activity_logs ) : ?>
                            <?php foreach ( $activity_logs as $log ) : ?>
                                <tr>
                                    <td><?php echo esc_html( $log->id ); ?></td>
                                    <td><?php echo esc_html( $log->user_id ); ?></td>
                                    <td><?php echo esc_html( $log->event_type ); ?></td>
                                    <td><?php echo esc_html( $log->activity_description ); ?></td>
                                    <td><?php echo esc_html( $log->timestamp ); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else : ?>
                            <tr>
                                <td colspan="6"><?php _e( 'No advanced analytics logs found.', 'adversarial-bug-fixing' ); ?></td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                    <tfoot>
                        <tr>
                            <th><?php _e( 'ID', 'adversarial-bug-fixing' ); ?></th>
                            <th><?php _e( 'User ID', 'adversarial-bug-fixing' ); ?></th>
                            <th><?php _e( 'Event Type', 'adversarial-bug-fixing' ); ?></th>
                            <th><?php _e( 'Event Action', 'adversarial-bug-fixing' ); ?></th>
                            <th><?php _e( 'Event Value', 'adversarial-bug-fixing' ); ?></th>
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
     * Retrieves distinct event types from the advanced analytics log.
     *
     * @return array Array of distinct event types.
     */
    public function get_distinct_event_types() {
        $table_name = $this->table_name;
        $sql = "SELECT DISTINCT event_type FROM $table_name";
        $results = $this->db->wpdb->get_results( $sql );
        return $results;
    }

    /**
     * Registers admin hooks for the Advanced Analytics module.
     */
    public function register_admin_hooks() {
        add_action( 'admin_menu', [$this, 'register_admin_menu'] );
    }
}
new AdvancedAnalytics();
