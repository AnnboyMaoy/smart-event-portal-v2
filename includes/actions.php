<?php
/* Shared state changes for registrations / payments */
require_once __DIR__ . '/../config.php';

function payment_verify(int $paymentId, int $adminId): void {
    $pdo = db(); $pdo->beginTransaction();
    $p = $pdo->prepare("SELECT * FROM payments WHERE payment_id=? FOR UPDATE"); $p->execute([$paymentId]); $p = $p->fetch();
    if ($p && $p['status'] === 'Pending') {
        $pdo->prepare("UPDATE payments SET status='Verified', verified_by=?, verified_at=NOW() WHERE payment_id=?")->execute([$adminId, $paymentId]);
        $pdo->prepare("UPDATE registrations SET payment_status='Paid', registration_status='Approved' WHERE registration_id=? AND registration_status<>'Cancelled'")->execute([$p['registration_id']]);
    }
    $pdo->commit();
}
function payment_reject(int $paymentId, int $adminId): void {
    $pdo = db(); $pdo->beginTransaction();
    $p = $pdo->prepare("SELECT * FROM payments WHERE payment_id=? FOR UPDATE"); $p->execute([$paymentId]); $p = $p->fetch();
    if ($p && $p['status'] === 'Pending') {
        $pdo->prepare("UPDATE payments SET status='Rejected', verified_by=?, verified_at=NOW() WHERE payment_id=?")->execute([$adminId, $paymentId]);
        $pdo->prepare("UPDATE registrations SET payment_status='Unpaid' WHERE registration_id=?")->execute([$p['registration_id']]);
    }
    $pdo->commit();
}
/** admin received cash in person */
function mark_paid_cash(int $regId, int $adminId): void {
    $pdo = db(); $pdo->beginTransaction();
    $r = $pdo->prepare("SELECT * FROM registrations WHERE registration_id=? FOR UPDATE"); $r->execute([$regId]); $r = $r->fetch();
    if ($r && $r['payment_status'] !== 'Paid' && $r['payment_status'] !== 'Free' && $r['registration_status'] !== 'Cancelled') {
        $pdo->prepare("UPDATE payments SET status='Rejected', verified_by=?, verified_at=NOW() WHERE registration_id=? AND status='Pending'")->execute([$adminId, $regId]);
        $pdo->prepare("INSERT INTO payments (registration_id, amount, method, reference_no, status, verified_by, verified_at) VALUES (?,?, 'Cash', 'Paid at desk', 'Verified', ?, NOW())")
            ->execute([$regId, $r['amount'], $adminId]);
        $pdo->prepare("UPDATE registrations SET payment_status='Paid', registration_status='Approved' WHERE registration_id=?")->execute([$regId]);
    }
    $pdo->commit();
}
