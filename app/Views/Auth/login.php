<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Email Platform</title>
    <link rel="icon" href="<?= base_url('favicon.png') ?>" />
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link rel="stylesheet" href="<?= base_url('css/app.css') ?>">
</head>
<body class="login-page">
    <div class="login-card">
        <div class="login-brand">
            <h1>Email Platform</h1>
            <p>Sign in to your account</p>
        </div>
        <?php if (session()->getFlashdata('error')): ?>
            <div class="alert alert-danger mb-3" role="alert">
                <i class="fas fa-exclamation-circle me-1"></i>
                <?= esc(session()->getFlashdata('error')) ?>
            </div>
        <?php endif; ?>
        <?php if (session()->getFlashdata('success')): ?>
            <div class="alert alert-success mb-3" role="alert">
                <i class="fas fa-check-circle me-1"></i>
                <?= esc(session()->getFlashdata('success')) ?>
            </div>
        <?php endif; ?>
        <form action="<?= base_url('/login') ?>" method="post">
            <?= csrf_field() ?>
            <div class="mb-3">
                <label for="username" class="form-label">Username</label>
                <div class="input-group">
                    <span class="input-group-text"><i class="fas fa-user"></i></span>
                    <input type="text" name="username" value="<?= old('username') ?>" class="form-control" placeholder="Enter your username" required autofocus>
                </div>
            </div>
            <div class="mb-4">
                <label for="password" class="form-label">Password</label>
                <div class="input-group">
                    <span class="input-group-text"><i class="fas fa-lock"></i></span>
                    <input type="password" name="password" class="form-control" placeholder="Enter your password" required>
                </div>
            </div>
            <button type="submit" class="btn btn-primary w-100 py-2">
                Sign In
                <i class="fas fa-arrow-right ms-1"></i>
            </button>
        </form>
    </div>
</body>
</html>
