<?php

namespace App\Controllers;

use App\Models\AudiencesModel;
use App\Models\ContactsModel;

class AudiencesController extends BaseController
{
    protected $audiencesModel;
    protected $contactsModel;

    public function __construct()
    {
        $this->audiencesModel = new AudiencesModel();
        $this->contactsModel = new ContactsModel();
        helper('domain');
    }

    public function index()
    {
        $data['allAudiences'] = $this->audiencesModel->getAllAudiences();

        echo view('Templates/header', ['currentPage' => 'audiences']);
        echo view('Audiences/index', $data);
        echo view('Templates/footer');
    }

    public function create()
    {
        if (strtolower($this->request->getMethod()) === 'post') {
            return $this->store();
        }

        echo view('Templates/header', ['currentPage' => 'audiences']);
        echo view('Audiences/create');
        echo view('Templates/footer');
    }

    public function store()
    {
        if (strtolower($this->request->getMethod()) !== 'post') {
            return redirect()->to('/audiences/create');
        }

        $activeDomain = get_active_domain();
        if (!$activeDomain) {
            return redirect()->back()->with('error', 'Please select an active domain first');
        }

        $audienceName = $this->request->getPost('name');
        $csvFile = $this->request->getFile('csvFile');

        $contactsIds = [];

        // CSV is optional — only process if a valid file was uploaded
        if ($csvFile && $csvFile->isValid() && $csvFile->getExtension() === 'csv') {
            $contacts = $this->parseCSVContacts($csvFile);
            if (!empty($contacts)) {
                $contactsIds = $this->processContacts($contacts);
            }
        }

        $audienceData = [
            'name' => $audienceName,
            'domain_id' => $activeDomain['id'],
        ];

        $audienceId = $this->audiencesModel->insertAudience($audienceData);
        if ($audienceId && !empty($contactsIds)) {
            $this->audiencesModel->setAudienceContacts($audienceId, $contactsIds);
        }
        session()->setFlashdata('success', 'Audience created successfully');

        return redirect()->route('audiences');
    }

    public function update()
    {
        if (strtolower($this->request->getMethod()) !== 'post') {
            return redirect()->to('/audiences');
        }

        set_time_limit(300);

        $audienceId = $this->request->getPost('id');
        $audienceName = $this->request->getPost('name');
        $csvFile = $this->request->getFile('csvFile');

        // Always update the name
        $audienceData = ['name' => $audienceName];
        $this->audiencesModel->updateAudience($audienceId, $audienceData);

        // Only replace contacts if a valid CSV was uploaded
        if ($csvFile && $csvFile->isValid() && $csvFile->getExtension() === 'csv') {
            $contacts = $this->parseCSVContacts($csvFile);
            if (!empty($contacts)) {
                $contactsIds = $this->processContacts($contacts);
                $this->audiencesModel->setAudienceContacts($audienceId, $contactsIds);
            }
        }

        session()->setFlashdata('success', 'Audience updated successfully');

        set_time_limit(30);
        return redirect()->route('audiences');
    }

    public function delete($id)
    {
        if (strtolower($this->request->getMethod()) !== 'post') {
            return redirect()->route('audiences');
        }

        $this->audiencesModel->deleteAudience($id);
        session()->setFlashdata('success', 'Audience deleted successfully');
        return redirect()->route('audiences');
    }

    public function details($id)
    {
        $data = [
            'audience'         => $this->audiencesModel->getAudience($id),
            'audienceContacts' => $this->audiencesModel->getAudienceContacts($id),
            'allContacts'      => $this->contactsModel->getAllContacts(),
        ];

        echo view('Templates/header', ['currentPage' => 'audiences']);
        echo view('Audiences/details', $data);
        echo view('Templates/footer');
    }

