<?php
require_once __DIR__ . '/includes/layout.php';
$u = current_user();
$events = db()->query("SELECT * FROM events WHERE status IN ('Upcoming','Ongoing') AND event_date >= CURDATE() ORDER BY event_date, event_time LIMIT 12")->fetchAll();
head('Public events');
?>
<div class="ushell">
  <header class="pub-top">
    <div class="brand" style="margin:0"><div class="logo">SE</div><div><b>Smart Event Portal</b><span>Public events portal</span></div></div>
    <div style="display:flex;gap:10px">
      <?php if ($u): ?><a class="btn btn-primary" href="<?= url(home_for($u)) ?>">Go to my dashboard</a>
      <?php else: ?><a class="btn" href="<?= url('signup.php') ?>">Create account</a><a class="btn btn-primary" href="<?= url('login.php') ?>">Login</a><?php endif; ?>
    </div>
  </header>
  <section class="hero">
    <div><div class="eyebrow">Public events portal</div><h1>Find an event. Get your ticket.</h1>
    <p>Browse what is coming up, register in a minute and bring your QR ticket to the door.</p></div>
    <div class="hero-actions"><a class="btn btn-primary" href="<?= url($u ? 'user/events.php' : 'login.php') ?>">Register for an event</a></div>
  </section>
  <div class="eyebrow">Coming up</div><h2 style="margin-bottom:16px">Upcoming events</h2>
  <?php if (!$events): ?><div class="card muted">No upcoming events yet. Please check back soon.</div><?php endif; ?>
  <div class="grid-3" style="grid-template-columns:repeat(auto-fill,minmax(280px,1fr))">
  <?php foreach ($events as $e): $taken = seats_taken((int)$e['event_id']); $left = max(0, $e['capacity'] - $taken); ?>
    <article class="ev">
      <div class="date"><?= fdate($e['event_date']) ?> at <?= ftime($e['event_time']) ?></div>
      <h3><?= h($e['event_name']) ?></h3>
      <p><?= h(mb_strimwidth((string)$e['description'], 0, 110, '...')) ?></p>
      <p><?= h($e['location']) ?></p>
      <?= bar($taken, (int)$e['capacity']) ?>
      <div class="foot"><span class="price"><?= $e['price'] > 0 ? money($e['price']) : 'Free' ?></span>
        <span class="muted small"><?= $left > 0 ? $left . ' seats left' : 'Fully booked' ?></span></div>
      <a class="btn btn-primary" href="<?= url($u && $u['role']==='user' ? 'user/register.php?event_id='.$e['event_id'] : 'login.php') ?>">Register</a>
    </article>
  <?php endforeach; ?>
  </div>
</div></body></html>
