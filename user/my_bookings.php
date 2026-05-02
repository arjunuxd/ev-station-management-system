<?php
require_once __DIR__ . '/../includes/auth.php';
requireUser();
$pageTitle = 'My Bookings';

$msg     = '';
$msgType = 'success';
$filter  = $_GET['filter'] ?? 'upcoming';

// Handle cancel
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'cancel') {
    $id = (int)$_POST['id'];
    if ($id) {
        $check = $pdo->prepare("SELECT id, status FROM bookings WHERE id = ? AND user_id = ?");
        $check->execute([$id, $_SESSION['user_id']]);
        $booking = $check->fetch();
        if ($booking && in_array($booking['status'], ['upcoming', 'active'])) {
            $pdo->prepare("UPDATE bookings SET status='cancelled' WHERE id=?")->execute([$id]);
            $msg = 'Booking cancelled successfully.';
        } else {
            $msg = 'Cannot cancel this booking.'; $msgType = 'error';
        }
    }
}

$statusMap = [
    'upcoming'  => 'upcoming',
    'active'    => 'active',
    'completed' => 'completed',
    'cancelled' => 'cancelled',
    'all'       => null,
];

$statusFilter = $statusMap[$filter] ?? 'upcoming';
$whereStatus  = $statusFilter ? "AND b.status = '$statusFilter'" : '';

