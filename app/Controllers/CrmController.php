<?php

namespace App\Controllers;

use App\Models\CrmModel;
use App\Models\ContactsModel;

class CrmController extends BaseController
{
    protected $crmModel;
    protected $contactsModel;

    public function __construct()
    {
        $this->crmModel = new CrmModel();
        $this->contactsModel = new ContactsModel();
        helper('domain');
    }

    /**
     * CRM contact list with category filter.
     */
    public function index()
    {
        $category = $this->request->getGet('category');
        $search = $this->request->getGet('q');

        $data = [
            'crmContacts' => $this->crmModel->getCrmContacts($category, $search),
            'categories'  => $this->crmModel->getCategories(),
            'activeCategory' => $category,
            'searchQuery' => $search,
        ];

        echo view('Templates/header', ['currentPage' => 'crm']);
        echo view('Crm/index', $data);
        echo view('Templates/footer');
    }

    /**
     * Contact profile with CRM data, email history, send form.
     */
    public function profile(string $contactId)
    {
        $contact = $this->contactsModel->getContact($contactId);
        if (!$contact) {
            session()->setFlashdata('error', 'Contact not found');
            return redirect()->to('/crm');
        }

        $crmData = $this->crmModel->getCrmDataByContactId($contactId);
        $crmEmails = $this->crmModel->getCrmEmailHistory($contactId);
        $campaignEmails = $this->crmModel->getCampaignEmailHistory($contact['email']);

        // Merge and sort email history
        $emailHistory = [];
        foreach ($crmEmails as $e) {
            $emailHistory[] = [
                'type'    => 'individual',
                'subject' => $e['subject'] ?? '',
                'status'  => $e['status'] ?? 'sent',
                'date'    => $e['sent_at'] ?? '',
            ];
        }
        foreach ($campaignEmails as $e) {
            $emailHistory[] = [
                'type'    => 'campaign',
                'subject' => $e['subject'] ?? '',
                'status'  => $e['status'] ?? '',
                'date'    => $e['created_at'] ?? '',
            ];
        }
        usort($emailHistory, fn($a, $b) => strtotime($b['date']) - strtotime($a['date']));

        $activeDomain = get_active_domain();

        $data = [
            'contact'      => $contact,
            'crmData'      => $crmData,
            'emailHistory' => $emailHistory,
            'activeDomain' => $activeDomain,
            'categories'   => $this->crmModel->getCategories(),
        ];

        echo view('Templates/header', ['currentPage' => 'crm']);
        echo view('Crm/profile', $data);
        echo view('Templates/footer');
    }

    /**
     * Update CRM data for a contact.
     */
    public function updateCrm()
    {
        if (strtolower($this->request->getMethod()) !== 'post') {
            return redirect()->to('/crm');
        }

        $contactId = $this->request->getPost('contact_id');

        $data = [
            'company_name'   => trim($this->request->getPost('company_name') ?? ''),
            'contact_person' => trim($this->request->getPost('contact_person') ?? ''),
            'category'       => trim($this->request->getPost('category') ?? ''),
            'needs'          => trim($this->request->getPost('needs') ?? ''),
            'last_contact'   => trim($this->request->getPost('last_contact') ?? ''),
            'notes'          => trim($this->request->getPost('notes') ?? ''),
        ];

        $this->crmModel->upsertCrmData($contactId, $data);
        session()->setFlashdata('success', 'CRM data updated');
        return redirect()->to('/crm/profile/' . $contactId);
    }

