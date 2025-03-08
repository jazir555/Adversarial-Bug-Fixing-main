class CodeFormatter {
    public function format_code($code, $language) {
        $prompt = "Format the following " . $language . " code according to the language's best practices:\n\n" . $code;
        
        $llm_handler = new LLMHandler();
        $response = $llm_handler->call_llm_api($llm_handler->select_model('formatting'), $prompt, 'format_code', $language);
        if (isset($response['formatted_code'])) {
            return $response;
        } else {
            return [
                'error' => 'Code formatting failed',
                'details' => isset($response['error']) ? $response['error'] : 'Unknown error'
            ];
        }
    }
}
