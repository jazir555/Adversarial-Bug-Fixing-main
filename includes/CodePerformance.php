class CodePerformance {
    public function analyze_code_performance($code, $language) {
        $prompt = "Analyze the performance of the following " . $language . " code:\n\n" . $code . 
                  "\n\nIdentify potential bottlenecks, suggest optimizations, and provide recommendations for improving performance.";
        
        $llm_handler = new LLMHandler();
        $response = $llm_handler->call_llm_api($llm_handler->select_model('performance'), $prompt, 'analyze_performance', $language);
        if (isset($response['performance_analysis'])) {
            return $response;
        } else {
            return [
                'error' => 'Code performance analysis failed',
                'details' => isset($response['error']) ? $response['error'] : 'Unknown error'
            ];
        }
    }

    public function optimize_code($code, $language) {
        $prompt = "Optimize the following " . $language . " code for performance:\n\n" . $code . 
                  "\n\nApply appropriate optimizations while maintaining code readability and functionality.";
        
        $llm_handler = new LLMHandler();
        $response =  $llm_handler->call_llm_api($llm_handler->select_model('optimization'), $prompt, 'optimize_code', $language);
        if (isset($response['optimized_code'])) {
            return $response;
        } else {
            return [
                'error' => 'Code optimization failed',
                'details' => isset($response['error']) ? $response['error'] : 'Unknown error'
            ];
        }
    }
}
