<div class="mb-3">
    <a href="<?= base_url('/crm') ?>" class="btn btn-sm btn-outline-secondary">
        <i class="fas fa-arrow-left me-1"></i> Tillbaka till CRM
    </a>
</div>

<div class="row g-4">
    <!-- Left column: Contact info + CRM data + Send email -->
    <div class="col-lg-8">

        <!-- Contact Info -->
        <div class="card">
            <div class="card-header">
                <h5><i class="fas fa-user me-2"></i><?= esc($crmData['company_name'] ?? $contact['email']) ?></h5>
                <?php if (!empty($contact['subscribed'])): ?>
                    <span class="badge-status badge-subscribed">Prenumerant</span>
                <?php else: ?>
                    <span class="badge-status badge-unsubscribed">Avprenumererad</span>
                <?php endif ?>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-sm-4 mb-2">
                        <small class="text-muted d-block">E-post</small>
                        <strong><?= esc($contact['email']) ?></strong>
                    </div>
                    <div class="col-sm-4 mb-2">
                        <small class="text-muted d-block">Namn</small>
                        <span><?= esc(trim(($contact['firstName'] ?? '') . ' ' . ($contact['lastName'] ?? ''))) ?: '-' ?></span>
                    </div>
                    <div class="col-sm-4 mb-2">
                        <small class="text-muted d-block">Kontaktperson</small>
                        <span><?= esc($crmData['contact_person'] ?? '-') ?></span>
                    </div>
                </div>
            </div>
        </div>

        <!-- CRM Data Form -->
        <div class="card">
            <div class="card-header">
                <h5><i class="fas fa-briefcase me-2"></i>CRM Data</h5>
            </div>
            <div class="card-body">
                <form action="<?= base_url('/crm/update') ?>" method="POST">
                    <?= csrf_field() ?>
                    <input type="hidden" name="contact_id" value="<?= esc($contact['id']) ?>">

                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label class="form-label">Företagsnamn</label>
                            <input type="text" name="company_name" class="form-control" value="<?= esc($crmData['company_name'] ?? '') ?>">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Kontaktperson</label>
                            <input type="text" name="contact_person" class="form-control" value="<?= esc($crmData['contact_person'] ?? '') ?>">
                        </div>
                    </div>

                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label class="form-label">Kategori</label>
                            <select name="category" class="form-select">
                                <option value="">-- Välj kategori --</option>
                                <?php
                                    $defaultCats = ['Återkommande', 'Har köpt', 'Förfrågan', 'Obetald', 'Avböjd', 'Gratisprov', 'Leverantör/partner'];
                                    $allCats = array_unique(array_merge($defaultCats, $categories ?? []));
                                    sort($allCats);
                                    foreach ($allCats as $cat):
                                ?>
                                    <option value="<?= esc($cat) ?>" <?= ($crmData['category'] ?? '') === $cat ? 'selected' : '' ?>><?= esc($cat) ?></option>
                                <?php endforeach ?>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Senaste kontakt</label>
                            <input type="text" name="last_contact" class="form-control" value="<?= esc($crmData['last_contact'] ?? '') ?>" placeholder="T.ex. Feb 2026">
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Behov / Vad de vill</label>
                        <textarea name="needs" class="form-control" rows="2"><?= esc($crmData['needs'] ?? '') ?></textarea>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Noteringar</label>
                        <textarea name="notes" class="form-control" rows="3"><?= esc($crmData['notes'] ?? '') ?></textarea>
                    </div>

                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save me-1"></i> Spara CRM-data
                    </button>
                </form>
            </div>
        </div>

        <!-- Send Individual Email -->
        <div class="card">
            <div class="card-header">
                <h5><i class="fas fa-envelope me-2"></i>Skicka e-post</h5>
            </div>
            <div class="card-body">
                <div class="mb-2" style="font-size: 0.8125rem;">
                    <span class="text-muted">Från:</span>
                    <strong><?= esc($activeDomain['pretty_name'] ?? $activeDomain['name'] ?? '') ?></strong>
                    &lt;<?= esc($activeDomain['sender_email'] ?? ('noreply@' . ($activeDomain['name'] ?? ''))) ?>&gt;
                </div>
                <div class="mb-3" style="font-size: 0.8125rem;">
                    <span class="text-muted">Till:</span>
                    <strong><?= esc($contact['email']) ?></strong>
                </div>

                <div class="mb-3">
                    <label class="form-label">Ämne</label>
                    <input type="text" id="emailSubject" class="form-control" placeholder="Skriv ämnesrad...">
                </div>
                <div class="mb-3">
                    <label class="form-label">Meddelande (HTML)</label>
                    <textarea id="emailBody" class="form-control" rows="6" placeholder="Skriv ditt meddelande här..."></textarea>
                </div>

                <button type="button" id="sendEmailBtn" class="btn btn-primary">
                    <i class="fas fa-paper-plane me-1"></i> Skicka
                </button>
                <div id="sendResult" class="mt-2" style="display:none;"></div>
            </div>
        </div>
    </div>

    <!-- Right column: Email History -->
    <div class="col-lg-4">
        <div class="card">
            <div class="card-header">
                <h5><i class="fas fa-history me-2"></i>E-posthistorik</h5>
            </div>
            <div class="card-body p-0">
                <?php if (!empty($emailHistory)): ?>
                    <div style="max-height: 600px; overflow-y: auto;">
                        <?php foreach ($emailHistory as $email): ?>
                            <div class="px-3 py-2 border-bottom" style="font-size: 0.8125rem;">
                                <div class="d-flex justify-content-between align-items-start">
                                    <span class="fw-semibold text-truncate" style="max-width: 200px;" title="<?= esc($email['subject']) ?>">
                                        <?= esc($email['subject'] ?: '(Inget ämne)') ?>
                                    </span>
                                    <span class="badge-status <?= $email['type'] === 'campaign' ? 'badge-opened' : 'badge-clicked' ?>" style="font-size: 0.6rem; white-space: nowrap;">
                                        <?= $email['type'] === 'campaign' ? 'Kampanj' : 'Enskilt' ?>
                                    </span>
                                </div>
                                <div class="text-muted mt-1" style="font-size: 0.75rem;">
                                    <?php if (!empty($email['date'])): ?>
                                        <?= date('d M Y, H:i', strtotime($email['date'])) ?>
                                    <?php endif ?>
                                    <?php if (!empty($email['status'])): ?>
                                        &middot; <?= esc(ucfirst($email['status'])) ?>
                                    <?php endif ?>
                                </div>
                            </div>
                        <?php endforeach ?>
                    </div>
                <?php else: ?>
                    <div class="empty-state py-4">
                        <i class="fas fa-inbox d-block" style="font-size: 1.5rem;"></i>
                        <p class="mb-0">Ingen e-posthistorik</p>
                    </div>
                <?php endif ?>
            </div>
        </div>
    </div>
