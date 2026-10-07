<?php
require_once __DIR__ . '/../includes/layout.php';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_login('admin'); csrf_check();
    if (($_POST['action'] ?? '') === 'delete') {
        $id = (int)$_POST['event_id'];
        db()->prepare("DELETE FROM events WHERE event_id=?")->execute([$id]);   // registrations deleted by FK cascade
        flash('Event deleted.');
    }
    redirect('admin/events.php');
}
$u = admin_start('Events', 'events');
$q = trim($_GET['q'] ?? ''); $st = $_GET['status'] ?? '';
$sql = "SELECT * FROM events WHERE 1"; $args = [];
if ($q !== '')  { $sql .= " AND (event_name LIKE ? OR location LIKE ?)"; $args[] = "%$q%"; $args[] = "%$q%"; }
if ($st !== '') { $sql .= " AND status=?"; $args[] = $st; }
$sql .= " ORDER BY event_date DESC, event_time DESC";
$s = db()->prepare($sql); $s->execute($args); $rows = $s->fetchAll();
?>
<div class="card">
  <div class="card-head"><div><div class="eyebrow">Event creation</div><h2>All events</h2></div>
    <a class="btn btn-primary" href="<?= url('admin/event_form.php') ?>">+ New event</a></div>
  <form class="toolbar" method="get">
    <input name="q" placeholder="Search name or location" value="<?= h($q) ?>">
    <select name="status"><option value="">All statuses</option>
      <?php foreach (['Upcoming','Ongoing','Completed','Cancelled'] as $o): ?><option <?= $st===$o?'selected':'' ?>><?= $o ?></option><?php endforeach; ?></select>
    <button class="btn">Filter</button>
  </form>
  <div class="tablewrap"><table><thead><tr><th>Event</th><th>Date &amp; time</th><th>Location</th><th>Capacity</th><th>Price</th><th>Status</th><th></th></tr></thead><tbody>
  <?php foreach ($rows as $e): $t = seats_taken((int)$e['event_id']); ?>
    <tr><td><b><?= h($e['event_name']) ?></b></td>
      <td><?= fdate($e['event_date']) ?><br><span class="muted small"><?= ftime($e['event_time']) ?></span></td>
      <td><?= h($e['location']) ?></td>
      <td style="min-width:150px"><?= $t ?> / <?= (int)$e['capacity'] ?><?= bar($t, (int)$e['capacity']) ?></td>
      <td><?= $e['price'] > 0 ? money($e['price']) : 'Free' ?><?= $e['vip_price'] > 0 ? '<br><span class="muted small">VIP ' . money($e['vip_price']) . '</span>' : '' ?></td>
      <td><?= badge($e['status']) ?></td>
      <td><div class="actions">
        <a class="btn btn-sm" href="<?= url('admin/registrations.php?event_id=' . $e['event_id']) ?>">Attendees</a>
        <a class="btn btn-sm" href="<?= url('admin/event_form.php?id=' . $e['event_id']) ?>">Edit</a>
        <form method="post" onsubmit="return confirm('Delete this event and all its registrations?')"><?= csrf_field() ?>
          <input type="hidden" name="action" value="delete"><input type="hidden" name="event_id" value="<?= $e['event_id'] ?>">
          <button class="btn btn-sm btn-danger">Delete</button></form></div></td></tr>
  <?php endforeach; if (!$rows): ?><tr><td colspan="7" class="muted">No events found.</td></tr><?php endif; ?>
  </tbody></table></div>
</div>
<?php admin_end();
