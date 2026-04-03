<!-- Action bar -->
<div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
    <form method="GET" action="<?= base_url('/crm') ?>" class="d-flex gap-2 align-items-center flex-wrap">
        <select name="category" class="form-select form-select-sm" style="width: auto; min-width: 180px;" onchange="this.form.submit()">
            <option value="">Alla kategorier</option>
            <?php foreach ($categories as $cat): ?>
                <option value="<?= esc($cat) ?>" <?= ($activeCategory ?? '') === $cat ? 'selected' : '' ?>><?= esc($cat) ?></option>
            <?php endforeach ?>
        </select>
        <div class="input-group input-group-sm" style="width: 240px;">
            <input type="text" name="q" class="form-control" placeholder="Sök företag, kontakt..." value="<?= esc($searchQuery ?? '') ?>">
            <button class="btn btn-outline-secondary" type="submit"><i class="fas fa-search"></i></button>
        </div>
        <?php if (!empty($activeCategory) || !empty($searchQuery)): ?>
            <a href="<?= base_url('/crm') ?>" class="btn btn-sm btn-outline-secondary"><i class="fas fa-times me-1"></i>Rensa</a>
        <?php endif ?>
    </form>
    <div class="d-flex gap-2">
        <a href="<?= base_url('/crm/import') ?>" class="btn btn-sm btn-outline-primary">
            <i class="fas fa-file-import me-1"></i> Importera CSV
        </a>
    </div>
</div>

<!-- Stats -->
<?php if (!empty($crmContacts)): ?>
<div class="row g-3 mb-4">
    <?php
        $catCounts = [];
        foreach ($crmContacts as $c) {
            $cat = $c['category'] ?? 'Okategoriserad';
            $catCounts[$cat] = ($catCounts[$cat] ?? 0) + 1;
        }
    ?>
    <div class="col-auto">
        <span class="badge-status badge-delivered"><?= count($crmContacts) ?> kontakter</span>
    </div>
    <?php foreach ($catCounts as $cat => $count): ?>
        <div class="col-auto">
            <span class="badge-status <?= getCategoryBadgeClass($cat) ?>"><?= esc($cat) ?>: <?= $count ?></span>
        </div>
    <?php endforeach ?>
</div>
<?php endif ?>

<!-- CRM Table -->
<div class="card">
    <div class="card-header">
        <h5><i class="fas fa-briefcase me-2"></i>CRM Kontakter</h5>
        <span class="text-muted" style="font-size: 0.75rem;"><?= count($crmContacts ?? []) ?> st</span>
    </div>
    <div class="card-body p-0">
        <?php if (!empty($crmContacts)): ?>
            <div class="table-responsive">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Företag</th>
                            <th>Kontaktperson</th>
                            <th>E-post</th>
                            <th>Kategori</th>
                            <th>Behov</th>
                            <th>Senaste kontakt</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($crmContacts as $crm): ?>
                            <tr>
                                <td><span class="fw-semibold"><?= esc($crm['company_name'] ?? '') ?></span></td>
                                <td><?= esc($crm['contact_person'] ?? '') ?></td>
                                <td>
                                    <a href="mailto:<?= esc($crm['contact_email'] ?? '') ?>" class="text-decoration-none">
                                        <?= esc($crm['contact_email'] ?? '') ?>
                                    </a>
                                </td>
                                <td>
                                    <span class="badge-status <?= getCategoryBadgeClass($crm['category'] ?? '') ?>">
                                        <?= esc($crm['category'] ?? 'Okategoriserad') ?>
                                    </span>
                                </td>
                                <td>
                                    <span style="max-width: 200px; display: inline-block; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;" title="<?= esc($crm['needs'] ?? '') ?>">
                                        <?= esc($crm['needs'] ?? '') ?>
                                    </span>
                                </td>
                                <td><small class="text-muted"><?= esc($crm['last_contact'] ?? '-') ?></small></td>
                                <td>
                                    <a href="<?= base_url('/crm/profile/' . ($crm['contact_id'] ?? '')) ?>" class="btn btn-sm btn-outline-primary">
                                        <i class="fas fa-user"></i> Profil
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach ?>
                    </tbody>
                </table>
            </div>
        <?php else: ?>
            <div class="empty-state">
                <i class="fas fa-briefcase d-block"></i>
                <h3>Inga CRM-kontakter</h3>
                <p>Importera din CRM-data via CSV för att komma igång.</p>
                <a href="<?= base_url('/crm/import') ?>" class="btn btn-primary">
                    <i class="fas fa-file-import me-1"></i> Importera CSV
                </a>
            </div>
        <?php endif ?>
    </div>
</div>

<?php
function getCategoryBadgeClass(string $category): string {
    $cat = strtolower(trim($category));
    if (str_contains($cat, 'återkommande')) return 'badge-verified';
    if (str_contains($cat, 'har köpt') || str_contains($cat, 'köpt')) return 'badge-delivered';
    if (str_contains($cat, 'förfrågan')) return 'badge-pending';
    if (str_contains($cat, 'obetald')) return 'badge-failed';
    if (str_contains($cat, 'avböjd') || str_contains($cat, 'info')) return 'badge-bounced';
    if (str_contains($cat, 'leverantör') || str_contains($cat, 'partner')) return 'badge-opened';
    if (str_contains($cat, 'gratisprov')) return 'badge-clicked';
    return 'badge-pending';
}
?>
