<div class="widget">
    <a href="<?= base_url('domains/') ?>" class="btn text-white btn-flattered">
        <i class="fas fa-chevron-left"></i> Back
    </a>
    <h3 class="d-flex justify-content-between align-items-center">
        Add Domain
    </h3>

    <?php if (session()->has('errors')): ?>
        <div class="alert alert-danger">
            <ul>
                <?php foreach (session('errors') as $error): ?>
                    <li><?= esc($error) ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>

    <form action="<?= base_url('domains/create') ?>" method="post">
        <?= csrf_field() ?>
        <div class="mb-3">
            <label for="domain_name" class="form-label">Domain Name</label>
            <input type="text" class="form-control" id="domain_name" name="domain_name" required
                   value="<?= old('domain_name') ?>" placeholder="example.com">
        </div>

        <div class="mb-3">
            <label for="sender_email" class="form-label">Sender Email</label>
            <input type="email" class="form-control" id="sender_email" name="sender_email" required
                   value="<?= old('sender_email') ?>" placeholder="noreply@example.com">
        </div>

        <div class="mt-4">
            <button type="submit" class="btn btn-flattered text-white">Save</button>
            <a href="<?= base_url('domains') ?>" class="btn btn-secondary">Cancel</a>
        </div>
    </form>
</div>
