<div class="mb-3">
    <a href="<?= base_url('domains/') ?>" class="btn btn-outline-primary btn-sm">
        <i class="fas fa-arrow-left me-1"></i> Back to Domains
    </a>
</div>

<div class="card">
    <div class="card-header">
        <h5><i class="fas fa-edit me-2"></i>Edit Domain</h5>
    </div>
    <div class="card-body">
        <form action="<?= base_url('domains/edit/' . esc($domain['domain_id'] ?? '')) ?>" method="post">
            <?= csrf_field() ?>

            <div class="mb-3">
                <label for="domain_name" class="form-label">Domain Name</label>
                <input type="text" class="form-control" id="domain_name" value="<?= esc($domain['domain_name'] ?? '') ?>" disabled>
                <small class="form-text">Domain name cannot be changed as it is managed by Resend</small>
            </div>

            <div class="mb-3">
                <label for="sender_email" class="form-label">Sender Email <span class="text-danger">*</span></label>
                <input type="email" class="form-control <?= (isset($validation) && $validation->hasError('sender_email')) ? 'is-invalid' : '' ?>"
                       id="sender_email" name="sender_email"
                       value="<?= esc(old('sender_email', $domain['sender_email'] ?? '')) ?>" required>
                <?php if (isset($validation) && $validation->hasError('sender_email')): ?>
                    <div class="invalid-feedback">
                        <?= esc($validation->getError('sender_email')) ?>
                    </div>
                <?php endif; ?>
                <small class="form-text">This email will be used as the sender for this domain</small>
            </div>

            <div class="mb-3">
                <label for="pretty_name" class="form-label">Display Name <span class="text-danger">*</span></label>
                <input type="text" class="form-control <?= (isset($validation) && $validation->hasError('pretty_name')) ? 'is-invalid' : '' ?>"
                       id="pretty_name" name="pretty_name"
                       value="<?= esc(old('pretty_name', $domain['pretty_name'] ?? '')) ?>" required>
                <?php if (isset($validation) && $validation->hasError('pretty_name')): ?>
                    <div class="invalid-feedback">
                        <?= esc($validation->getError('pretty_name')) ?>
                    </div>
                <?php endif; ?>
                <small class="form-text">A friendly name to identify this domain in the interface</small>
            </div>

            <div class="mb-3">
                <label for="status" class="form-label">Status</label>
                <input type="text" class="form-control" id="status" value="<?= esc($domain['status'] ?? '') ?>" disabled>
                <small class="form-text">Domain status is managed by Resend</small>
            </div>

            <div class="d-flex justify-content-between">
                <a href="<?= base_url('domains') ?>" class="btn btn-secondary">Cancel</a>
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-save me-1"></i> Update Domain
                </button>
            </div>
        </form>
    </div>
</div>