</div>

<script>
document.getElementById('sendEmailBtn').addEventListener('click', function() {
    var subject = document.getElementById('emailSubject').value.trim();
    var bodyHtml = document.getElementById('emailBody').value.trim();
    var resultDiv = document.getElementById('sendResult');
    var btn = this;

    if (!subject || !bodyHtml) {
        resultDiv.style.display = 'block';
        resultDiv.className = 'mt-2 alert alert-warning';
        resultDiv.textContent = 'Fyll i ämne och meddelande.';
        return;
    }

    if (!confirm('Skicka e-post till <?= esc($contact['email']) ?>?')) return;

    btn.disabled = true;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i> Skickar...';
    resultDiv.style.display = 'none';

    var formData = new FormData();
    formData.append('subject', subject);
    formData.append('body_html', bodyHtml);
    formData.append('csrf_test_name', document.querySelector('meta[name="csrf-token"]').content);

    fetch(BASE_URL + 'crm/send-email/<?= esc($contact['id']) ?>', {
        method: 'POST',
        body: formData
    })
    .then(function(response) { return response.json(); })
    .then(function(data) {
        if (data.csrf_token) {
            document.querySelector('meta[name="csrf-token"]').content = data.csrf_token;
        }
        resultDiv.style.display = 'block';
        if (data.success) {
            resultDiv.className = 'mt-2 alert alert-success';
            resultDiv.textContent = data.message;
            document.getElementById('emailSubject').value = '';
            document.getElementById('emailBody').value = '';
        } else {
            resultDiv.className = 'mt-2 alert alert-danger';
            resultDiv.textContent = data.message || 'Kunde inte skicka.';
        }
    })
    .catch(function(err) {
        resultDiv.style.display = 'block';
        resultDiv.className = 'mt-2 alert alert-danger';
        resultDiv.textContent = 'Fel: ' + err.message;
    })
    .finally(function() {
        btn.disabled = false;
        btn.innerHTML = '<i class="fas fa-paper-plane me-1"></i> Skicka';
    });
});
</script>
