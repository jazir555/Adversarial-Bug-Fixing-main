class AdminUI {
    public function __construct() {
        add_action('admin_menu', [$this, 'add_admin_menu']);
    }

    public function add_admin_menu() {
        add_menu_page(
            __('Adversarial Code Generator', 'adversarial-code-generator'),
            __('Adversarial Code Generator', 'adversarial-code-generator'),
            'manage_options',
            'adversarial-code-generator',
            [$this, 'render_admin_page'],
            'dashicons-editor-code',
            6
        );
        
        add_submenu_page(
            'adversarial-code-generator',
            __('Settings', 'adversarial-code-generator'),
            __('Settings', 'adversarial-code-generator'),
            'manage_code_generator_settings',
            'adversarial-code-generator-settings',
            [$this, 'render_settings_page']
        );
        
        add_submenu_page(
            'adversarial-code-generator',
            __('Analytics', 'adversarial-code-generator'),
            __('Analytics', 'adversarial-code-generator'),
            'view_code_analytics',
            'adversarial-code-generator-analytics',
            [$this, 'render_analytics_page']
        );
        
        add_submenu_page(
            'adversarial-code-generator',
            __('Execute Code', 'adversarial-code-generator'),
            __('Execute Code', 'adversarial-code-generator'),
            'execute_generated_code',
            'adversarial-code-generator-execute',
            [$this, 'render_execution_page']
        );
        
        add_submenu_page(
            'adversarial-code-generator',
            __('Code Comparison', 'adversarial-code-generator'),
            __('Code Comparison', 'adversarial-code-generator'),
            'manage_options',
            'adversarial-code-generator-comparison',
            [$this, 'render_comparison_page']
        );
        
        add_submenu_page(
            'adversarial-code-generator',
            __('Git Repositories', 'adversarial-code-generator'),
            __('Git Repositories', 'adversarial-code-generator'),
            'manage_options',
            'adversarial-code-generator-git',
            [$this, 'render_git_management_page']
        );
        
        add_submenu_page(
            'adversarial-code-generator',
            __('Documentation', 'adversarial-code-generator'),
            __('Documentation', 'adversarial-code-generator'),
            'read',
            'adversarial-code-generator-docs',
            [$this, 'render_docs_page']
        );
        
        add_submenu_page(
            'adversarial-code-generator',
            __('Code Templates', 'adversarial-code-generator'),
            __('Code Templates', 'adversarial-code-generator'),
            'manage_options',
            'adversarial-code-generator-templates',
            [$this, 'render_templates_page']
        );
        
        add_submenu_page(
            'adversarial-code-generator',
            __('Collaboration Projects', 'adversarial-code-generator'),
            __('Collaboration Projects', 'adversarial-code-generator'),
            'manage_options',
            'adversarial-code-generator-collaboration',
            [$this, 'render_collaboration_page']
        );
        
        add_submenu_page(
            'adversarial-code-generator',
            __('Code History', 'adversarial-code-generator'),
            __('Code History', 'adversarial-code-generator'),
            'view_code_history',
            'adversarial-code-generator-history',
            [$this, 'render_history_page']
        );
        
        add_submenu_page(
            'adversarial-code-generator',
            __('Security Management', 'adversarial-code-generator'),
            __('Security Management', 'adversarial-code-generator'),
            'manage_options',
            'adversarial-code-generator-security',
            [$this, 'render_security_page']
        );
        
        add_submenu_page(
            'adversarial-code-generator',
            __('Performance Optimization', 'adversarial-code-generator'),
            __('Performance Optimization', 'adversarial-code-generator'),
            'manage_options',
            'adversarial-code-generator-performance',
            [$this, 'render_performance_page']
        );
        
        add_submenu_page(
            'adversarial-code-generator',
            __('Code Snippets', 'adversarial-code-generator'),
            __('Code Snippets', 'adversarial-code-generator'),
            'manage_options',
            'adversarial-code-generator-snippets',
            [$this, 'render_snippets_page']
        );
        
        add_submenu_page(
            'adversarial-code-generator',
            __('Code Environments', 'adversarial-code-generator'),
            __('Code Environments', 'adversarial-code-generator'),
            'manage_options',
            'adversarial-code-generator-environments',
            [$this, 'render_environments_page']
        );
        
        add_submenu_page(
            'adversarial-code-generator',
            __('CI/CD Pipelines', 'adversarial-code-generator'),
            __('CI/CD Pipelines', 'adversarial-code-generator'),
            'manage_options',
            'adversarial-code-generator-ci',
            [$this, 'render_ci_pipelines_page']
        );
    }

    public function render_admin_page() {
        if (!current_user_can('manage_options')) {
            wp_die(__('You do not have sufficient permissions to access this page.', 'adversarial-code-generator'));
        }
        ?>
        <div class="wrap">
            <h1><?php esc_html_e('Adversarial Code Generator', 'adversarial-code-generator'); ?></h1>
            <?php 
                $this->render_comparison_page();
                $this->render_ci_pipelines_page();
            ?>
        </div>
        <?php
    }

    public function render_comparison_page() {
        ?>
        <h2><?php esc_html_e('Code Comparison', 'adversarial-code-generator'); ?></h2>
        <div class="code-comparison-container">
            <div class="code-editor-group original-code">
                <h3><?php esc_html_e('Original Code', 'adversarial-code-generator'); ?></h3>
                <?php echo CodeEditor::render_editor('original-code-editor'); ?>
            </div>
            <div class="code-editor-group modified-code">
                <h3><?php esc_html_e('Modified Code', 'adversarial-code-generator'); ?></h3>
                <?php echo CodeEditor::render_editor('modified-code-editor'); ?>
            </div>
            <div class="comparison-actions">
                <button class="button button-primary compare-button"><?php esc_html_e('Compare Codes', 'adversarial-code-generator'); ?></button>
            </div>
            <div class="comparison-diff-output">
                <!-- Diff output will be rendered here -->
            </div>
        </div>
        <?php
    }

    public function render_ci_pipelines_page() {
        if (!current_user_can('manage_options')) {
            wp_die(__('You do not have sufficient permissions to access this page.', 'adversarial-code-generator'));
        }
        ?>
        <div class="wrap">
            <h1><?php esc_html_e('CI/CD Pipelines', 'adversarial-code-generator'); ?></h1>
            <div class="adversarial-ci-pipelines-container">
                <div class="pipeline-actions">
                    <h2><?php esc_html_e('Create New Pipeline', 'adversarial-code-generator'); ?></h2>
                    <form method="post" class="pipeline-form">
                        <?php wp_nonce_field('adversarial_create_pipeline', 'adversarial_nonce'); ?>
                        <div class="form-group">
                            <label for="pipeline_name"><?php esc_html_e('Pipeline Name:', 'adversarial-code-generator'); ?></label>
                            <input type="text" id="pipeline_name" name="pipeline_name" class="regular-text" required>
                        </div>
                        <div class="form-group">
                            <label for="pipeline_repository"><?php esc_html_e('Repository URL:', 'adversarial-code-generator'); ?></label>
                            <input type="url" id="pipeline_repository" name="pipeline_repository" class="regular-text" required>
                        </div>
                        <div class="form-group">
                            <label for="pipeline_branch"><?php esc_html_e('Branch:', 'adversarial-code-generator'); ?></label>
                            <input type="text" id="pipeline_branch" name="pipeline_branch" class="regular-text" value="main">
                        </div>
                        <div class="form-group">
                            <label for="pipeline_build_command"><?php esc_html_e('Build Command:', 'adversarial-code-generator'); ?></label>
                            <input type="text" id="pipeline_build_command" name="pipeline_build_command" class="regular-text">
                        </div>
                        <div class="form-group">
                            <label for="pipeline_test_command"><?php esc_html_e('Test Command:', 'adversarial-code-generator'); ?></label>
                            <input type="text" id="pipeline_test_command" name="pipeline_test_command" class="regular-text">
                        </div>
                        <button type="submit" class="button button-primary"><?php esc_html_e('Create Pipeline', 'adversarial-code-generator'); ?></button>
                    </form>
                    
                    <?php
                    if (isset($_POST['pipeline_name']) && wp_verify_nonce($_POST['adversarial_nonce'], 'adversarial_create_pipeline')) {
                        try {
                            $name = sanitize_text_field($_POST['pipeline_name']);
                            $repository = esc_url_raw($_POST['pipeline_repository']);
                            $branch = isset($_POST['pipeline_branch']) ? sanitize_text_field($_POST['pipeline_branch']) : 'main';
                            $build_command = isset($_POST['pipeline_build_command']) ? sanitize_text_field($_POST['pipeline_build_command']) : '';
                            $test_command = isset($_POST['pipeline_test_command']) ? sanitize_text_field($_POST['pipeline_test_command']) : '';
                            
                            $ci = new CIIntegration();
                            $ci->create_pipeline(
                                $name, [
                                'repository' => $repository,
                                'branch' => $branch,
                                'build_command' => $build_command,
                                'test_command' => $test_command
                                ]
                            );
                            
                            echo '<div class="notice notice-success"><p>' . esc_html__('Pipeline created successfully!', 'adversarial-code-generator') . '</p></div>';
                        } catch (Exception $e) {
                            echo '<div class="notice notice-error"><p>' . esc_html__('Pipeline creation failed: ', 'adversarial-code-generator') . esc_html($e->getMessage()) . '</p></div>';
                        }
                    }
                    ?>
                </div>
                
                <div class="pipelines-list">
                    <h2><?php esc_html_e('Existing Pipelines', 'adversarial-code-generator'); ?></h2>
                    <table class="wp-list-table widefat fixed striped">
                        <thead>
                            <tr>
                                <th><?php esc_html_e('Pipeline ID', 'adversarial-code-generator'); ?></th>
                                <th><?php esc_html_e('Name', 'adversarial-code-generator'); ?></th>
                                <th><?php esc_html_e('Repository', 'adversarial-code-generator'); ?></th>
                                <th><?php esc_html_e('Branch', 'adversarial-code-generator'); ?></th>
                                <th><?php esc_html_e('Last Run', 'adversarial-code-generator'); ?></th>
                                <th><?php esc_html_e('Actions', 'adversarial-code-generator'); ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $ci = new CIIntegration();
                            $pipelines = $ci->list_pipelines();
                            
                            foreach ($pipelines as $pipeline) {
                                ?>
                                <tr>
                                    <td><?php echo esc_html($pipeline['id']); ?></td>
                                    <td><?php echo esc_html($pipeline['name']); ?></td>
                                    <td><?php echo esc_html($pipeline['repository']); ?></td>
                                    <td><?php echo esc_html($pipeline['branch']); ?></td>
                                    <td><?php echo esc_html($pipeline['last_run'] ?: 'Never'); ?></td>
                                    <td>
                                        <button class="button run-pipeline" data-id="<?php echo esc_attr($pipeline['id']); ?>">
                                            <?php esc_html_e('Run Pipeline', 'adversarial-code-generator'); ?>
                                        </button>
                                        <button class="button edit-pipeline" data-id="<?php echo esc_attr($pipeline['id']); ?>">
                                            <?php esc_html_e('Edit', 'adversarial-code-generator'); ?>
                                        </button>
                                        <button class="button delete-pipeline" data-id="<?php echo esc_attr($pipeline['id']); ?>">
                                            <?php esc_html_e('Delete', 'adversarial-code-generator'); ?>
                                        </button>
                                    </td>
                                </tr>
                                <?php
                            }
                            ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        <?php
    }

    public function render_settings_page() {
        if (!current_user_can('manage_code_generator_settings')) {
            wp_die(__('You do not have sufficient permissions to access this page.', 'adversarial-code-generator'));
        }
        ?>
        <div class="wrap">
            <h1><?php esc_html_e('Adversarial Code Generator Settings', 'adversarial-code-generator'); ?></h1>
            <form method="post" action="options.php">
                <?php
                    settings_fields('adversarial_code_generator_settings');
                    do_settings_sections('adversarial-code-generator-settings');
                    submit_button('Save Settings');
                ?>
            </form>
        </div>
        <?php
    }

    public function render_analytics_page() {
        if (!current_user_can('view_code_analytics')) {
            wp_die(__('You do not have sufficient permissions to access this page.', 'adversarial-code-generator'));
        }
        ?>
        <div class="wrap">
            <h1><?php esc_html_e('Analytics Dashboard', 'adversarial-code-generator'); ?></h1>
            <div id="analytics-dashboard">
                <p><?php esc_html_e('This is where analytics data will be displayed.', 'adversarial-code-generator'); ?></p>
            </div>
        </div>
        <?php
    }
    
    public function render_execution_page() {
        if (!current_user_can('execute_generated_code')) {
            wp_die(__('You do not have sufficient permissions to access this page.', 'adversarial-code-generator'));
        }
        ?>
        <div class="wrap">
            <h1><?php esc_html_e('Execute Code', 'adversarial-code-generator'); ?></h1>
            <div id="code-execution-area">
                <p><?php esc_html_e('This is where code execution interface will be.', 'adversarial-code-generator'); ?></p>
            </div>
        </div>
        <?php
    }
    
    public function render_git_management_page() {
        if (!current_user_can('manage_options')) {
            wp_die(__('You do not have sufficient permissions to access this page.', 'adversarial-code-generator'));
        }
        ?>
        <div class="wrap">
            <h1><?php esc_html_e('Git Repository Management', 'adversarial-code-generator'); ?></h1>
            <div id="git-management-area">
                <p><?php esc_html_e('This is where Git repository management interface will be.', 'adversarial-code-generator'); ?></p>
            </div>
        </div>
        <?php
    }
    
    public function render_docs_page() {
        ?>
        <div class="wrap">
            <h1><?php esc_html_e('Plugin Documentation', 'adversarial-code-generator'); ?></h1>
            <div id="plugin-documentation">
                <p><?php esc_html_e('This is where plugin documentation will be displayed.', 'adversarial-code-generator'); ?></p>
            </div>
        </div>
        <?php
    }
        
    public function render_templates_page() {
        if (!current_user_can('manage_options')) {
            wp_die(__('You do not have sufficient permissions to access this page.', 'adversarial-code-generator'));
        }
        ?>
        <div class="wrap">
            <h1><?php esc_html_e('Code Templates Management', 'adversarial-code-generator'); ?></h1>
            <div id="templates-management-area">
                <p><?php esc_html_e('This is where code templates management interface will be.', 'adversarial-code-generator'); ?></p>
            </div>
        </div>
        <?php
    }
            
    public function render_collaboration_page() {
        if (!current_user_can('manage_options')) {
            wp_die(__('You do not have sufficient permissions to access this page.', 'adversarial-code-generator'));
        }
        ?>
        <div class="wrap">
            <h1><?php esc_html_e('Collaboration Projects', 'adversarial-code-generator'); ?></h1>
            <div id="collaboration-projects-area">
                <p><?php esc_html_e('This is where collaboration projects interface will be.', 'adversarial-code-generator'); ?></p>
            </div>
        </div>
        <?php
    }
                
    public function render_history_page() {
        if (!current_user_can('view_code_history')) {
            wp_die(__('You do not have sufficient permissions to access this page.', 'adversarial-code-generator'));
        }
        ?>
        <div class="wrap">
            <h1><?php esc_html_e('Code History', 'adversarial-code-generator'); ?></h1>
            <div id="code-history-area">
                <p><?php esc_html_e('This is where code history will be displayed.', 'adversarial-code-generator'); ?></p>
            </div>
        </div>
        <?php
    }
                    
    public function render_security_page() {
        if (!current_user_can('manage_options')) {
            wp_die(__('You do not have sufficient permissions to access this page.', 'adversarial-code-generator'));
        }
        ?>
        <div class="wrap">
            <h1><?php esc_html_e('Security Management', 'adversarial-code-generator'); ?></h1>
            <div id="security-management-area">
                <p><?php esc_html_e('This is where security management interface will be.', 'adversarial-code-generator'); ?></p>
            </div>
        </div>
        <?php
    }
                        
    public function render_performance_page() {
        if (!current_user_can('manage_options')) {
            wp_die(__('You do not have sufficient permissions to access this page.', 'adversarial-code-generator'));
        }
        ?>
        <div class="wrap">
            <h1><?php esc_html_e('Performance Optimization', 'adversarial-code-generator'); ?></h1>
            <div id="performance-optimization-area">
                <p><?php esc_html_e('This is where performance optimization interface will be.', 'adversarial-code-generator'); ?></p>
            </div>
        </div>
        <?php
    }
                            
    public function render_snippets_page() {
        if (!current_user_can('manage_options')) {
            wp_die(__('You do not have sufficient permissions to access this page.', 'adversarial-code-generator'));
        }
        ?>
        <div class="wrap">
            <h1><?php esc_html_e('Code Snippets', 'adversarial-code-generator'); ?></h1>
            <div id="code-snippets-area">
                <p><?php esc_html_e('This is where code snippets management interface will be.', 'adversarial-code-generator'); ?></p>
            </div>
        </div>
        <?php
    }
                                
    public function render_environments_page() {
        if (!current_user_can('manage_options')) {
            wp_die(__('You do not have sufficient permissions to access this page.', 'adversarial-code-generator'));
        }
        ?>
        <div class="wrap">
            <h1><?php esc_html_e('Code Environments', 'adversarial-code-generator'); ?></h1>
            <div id="code-environments-area">
                <p><?php esc_html_e('This is where code environments management interface will be.', 'adversarial-code-generator'); ?></p>
            </div>
        </div>
        <?php
    }
}
