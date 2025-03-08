require_once plugin_dir_path(__FILE__) . 'CodeAnalysis.php';

class CodeQualityAnalyzer {
    private $llm_handler;
    private $code_analysis;
    
    public function __construct() {
        $this->llm_handler = new LLMHandler();
        $this->code_analysis = new CodeAnalysis();
    }
    
    public function analyze($code, $language) {
        try {
            $metrics_response = $this->code_analysis->analyze_code_quality($code, $language);
            $quality_analysis_response = $this->llm_handler->analyze_code_quality($code, $language);

            return [
                'code_metrics' => isset($metrics_response['metrics']) ? $metrics_response['metrics'] : 'Code metrics analysis not available.',
                'quality_analysis' => isset($quality_analysis_response['quality_analysis']) ? $quality_analysis_response['quality_analysis'] : 'Code quality analysis not available.',
            ];

        } catch (Exception $e) {
            return [
                'error' => "Code quality analysis failed: " . $e->getMessage(),
                'details' => [
                    'message' => $e->getMessage(),
                    'trace' => $e->getTraceAsString()
                ]
            ];
        }
    }
}
