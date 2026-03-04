<div class="d-flex justify-content-between align-items-center mb-4">
    <div></div>
    <a href="<?= base_url('/users') ?>" class="btn btn-outline-secondary btn-sm">
        <i class="fas fa-arrow-left me-1"></i> Back to Users
    </a>
</div>

<div class="card">
    <div class="card-header">
        <h5><i class="fas fa-user-edit me-2"></i>Edit User: <?= esc($user['username'] ?? '') ?></h5>
    </div>
    <div class="card-body">
        <form action="<?= base_url('/users/edit/' . esc($user['id'] ?? '')) ?>" method="post">
            <?= csrf_field() ?>

            <div class="mb-3">
                <label for="username" class="form-label">Username</label>
                <input type="text" class="form-control" id="username" name="username" value="<?= esc(old('username') ?? $user['username'] ?? '') ?>" required minlength="3" maxlength="50">
            </div>

            <div class="mb-3">
                <label for="password" class="form-label">Password</label>
                <input type="password" class="form-control" id="password" name="password" minlength="6">
                <div class="form-text">Leave blank to keep current password. Minimum 6 characters if changing.</div>
            </div>

            <div class="mb-3">
                <label for="role" class="form-label">Role</label>
                <select class="form-select" id="role" name="role" required>
                    <option value="user" <?= ($user['role'] ?? '') === 'user' ? 'selected' : '' ?>>User (limited to assigned domains)</option>
                    <option value="super" <?= ($user['role'] ?? '') === 'super' ? 'selected' : '' ?>>Super (access to all domains)</option>
                </select>
            </div>

            <div class="mb-3" id="domainsSection">
                <label class="form-label">Assigned Domains</label>
                <div class="form-text mb-2">Select which domains this user can access. Only applies to "User" role.</div>
                <?php if (!empty($domains)): ?>
                    <?php foreach ($domains as $domain): ?>
                        <?php
                            $domainId = $domain['id'] ?? '';
                            $isAssigned = in_array($domainId, $userDomainIds ?? []);
                        ?>
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" name="domains[]" value="<?= esc($domainId) ?>" id="domain_<?= esc($domainId) ?>" <?= $isAssigned ? 'checked' : '' ?>>
                            <label class="form-check-label" for="domain_<?= esc($domainId) ?>">
                                <?= esc($domain['name'] ?? $domain['domain_name'] ?? 'Unknown') ?>
                            </label>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <p class="text-muted">No domains available. Import domains from Resend first.</p>
                <?php endif; ?>
            </div>

            <button type="submit" class="btn btn-primary">
                <i class="fas fa-save me-1"></i> Update User
            </button>
        </form>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    var roleSelect = document.getElementById('role');
    var domainsSection = document.getElementById('domainsSection');

    function toggleDomains() {
        if (roleSelect.value === 'super') {
            domainsSection.style.display = 'none';
        } else {
            domainsSection.style.display = 'block';
        }
    }

    roleSelect.addEventListener('change', toggleDomains);
    toggleDomains();
});
</script>
