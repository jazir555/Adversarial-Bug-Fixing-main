<?php

class Settings
{
    private $options;
    private static $instance;

    private function __construct()
    {
        $this->options = get_option('adversarial_settings', []);
    }

    public static function get_instance()
    {
        if (!self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function get(string $key)
    {
        return isset($this->options[$key]) ? $this->options[$key] : null;
    }

    public function update($key, $value)
    {
        $this->options[$key] = $value;
        update_option('adversarial_settings', $this->options);
    }

    public function initialize_settings()
    {
        // Initialize default settings if they don't exist
        $upload_dir = wp_upload_dir();
        $default_settings = [
            'llm_models' => [
                'generation' => 'claude',
                'checking' => 'claude',
                'fixing' => 'claude',
                'review' => 'claude',
                'performance' => 'claude',
                'formatting' => 'claude',
                'translation' => 'claude',
                'testing' => 'claude',
                'security' => 'claude'
            ],
            'llm_rotation_strategy' => 'round_robin',
            'max_iterations' => 5,
            'iteration_limit' => 3,
            'code_execution_timeout' => 30,
            'vulnerability_db_path' => trailingslashit($upload_dir['basedir']) . 'adversarial-code-generator/security/vulnerabilities.json',
        ];

        foreach ($default_settings as $key => $default_value) {
            if ($this->get($key) === null) {
                $this->update($key, $default_value);
            }
        }
    }
}
