<div class="d-flex justify-content-between align-items-center mb-4">
    <div></div>
    <a href="<?= base_url('/audiences/create') ?>" class="btn btn-primary btn-sm">
        <i class="fas fa-plus me-1"></i> New Audience
    </a>
</div>

<div class="card">
    <div class="card-header">
        <h5><i class="fas fa-users me-2"></i>Audiences</h5>
    </div>
    <div class="card-body p-0">
        <?php if (!empty($allAudiences) && is_array($allAudiences)): ?>
            <div class="table-responsive">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Name</th>
                            <th>Total Contacts</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($allAudiences as $audience): ?>
                            <tr>
                                <td>
                                    <span class="fw-semibold"><?= esc($audience['name'] ?? '') ?></span>
                                </td>
                                <td>
                                    <span class="badge-status badge-delivered"><?= esc($audience['contactsCount'] ?? 0) ?></span>
                                </td>
                                <td>
                                    <div class="action-buttons">
                                        <a href="<?= base_url('audiences/details/' . ($audience['id'] ?? '')) ?>" class="btn btn-sm btn-accent">
                                            <i class="fas fa-eye"></i> Contacts
                                        </a>
                                        <a href="<?= base_url('audiences/edit/' . ($audience['id'] ?? '')) ?>" class="btn btn-sm btn-outline-primary">
                                            <i class="fas fa-edit"></i> Edit
                                        </a>
                                        <form action="<?= base_url('audiences/delete/' . ($audience['id'] ?? '')) ?>" method="POST" class="delete-form d-inline">
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
                <i class="fas fa-users d-block"></i>
                <h3>No Audiences Found</h3>
                <p>Create your first audience to start organizing your contacts.</p>
                <a href="<?= base_url('/audiences/create') ?>" class="btn btn-primary">
                    <i class="fas fa-plus me-1"></i> Create Audience
                </a>
            </div>
        <?php endif; ?>
    </div>
</div>
