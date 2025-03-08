class RESTAPI {
    public function __construct() {
        add_action('rest_api_init', [$this, 'register_routes']);
    }
    
    public function register_routes() {
        register_rest_route('adversarial-code-generator/v1', '/generate', [
            'methods' => WP_REST_Server::CREATABLE,
            'callback' => [$this, 'generate_code'],
            'permission_callback' => function() {
                return current_user_can('generate_code');
            }
        ]);
        
        register_rest_route('adversarial-code-generator/v1', '/execute', [
            'methods' => WP_REST_Server::CREATABLE,
            'callback' => [$this, 'execute_code'],
            'permission_callback' => function() {
                return current_user_can('execute_generated_code');
            }
        ]);
        
        register_rest_route('adversarial-code-generator/v1', '/history', [
            'methods' => WP_REST_Server::READABLE,
            'callback' => [$this, 'get_code_history'],
            'permission_callback' => function() {
                return current_user_can('view_code_history');
            }
        ]);
    }
    
    public function generate_code($request) {
        $params = $request->get_params();
        
        if (!isset($params['prompt'])) {
            return new WP_Error('missing_prompt', 'Prompt parameter is required', ['status' => 400]);
        }
        
        $prompt = sanitize_text_field($params['prompt']);
        $language = isset($params['language']) ? sanitize_text_field($params['language']) : 'python';
        
        $llm_handler = new LLMHandler();
        try {
            $code = $llm_handler->generate_code($prompt, $language);
            return new WP_REST_Response([
                'code' => $code,
                'language' => $language
            ], 200);
        } catch (Exception $e) {
            return new WP_Error('generation_failed', $e->getMessage(), ['status' => 500]);
        }
    }
    
    public function execute_code($request) {
        $params = $request->get_params();
        
        if (!isset($params['code'])) {
            return new WP_Error('missing_code', 'Code parameter is required', ['status' => 400]);
        }
        
        $code = sanitize_textarea_field($params['code']);
        $language = isset($params['language']) ? sanitize_text_field($params['language']) : 'python';
        
        $sandbox = new Sandbox();
        try {
            $output = $sandbox->execute_code($code, $language);
            return new WP_REST_Response([
                'output' => $output
            ], 200);
        } catch (Exception $e) {
            return new WP_Error('execution_failed', $e->getMessage(), ['status' => 500]);
        }
    }
    
    public function get_code_history($request) {
        $history = new CodeHistory();
        $limit = isset($request['limit']) ? intval($request['limit']) : 20;
        
        return new WP_REST_Response($history->get_history($limit), 200);
    }
}