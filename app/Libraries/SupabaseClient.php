<?php

namespace App\Libraries;

class SupabaseClient
{
    private string $baseUrl;
    private string $apiKey;

    public function __construct()
    {
        $url = env('SUPABASE_URL') ?: ($_SERVER['SUPABASE_URL'] ?? '');
        $key = env('SUPABASE_KEY') ?: ($_SERVER['SUPABASE_KEY'] ?? '');

        if (empty($url) || empty($key)) {
            log_message('error', 'SUPABASE_URL or SUPABASE_KEY environment variable not set');
            throw new \RuntimeException('Database configuration error. Please contact administrator.');
        }

        $this->baseUrl = rtrim($url, '/') . '/rest/v1';
        $this->apiKey = $key;
    }

    /**
     * SELECT - GET request with optional filters, ordering, limiting
     *
     * @param string $table Table or view name
     * @param array  $params Query parameters: select, order, limit, offset, and PostgREST filters (column=eq.value)
     * @param bool   $single If true, returns first row or null instead of array
     * @param bool   $count  If true, returns ['data' => [...], 'count' => N]
     * @return array|null
     */
    public function select(string $table, array $params = [], bool $single = false, bool $count = false): array|null
    {
        $url = $this->baseUrl . '/' . $table;

        if (!empty($params)) {
            $url .= '?' . http_build_query($params);
        }

        $headers = $this->getHeaders();
        if ($single) {
            $headers[] = 'Accept: application/vnd.pgrst.object+json';
        }
        if ($count) {
            $headers[] = 'Prefer: count=exact';
        }

        $response = $this->request('GET', $url, null, $headers);

        if ($count) {
            return [
                'data' => $response['body'],
                'count' => $response['count'] ?? 0,
            ];
        }

        if ($single) {
            return $response['body'] ?: null;
        }

        return $response['body'] ?? [];
    }

    /**
     * INSERT - POST request. Supports single row or batch (array of rows).
     * Returns inserted row(s).
     */
    public function insert(string $table, array $data): array
    {
        $url = $this->baseUrl . '/' . $table;

        $headers = $this->getHeaders();
        $headers[] = 'Content-Type: application/json';
        $headers[] = 'Prefer: return=representation';

        $response = $this->request('POST', $url, json_encode($data), $headers);

        return $response['body'] ?? [];
    }

    /**
     * UPDATE - PATCH request with filter.
     *
     * @param string $table
     * @param array  $filters PostgREST filters, e.g. ['id' => 'eq.xxx']
     * @param array  $data    Data to update
     * @return array Updated row(s)
     */
    public function update(string $table, array $filters, array $data): array
    {
        $url = $this->baseUrl . '/' . $table . '?' . http_build_query($filters);

        $headers = $this->getHeaders();
        $headers[] = 'Content-Type: application/json';
        $headers[] = 'Prefer: return=representation';

        $response = $this->request('PATCH', $url, json_encode($data), $headers);

        return $response['body'] ?? [];
    }

    /**
     * DELETE - DELETE request with filter.
     *
     * @param string $table
     * @param array  $filters PostgREST filters
     * @return array Deleted row(s)
     */
    public function delete(string $table, array $filters): array
    {
        $url = $this->baseUrl . '/' . $table . '?' . http_build_query($filters);

        $headers = $this->getHeaders();
        $headers[] = 'Prefer: return=representation';

        $response = $this->request('DELETE', $url, null, $headers);

        return $response['body'] ?? [];
    }

    /**
     * RPC - Call a PostgreSQL function via POST /rest/v1/rpc/{function}
     *
     * @param string $function Function name
     * @param array  $params   Function parameters
     * @return array
     */
    public function rpc(string $function, array $params = []): array
    {
        $url = $this->baseUrl . '/rpc/' . $function;

        $headers = $this->getHeaders();
        $headers[] = 'Content-Type: application/json';

        $response = $this->request('POST', $url, json_encode($params), $headers);

        return $response['body'] ?? [];
    }

    /**
     * Build default headers
     */
    private function getHeaders(): array
    {
        return [
            'apikey: ' . $this->apiKey,
            'Authorization: Bearer ' . $this->apiKey,
        ];
    }

    /**
     * Execute HTTP request via curl
     */
    private function request(string $method, string $url, ?string $body = null, array $headers = []): array
    {
        $ch = curl_init();

        curl_setopt_array($ch, [
            CURLOPT_URL            => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CUSTOMREQUEST  => $method,
            CURLOPT_HTTPHEADER     => $headers,
            CURLOPT_TIMEOUT        => 30,
            CURLOPT_CONNECTTIMEOUT => 10,
            CURLOPT_HEADER         => true,
        ]);

        if ($body !== null) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, $body);
        }

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $headerSize = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
        $error = curl_error($ch);

        if ($response === false) {
            log_message('error', "Supabase request failed: {$error}");
            throw new \RuntimeException('Database connection error. Please try again later.');
        }

        $responseHeaders = substr($response, 0, $headerSize);
        $responseBody = substr($response, $headerSize);

        // Parse count from content-range header
        $count = null;
        if (preg_match('/content-range:\s*\d+-\d+\/(\d+)/i', $responseHeaders, $matches)) {
            $count = (int)$matches[1];
        } elseif (preg_match('/content-range:\s*\*\/(\d+)/i', $responseHeaders, $matches)) {
            $count = (int)$matches[1];
        }

        $decoded = json_decode($responseBody, true);

        if ($httpCode >= 400) {
            $message = $decoded['message'] ?? $decoded['error'] ?? "HTTP {$httpCode}";
            log_message('error', "Supabase API error ({$httpCode}): {$message} | URL: {$url}");

            if ($httpCode === 404 || ($decoded['code'] ?? '') === 'PGRST116') {
                // Not found - return null/empty
                return ['body' => null, 'count' => 0];
            }

            throw new \RuntimeException("Database error: {$message}");
        }

        return [
            'body'  => $decoded,
            'count' => $count,
        ];
    }
}
