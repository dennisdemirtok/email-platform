<div class="mb-3">
    <a href="<?= base_url('/crm') ?>" class="btn btn-sm btn-outline-secondary">
        <i class="fas fa-arrow-left me-1"></i> Tillbaka till CRM
    </a>
</div>

<div class="row justify-content-center">
    <div class="col-lg-7">
        <div class="card">
            <div class="card-header">
                <h5><i class="fas fa-file-import me-2"></i>Importera CRM-data</h5>
            </div>
            <div class="card-body">
                <div class="alert alert-info mb-4" style="font-size: 0.8125rem;">
                    <strong>Instruktioner:</strong>
                    <ol class="mb-0 mt-2">
                        <li>Spara din Excel-fil som CSV (UTF-8, semikolon-separerad)</li>
                        <li>Filen ska ha kolumner: <code>Namn/Företag</code>, <code>Kontaktperson</code>, <code>E-post</code>, <code>Kategori</code>, <code>Vad de vill/behöver</code>, <code>Senaste kontakt</code>, <code>Noteringar</code></li>
                        <li>Rader utan e-postadress hoppas över automatiskt</li>
                        <li>Befintliga kontakter uppdateras, nya skapas</li>
                    </ol>
                </div>

                <form action="<?= base_url('/crm/import') ?>" method="POST" enctype="multipart/form-data">
                    <?= csrf_field() ?>

                    <div class="mb-4">
                        <label class="form-label">Välj CSV-fil</label>
                        <input type="file" name="csv_file" class="form-control" accept=".csv,.txt" required>
                        <div class="form-text">Max filstorlek: 2MB. Format: CSV med semikolon (;) som separator.</div>
                    </div>

                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-upload me-1"></i> Starta import
                    </button>
                </form>
            </div>
        </div>

        <div class="card">
            <div class="card-header">
                <h5><i class="fas fa-table me-2"></i>Kolumnmappning</h5>
            </div>
            <div class="card-body p-0">
                <table class="table mb-0">
                    <thead>
                        <tr>
                            <th>CSV-kolumn</th>
                            <th>CRM-fält</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr><td><code>Namn/Företag</code></td><td>Företagsnamn</td></tr>
                        <tr><td><code>Kontaktperson</code></td><td>Kontaktperson</td></tr>
                        <tr><td><code>E-post</code></td><td>E-postadress (obligatorisk)</td></tr>
                        <tr><td><code>Kategori</code></td><td>Segment/Kategori</td></tr>
                        <tr><td><code>Vad de vill/behöver</code></td><td>Behov</td></tr>
                        <tr><td><code>Senaste kontakt</code></td><td>Senaste kontakt</td></tr>
                        <tr><td><code>Noteringar</code></td><td>Noteringar</td></tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
