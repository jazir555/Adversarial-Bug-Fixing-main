uclass Settings {
    private $options;
    
    public function __construct() {
        $this->options = get_option('adversarial_settings', []);
        
        add_action('admin_init', [$this, 'register_settings']);
    }
    
    public function register_settings() {
        register_setting('adversarial_settings', 'adversarial_settings');
        
        add_settings_section(
            'llm_configuration',
            __('LLM Configuration', 'adversarial-code-generator'),
            [$this, 'render_llm_configuration_section'],
            'adversarial-code-generator-settings'
        );
        
        add_settings_field(
            'llm_api_keys',
            __('LLM API Keys', 'adversarial-code-generator'),
            [$this, 'render_llm_api_keys_field'],
            'adversarial-code-generator-settings',
            'llm_configuration'
        );
        
        add_settings_field(
            'llm_models',
            __('LLM Models', 'adversarial-code-generator'),
            [$this, 'render_llm_models_field'],
            'adversarial-code-generator-settings',
            'llm_configuration'
        );
        
        add_settings_field(
            'llm_rate_limits',
            __('LLM Rate Limits', 'adversarial-code-generator'),
            [$this, 'render_llm_rate_limits_field'],
            'adversarial-code-generator-settings',
            'llm_configuration'
        );
        
        add_settings_section(
            'workflow_settings',
            __('Workflow Settings', 'adversarial-code-generator'),
            [$this, 'render_workflow_settings_section'],
            'adversarial-code-generator-settings'
        );

        add_settings_section(
            'code_formatter_settings',
            __('Code Formatter Settings', 'adversarial-bug-fixing'),
            [$this, 'render_code_formatter_settings_section'],
            'adversarial-code-generator-settings'
        );

        add_settings_field(
            'enable_code_formatter',
            __('Enable Code Formatter', 'adversarial-bug-fixing'),
            [$this, 'render_enable_code_formatter_field'],
            'adversarial-code-generator-settings',
            'code_formatter_settings'
        );

        add_settings_field(
            'code_formatter_languages',
            __('Supported Languages', 'adversarial-bug-fixing'),
            [$this, 'render_code_formatter_languages_field'],
            'adversarial-code-generator-settings',
            'code_formatter_settings'
        );

        add_settings_field(
            'max_iterations',
            __('Maximum Iterations', 'adversarial-code-generator'),
            [$this, 'render_max_iterations_field'],
            'adversarial-code-generator-settings',
            'workflow_settings'
        );
        
        add_settings_field(
            'iteration_limit',
            __('Iteration Limit', 'adversarial-code-generator'),
            [$this, 'render_iteration_limit_field'],
            'adversarial-code-generator-settings',
            'workflow_settings'
        );
    }

    public function render_iteration_limit_field() {
        $value = $this->get('iteration_limit') ?: 3;
        ?>
        <input type="number" name="adversarial_settings[iteration_limit]" value="<?php echo esc_attr($value); ?>" min="1" max="10">
        <p class="description"><?php esc_html_e('Number of iterations before adding new features.', 'adversarial-code-generator'); ?></p>
        <?php
    }


	public function render_code_formatter_settings_section() {
		echo '<p>' . esc_html__( 'Configure code formatter settings.', 'adversarial-bug-fixing' ) . '</p>';
	}

	public function render_enable_code_formatter_field() {
		$enabled = $this->get( 'enable_code_formatter' ) ?: false;
		?>
		<label for="enable_code_formatter">
			<input type="checkbox" id="enable_code_formatter" name="adversarial_settings[enable_code_formatter]" value="1" <?php checked( 1, $enabled ); ?> />
			<?php esc_html_e( 'Enable Code Formatter', 'adversarial-bug-fixing' ); ?>
		</label>
		<p class="description"><?php esc_html_e( 'Enable or disable the code formatter.', 'adversarial-bug-fixing' ); ?></p>
		<?php
	}

	public function render_code_formatter_languages_field() {
		$languages         = $this->get( 'code_formatter_languages' ) ?: array( 'php', 'javascript', 'css', 'html' );
		$available_languages = array(
			'php'        => 'PHP',
			'javascript' => 'JavaScript',
			'css'        => 'CSS',
			'html'       => 'HTML',
		);
		?>
		<div class="checkbox-group">
			<?php foreach ( $available_languages as $lang_key => $lang_label ) : ?>
				<label>
					<input type="checkbox" name="adversarial_settings[code_formatter_languages][]" value="<?php echo esc_attr( $lang_key ); ?>" <?php checked( in_array( $lang_key, $languages ) ); ?> />
					<?php echo esc_html( $lang_label ); ?>
				</label><br/>
			<?php endforeach; ?>
		</div>
		<p class="description"><?php esc_html_e( 'Select the languages for which the code formatter should be enabled.', 'adversarial-bug-fixing' ); ?></p>
		<?php
	}

	public function get( string $key ) {
		return isset( $this->options[ $key ] ) ? $this->options[ $key ] : null;
	}

	public function update( $key, $value ) {
		$this->options[ $key ] = $value;
		update_option( 'adversarial_settings', $this->options );
	}

	// Render methods for settings fields
	public function render_llm_configuration_section() {
		echo '<p>' . esc_html__( 'Configure your LLM API settings.', 'adversarial-code-generator' ) . '</p>';
	}

	public function render_llm_api_keys_field() {
		$api_keys = $this->get( 'llm_api_keys' ) ?: array();
		?>
		<textarea name="adversarial_settings[llm_api_keys]" class="large-text" rows="3"><?php echo esc_textarea( wp_json_encode( $api_keys ) ); ?></textarea>
		<p class="description"><?php esc_html_e( 'JSON object with API keys for each LLM service.', 'adversarial-code-generator' ); ?></p>
		<?php
	}

	public function render_llm_models_field() {
		$models = $this->get( 'llm_models' ) ?: array();
		?>
		<textarea name="adversarial_settings[llm_models]" class="large-text" rows="3"><?php echo esc_textarea( wp_json_encode( $models ) ); ?></textarea>
		<p class="description"><?php esc_html_e( 'JSON object specifying which LLM models to use for different tasks.', 'adversarial-code-generator' ); ?></p>
		<?php
	}

	public function render_llm_rate_limits_field() {
		$rate_limits = $this->get( 'llm_rate_limits' ) ?: array();
		?>
		<textarea name="adversarial_settings[llm_rate_limits]" class="large-text" rows="3"><?php echo esc_textarea( wp_json_encode( $rate_limits ) ); ?></textarea>
		<p class="description"><?php esc_html_e( 'JSON object with rate limits for each LLM service.', 'adversarial-code-generator' ); ?></p>
		<?php
	}

	public function render_workflow_settings_section() {
		echo '<p>' . esc_html__( 'Configure workflow settings.', 'adversarial-code-generator' ) . '</p>';
	}

	public function render_max_iterations_field() {
		$value = $this->get( 'max_iterations' ) ?: 5;
		?>
		<input type="number" name="adversarial_settings[max_iterations]" value="<?php echo esc_attr( $value ); ?>" min="1" max="20">
		<p class="description"><?php esc_html_e( 'Maximum number of iterations for bug fixing.', 'adversarial-code-generator' ); ?></p>
        <?php
    }
}
uclass Settings {
    private $options;
    
    public function __construct() {
        $this->options = get_option('adversarial_settings', []);
        
        add_action('admin_init', [$this, 'register_settings']);
    }
    
    public function register_settings() {
        register_setting('adversarial_settings', 'adversarial_settings');
        
        add_settings_section(
            'llm_configuration',
            __('LLM Configuration', 'adversarial-code-generator'),
            [$this, 'render_llm_configuration_section'],
            'adversarial-code-generator-settings'
        );
        
        add_settings_field(
            'llm_api_keys',
            __('LLM API Keys', 'adversarial-code-generator'),
            [$this, 'render_llm_api_keys_field'],
            'adversarial-code-generator-settings',
            'llm_configuration'
        );
        
        add_settings_field(
            'llm_models',
            __('LLM Models', 'adversarial-code-generator'),
            [$this, 'render_llm_models_field'],
            'adversarial-code-generator-settings',
            'llm_configuration'
        );
        
        add_settings_field(
            'llm_rate_limits',
            __('LLM Rate Limits', 'adversarial-code-generator'),
            [$this, 'render_llm_rate_limits_field'],
            'adversarial-code-generator-settings',
            'llm_configuration'
        );
        
        add_settings_section(
            'workflow_settings',
            __('Workflow Settings', 'adversarial-code-generator'),
            [$this, 'render_workflow_settings_section'],
            'adversarial-code-generator-settings'
        );

        add_settings_section(
            'code_formatter_settings',
            __('Code Formatter Settings', 'adversarial-bug-fixing'),
            [$this, 'render_code_formatter_settings_section'],
            'adversarial-code-generator-settings'
        );

        add_settings_field(
            'enable_code_formatter',
            __('Enable Code Formatter', 'adversarial-bug-fixing'),
            [$this, 'render_enable_code_formatter_field'],
            'adversarial-code-generator-settings',
            'code_formatter_settings'
        );

        add_settings_field(
            'code_formatter_languages',
            __('Supported Languages', 'adversarial-bug-fixing'),
            [$this, 'render_code_formatter_languages_field'],
            'adversarial-code-generator-settings',
            'code_formatter_settings'
        );

        add_settings_field(
            'max_iterations',
            __('Maximum Iterations', 'adversarial-code-generator'),
            [$this, 'render_max_iterations_field'],
            'adversarial-code-generator-settings',
            'workflow_settings'
        );
        
        add_settings_field(
            'iteration_limit',
            __('Iteration Limit', 'adversarial-code-generator'),
            [$this, 'render_iteration_limit_field'],
            'adversarial-code-generator-settings',
            'workflow_settings'
        );
    }

    public function render_iteration_limit_field() {
        $value = $this->get('iteration_limit') ?: 3;
        ?>
        <input type="number" name="adversarial_settings[iteration_limit]" value="<?php echo esc_attr($value); ?>" min="1" max="10">
        <p class="description"><?php esc_html_e('Number of iterations before adding new features.', 'adversarial-code-generator'); ?></p>
        <?php
    }


	public function render_code_formatter_settings_section() {
		echo '<p>' . esc_html__( 'Configure code formatter settings.', 'adversarial-bug-fixing' ) . '</p>';
	}

	public function render_enable_code_formatter_field() {
		$enabled = $this->get( 'enable_code_formatter' ) ?: false;
		?>
		<label for="enable_code_formatter">
			<input type="checkbox" id="enable_code_formatter" name="adversarial_settings[enable_code_formatter]" value="1" <?php checked( 1, $enabled ); ?> />
			<?php esc_html_e( 'Enable Code Formatter', 'adversarial-bug-fixing' ); ?>
		</label>
		<p class="description"><?php esc_html_e( 'Enable or disable the code formatter.', 'adversarial-bug-fixing' ); ?></p>
		<?php
	}

	public function render_code_formatter_languages_field() {
		$languages         = $this->get( 'code_formatter_languages' ) ?: array( 'php', 'javascript', 'css', 'html' );
		$available_languages = array(
			'php'        => 'PHP',
			'javascript' => 'JavaScript',
			'css'        => 'CSS',
			'html'       => 'HTML',
		);
		?>
		<div class="checkbox-group">
			<?php foreach ( $available_languages as $lang_key => $lang_label ) : ?>
				<label>
					<input type="checkbox" name="adversarial_settings[code_formatter_languages][]" value="<?php echo esc_attr( $lang_key ); ?>" <?php checked( in_array( $lang_key, $languages ) ); ?> />
					<?php echo esc_html( $lang_label ); ?>
				</label><br/>
			<?php endforeach; ?>
		</div>
		<p class="description"><?php esc_html_e( 'Select the languages for which the code formatter should be enabled.', 'adversarial-bug-fixing' ); ?></p>
		<?php
	}

	public function get( $key ) {
		return isset( $this->options[ $key ] ) ? $this->options[ $key ] : null;
	}

	public function update( $key, $value ) {
		$this->options[ $key ] = $value;
		update_option( 'adversarial_settings', $this->options );
	}

	// Render methods for settings fields
	public function render_llm_configuration_section() {
		echo '<p>' . esc_html__( 'Configure your LLM API settings.', 'adversarial-code-generator' ) . '</p>';
	}

	public function render_llm_api_keys_field() {
		$api_keys = $this->get( 'llm_api_keys' ) ?: array();
		?>
		<textarea name="adversarial_settings[llm_api_keys]" class="large-text" rows="3"><?php echo esc_textarea( wp_json_encode( $api_keys ) ); ?></textarea>
		<p class="description"><?php esc_html_e( 'JSON object with API keys for each LLM service.', 'adversarial-code-generator' ); ?></p>
		<?php
	}

	public function render_llm_models_field() {
		$models = $this->get( 'llm_models' ) ?: array();
		?>
		<textarea name="adversarial_settings[llm_models]" class="large-text" rows="3"><?php echo esc_textarea( wp_json_encode( $models ) ); ?></textarea>
		<p class="description"><?php esc_html_e( 'JSON object specifying which LLM models to use for different tasks.', 'adversarial-code-generator' ); ?></p>
		<?php
	}

	public function render_llm_rate_limits_field() {
		$rate_limits = $this->get( 'llm_rate_limits' ) ?: array();
		?>
		<textarea name="adversarial_settings[llm_rate_limits]" class="large-text" rows="3"><?php echo esc_textarea( wp_json_encode( $rate_limits ) ); ?></textarea>
		<p class="description"><?php esc_html_e( 'JSON object with rate limits for each LLM service.', 'adversarial-code-generator' ); ?></p>
		<?php
	}

	public function render_workflow_settings_section() {
		echo '<p>' . esc_html__( 'Configure workflow settings.', 'adversarial-code-generator' ) . '</p>';
	}

	public function render_max_iterations_field() {
		$value = $this->get( 'max_iterations' ) ?: 5;
		?>
		<input type="number" name="adversarial_settings[max_iterations]" value="<?php echo esc_attr( $value ); ?>" min="1" max="20">
		<p class="description"><?php esc_html_e( 'Maximum number of iterations for bug fixing.', 'adversarial-code-generator' ); ?></p>
		<?php
	}

}
