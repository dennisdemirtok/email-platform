<div class="d-flex justify-content-between align-items-center mb-4">
    <div></div>
    <a href="<?= base_url('/domains/import') ?>" class="btn btn-primary btn-sm">
        <i class="fas fa-download me-1"></i> Import from Resend
    </a>
</div>

<div class="card">
    <div class="card-header">
        <h5><i class="fas fa-globe me-2"></i>Domains</h5>
    </div>
    <div class="card-body p-0">
        <?php if (!empty($domains) && is_array($domains)): ?>
            <div class="table-responsive">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Domain Name</th>
                            <th>Sender Email</th>
                            <th>Display Name</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($domains as $domain): ?>
                            <tr>
                                <td>
                                    <span class="fw-semibold"><?= esc($domain['domain_name'] ?? '') ?></span>
                                </td>
                                <td><?= esc($domain['sender_email'] ?? 'N/A') ?></td>
                                <td><?= esc($domain['pretty_name'] ?? 'N/A') ?></td>
                                <td>
                                    <?php
                                        $status = $domain['status'] ?? 'unknown';
                                        $badgeClass = 'badge-pending';
                                        if ($status === 'verified' || $status === 'active') {
                                            $badgeClass = 'badge-verified';
                                        } elseif ($status === 'failed') {
                                            $badgeClass = 'badge-failed';
                                        }
                                    ?>
                                    <span class="badge-status <?= $badgeClass ?>"><?= esc($status) ?></span>
                                </td>
                                <td>
                                    <div class="action-buttons">
                                        <a href="<?= base_url('domains/edit/' . esc((string)($domain['domain_id'] ?? ''))) ?>" class="btn btn-sm btn-outline-primary">
                                            <i class="fas fa-edit"></i> Edit
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php else: ?>
            <div class="empty-state">
                <i class="fas fa-globe d-block"></i>
                <h3>No Domains Found</h3>
                <p>Import your domains from Resend to get started.</p>
                <a href="<?= base_url('/domains/import') ?>" class="btn btn-primary">
                    <i class="fas fa-download me-1"></i> Import from Resend
                </a>
            </div>
        <?php endif; ?>
    </div>
</div>
