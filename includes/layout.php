<?php
require_once __DIR__ . '/../config.php';

function chip(array $u): string {
    $ini = strtoupper(substr($u['full_name'] ?: $u['username'], 0, 1));
    return '<details class="userchip"><summary><span class="avatar">' . h($ini) . '</span>
      <span><b>' . h($u['username']) . '</b><small>' . ($u['role'] === 'admin' ? 'Super admin' : 'Community member') . '</small></span></summary>
      <div class="menu"><a href="' . url('logout.php') . '">Log out</a></div></details>';
}

function head(string $title): void {
    echo '<!doctype html><html lang="en"><head><meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>' . h($title) . ' - Smart Event Portal</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="' . url('assets/style.css') . '"></head><body>';
}

/* ---------------- ADMIN layout ---------------- */
function admin_start(string $title, string $active): array {
    $u = require_login('admin');
    $nav = [
        'dashboard' => ['Dashboard', 'admin/index.php'],
        'events'    => ['Events', 'admin/events.php'],
        'regs'      => ['Registrations', 'admin/registrations.php'],
        'payments'  => ['Payments', 'admin/payments.php'],
        'attendance'=> ['QR Attendance', 'admin/attendance.php'],
        'reports'   => ['Reports', 'admin/reports.php'],
        'users'     => ['Users', 'admin/users.php'],
    ];
    head($title);
    echo '<div class="app"><aside class="sidebar"><div class="brand"><div class="logo">SE</div>
          <div><b>Admin Panel</b><span>Smart Event Portal</span></div></div>
          <div class="nav-label">Workspace</div><nav class="nav">';
    $i = 1;
    foreach ($nav as $k => [$label, $path]) {
        echo '<a href="' . url($path) . '" class="' . ($k === $active ? 'active' : '') . '"><span class="num">'
           . sprintf('%02d', $i++) . '</span>' . h($label) . '</a>';
    }
    echo '</nav></aside><div class="main"><header class="topbar"><div><div class="eyebrow">Operations</div><h1>' . h($title) . '</h1></div>
          <div class="top-right">' . chip($u) . '<span class="pill">' . date('D, M j, Y') . '</span></div></header><div class="content">';
    flash_show();
    return $u;
}
function admin_end(): void { echo '</div></div></div></body></html>'; }

/* ---------------- USER layout ---------------- */
function user_start(string $title, string $active): array {
    $u = require_login('user');
    $nav = ['dashboard' => ['My dashboard', 'user/index.php'], 'events' => ['Browse events', 'user/events.php']];
    head($title);
    echo '<div class="ushell"><header class="utop"><div class="brand" style="margin:0"><div class="logo">SE</div>
          <div><b>Smart Event Portal</b><span>Community account</span></div></div><nav class="unav">';
    foreach ($nav as $k => [$label, $path]) {
        echo '<a href="' . url($path) . '" class="' . ($k === $active ? 'active' : '') . '">' . h($label) . '</a>';
    }
    echo '</nav>' . chip($u) . '</header>';
    flash_show();
    return $u;
}
function user_end(): void { echo '</div></body></html>'; }
