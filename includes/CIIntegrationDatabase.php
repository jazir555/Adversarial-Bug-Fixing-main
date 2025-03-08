<?php
class CIIntegrationDatabase
{
    private static $instance;
    private $wpdb;
    private $table_name;

    private function __construct()
    {
        global $wpdb;
        $this->wpdb = $wpdb;
        $this->table_name = $wpdb->prefix . 'adversarial_ci_pipeline_logs';
    }

    public static function get_instance()
    {
        if (!self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function install()
    {
        $charset_collate = $this->wpdb->get_charset_collate();
        
        $sql = "CREATE TABLE IF NOT EXISTS $this->table_name (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            pipeline_id varchar(50) NOT NULL,
            status varchar(20) NOT NULL,
            log_message longtext,
            created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY  (id),
            KEY pipeline_id (pipeline_id),
            KEY status (status),
            KEY created_at (created_at)
        ) $charset_collate;";

        include_once ABSPATH . 'wp-admin/includes/upgrade.php';
        dbDelta($sql);
    }

    public function log_pipeline_run($pipeline_id, $status, $log_message)
    {
        $this->wpdb->insert(
            $this->table_name,
            [
                'pipeline_id' => $pipeline_id,
                'status' => $status,
                'log_message' => $log_message
            ]
        );
    }

    public function get_pipeline_logs($pipeline_id, $limit = 20)
    {
        return $this->wpdb->get_results(
            $this->wpdb->prepare("SELECT * FROM $this->table_name WHERE pipeline_id = %s ORDER BY created_at DESC LIMIT %d", $pipeline_id, $limit)
        );
    }

    public function get_pipeline_status_summary($pipeline_id)
    {
        return $this->wpdb->get_row(
            $this->wpdb->prepare(
                "
                SELECT 
                    status,
                    COUNT(*) as count
                FROM $this->table_name
                WHERE pipeline_id = %s
                GROUP BY status
            ", $pipeline_id
            )
        );
    }
}