$bookings = $pdo->prepare("
    SELECT b.*, s.name AS station_name, s.location AS station_location,
           c.connector_type, c.power_kw
    FROM bookings b
    JOIN stations s ON b.station_id = s.id
    JOIN connectors c ON b.connector_id = c.id
    WHERE b.user_id = ? $whereStatus
    ORDER BY b.booking_date DESC, b.start_time DESC
");
$bookings->execute([$_SESSION['user_id']]);
$bookings = $bookings->fetchAll();

// Counts per status
$counts = $pdo->prepare("
    SELECT status, COUNT(*) AS cnt FROM bookings WHERE user_id = ? GROUP BY status
");
$counts->execute([$_SESSION['user_id']]);
$countMap = ['all' => 0];
foreach ($counts->fetchAll() as $row) {
    $countMap[$row['status']] = $row['cnt'];
    $countMap['all'] += $row['cnt'];
}

require_once __DIR__ . '/../includes/header.php';
?>

<div class="page-header">
    <div>
        <h1 class="page-title"><i class="fas fa-calendar-check me-2" style="color:var(--accent);"></i>My Bookings</h1>
        <p class="page-subtitle">Track and manage your charging sessions</p>
    </div>
    <a href="<?= BASE_URL ?>/user/index.php" class="btn-ev btn-ev-primary">
        <i class="fas fa-plus"></i> New Booking
    </a>
</div>

<?php if ($msg): ?>
    <div class="ev-alert ev-alert-<?= $msgType ?>">
        <i class="fas fa-<?= $msgType === 'success' ? 'circle-check' : 'circle-exclamation' ?>"></i>
        <?= htmlspecialchars($msg) ?>
    </div>
<?php endif; ?>

<!-- Filter Tabs -->
<div style="display:flex; gap:.5rem; margin-bottom:1.5rem; flex-wrap:wrap;">
    <?php
    $tabs = [
        'upcoming'  => ['label' => 'Upcoming', 'icon' => 'clock'],
        'active'    => ['label' => 'Active',   'icon' => 'bolt'],
        'completed' => ['label' => 'Completed','icon' => 'circle-check'],
        'cancelled' => ['label' => 'Cancelled','icon' => 'ban'],
        'all'       => ['label' => 'All',      'icon' => 'list'],
    ];
    foreach ($tabs as $key => $tab):
        $active = $filter === $key;
        $count  = $countMap[$key] ?? 0;
    ?>
        <a href="?filter=<?= $key ?>"
           class="btn-ev <?= $active ? 'btn-ev-primary' : 'btn-ev-secondary' ?> btn-ev-sm">
            <i class="fas fa-<?= $tab['icon'] ?>"></i>
            <?= $tab['label'] ?>
            <?php if ($count > 0): ?>
                <span style="background:rgba(0,0,0,0.2); padding:1px 7px; border-radius:999px; font-size:.7rem;">
                    <?= $count ?>
                </span>
            <?php endif; ?>
        </a>
    <?php endforeach; ?>
</div>

<?php if (empty($bookings)): ?>
    <div class="empty-state" style="padding:4rem 2rem;">
        <i class="fas fa-calendar-xmark"></i>
        <h4>No <?= $filter !== 'all' ? $filter : '' ?> bookings</h4>
        <p><?= $filter === 'upcoming' ? 'Book a charging slot to get started.' : 'Nothing to show here.' ?></p>
        <?php if ($filter === 'upcoming'): ?>
            <a href="<?= BASE_URL ?>/user/index.php" class="btn-ev btn-ev-primary" style="margin-top:1rem;">
                <i class="fas fa-bolt"></i> Find Stations
            </a>
        <?php endif; ?>
    </div>
<?php else: ?>
    <div style="display:flex; flex-direction:column; gap:1rem;">
        <?php foreach ($bookings as $b):
            $isPast       = strtotime($b['booking_date']) < strtotime(date('Y-m-d'));
            $isToday      = $b['booking_date'] === date('Y-m-d');
            $canCancel    = in_array($b['status'], ['upcoming', 'active']);
        ?>
        <div class="ev-card" style="display:grid; grid-template-columns:auto 1fr auto; gap:1.25rem; align-items:center; flex-wrap:wrap;">
            <!-- Date block -->
            <div style="text-align:center; min-width:64px; padding:.5rem; background:var(--bg-elevated); border-radius:var(--radius-sm); border:1px solid var(--border);">
                <div style="font-family:var(--font-display); font-size:1.6rem; font-weight:800; line-height:1; color:var(--accent);">
                    <?= date('d', strtotime($b['booking_date'])) ?>
                </div>
                <div style="font-size:.7rem; text-transform:uppercase; letter-spacing:.06em; color:var(--text-muted); font-weight:700;">
                    <?= date('M Y', strtotime($b['booking_date'])) ?>
                </div>
                <?php if ($isToday): ?>
                    <div style="font-size:.62rem; color:var(--accent); font-weight:700; font-family:var(--font-display); margin-top:2px;">TODAY</div>
                <?php endif; ?>
            </div>

            <!-- Info -->
            <div>
                <div style="display:flex; align-items:center; gap:.75rem; flex-wrap:wrap; margin-bottom:.4rem;">
                    <h4 style="font-family:var(--font-display); font-weight:700; font-size:.95rem; margin:0;">
                        <?= htmlspecialchars($b['station_name']) ?>
                    </h4>
                    <span class="status-badge status-<?= $b['status'] ?>"><?= ucfirst($b['status']) ?></span>
                </div>
                <div style="display:flex; flex-wrap:wrap; gap:1rem; font-size:.84rem; color:var(--text-muted);">
                    <span><i class="fas fa-clock me-1"></i><?= date('H:i', strtotime($b['start_time'])) ?> – <?= date('H:i', strtotime($b['end_time'])) ?></span>
                    <span><i class="fas fa-plug me-1"></i><?= htmlspecialchars($b['connector_type']) ?> · <?= $b['power_kw'] ?>kW</span>
                    <span><i class="fas fa-location-dot me-1"></i><?= htmlspecialchars($b['station_location']) ?></span>
                    <?php if ($b['vehicle_number']): ?>
                        <span><i class="fas fa-car me-1"></i><?= htmlspecialchars($b['vehicle_number']) ?></span>
                    <?php endif; ?>
                </div>
                <?php if ($b['notes']): ?>
                    <div style="font-size:.8rem; color:var(--text-faint); margin-top:.4rem; font-style:italic;">
                        "<?= htmlspecialchars($b['notes']) ?>"
                    </div>
                <?php endif; ?>
                <div style="font-size:.75rem; color:var(--text-faint); margin-top:.3rem;">
                    Booking #<?= $b['id'] ?> · Made <?= date('d M Y', strtotime($b['created_at'])) ?>
                </div>
            </div>

            <!-- Actions -->
            <div style="display:flex; flex-direction:column; gap:.5rem; align-items:flex-end;">
                <?php if ($canCancel): ?>
                    <form method="POST" onsubmit="return confirm('Cancel this booking?');">
                        <input type="hidden" name="action" value="cancel">
                        <input type="hidden" name="id" value="<?= $b['id'] ?>">
                        <button type="submit" class="btn-ev btn-ev-danger btn-ev-sm">
                            <i class="fas fa-xmark"></i> Cancel
                        </button>
                    </form>
                <?php endif; ?>
                <?php if ($b['status'] === 'upcoming' || ($b['status'] === 'cancelled' && !$isPast)): ?>
                    <a href="<?= BASE_URL ?>/user/book.php?station_id=<?= $b['station_id'] ?>"
                       class="btn-ev btn-ev-secondary btn-ev-sm">
                        <i class="fas fa-rotate-right"></i> Rebook
                    </a>
                <?php endif; ?>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
<?php endif; ?>

</div>
<script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.2/js/bootstrap.bundle.min.js"></script>
</body>
</html>