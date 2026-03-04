<?php

namespace App\Controllers;

use App\Models\AudiencesModel;
use App\Models\CampaignsModel;
use App\Models\DomainsModel;

class CampaignsController extends BaseController
{
    protected $domainsModel;
    protected $campaignsModel;
    protected $audiencesModel;

    public function __construct()
    {
        $this->domainsModel = new DomainsModel();
        $this->campaignsModel = new CampaignsModel();
        $this->audiencesModel = new AudiencesModel();
        helper('domain');
    }

    public function index()
    {
        $allCampaigns = $this->campaignsModel->getCampaignsByDomain();

        // Build stats per sent campaign from campaign_sends table
        $campaignStats = [];
        foreach ($allCampaigns as $c) {
            $st = $c['status'] ?? '';
            if ($st === 'sent' || $st === 'sending' || $st === 'failed') {
                $campaignStats[$c['id']] = $this->campaignsModel->getCampaignStats($c['id']);
            }
        }

        $data = [
            'allCampaigns'  => $allCampaigns,
            'campaignStats' => $campaignStats,
        ];

        echo view('Templates/header', ['currentPage' => 'campaigns']);
        echo view('Campaigns/index', $data);
        echo view('Templates/footer');
    }

    public function create()
    {
        $data = [
            'domains' => $this->domainsModel->getActiveDomains(),
            'audiences' => $this->audiencesModel->getAllAudiences(),
            'templates' => $this->campaignsModel->getTemplatesWithContent()
        ];

        echo view('Templates/header', ['currentPage' => 'campaigns']);
        echo view('Campaigns/create', $data);
        echo view('Templates/footer');
    }

    public function reloadAnalytics()
    {
        $analyticsModel = model(AnalyticsModel::class);
        $analyticsModel->insertAnalytics();
        session()->setFlashdata('success', 'Data reloaded successfully');
        return redirect()->route('campaigns');
    }

    public function edit($campaignId)
    {
        $data = [
            'audiences' => $this->audiencesModel->getAllAudiences(),
            'campaign' => $this->campaignsModel->getCampaign($campaignId)
        ];

        echo view('Templates/header', ['currentPage' => 'campaigns']);
        echo view('Campaigns/edit', $data);
        echo view('Templates/footer');
    }

    public function delete($id)
    {
        if (strtolower($this->request->getMethod()) !== 'post') {
            return redirect()->route('campaigns');
        }

        $this->campaignsModel->deleteCampaign($id);
        session()->setFlashdata('success', 'Campaign deleted successfully');
        return redirect()->route('campaigns');
    }

    public function store()
    {
        if (strtolower($this->request->getMethod()) !== 'post') {
            return redirect()->to('/campaigns/create');
        }

        $activeDomain = get_active_domain();
        if (!$activeDomain) {
            return redirect()->back()->with('error', 'Please select an active domain first');
        }

        $campaignName = $this->request->getPost('campaign_name');
        $subject = $this->request->getPost('subject');
        $templateHTML = $this->request->getPost('contentHTML');
        $templatePlainText = $this->request->getPost('contentPlainText');
        $audiences = $this->request->getPost('audiences');
        $grapesJSData = $this->request->getPost('grapesJSData');

        // Auto-generate plain text from HTML if empty
        if (empty($templatePlainText) && !empty($templateHTML)) {
            $templatePlainText = strip_tags(str_replace(['<br>', '<br/>', '<br />', '</p>', '</div>', '</li>'], "\n", $templateHTML));
            $templatePlainText = preg_replace('/\n{3,}/', "\n\n", trim($templatePlainText));
        }

        $utmParams = [
            'utm_source' => 'newsletter',
            'utm_medium' => 'email',
            'utm_campaign' => $campaignName
        ];
        $templateHTML = $this->addUTMParametersToLinks($templateHTML, $utmParams);

        $data = [
            'name' => $campaignName,
            'subject' => $subject,
            'status' => 'unsent',
            'template_html' => $templateHTML,
            'template_plain_text' => $templatePlainText,
            'template_title' => $subject,
            'grapes_js_data' => $grapesJSData,
            'domain_id' => $activeDomain['id'],
            'created_at' => date('Y-m-d\TH:i:s\Z')
        ];

        $campaignId = $this->campaignsModel->insertCampaign($data);
        if ($campaignId) {
            if (!empty($audiences)) {
                $this->campaignsModel->setCampaignAudiences($campaignId, $audiences);
            }
            session()->setFlashdata('success', 'Campaign created successfully');
            return redirect()->to('/campaigns');
        }
        return redirect()->back()->withInput()->with('error', 'Error creating campaign');
    }

