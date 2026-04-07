<?php

if (!function_exists('gemini_generate_image')) {
    /**
     * Generate an image using Gemini API (Google AI).
     *
     * @param string $prompt  Description of the image to generate
     * @param string $style   Optional style hint (e.g. 'email-header', 'product', 'abstract')
     * @return string|false   Public URL of the generated image, or false on failure
     */
    function gemini_generate_image(string $prompt, string $style = 'email-header')
    {
        $apiKey = env('GEMINI_API_KEY') ?: ($_SERVER['GEMINI_API_KEY'] ?? '');
        if (empty($apiKey)) {
            log_message('warning', 'GEMINI_API_KEY not set, skipping image generation');
            return false;
        }

        // Build optimized prompt for email header images
        $imagePrompt = match ($style) {
            'email-header' => "Create a professional, modern email header banner image (600x280 pixels aspect ratio). Clean, minimal design with subtle gradients. No text in the image. " . $prompt,
            'product'      => "Professional product photography style, clean white background, well-lit. " . $prompt,
            'abstract'     => "Abstract, modern, geometric design. Soft gradients, professional color palette. No text. " . $prompt,
            default        => $prompt,
        };

        $model = 'gemini-2.0-flash-exp';
        $url = "https://generativelanguage.googleapis.com/v1beta/models/{$model}:generateContent?key={$apiKey}";

        $payload = json_encode([
            'contents' => [[
                'parts' => [['text' => $imagePrompt]]
            ]],
            'generationConfig' => [
                'responseModalities' => ['Text', 'Image'],
            ],
        ]);

        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL            => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => $payload,
            CURLOPT_HTTPHEADER     => ['Content-Type: application/json'],
            CURLOPT_TIMEOUT        => 60,
            CURLOPT_CONNECTTIMEOUT => 10,
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($response === false || $httpCode < 200 || $httpCode >= 300) {
            log_message('error', "Gemini API error ({$httpCode}): " . substr($response ?: 'no response', 0, 500));
            return false;
        }

        $decoded = json_decode($response, true);
        if (!$decoded) {
            log_message('error', 'Gemini API: could not decode JSON response');
            return false;
        }

        // Extract base64 image from response
        $base64Image = null;
        $mimeType = 'image/png';

        $candidates = $decoded['candidates'] ?? [];
        foreach ($candidates as $candidate) {
            $parts = $candidate['content']['parts'] ?? [];
            foreach ($parts as $part) {
                if (isset($part['inlineData']['data'])) {
                    $base64Image = $part['inlineData']['data'];
                    $mimeType = $part['inlineData']['mimeType'] ?? 'image/png';
                    break 2;
                }
            }
        }

        if (!$base64Image) {
            log_message('error', 'Gemini API: no image data in response');
            return false;
        }

        // Save image to public directory
        $extension = ($mimeType === 'image/jpeg' || $mimeType === 'image/jpg') ? 'jpg' : 'png';
        $filename = 'email-img-' . uniqid() . '.' . $extension;

        // Ensure upload directory exists
        $uploadDir = FCPATH . 'uploads/email-images/';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }

        $filePath = $uploadDir . $filename;
        $imageData = base64_decode($base64Image);

        if (file_put_contents($filePath, $imageData) === false) {
            log_message('error', 'Gemini: could not save image to ' . $filePath);
            return false;
        }

        // Return public URL
        $baseUrl = rtrim(base_url(), '/');
        return $baseUrl . '/uploads/email-images/' . $filename;
    }
}
