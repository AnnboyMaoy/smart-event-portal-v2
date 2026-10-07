<?php
require_once __DIR__ . '/includes/layout.php';
$u = require_login();
$id = (int)($_GET['id'] ?? 0);
$s = db()->prepare("SELECT r.*, e.event_name, e.event_date, e.event_time, e.location FROM registrations r JOIN events e ON e.event_id=r.event_id WHERE r.registration_id=?");
$s->execute([$id]); $r = $s->fetch();
if (!$r || ($u['role'] !== 'admin' && (int)$r['user_id'] !== $u['id'])) { flash('Ticket not found.', 'err'); redirect(home_for($u)); }
if ($u['role'] === 'admin') { admin_start('Ticket', 'regs'); } else { user_start('Ticket', 'dashboard'); }
?>
<?php if ($r['registration_status'] !== 'Approved'): ?>
  <div class="card"><h2>Ticket not available yet</h2><p class="muted">Status: <?= badge($r['registration_status']) ?> Payment: <?= badge($r['payment_status']) ?>. The QR ticket is released once your registration is approved.</p></div>
<?php else: ?>
  <div class="ticket">
    <div class="l"><div class="eyebrow" style="color:#8fd8ff"><?= h($r['ticket_type']) ?> ticket</div><h1 style="font-size:32px"><?= h($r['event_name']) ?></h1>
      <p style="color:#cfe3f6;margin:14px 0 0"><?= fdate($r['event_date']) ?> at <?= ftime($r['event_time']) ?><br><?= h($r['location']) ?></p>
      <div style="margin-top:26px"><div class="muted small">Attendee</div><b style="font-size:20px"><?= h($r['attendee_name']) ?></b></div>
      <div style="margin-top:14px;display:flex;gap:8px"><?= badge($r['payment_status']) ?><?= badge($r['attendance_status']) ?></div></div>
    <div class="r"><div class="qrbox"><div id="qr"></div></div><div class="code"><?= h($r['ticket_code']) ?></div><div class="muted small">Show this QR at the entrance</div></div>
  </div>
  <p style="text-align:center;margin-top:20px" class="no-print"><button class="btn btn-primary" onclick="window.print()">Print / Save as PDF</button></p>
  <script src="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js"></script>
  <script>new QRCode(document.getElementById('qr'),{text:<?= json_encode($r['ticket_code']) ?>,width:176,height:176,correctLevel:QRCode.CorrectLevel.M});</script>
<?php endif;
if ($u['role'] === 'admin') admin_end(); else user_end();