    /**
     * AI Email Generation endpoint.
     * Accepts a prompt (and optional image) via AJAX POST,
     * calls Claude API, returns generated HTML + subject as JSON.
     */
    public function generate()
    {
        set_time_limit(180); // Allow up to 3 minutes for AI generation (images take longer)

        if (strtolower($this->request->getMethod()) !== 'post') {
            return $this->response->setStatusCode(405)->setJSON(['success' => false, 'error' => 'Method not allowed']);
        }

        helper('anthropic');

        $prompt = trim($this->request->getPost('prompt') ?? '');
        if (empty($prompt)) {
            return $this->response->setJSON([
                'success'    => false,
                'error'      => 'Please enter a prompt describing the email you want.',
                'csrf_token' => csrf_hash(),
            ]);
        }

        // Handle optional image upload
        $base64Image = null;
        $mimeType = null;
        $imageFile = $this->request->getFile('image');
        if ($imageFile && $imageFile->isValid() && !$imageFile->hasMoved()) {
            $allowedTypes = ['image/png', 'image/jpeg', 'image/gif', 'image/webp'];
            $mimeType = $imageFile->getMimeType();
            if (in_array($mimeType, $allowedTypes)) {
                $base64Image = base64_encode(file_get_contents($imageFile->getTempName()));
            }
        }

        $result = anthropic_generate_email($prompt, $base64Image, $mimeType);

        if ($result === false) {
            return $this->response->setJSON([
                'success'    => false,
                'error'      => 'AI generation failed. Please try again.',
                'csrf_token' => csrf_hash(),
            ]);
        }

        // Parse the JSON response from Claude
        $cleaned = trim($result);
        // Strip markdown code fences if present
        $cleaned = preg_replace('/^```(?:json)?\s*/i', '', $cleaned);
        $cleaned = preg_replace('/\s*```$/', '', $cleaned);
        // Remove control characters that break json_decode (except normal whitespace)
        $cleaned = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/', '', $cleaned);

        $parsed = json_decode($cleaned, true, 512, JSON_INVALID_UTF8_SUBSTITUTE);
        if (!$parsed || !isset($parsed['html'])) {
            // Try to extract HTML directly if JSON parsing failed
            error_log('AI response parse failed. Raw (first 500 chars): ' . substr($result, 0, 500));
            error_log('Cleaned (first 500 chars): ' . substr($cleaned, 0, 500));
            error_log('JSON error: ' . json_last_error_msg());
            return $this->response->setJSON([
                'success'    => false,
                'error'      => 'Could not parse AI response. Please try again.',
                'csrf_token' => csrf_hash(),
            ]);
        }

        return $this->response->setJSON([
            'success'    => true,
            'html'       => $parsed['html'],
            'subject'    => $parsed['subject'] ?? '',
            'csrf_token' => csrf_hash(),
        ]);
    }

    public function update()
    {
        if (strtolower($this->request->getMethod()) !== 'post') {
            return redirect()->route('campaigns');
        }

        $campaignId = $this->request->getPost('id');
        $campaignName = $this->request->getPost('campaign_name');
        $subject = $this->request->getPost('subject');
        $templateHTML = $this->request->getPost('contentHTML');
        $templatePlainText = $this->request->getPost('contentPlainText');
        $audiences = $this->request->getPost('audiences');
        $grapesJSData = $this->request->getPost('grapesJSData');

        if (empty($templatePlainText) && !empty($templateHTML)) {
            $templatePlainText = strip_tags(str_replace(['<br>', '<br/>', '<br />', '</p>', '</div>', '</li>'], "\n", $templateHTML));
            $templatePlainText = preg_replace('/\n{3,}/', "\n\n", trim($templatePlainText));
        }

        $utmParams = [
            'utm_source' => 'newsletter',
            'utm_medium' => 'email',
            'utm_campaign' => $campaignName
        ];
        $templateHTML = $this->addUTMParametersToLinks($templateHTML, $utmParams);

        $data = [
            'name' => $campaignName,
            'subject' => $subject,
            'template_title' => $subject,
            'template_html' => $templateHTML,
            'template_plain_text' => $templatePlainText,
            'grapes_js_data' => $grapesJSData
        ];

        if ($this->campaignsModel->updateCampaign($campaignId, $data)) {
            if (!empty($audiences)) {
                $this->campaignsModel->setCampaignAudiences($campaignId, $audiences);
            }
            return redirect()->to('/campaigns')->with('success', 'Campaign updated successfully');
        }
        return redirect()->back()->withInput()->with('error', 'Error updating campaign');
    }

