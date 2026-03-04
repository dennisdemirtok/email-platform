<div class="d-flex justify-content-between align-items-center mb-4">
    <div></div>
    <a href="<?= base_url('/users/create') ?>" class="btn btn-primary btn-sm">
        <i class="fas fa-plus me-1"></i> Create User
    </a>
</div>

<div class="card">
    <div class="card-header">
        <h5><i class="fas fa-users-cog me-2"></i>Users</h5>
    </div>
    <div class="card-body p-0">
        <?php if (!empty($users) && is_array($users)): ?>
            <div class="table-responsive">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Username</th>
                            <th>Role</th>
                            <th>Created</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($users as $user): ?>
                            <tr>
                                <td>
                                    <span class="fw-semibold"><?= esc($user['username'] ?? '') ?></span>
                                </td>
                                <td>
                                    <?php if (($user['role'] ?? '') === 'super'): ?>
                                        <span class="badge-status badge-verified">Super</span>
                                    <?php else: ?>
                                        <span class="badge-status badge-pending">User</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php
                                        $created = $user['created_at'] ?? '';
                                        echo $created ? date('Y-m-d H:i', strtotime($created)) : 'N/A';
                                    ?>
                                </td>
                                <td>
                                    <div class="action-buttons">
                                        <a href="<?= base_url('users/edit/' . esc($user['id'] ?? '')) ?>" class="btn btn-sm btn-outline-primary">
                                            <i class="fas fa-edit"></i> Edit
                                        </a>
                                        <?php if (($user['id'] ?? '') !== session()->get('user_id')): ?>
                                            <form action="<?= base_url('users/delete/' . esc($user['id'] ?? '')) ?>" method="post" class="d-inline delete-form">
                                                <?= csrf_field() ?>
                                                <button type="submit" class="btn btn-sm btn-outline-danger" data-confirm="Are you sure you want to delete this user?">
                                                    <i class="fas fa-trash"></i> Delete
                                                </button>
                                            </form>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php else: ?>
            <div class="empty-state">
                <i class="fas fa-users-cog d-block"></i>
                <h3>No Users Found</h3>
                <p>Create your first user to get started.</p>
                <a href="<?= base_url('/users/create') ?>" class="btn btn-primary">
                    <i class="fas fa-plus me-1"></i> Create User
                </a>
            </div>
        <?php endif; ?>
    </div>
</div>
