<?php
/* ============================================================
   Smart Event Portal - configuration
   Edit these 4 lines if your MySQL settings are different.
   ============================================================ */
const DB_HOST = 'localhost';
const DB_NAME = 'smart_event_portal';
const DB_USER = 'root';
const DB_PASS = '';              // XAMPP default is empty

// Folder name inside C:\xampp\htdocs\  (must match!)
const BASE_URL = '/smart-event-portal';

// Shown to users on the payment page. Change to your real numbers.
const PAY_INSTRUCTIONS = 'Send the exact amount to GCash 0917-000-0000 (Smart Events), then enter the reference number below.';

date_default_timezone_set('Asia/Manila');
session_start();

function db(): PDO {
    static $pdo = null;
    if ($pdo === null) {
        $pdo = new PDO(
            'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4',
            DB_USER, DB_PASS,
            [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
            ]
        );
    }
    return $pdo;
}

function h($v): string { return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8'); }
function url(string $p = ''): string { return BASE_URL . '/' . ltrim($p, '/'); }
function redirect(string $p): void { header('Location: ' . url($p)); exit; }
function money($n): string { return '₱' . number_format((float)$n, 2); }
function fdate($d): string { return $d ? date('M j, Y', strtotime($d)) : '-'; }
function ftime($t): string { return $t ? date('g:i A', strtotime($t)) : '-'; }

/* ---------- flash messages ---------- */
function flash(string $msg, string $type = 'ok'): void { $_SESSION['flash'] = [$msg, $type]; }
function flash_show(): void {
    if (!empty($_SESSION['flash'])) {
        [$m, $t] = $_SESSION['flash'];
        unset($_SESSION['flash']);
        echo '<div class="flash ' . h($t) . '">' . h($m) . '</div>';
    }
}

/* ---------- CSRF ---------- */
function csrf_token(): string {
    if (empty($_SESSION['csrf'])) $_SESSION['csrf'] = bin2hex(random_bytes(16));
    return $_SESSION['csrf'];
}
function csrf_field(): string { return '<input type="hidden" name="csrf" value="' . h(csrf_token()) . '">'; }
function csrf_check(): void {
    $t = $_POST['csrf'] ?? ($_SERVER['HTTP_X_CSRF'] ?? '');
    if (!hash_equals($_SESSION['csrf'] ?? '', (string)$t)) {
        http_response_code(419);
        exit('Session expired. Please go back and try again.');
    }
}

/* ---------- auth ---------- */
function current_user(): ?array { return $_SESSION['user'] ?? null; }
function require_login(?string $role = null): array {
    $u = current_user();
    if (!$u) redirect('login.php');
    if ($role && $u['role'] !== $role) redirect($u['role'] === 'admin' ? 'admin/index.php' : 'user/index.php');
    return $u;
}
function home_for(array $u): string { return $u['role'] === 'admin' ? 'admin/index.php' : 'user/index.php'; }

/* ---------- business helpers ---------- */
function ticket_code(): string { return 'EVT-' . strtoupper(bin2hex(random_bytes(5))); }

/** seats already taken (Pending + Approved registrations) */
function seats_taken(int $eventId): int {
    $s = db()->prepare("SELECT COUNT(*) FROM registrations WHERE event_id=? AND registration_status IN ('Pending','Approved')");
    $s->execute([$eventId]);
    return (int)$s->fetchColumn();
}

function badge(string $text): string {
    $map = [
        'Approved'=>'b-green','Paid'=>'b-green','Free'=>'b-green','Verified'=>'b-green','Attended'=>'b-green','Upcoming'=>'b-blue','Ongoing'=>'b-green',
        'Pending'=>'b-amber','Unpaid'=>'b-amber','Not Attended'=>'b-gray','Completed'=>'b-gray',
        'Rejected'=>'b-red','Cancelled'=>'b-red','Refunded'=>'b-red',
    ];
    return '<span class="badge ' . ($map[$text] ?? '') . '">' . h($text) . '</span>';
}
function bar(int $taken, int $cap): string {
    $pct = $cap > 0 ? min(100, round($taken / $cap * 100)) : 0;
    return '<div class="bar"><i class="' . ($pct >= 100 ? 'full' : '') . '" style="width:' . $pct . '%"></i></div>';
}
