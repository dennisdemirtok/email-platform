<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <small class="text-muted">
            <i class="fas fa-info-circle me-1"></i>
            Campaign analytics are synced from Resend. Click "Sync" on a sent campaign to update stats.
        </small>
    </div>
    <div class="d-flex gap-2">
        <a href="<?= base_url('/campaigns/create') ?>" class="btn btn-primary btn-sm">
            <i class="fas fa-plus me-1"></i> New Campaign
        </a>
    </div>
</div>

<!-- Unsent Campaigns -->
<div class="card">
    <div class="card-header">
        <h5><i class="fas fa-edit me-2"></i>Draft Campaigns</h5>
    </div>
    <div class="card-body p-0">
        <?php
            $draftCampaigns = [];
            if (!empty($allCampaigns) && is_array($allCampaigns)):
                foreach ($allCampaigns as $c) {
                    if (($c['status'] ?? 'unsent') === 'unsent') { $draftCampaigns[] = $c; }
                }
            endif;
        ?>
        <?php if (!empty($draftCampaigns)): ?>
            <div class="table-responsive">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Campaign</th>
                            <th>Subject</th>
                            <th>Template</th>
                            <th>Created</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($draftCampaigns as $campaign): ?>
                                <tr>
                                    <td>
                                        <span class="fw-semibold"><?= esc($campaign['name'] ?? 'Unknown Campaign') ?></span>
                                    </td>
                                    <td><?= esc($campaign['subject'] ?? '') ?></td>
                                    <td>
                                        <button type="button" class="btn btn-sm btn-outline-primary view-template"
                                                data-bs-toggle="modal" data-bs-target="#templateModal"
                                                data-content-html="<?= htmlspecialchars($campaign['templateHTML'] ?? '', ENT_QUOTES, 'UTF-8') ?>"
                                                data-content-plaintext="<?= htmlspecialchars($campaign['templatePlainText'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
                                            <i class="fas fa-eye"></i> Preview
                                        </button>
                                    </td>
                                    <td>
                                        <?php
                                            $utcDateTime = $campaign['created_at'] ?? null;
                                            $formattedDate = 'N/A';
                                            if ($utcDateTime) {
                                                $dateTime = new DateTime($utcDateTime);
                                                $formattedDate = $dateTime->format('d M Y, H:i');
                                            }
                                        ?>
                                        <small class="text-muted"><?= esc($formattedDate) ?></small>
                                    </td>
                                    <td>
                                        <div class="action-buttons">
                                            <button type="button" class="btn btn-sm btn-success open-send-modal"
                                                    data-campaign-id="<?= esc($campaign['id'] ?? '') ?>">
                                                <i class="fas fa-paper-plane"></i> Send
                                            </button>
                                            <a href="<?= base_url('campaigns/edit/' . ($campaign['id'] ?? '')) ?>" class="btn btn-sm btn-accent">
                                                <i class="fas fa-edit"></i> Edit
                                            </a>
                                            <form action="<?= base_url('campaigns/delete/' . ($campaign['id'] ?? '')) ?>" method="POST" class="delete-form d-inline">
                                                <?= csrf_field() ?>
                                                <button type="submit" class="btn btn-sm btn-danger">
                                                    <i class="fas fa-trash"></i> Delete
                                                </button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php else: ?>
            <div class="empty-state">
                <i class="fas fa-paper-plane d-block"></i>
                <h3>No Draft Campaigns</h3>
                <p>All campaigns have been sent, or none have been created yet.</p>
                <a href="<?= base_url('/campaigns/create') ?>" class="btn btn-primary">
                    <i class="fas fa-plus me-1"></i> Create Campaign
                </a>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- Sent Campaigns -->
