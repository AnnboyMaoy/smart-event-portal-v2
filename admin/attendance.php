<?php
require_once __DIR__ . '/../includes/layout.php';
admin_start('QR Attendance', 'attendance');
$events = db()->query("SELECT event_id, event_name FROM events WHERE status IN ('Upcoming','Ongoing') ORDER BY event_date")->fetchAll();
$recent = db()->query("SELECT r.attendee_name, r.ticket_type, r.checked_in_at, e.event_name FROM registrations r JOIN events e ON e.event_id=r.event_id
                       WHERE r.attendance_status='Attended' ORDER BY r.checked_in_at DESC LIMIT 12")->fetchAll();
$cnt = db()->query("SELECT e.event_name, SUM(r.registration_status='Approved') ok, SUM(r.attendance_status='Attended') att FROM events e
                    LEFT JOIN registrations r ON r.event_id=e.event_id WHERE e.status IN ('Upcoming','Ongoing') GROUP BY e.event_id ORDER BY e.event_date")->fetchAll();
?>
<div class="grid-2">
  <div class="card">
    <div class="card-head"><div><div class="eyebrow">Check-in</div><h2>Scan a ticket</h2></div></div>
    <div class="toolbar"><select id="evfilter"><option value="0">Any event</option><?php foreach ($events as $e): ?><option value="<?= $e['event_id'] ?>"><?= h($e['event_name']) ?></option><?php endforeach; ?></select></div>
    <div id="reader"></div>
    <p class="muted small" id="camnote">Allow camera access when your browser asks. A USB barcode scanner also works: click the box below and scan.</p>
    <div class="form-grid" style="margin-top:14px;grid-template-columns:1fr auto;align-items:end">
      <div><label for="manual">Or type / scan the ticket code</label><input id="manual" placeholder="EVT-XXXXXXXXXX" autocomplete="off"></div>
      <button class="btn btn-primary" id="manualBtn" type="button">Check in</button>
    </div>
    <div id="result"></div>
  </div>
  <div>
    <div class="card"><div class="card-head"><div><div class="eyebrow">Live</div><h2>Event headcount</h2></div></div>
      <?php foreach ($cnt as $c): ?><div style="margin-bottom:14px"><b><?= h($c['event_name']) ?></b><div class="muted small"><?= (int)$c['att'] ?> of <?= (int)$c['ok'] ?> approved checked in</div><?= bar((int)$c['att'], (int)$c['ok']) ?></div><?php endforeach; if (!$cnt) echo '<p class="muted">No active events.</p>'; ?></div>
    <div class="card"><div class="card-head"><div><div class="eyebrow">Recent</div><h2>Latest check-ins</h2></div></div>
      <?php foreach ($recent as $r): ?><div style="padding:8px 0;border-bottom:1px solid #15233a"><b><?= h($r['attendee_name']) ?></b> <?= badge($r['ticket_type']) ?><div class="muted small"><?= h($r['event_name']) ?> &middot; <?= date('M j, g:i A', strtotime($r['checked_in_at'])) ?></div></div><?php endforeach; if (!$recent) echo '<p class="muted">Nobody checked in yet.</p>'; ?></div>
  </div>
</div>
<script src="https://cdnjs.cloudflare.com/ajax/libs/html5-qrcode/2.3.8/html5-qrcode.min.js"></script>
<script>
const CSRF = <?= json_encode(csrf_token()) ?>, API = <?= json_encode(url('api/checkin.php')) ?>;
const box = document.getElementById('result'); let busy = false, lastCode = '', lastAt = 0;
function esc(s){const d=document.createElement('div');d.textContent=s==null?'':s;return d.innerHTML}
async function checkin(code){
  code = (code||'').trim(); if(!code || busy) return;
  if (code === lastCode && Date.now() - lastAt < 4000) return;   // ignore repeat reads of the same QR
  busy = true; lastCode = code; lastAt = Date.now();
  try{
    const body = new URLSearchParams({code, event_id: document.getElementById('evfilter').value, csrf: CSRF});
    const res = await fetch(API,{method:'POST',body,headers:{'X-CSRF':CSRF}}); const j = await res.json();
    box.innerHTML = '<div class="result '+(j.ok?'ok':'err')+'"><h3>'+(j.ok?'&#10003; Checked in':'&#10007; Not accepted')+'</h3><div>'+esc(j.message)+'</div>'
      +(j.attendee?'<div style="margin-top:8px"><b>'+esc(j.attendee)+'</b> &middot; '+esc(j.ticket)+'<br><span class="muted small">'+esc(j.event)+'</span></div>':'')+'</div>';
  }catch(e){ box.innerHTML='<div class="result err"><h3>Network error</h3>Could not reach the server.</div>'; }
  busy = false;
}
document.getElementById('manualBtn').onclick = ()=>{ const i=document.getElementById('manual'); lastCode=''; checkin(i.value); i.value=''; i.focus(); };
document.getElementById('manual').addEventListener('keydown',e=>{ if(e.key==='Enter'){e.preventDefault();document.getElementById('manualBtn').click();} });
if (window.Html5Qrcode) {
  const sc = new Html5Qrcode('reader');
  sc.start({facingMode:'environment'},{fps:10,qrbox:240},txt=>checkin(txt),()=>{}).catch(()=>{
    document.getElementById('camnote').textContent='Camera unavailable (needs permission, and https or localhost). Use the code box below instead.'; });
} else document.getElementById('camnote').textContent='Scanner library could not load (check your internet). Use the code box below.';
</script>
<?php admin_end();
