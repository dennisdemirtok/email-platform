<?php

namespace App\Controllers;

use App\Models\ContactsModel;
use App\Models\CampaignsModel;

class Home extends BaseController
{
    public function index()
    {
        $contactsModel = model(ContactsModel::class);
        $campaignsModel = model(CampaignsModel::class);

        helper('domain');

        // Get dashboard stats directly from campaign_sends table
        $dashboardStats = $campaignsModel->getDashboardStats();

        $data = [
            'totalSubscribedContacts' => $contactsModel->countSubscribedContacts(),
            'totalSent'      => $dashboardStats['totalSent'] ?? 0,
            'totalDelivered'  => $dashboardStats['totalDelivered'] ?? 0,
            'totalOpened'     => $dashboardStats['totalOpened'] ?? 0,
            'totalClicked'    => $dashboardStats['totalClicked'] ?? 0,
            'allCampaigns'    => $campaignsModel->getCampaignsByDomain(),
        ];
        echo view('Templates/header', ['currentPage' => 'home']);
        echo view('home', $data);
        echo view('real-time');
        echo view('Templates/footer');
    }
}