    public function send($campaignId)
    {
        if (strtolower($this->request->getMethod()) !== 'post') {
            return redirect()->route('campaigns');
        }

        helper('resend');
        ignore_user_abort(true); // Continue sending even if browser disconnects
        set_time_limit(600);     // Allow up to 10 minutes for large campaigns

        $campaign = $this->campaignsModel->getCampaign($campaignId);
        if (!$campaign) {
            return $this->response->setJSON([
                'success' => false,
                'message' => 'Campaign not found'
            ]);
        }

        $uniqueContacts = $this->audiencesModel->getUniqueContactsFromAudiences(
            $campaign['audiences'] ?? []
        );

        if (empty($uniqueContacts)) {
            return $this->response->setJSON([
                'success' => false,
                'message' => 'No subscribed contacts found in campaign audiences'
            ]);
        }

        $activeDomain = get_active_domain();
        if (!$activeDomain) {
            return $this->response->setJSON([
                'success' => false,
                'message' => 'No active domain configured'
            ]);
        }

        if (($activeDomain['pretty_name'] ?? 'N/A') === 'N/A' || ($activeDomain['sender_email'] ?? 'N/A') === 'N/A') {
            return $this->response->setJSON([
                'success' => false,
                'message' => 'Domain not properly configured. Please set a pretty name and sender email in domain settings.'
            ]);
        }

        $fromAddress = $activeDomain['pretty_name'] . ' <' . $activeDomain['sender_email'] . '>';
        $campaignSubject = $campaign['subject'] ?? '';
        $campaignHTML = $campaign['templateHTML'] ?? '';
        $campaignPlainText = $campaign['templatePlainText'] ?? '';
        $campaignIdStr = (string)($campaign['id'] ?? $campaignId);

        // Unsubscribe link template
        $unsubscribeHtml = '<div style="text-align:center; padding:20px 0; font-size:12px; color:#666;">'
            . '<a href="' . base_url('/unsubscribe/') . '{% id %}" style="color:#666; text-decoration:underline;">Unsubscribe</a>'
            . '</div>';

        // Mark campaign as "sending"
        $this->campaignsModel->updateCampaign($campaignId, ['status' => 'sending']);

        $start_time = microtime(true);
        $successCount = 0;
        $failCount = 0;
        $errors = [];

        foreach ($uniqueContacts as $contact) {
            $contactId = $contact['id'];
            $contactEmail = $contact['email'];

            // Personalize unsubscribe link for this contact
            $personalizedUnsubscribe = str_replace('{% id %}', $contactId, $unsubscribeHtml);
            $personalizedHTML = $campaignHTML . $personalizedUnsubscribe;

            // Plain text unsubscribe
            $personalizedText = $campaignPlainText;
            if (!empty($personalizedText)) {
                $personalizedText .= "\n\nUnsubscribe: " . base_url('/unsubscribe/' . $contactId);
            }

            // Tags for Resend tracking
            $tags = [
                ['name' => 'campaign_id', 'value' => $campaignIdStr],
            ];

            // List-Unsubscribe header for email clients
            $headers = [
                'List-Unsubscribe' => '<' . base_url('/unsubscribe/' . $contactId) . '>',
            ];

            $result = resend_send_email(
                $fromAddress,
                $contactEmail,
                $campaignSubject,
                $personalizedHTML,
                $personalizedText,
                $tags,
                $headers
            );

            if ($result !== false && isset($result['id'])) {
                $successCount++;
                log_message('info', "Campaign {$campaignIdStr}: sent to {$contactEmail} (resend_id: {$result['id']})");

                // Save to campaign_sends for tracking
                try {
                    $this->campaignsModel->insertCampaignSend([
                        'campaign_id'      => $campaignIdStr,
                        'contact_email'    => $contactEmail,
                        'resend_email_id'  => $result['id'],
                        'status'           => 'sent',
                    ]);
                } catch (\Exception $e) {
                    log_message('error', "Failed to save campaign_send for {$contactEmail}: " . $e->getMessage());
                }
            } else {
                $failCount++;
                $errors[] = $contactEmail;
                log_message('error', "Campaign {$campaignIdStr}: failed to send to {$contactEmail}");
            }

            // Rate limiting: 100ms delay between sends
            usleep(100000);
        }

        $end_time = microtime(true);
        $execution_time = ($end_time - $start_time);

        // Update campaign status
        if ($successCount > 0) {
            $this->campaignsModel->updateCampaign($campaignId, [
                'status' => 'sent',
                'sent_at' => date('Y-m-d\TH:i:s\Z')
            ]);
        } else {
            $this->campaignsModel->updateCampaign($campaignId, [
                'status' => 'failed'
            ]);
        }

        set_time_limit(30);

        return $this->response->setJSON([
            'success' => $successCount > 0,
            'message' => "Campaign sent: {$successCount} delivered, {$failCount} failed",
            'execution_time' => number_format($execution_time, 2),
            'sent_count' => $successCount,
            'fail_count' => $failCount,
        ]);
    }

