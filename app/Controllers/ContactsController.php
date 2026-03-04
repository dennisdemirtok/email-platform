<?php

namespace App\Controllers;

use App\Models\ContactsModel;
use App\Models\AudiencesModel;

class ContactsController extends BaseController
{
    protected $contactsModel;

    public function __construct()
    {
        $this->contactsModel = new ContactsModel();
        helper('domain');
    }

    public function index()
    {
        $audiencesModel = new AudiencesModel();

        $data = [
            'allContacts'  => $this->contactsModel->getAllContacts(),
            'allAudiences' => $audiencesModel->getAllAudiences(),
        ];

        echo view('Templates/header', ['currentPage' => 'contacts']);
        echo view('Contacts/index', $data);
        echo view('Templates/footer');
    }

    public function store()
    {
        if (strtolower($this->request->getMethod()) !== 'post') {
            return redirect()->to('/contacts');
        }

        $email     = trim($this->request->getPost('email'));
        $firstName = trim($this->request->getPost('first_name'));
        $lastName  = trim($this->request->getPost('last_name'));
        $audienceId = $this->request->getPost('audience_id');

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            session()->setFlashdata('error', 'Please enter a valid email address');
            return redirect()->to('/contacts');
        }

        // Check for duplicate
        $existing = $this->contactsModel->getContactByEmail($email);
        if ($existing) {
            // If an audience was specified, add existing contact to it
            if (!empty($audienceId)) {
                $audiencesModel = new AudiencesModel();
                $audiencesModel->addContactToAudience($audienceId, $existing['id']);
                session()->setFlashdata('success', 'Contact already exists and was added to the audience');
            } else {
                session()->setFlashdata('error', 'A contact with this email already exists');
            }

            $redirectTo = $this->request->getPost('redirect_to');
            return redirect()->to($redirectTo ?: '/contacts');
        }

        // Get active domain for domain_id (optional — don't block contact creation)
        $domainId = null;
        try {
            $activeDomain = get_active_domain();
            $domainId = $activeDomain ? ($activeDomain['id'] ?? null) : null;
        } catch (\Exception $e) {
            log_message('warning', 'Could not get active domain: ' . $e->getMessage());
        }

        $contactData = [
            'email'      => $email,
            'first_name' => $firstName,
            'last_name'  => $lastName,
            'subscribed' => true,
        ];

        if ($domainId) {
            $contactData['domain_id'] = $domainId;
        }

        $contactId = $this->contactsModel->insertContact($contactData);

        if ($contactId && !empty($audienceId)) {
            $audiencesModel = new AudiencesModel();
            $audiencesModel->addContactToAudience($audienceId, $contactId);
        }

        session()->setFlashdata('success', 'Contact created successfully');

        $redirectTo = $this->request->getPost('redirect_to');
        return redirect()->to($redirectTo ?: '/contacts');
    }

    public function update()
    {
        if (strtolower($this->request->getMethod()) !== 'post') {
            return redirect()->to('/contacts');
        }

        $contactId = $this->request->getPost('id');
        $email     = trim($this->request->getPost('email'));

        if (!empty($email) && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return redirect()->back()->with('error', 'Please enter a valid email address');
        }

        $updateData = [
            'first_name' => trim($this->request->getPost('first_name')),
            'last_name'  => trim($this->request->getPost('last_name')),
            'email'      => $email,
        ];

        $this->contactsModel->updateContact($contactId, $updateData);
        session()->setFlashdata('success', 'Contact updated successfully');
        return redirect()->to('/contacts');
    }

    public function delete($id)
    {
        if (strtolower($this->request->getMethod()) !== 'post') {
            return redirect()->to('/contacts');
        }

        $this->contactsModel->deleteContact($id);
        session()->setFlashdata('success', 'Contact deleted successfully');
        return redirect()->to('/contacts');
    }

    public function unsubscribe($contactId)
    {
        $contact = $this->contactsModel->getContact($contactId);
        if (!$contact) {
            return view('Contacts/unsubscribe', ['message' => 'Contact not found']);
        }

        $this->contactsModel->updateContact($contactId, ['subscribed' => false]);

        return view('Contacts/unsubscribe', ['message' => 'Contact unsubscribed']);
    }
}
