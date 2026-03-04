<?php

if (!function_exists('resend_api_request')) {
    function resend_api_request($endpoint, $method = 'GET', $data = null) {
        $api_key = getenv('RESEND_API_KEY');

        $ch = curl_init();

        curl_setopt($ch, CURLOPT_URL, "https://api.resend.com" . $endpoint);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Authorization: Bearer ' . $api_key,
            'Content-Type: application/json'
        ]);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);

        if ($method !== 'GET') {
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
            if ($data) {
                curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
            }
        }

        $response = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);

        if ($response === false) {
            $error = curl_error($ch);
            log_message('error', 'Resend API curl error: ' . $error);
            return false;
        }

        if ($http_code >= 200 && $http_code < 300) {
            return json_decode($response, true);
        }

        log_message('error', 'Resend API Error (' . $http_code . '): ' . $response);
        return false;
    }
}

if (!function_exists('resend_send_email')) {
    /**
     * Send a single email via the Resend API.
     *
     * @param string $from     Sender address (e.g. "Brand Name <noreply@example.com>")
     * @param string $to       Recipient email address
     * @param string $subject  Email subject line
     * @param string $html     HTML body
     * @param string $text     Plain text body (optional)
     * @param array  $tags     Optional Resend tags [['name'=>'key','value'=>'val']]
     * @param array  $headers  Optional custom email headers ['List-Unsubscribe'=>'<url>']
     * @return array|false     Resend API response on success, false on failure
     */
    function resend_send_email(string $from, string $to, string $subject, string $html, string $text = '', array $tags = [], array $headers = [])
    {
        $data = [
            'from'    => $from,
            'to'      => [$to],
            'subject' => $subject,
            'html'    => $html,
        ];

        if (!empty($text)) {
            $data['text'] = $text;
        }

        if (!empty($tags)) {
            $data['tags'] = $tags;
        }

        if (!empty($headers)) {
            $data['headers'] = $headers;
        }

        return resend_api_request('/emails', 'POST', $data);
    }
}

if (!function_exists('resend_get_email')) {
    /**
     * Retrieve a single email's status from the Resend API.
     *
     * @param string $emailId  The Resend email ID (returned when sending)
     * @return array|false     Email data including last_event, or false on failure
     */
    function resend_get_email(string $emailId)
    {
        return resend_api_request('/emails/' . $emailId);
    }
}
