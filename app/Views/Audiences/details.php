<div class="mb-3">
    <a href="<?= base_url('audiences/') ?>" class="btn btn-outline-primary btn-sm">
        <i class="fas fa-arrow-left me-1"></i> Back to Audiences
    </a>
</div>

<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h5><i class="fas fa-address-book me-2"></i>Contacts of "<?= esc($audience['name'] ?? '') ?>"</h5>
        <div class="d-flex gap-2">
            <button type="button" class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#addContactToAudienceModal">
                <i class="fas fa-user-plus me-1"></i> Add Contact
            </button>
            <a href="<?= base_url('audiences/edit/' . ($audience['id'] ?? '')) ?>" class="btn btn-sm btn-outline-primary">
                <i class="fas fa-edit me-1"></i> Edit Audience
            </a>
        </div>
    </div>
    <div class="card-body p-0">
        <?php if (!empty($audienceContacts) && is_array($audienceContacts)): ?>
            <div class="table-responsive">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Email</th>
                            <th>First Name</th>
                            <th>Last Name</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($audienceContacts as $contact): ?>
                            <tr>
                                <td><?= esc($contact['email'] ?? '') ?></td>
                                <td><?= esc($contact['first_name'] ?? $contact['firstName'] ?? '') ?></td>
                                <td><?= esc($contact['last_name'] ?? $contact['lastName'] ?? '') ?></td>
                                <td>
                                    <?php if (!empty($contact['subscribed'])): ?>
                                        <span class="badge-status badge-subscribed">Subscribed</span>
                                    <?php else: ?>
                                        <span class="badge-status badge-unsubscribed">Unsubscribed</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <form action="<?= base_url('audiences/removeContact') ?>" method="POST"
                                          class="d-inline"
                                          onsubmit="return confirm('Remove this contact from the audience?')">
                                        <?= csrf_field() ?>
                                        <input type="hidden" name="audience_id" value="<?= esc($audience['id'] ?? '') ?>">
                                        <input type="hidden" name="contact_id" value="<?= esc($contact['id'] ?? '') ?>">
                                        <button type="submit" class="btn btn-sm btn-outline-danger">
                                            <i class="fas fa-times me-1"></i> Remove
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php else: ?>
            <div class="empty-state">
                <i class="fas fa-address-book d-block"></i>
                <h3>No Contacts Yet</h3>
                <p>Add contacts to this audience manually or upload a CSV.</p>
                <div class="d-flex justify-content-center gap-2">
                    <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addContactToAudienceModal">
                        <i class="fas fa-user-plus me-1"></i> Add Contact
                    </button>
                    <a href="<?= base_url('audiences/edit/' . ($audience['id'] ?? '')) ?>" class="btn btn-outline-primary">
                        <i class="fas fa-upload me-1"></i> Upload CSV
                    </a>
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- Add Contact to Audience Modal -->
<div class="modal fade" id="addContactToAudienceModal" tabindex="-1" aria-labelledby="addContactToAudienceModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="addContactToAudienceModalLabel">
                    <i class="fas fa-user-plus me-2"></i>Add Contact to Audience
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <!-- Tabs -->
                <ul class="nav nav-tabs mb-3" role="tablist">
                    <li class="nav-item">
                        <button class="nav-link active" data-bs-toggle="tab" data-bs-target="#existingContactTab" type="button">
                            Select Existing
                        </button>
                    </li>
                    <li class="nav-item">
                        <button class="nav-link" data-bs-toggle="tab" data-bs-target="#newContactTab" type="button">
                            Create New
                        </button>
                    </li>
                </ul>
                <div class="tab-content">
                    <!-- Tab 1: Select existing contact -->
                    <div class="tab-pane fade show active" id="existingContactTab">
                        <form action="<?= base_url('audiences/addContact') ?>" method="POST">
                            <?= csrf_field() ?>
                            <input type="hidden" name="audience_id" value="<?= esc($audience['id'] ?? '') ?>">
                            <div class="mb-3">
                                <label class="form-label">Select Contact</label>
                                <select class="form-select" name="contact_id" required>
                                    <option value="">-- Choose a contact --</option>
                                    <?php if (!empty($allContacts)): ?>
                                        <?php foreach ($allContacts as $c): ?>
                                            <option value="<?= esc($c['id']) ?>">
                                                <?= esc($c['email']) ?>
                                                <?php if (!empty($c['firstName'])): ?>
                                                    (<?= esc($c['firstName'] . ' ' . $c['lastName']) ?>)
                                                <?php endif; ?>
                                            </option>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </select>
                            </div>
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-plus me-1"></i> Add to Audience
                            </button>
                        </form>
                    </div>
                    <!-- Tab 2: Create new contact and add to audience -->
                    <div class="tab-pane fade" id="newContactTab">
                        <form action="<?= base_url('contacts/store') ?>" method="POST">
                            <?= csrf_field() ?>
                            <input type="hidden" name="audience_id" value="<?= esc($audience['id'] ?? '') ?>">
                            <input type="hidden" name="redirect_to" value="<?= base_url('audiences/details/' . ($audience['id'] ?? '')) ?>">
                            <div class="mb-3">
                                <label class="form-label">Email <span class="text-danger">*</span></label>
                                <input class="form-control" type="email" name="email" required placeholder="contact@example.com">
                            </div>
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">First Name</label>
                                    <input class="form-control" type="text" name="first_name" placeholder="John">
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Last Name</label>
                                    <input class="form-control" type="text" name="last_name" placeholder="Doe">
                                </div>
                            </div>
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save me-1"></i> Create & Add
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
