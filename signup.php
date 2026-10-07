<?php
require_once __DIR__ . '/includes/layout.php';
if ($u = current_user()) redirect(home_for($u));
$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $name = trim($_POST['full_name'] ?? ''); $user = trim($_POST['username'] ?? '');
    $mail = trim($_POST['email'] ?? '');     $pass = $_POST['password'] ?? '';
    if ($name === '' || $user === '' || !filter_var($mail, FILTER_VALIDATE_EMAIL)) $error = 'Please fill in all fields with a valid email.';
    elseif (strlen($pass) < 6) $error = 'Password must be at least 6 characters.';
    else {
        $c = db()->prepare("SELECT COUNT(*) FROM users WHERE username=? OR email=?");
        $c->execute([$user, $mail]);
        if ($c->fetchColumn() > 0) $error = 'That username or email is already taken.';
        else {
            db()->prepare("INSERT INTO users (full_name,username,email,password,role,status) VALUES (?,?,?,?, 'user','Active')")
                ->execute([$name, $user, $mail, password_hash($pass, PASSWORD_DEFAULT)]);
            flash('Account created. You can log in now.');
            redirect('login.php');
        }
    }
}
head('Create account');
?>
<div class="auth">
  <section class="art">
    <div class="brand"><div class="logo">SE</div><div><b>Smart Event Portal</b><span>Public events portal</span></div></div>
    <div><h1>Join your community's events.</h1><p>Register in a minute, pay online, and get a QR ticket you can show at the door.</p></div>
    <a href="<?= url('index.php') ?>" class="muted">&larr; Back to public events</a>
  </section>
  <section class="formside">
    <form method="post">
      <div class="eyebrow">Community account</div>
      <h2 style="margin-bottom:22px">Create your account</h2>
      <?php if ($error): ?><div class="flash err"><?= h($error) ?></div><?php endif; ?>
      <?= csrf_field() ?>
      <div class="row"><label>Full name</label><input name="full_name" required value="<?= h($_POST['full_name'] ?? '') ?>"></div>
      <div class="row"><label>Username</label><input name="username" required value="<?= h($_POST['username'] ?? '') ?>"></div>
      <div class="row"><label>Email</label><input type="email" name="email" required value="<?= h($_POST['email'] ?? '') ?>"></div>
      <div class="row"><label>Password (min 6)</label><input type="password" name="password" required></div>
      <button class="btn btn-primary" style="width:100%">Create account</button>
      <p class="muted small" style="text-align:center;margin-top:16px">Already registered? <a href="<?= url('login.php') ?>">Log in</a></p>
    </form>
  </section>
</div></body></html>
