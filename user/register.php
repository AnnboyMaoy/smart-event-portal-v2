<?php
require_once __DIR__ . '/../includes/layout.php';
$u = require_login('user');
$eid = (int)($_GET['event_id'] ?? $_POST['event_id'] ?? 0);
$s = db()->prepare("SELECT * FROM events WHERE event_id=? AND status IN ('Upcoming','Ongoing')"); $s->execute([$eid]); $ev = $s->fetch();
if (!$ev) { flash('Event not available.', 'err'); redirect('user/events.php'); }
$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $name = trim($_POST['attendee_name'] ?? ''); $mail = trim($_POST['email'] ?? ''); $phone = trim($_POST['phone'] ?? '');
    $type = ($_POST['ticket_type'] ?? '') === 'VIP' ? 'VIP' : 'General';
    if ($type === 'VIP' && $ev['vip_price'] <= 0) $type = 'General';
    if ($name === '' || !filter_var($mail, FILTER_VALIDATE_EMAIL)) $error = 'Please enter your name and a valid email.';
    else {
        $pdo = db(); $pdo->beginTransaction();
        $pdo->prepare("SELECT event_id FROM events WHERE event_id=? FOR UPDATE")->execute([$eid]);   // lock row so capacity cannot be oversold
        $dup = $pdo->prepare("SELECT COUNT(*) FROM registrations WHERE event_id=? AND user_id=? AND registration_status IN ('Pending','Approved')");
        $dup->execute([$eid, $u['id']]);
        if ($dup->fetchColumn() > 0) { $pdo->rollBack(); flash('You are already registered for this event.', 'err'); redirect('user/index.php'); }
        if (seats_taken($eid) >= $ev['capacity']) { $pdo->rollBack(); flash('Sorry, this event just became fully booked.', 'err'); redirect('user/events.php'); }
        $amount = $type === 'VIP' ? (float)$ev['vip_price'] : (float)$ev['price'];
        $free = $amount <= 0;
        $pdo->prepare("INSERT INTO registrations (event_id,user_id,attendee_name,email,phone,ticket_type,ticket_code,amount,payment_status,registration_status,attendance_status)
                       VALUES (?,?,?,?,?,?,?,?,?,?, 'Not Attended')")
            ->execute([$eid, $u['id'], $name, $mail, $phone, $type, ticket_code(), $amount, $free ? 'Free' : 'Unpaid', $free ? 'Approved' : 'Pending']);
        $rid = (int)$pdo->lastInsertId(); $pdo->commit();
        if ($free) { flash('You are registered! Your QR ticket is ready.'); redirect('ticket.php?id=' . $rid); }
        flash('Registered. Complete your payment to secure your seat.'); redirect('user/pay.php?id=' . $rid);
    }
}
user_start('Register', 'events');
if ($error) echo '<div class="flash err">' . h($error) . '</div>';
$left = max(0, $ev['capacity'] - seats_taken($eid));
?>
<div class="card">
  <div class="eyebrow">Event registration</div><h2><?= h($ev['event_name']) ?></h2>
  <p class="muted"><?= fdate($ev['event_date']) ?> at <?= ftime($ev['event_time']) ?> &middot; <?= h($ev['location']) ?> &middot; <?= $left ?> seats left</p>
  <form method="post" style="margin-top:20px"><?= csrf_field() ?><input type="hidden" name="event_id" value="<?= $eid ?>">
    <div class="form-grid">
      <div><label>Attendee name</label><input name="attendee_name" required value="<?= h($_POST['attendee_name'] ?? $u['full_name']) ?>"></div>
      <div><label>Email</label><input type="email" name="email" required value="<?= h($_POST['email'] ?? $u['email']) ?>"></div>
      <div><label>Phone</label><input name="phone" value="<?= h($_POST['phone'] ?? '') ?>"></div>
      <div><label>Ticket type</label><select name="ticket_type">
        <option value="General">General &ndash; <?= $ev['price'] > 0 ? money($ev['price']) : 'Free' ?></option>
        <?php if ($ev['vip_price'] > 0): ?><option value="VIP">VIP &ndash; <?= money($ev['vip_price']) ?></option><?php endif; ?></select></div>
    </div>
    <div style="display:flex;gap:10px;margin-top:22px"><button class="btn btn-primary">Confirm registration</button><a class="btn" href="<?= url('user/events.php') ?>">Back</a></div>
  </form>
</div>
<?php user_end();
