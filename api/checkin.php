<?php
require_once __DIR__ . '/../config.php';
header('Content-Type: application/json; charset=utf-8');
$u = current_user();
if (!$u || $u['role'] !== 'admin') { http_response_code(403); echo json_encode(['ok'=>false,'message'=>'Admin login required.']); exit; }
if ($_SERVER['REQUEST_METHOD'] !== 'POST') { http_response_code(405); exit; }
csrf_check();

$code = strtoupper(trim($_POST['code'] ?? ''));
$eventFilter = (int)($_POST['event_id'] ?? 0);
if ($code === '') { echo json_encode(['ok'=>false,'message'=>'No ticket code received.']); exit; }

$pdo = db(); $pdo->beginTransaction();
$s = $pdo->prepare("SELECT r.*, e.event_name, e.event_date, e.status AS event_status
                    FROM registrations r JOIN events e ON e.event_id=r.event_id WHERE r.ticket_code=? FOR UPDATE");
$s->execute([$code]); $r = $s->fetch();

function out(PDO $pdo, bool $ok, string $msg, ?array $r = null): void {
    $pdo->commit();
    echo json_encode(['ok'=>$ok,'message'=>$msg,'attendee'=>$r['attendee_name'] ?? null,'event'=>$r['event_name'] ?? null,'ticket'=>$r['ticket_type'] ?? null]);
    exit;
}
if (!$r)                                    out($pdo, false, 'Ticket not found. This code is not in the system.');
if ($eventFilter && $r['event_id'] != $eventFilter) out($pdo, false, 'Wrong event. This ticket is for: ' . $r['event_name'], $r);
if ($r['event_status'] === 'Cancelled')     out($pdo, false, 'This event was cancelled.', $r);
if ($r['registration_status'] !== 'Approved') out($pdo, false, 'Ticket is not valid. Registration status: ' . $r['registration_status'] . '.', $r);
if (!in_array($r['payment_status'], ['Paid','Free'])) out($pdo, false, 'Payment not completed (' . $r['payment_status'] . ').', $r);
if ($r['attendance_status'] === 'Attended') out($pdo, false, 'Already checked in on ' . date('M j, g:i A', strtotime($r['checked_in_at'])) . '.', $r);

$pdo->prepare("UPDATE registrations SET attendance_status='Attended', checked_in_at=NOW() WHERE registration_id=?")->execute([$r['registration_id']]);
out($pdo, true, 'Check-in successful. Welcome!', $r);
