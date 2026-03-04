<?php

namespace App\Controllers;

use App\Models\EmailEventsModel;

class LogsController extends BaseController
{
    public function index()
    {
        $model = model(EmailEventsModel::class);

        $data = [
            'emailEvents' => $model->getEmailEvents()
        ];
        echo view('Templates/header', ['currentPage' => 'logs']);
        echo view('Logs/index', $data);
        echo view('Templates/footer');
    }

    public function explorer(){
        $model = model(EmailEventsModel::class);
        $emails = $model->getEventsGroupedByUniqueMail();
    
        // Définir l'ordre des types d'événements
        $order = ['sent', 'delivered', 'clicked', 'opened'];
    
        // Sort events by type
        foreach ($emails as &$email) {
            $eventsArray = $email['events'] ?? [];

            usort($eventsArray, function ($a, $b) use ($order) {
                return array_search($a['event_type'], $order) <=> array_search($b['event_type'], $order);
            });

            $email['events'] = $eventsArray;
        }

        $data['emails'] = $emails;

        echo view('Templates/header', ['currentPage' => 'logs']);
        echo view('Logs/explorer', $data);
        echo view('Templates/footer');
    }
}
