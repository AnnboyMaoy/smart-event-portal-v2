<?php
require_once __DIR__ . '/../includes/layout.php';
$u = user_start('Browse events', 'events');
$events = db()->query("SELECT * FROM events WHERE status IN ('Upcoming','Ongoing') AND event_date >= CURDATE() ORDER BY event_date, event_time")->fetchAll();
$mine = db()->prepare("SELECT event_id FROM registrations WHERE user_id=? AND registration_status IN ('Pending','Approved')"); $mine->execute([$u['id']]);
$mine = array_column($mine->fetchAll(), 'event_id');
?>
<div class="eyebrow">Available now</div><h2 style="margin-bottom:16px">Upcoming events</h2>
<?php if (!$events): ?><div class="card muted">No upcoming events right now.</div><?php endif; ?>
<div class="grid-3" style="grid-template-columns:repeat(auto-fill,minmax(290px,1fr))">
<?php foreach ($events as $e): $taken = seats_taken((int)$e['event_id']); $left = max(0, $e['capacity'] - $taken); $reg = in_array($e['event_id'], $mine); ?>
  <article class="ev">
    <div class="date"><?= fdate($e['event_date']) ?> at <?= ftime($e['event_time']) ?></div>
    <h3><?= h($e['event_name']) ?></h3><p><?= h(mb_strimwidth((string)$e['description'], 0, 120, '...')) ?></p><p><?= h($e['location']) ?></p>
    <?= bar($taken, (int)$e['capacity']) ?>
    <div class="foot"><span class="price"><?= $e['price'] > 0 ? money($e['price']) : 'Free' ?></span><span class="muted small"><?= $left ? $left . ' seats left' : 'Fully booked' ?></span></div>
    <?php if ($reg): ?><a class="btn" href="<?= url('user/index.php') ?>">Already registered</a>
    <?php elseif (!$left): ?><button class="btn" disabled>Fully booked</button>
    <?php else: ?><a class="btn btn-primary" href="<?= url('user/register.php?event_id=' . $e['event_id']) ?>">Register</a><?php endif; ?>
  </article>
<?php endforeach; ?>
</div>
<?php user_end();
