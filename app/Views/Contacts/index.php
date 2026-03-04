<!-- Action bar -->
<div class="d-flex justify-content-between align-items-center mb-4">
    <div></div>
    <button type="button" class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#addContactModal">
        <i class="fas fa-plus me-1"></i> Add Contact
    </button>
</div>

<div class="card">
    <div class="card-header">
        <h5><i class="fas fa-address-book me-2"></i>All Contacts</h5>
    </div>
    <div class="card-body p-0">
        <?php if (!empty($allContacts) && is_array($allContacts)): ?>
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
                        <?php foreach ($allContacts as $contact): ?>
                            <tr>
                                <td><?= esc($contact['email'] ?? '') ?></td>
                                <td><?= esc($contact['firstName'] ?? '') ?></td>
                                <td><?= esc($contact['lastName'] ?? '') ?></td>
                                <td>
                                    <?php if (!empty($contact['subscribed'])): ?>
                                        <span class="badge-status badge-subscribed">Subscribed</span>
                                    <?php else: ?>
                                        <span class="badge-status badge-unsubscribed">Unsubscribed</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <div class="action-buttons">
                                        <button type="button" class="btn btn-sm btn-outline-primary edit-contact-btn"
                                            data-id="<?= esc($contact['id'] ?? '') ?>"
                                            data-email="<?= esc($contact['email'] ?? '') ?>"
                                            data-firstname="<?= esc($contact['firstName'] ?? '') ?>"
                                            data-lastname="<?= esc($contact['lastName'] ?? '') ?>"
                                            data-bs-toggle="modal" data-bs-target="#editContactModal">
                                            <i class="fas fa-edit"></i>
                                        </button>
                                        <form action="<?= base_url('contacts/delete/' . ($contact['id'] ?? '')) ?>"
                                              method="POST" class="d-inline"
                                              onsubmit="return confirm('Are you sure you want to delete this contact?')">
                                            <?= csrf_field() ?>
                                            <button type="submit" class="btn btn-sm btn-outline-danger">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php else: ?>
            <div class="empty-state">
                <i class="fas fa-address-book d-block"></i>
                <h3>No Contacts Found</h3>
                <p>Add your first contact manually or import via CSV through an audience.</p>
                <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addContactModal">
                    <i class="fas fa-plus me-1"></i> Add Contact
                </button>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- Add Contact Modal -->
<div class="modal fade" id="addContactModal" tabindex="-1" aria-labelledby="addContactModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="addContactModalLabel">
                    <i class="fas fa-user-plus me-2"></i>Add Contact
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="<?= base_url('contacts/store') ?>" method="POST">
                <?= csrf_field() ?>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label" for="addEmail">Email <span class="text-danger">*</span></label>
                        <input class="form-control" type="email" name="email" id="addEmail" required
                               placeholder="contact@example.com">
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label" for="addFirstName">First Name</label>
                            <input class="form-control" type="text" name="first_name" id="addFirstName"
                                   placeholder="John">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label" for="addLastName">Last Name</label>
                            <input class="form-control" type="text" name="last_name" id="addLastName"
                                   placeholder="Doe">
                        </div>
                    </div>
                    <?php if (!empty($allAudiences)): ?>
                    <div class="mb-3">
                        <label class="form-label" for="addAudience">Add to Audience (optional)</label>
                        <select class="form-select" name="audience_id" id="addAudience">
                            <option value="">-- None --</option>
                            <?php foreach ($allAudiences as $audience): ?>
                                <option value="<?= esc($audience['id']) ?>">
                                    <?= esc($audience['name']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <?php endif; ?>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save me-1"></i> Save Contact
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Edit Contact Modal -->
<div class="modal fade" id="editContactModal" tabindex="-1" aria-labelledby="editContactModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="editContactModalLabel">
                    <i class="fas fa-edit me-2"></i>Edit Contact
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="<?= base_url('contacts/update') ?>" method="POST">
                <?= csrf_field() ?>
                <input type="hidden" name="id" id="editContactId">
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label" for="editEmail">Email <span class="text-danger">*</span></label>
                        <input class="form-control" type="email" name="email" id="editEmail" required>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label" for="editFirstName">First Name</label>
                            <input class="form-control" type="text" name="first_name" id="editFirstName">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label" for="editLastName">Last Name</label>
                            <input class="form-control" type="text" name="last_name" id="editLastName">
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save me-1"></i> Update Contact
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
document.querySelectorAll('.edit-contact-btn').forEach(function(btn) {
    btn.addEventListener('click', function() {
        document.getElementById('editContactId').value = this.dataset.id;
        document.getElementById('editEmail').value = this.dataset.email;
        document.getElementById('editFirstName').value = this.dataset.firstname;
        document.getElementById('editLastName').value = this.dataset.lastname;
    });
});
</script>