<div class="card">
    <div class="card-header">
        <h5><i class="fas fa-check-circle me-2"></i>Sent Campaigns</h5>
    </div>
    <div class="card-body p-0">
        <?php
            // Gather all non-draft campaigns
            $sentCampaigns = [];
            if (!empty($allCampaigns) && is_array($allCampaigns)) {
                foreach ($allCampaigns as $c) {
                    $st = $c['status'] ?? '';
                    if ($st === 'sent' || $st === 'sending' || $st === 'failed') {
                        $sentCampaigns[] = $c;
                    }
                }
            }
        ?>
        <?php if (!empty($sentCampaigns)): ?>
            <div class="table-responsive">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Campaign</th>
                            <th>Subject</th>
                            <th>Status</th>
                            <th>Template</th>
                            <th>Total Emails</th>
                            <th>Delivery Rate</th>
                            <th>Open Rate</th>
                            <th>Click Rate</th>
                            <th>Sent At</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($sentCampaigns as $campaign): ?>
                            <?php $stats = $campaignStats[$campaign['id']] ?? null; ?>
                            <tr>
                                <td>
                                    <span class="fw-semibold"><?= esc($campaign['name'] ?? 'Unknown Campaign') ?></span>
                                </td>
                                <td><?= esc($campaign['subject'] ?? '') ?></td>
                                <td>
                                    <?php
                                        $status = $campaign['status'] ?? 'unknown';
                                        $badgeClass = 'badge-pending';
                                        if ($status === 'sent') $badgeClass = 'badge-verified';
                                        elseif ($status === 'sending') $badgeClass = 'badge-pending';
                                        elseif ($status === 'failed') $badgeClass = 'badge-failed';
                                    ?>
                                    <span class="badge-status <?= $badgeClass ?>"><?= esc(ucfirst($status)) ?></span>
                                </td>
                                <td>
                                    <button type="button" class="btn btn-sm btn-outline-primary view-template"
                                            data-bs-toggle="modal" data-bs-target="#templateModal"
                                            data-content-html="<?= htmlspecialchars($campaign['templateHTML'] ?? '', ENT_QUOTES, 'UTF-8') ?>"
                                            data-content-plaintext="<?= htmlspecialchars($campaign['templatePlainText'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
                                        <i class="fas fa-eye"></i>
                                    </button>
                                </td>
                                <td><span class="fw-bold"><?= esc($stats['total'] ?? 0) ?></span></td>
                                <td>
                                    <?php if ($stats && $stats['total'] > 0): ?>
                                        <span class="badge-status badge-delivered">
                                            <?= number_format($stats['deliveryRate'] ?? 0, 1) ?>%
                                        </span>
                                        <small class="text-muted d-block">(<?= esc($stats['delivered'] ?? 0) ?>)</small>
                                    <?php else: ?>
                                        <small class="text-muted">-</small>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if ($stats && $stats['total'] > 0): ?>
                                        <span class="badge-status badge-opened">
                                            <?= number_format($stats['openRate'] ?? 0, 1) ?>%
                                        </span>
                                        <small class="text-muted d-block">(<?= esc($stats['opened'] ?? 0) ?>)</small>
                                    <?php else: ?>
                                        <small class="text-muted">-</small>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if ($stats && $stats['total'] > 0): ?>
                                        <span class="badge-status badge-clicked">
                                            <?= number_format($stats['clickRate'] ?? 0, 1) ?>%
                                        </span>
                                        <small class="text-muted d-block">(<?= esc($stats['clicked'] ?? 0) ?>)</small>
                                    <?php else: ?>
                                        <small class="text-muted">-</small>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php
                                        $sentDate = 'N/A';
                                        $sentAt = $campaign['sent_at'] ?? null;
                                        if ($sentAt) {
                                            $dateTime = new DateTime($sentAt);
                                            $dateTime->setTimezone(new DateTimeZone('Europe/Paris'));
                                            $sentDate = $dateTime->format('d M Y, H:i');
                                        }
                                    ?>
                                    <small class="text-muted"><?= esc($sentDate) ?></small>
                                </td>
                                <td>
                                    <button type="button" class="btn btn-sm btn-outline-info sync-campaign-btn"
                                            data-campaign-id="<?= esc($campaign['id'] ?? '') ?>">
                                        <i class="fas fa-sync-alt"></i> Sync
                                    </button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php else: ?>
            <div class="empty-state">
                <i class="fas fa-inbox d-block"></i>
                <h3>No Sent Campaigns</h3>
                <p>Campaigns that have been sent will appear here with analytics data.</p>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- Template Preview Modal -->
<div class="modal fade" id="templateModal" tabindex="-1" aria-labelledby="templateModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="templateModalLabel">
                    <i class="fas fa-code me-2"></i>Template Preview
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <ul class="nav nav-tabs" id="templateTab" role="tablist">
                    <li class="nav-item" role="presentation">
                        <button class="nav-link active" id="preview-tab" data-bs-toggle="tab" data-bs-target="#preview" type="button" role="tab" aria-controls="preview" aria-selected="true">
                            <i class="fas fa-eye me-1"></i> Preview
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="plaintext-tab" data-bs-toggle="tab" data-bs-target="#plaintext" type="button" role="tab" aria-controls="plaintext" aria-selected="false">
                            <i class="fas fa-align-left me-1"></i> Plain Text
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="source-tab" data-bs-toggle="tab" data-bs-target="#source" type="button" role="tab" aria-controls="source" aria-selected="false">
                            <i class="fas fa-code me-1"></i> Source
                        </button>
                    </li>
                </ul>
                <div class="tab-content mt-3">
                    <div class="tab-pane fade show active" id="preview" role="tabpanel" aria-labelledby="preview-tab">
                        <iframe id="templatePreview" style="border: 1px solid var(--card-border); border-radius: var(--border-radius); height: 500px; width: 100%;"></iframe>
                    </div>
                    <div class="tab-pane fade" id="plaintext" role="tabpanel" aria-labelledby="plaintext-tab">
                        <pre id="templatePlainText" style="height: 500px; width: 100%; overflow: auto; padding: 1rem; background: #f8f9fc; border-radius: var(--border-radius);"></pre>
                    </div>
                    <div class="tab-pane fade" id="source" role="tabpanel" aria-labelledby="source-tab">
                        <pre id="templateSource" style="height: 500px; width: 100%; overflow: auto; padding: 1rem; background: #f8f9fc; border-radius: var(--border-radius);"></pre>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<!-- Send Campaign Modal -->
