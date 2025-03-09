<?php
/**
 * Class ActivityLogging
 *
 * Handles activity logging functionality for the plugin, recording user actions and events.
 */
class ActivityLogging {
    /**
     * @var string $table_name The name of the database table for storing activity logs.
     */
    private $table_name;

    /**
     * @var string $version The version of the ActivityLogging class.
     */
    private $version = '1.0';

    /**
     * @var Database Database instance for data operations.
     */
    private $db;

    /**
     * Constructor for the ActivityLogging class.
     *
     * Initializes the Database instance and sets up the database table name and actions.
     */
    public function __construct() {
        $this->db = Database::get_instance();
        $this->table_name = $this->db->get_table_name('adversarial_activity_log');
        register_activation_hook(__FILE__, [$this, 'install']);
        add_action( 'admin_menu', [$this, 'register_admin_menu'] );
        $this->register_hooks();
    }
    /**
     * Installation function for the ActivityLogging module.
     *
     * Creates the database table to store activity logs using Database class.
     * @global wpdb $wpdb WordPress database abstraction object.
     */
    public function install() {
        $table_name = $this->table_name;
        $charset_collate = $this->db->wpdb->get_charset_collate();
        $sql = "CREATE TABLE $table_name (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            user_id BIGINT UNSIGNED NOT NULL,
            event_type VARCHAR(255) NOT NULL,
            activity_description TEXT NOT NULL,
            timestamp TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY user_id (user_id),
            KEY event_type (event_type),
            INDEX activity_description_index (activity_description(200))
        ) $charset_collate;";
        $this->db->install_table($table_name, $sql);
    }

    /**
     * Adds settings link to the plugin actions links on the plugins page.
     *
     * @param array $links Array of plugin action links.
     * @return array Updated array of plugin action links.
     */
    public function plugin_action_links( $links ) {
        $settings_link = '<a href="' . admin_url( 'admin.php?page=activity-logs' ) . '">' . __( 'Activity Logs', 'adversarial-bug-fixing' ) . '</a>';
        array_unshift( $links, $settings_link );
        return $links;
    }

     /**
     * Registers all admin hooks for the Activity Logging module.
     */
    public function register_admin_hooks() {
        add_action( 'plugin_action_links_' . plugin_basename( __FILE__ ), [$this, 'plugin_action_links'] );
        add_action( 'wp_login', [$this, 'log_login_activity'], 10, 2 );
        add_action( 'wp_logout', [$this, 'log_logout_activity'] );
    }

     /**
     * Registers  admin and frontend hooks for the Activity Logging module.
     */
    public function register_hooks() {
        $this->register_admin_hooks();
        add_action( 'wp_enqueue_scripts', [$this, 'enqueue_frontend_assets'] );
    }

    /**
     * Enqueue frontend assets for activity logging (if needed).
     */
    public function enqueue_frontend_assets() {
        // Placeholder for future frontend activity logging scripts
         wp_enqueue_script( 'activity-logging-frontend', plugin_dir_url( __FILE__ ) . 'Assets/js/activity-logging-frontend.js', [], $this->version, true );
    }

    /**
     * Logs user login activity.
     *
     * @param string $user_login The user's login name.
     * @param WP_User $user The WP_User object of the logged-in user.
     */
    public function log_login_activity( $user_login, $user ) {
        $user_id = $user->ID;
        $activity_description = sprintf( 'User logged in: %s', $user_login );
        $this->log_activity( $user_id, 'login', $activity_description );
    }

    /**
     * Logs frontend activity - capturing actions like page views, button clicks, etc.
     *
     * @param string $event_type Type of frontend event.
     * @param string $event_details Details about the event.
     */
    public function log_frontend_activity( $event_type, $event_details ) {
        $user_id = get_current_user_id(); // Or determine user ID as needed for frontend context
        $activity_description = sprintf( 'Frontend activity: %s - %s', sanitize_key( $event_type ), sanitize_text_field( $event_details ) );
        $this->log_activity( $user_id, 'frontend_event', $activity_description );
    }


    /**
     * Logs user logout activity.
     */
    public function log_logout_activity() {
        $user_id = get_current_user_id();
        if ( $user_id ) {
            $user = wp_get_current_user(); // Use wp_get_current_user() to get the user object
            $user_login = $user->user_login;
            $activity_description = sprintf( 'User logged out: %s', $user_login );
            $this->log_activity( $user_id, 'logout', $activity_description );
        }
    }

    /**
     * Logs user activity to the database using Database class.
     *
     * @param int $user_id The ID of the user performing the activity.
     * @param string $activity_type A type or category for the activity.
     * @param string $activity_description Detailed description of the activity.
     */
    public function log_activity($user_id, $activity_type, $activity_description) {
        $this->db->insert(
            $this->table_name,
            [
                'user_id' => $user_id,
                'event_type' => sanitize_key($activity_type),
                'activity_description' => sanitize_textarea_field($activity_description),
            ],
            [
                '%d',
                '%s',
                '%s'
            ]
        );
    }

    /**
     * Retrieves activity logs from the database with pagination and filtering.
     *
     * @param int $per_page Number of logs to display per page.
     * @param int $page_number Current page number.
     * @param string $search_term Search term to filter logs.
     * @param string $event_type_filter Event type to filter logs.
     * @return array Array of activity log objects.
     */
    public function get_activity_logs( $per_page = 20, $page_number = 1, $search_term = '', $event_type_filter = '' ) {
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
     * Gets the total count of activity logs, considering search and filter terms.
     *
     * @param string $search_term Search term to filter logs.
     * @param string $event_type_filter Event type to filter logs.
     * @return int Total count of activity logs.
     */
    public function get_total_activity_log_count( $search_term = '', $event_type_filter = '' ) {
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
     * Registers the admin menu for Activity Logging.
     */
    public function register_admin_menu() {
        add_submenu_page(
            'adversarial-bug-fixing', // parent slug
            'Activity Logs', // page title
            'Activity Logs', // menu title
            'manage_options', // capability
            'activity-logs', // menu slug
            [$this, 'display_activity_logs_page'] // callback function
        );
    }

    /**
     * Displays the activity logs admin page.
     */
    public function display_activity_logs_page() {
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_die( __( 'You do not have sufficient permissions to access this page.', 'adversarial-bug-fixing' ) );
        }

        $per_page = 20;
        $page_number = isset( $_GET['paged'] ) ? absint( $_GET['paged'] ) : 1;
        $search_term = isset( $_GET['s'] ) ? sanitize_text_field( $_GET['s'] ) : '';
        $event_type_filter = isset( $_GET['event_type'] ) ? sanitize_text_field( $_GET['event_type'] ) : '';

        $activity_logs = $this->get_activity_logs( $per_page, $page_number, $search_term, $event_type_filter );
        $total_logs = $this->get_total_activity_log_count( $search_term, $event_type_filter );
        $total_pages = ceil( $total_logs / $per_page );

        $event_types = $this->get_distinct_event_types();

        ?>
        <div class="wrap">
            <h2><?php _e( 'Activity Logs', 'adversarial-bug-fixing' ); ?></h2>
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
                            <th><?php _e( 'Activity Description', 'adversarial-bug-fixing' ); ?></th>
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
                                <td colspan="5"><?php _e( 'No activity logs found.', 'adversarial-bug-fixing' ); ?></td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                    <tfoot>
                        <tr>
                            <th><?php _e( 'ID', 'adversarial-bug-fixing' ); ?></th>
                            <th><?php _e( 'User ID', 'adversarial-bug-fixing' ); ?></th>
                            <th><?php _e( 'Event Type', 'adversarial-bug-fixing' ); ?></th>
                            <th><?php _e( 'Activity Description', 'adversarial-bug-fixing' ); ?></th>
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
     * Retrieves distinct event types from the activity log.
     *
     * @return array Array of distinct event types.
     */
    public function get_distinct_event_types() {
        $table_name = $this->table_name;
        $sql = "SELECT DISTINCT event_type FROM $table_name";
        $results = $this->db->wpdb->get_results( $sql );
        return $results;
    }
}
new ActivityLogging();