    /**
     * Send individual email from profile page.
     */
    public function sendEmail(string $contactId)
    {
        helper('resend');

        $contact = $this->contactsModel->getContact($contactId);
        if (!$contact) {
            return $this->response->setJSON(['success' => false, 'message' => 'Contact not found']);
        }

        $subject = trim($this->request->getPost('subject') ?? '');
        $bodyHtml = trim($this->request->getPost('body_html') ?? '');

        if (empty($subject) || empty($bodyHtml)) {
            return $this->response->setJSON(['success' => false, 'message' => 'Subject and message are required']);
        }

        $activeDomain = get_active_domain();
        if (!$activeDomain) {
            return $this->response->setJSON(['success' => false, 'message' => 'No active domain']);
        }

        $senderEmail = $activeDomain['sender_email'] ?? ('noreply@' . ($activeDomain['name'] ?? 'example.com'));
        $prettyName = $activeDomain['pretty_name'] ?? $activeDomain['name'] ?? 'Email Platform';
        $from = "{$prettyName} <{$senderEmail}>";

        // Generate plain text
        $bodyText = strip_tags($bodyHtml);

        $result = resend_send_email($from, $contact['email'], $subject, $bodyHtml, $bodyText);

        if (!$result || empty($result['id'])) {
            return $this->response->setJSON(['success' => false, 'message' => 'Failed to send email']);
        }

        // Log the email
        $this->crmModel->insertCrmEmail([
            'contact_id'      => $contactId,
            'domain_id'       => $activeDomain['id'],
            'from_address'    => $from,
            'to_address'      => $contact['email'],
            'subject'         => $subject,
            'body_html'       => $bodyHtml,
            'body_text'       => $bodyText,
            'resend_email_id' => $result['id'],
            'status'          => 'sent',
        ]);

        // Update last_contact
        $this->crmModel->upsertCrmData($contactId, [
            'last_contact' => date('Y-m-d'),
        ]);

        $csrfToken = csrf_hash();
        return $this->response->setJSON([
            'success'    => true,
            'message'    => 'Email sent to ' . $contact['email'],
            'csrf_token' => $csrfToken,
        ]);
    }

    /**
     * Show import form.
     */
    public function import()
    {
        echo view('Templates/header', ['currentPage' => 'crm']);
        echo view('Crm/import');
        echo view('Templates/footer');
    }

    /**
     * Process CSV import.
     */
    public function doImport()
    {
        if (strtolower($this->request->getMethod()) !== 'post') {
            return redirect()->to('/crm/import');
        }

        $file = $this->request->getFile('csv_file');
        if (!$file || !$file->isValid()) {
            session()->setFlashdata('error', 'Please upload a valid CSV file');
            return redirect()->to('/crm/import');
        }

        $activeDomain = get_active_domain();
        if (!$activeDomain) {
            session()->setFlashdata('error', 'No active domain');
            return redirect()->to('/crm/import');
        }

        $handle = fopen($file->getTempName(), 'r');
        if (!$handle) {
            session()->setFlashdata('error', 'Could not read file');
            return redirect()->to('/crm/import');
        }

        // Read header row
        $header = fgetcsv($handle, 0, ';');
        if (!$header) {
            $header = fgetcsv($handle, 0, ',');
        }

        // Normalize headers
        $headerMap = [];
        foreach ($header as $i => $h) {
            $h = strtolower(trim($h));
            if (str_contains($h, 'e-post') || str_contains($h, 'email')) $headerMap['email'] = $i;
            elseif (str_contains($h, 'namn') || str_contains($h, 'företag') || str_contains($h, 'company')) $headerMap['company_name'] = $i;
            elseif (str_contains($h, 'kontaktperson') || str_contains($h, 'contact')) $headerMap['contact_person'] = $i;
            elseif (str_contains($h, 'kategori') || str_contains($h, 'category')) $headerMap['category'] = $i;
            elseif (str_contains($h, 'vill') || str_contains($h, 'behöver') || str_contains($h, 'needs')) $headerMap['needs'] = $i;
            elseif (str_contains($h, 'senaste') || str_contains($h, 'last contact')) $headerMap['last_contact'] = $i;
            elseif (str_contains($h, 'noter') || str_contains($h, 'notes')) $headerMap['notes'] = $i;
        }

        $rows = [];
        while (($line = fgetcsv($handle, 0, ';')) !== false) {
            if (empty($line) || count($line) < 2) continue;

            $row = [];
            foreach ($headerMap as $field => $colIdx) {
                $row[$field] = isset($line[$colIdx]) ? trim($line[$colIdx]) : '';
            }

            // Skip section headers (no email)
            if (empty($row['email']) || strpos($row['email'], '@') === false) {
                continue;
            }

            // Clean category (remove emoji prefixes for consistency)
            if (!empty($row['category'])) {
                $row['category'] = preg_replace('/^[\x{1F300}-\x{1F9FF}\x{2600}-\x{26FF}\x{2700}-\x{27BF}\s]+/u', '', $row['category']);
                $row['category'] = trim($row['category']);
            }

            $rows[] = $row;
        }
        fclose($handle);

        $result = $this->crmModel->importBulkCrmData($rows, $activeDomain['id']);

        $msg = "Import complete: {$result['created']} created, {$result['updated']} updated.";
        if (!empty($result['errors'])) {
            $msg .= ' Errors: ' . count($result['errors']);
            log_message('error', 'CRM import errors: ' . json_encode($result['errors']));
        }

        session()->setFlashdata('success', $msg);
        return redirect()->to('/crm');
    }
}
