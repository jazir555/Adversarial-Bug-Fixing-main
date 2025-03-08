<?php
/**
 * Class FileBasedCollaborationSessionManager
 *
 * Manages basic collaboration sessions using file-based storage.
 * Note: This class does not implement real-time collaboration features.
 *       It provides basic session management for storing and retrieving
 *       code and metadata for collaboration sessions, but lacks
 *       real-time synchronization or communication capabilities.
 *       For true real-time collaboration, a separate implementation
 *       using WebSockets or WebRTC and a real-time database would be required.
 */
class FileBasedCollaborationSessionManager
{
    private $collaboration_dir;
    
    public function __construct()
    {
        $upload_dir = wp_upload_dir();
        $this->collaboration_dir = trailingslashit($upload_dir['basedir']) . 'adversarial-code-generator/collaboration';
        wp_mkdir_p($this->collaboration_dir);
    }

    /**
     * Creates a new collaboration session and stores it in file-based storage.
     *
     * Note: This function does not implement real-time features.
     *
     * @param  string $project_name The name of the collaboration project.
     * @param  string $initial_code The initial code for the session.
     * @return string The ID of the newly created session.
     */
    public function create_collaboration_session($project_name, $initial_code = '')
    {
        $session_id = uniqid('collab_');
        $session_dir = $this->collaboration_dir . '/' . $session_id;
        wp_mkdir_p($session_dir);
        
        file_put_contents($session_dir . '/code.txt', $initial_code);
        file_put_contents(
            $session_dir . '/metadata.json', wp_json_encode(
                [
                'project_name' => sanitize_text_field($project_name),
                'created_at' => current_time('mysql'),
                'participants' => []
                ]
            )
        );
        
        return $session_id;
    }

    /**
     * Retrieves a collaboration session by ID from file-based storage.
     *
     * Note: This function does not implement real-time features.
     *
     * @param  string $session_id The ID of the collaboration session to retrieve.
     * @return array|null An array containing the session code and metadata, or null if not found.
     */
    public function get_collaboration_session($session_id)
    {
        $session_dir = $this->collaboration_dir . '/' . $session_id;
        
        if (!is_dir($session_dir)) { // Use is_dir to check if directory exists
            return null;
        }
        
        $code = file_get_contents($session_dir . '/code.txt');
        $metadata = json_decode(file_get_contents($session_dir . '/metadata.json'), true);
        
        return [
            'code' => $code,
            'metadata' => $metadata
        ];
    }

    /**
     * Updates the code for a collaboration session in file-based storage.
     * Logs user changes in metadata.
     *
     * Note: This function does not implement real-time features.
     *
     * @param  string $session_id The ID of the collaboration session to update.
     * @param  string $new_code   The new code content.
     * @param  int    $user_id    The ID of the user updating the code.
     * @return bool True if the update was successful, false otherwise.
     */
    public function update_collaboration_session($session_id, $new_code, $user_id)
    {
        $session_dir = $this->collaboration_dir . '/' . $session_id;
        
        if (!is_dir($session_dir)) { // Use is_dir to check if directory exists
            return false;
        }
        
        file_put_contents($session_dir . '/code.txt', $new_code);
        
        $metadata = json_decode(file_get_contents($session_dir . '/metadata.json'), true);
        if (!is_array($metadata['participants'])) {
             $metadata['participants'] = []; // Initialize participants array if it's not an array (for robustness)
        }
        $metadata['participants'][] = [
            'user_id' => intval($user_id), // Ensure user_id is an integer
            'updated_at' => current_time('mysql'),
            'changes' => wp_kses_post($new_code) // Sanitize new_code
        ];
        
        file_put_contents($session_dir . '/metadata.json', wp_json_encode($metadata));
        
        return true;
    }

    /**
     * Lists recent collaboration sessions from file-based storage.
     *
     * Note: This function does not implement real-time features.
     *
     * @param  int $limit The maximum number of sessions to list.
     * @return array An array of collaboration session summaries.
     */
    public function list_collaboration_sessions($limit = 20)
    {
        $sessions = [];
        $session_dirs = glob($this->collaboration_dir . '/*', GLOB_ONLYDIR);
        
        // Use usort with a closure for sorting
        usort(
            $sessions_dirs, function ($a, $b) {
                return filemtime($b) - filemtime($a);
            }
        );
        
        foreach (array_slice($session_dirs, 0, $limit) as $dir) {
            $metadata = json_decode(file_get_contents($dir . '/metadata.json'), true);
            if (!isset($metadata['project_name']) || !isset($metadata['created_at'])) {
                continue; // Skip if metadata is incomplete or missing required fields
            }
            $sessions[] = [
                'id' => basename($dir),
                'project_name' => sanitize_text_field($metadata['project_name']), // Sanitize project_name
                'created_at' => sanitize_text_field($metadata['created_at']), // Sanitize created_at
                'last_updated' => filemtime($dir)
            ];
        }
        
        return $sessions;
    }
}
