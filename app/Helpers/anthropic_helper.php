<?php

if (!function_exists('anthropic_generate_email')) {
    /**
     * Generate email HTML using Claude API.
     *
     * @param string      $prompt      User's description of the email
     * @param string|null $base64Image Optional base64-encoded image for visual inspiration
     * @param string|null $mimeType    MIME type of the image (e.g. image/png)
     * @return string|false            Raw text response from Claude, or false on error
     */
    function anthropic_generate_email(string $prompt, ?string $base64Image = null, ?string $mimeType = null)
    {
        // Try multiple ways to get the API key (CI4 env() handles DotEnv correctly)
        $apiKey = env('ANTHROPIC_API_KEY') ?: getenv('ANTHROPIC_API_KEY') ?: $_ENV['ANTHROPIC_API_KEY'] ?? '';
        if (empty($apiKey)) {
            log_message('error', 'ANTHROPIC_API_KEY not set. env()=' . var_export(env('ANTHROPIC_API_KEY'), true));
            return false;
        }

        $systemPrompt = <<<'SYSPROMPT'
You are an expert email designer for "Flattered", a premium fashion brand. Generate responsive, email-client-compatible HTML for the email described below.

Rules:
- Use HTML tables for layout (not divs with CSS grid/flexbox) for maximum email client compatibility
- All styles must be inline (no <style> blocks, no external CSS)
- Use web-safe fonts with fallbacks: Arial, Helvetica, sans-serif
- Maximum width: 600px, centered with a wrapper table
- Include a hidden preheader text span at the very top for inbox preview
- Make the design responsive using percentage widths where possible
- Use the Flattered brand color palette:
  - Primary: #e94560 (buttons, accents)
  - Dark: #1a1a2e (headers, footer background)
  - Accent: #0f3460 (secondary elements)
  - Light background: #f8f9fc
  - Text: #333333
- Use https://placehold.co/ for placeholder images (e.g. https://placehold.co/600x300/e94560/ffffff?text=Flattered)
- Include an unsubscribe link at the bottom with href="{UNSUBSCRIBE_LINK}"
- Do NOT include <html>, <head>, or <body> tags - just the email body content starting from the outermost <table>
- Make the design professional, modern, and visually appealing
- Include proper spacing, padding, and visual hierarchy

Return a JSON object with two keys:
{"subject": "Suggested subject line here", "html": "<table>...full email HTML here...</table>"}

Return ONLY valid JSON. No markdown code fences, no explanation, no extra text.
SYSPROMPT;

        // Build the user message content
        $userContent = [];

        // Add image if provided
        if ($base64Image && $mimeType) {
            $userContent[] = [
                'type'   => 'image',
                'source' => [
                    'type'       => 'base64',
                    'media_type' => $mimeType,
                    'data'       => $base64Image,
                ],
            ];
            $userContent[] = [
                'type' => 'text',
                'text' => "Use the attached image as visual inspiration for the design.\n\n" . $prompt,
            ];
        } else {
            $userContent[] = [
                'type' => 'text',
                'text' => $prompt,
            ];
        }

        $requestBody = [
            'model'      => 'claude-sonnet-4-20250514',
            'max_tokens' => 8192,
            'system'     => $systemPrompt,
            'messages'   => [
                [
                    'role'    => 'user',
                    'content' => $userContent,
                ],
            ],
        ];

        $ch = curl_init();

        curl_setopt_array($ch, [
            CURLOPT_URL            => 'https://api.anthropic.com/v1/messages',
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => json_encode($requestBody),
            CURLOPT_HTTPHEADER     => [
                'x-api-key: ' . $apiKey,
                'anthropic-version: 2023-06-01',
                'Content-Type: application/json',
            ],
            CURLOPT_TIMEOUT        => 120,
            CURLOPT_CONNECTTIMEOUT => 10,
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

        if ($response === false) {
            $error = curl_error($ch);
            log_message('error', 'Anthropic API curl error: ' . $error);
            return false;
        }

        if ($httpCode < 200 || $httpCode >= 300) {
            log_message('error', 'Anthropic API error (' . $httpCode . '): ' . $response);
            error_log('Anthropic API error (' . $httpCode . '): ' . substr($response, 0, 500));
            return false;
        }

        $decoded = json_decode($response, true);
        if (!$decoded || !isset($decoded['content'][0]['text'])) {
            log_message('error', 'Anthropic API: unexpected response format: ' . $response);
            return false;
        }

        return $decoded['content'][0]['text'];
    }
}
