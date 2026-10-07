<?php
require_once __DIR__ . '/includes/layout.php';
if ($u = current_user()) redirect(home_for($u));

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $login = trim($_POST['login'] ?? '');
    $pass  = $_POST['password'] ?? '';
    try {
        $s = db()->prepare("SELECT * FROM users WHERE (username=? OR email=?) LIMIT 1");
        $s->execute([$login, $login]);
        $row = $s->fetch();
        if ($row && $row['status'] === 'Active' && password_verify($pass, $row['password'])) {
            session_regenerate_id(true);
            $_SESSION['user'] = ['id'=>(int)$row['id'], 'full_name'=>$row['full_name'], 'username'=>$row['username'],
                                 'email'=>$row['email'], 'role'=>$row['role']];
            redirect(home_for($_SESSION['user']));
        }
        $error = ($row && $row['status'] !== 'Active') ? 'This account is inactive. Contact the administrator.' : 'Invalid username or password.';
    } catch (Throwable $e) {
        $error = 'Database error: ' . $e->getMessage();
    }
}
head('Login');
?>
<div class="auth">
  <section class="art">
    <div class="brand"><div class="logo">SE</div><div><b>Smart Event Portal</b><span>Public events portal</span></div></div>
    <div><h1>Plan it. Register it. Check them in.</h1>
    <p>Event creation, registration, tickets with QR codes, capacity control, payments and live reports, all in one place.</p></div>
    <a href="<?= url('index.php') ?>" class="muted">&larr; Back to public events</a>
  </section>
  <section class="formside">
    <form method="post" autocomplete="on">
      <div class="eyebrow">Welcome back</div>
      <h2 style="margin-bottom:22px">Log in to your account</h2>
      <?php if ($error): ?><div class="flash err"><?= h($error) ?></div><?php endif; flash_show(); ?>
      <?= csrf_field() ?>
      <div class="row"><label for="login">Username or email</label><input id="login" name="login" required autofocus value="<?= h($_POST['login'] ?? '') ?>"></div>
      <div class="row"><label for="password">Password</label><input id="password" type="password" name="password" required></div>
      <button class="btn btn-primary" style="width:100%">Log in</button>
      <p class="muted small" style="text-align:center;margin-top:16px">No account yet? <a href="<?= url('signup.php') ?>">Create one</a></p>

    </form>
  </section>
</div></body></html>
