class AdminSettings {
    public function __construct() {
        add_action('admin_menu', [$this, 'add_settings_page']);
        add_action('admin_init', [$this, 'register_settings']);
    }

    public function add_settings_page() {
        add_options_page(
            __('Adversarial Code Generator Settings', 'adversarial-code-generator'),
            __('Adversarial Code Generator', 'adversarial-code-generator'),
            'manage_options',
            'adversarial-code-generator-settings',
            [$this, 'render_settings_page']
        );
    }

    public function register_settings() {
        register_setting('adversarial_settings', 'adversarial_settings');

        // Section: LLM Configuration
        add_settings_section(
            'llm_configuration',
            __('LLM Configuration', 'adversarial-code-generator'),
            [$this, 'llm_configuration_callback'],
            'adversarial-code-generator-settings'
        );

        // Field: API Keys
        add_settings_field(
            'llm_api_keys',
            __('LLM API Keys', 'adversarial-code-generator'),
            [$this, 'llm_api_keys_callback'],
            'adversarial-code-generator-settings',
            'llm_configuration'
        );

        // Field: Model Selection
        add_settings_field(
            'llm_models',
            __('LLM Models', 'adversarial-code-generator'),
            [$this, 'llm_models_callback'],
            'adversarial-code-generator-settings',
            'llm_configuration'
        );

        // Field: Rotation Strategy
        add_settings_field(
            'llm_rotation_strategy',
            __('LLM Rotation Strategy', 'adversarial-code-generator'),
            [$this, 'llm_rotation_strategy_callback'],
            'adversarial-code-generator-settings',
            'llm_configuration'
        );

        // Field: Weights
        add_settings_field(
            'llm_weights',
            __('Model Weights', 'adversarial-code-generator'),
            [$this, 'llm_weights_callback'],
            'adversarial-code-generator-settings',
            'llm_configuration'
        );

        // Section: Workflow Settings
        add_settings_section(
            'workflow_settings',
            __('Workflow Settings', 'adversarial-code-generator'),
            [$this, 'workflow_settings_callback'],
            'adversarial-code-generator-settings'
        );

        // Field: Max Iterations
        add_settings_field(
            'max_iterations',
            __('Maximum Iterations', 'adversarial-code-generator'),
            [$this, 'max_iterations_callback'],
            'adversarial-code-generator-settings',
            'workflow_settings'
        );

        // Field: Iteration Limit
        add_settings_field(
            'iteration_limit',
            __('Iteration Limit', 'adversarial-code-generator'),
            [$this, 'iteration_limit_callback'],
            'adversarial-code-generator-settings',
            'workflow_settings'
        );

        // Field: Code Execution Timeout
        add_settings_field(
            'code_execution_timeout',
            __('Code Execution Timeout (seconds)', 'adversarial-code-generator'),
            [$this, 'code_execution_timeout_callback'],
            'adversarial-code-generator-settings',
            'workflow_settings'
        );

        // Section: Advanced Security Settings
        add_settings_section(
            'advanced_security_settings',
            __('Advanced Security Settings', 'adversarial-code-generator'),
            [$this, 'advanced_security_settings_callback'],
            'adversarial-code-generator-settings'
        );

        // Field: Vulnerability Database Path
        add_settings_field(
            'vulnerability_db_path',
            __('Vulnerability Database Path', 'adversarial-code-generator'),
            [$this, 'vulnerability_db_path_callback'],
            'adversarial-code-generator-settings',
            'advanced_security_settings'
        );
    }

    public function advanced_security_settings_callback() {
        esc_html_e('Configure advanced security settings.', 'adversarial-code-generator');
    }

    public function vulnerability_db_path_callback() {
        $options = get_option('adversarial_settings', []);
        $value = isset($options['vulnerability_db_path']) ? $options['vulnerability_db_path'] : '';
        ?>
        <input type="text" name="adversarial_settings[vulnerability_db_path]" value="<?php echo esc_attr($value); ?>" class="regular-text">
        <p class="description"><?php esc_html_e('Path to the vulnerability database JSON file', 'adversarial-code-generator'); ?></p>
        <?php
        }


        public function code_execution_timeout_callback()
        {
            $options = get_option('adversarial_settings', []);
            $value = isset($options['code_execution_timeout']) ? $options['code_execution_timeout'] : 30; // Default to 30 seconds
            ?>
        <input type="number" name="adversarial_settings[code_execution_timeout]" value="<?php echo esc_attr($value); ?>" min="1" max="300">
        <p class="description"><?php esc_html_e('Maximum execution time for code in seconds', 'adversarial-code-generator'); ?></p>
            <?php
        }

