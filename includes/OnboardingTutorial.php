<?php
class OnboardingTutorial
{
    public function __construct()
    {
        add_action('admin_init', [$this, 'register_settings']);
        add_action('admin_enqueue_scripts', [$this, 'enqueue_scripts']);
        add_action('adversarial_code_generator_onboarding', [$this, 'show_onboarding_tutorial']);
        add_action('wp_ajax_adversarial_complete_onboarding', [$this, 'complete_tutorial']);
    }

    public function enqueue_scripts()
    {
        wp_enqueue_script('adversarial-onboarding-tutorial', plugin_dir_url(__FILE__) . '../Assets/js/onboarding-tutorial.js', ['jquery'], '1.0', true);
        wp_localize_script(
            'adversarial-onboarding-tutorial', 'ajax_object', [
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('adversarial_onboarding_nonce'),
            ]
        );
    }

    public function register_settings()
    {
        register_setting('adversarial_settings', 'adversarial_onboarding_completed');
    }

    public function show_onboarding_tutorial()
    {
        if (get_option('adversarial_onboarding_completed')) {
            return;
        }

        echo '<div class="adversarial-onboarding-tutorial">';
        echo '<div class="tutorial-header">';
        echo '<h2>Welcome to Adversarial Code Generator!</h2>';
        echo '<p>This quick tutorial will help you get started with the plugin.</p>';
        echo '</div>';

        echo '<div class="tutorial-steps">';
        echo '<div class="tutorial-step active" data-step="1">';
        echo '<h3>Step 1: Configuration</h3>';
        echo '<p>Configure your LLM API keys in the settings page.</p>';
        echo '<button class="button next-step">Next</button>';
        echo '</div>';

        echo '<div class="tutorial-step" data-step="2">';
        echo '<h3>Step 2: Generating Code</h3>';
        echo '<p>Use the code generator to create your first piece of code.</p>';
        echo '<button class="button next-step">Next</button>';
        echo '</div>';

        echo '<div class="tutorial-step" data-step="3">';
        echo '<h3>Step 3: Refining Code</h3>';
        echo '<p>Learn how to refine and improve your generated code.</p>';
        echo '<button class="button next-step">Next</button>';
        echo '</div>';

        echo '<div class="tutorial-step" data-step="4">';
        echo '<h3>Step 4: Execution</h3>';
        echo '<p>Execute your code in a secure sandbox environment.</p>';
        echo '<button class="button next-step">Next</button>';
        echo '</div>';

        echo '<div class="tutorial-step" data-step="5">';
        echo '<h3>Step 5: Collaboration</h3>';
        echo '<p>Explore collaboration features for team projects.</p>';
        echo '<button class="button complete-tutorial">Complete Tutorial</button>';
        echo '</div>';
        echo '</div>';
        echo '</div>';
    }

    public function complete_tutorial()
    {
        update_option('adversarial_onboarding_completed', true);
        wp_die();
    }
}
