<?php
require_once __DIR__ . '/../includes/layout.php';
require_login('admin');
$id = (int)($_GET['id'] ?? $_POST['event_id'] ?? 0);
$ev = ['event_name'=>'','description'=>'','event_date'=>date('Y-m-d', strtotime('+7 days')),'event_time'=>'09:00','location'=>'',
       'capacity'=>100,'price'=>0,'vip_price'=>0,'status'=>'Upcoming'];
if ($id) {
    $s = db()->prepare("SELECT * FROM events WHERE event_id=?"); $s->execute([$id]);
    $ev = $s->fetch() ?: redirect('admin/events.php');
}
$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $d = [
        trim($_POST['event_name'] ?? ''), trim($_POST['description'] ?? ''), $_POST['event_date'] ?? '', $_POST['event_time'] ?? '',
        trim($_POST['location'] ?? ''), (int)($_POST['capacity'] ?? 0), (float)($_POST['price'] ?? 0), (float)($_POST['vip_price'] ?? 0),
        $_POST['status'] ?? 'Upcoming',
    ];
    $taken = $id ? seats_taken($id) : 0;
    if ($d[0] === '' || $d[2] === '' || $d[3] === '' || $d[4] === '') $error = 'Name, date, time and location are required.';
    elseif ($d[5] < 1) $error = 'Capacity must be at least 1.';
    elseif ($d[5] < $taken) $error = "Capacity cannot be lower than the $taken seats already taken.";
    elseif ($d[6] < 0 || $d[7] < 0) $error = 'Prices cannot be negative.';
    elseif (!in_array($d[8], ['Upcoming','Ongoing','Completed','Cancelled'], true)) $error = 'Invalid status.';
    if ($error) { $ev = array_merge($ev, array_combine(['event_name','description','event_date','event_time','location','capacity','price','vip_price','status'], $d)); }
    else {
        if ($id) { $d[] = $id; db()->prepare("UPDATE events SET event_name=?,description=?,event_date=?,event_time=?,location=?,capacity=?,price=?,vip_price=?,status=? WHERE event_id=?")->execute($d); }
        else     { $d[] = $_SESSION['user']['id']; db()->prepare("INSERT INTO events (event_name,description,event_date,event_time,location,capacity,price,vip_price,status,created_by) VALUES (?,?,?,?,?,?,?,?,?,?)")->execute($d); }
        flash($id ? 'Event updated.' : 'Event created.');
        redirect('admin/events.php');
    }
}
admin_start($id ? 'Edit event' : 'New event', 'events');
if ($error) echo '<div class="flash err">' . h($error) . '</div>';
?>
<div class="card"><form method="post"><?= csrf_field() ?><input type="hidden" name="event_id" value="<?= $id ?>">
  <div class="form-grid">
    <div class="full"><label>Event name</label><input name="event_name" required value="<?= h($ev['event_name']) ?>"></div>
    <div class="full"><label>Description</label><textarea name="description"><?= h($ev['description']) ?></textarea></div>
    <div><label>Date</label><input type="date" name="event_date" required value="<?= h($ev['event_date']) ?>"></div>
    <div><label>Time</label><input type="time" name="event_time" required value="<?= h(substr((string)$ev['event_time'], 0, 5)) ?>"></div>
    <div class="full"><label>Location</label><input name="location" required value="<?= h($ev['location']) ?>"></div>
    <div><label>Capacity (max attendees)</label><input type="number" min="1" name="capacity" required value="<?= (int)$ev['capacity'] ?>"></div>
    <div><label>Status</label><select name="status"><?php foreach (['Upcoming','Ongoing','Completed','Cancelled'] as $o): ?><option <?= $ev['status']===$o?'selected':'' ?>><?= $o ?></option><?php endforeach; ?></select></div>
    <div><label>General ticket price (PHP, 0 = free)</label><input type="number" step="0.01" min="0" name="price" value="<?= h($ev['price']) ?>"></div>
    <div><label>VIP ticket price (PHP, 0 = no VIP)</label><input type="number" step="0.01" min="0" name="vip_price" value="<?= h($ev['vip_price']) ?>"></div>
  </div>
  <div style="display:flex;gap:10px;margin-top:22px"><button class="btn btn-primary">Save event</button><a class="btn" href="<?= url('admin/events.php') ?>">Cancel</a></div>
</form></div>
<?php admin_end();
