<?php
require_once('includes/auth.php');
requireAdmin();
$pageTitle = 'Dashboard';

// Stats
$stats = [];
$stats['stations']   = $pdo->query("SELECT COUNT(*) FROM stations")->fetchColumn();
$stats['connectors'] = $pdo->query("SELECT COUNT(*) FROM connectors")->fetchColumn();
$stats['bookings']   = $pdo->query("SELECT COUNT(*) FROM bookings")->fetchColumn();
$stats['users']      = $pdo->query("SELECT COUNT(*) FROM users WHERE role = 'user'")->fetchColumn();

$stats['active_stations']    = $pdo->query("SELECT COUNT(*) FROM stations WHERE status = 'active'")->fetchColumn();
$stats['available_connectors']= $pdo->query("SELECT COUNT(*) FROM connectors WHERE status = 'available'")->fetchColumn();
$stats['today_bookings']     = $pdo->query("SELECT COUNT(*) FROM bookings WHERE booking_date = CURDATE()")->fetchColumn();
$stats['upcoming_bookings']  = $pdo->query("SELECT COUNT(*) FROM bookings WHERE status = 'upcoming' AND booking_date >= CURDATE()")->fetchColumn();

// Recent bookings
$recentBookings = $pdo->query("
    SELECT b.*, u.name AS user_name, s.name AS station_name, c.connector_type, c.power_kw
    FROM bookings b
    JOIN users u ON b.user_id = u.id
    JOIN stations s ON b.station_id = s.id
    JOIN connectors c ON b.connector_id = c.id
    ORDER BY b.created_at DESC LIMIT 8
")->fetchAll();

require_once __DIR__ . '/../includes/header.php';
?>

<div class="welcome-banner">
    <div>
        <h2 class="welcome-title">Good <?= date('H') < 12 ? 'morning' : (date('H') < 17 ? 'afternoon' : 'evening') ?>,
            <span><?= htmlspecialchars(explode(' ', $_SESSION['user_name'])[0]) ?></span> ⚡
        </h2>
        <p style="color:var(--text-muted); margin:0; font-size:.9rem;">Here's what's happening across your charging network.</p>
    </div>
    <div style="display:flex; gap:.75rem; flex-wrap:wrap;">
        <a href="<?= BASE_URL ?>/admin/stations.php" class="btn-ev btn-ev-primary btn-ev-sm">
            <i class="fas fa-plus"></i> Add Station
        </a>
        <a href="<?= BASE_URL ?>/admin/bookings.php" class="btn-ev btn-ev-secondary btn-ev-sm">
            <i class="fas fa-calendar"></i> All Bookings
        </a>
    </div>
</div>

<!-- Stats -->
<div class="stat-grid">
    <div class="stat-card" style="--stat-color: var(--accent); --stat-bg: var(--accent-dim);">
        <div class="stat-icon"><i class="fas fa-charging-station"></i></div>
        <div class="stat-info">
            <div class="stat-value"><?= $stats['active_stations'] ?><span style="font-size:1rem;color:var(--text-muted);">/<?= $stats['stations'] ?></span></div>
            <div class="stat-label">Active Stations</div>
        </div>
    </div>
    <div class="stat-card" style="--stat-color: #3b8bff; --stat-bg: rgba(59,139,255,0.12);">
        <div class="stat-icon" style="background:rgba(59,139,255,0.12);color:#3b8bff;"><i class="fas fa-plug"></i></div>
        <div class="stat-info">
            <div class="stat-value"><?= $stats['available_connectors'] ?><span style="font-size:1rem;color:var(--text-muted);">/<?= $stats['connectors'] ?></span></div>
            <div class="stat-label">Available Connectors</div>
        </div>
    </div>
    <div class="stat-card" style="--stat-color: var(--warning); --stat-bg: var(--warning-dim);">
        <div class="stat-icon" style="background:var(--warning-dim);color:var(--warning);"><i class="fas fa-calendar-day"></i></div>
        <div class="stat-info">
            <div class="stat-value"><?= $stats['today_bookings'] ?></div>
            <div class="stat-label">Today's Bookings</div>
        </div>
    </div>
    <div class="stat-card" style="--stat-color: #c06aff; --stat-bg: rgba(192,106,255,0.12);">
        <div class="stat-icon" style="background:rgba(192,106,255,0.12);color:#c06aff;"><i class="fas fa-users"></i></div>
        <div class="stat-info">
            <div class="stat-value"><?= $stats['users'] ?></div>
            <div class="stat-label">Registered Users</div>
        </div>
    </div>
</div>

<!-- Recent Bookings -->
<div class="ev-card">
    <div style="display:flex; align-items:center; justify-content:space-between; margin-bottom:1.25rem;">
        <h3 style="font-family:var(--font-display); font-size:1rem; font-weight:700; margin:0;">
            <i class="fas fa-clock-rotate-left me-2" style="color:var(--accent);"></i> Recent Bookings
        </h3>
        <a href="<?= BASE_URL ?>/admin/bookings.php" class="btn-ev btn-ev-secondary btn-ev-sm">View All</a>
    </div>

    <?php if (empty($recentBookings)): ?>
        <div class="empty-state" style="padding:2rem;">
            <i class="fas fa-calendar-xmark"></i>
            <h4>No bookings yet</h4>
            <p>Bookings will appear here as users start reserving connectors.</p>
        </div>
    <?php else: ?>
        <div class="table-wrapper">
            <table class="ev-table">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>User</th>
                        <th>Station</th>
                        <th>Connector</th>
                        <th>Date</th>
                        <th>Time Slot</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($recentBookings as $b): ?>
                    <tr>
                        <td style="color:var(--text-muted); font-size:.82rem;">#<?= $b['id'] ?></td>
                        <td><?= htmlspecialchars($b['user_name']) ?></td>
                        <td><?= htmlspecialchars($b['station_name']) ?></td>
                        <td>
                            <span class="connector-chip available"><?= htmlspecialchars($b['connector_type']) ?></span>
                            <span style="color:var(--text-muted); font-size:.78rem;"> <?= $b['power_kw'] ?>kW</span>
                        </td>
                        <td><?= date('d M Y', strtotime($b['booking_date'])) ?></td>
                        <td><?= date('H:i', strtotime($b['start_time'])) ?> – <?= date('H:i', strtotime($b['end_time'])) ?></td>
                        <td><span class="status-badge status-<?= $b['status'] ?>"><?= ucfirst($b['status']) ?></span></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>

</div><!-- page-wrapper -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.2/js/bootstrap.bundle.min.js"></script>
</body>
</html>