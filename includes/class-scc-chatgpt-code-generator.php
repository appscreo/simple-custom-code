<?php

/**
 * ChatGPT Code Generator
 *
 * @package CustomCodeManager
 */

if (! defined('ABSPATH')) {
    exit;
}


class SCC_ChatGPTCodeGenerator
{

    private $api_key;
    private $api_url = 'https://api.openai.com/v1/chat/completions';
    private $model = 'gpt-4.1';
    private $max_tokens = 2000;
    private $temperature = 0.3; // Lower temperature for more consistent code generation

    /**
     * Constructor
     * 
     * @param string $api_key OpenAI API key
     * @param string $model Model to use (default: gpt-4.1)
     */
    public function __construct($api_key, $model = 'gpt-4.1')
    {
        $this->api_key = $api_key;
        $this->model = $model;
    }

    /**
     * Set configuration options
     * 
     * @param array $config Configuration array
     */
    public function setConfig($config = [])
    {
        if (isset($config['max_tokens'])) {
            $this->max_tokens = $config['max_tokens'];
        }
        if (isset($config['temperature'])) {
            $this->temperature = $config['temperature'];
        }
        if (isset($config['model'])) {
            $this->model = $config['model'];
        }
    }

    public function setModel($model = '')
    {
        $this->model = $model;
    }

    /**
     * Generate code based on type and description
     * 
     * @param string $code_type Type of code (css, js, html, php)
     * @param string $description Description of what the code should do
     * @param array $options Additional options
     * @return array Response with generated code and metadata
     */
    public function generateCode($code_type, $description, $options = [])
    {
        try {
            // Validate code type
            $valid_types = ['css', 'js', 'javascript', 'html', 'php'];
            if (!in_array(strtolower($code_type), $valid_types)) {
                throw new Exception("Invalid code type. Supported types: " . implode(', ', $valid_types));
            }

            // Build the prompt
            $prompt = $this->buildPrompt($code_type, $description, $options);

            // Make API request
            $response = $this->makeApiRequest($prompt);

            // Extract code from response
            $generated_code = $this->extractCode($response, $code_type);

            return [
                'success' => true,
                'code' => $generated_code,
                'type' => strtolower($code_type),
                'description' => $description,
                'tokens_used' => $response['usage']['total_tokens'] ?? 0,
                'raw_response' => $response['choices'][0]['message']['content'] ?? ''
            ];
        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage(),
                'code' => '',
                'type' => strtolower($code_type),
                'description' => $description
            ];
        }
    }

    /**
     * Build optimized prompt for code generation
     * 
     * @param string $code_type
     * @param string $description
     * @param array $options
     * @return string
     */
    private function buildPrompt($code_type, $description, $options = [])
    {
        $code_type = strtolower($code_type);

        // Base prompt templates for each code type
        $prompts = [
            'css' => "Generate clean, modern CSS code for: {description}
                     
                     Requirements:
                     - Use modern CSS features and best practices
                     - Include responsive design considerations
                     - Add helpful comments
                     - Ensure cross-browser compatibility
                     - Return ONLY the CSS code without explanations",

            'js' => "Generate clean, modern JavaScript code for: {description}
                    
                    Requirements:
                    - Use ES6+ features when appropriate
                    - Include error handling
                    - Add helpful comments
                    - Follow JavaScript best practices
                    - Ensure browser compatibility
                    - Return ONLY the JavaScript code without explanations",

            'javascript' => "Generate clean, modern JavaScript code for: {description}
                           
                           Requirements:
                           - Use ES6+ features when appropriate
                           - Include error handling
                           - Add helpful comments
                           - Follow JavaScript best practices
                           - Ensure browser compatibility
                           - Return ONLY the JavaScript code without explanations",

            'html' => "Generate semantic, accessible HTML code for: {description}
                      
                      Requirements:
                      - Use semantic HTML5 elements
                      - Include proper accessibility attributes
                      - Add helpful comments
                      - Follow HTML best practices
                      - Ensure mobile-friendly structure
                      - Return ONLY the HTML code without explanations",

            'php' => "Generate secure, efficient PHP code for: {description}
                     
                     Requirements:
                     - Follow WordPress coding standards if applicable
                     - Include proper error handling and validation
                     - Add helpful comments
                     - Use secure coding practices
                     - Follow PSR standards when possible
                     - Return ONLY the PHP code without explanations"
        ];

        $base_prompt = $prompts[$code_type] ?? $prompts['js'];
        $prompt = str_replace('{description}', $description, $base_prompt);

        // Add additional requirements from options
        if (!empty($options['requirements'])) {
            $prompt .= "\n\nAdditional requirements:\n" . implode("\n", (array)$options['requirements']);
        }

        // Add framework/library specific instructions
        if (!empty($options['framework'])) {
            $prompt .= "\n\nUse {$options['framework']} framework/library.";
        }


        return $prompt;
    }

    /**
     * Make API request to OpenAI
     * 
     * @param string $prompt
     * @return array
     */
    private function makeApiRequest($prompt)
    {
        $data = [
            'model' => $this->model,
            'messages' => [
                [
                    'role' => 'user',
                    'content' => $prompt
                ]
            ],
            'max_tokens' => $this->max_tokens,
            'temperature' => $this->temperature
        ];

        $headers = [
            'Content-Type: application/json',
            'Authorization: Bearer ' . $this->api_key
        ];

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $this->api_url);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 60);

        $response = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);

        if (curl_error($ch)) {
            throw new Exception('cURL error: ' . curl_error($ch));
        }

        curl_close($ch);

        if ($http_code !== 200) {
            $error_response = json_decode($response, true);
            $error_message = $error_response['error']['message'] ?? 'Unknown API error';
            throw new Exception("OpenAI API error ({$http_code}): {$error_message}");
        }

        $decoded_response = json_decode($response, true);

        if (!$decoded_response) {
            throw new Exception('Failed to decode API response');
        }

        return $decoded_response;
    }

    /**
     * Extract clean code from API response
     * 
     * @param array $response
     * @param string $code_type
     * @return string
     */
    private function extractCode($response, $code_type)
    {
        $content = $response['choices'][0]['message']['content'] ?? '';

        // Remove code block markers if present
        $content = preg_replace('/```[a-z]*\n?/', '', $content);
        $content = preg_replace('/```\n?/', '', $content);

        // Remove extra whitespace
        $content = trim($content);

        return $content;
    }

    /**
     * Generate CSS code
     * 
     * @param string $description
     * @param array $options
     * @return array
     */
    public function generateCSS($description, $options = [])
    {
        return $this->generateCode('css', $description, $options);
    }

    /**
     * Generate JavaScript code
     * 
     * @param string $description
     * @param array $options
     * @return array
     */
    public function generateJS($description, $options = [])
    {
        return $this->generateCode('js', $description, $options);
    }

    /**
     * Generate HTML code
     * 
     * @param string $description
     * @param array $options
     * @return array
     */
    public function generateHTML($description, $options = [])
    {
        return $this->generateCode('html', $description, $options);
    }

    /**
     * Generate PHP code
     * 
     * @param string $description
     * @param array $options
     * @return array
     */
    public function generatePHP($description, $options = [])
    {
        return $this->generateCode('php', $description, $options);
    }
}

