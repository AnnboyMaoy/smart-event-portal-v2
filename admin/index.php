<?php
require_once __DIR__ . '/../includes/layout.php';
$u = admin_start('Admin Dashboard', 'dashboard');
$pdo = db();
$totalEvents = (int)$pdo->query("SELECT COUNT(*) FROM events")->fetchColumn();
$totalRegs   = (int)$pdo->query("SELECT COUNT(*) FROM registrations WHERE registration_status<>'Cancelled'")->fetchColumn();
$attended    = (int)$pdo->query("SELECT COUNT(*) FROM registrations WHERE attendance_status='Attended'")->fetchColumn();
$revenue     = (float)$pdo->query("SELECT COALESCE(SUM(amount),0) FROM payments WHERE status='Verified'")->fetchColumn();
$pendingPay  = (int)$pdo->query("SELECT COUNT(*) FROM payments WHERE status='Pending'")->fetchColumn();
$pendingReg  = (int)$pdo->query("SELECT COUNT(*) FROM registrations WHERE registration_status='Pending'")->fetchColumn();

$monthly = array_fill(1, 12, 0);
foreach ($pdo->query("SELECT MONTH(event_date) m, COUNT(*) c FROM events WHERE YEAR(event_date)=YEAR(CURDATE()) GROUP BY m") as $r) $monthly[(int)$r['m']] = (int)$r['c'];
$maxM = max(1, max($monthly));

$st = ['Upcoming'=>0,'Ongoing'=>0,'Completed'=>0,'Cancelled'=>0];
foreach ($pdo->query("SELECT status, COUNT(*) c FROM events GROUP BY status") as $r) $st[$r['status']] = (int)$r['c'];
$colors = ['Upcoming'=>'#5aa9ff','Ongoing'=>'#3fd0c0','Completed'=>'#f6c344','Cancelled'=>'#ff6b7a'];
$sum = max(1, array_sum($st)); $acc = 0; $stops = [];
foreach ($st as $k => $v) { $from = $acc; $acc += $v / $sum * 100; $stops[] = "{$colors[$k]} {$from}% {$acc}%"; }
$grad = array_sum($st) ? implode(',', $stops) : '#1d2c42 0 100%';

$upcoming = $pdo->query("SELECT * FROM events WHERE event_date >= CURDATE() AND status IN ('Upcoming','Ongoing') ORDER BY event_date, event_time LIMIT 5")->fetchAll();
$months = ['J','F','M','A','M','J','J','A','S','O','N','D'];
?>
<section class="hero">
  <div><div class="eyebrow">Premium control center</div><h1>Welcome back, <?= h($u['username']) ?>.</h1>
  <p>Track registrations, verify payments and check people in, while your event schedule keeps moving.</p></div>
  <div class="hero-actions"><a class="btn btn-primary" href="<?= url('admin/event_form.php') ?>">+ New event</a>
    <a class="btn" href="<?= url('admin/attendance.php') ?>">Scan tickets</a></div>
</section>

<div class="stats">
  <div class="stat"><div class="lbl">Total events</div><div class="val"><?= $totalEvents ?></div><a class="more" href="<?= url('admin/events.php') ?>">View events &rarr;</a></div>
  <div class="stat"><div class="lbl">Registrations</div><div class="val"><?= $totalRegs ?></div><a class="more" href="<?= url('admin/registrations.php') ?>"><?= $pendingReg ?> awaiting review &rarr;</a></div>
  <div class="stat"><div class="lbl">Checked in</div><div class="val"><?= $attended ?></div><a class="more" href="<?= url('admin/attendance.php') ?>">Open scanner &rarr;</a></div>
  <div class="stat"><div class="lbl">Revenue (verified)</div><div class="val" style="font-size:28px;padding:5px 0"><?= money($revenue) ?></div><a class="more" href="<?= url('admin/payments.php') ?>"><?= $pendingPay ?> to verify &rarr;</a></div>
</div>

<div class="grid-2">
  <div class="card">
    <div class="card-head"><div><div class="eyebrow">Insights</div><h2>Monthly event volume</h2></div><span class="pill"><?= date('Y') ?></span></div>
    <div class="chart">
      <?php foreach ($monthly as $m => $c): ?>
        <div class="col"><b><?= $c ?: '' ?></b><i class="<?= $c ? '' : 'zero' ?>" style="height:<?= $c ? max(8, $c / $maxM * 130) : 6 ?>px"></i><span><?= $months[$m-1] ?></span></div>
      <?php endforeach; ?>
    </div>
  </div>
  <div class="card">
    <div class="card-head"><div><div class="eyebrow">Portfolio</div><h2>Operational snapshot</h2></div></div>
    <div class="donut-wrap"><div class="donut" style="background:conic-gradient(<?= $grad ?>)"></div>
      <div class="legend"><?php foreach ($st as $k => $v): ?><div><i style="background:<?= $colors[$k] ?>"></i><?= $k ?><b><?= $v ?></b></div><?php endforeach; ?></div></div>
  </div>
</div>

<div class="card">
  <div class="card-head"><div><div class="eyebrow">Schedule</div><h2>Upcoming events</h2></div><a class="btn btn-sm" href="<?= url('admin/events.php') ?>">All events</a></div>
  <div class="tablewrap"><table><thead><tr><th>Event</th><th>Date</th><th>Location</th><th>Capacity</th><th>Status</th></tr></thead><tbody>
  <?php foreach ($upcoming as $e): $t = seats_taken((int)$e['event_id']); ?>
    <tr><td><b><?= h($e['event_name']) ?></b></td><td><?= fdate($e['event_date']) ?> &middot; <?= ftime($e['event_time']) ?></td><td><?= h($e['location']) ?></td>
      <td style="min-width:150px"><?= $t ?> / <?= (int)$e['capacity'] ?><?= bar($t, (int)$e['capacity']) ?></td><td><?= badge($e['status']) ?></td></tr>
  <?php endforeach; if (!$upcoming): ?><tr><td colspan="5" class="muted">No upcoming events.</td></tr><?php endif; ?>
  </tbody></table></div>
</div>
<?php admin_end();
