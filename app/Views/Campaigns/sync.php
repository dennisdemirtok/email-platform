<div class="card">
    <div class="card-header">
        <h5><i class="fas fa-sync-alt me-2"></i>Synchronize Resend Events</h5>
    </div>
    <div class="card-body">
        <form action="<?= base_url('campaigns/sync-events') ?>" method="POST" id="syncForm">
            <?= csrf_field() ?>

            <div class="mb-3">
                <label for="campaignId" class="form-label">Campaign</label>
                <select name="campaignId" id="campaignId" class="form-select" required>
                    <option value="">-- Select a campaign --</option>
                    <?php if (!empty($campaigns)): ?>
                        <?php foreach ($campaigns as $campaign): ?>
                            <option value="<?= esc($campaign['id'] ?? '') ?>">
                                <?= esc($campaign['name'] ?? 'Unknown') ?> (<?= esc($campaign['subject'] ?? '') ?>)
                            </option>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </select>
            </div>

            <div class="mb-3">
                <label for="resendEvents" class="form-label">Resend Events (JSON)</label>
                <small class="form-text d-block mb-1">Paste JSON array of Resend events to synchronize</small>
                <textarea name="resendEvents" id="resendEvents" class="form-control" rows="10" required
                    placeholder='[{
    "id": "8975b0cf-897c-4223-96d9-d9cd8b1c2b0e",
    "to": ["example@email.com"],
    "subject": "Campaign Subject",
    "created_at": "2024-11-15 09:59:58.334031+00",
    "last_event": "opened"
}]'></textarea>
            </div>

            <button type="submit" class="btn btn-primary">
                <i class="fas fa-sync-alt me-1"></i> Synchronize
            </button>
        </form>
    </div>
</div>

<?php if (isset($result)): ?>
    <div class="card">
        <div class="card-header">
            <h5><i class="fas fa-chart-bar me-2"></i>Synchronization Results</h5>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table">
                    <tbody>
                        <tr>
                            <td class="fw-semibold">Inserted Events</td>
                            <td>
                                <span class="badge-status badge-delivered"><?= esc($result['inserted_count'] ?? 0) ?></span>
                            </td>
                        </tr>
                        <tr>
                            <td class="fw-semibold">Skipped Events (already exist)</td>
                            <td>
                                <span class="badge-status badge-unsent"><?= esc($result['skipped_count'] ?? 0) ?></span>
                            </td>
                        </tr>
                        <?php if (!empty($result['errors'])): ?>
                            <tr>
                                <td class="fw-semibold">Errors</td>
                                <td>
                                    <ul class="list-unstyled mb-0">
                                        <?php foreach ($result['errors'] as $error): ?>
                                            <li class="text-danger mb-1">
                                                <i class="fas fa-exclamation-circle me-1"></i>
                                                Email ID: <?= esc($error['email_id'] ?? '') ?> -
                                                <?= esc($error['error'] ?? '') ?>
                                            </li>
                                        <?php endforeach; ?>
                                    </ul>
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
<?php endif; ?>

<script>
    document.getElementById('syncForm').addEventListener('submit', function(e) {
        try {
            var jsonText = document.getElementById('resendEvents').value;
            JSON.parse(jsonText);
        } catch (err) {
            alert('Invalid JSON format. Please check the format and try again.');
            e.preventDefault();
        }
    });
</script>
