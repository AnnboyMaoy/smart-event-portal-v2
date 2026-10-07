<?php
require_once __DIR__ . '/../includes/layout.php';
require_once __DIR__ . '/../includes/actions.php';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $a = require_login('admin'); csrf_check();
    $id = (int)($_POST['registration_id'] ?? 0);
    switch ($_POST['action'] ?? '') {
        case 'approve': db()->prepare("UPDATE registrations SET registration_status='Approved' WHERE registration_id=?")->execute([$id]); flash('Registration approved.'); break;
        case 'reject':  db()->prepare("UPDATE registrations SET registration_status='Rejected' WHERE registration_id=?")->execute([$id]); flash('Registration rejected. The seat is released.'); break;
        case 'cancel':  db()->prepare("UPDATE registrations SET registration_status='Cancelled' WHERE registration_id=?")->execute([$id]); flash('Registration cancelled.'); break;
        case 'cash':    mark_paid_cash($id, $a['id']); flash('Cash payment recorded and registration approved.'); break;
        case 'delete':  db()->prepare("DELETE FROM registrations WHERE registration_id=?")->execute([$id]); flash('Registration deleted.'); break;
    }
    redirect('admin/registrations.php' . (!empty($_POST['back']) ? '?' . $_POST['back'] : ''));
}
admin_start('Registrations', 'regs');
$eid = (int)($_GET['event_id'] ?? 0); $st = $_GET['status'] ?? ''; $q = trim($_GET['q'] ?? '');
$sql = "SELECT r.*, e.event_name FROM registrations r JOIN events e ON e.event_id=r.event_id WHERE 1"; $args = [];
if ($eid)      { $sql .= " AND r.event_id=?"; $args[] = $eid; }
if ($st !== ''){ $sql .= " AND r.registration_status=?"; $args[] = $st; }
if ($q !== '') { $sql .= " AND (r.attendee_name LIKE ? OR r.email LIKE ? OR r.ticket_code LIKE ?)"; array_push($args, "%$q%", "%$q%", "%$q%"); }
$sql .= " ORDER BY r.registration_date DESC";
$s = db()->prepare($sql); $s->execute($args); $rows = $s->fetchAll();
$events = db()->query("SELECT event_id, event_name FROM events ORDER BY event_date DESC")->fetchAll();
$back = $_SERVER['QUERY_STRING'] ?? '';
?>
<div class="card">
  <div class="card-head"><div><div class="eyebrow">Registration</div><h2>Attendees &amp; tickets</h2></div></div>
  <form class="toolbar" method="get">
    <input name="q" placeholder="Name, email or ticket code" value="<?= h($q) ?>">
    <select name="event_id"><option value="0">All events</option><?php foreach ($events as $e): ?><option value="<?= $e['event_id'] ?>" <?= $eid==$e['event_id']?'selected':'' ?>><?= h($e['event_name']) ?></option><?php endforeach; ?></select>
    <select name="status"><option value="">All statuses</option><?php foreach (['Pending','Approved','Rejected','Cancelled'] as $o): ?><option <?= $st===$o?'selected':'' ?>><?= $o ?></option><?php endforeach; ?></select>
    <button class="btn">Filter</button>
  </form>
  <div class="tablewrap"><table><thead><tr><th>Attendee</th><th>Event</th><th>Ticket</th><th>Amount</th><th>Payment</th><th>Status</th><th>Attendance</th><th></th></tr></thead><tbody>
  <?php foreach ($rows as $r): $rid = $r['registration_id']; ?>
    <tr><td><b><?= h($r['attendee_name']) ?></b><br><span class="muted small"><?= h($r['email']) ?><?= $r['phone'] ? ' &middot; ' . h($r['phone']) : '' ?></span></td>
      <td><?= h($r['event_name']) ?></td>
      <td><?= h($r['ticket_type']) ?><br><span class="code small"><?= h($r['ticket_code']) ?></span></td>
      <td><?= money($r['amount']) ?></td><td><?= badge($r['payment_status']) ?></td><td><?= badge($r['registration_status']) ?></td><td><?= badge($r['attendance_status']) ?></td>
      <td><div class="actions">
      <?php
        $btn = function (string $act, string $label, string $cls = '', string $confirm = '') use ($rid, $back) {
            return '<form method="post"' . ($confirm ? ' onsubmit="return confirm(\'' . $confirm . '\')"' : '') . '>' . csrf_field()
                 . '<input type="hidden" name="action" value="' . $act . '"><input type="hidden" name="registration_id" value="' . $rid . '">'
                 . '<input type="hidden" name="back" value="' . h($back) . '"><button class="btn btn-sm ' . $cls . '">' . $label . '</button></form>';
        };
        if ($r['registration_status'] === 'Pending') echo $btn('approve', 'Approve', 'btn-primary') . $btn('reject', 'Reject', 'btn-danger');
        if (!in_array($r['payment_status'], ['Paid','Free']) && in_array($r['registration_status'], ['Pending','Approved'])) echo $btn('cash', 'Mark paid (cash)');
        if ($r['registration_status'] === 'Approved') echo '<a class="btn btn-sm" href="' . url('ticket.php?id=' . $rid) . '">Ticket</a>';
        if (in_array($r['registration_status'], ['Pending','Approved'])) echo $btn('cancel', 'Cancel', '', 'Cancel this registration?');
        echo $btn('delete', 'Delete', 'btn-danger', 'Delete permanently?');
      ?></div></td></tr>
  <?php endforeach; if (!$rows): ?><tr><td colspan="8" class="muted">No registrations found.</td></tr><?php endif; ?>
  </tbody></table></div>
</div>
<?php admin_end();