    /**
     * Sync delivery status for all sends in a campaign by polling Resend API.
     * GET /emails/{id} returns: last_event, created_at, etc.
     * Resend last_event values: "delivered", "opened", "clicked", "bounced", "complained"
     */
    public function syncCampaignStatus($campaignId)
    {
        if (strtolower($this->request->getMethod()) !== 'post') {
            return redirect()->route('campaigns');
        }

        helper('resend');
        set_time_limit(300);

        $sends = $this->campaignsModel->getCampaignSends($campaignId);

        if (empty($sends)) {
            return $this->response->setJSON([
                'success' => false,
                'message' => 'No sends found for this campaign. Send the campaign first.',
            ]);
        }

        $updated = 0;
        $errors = 0;

        foreach ($sends as $send) {
            $resendId = $send['resend_email_id'] ?? null;
            if (empty($resendId)) {
                continue;
            }

            $emailData = resend_get_email($resendId);

            if ($emailData === false) {
                $errors++;
                log_message('error', "Sync: Failed to fetch status for resend_id {$resendId}");
                usleep(100000); // 100ms delay even on error
                continue;
            }

            $updateData = [];
            $lastEvent = $emailData['last_event'] ?? '';

            // Map Resend events to our columns
            // Resend returns: delivered_at, opened_at, clicked_at as ISO timestamps
            if (!empty($emailData['delivered_at']) && empty($send['delivered_at'])) {
                $updateData['delivered_at'] = $emailData['delivered_at'];
            }
            if (!empty($emailData['opened_at']) && empty($send['opened_at'])) {
                $updateData['opened_at'] = $emailData['opened_at'];
            }
            if (!empty($emailData['clicked_at']) && empty($send['clicked_at'])) {
                $updateData['clicked_at'] = $emailData['clicked_at'];
            }
            if (!empty($emailData['bounced_at']) && empty($send['bounced_at'])) {
                $updateData['bounced_at'] = $emailData['bounced_at'];
            }

            // Update status based on last_event
            if (!empty($lastEvent) && $lastEvent !== $send['status']) {
                $updateData['status'] = $lastEvent;
            }

            if (!empty($updateData)) {
                $this->campaignsModel->updateCampaignSend($send['id'], $updateData);
                $updated++;
            }

            // Rate limiting: 100ms delay between API calls
            usleep(100000);
        }

        return $this->response->setJSON([
            'success' => true,
            'message' => "Synced {$updated} of " . count($sends) . " sends. Errors: {$errors}",
            'updated' => $updated,
            'total'   => count($sends),
            'errors'  => $errors,
        ]);
    }

    /**
     * Save the current campaign HTML as a reusable template.
     * POST /campaigns/save-template  { name, html }
     */
    public function saveTemplate()
    {
        if (strtolower($this->request->getMethod()) !== 'post') {
            return redirect()->to('/campaigns');
        }

        $name = trim($this->request->getPost('template_name') ?? '');
        $html = $this->request->getPost('template_html') ?? '';

        if (empty($name) || empty($html)) {
            session()->setFlashdata('error', 'Template name and HTML are required');
            return redirect()->back();
        }

        $activeDomain = get_active_domain();
        $domainId = $activeDomain ? $activeDomain['id'] : null;

        $templateId = $this->campaignsModel->saveTemplate([
            'name'      => $name,
            'html'      => $html,
            'domain_id' => $domainId,
        ]);

        if ($templateId) {
            session()->setFlashdata('success', 'Template saved!');
        } else {
            session()->setFlashdata('error', 'Failed to save template');
        }

        return redirect()->back();
    }

    /**
     * Delete a saved template.
     * POST /campaigns/delete-template/{id}
     */
    public function deleteTemplate($id)
    {
        if (strtolower($this->request->getMethod()) !== 'post') {
            return redirect()->to('/campaigns');
        }

        $this->campaignsModel->deleteTemplate($id);
        session()->setFlashdata('success', 'Template deleted');
        return redirect()->back();
    }

