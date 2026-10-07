<?php
require_once __DIR__ . '/../includes/layout.php';
require_login('admin');
$eid = (int)($_GET['event_id'] ?? 0);
$where = $eid ? "WHERE e.event_id = " . $eid : "";
$rows = db()->query("SELECT e.event_id, e.event_name, e.event_date, e.capacity, e.status,
    SUM(r.registration_status IN ('Pending','Approved')) AS registered,
    SUM(r.registration_status='Approved') AS approved,
    SUM(r.attendance_status='Attended') AS attended,
    COALESCE((SELECT SUM(p.amount) FROM payments p JOIN registrations r2 ON r2.registration_id=p.registration_id WHERE r2.event_id=e.event_id AND p.status='Verified'),0) AS revenue
  FROM events e LEFT JOIN registrations r ON r.event_id=e.event_id $where GROUP BY e.event_id ORDER BY e.event_date DESC")->fetchAll();

if (isset($_GET['export'])) {
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="event-report-' . date('Ymd') . '.csv"');
    $o = fopen('php://output', 'w'); fwrite($o, "\xEF\xBB\xBF");
    fputcsv($o, ['Event','Date','Status','Capacity','Registered','Approved','Attended','No-show','Fill %','Attendance %','Revenue (PHP)']);
    foreach ($rows as $r) {
        $noshow = max(0, $r['approved'] - $r['attended']);
        fputcsv($o, [$r['event_name'], $r['event_date'], $r['status'], $r['capacity'], $r['registered'], $r['approved'], $r['attended'], $noshow,
            $r['capacity'] ? round($r['registered'] / $r['capacity'] * 100) : 0, $r['approved'] ? round($r['attended'] / $r['approved'] * 100) : 0, $r['revenue']]);
    }
    exit;
}
admin_start('Reports', 'reports');
$events = db()->query("SELECT event_id, event_name FROM events ORDER BY event_date DESC")->fetchAll();
$T = ['registered'=>0,'approved'=>0,'attended'=>0,'revenue'=>0];
foreach ($rows as $r) foreach ($T as $k => $_) $T[$k] += $r[$k];
$methods = db()->query("SELECT method, COUNT(*) c, SUM(amount) a FROM payments WHERE status='Verified' GROUP BY method")->fetchAll();
$types = db()->query("SELECT ticket_type, COUNT(*) c FROM registrations WHERE registration_status IN ('Pending','Approved') GROUP BY ticket_type")->fetchAll();
?>
<form class="toolbar no-print" method="get">
  <select name="event_id"><option value="0">All events</option><?php foreach ($events as $e): ?><option value="<?= $e['event_id'] ?>" <?= $eid==$e['event_id']?'selected':'' ?>><?= h($e['event_name']) ?></option><?php endforeach; ?></select>
  <button class="btn">Apply</button>
  <a class="btn btn-primary" href="?export=1&event_id=<?= $eid ?>">Download CSV (Excel)</a>
  <button class="btn" type="button" onclick="window.print()">Print / Save as PDF</button>
</form>
<div class="stats">
  <div class="stat"><div class="lbl">Registered</div><div class="val"><?= (int)$T['registered'] ?></div></div>
  <div class="stat"><div class="lbl">Approved tickets</div><div class="val"><?= (int)$T['approved'] ?></div></div>
  <div class="stat"><div class="lbl">Attended</div><div class="val"><?= (int)$T['attended'] ?></div><span class="muted small"><?= $T['approved'] ? round($T['attended'] / $T['approved'] * 100) : 0 ?>% of approved</span></div>
  <div class="stat"><div class="lbl">Revenue</div><div class="val" style="font-size:28px;padding:5px 0"><?= money($T['revenue']) ?></div></div>
</div>
<div class="card"><div class="card-head"><div><div class="eyebrow">Per event</div><h2>Capacity, attendance &amp; revenue</h2></div></div>
  <div class="tablewrap"><table><thead><tr><th>Event</th><th>Date</th><th>Status</th><th>Capacity fill</th><th>Approved</th><th>Attended</th><th>No-show</th><th>Revenue</th></tr></thead><tbody>
  <?php foreach ($rows as $r): ?>
    <tr><td><b><?= h($r['event_name']) ?></b></td><td><?= fdate($r['event_date']) ?></td><td><?= badge($r['status']) ?></td>
      <td style="min-width:150px"><?= (int)$r['registered'] ?> / <?= (int)$r['capacity'] ?><?= bar((int)$r['registered'], (int)$r['capacity']) ?></td>
      <td><?= (int)$r['approved'] ?></td><td><?= (int)$r['attended'] ?></td><td><?= max(0, $r['approved'] - $r['attended']) ?></td><td><b><?= money($r['revenue']) ?></b></td></tr>
  <?php endforeach; if (!$rows): ?><tr><td colspan="8" class="muted">No data.</td></tr><?php endif; ?></tbody></table></div></div>
<div class="grid-2" style="grid-template-columns:1fr 1fr">
  <div class="card"><div class="card-head"><div><div class="eyebrow">Payments</div><h2>Revenue by method</h2></div></div>
    <?php foreach ($methods as $m): ?><div style="display:flex;justify-content:space-between;padding:8px 0;border-bottom:1px solid #15233a"><span><?= h($m['method']) ?> <span class="muted small">(<?= $m['c'] ?>)</span></span><b><?= money($m['a']) ?></b></div><?php endforeach; if (!$methods) echo '<p class="muted">No verified payments yet.</p>'; ?></div>
  <div class="card"><div class="card-head"><div><div class="eyebrow">Tickets</div><h2>Ticket type mix</h2></div></div>
    <?php foreach ($types as $t): ?><div style="display:flex;justify-content:space-between;padding:8px 0;border-bottom:1px solid #15233a"><span><?= h($t['ticket_type']) ?></span><b><?= (int)$t['c'] ?></b></div><?php endforeach; if (!$types) echo '<p class="muted">No tickets yet.</p>'; ?></div>
</div>
<?php admin_end();
