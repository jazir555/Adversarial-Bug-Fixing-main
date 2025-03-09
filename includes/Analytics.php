<?php
class Analytics {
    private $table_name;
    private $version = '1.0';
    /**
     * @var Database Database instance for data operations.
     */
    private $db;

    public function __construct() {
        $this->db = Database::get_instance();
        $this->table_name = $this->db->get_table_name('adversarial_analytics');
        register_activation_hook(__FILE__, [$this, 'install']);
        add_action('wp_footer', [$this, 'track_page_view']);
        add_action('admin_enqueue_scripts', [$this, 'enqueue_scripts']);
        add_action('wp_ajax_adversarial_submit_event', [$this, 'ajax_submit_event']);
        add_action('wp_ajax_nopriv_adversarial_submit_event', [$this, 'ajax_submit_event']);
    }

    public function install() {
        $table_name = $this->table_name;
        $charset_collate = $this->db->wpdb->get_charset_collate();
        $sql = "CREATE TABLE $table_name (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            user_id BIGINT UNSIGNED NOT NULL,
            event_type VARCHAR(255) NOT NULL,
            event_data LONGTEXT,
            timestamp TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY user_id (user_id),
            KEY event_type (event_type)
        ) $charset_collate;";
        $this->db->install_table($table_name, $sql);
    }

    public function enqueue_scripts() {
        wp_enqueue_script('adversarial-analytics', plugins_url('assets/js/analytics.js', __FILE__), ['jquery'], $this->version, true);
        wp_localize_script('adversarial-analytics', 'analyticsSettings', [
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('analytics_nonce')
        ]);
    }

    public function track_page_view() {
        $this->log_event('page_view', [
            'url' => home_url(add_query_arg(array())),
            'referrer' => wp_get_original_referer() ?: ''
        ]);
    }

    public function log_event($type, $data = []) {
        global $current_user;
        get_currentuserinfo();
        $user_id = $current_user->ID;
        $this->db->insert(
            $this->table_name,
            [
                'user_id' => $user_id,
                'event_type' => sanitize_key($type),
                'event_data' => maybe_serialize($data),
                'timestamp' => current_time('mysql')
            ],
            [
                '%d',
                '%s',
                '%s',
                '%s'
            ]
        );
    }

    public function ajax_submit_event() {
        check_ajax_referer('analytics_nonce', 'nonce');
        if (!isset($_POST['type'])) {
            wp_send_json_error(['message' => 'Missing event type']);
        }
        $type = sanitize_key($_POST['type']);
        $data = isset($_POST['data']) ? $_POST['data'] : [];
        $this->log_event($type, $data);
        wp_send_json_success();
    }

    public function generate_report($start_date, $end_date, $user_id = 0) {
        $query = $this->db->wpdb->prepare("
            SELECT event_type, COUNT(*) as count, AVG(TIMESTAMPDIFF(SECOND, timestamp, NOW())) as avg_time
            FROM $this->table_name
            WHERE timestamp BETWEEN %s AND %s
        ", $start_date, $end_date);
        if ($user_id) {
            $query .= $this->db->wpdb->prepare(" AND user_id = %d ", $user_id);
        }
        $query .= " GROUP BY event_type ORDER BY count DESC";
        return $this->db->get_results($this->table_name, $query);
    }

    public function export_data($format = 'csv') {
        $data = $this->db->get_results($this->table_name, "SELECT * FROM $this->table_name", [], OBJECT);
        if ($format === 'csv') {
            $output = fopen('php://output', 'w');
            fputcsv($output, array_keys((array)$data[0] ?? []));
            foreach ($data as $row) {
                fputcsv($output, (array)$row);
            }
            fclose($output);
        } elseif ($format === 'json') {
            echo json_encode($data);
        }
        exit;
    }
}
new Analytics();