    public function showSync()
    {
        $data = [
            'campaigns' => $this->campaignsModel->getCampaignsByDomain()
        ];

        echo view('Templates/header', ['currentPage' => 'campaigns']);
        echo view('Campaigns/sync', $data);
        echo view('Templates/footer');
    }

    public function syncEvents()
    {
        ini_set('max_execution_time', 300);
        set_time_limit(300);

        if (strtolower($this->request->getMethod()) !== 'post') {
            return redirect()->to('/campaigns/sync');
        }

        $campaignId = $this->request->getPost('campaignId');
        $resendEventsJson = $this->request->getPost('resendEvents');

        try {
            $resendEvents = json_decode($resendEventsJson, true);

            if (json_last_error() !== JSON_ERROR_NONE) {
                throw new \Exception('Invalid JSON format: ' . json_last_error_msg());
            }

            $emailEventsModel = model(EmailEventsModel::class);

            $chunks = array_chunk($resendEvents, 100);
            $totalResult = [
                'success' => true,
                'inserted_count' => 0,
                'skipped_count' => 0,
                'errors' => []
            ];

            foreach ($chunks as $chunk) {
                $result = $emailEventsModel->insertResendEvents($chunk, $campaignId);

                if (!$result['success']) {
                    session()->setFlashdata('error', $result['message']);
                    return redirect()->to('/campaigns/sync');
                }

                $totalResult['inserted_count'] += $result['inserted_count'];
                $totalResult['skipped_count'] += $result['skipped_count'];
                $totalResult['errors'] = array_merge($totalResult['errors'], $result['errors']);
            }

            if ($totalResult['inserted_count'] > 0) {
                session()->setFlashdata('success', "{$totalResult['inserted_count']} events successfully synchronized. {$totalResult['skipped_count']} events skipped (already exist).");
            } else {
                session()->setFlashdata('warning', "No new events synchronized. {$totalResult['skipped_count']} events skipped (already exist).");
            }

            return redirect()->to('/campaigns/sync')->with('result', $totalResult);
        } catch (\Exception $e) {
            session()->setFlashdata('error', 'Error: ' . $e->getMessage());
            return redirect()->to('/campaigns/sync');
        }
    }

    private function addUTMParameters($url, $params)
    {
        $parsedUrl = parse_url($url);
        $query = isset($parsedUrl['query']) ? $parsedUrl['query'] : '';
        parse_str($query, $queryParams);
        $queryParams = array_merge($queryParams, $params);
        $queryString = http_build_query($queryParams);

        return (isset($parsedUrl['scheme']) ? "{$parsedUrl['scheme']}:" : '') .
            ((isset($parsedUrl['user']) || isset($parsedUrl['host'])) ? '//' : '') .
            (isset($parsedUrl['user']) ? "{$parsedUrl['user']}" : '') .
            (isset($parsedUrl['pass']) ? ":{$parsedUrl['pass']}" : '') .
            (isset($parsedUrl['user']) ? '@' : '') .
            (isset($parsedUrl['host']) ? "{$parsedUrl['host']}" : '') .
            (isset($parsedUrl['port']) ? ":{$parsedUrl['port']}" : '') .
            (isset($parsedUrl['path']) ? "{$parsedUrl['path']}" : '') .
            "?$queryString" .
            (isset($parsedUrl['fragment']) ? "#{$parsedUrl['fragment']}" : '');
    }

    /**
     * Add UTM parameters to all links in HTML using regex.
     * Uses regex instead of DOMDocument to preserve the original HTML structure
     * (DOMDocument would strip conditional comments, re-encode entities,
     * and wrap content in <html><body> tags, breaking email templates).
     */
    private function addUTMParametersToLinks($html, $utmParams)
    {
        if (empty($html)) {
            return $html;
        }

        // Match href="http..." in <a> tags, using a callback to add UTM params
        return preg_replace_callback(
            '/(<a\s[^>]*href=["\'])((https?:\/\/[^"\']+))(["\'])/i',
            function ($matches) use ($utmParams) {
                $prefix  = $matches[1]; // <a ... href="
                $url     = $matches[2]; // the URL
                $suffix  = $matches[4]; // closing " or '
                $newUrl  = $this->addUTMParameters($url, $utmParams);
                return $prefix . $newUrl . $suffix;
            },
            $html
        );
    }
}
