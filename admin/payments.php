<?php
require_once __DIR__ . '/../includes/layout.php';
require_once __DIR__ . '/../includes/actions.php';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $a = require_login('admin'); csrf_check();
    $id = (int)($_POST['payment_id'] ?? 0);
    if (($_POST['action'] ?? '') === 'verify') { payment_verify($id, $a['id']); flash('Payment verified. Registration approved and ticket issued.'); }
    if (($_POST['action'] ?? '') === 'reject') { payment_reject($id, $a['id']); flash('Payment rejected. The attendee can submit again.'); }
    redirect('admin/payments.php');
}
admin_start('Payments', 'payments');
$st = $_GET['status'] ?? '';
$sql = "SELECT p.*, r.attendee_name, r.ticket_type, r.ticket_code, e.event_name FROM payments p
        JOIN registrations r ON r.registration_id=p.registration_id JOIN events e ON e.event_id=r.event_id"; $args = [];
if ($st !== '') { $sql .= " WHERE p.status=?"; $args[] = $st; }
$sql .= " ORDER BY FIELD(p.status,'Pending','Verified','Rejected'), p.created_at DESC";
$s = db()->prepare($sql); $s->execute($args); $rows = $s->fetchAll();
$tot = db()->query("SELECT status, COALESCE(SUM(amount),0) a, COUNT(*) c FROM payments GROUP BY status")->fetchAll(PDO::FETCH_UNIQUE);
$g = fn($k, $f) => $tot[$k][$f] ?? 0;
?>
<div class="stats" style="grid-template-columns:repeat(3,1fr)">
  <div class="stat"><div class="lbl">Verified revenue</div><div class="val" style="font-size:28px"><?= money($g('Verified','a')) ?></div><span class="muted small"><?= $g('Verified','c') ?> payments</span></div>
  <div class="stat"><div class="lbl">Waiting for verification</div><div class="val" style="font-size:28px"><?= money($g('Pending','a')) ?></div><span class="muted small"><?= $g('Pending','c') ?> payments</span></div>
  <div class="stat"><div class="lbl">Rejected</div><div class="val" style="font-size:28px"><?= money($g('Rejected','a')) ?></div><span class="muted small"><?= $g('Rejected','c') ?> payments</span></div>
</div>
<div class="card">
  <div class="card-head"><div><div class="eyebrow">Payments</div><h2>Payment records</h2></div></div>
  <form class="toolbar" method="get"><select name="status"><option value="">All statuses</option><?php foreach (['Pending','Verified','Rejected'] as $o): ?><option <?= $st===$o?'selected':'' ?>><?= $o ?></option><?php endforeach; ?></select><button class="btn">Filter</button></form>
  <div class="tablewrap"><table><thead><tr><th>Submitted</th><th>Attendee</th><th>Event</th><th>Method</th><th>Reference</th><th>Amount</th><th>Status</th><th></th></tr></thead><tbody>
  <?php foreach ($rows as $p): ?>
    <tr><td><?= fdate($p['created_at']) ?></td><td><b><?= h($p['attendee_name']) ?></b><br><span class="muted small"><?= h($p['ticket_type']) ?> &middot; <?= h($p['ticket_code']) ?></span></td>
      <td><?= h($p['event_name']) ?></td><td><?= h($p['method']) ?></td><td><?= h($p['reference_no']) ?></td><td><b><?= money($p['amount']) ?></b></td><td><?= badge($p['status']) ?></td>
      <td><?php if ($p['status'] === 'Pending'): ?><div class="actions">
        <form method="post"><?= csrf_field() ?><input type="hidden" name="payment_id" value="<?= $p['payment_id'] ?>"><input type="hidden" name="action" value="verify"><button class="btn btn-sm btn-primary">Verify</button></form>
        <form method="post"><?= csrf_field() ?><input type="hidden" name="payment_id" value="<?= $p['payment_id'] ?>"><input type="hidden" name="action" value="reject"><button class="btn btn-sm btn-danger">Reject</button></form></div><?php endif; ?></td></tr>
  <?php endforeach; if (!$rows): ?><tr><td colspan="8" class="muted">No payments yet.</td></tr><?php endif; ?>
  </tbody></table></div>
</div>
<?php admin_end();
