<?php
require_once __DIR__ . '/../includes/layout.php';
$me = require_login('admin');
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check(); $act = $_POST['action'] ?? ''; $id = (int)($_POST['id'] ?? 0);
    if ($act === 'add') {
        $n = trim($_POST['full_name'] ?? ''); $un = trim($_POST['username'] ?? ''); $em = trim($_POST['email'] ?? ''); $pw = $_POST['password'] ?? ''; $ro = $_POST['role'] === 'admin' ? 'admin' : 'user';
        $c = db()->prepare("SELECT COUNT(*) FROM users WHERE username=? OR email=?"); $c->execute([$un, $em]);
        if ($n === '' || $un === '' || !filter_var($em, FILTER_VALIDATE_EMAIL) || strlen($pw) < 6) flash('Fill all fields (valid email, password min 6).', 'err');
        elseif ($c->fetchColumn() > 0) flash('Username or email already exists.', 'err');
        else { db()->prepare("INSERT INTO users (full_name,username,email,password,role,status) VALUES (?,?,?,?,?, 'Active')")->execute([$n, $un, $em, password_hash($pw, PASSWORD_DEFAULT), $ro]); flash('User added.'); }
    } elseif ($id === $me['id']) { flash('You cannot change or delete your own account here.', 'err'); }
    elseif ($act === 'toggle') { db()->prepare("UPDATE users SET status=IF(status='Active','Inactive','Active') WHERE id=?")->execute([$id]); flash('Status updated.'); }
    elseif ($act === 'delete') { db()->prepare("DELETE FROM users WHERE id=?")->execute([$id]); flash('User deleted.'); }
    elseif ($act === 'reset') { $pw = $_POST['new_password'] ?? ''; if (strlen($pw) >= 6) { db()->prepare("UPDATE users SET password=? WHERE id=?")->execute([password_hash($pw, PASSWORD_DEFAULT), $id]); flash('Password reset.'); } else flash('Password must be at least 6 characters.', 'err'); }
    redirect('admin/users.php');
}
admin_start('Users', 'users');
$rows = db()->query("SELECT * FROM users ORDER BY role, full_name")->fetchAll();
?>
<div class="card"><div class="card-head"><div><div class="eyebrow">Access</div><h2>Add a user</h2></div></div>
<form method="post"><?= csrf_field() ?><input type="hidden" name="action" value="add">
  <div class="form-grid"><div><label>Full name</label><input name="full_name" required></div><div><label>Username</label><input name="username" required></div>
  <div><label>Email</label><input type="email" name="email" required></div><div><label>Password</label><input type="password" name="password" required minlength="6"></div>
  <div><label>Role</label><select name="role"><option value="user">User (community)</option><option value="admin">Admin</option></select></div></div>
  <button class="btn btn-primary" style="margin-top:18px">Add user</button></form></div>
<div class="card"><div class="card-head"><div><div class="eyebrow">Accounts</div><h2>All users</h2></div></div>
<div class="tablewrap"><table><thead><tr><th>Name</th><th>Username</th><th>Email</th><th>Role</th><th>Status</th><th></th></tr></thead><tbody>
<?php foreach ($rows as $r): ?>
  <tr><td><b><?= h($r['full_name']) ?></b></td><td><?= h($r['username']) ?></td><td><?= h($r['email']) ?></td>
    <td><?= $r['role'] === 'admin' ? '<span class="badge b-blue">Admin</span>' : '<span class="badge b-gray">User</span>' ?></td>
    <td><span class="badge <?= $r['status']==='Active' ? 'b-green' : 'b-red' ?>"><?= h($r['status']) ?></span></td>
    <td><?php if ((int)$r['id'] !== $me['id']): ?><div class="actions">
      <form method="post"><?= csrf_field() ?><input type="hidden" name="action" value="toggle"><input type="hidden" name="id" value="<?= $r['id'] ?>"><button class="btn btn-sm"><?= $r['status']==='Active' ? 'Deactivate' : 'Activate' ?></button></form>
      <form method="post" onsubmit="var p=prompt('New password (min 6 characters):');if(!p)return false;this.new_password.value=p;return true"><?= csrf_field() ?><input type="hidden" name="action" value="reset"><input type="hidden" name="id" value="<?= $r['id'] ?>"><input type="hidden" name="new_password"><button class="btn btn-sm">Reset password</button></form>
      <form method="post" onsubmit="return confirm('Delete this user?')"><?= csrf_field() ?><input type="hidden" name="action" value="delete"><input type="hidden" name="id" value="<?= $r['id'] ?>"><button class="btn btn-sm btn-danger">Delete</button></form></div>
    <?php else: ?><span class="muted small">You</span><?php endif; ?></td></tr>
<?php endforeach; ?></tbody></table></div></div>
<?php admin_end();
