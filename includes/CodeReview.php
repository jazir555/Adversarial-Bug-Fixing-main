<?php
/**
 * Class CodeReview
 *
 * Handles code review functionalities for the plugin, allowing users to request, 
 * conduct, and manage code reviews for code snippets.
 */
class CodeReview {
    /**
     * @var string $table_name The name of the database table for storing code review data.
     */
    private $table_name;

    /**
     * @var string $version The version of the CodeReview class.
     */
    private $version = '1.0';

    /**
     * Constructor for the CodeReview class.
     *
     * Sets up the database table name and registers activation hook for database table creation.
     */
    public function __construct() {
        global $wpdb;
        $this->table_name = $wpdb->prefix . 'adversarial_code_reviews';
        register_activation_hook(__FILE__, [$this, 'install']);
    }

    /**
     * Installation function for the CodeReview module.
     *
     * Creates the database table to store code review data.
     * @global wpdb $wpdb WordPress database abstraction object.
     */
    public function install() {
        global $wpdb;
        $charset_collate = $wpdb->get_charset_collate();
        $sql = "CREATE TABLE $this->table_name (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            code_snippet_id BIGINT UNSIGNED NOT NULL,
            reviewer_id BIGINT UNSIGNED NOT NULL,
            status VARCHAR(50) NOT NULL DEFAULT 'pending',
            comments LONGTEXT NULL, 
            timestamp TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY code_snippet_id (code_snippet_id),
            KEY reviewer_id (reviewer_id),
            KEY status (status)
        ) $charset_collate;";

        $sql_comments_table = "CREATE TABLE {$wpdb->prefix}adversarial_code_review_comments (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            review_id BIGINT UNSIGNED NOT NULL,
            user_id BIGINT UNSIGNED NOT NULL,
            comment_text TEXT NOT NULL,
            timestamp TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY review_id (review_id),
            KEY user_id (user_id)
        ) $charset_collate;";

        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
        dbDelta($sql_comments_table);
    }

    /**
     * Creates a new code review request.
     *
     * @param int $code_snippet_id The ID of the code snippet to be reviewed.
     * @param int $reviewer_id The ID of the user who will be reviewing the code.
     * @return int|WP_Error Returns the ID of the newly created review request on success, or WP_Error on failure.
     */
    public function create_code_review($code_snippet_id, $reviewer_id) {
        global $wpdb;
        $result = $wpdb->insert(
            $this->table_name,
            [
                'code_snippet_id' => intval($code_snippet_id),
                'reviewer_id' => intval($reviewer_id),
            ],
            [
                '%d',
                '%d'
            ]
        );
        if ($result) {
            return $wpdb->insert_id;
        } else {
            return new WP_Error('db_insert_error', 'Failed to create code review request.', $wpdb->last_error);
        }
    }

    /**
     * Retrieves a code review request by ID.
     *
     * @param int $review_id The ID of the code review request to retrieve.
     * @return array|null Returns the code review data array on success, or null if not found.
     */
    public function get_code_review($review_id) {
        global $wpdb;
        return $wpdb->get_row(
            $wpdb->prepare("SELECT * FROM $this->table_name WHERE id = %d", $review_id),
            ARRAY_A
        );
    }

    /**
     * Updates an existing code review request.
     *
     * @param int $review_id The ID of the code review request to update.
     * @param array $review_data Array of code review data to update (status, comments).
     * @return bool|WP_Error Returns true on success, or WP_Error on failure.
     */
    public function update_code_review($review_id, $review_data) {
        global $wpdb;
        $update_data = [];
        $format = [];
        if (isset($review_data['status'])) {
            $update_data['status'] = sanitize_key($review_data['status']);
            $format[] = '%s';
        }
        if (isset($review_data['comments'])) {
            $update_data['comments'] = wp_kses_post($review_data['comments']);
            $format[] = '%s';
        }

        if (empty($update_data)) {
            return true; // No data to update
        }

        $result = $wpdb->update(
            $this->table_name,
            $update_data,
            ['id' => $review_id],
            $format,
            ['%d']
        );

        if ($result !== false) {
            return true;
        } else {
            return new WP_Error('db_update_error', 'Failed to update code review request.', $wpdb->last_error);
        }
    }

    /**
     * Deletes a code review request by ID.
     *
     * @param int $review_id The ID of the code review request to delete.
     * @return bool|WP_Error Returns true on success, or WP_Error on failure.
     */
    public function delete_code_review($review_id) {
        global $wpdb;
        $result = $wpdb->delete(
            $this->table_name,
            ['id' => $review_id],
            ['%d']
        );

        if ($result !== false) {
            return true;
        } else {
            return new WP_Error('db_delete_error', 'Failed to delete code review request.', $wpdb->last_error);
        }
    }

    /**
     * Lists code review requests, with optional filters for code snippet ID, reviewer ID, and status.
     *
     * @param int|null $code_snippet_id Filter by code snippet ID (optional).
     * @param int|null $reviewer_id Filter by reviewer ID (optional).
     * @param string|null $status Filter by review status (optional).
     * @return array Returns an array of code review request arrays.
     */
    public function list_code_reviews($code_snippet_id = null, $reviewer_id = null, $status = null) {
        global $wpdb;
        $query = "SELECT * FROM $this->table_name WHERE 1=1";
        $prepare_args = [];

        if ($code_snippet_id) {
            $query .= " AND code_snippet_id = %d";
            $prepare_args[] = $code_snippet_id;
        }
        if ($reviewer_id) {
            $query .= " AND reviewer_id = %d";
            $prepare_args[] = $reviewer_id;
        }
        if ($status) {
            $query .= " AND status = %s";
            $prepare_args[] = $status;
        }

        $query .= " ORDER BY timestamp DESC";

        return $wpdb->get_results(
            $wpdb->prepare($query, $prepare_args),
            ARRAY_A
        );
    }

    /**
     * Adds a comment to a code review request.
     * 
     * @param int $review_id The ID of the code review request.
     * @param int $user_id The ID of the user adding the comment.
     * @param string $comment_text The comment text.
     * @return int|WP_Error Returns the ID of the newly added comment on success, or WP_Error on failure.
     */
    public function add_review_comment($review_id, $user_id, $comment_text) {
        global $wpdb;
        $result = $wpdb->insert(
            "{$wpdb->prefix}adversarial_code_review_comments",
            [
                'review_id' => intval($review_id),
                'user_id' => intval($user_id),
                'comment_text' => wp_kses_post($comment_text),
            ],
            [
                '%d',
                '%d',
                '%s'
            ]
        );
        if ($result) {
            return $wpdb->insert_id;
        } else {
            return new WP_Error('db_insert_error', 'Failed to add code review comment.', $wpdb->last_error);
        }
    }

    /**
     * Retrieves comments for a specific code review request.
     *
     * @param int $review_id The ID of the code review request.
     * @return array Returns an array of code review comment arrays.
     */
    public function get_review_comments($review_id) {
        global $wpdb;
        return $wpdb->get_results(
            $wpdb->prepare(
                "SELECT * FROM {$wpdb->prefix}adversarial_code_review_comments WHERE review_id = %d ORDER BY timestamp ASC",
                $review_id
            ),
            ARRAY_A
        );
    }
}
new CodeReview();
