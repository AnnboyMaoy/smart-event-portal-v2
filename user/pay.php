<?php
require_once __DIR__ . '/../includes/layout.php';
$u = require_login('user');
$id = (int)($_GET['id'] ?? $_POST['id'] ?? 0);
$s = db()->prepare("SELECT r.*, e.event_name FROM registrations r JOIN events e ON e.event_id=r.event_id WHERE r.registration_id=? AND r.user_id=?");
$s->execute([$id, $u['id']]); $r = $s->fetch();
if (!$r || $r['payment_status'] !== 'Unpaid' || !in_array($r['registration_status'], ['Pending','Approved'])) { flash('Nothing to pay for this registration.', 'err'); redirect('user/index.php'); }
$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $m = $_POST['method'] ?? ''; $ref = trim($_POST['reference_no'] ?? '');
    if (!in_array($m, ['GCash','Maya','Bank Transfer','Cash at venue'], true)) $error = 'Choose a payment method.';
    elseif ($m !== 'Cash at venue' && strlen($ref) < 4) $error = 'Enter the payment reference number.';
    else {
        db()->prepare("INSERT INTO payments (registration_id, amount, method, reference_no, status) VALUES (?,?,?,?, 'Pending')")
            ->execute([$id, $r['amount'], $m, $m === 'Cash at venue' ? 'To pay at venue' : $ref]);
        db()->prepare("UPDATE registrations SET payment_status='Pending' WHERE registration_id=?")->execute([$id]);
        flash('Payment submitted. The organizer will verify it shortly.'); redirect('user/index.php');
    }
}
user_start('Payment', 'dashboard');
if ($error) echo '<div class="flash err">' . h($error) . '</div>';
?>
<div class="card" style="max-width:640px">
  <div class="eyebrow">Payment</div><h2><?= h($r['event_name']) ?></h2>
  <p class="muted"><?= h($r['ticket_type']) ?> ticket &middot; <span class="code"><?= h($r['ticket_code']) ?></span></p>
  <div class="kv" style="grid-template-columns:1fr"><div><small>Amount to pay</small><span class="price" style="font-size:28px"><?= money($r['amount']) ?></span></div></div>
  <div class="note"><?= h(PAY_INSTRUCTIONS) ?></div>
  <form method="post"><?= csrf_field() ?><input type="hidden" name="id" value="<?= $id ?>">
    <div class="form-grid">
      <div><label>Payment method</label><select name="method"><option>GCash</option><option>Maya</option><option>Bank Transfer</option><option>Cash at venue</option></select></div>
      <div><label>Reference number</label><input name="reference_no" placeholder="Not needed for cash"></div>
    </div>
    <div style="display:flex;gap:10px;margin-top:22px"><button class="btn btn-primary">Submit payment</button><a class="btn" href="<?= url('user/index.php') ?>">Later</a></div>
  </form>
</div>
<?php user_end();
