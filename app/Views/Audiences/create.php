<div class="mb-3">
    <a href="<?= base_url('audiences/') ?>" class="btn btn-outline-primary btn-sm">
        <i class="fas fa-arrow-left me-1"></i> Back to Audiences
    </a>
</div>

<div class="card">
    <div class="card-header">
        <h5><i class="fas fa-plus-circle me-2"></i>Create Audience</h5>
    </div>
    <div class="card-body">
        <form action="<?= base_url('audiences/store') ?>" method="POST" enctype="multipart/form-data">
            <?= csrf_field() ?>

            <div class="mb-3">
                <label class="form-label" for="name">Audience Name</label>
                <input class="form-control" type="text" name="name" id="name" required placeholder="Enter audience name">
            </div>

            <div class="mb-3">
                <label class="form-label" for="csvFile">Upload CSV File (optional)</label>
                <small class="form-text d-block mb-1">CSV format: Email, First Name, Last Name, Email Marketing Consent. You can skip this and add contacts manually later.</small>
                <input class="form-control" type="file" name="csvFile" id="csvFile" accept=".csv">
            </div>

            <div class="d-flex justify-content-between">
                <a href="<?= base_url('audiences/') ?>" class="btn btn-secondary">Cancel</a>
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-save me-1"></i> Create Audience
                </button>
            </div>
        </form>
    </div>
</div>
