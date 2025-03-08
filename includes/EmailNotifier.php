class EmailNotifier {
    public function send_completion_notification($result) {
        $settings = Settings::get_instance();
        $notification_email = $settings->get('notification_email');
        
        if (!$notification_email) {
            return;
        }
        
        $subject = wp_strip_all_tags('Code Generation Complete');
        $message = wp_strip_all_tags("Your code generation request has been completed.\n\n");
        $message .= wp_strip_all_tags("Iterations: " . intval($result['iterations']) . "\n");
        $message .= wp_strip_all_tags("Duration: " . number_format(floatval($result['duration']), 2) . "s\n");
        
        if (isset($result['features_implemented'])) {
            $message .= wp_strip_all_tags("Features implemented: " . sanitize_text_field($result['features_implemented']) . "\n");
        }
        
        $mail_result = wp_mail(sanitize_email($notification_email), $subject, $message);
        if (!$mail_result) {
            error_log('EmailNotifier: wp_mail failed to send completion notification to ' . $notification_email);
        }
    }
    
    public function send_error_notification($error) {
        $settings = Settings::get_instance();
        $notification_email = $settings->get('notification_email');
        
        if (!$notification_email) {
            return;
        }
        
        $subject = wp_strip_all_tags('Code Generation Error');
        $message = wp_strip_all_tags("An error occurred during code generation:\n\n");
        $message .= sanitize_textarea_field($error->getMessage()); // Sanitize error message
        
        $mail_result = wp_mail(sanitize_email($notification_email), $subject, $message);
        if (!$mail_result) {
            error_log('EmailNotifier: wp_mail failed to send error notification to ' . $notification_email);
        }
    }
}
