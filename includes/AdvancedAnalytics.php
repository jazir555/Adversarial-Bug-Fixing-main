class AdvancedAnalytics {
    private $wpdb;
    private $table_name;

    public function __construct() {
        global $wpdb;
        $this->wpdb = $wpdb;
        $this->table_name = $wpdb->prefix . 'adversarial_code_analytics';
    }

    public function install() {
        $charset_collate = $this->wpdb->get_charset_collate();
        
        $sql = "CREATE TABLE IF NOT EXISTS $this->table_name (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            request_id bigint(20) unsigned NOT NULL,
            model_id varchar(50) NOT NULL,
            action varchar(20) NOT NULL,
            tokens_in int NOT NULL,
            tokens_out int NOT NULL,
            duration float NOT NULL,
            status varchar(20) NOT NULL,
            user_id bigint(20) unsigned NOT NULL,
            request_params longtext,
            full_response longtext,
            error_details longtext,
            created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY  (id),
            KEY request_id (request_id),
            KEY model_id (model_id),
            KEY action (action),
            KEY created_at (created_at),
            KEY user_id (user_id)
        ) $charset_collate;";

        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        dbDelta($sql);
    }

    public function log_api_call($request_id, $model_id, $action, $tokens_in, $tokens_out, $duration, $status, $user_id, $request_params = null, $full_response = null, $error_details = null) {
        $this->wpdb->insert(
            $this->table_name,
            [
                'request_id' => $request_id,
                'model_id' => $model_id,
                'action' => $action,
                'tokens_in' => $tokens_in,
                'tokens_out' => $tokens_out,
                'duration' => $duration,
                'status' => $status,
                'user_id' => $user_id,
                'request_params' => is_array($request_params) ? wp_json_encode($request_params) : $request_params,
                'full_response' => is_array($full_response) ? wp_json_encode($full_response) : $full_response,
                'error_details' => is_array($error_details) ? wp_json_encode($error_details) : $error_details,
            ]
        );
    }

    public function get_usage_report($days = 30, $user_id = null, $filters = []) {
        $date_from = date('Y-m-d H:i:s', strtotime("-$days days"));
        $date_to = current_time('mysql');

        if (!empty($filters['date_from'])) {
            $date_from = sanitize_text_field($filters['date_from']);
        }
        if (!empty($filters['date_to'])) {
            $date_to = sanitize_text_field($filters['date_to']);
        }

        $query = "SELECT model_id, action, COUNT(*) AS calls, 
                         SUM(tokens_in) AS tokens_in, SUM(tokens_out) AS tokens_out,
                         AVG(duration) AS avg_duration
                  FROM $this->table_name WHERE created_at BETWEEN %s AND %s";
        
        $args = [$date_from, $date_to];

        if ($user_id !== null) {
            $query .= " AND user_id = %d";
            $args[] = $user_id;
        }

        if (!empty($filters['model_id'])) {
            $query .= " AND model_id = %s";
            $args[] = $filters['model_id'];
        }

        if (!empty($filters['action'])) {
            $query .= " AND action = %s";
            $args[] = $filters['action'];
        }

        if (!empty($filters['status'])) {
            $query .= " AND status = %s";
            $args[] = $filters['status'];
        }
        
        $query .= " GROUP BY model_id, action ORDER BY calls DESC";
        
        return $this->wpdb->get_results($this->wpdb->prepare($query, $args));
    }

    public function get_user_stats($user_id, $days = 30, $filters = []) {
        $date_from = date('Y-m-d H:i:s', strtotime("-$days days"));
        $date_to = current_time('mysql');

        if (!empty($filters['date_from'])) {
            $date_from = sanitize_text_field($filters['date_from']);
        }
        if (!empty($filters['date_to'])) {
            $date_to = sanitize_text_field($filters['date_to']);
        }

        $query = "SELECT
                COUNT(*) AS total_requests, 
                SUM(CASE WHEN status = 'success' THEN 1 ELSE 0 END) AS successful_requests,
                SUM(CASE WHEN status = 'failed' THEN 1 ELSE 0 END) AS failed_requests,
                AVG(duration) AS average_duration
            FROM $this->table_name WHERE user_id = %d AND created_at BETWEEN %s AND %s";
        
        $args = [$user_id, $date_from, $date_to];
        
        if (!empty($filters['model_id'])) {
            $query .= " AND model_id = %s";
            $args[] = $filters['model_id'];
        }

        if (!empty($filters['action'])) {
            $query .= " AND action = %s";
            $args[] = $filters['action'];
        }

        if (!empty($filters['status'])) {
            $query .= " AND status = %s";
            $args[] = $filters['status'];
        }
        
        return $this->wpdb->get_row($this->wpdb->prepare($query, $args));
    }

    public function purge_old_data($days = 90) {
        $date = date('Y-m-d H:i:s', strtotime("-$days days"));
        $deleted_rows = $this->wpdb->query(
            $this->wpdb->prepare("DELETE FROM $this->table_name WHERE created_at < %s", $date)
        );
        return $deleted_rows;
    }

    public function export_data($format = 'csv', $days = 30, $filters = []) {
        $date = date('Y-m-d H:i:s', strtotime("-$days days"));
        $data = $this->get_analytics_data($date, $filters);

        if ($format === 'json') {
            return wp_json_encode($data);
        } else { // CSV format
            return $this->format_csv($data);
        }
    }

    private function get_analytics_data($date, $filters = []) {
        $query = "SELECT * FROM $this->table_name WHERE created_at >= %s";
        $args = [$date];

        if (!empty($filters['model_id'])) {
            $query .= " AND model_id = %s";
            $args[] = $filters['model_id'];
        }

        if (!empty($filters['action'])) {
            $query .= " AND action = %s";
            $args[] = $filters['action'];
        }

        if (!empty($filters['status'])) {
            $query .= " AND status = %s";
            $args[] = $filters['status'];
        }

        return $this->wpdb->get_results($this->wpdb->prepare($query, $args));
    }

    private function format_csv($data) {
        $output = fopen('php://temp', 'r+');
        if (empty($data)) {
            fputcsv($output, ['No data available']);
        } else {
            fputcsv($output, array_keys((array)$data[0])); // Header row
            foreach ($data as $row) {
                fputcsv($output, (array)$row);
            }
        }
        rewind($output);
        $csv_content = stream_get_contents($output);
        fclose($output);
        return $csv_content;
    }
}
