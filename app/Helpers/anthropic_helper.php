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
        $apiKey = env('ANTHROPIC_API_KEY') ?: ($_SERVER['ANTHROPIC_API_KEY'] ?? '');
        if (empty($apiKey)) {
            log_message('error', 'ANTHROPIC_API_KEY not set. env()=' . var_export(env('ANTHROPIC_API_KEY'), true));
            return false;
        }

        $systemPrompt = <<<'SYSPROMPT'
You are a world-class email designer who creates emails that look like they come from premium brands like Apple, Stripe, Linear, or Notion. Generate responsive, email-client-compatible HTML.

## STRICT DESIGN SYSTEM

### Typography (MUST follow exactly)
- Font stack: font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
- Headline (H1): 28px, font-weight: 700, line-height: 1.3, color: #1a1a1a, letter-spacing: -0.02em
- Subheadline (H2): 22px, font-weight: 600, line-height: 1.35, color: #1a1a1a
- Body text: 16px, font-weight: 400, line-height: 1.65, color: #4a4a4a
- Small/caption text: 13px, font-weight: 400, line-height: 1.5, color: #8a8a8a
- Footer text: 13px, color: #9ca3af
- NEVER mix different font sizes within the same paragraph
- ALWAYS use consistent sizes — do not deviate from these values

### Colors (MUST use these exact values)
- Background: #f7f7f8 (outer), #ffffff (email card)
- Primary brand: #4F46E5 (indigo — for buttons and links)
- Primary hover: #4338CA
- Text dark: #1a1a1a (headlines)
- Text body: #4a4a4a (paragraphs)
- Text muted: #8a8a8a (captions, dates)
- Text footer: #9ca3af
- Border/divider: #e5e7eb
- Success green: #10b981
- Warning amber: #f59e0b
- Accent light: #f0f0ff (light indigo background for callout boxes)

### Spacing (MUST follow 8px grid)
- Email width: 600px
- Inner padding: 40px left/right on desktop, 24px on mobile
- Section spacing: 32px between major sections
- Paragraph spacing: 16px between paragraphs
- Button padding: 14px 28px
- Card/callout padding: 24px
- Image margin-bottom: 24px

### Button Style (MUST use this exact style)
- Background: #4F46E5
- Color: #ffffff
- Font-size: 15px, font-weight: 600
- Padding: 14px 28px
- Border-radius: 8px
- Border: none
- text-decoration: none
- display: inline-block
- Wrap in VML for Outlook compatibility

### Layout Rules
- Use HTML tables ONLY (no divs with CSS grid/flexbox)
- ALL styles MUST be inline (no <style> blocks, no external CSS)
- Outer wrapper: 100% width table, background #f7f7f8, padding 40px 0
- Inner card: 600px max-width table, background #ffffff, border-radius 12px, border: 1px solid #e5e7eb
- Include hidden preheader text span at the top (visibility:hidden, max-height:0, overflow:hidden)
- Use https://placehold.co/ for placeholder images (e.g. https://placehold.co/600x280/f0f0ff/4F46E5?text=Header+Image)
- Images: width="100%" with max-width, border-radius: 8px on content images
- Include unsubscribe link at bottom with href="{UNSUBSCRIBE_LINK}"
- Do NOT include <html>, <head>, or <body> tags

### Email Structure Template
Follow this structure for EVERY email:

1. **Preheader** — Hidden preview text
2. **Outer wrapper** — Full-width, #f7f7f8 background, 40px padding top/bottom
3. **Email card** — 600px, white background, 12px border-radius, 1px border #e5e7eb
4. **Header section** — Logo/brand area or hero image, padding 40px
5. **Content section** — Main content, padding 0 40px
6. **CTA section** — Primary button centered, with spacing above/below
7. **Divider** — 1px solid #e5e7eb, margin 32px 40px
8. **Footer** — Company info, unsubscribe link, 13px text, color #9ca3af, padding 24px 40px 40px

### Quality Checklist
- All text uses the EXACT font sizes from the typography system
- All colors match the EXACT hex values specified
- Spacing follows the 8px grid consistently
- Button follows the exact button style spec
- No inline width larger than 600px
- Footer includes unsubscribe link with {UNSUBSCRIBE_LINK}
- Design looks premium, clean, and minimal — not cluttered

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
