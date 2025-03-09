<?php
/**
 * Class CodeComments
 *
 * Handles code commenting functionality for the plugin, allowing users to add, 
 * retrieve, update, and delete comments on code snippets.
 */
class CodeComments {
    /**
     * @var string $table_name The name of the database table for storing code comments.
     */
    private $table_name;

    /**
     * @var string $version The version of the CodeComments class.
     */
    private $version = '1.0';

    /**
     * @var Database Database instance for data operations.
     */
    private $db;

    /**
     * Constructor for the CodeComments class.
     *
     * Initializes the Database instance and sets up the database table name and actions.
     */
    public function __construct() {
        $this->db = Database::get_instance();
        $this->table_name = $this->db->get_table_name('adversarial_code_comments');
        register_activation_hook(__FILE__, [$this, 'install']);
        add_action('admin_enqueue_scripts', [$this, 'enqueue_scripts']);
        add_action('wp_ajax_adversarial_add_comment', [$this, 'ajax_add_comment']);
        add_action('wp_ajax_nopriv_adversarial_add_comment', [$this, 'ajax_add_comment']);
        add_action('wp_ajax_adversarial_get_comments', [$this, 'ajax_get_comments']);
        add_action('wp_ajax_nopriv_adversarial_get_comments', [$this, 'ajax_get_comments']);
        add_action('wp_ajax_adversarial_update_comment', [$this, 'ajax_update_comment']);
        add_action('wp_ajax_nopriv_adversarial_update_comment', [$this, 'ajax_update_comment']);
        add_action('wp_ajax_adversarial_delete_comment', [$this, 'ajax_delete_comment']);
        add_action('wp_ajax_nopriv_adversarial_delete_comment', [$this, 'ajax_delete_comment']);
    }

