class Exporter {
    public function export($version_id, $format = 'zip') {
        try {
            $version = VersionControl::get_instance()->get_version($version_id);
            
            switch ($format) {
                case 'zip':
                    return $this->export_as_zip($version);
                case 'github':
                    return $this->export_to_github($version);
                default:
                    throw new Exception("Unsupported export format: $format");
            }
        } catch (Exception $e) {
            throw new Exception("Export failed: " . $e->getMessage());
        }
    }
    
    private function export_as_zip($version) {
        // Create a temporary directory
        $temp_dir = wp_tempnam();
        wp_mkdir_p($temp_dir);
        
        // Create directory structure
        $project_dir = $temp_dir . '/project';
        wp_mkdir_p($project_dir);
        
        // Save code file
        $file_name = $project_dir . '/code.' . $version['language'];
        file_put_contents($file_name, $version['code']);
        
        // Create zip archive
        $zip = new ZipArchive();
        $zip_file = $temp_dir . '/project.zip';
        
        if ($zip->open($zip_file, ZipArchive::CREATE) !== TRUE) {
            throw new Exception("Failed to create zip archive: " . $zip->getStatusString());
        }
        
        if (!$zip->addGlob($project_dir . '/*')) {
            $zip->close();
            throw new Exception("Failed to add files to zip archive.");
        }
        
        if (!$zip->close()) {
            throw new Exception("Failed to close zip archive.");
        }
        
        // Read zip file contents
        $zip_contents = file_get_contents($zip_file);
        if ($zip_contents === false) {
            throw new Exception("Failed to read zip file contents.");
        }
        
        // Clean up
        if (!wp_rmdir($temp_dir)) {
            error_log("Exporter: Warning - Failed to delete temporary directory: " . $temp_dir);
        }
        
        return [
            'filename' => 'code-export-' . date('Ymd-His') . '.zip',
            'contents' => $zip_contents
        ];
    }
    
    private function export_to_github($version) {
        // Implement GitHub export logic
        // Implement GitHub export logic here (To be implemented in future)
        return [
            'error' => 'GitHub export functionality is not fully implemented yet.',
            'details' => 'GitHub export is a placeholder function. Full implementation will be added in a future version.'
        ];
    }
}
