<div class="row g-3 mb-4">
    <div class="col-xl-3 col-md-6">
        <div class="stat-card">
            <div class="stat-icon primary"><i class="fas fa-users"></i></div>
            <div class="stat-details">
                <div class="stat-label">Subscribed Contacts</div>
                <div class="stat-value"><?= esc($totalSubscribedContacts ?? 0) ?></div>
            </div>
        </div>
    </div>
    <div class="col-xl-3 col-md-6">
        <div class="stat-card success">
            <div class="stat-icon success"><i class="fas fa-check-circle"></i></div>
            <div class="stat-details">
                <div class="stat-label">Mails Delivered</div>
                <div class="stat-value"><?= esc($totalDelivered ?? 0) ?></div>
            </div>
        </div>
    </div>
    <div class="col-xl-3 col-md-6">
        <div class="stat-card info">
            <div class="stat-icon info"><i class="fas fa-envelope-open"></i></div>
            <div class="stat-details">
                <div class="stat-label">Mails Opened</div>
                <div class="stat-value"><?= esc($totalOpened ?? 0) ?></div>
            </div>
        </div>
    </div>
    <div class="col-xl-3 col-md-6">
        <div class="stat-card warning">
            <div class="stat-icon warning"><i class="fas fa-mouse-pointer"></i></div>
            <div class="stat-details">
                <div class="stat-label">Mails Clicked</div>
                <div class="stat-value"><?= esc($totalClicked ?? 0) ?></div>
            </div>
        </div>
    </div>
</div>
