class UnitTestGenerator {
    private $llm_handler;
    
    public function __construct() {
        $this->llm_handler = new LLMHandler();
    }
    
    public function generate_tests($code, $language) {
        try {
            $response = $this->llm_handler->generate_tests($code, $language);
            if (isset($response['unit_tests'])) {
                return $response;
            } else {
                return [
                    'error' => 'Unit test generation failed',
                    'details' => isset($response['error']) ? $response['error'] : 'Unknown error'
                ];
            }
        } catch (Exception $e) {
            return [
                'error' => "Test generation failed: " . $e->getMessage(),
                'details' => [
                    'message' => $e->getMessage(),
                    'trace' => $e->getTraceAsString()
                ]
            ];
        }
    }
}