// Example usage and helper functions

/**
 * WordPress integration helper
 * Add this to your WordPress plugin
 */
if (!function_exists('scc_get_chatgpt_code_generator')) {
    function scc_get_chatgpt_code_generator()
    {
        static $generator = null;


        if ($generator === null) {
            // Get API key from WordPress options
            $settings = scc_get_settings();
            $api_key = $settings['ai_key'];
            $api_model = $settings['ai_model'];

            if (empty($api_key)) {
                throw new Exception('ChatGPT API key not configured');
            }

            $generator = new SCC_ChatGPTCodeGenerator($api_key);
            $generator->setModel($api_model);
        }

        return $generator;
    }
}

// Example usage functions
if (!function_exists('scc_generate_custom_code')) {
    function scc_generate_custom_code($mode = 'css', $prompt = '', $requirements = '')
    {
        $r = array('status' => '', 'code' => '');

        $settings = scc_get_settings();
        $api_key = $settings['ai_key'];

        if (empty($api_key)) {
            $r['status'] = 'fail';
            $r['code'] = __('Missing API key', 'simple-custom-code');
        }

        try {
            $generator = scc_get_chatgpt_code_generator();

            $additional_requirements = array();
            if (!empty($requirements)) {
                $additional_requirements = explode("\n", $requirements);
            }

            if ($mode == 'css') {
                $css_result = $generator->generateCSS(
                    $prompt,
                    [
                        'requirements' => $additional_requirements
                    ]
                );

                if ($css_result['success']) {
                    $r['code'] = $css_result['code'];
                    $r['status'] = 'success';
                } else {
                    $r['code'] = $css_result['error'];
                    $r['status'] = 'fail';
                }
            }

            if ($mode == 'js') {
                $js_result = $generator->generateJS(
                    $prompt,
                    [
                        'requirements' => $additional_requirements
                    ]
                );

                if ($js_result['success']) {
                    $r['code'] = $js_result['code'];
                    $r['status'] = 'success';
                } else {
                    $r['code'] = $js_result['error'];
                    $r['status'] = 'fail';
                }
            }

            if ($mode == 'html') {
                $html_result = $generator->generateHTML(
                    $prompt,
                    [
                        'requirements' => $additional_requirements
                    ]
                );

                if ($html_result['success']) {
                    $r['code'] = $html_result['code'];
                    $r['status'] = 'success';
                } else {
                    $r['code'] = $html_result['error'];
                    $r['status'] = 'fail';
                }
            }
        } catch (Exception $e) {
            $r['status'] = 'exception';
            $r['code'] = $e->getMessage();
        }

        return $r;
    }
}
