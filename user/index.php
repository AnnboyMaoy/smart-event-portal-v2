<?php
require_once __DIR__ . '/../includes/layout.php';
$u = require_login('user');
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    if (($_POST['action'] ?? '') === 'cancel') {
        $id = (int)$_POST['registration_id'];
        $s = db()->prepare("SELECT * FROM registrations WHERE registration_id=? AND user_id=?"); $s->execute([$id, $u['id']]); $r = $s->fetch();
        if (!$r) flash('Registration not found.', 'err');
        elseif ($r['payment_status'] === 'Paid') flash('This registration is already paid. Please contact the organizer to cancel and request a refund.', 'err');
        elseif ($r['attendance_status'] === 'Attended' || in_array($r['registration_status'], ['Cancelled','Rejected'])) flash('This registration can no longer be cancelled.', 'err');
        else { db()->prepare("UPDATE registrations SET registration_status='Cancelled' WHERE registration_id=?")->execute([$id]); flash('Registration cancelled. Your seat was released.'); }
    }
    redirect('user/index.php');
}
user_start('My dashboard', 'dashboard');
$s = db()->prepare("SELECT r.*, e.event_name, e.event_date, e.event_time, e.location FROM registrations r JOIN events e ON e.event_id=r.event_id WHERE r.user_id=? ORDER BY r.registration_date DESC");
$s->execute([$u['id']]); $rows = $s->fetchAll();
$all = count($rows); $pend = $appr = $no = 0;
foreach ($rows as $r) { if ($r['registration_status']==='Pending') $pend++; elseif ($r['registration_status']==='Approved') $appr++; else $no++; }
?>
<section class="hero">
  <div><div class="eyebrow">Community account</div><h1>Welcome, <?= h($u['full_name']) ?>.</h1><p>Register for an event and follow your payment and approval progress here.</p></div>
  <a class="btn btn-primary" href="<?= url('user/events.php') ?>">Browse events +</a>
</section>
<div class="stats">
  <div class="stat"><div class="lbl">All registrations</div><div class="val"><?= $all ?></div></div>
  <div class="stat"><div class="lbl">Awaiting review</div><div class="val"><?= $pend ?></div></div>
  <div class="stat"><div class="lbl">Approved</div><div class="val"><?= $appr ?></div></div>
  <div class="stat"><div class="lbl">Not approved</div><div class="val"><?= $no ?></div></div>
</div>
<div class="card">
  <div class="eyebrow">Registration history</div><h2 style="margin-bottom:18px">Your tickets</h2>
  <?php if (!$rows): ?><p class="muted">You have not registered for any event yet. <a href="<?= url('user/events.php') ?>">Browse events</a></p><?php endif; ?>
  <?php foreach ($rows as $r): $rid = $r['registration_id']; ?>
  <div class="reg">
    <div class="reg-head"><div><h3><?= h($r['event_name']) ?></h3><div class="muted small">Registered <?= fdate($r['registration_date']) ?></div></div><?= badge($r['registration_status']) ?></div>
    <div class="kv">
      <div><small>Event date</small><?= fdate($r['event_date']) ?> at <?= ftime($r['event_time']) ?><br><span class="muted small"><?= h($r['location']) ?></span></div>
      <div><small>Ticket</small><?= h($r['ticket_type']) ?> &middot; <?= $r['amount'] > 0 ? money($r['amount']) : 'Free' ?><br><span class="code small"><?= h($r['ticket_code']) ?></span></div>
    </div>
    <div style="display:flex;gap:10px;align-items:center;flex-wrap:wrap">
      <span class="muted small">Payment:</span><?= badge($r['payment_status']) ?>
      <?php if ($r['attendance_status'] === 'Attended'): ?><span class="muted small">Attendance:</span><?= badge('Attended') ?><?php endif; ?>
    </div>
    <?php if ($r['registration_status'] === 'Pending' && $r['payment_status'] === 'Pending'): ?><div class="note">Payment submitted. The organizer is verifying it. Your ticket will be released once approved.</div>
    <?php elseif ($r['registration_status'] === 'Pending'): ?><div class="note">Complete your payment to secure your seat.</div>
    <?php elseif ($r['registration_status'] === 'Approved'): ?><div class="note">You are in! Show your QR ticket at the entrance.</div><?php endif; ?>
    <div class="actions" style="margin-top:12px">
      <?php if ($r['registration_status'] === 'Approved'): ?><a class="btn btn-primary btn-sm" href="<?= url('ticket.php?id=' . $rid) ?>">View QR ticket</a><?php endif; ?>
      <?php if ($r['payment_status'] === 'Unpaid' && in_array($r['registration_status'], ['Pending','Approved'])): ?><a class="btn btn-primary btn-sm" href="<?= url('user/pay.php?id=' . $rid) ?>">Pay now</a><?php endif; ?>
      <?php if (in_array($r['registration_status'], ['Pending','Approved']) && $r['payment_status'] !== 'Paid' && $r['attendance_status'] !== 'Attended'): ?>
        <form method="post" onsubmit="return confirm('Cancel this registration?')"><?= csrf_field() ?><input type="hidden" name="action" value="cancel"><input type="hidden" name="registration_id" value="<?= $rid ?>"><button class="btn btn-sm btn-danger">Cancel</button></form><?php endif; ?>
    </div>
  </div>
  <?php endforeach; ?>
</div>
<?php user_end();