    /**
     * Installation function for the CodeComments module.
     *
     * Creates the database table to store code comments using Database class.
     * @global wpdb $wpdb WordPress database abstraction object.
     */
    public function install() {
        $table_name = $this->table_name;
        $charset_collate = $this->db->wpdb->get_charset_collate();
        $sql = "CREATE TABLE $table_name (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            user_id BIGINT UNSIGNED NOT NULL,
            code_snippet_id BIGINT UNSIGNED NOT NULL,
            comment_text TEXT NOT NULL,
            timestamp TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY user_id (user_id),
            KEY code_snippet_id (code_snippet_id)
        ) $charset_collate;";
        $this->db->install_table($table_name, $sql);
    }

    /**
     * Enqueue scripts for the admin area.
     *
     * Registers and enqueues the javascript file for code comments functionality.
     */
    public function enqueue_scripts() {
        wp_enqueue_script('adversarial-code-comments', plugins_url('assets/js/code-comments.js', __FILE__), ['jquery'], $this->version, true);
        wp_localize_script('adversarial-code-comments', 'codeCommentsSettings', [
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('code_comments_nonce')
        ]);
    }

    /**
     * AJAX handler for adding a code comment.
     *
     * Handles the AJAX request to add a new comment to a code snippet and uses Database class for database interaction.
     * Retrieves comment data from POST data, saves it to the database,
     * and responds with success or error in JSON format.
     */
    public function ajax_add_comment() {
        check_ajax_referer('code_comments_nonce', 'nonce');
        if (!isset($_POST['code_snippet_id'], $_POST['comment_text'])) {
            wp_send_json_error(['message' => 'Missing parameters']);
        }
        $code_snippet_id = intval($_POST['code_snippet_id']);
        $comment_text = wp_kses_post($_POST['comment_text']);
        $result = $this->db->insert(
            $this->table_name,
            [
                'user_id' => get_current_user_id(),
                'code_snippet_id' => $code_snippet_id,
                'comment_text' => $comment_text
            ],
            [
                '%d',
                '%d',
                '%s'
            ]
        );
        if (is_wp_error($result)) {
            wp_send_json_error(['message' => 'Failed to add comment', 'error' => $result->get_error_message()]);
        }
        if ($result) {
            wp_send_json_success(['message' => 'Comment added successfully', 'comment_id' => $this->db->wpdb->insert_id]);
        } else {
            wp_send_json_error(['message' => 'Failed to add comment']);
        }
    }

    /**
     * AJAX handler for getting comments for a code snippet.
     *
     * Handles the AJAX request to retrieve comments for a specific code snippet ID and uses Database class for database interaction.
     * Retrieves code_snippet_id from POST data, fetches comments from the database,
     * and responds with comments data or an error message in JSON format.
     */
    public function ajax_get_comments() {
        check_ajax_referer('code_comments_nonce', 'nonce');
        if (!isset($_POST['code_snippet_id'])) {
            wp_send_json_error(['message' => 'Missing code snippet ID']);
        }
        $code_snippet_id = intval($_POST['code_snippet_id']);
        $snippets = $this->db->get_results(
            $this->table_name,
            $this->db->wpdb->prepare(
                "SELECT * FROM $this->table_name WHERE code_snippet_id = %d ORDER BY timestamp DESC",
                $code_snippet_id
            )
        );
        if ($snippets) {
            wp_send_json_success(['comments' => $snippets]);
        } else {
            wp_send_json_success(['comments' => [], 'message' => 'No comments found for this snippet']);
        }
    }

    /**
     * AJAX handler for updating a code comment.
     *
     * Handles the AJAX request to update an existing code comment and uses Database class for database interaction.
     * Retrieves comment_id and comment_text from POST data, updates the comment in the database,
     * and responds with success or error in JSON format.
     */
    public function ajax_update_comment() {
        check_ajax_referer('code_comments_nonce', 'nonce');
        if (!isset($_POST['comment_id'], $_POST['comment_text'])) {
            wp_send_json_error(['message' => 'Missing parameters']);
        }
        $comment_id = intval($_POST['comment_id']);
        $comment_text = wp_kses_post($_POST['comment_text']);
        $result = $this->db->update(
            $this->table_name,
            [
                'comment_text' => $comment_text
            ],
            [
                'id' => $comment_id,
                'user_id' => get_current_user_id() // Ensure user is updating their own comment
            ],
            [
                '%s'
            ],
            [
                '%d',
                '%d'
            ]
        );
        if (is_wp_error($result)) {
            wp_send_json_error(['message' => 'Failed to update comment', 'error' => $result->get_error_message()]);
        }
        if ($result !== false) {
            wp_send_json_success(['message' => 'Comment updated successfully']);
        } else {
            wp_send_json_error(['message' => 'Failed to update comment']);
        }
    }

    /**
     * AJAX handler for deleting a code comment.
     *
     * Handles the AJAX request to delete a code comment and uses Database class for database interaction.
     * Retrieves comment_id from POST data, deletes the comment from the database,
     * and responds with success or error in JSON format.
     */
    public function ajax_delete_comment() {
        check_ajax_referer('code_comments_nonce', 'nonce');
        if (!isset($_POST['comment_id'])) {
            wp_send_json_error(['message' => 'Missing comment ID']);
        }
        $comment_id = intval($_POST['comment_id']);
        $result = $this->db->delete(
            $this->table_name,
            [
                'id' => $comment_id,
                'user_id' => get_current_user_id() // Ensure user is deleting their own comment
            ],
            [
                '%d',
                '%d'
            ]
        );
        if (is_wp_error($result)) {
            wp_send_json_error(['message' => 'Failed to delete comment', 'error' => $result->get_error_message()]);
        }
        if ($result !== false) {
            wp_send_json_success(['message' => 'Comment deleted successfully']);
        } else {
            wp_send_json_error(['message' => 'Failed to delete comment']);
        }
    }

    // Implement functions to retrieve, update, and delete comments from the database.
}
new CodeComments();
