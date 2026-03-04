<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= esc(ucfirst($currentPage ?? 'Dashboard')) ?> - Flattered Email Platform</title>
    <link rel="icon" href="<?= base_url('favicon.png') ?>" />
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link rel="stylesheet" href="<?= base_url('css/app.css') ?>">
    <meta name="csrf-token" content="<?= csrf_hash() ?>">
    <meta name="csrf-header" content="X-CSRF-TOKEN">
    <script>var BASE_URL = '<?= base_url('/') ?>';</script>
</head>
<body>
<?php helper('domain'); ?>

<!-- Toast Container -->
<div id="toastContainer" class="toast-container">
    <?php if (session()->getFlashdata('success')): ?>
        <div class="toast-notification success">
            <i class="fas fa-check-circle"></i>
            <span><?= esc(session()->getFlashdata('success')) ?></span>
            <button class="toast-close">&times;</button>
        </div>
    <?php endif; ?>
    <?php if (session()->getFlashdata('error')): ?>
        <div class="toast-notification error">
            <i class="fas fa-exclamation-circle"></i>
            <span><?= esc(session()->getFlashdata('error')) ?></span>
            <button class="toast-close">&times;</button>
        </div>
    <?php endif; ?>
    <?php if (session()->getFlashdata('warning')): ?>
        <div class="toast-notification warning">
            <i class="fas fa-exclamation-triangle"></i>
            <span><?= esc(session()->getFlashdata('warning')) ?></span>
            <button class="toast-close">&times;</button>
        </div>
    <?php endif; ?>
</div>

<div id="sidebarOverlay" class="sidebar-overlay"></div>

<div class="app-wrapper">
    <aside id="sidebar" class="sidebar">
        <div class="sidebar-brand">
            <h2>Flattered</h2>
            <small>Email Platform</small>
        </div>
        <nav class="sidebar-nav">
            <div class="nav-section">Main</div>
            <a href="<?= base_url('/') ?>" class="nav-link <?= ($currentPage ?? '') === 'home' ? 'active' : '' ?>">
                <i class="fas fa-chart-pie"></i> Dashboard
            </a>
            <a href="<?= base_url('/campaigns') ?>" class="nav-link <?= ($currentPage ?? '') === 'campaigns' ? 'active' : '' ?>">
                <i class="fas fa-paper-plane"></i> Campaigns
            </a>
            <div class="nav-section">Contacts</div>
            <a href="<?= base_url('/audiences') ?>" class="nav-link <?= ($currentPage ?? '') === 'audiences' ? 'active' : '' ?>">
                <i class="fas fa-users"></i> Audiences
            </a>
            <a href="<?= base_url('/contacts') ?>" class="nav-link <?= ($currentPage ?? '') === 'contacts' ? 'active' : '' ?>">
                <i class="fas fa-address-book"></i> Contacts
            </a>
            <div class="nav-section">Settings</div>
            <a href="<?= base_url('/domains') ?>" class="nav-link <?= ($currentPage ?? '') === 'domains' ? 'active' : '' ?>">
                <i class="fas fa-globe"></i> Domains
            </a>
            <a href="<?= base_url('/logs') ?>" class="nav-link <?= ($currentPage ?? '') === 'logs' ? 'active' : '' ?>">
                <i class="fas fa-list-alt"></i> Logs
            </a>
            <a href="<?= base_url('/campaigns/sync') ?>" class="nav-link <?= ($currentPage ?? '') === 'sync' ? 'active' : '' ?>">
                <i class="fas fa-sync-alt"></i> Sync Events
            </a>
            <?php if (session()->get('user_role') === 'super'): ?>
                <div class="nav-section">Admin</div>
                <a href="<?= base_url('/users') ?>" class="nav-link <?= ($currentPage ?? '') === 'users' ? 'active' : '' ?>">
                    <i class="fas fa-users-cog"></i> Users
                </a>
            <?php endif; ?>
        </nav>
        <div class="sidebar-footer">
            <?php
                $allDomains = get_all_active_domains();
                $activeDomain = get_active_domain();
                $activeDomainId = $activeDomain ? ($activeDomain['id'] ?? '') : '';
            ?>
            <?php if (!empty($allDomains) && count($allDomains) > 1): ?>
                <select id="domainSelector" class="domain-selector mb-2">
                    <?php foreach ($allDomains as $domain): ?>
                        <option value="<?= esc($domain['id'] ?? '') ?>" <?= ($domain['id'] ?? '') == $activeDomainId ? 'selected' : '' ?>>
                            <?= esc($domain['name'] ?? $domain['domain_name'] ?? 'Unknown') ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            <?php elseif (!empty($allDomains)): ?>
                <div class="domain-selector mb-2" style="text-align:center; opacity:0.7; font-size:0.85rem;">
                    <i class="fas fa-globe me-1"></i> <?= esc($allDomains[0]['name'] ?? $allDomains[0]['domain_name'] ?? 'Unknown') ?>
                </div>
            <?php endif; ?>
            <a href="<?= base_url('/logout') ?>" class="nav-link">
                <i class="fas fa-sign-out-alt"></i> Logout
            </a>
        </div>
    </aside>

    <main class="main-content">
        <div class="content-header">
            <div class="d-flex align-items-center">
                <button id="sidebarToggle" class="sidebar-toggle me-3">
                    <i class="fas fa-bars"></i>
                </button>
                <h1><?= esc(ucfirst($currentPage ?? 'Dashboard')) ?></h1>
            </div>
        </div>
        <div class="content-body">
