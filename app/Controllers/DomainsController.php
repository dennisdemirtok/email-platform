<?php

namespace App\Controllers;

use App\Models\DomainsModel;

class DomainsController extends BaseController
{
    protected $domainsModel;

    public function __construct()
    {
        $this->domainsModel = new DomainsModel();
        helper('domain');
    }

    public function index()
    {
        $data['domains'] = $this->domainsModel->findAll();
        
        echo view('Templates/header', ['currentPage' => 'domains']);
        echo view('Domains/index', $data);
        echo view('Templates/footer');
    }

    public function setActive($id)
    {
        $session = session();
        $role = $session->get('user_role') ?? 'super';

        // Regular users can only switch to domains they have access to
        if ($role !== 'super') {
            $allowedIds = $session->get('allowed_domain_ids') ?? [];
            if (!in_array($id, $allowedIds)) {
                return $this->response->setJSON([
                    'success' => false,
                    'message' => 'You do not have access to this domain.',
                ]);
            }
        }

        set_active_domain($id);
        invalidate_domain_cache();
        return $this->response->setJSON(['success' => true]);
    }

    public function import()
    {
        $resendDomains = $this->domainsModel ->getAllDomains();
        $importCount = 0;

        foreach ($resendDomains as $domain) {
            $existingDomain = $this->domainsModel->findByDomainId($domain['domain_id']);

            if (!$existingDomain) {
                $domainData = [
                    'domain_id' => $domain['domain_id'],
                    'domain_name' => $domain['domain_name'],
                    'status' => $domain['status'],
                    'created_at' => date('Y-m-d\TH:i:s\Z', strtotime($domain['created_at'])),
                    'updated_at' => date('Y-m-d\TH:i:s\Z')
                ];

                $this->domainsModel->insertDomain($domainData);
                $importCount++;
            }
        }

        invalidate_domain_cache();

        if ($importCount > 0) {
            session()->setFlashdata('success', "{$importCount} domain(s) successfully imported from Resend");
        } else {
            session()->setFlashdata('info', "No new domains to import from Resend");
        }

        return redirect()->to('/domains');
    }

    public function edit($id = null)
    {
        if ($id === null) {
            return redirect()->to('/domains');
        }

        $domain = $this->domainsModel->findByDomainId($id);
        if (!$domain) {
            session()->setFlashdata('error', 'Domain not found');
            return redirect()->to('/domains');
        }

        if (strtolower($this->request->getMethod()) === 'post') {
            $rules = [
                'sender_email' => 'required|valid_email',
                'pretty_name' => 'required|min_length[3]'
            ];

            if ($this->validate($rules)) {
                $updateData = [
                    'sender_email' => $this->request->getPost('sender_email'),
                    'pretty_name' => $this->request->getPost('pretty_name'),
                    'updated_at' => date('Y-m-d\TH:i:s\Z')
                ];

                $this->domainsModel->updateByDomainId($id, $updateData);
                invalidate_domain_cache();

                session()->setFlashdata('success', 'Domain updated successfully');
                return redirect()->to('/domains');
            } else {
                session()->setFlashdata('error', 'Please check your input');
            }
        }
        echo view('Templates/header', ['currentPage' => 'domains']);
        echo view('Domains/edit', ['domain' => $domain]);
        return view('Templates/footer');
    }
}