<div class="modal fade" id="sendModal" tabindex="-1" aria-labelledby="sendModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="sendModalLabel">
                    <i class="fas fa-paper-plane me-2"></i>Sending Campaign
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div id="sendResultContent"></div>
            </div>
        </div>
    </div>
</div>

<script>
    // Template preview modal
    document.querySelectorAll('.view-template').forEach(function(button) {
        button.addEventListener('click', function() {
            var contentHTML = this.getAttribute('data-content-html');
            var contentPlainText = this.getAttribute('data-content-plaintext');
            document.getElementById('templatePreview').srcdoc = contentHTML;
            document.getElementById('templateSource').textContent = contentHTML;
            document.getElementById('templatePlainText').textContent = contentPlainText;
        });
    });

    // Send campaign modal with POST
    document.querySelectorAll('.open-send-modal').forEach(function(button) {
        button.addEventListener('click', function(event) {
            event.preventDefault();
            if (!confirm('Are you sure you want to send this campaign?')) {
                return;
            }

            var campaignId = this.getAttribute('data-campaign-id');
            var url = '<?= base_url("campaigns/send/") ?>' + campaignId;

            var message = '<div class="text-center py-4">' +
                '<i class="fas fa-spinner fa-spin fa-3x text-primary-custom mb-3"></i>' +
                '<h5>Sending campaign...</h5>' +
                '<p class="text-muted">Emails are being sent. This may take a moment.</p>' +
                '</div>';

            document.getElementById('sendResultContent').innerHTML = message;
            var sendModal = new bootstrap.Modal(document.getElementById('sendModal'));
            sendModal.show();

            document.getElementById('sendModal').addEventListener('hidden.bs.modal', function () {
                location.reload();
            });

            fetch(url, {
                method: 'POST'
            })
            .then(function(response) { return response.json(); })
            .then(function(data) {
                var resultHTML = '';
                if (data.success) {
                    resultHTML = '<div class="text-center py-4">' +
                        '<i class="fas fa-check-circle fa-3x text-success mb-3"></i>' +
                        '<h5>' + data.message + '</h5>' +
                        '<p class="text-muted">Execution time: ' + data.execution_time + 's</p>' +
                        '</div>';
                } else {
                    resultHTML = '<div class="text-center py-4">' +
                        '<i class="fas fa-exclamation-circle fa-3x text-danger mb-3"></i>' +
                        '<h5>Send failed</h5>' +
                        '<p class="text-muted">' + data.message + '</p>' +
                        '</div>';
                }
                document.getElementById('sendResultContent').innerHTML = resultHTML;
            })
            .catch(function(error) {
                document.getElementById('sendResultContent').innerHTML =
                    '<div class="text-center py-4">' +
                    '<i class="fas fa-exclamation-circle fa-3x text-danger mb-3"></i>' +
                    '<h5>Error sending campaign</h5>' +
                    '<p class="text-muted">' + error + '</p></div>';
            });
        });
    });

    // Sync campaign status
    document.querySelectorAll('.sync-campaign-btn').forEach(function(button) {
        button.addEventListener('click', function(event) {
            event.preventDefault();
            var btn = this;
            var campaignId = btn.getAttribute('data-campaign-id');
            var url = '<?= base_url("campaigns/sync-status/") ?>' + campaignId;

            // Show spinner
            btn.disabled = true;
            btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Syncing...';

            fetch(url, { method: 'POST' })
            .then(function(response) { return response.json(); })
            .then(function(data) {
                if (data.success) {
                    btn.innerHTML = '<i class="fas fa-check"></i> Done!';
                    btn.classList.remove('btn-outline-info');
                    btn.classList.add('btn-success');
                    // Reload after 1 second to show updated stats
                    setTimeout(function() { location.reload(); }, 1000);
                } else {
                    btn.innerHTML = '<i class="fas fa-exclamation-triangle"></i> Error';
                    btn.classList.remove('btn-outline-info');
                    btn.classList.add('btn-danger');
                    alert(data.message);
                    setTimeout(function() {
                        btn.disabled = false;
                        btn.innerHTML = '<i class="fas fa-sync-alt"></i> Sync';
                        btn.classList.remove('btn-danger');
                        btn.classList.add('btn-outline-info');
                    }, 3000);
                }
            })
            .catch(function(error) {
                btn.disabled = false;
                btn.innerHTML = '<i class="fas fa-sync-alt"></i> Sync';
                alert('Sync failed: ' + error);
            });
        });
    });
</script>
