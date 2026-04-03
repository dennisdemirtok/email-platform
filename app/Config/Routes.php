<?php

use CodeIgniter\Router\RouteCollection;

/**
 * @var RouteCollection $routes
 */

$routes->group('', ['filter' => 'auth'], function ($routes) {
    $routes->get('/', 'Home::index');

    $routes->get('/campaigns', 'CampaignsController::index', ['as' => 'campaigns']);
    $routes->get('/campaigns/create', 'CampaignsController::create');
    $routes->post('/campaigns/store', 'CampaignsController::store');
    $routes->post('/campaigns/delete/(:segment)', 'CampaignsController::delete/$1');
    $routes->get('/campaigns/edit/(:segment)', 'CampaignsController::edit/$1');
    $routes->post('/campaigns/send/(:segment)', 'CampaignsController::send/$1');
    $routes->post('/campaigns/update', 'CampaignsController::update');
    $routes->post('/campaigns/generate', 'CampaignsController::generate');
    $routes->get('/campaigns/reloadAnalytics', 'CampaignsController::reloadAnalytics');
    $routes->get('/campaigns/sync', 'CampaignsController::showSync');
    $routes->post('/campaigns/sync-events', 'CampaignsController::syncEvents');
    $routes->post('/campaigns/sync-status/(:segment)', 'CampaignsController::syncCampaignStatus/$1');
    $routes->post('/campaigns/save-template', 'CampaignsController::saveTemplate');
    $routes->post('/campaigns/delete-template/(:segment)', 'CampaignsController::deleteTemplate/$1');
    $routes->post('/campaigns/send-preview', 'CampaignsController::sendPreview');
    $routes->post('/campaigns/upload-image', 'CampaignsController::uploadImage');

    $routes->get('/logs', 'LogsController::index');
    $routes->get('/explorer', 'LogsController::explorer');

    $routes->get('/contacts', 'ContactsController::index');
    $routes->post('/contacts/store', 'ContactsController::store');
    $routes->post('/contacts/update', 'ContactsController::update');
    $routes->post('/contacts/delete/(:segment)', 'ContactsController::delete/$1');

    $routes->get('/audiences', 'AudiencesController::index', ['as' => 'audiences']);
    $routes->get('/audiences/create', 'AudiencesController::create');
    $routes->post('/audiences/store', 'AudiencesController::store');
    $routes->post('/audiences/delete/(:segment)', 'AudiencesController::delete/$1');
    $routes->get('/audiences/edit/(:segment)', 'AudiencesController::edit/$1');
    $routes->get('/audiences/details/(:segment)', 'AudiencesController::details/$1');
    $routes->post('/audiences/update', 'AudiencesController::update');
    $routes->post('/audiences/addContact', 'AudiencesController::addContact');
    $routes->post('/audiences/removeContact', 'AudiencesController::removeContact');

    $routes->get('/domains', 'DomainsController::index');
    $routes->get('/domains/import', 'DomainsController::import');
    $routes->get('/domains/create', 'DomainsController::create');
    $routes->post('/domains/create', 'DomainsController::create');
    $routes->get('/domains/edit/(:segment)', 'DomainsController::edit/$1');
    $routes->post('/domains/edit/(:segment)', 'DomainsController::edit/$1');
    $routes->post('/domains/delete/(:segment)', 'DomainsController::delete/$1');
    $routes->post('/domains/set-active/(:segment)', 'DomainsController::setActive/$1');

    // CRM
    $routes->get('/crm', 'CrmController::index');
    $routes->get('/crm/profile/(:segment)', 'CrmController::profile/$1');
    $routes->post('/crm/update', 'CrmController::updateCrm');
    $routes->post('/crm/send-email/(:segment)', 'CrmController::sendEmail/$1');
    $routes->get('/crm/import', 'CrmController::import');
    $routes->post('/crm/import', 'CrmController::doImport');
});

$routes->group('users', ['filter' => ['auth', 'super']], function ($routes) {
    $routes->get('/', 'UsersController::index');
    $routes->get('create', 'UsersController::create');
    $routes->post('create', 'UsersController::create');
    $routes->get('edit/(:segment)', 'UsersController::edit/$1');
    $routes->post('edit/(:segment)', 'UsersController::edit/$1');
    $routes->post('delete/(:segment)', 'UsersController::delete/$1');
});

$routes->get('/login', 'AuthController::login');
$routes->post('/login', 'AuthController::doLogin');
$routes->get('/logout', 'AuthController::logout');
$routes->get('/unsubscribe/(:segment)', 'ContactsController::unsubscribe/$1');