        public function render_settings_page()
        {
            ?>
        <div class="wrap">
            <h1><?php esc_html_e('Adversarial Code Generator Settings', 'adversarial-code-generator'); ?></h1>
            <form action="options.php" method="post">
                <?php
                settings_fields('adversarial_settings');
                do_settings_sections('adversarial-code-generator-settings');
                submit_button();
                ?>
            </form>
        </div>
            <?php
        }

        public function llm_configuration_callback()
        {
            esc_html_e('Configure your LLM API keys and model selections.', 'adversarial-code-generator');
        }

        public function workflow_settings_callback()
        {
            esc_html_e('Configure the workflow parameters for code generation.', 'adversarial-code-generator');
        }

        public function llm_api_keys_callback()
        {
            $options = get_option('adversarial_settings', []);
            $api_keys = isset($options['llm_api_keys']) ? $options['llm_api_keys'] : [];
            ?>
        <textarea name="adversarial_settings[llm_api_keys]" class="regular-text" rows="4"><?php echo esc_textarea(wp_json_encode($api_keys)); ?></textarea>
        <p class="description"><?php esc_html_e('JSON object with API keys for each LLM (e.g., {"claude": "your-key", "gemini": "your-key"})', 'adversarial-code-generator'); ?></p>
            <?php
        }

        public function llm_models_callback()
        {
            $options = get_option('adversarial_settings', []);
            $models = isset($options['llm_models']) ? $options['llm_models'] : [
            'generation' => ['claude', 'gemini'],
            'checking' => ['claude', 'gemini'],
            'fixing' => ['claude']
            ];
            ?>
        <textarea name="adversarial_settings[llm_models]" class="regular-text" rows="4"><?php echo esc_textarea(wp_json_encode($models)); ?></textarea>
        <p class="description"><?php esc_html_e('JSON object specifying which models to use for each task', 'adversarial-code-generator'); ?></p>
            <?php
        }

        public function llm_rotation_strategy_callback()
        {
            $options = get_option('adversarial_settings', []);
            $strategy = isset($options['llm_rotation_strategy']) ? $options['llm_rotation_strategy'] : 'round_robin';
            ?>
        <select name="adversarial_settings[llm_rotation_strategy]" class="regular-text">
            <option value="round_robin" <?php selected($strategy, 'round_robin'); ?>>Round Robin</option>
            <option value="random" <?php selected($strategy, 'random'); ?>>Random</option>
            <option value="weighted" <?php selected($strategy, 'weighted'); ?>>Weighted</option>
        </select>
        <p class="description"><?php esc_html_e('Select the strategy for rotating between LLM models', 'adversarial-code-generator'); ?></p>
            <?php
        }

        public function llm_weights_callback()
        {
            $options = get_option('adversarial_settings', []);
            $weights = isset($options['llm_weights']) ? $options['llm_weights'] : [];
            ?>
        <textarea name="adversarial_settings[llm_weights]" class="regular-text" rows="2"><?php echo esc_textarea(wp_json_encode($weights)); ?></textarea>
        <p class="description"><?php esc_html_e('JSON object with weights for each LLM (e.g., {"claude": 1.0, "gemini": 0.8})', 'adversarial-code-generator'); ?></p>
            <?php
        }

        public function max_iterations_callback()
        {
            $options = get_option('adversarial_settings', []);
            $value = isset($options['max_iterations']) ? $options['max_iterations'] : 5;
            ?>
        <input type="number" name="adversarial_settings[max_iterations]" value="<?php echo esc_attr($value); ?>" min="1" max="20">
        <p class="description"><?php esc_html_e('Maximum number of iterations for bug fixing', 'adversarial-code-generator'); ?></p>
            <?php
        }

        public function iteration_limit_callback()
        {
            $options = get_option('adversarial_settings', []);
            $value = isset($options['iteration_limit']) ? $options['iteration_limit'] : 3;
            ?>
        <input type="number" name="adversarial_settings[iteration_limit]" value="<?php echo esc_attr($value); ?>" min="1" max="10">
        <p class="description"><?php esc_html_e('Number of iterations before adding a new feature', 'adversarial-code-generator'); ?></p>
            <?php
        }
        }
