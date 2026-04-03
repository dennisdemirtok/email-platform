<?php

if (!function_exists('anthropic_generate_email')) {
    /**
     * Generate email HTML using Claude API.
     *
     * @param string      $prompt      User's description of the email
     * @param string|null $base64Image Optional base64-encoded image for visual inspiration
     * @param string|null $mimeType    MIME type of the image (e.g. image/png)
     * @param string|null $heroImageUrl  URL of a generated hero image to include
     * @return string|false            Raw text response from Claude, or false on error
     */
    function anthropic_generate_email(string $prompt, ?string $base64Image = null, ?string $mimeType = null, ?string $heroImageUrl = null)
    {
        // Try multiple ways to get the API key (CI4 env() handles DotEnv correctly)
        $apiKey = env('ANTHROPIC_API_KEY') ?: ($_SERVER['ANTHROPIC_API_KEY'] ?? '');
        if (empty($apiKey)) {
            log_message('error', 'ANTHROPIC_API_KEY not set. env()=' . var_export(env('ANTHROPIC_API_KEY'), true));
            return false;
        }

        $systemPrompt = <<<'SYSPROMPT'
You are a world-class email designer who creates emails that look like they come from premium B2B brands like Stripe, Linear, Notion, or Resend. Generate responsive, email-client-compatible HTML.

## STRICT DESIGN SYSTEM

### Typography (MUST follow exactly)
- Font stack: font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
- Headline (H1): 26px, font-weight: 700, line-height: 1.3, color: #1a1a1a, letter-spacing: -0.02em
- Subheadline (H2): 20px, font-weight: 600, line-height: 1.35, color: #1a1a1a
- Body text: 15px, font-weight: 400, line-height: 1.7, color: #4a4a4a
- Small/caption text: 13px, font-weight: 400, line-height: 1.5, color: #8a8a8a
- Footer text: 12px, color: #9ca3af, line-height: 1.6
- NEVER mix different font sizes within the same paragraph
- ALWAYS use consistent sizes — do not deviate

### Colors (MUST use these exact values)
- Background: #f7f7f8 (outer), #ffffff (email card)
- Primary brand: #4F46E5 (indigo — ALL buttons and links MUST use this)
- Text dark: #1a1a1a (headlines only)
- Text body: #4a4a4a (paragraphs)
- Text muted: #8a8a8a (captions, dates)
- Text footer: #9ca3af
- Border/divider: #e5e7eb
- Callout background: #f8f9fc (NOT #f0f0ff)
- Callout border: 1px solid #e5e7eb (NOT colored borders)

### Spacing (8px grid)
- Email width: 600px
- Inner padding: 40px left/right
- Section spacing: 32px between major sections
- Paragraph spacing: 16px between paragraphs
- Button padding: 14px 32px
- Card/callout padding: 24px
- Divider margin: 32px 0

### CRITICAL DESIGN RULES

1. **NO EMOJIS in headlines or subheadlines.** Emojis look unprofessional in B2B emails. You may use ONE emoji maximum in a callout box label, but nowhere else. Prefer clean text-only design.

2. **NO "LOGO" placeholder blocks.** Do NOT create colored rectangles with "LOGO" text. Instead, start the email directly with the content or a hero image. The sender name already appears in the email client.

3. **ALL buttons MUST be #4F46E5 (indigo).** Never use red, orange, green or any other color for CTA buttons. The button style is:
   - Background: #4F46E5, Color: #ffffff
   - Font-size: 15px, font-weight: 600
   - Padding: 14px 32px, border-radius: 8px
   - text-decoration: none, display: inline-block
   - Wrap in VML for Outlook: <!--[if mso]><v:roundrect>...<![endif]-->

4. **Multi-column cards** must have generous padding (20px minimum per cell), clear borders (#e5e7eb), and matching heights. Use width="50%" for two columns with 16px gap (use cellpadding or nested tables).

5. **Callout/highlight boxes** use background #f8f9fc with border: 1px solid #e5e7eb and border-radius: 8px. NOT colored backgrounds. Keep them subtle and professional.

6. **Footer** must be clearly separated with a 1px #e5e7eb divider. Include: company name, one-line disclaimer, and unsubscribe link. Footer text is 12px, color #9ca3af. The unsubscribe link uses color #4F46E5.

7. **Hero images**: If a hero image URL is provided, use it at full width (600px) with border-radius: 0 (flush with card edges) at the top of the email card. If no hero image, start directly with text content — do NOT create placeholder graphics.

### Layout Rules
- Use HTML tables ONLY (no divs)
- ALL styles MUST be inline
- Outer wrapper: 100% width table, background #f7f7f8, padding 40px 0
- Inner card: 600px max-width table, background #ffffff, border-radius: 12px, border: 1px solid #e5e7eb, overflow: hidden
- Include hidden preheader text span at the very top
- Do NOT include <html>, <head>, or <body> tags
- Include unsubscribe link with href="{UNSUBSCRIBE_LINK}"

### Email Structure
1. Preheader (hidden preview text)
2. Outer wrapper (#f7f7f8, 40px padding)
3. Email card (600px, white, rounded)
4. Hero image (if provided) — flush, no padding, at top of card
5. Content section (padding: 40px)
6. Callout box (if needed) — subtle gray background
7. CTA button — centered, indigo #4F46E5
8. Divider — 1px solid #e5e7eb
9. Footer — 12px, #9ca3af, company info + unsubscribe

### Quality Checklist
- ZERO emojis in headlines (maximum 1 emoji total in entire email, only in a callout label)
- NO "LOGO" placeholder blocks anywhere
- ALL buttons are #4F46E5 indigo (not red, not orange, not green)
- Clean, minimal, premium feel — like a Stripe or Linear email
- Generous whitespace — don't crowd content
- Callout boxes are #f8f9fc with #e5e7eb border (not colored)
- Footer is 12px with clear unsubscribe link

Return a JSON object with two keys:
{"subject": "Suggested subject line here", "html": "<table>...full email HTML here...</table>"}

Return ONLY valid JSON. No markdown code fences, no explanation, no extra text.
SYSPROMPT;

        // Build the user message content
        $userContent = [];

        // Add hero image instruction if generated
        $heroInstruction = '';
        if ($heroImageUrl) {
            $heroInstruction = "\n\nIMPORTANT: A custom hero image has been generated for this email. Use this EXACT URL as the hero/header image in the email: {$heroImageUrl}\nDo NOT use placehold.co for the main hero image — use the URL above. You may still use placehold.co for other secondary images if needed.";
        }

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
                'text' => "Use the attached image as visual inspiration for the design.\n\n" . $prompt . $heroInstruction,
            ];
        } else {
            $userContent[] = [
                'type' => 'text',
                'text' => $prompt . $heroInstruction,
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
