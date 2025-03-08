<?php
class CodeRefactoringDatabase
{
    private static $instance;
    private $wpdb;
    private $table_name;

    private function __construct()
    {
        global $wpdb;
        $this->wpdb = $wpdb;
        $this->table_name = $wpdb->prefix . 'adversarial_code_refactoring_requests';
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
            code longtext NOT NULL,
            language varchar(50) NOT NULL DEFAULT 'python',
            goal text NOT NULL,
            refactored_code longtext,
            status varchar(20) NOT NULL DEFAULT 'pending',
            error text,
            created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
            completed_at datetime,
            full_error_details longtext,
            PRIMARY KEY  (id),
            KEY status (status),
            KEY created_at (created_at)
        ) $charset_collate;";

        include_once ABSPATH . 'wp-admin/includes/upgrade.php';
        dbDelta($sql);
    }

    public function create_entry($data)
    {
        $defaults = [
            'code' => '',
            'language' => 'python',
            'goal' => '',
            'refactored_code' => null,
            'status' => 'pending',
            'error' => null,
            'created_at' => current_time('mysql'),
            'completed_at' => null
        ];
        
        $data = wp_parse_args($data, $defaults);
        
        $this->wpdb->insert(
            $this->table_name,
            [
                'code' => $data['code'],
                'language' => $data['language'],
                'goal' => $data['goal'],
                'refactored_code' => $data['refactored_code'],
                'status' => $data['status'],
                'error' => $data['error'],
                'created_at' => $data['created_at'],
                'completed_at' => $data['completed_at']
            ]
        );
        
        return $this->wpdb->insert_id;
    }

    public function update_entry($entry_id, $data)
    {
        $fields = [
            'refactored_code', 'status', 'error', 'completed_at', 'full_error_details'
        ];

        $update_data = array_intersect_key($data, array_flip($fields));

        $this->wpdb->update(
            $this->table_name,
            $update_data,
            ['id' => $entry_id]
        );
    }

    public function get_entry($entry_id)
    {
        return $this->wpdb->get_row(
            $this->wpdb->prepare("SELECT * FROM $this->table_name WHERE id = %d", $entry_id)
        );
    }

    public function get_entries($status = null, $limit = 20)
    {
        $query = "SELECT * FROM $this->table_name";
        if ($status) {
            $query .= $this->wpdb->prepare(" WHERE status = %s", $status);
        }
        $query .= " ORDER BY created_at DESC LIMIT %d";
        
        return $this->wpdb->get_results(
            $this->wpdb->prepare($query, $limit)
        );
    }
}
