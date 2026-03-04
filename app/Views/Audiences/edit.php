<div class="mb-3">
    <a href="<?= base_url('audiences/') ?>" class="btn btn-outline-primary btn-sm">
        <i class="fas fa-arrow-left me-1"></i> Back to Audiences
    </a>
</div>

<div class="card">
    <div class="card-header">
        <h5><i class="fas fa-edit me-2"></i>Edit Audience</h5>
    </div>
    <div class="card-body">
        <form action="<?= base_url('audiences/update') ?>" method="POST" enctype="multipart/form-data">
            <?= csrf_field() ?>
            <input type="hidden" name="id" value="<?= esc($audience['id'] ?? '') ?>">

            <div class="mb-3">
                <label class="form-label" for="name">Audience Name</label>
                <input class="form-control" type="text" name="name" id="name" value="<?= esc($audience['name'] ?? '') ?>" required>
            </div>

            <div class="mb-3">
                <label class="form-label" for="csvFile">Replace Contacts via CSV (optional)</label>
                <small class="form-text d-block mb-1">CSV format: Email, First Name, Last Name, Email Marketing Consent. Uploading a CSV will replace all current contacts in this audience. Leave empty to keep existing contacts.</small>
                <input class="form-control" type="file" name="csvFile" id="csvFile" accept=".csv">
            </div>

            <div class="d-flex justify-content-between">
                <a href="<?= base_url('audiences/') ?>" class="btn btn-secondary">Cancel</a>
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-save me-1"></i> Update Audience
                </button>
            </div>
        </form>
    </div>
</div>