    public function addContact()
    {
        if (strtolower($this->request->getMethod()) !== 'post') {
            return redirect()->to('/audiences');
        }

        $audienceId = $this->request->getPost('audience_id');
        $contactId  = $this->request->getPost('contact_id');

        if (!$audienceId || !$contactId) {
            return redirect()->back()->with('error', 'Missing audience or contact');
        }

        $this->audiencesModel->addContactToAudience($audienceId, $contactId);
        session()->setFlashdata('success', 'Contact added to audience');
        return redirect()->to('/audiences/details/' . $audienceId);
    }

    public function removeContact()
    {
        if (strtolower($this->request->getMethod()) !== 'post') {
            return redirect()->to('/audiences');
        }

        $audienceId = $this->request->getPost('audience_id');
        $contactId  = $this->request->getPost('contact_id');

        $this->audiencesModel->removeContactFromAudience($audienceId, $contactId);
        session()->setFlashdata('success', 'Contact removed from audience');
        return redirect()->to('/audiences/details/' . $audienceId);
    }

    public function edit($id)
    {
        $audience = $this->audiencesModel->getAudience($id);

        if ($audience) {
            echo view('Templates/header', ['currentPage' => 'audiences']);
            echo view('Audiences/edit', ['audience' => $audience]);
            echo view('Templates/footer');
        } else {
            return redirect()->to('/audiences')->with('error', 'Audience not found');
        }
    }

    /**
     * Parse CSV file and return array of contacts
     */
    private function parseCSVContacts($csvFile): array
    {
        $delimiters = ['|', ';', '^', "\t"];
        $delimiter = ',';

        $str = file_get_contents($csvFile);
        $str = str_replace($delimiters, $delimiter, $str);
        file_put_contents($csvFile, $str);

        $newName = $csvFile->getRandomName();
        $csvFile->move(WRITEPATH . 'uploads/audience_csv', $newName);

        $filePath = WRITEPATH . 'uploads/audience_csv/' . $newName;
        $file = fopen($filePath, "r");
        $contacts = [];

        while (($data = fgetcsv($file, 1000, ',')) !== false) {
            // Validate that we have at least email and subscription status
            if (count($data) < 4) {
                continue;
            }

            $email = trim($data[0]);
            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                continue;
            }

            $subscribed_value = strtoupper(trim($data[3]));

            $contacts[] = [
                'email' => $email,
                'first_name' => $data[1] ?? '',
                'last_name' => $data[2] ?? '',
                'subscribed' => $subscribed_value === 'SUBSCRIBED',
            ];
        }
        fclose($file);

        return $contacts;
    }

    /**
     * Process contacts: update existing, insert new, return all IDs
     */
    private function processContacts(array $contacts): array
    {
        $allExistingContacts = $this->contactsModel->getAllContacts();
        $existingContactsByEmail = [];

        foreach ($allExistingContacts as $existingContact) {
            $existingContactsByEmail[$existingContact['email']] = $existingContact;
        }

        // Get active domain for new contacts
        $activeDomain = get_active_domain();
        $domainId = $activeDomain ? $activeDomain['id'] : null;

        $contactsToUpdate = [];
        $contactsToInsert = [];
        $contactsIds = [];

        foreach ($contacts as $contact) {
            $email = $contact['email'];
            if (isset($existingContactsByEmail[$email])) {
                $contactsToUpdate[] = [
                    'id' => $existingContactsByEmail[$email]['id'],
                    'data' => $contact
                ];
                $contactsIds[] = $existingContactsByEmail[$email]['id'];
            } else {
                if ($domainId) {
                    $contact['domain_id'] = $domainId;
                }
                $contactsToInsert[] = $contact;
            }
        }

        if (!empty($contactsToUpdate)) {
            $this->contactsModel->updateManyContacts($contactsToUpdate);
        }

        if (!empty($contactsToInsert)) {
            $newContactIds = $this->contactsModel->insertManyContacts($contactsToInsert);
            $contactsIds = array_merge($contactsIds, $newContactIds);
        }

        return $contactsIds;
    }
}
