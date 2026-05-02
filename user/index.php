<?php
require_once __DIR__ . '/../includes/auth.php';
requireUser();
$pageTitle = 'Charging Stations';

$search = trim($_GET['search'] ?? '');

$params = [];
$where  = "WHERE s.status = 'active'";
if ($search) {
    $where   .= " AND (s.name LIKE ? OR s.location LIKE ? OR s.address LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

$stmt = $pdo->prepare("
    SELECT s.*,
        COUNT(c.id) AS total_connectors,
        SUM(c.status = 'available') AS available_connectors,
        GROUP_CONCAT(DISTINCT c.connector_type ORDER BY c.connector_type SEPARATOR '|') AS connector_types
    FROM stations s
    LEFT JOIN connectors c ON s.id = c.station_id
    $where
    GROUP BY s.id
    ORDER BY available_connectors DESC, s.name
");
$stmt->execute($params);
$stations = $stmt->fetchAll();

require_once __DIR__ . '/../includes/header.php';
?>

<div class="page-header">
    <div>
        <h1 class="page-title"><i class="fas fa-charging-station me-2" style="color:var(--accent);"></i>Charging Stations</h1>
        <p class="page-subtitle">Find and book your nearest EV charging point</p>
    </div>
    <a href="<?= BASE_URL ?>/user/my_bookings.php" class="btn-ev btn-ev-secondary">
        <i class="fas fa-calendar-check"></i> My Bookings
    </a>
</div>

<!-- Search -->
<form method="GET" style="margin-bottom:1.75rem;">
    <div style="position:relative; max-width:460px;">
        <i class="fas fa-magnifying-glass" style="position:absolute; left:14px; top:50%; transform:translateY(-50%); color:var(--text-muted); font-size:.9rem;"></i>
        <input class="ev-input" type="text" name="search" value="<?= htmlspecialchars($search) ?>"
            placeholder="Search by name, location or address…"
            style="padding-left:40px;">
        <?php if ($search): ?>
            <a href="<?= BASE_URL ?>/user/index.php" style="position:absolute; right:12px; top:50%; transform:translateY(-50%); color:var(--text-muted); text-decoration:none;">
                <i class="fas fa-xmark"></i>
            </a>
        <?php endif; ?>
    </div>
</form>

<?php if (empty($stations)): ?>
    <div class="empty-state" style="padding:4rem 2rem;">
        <i class="fas fa-charging-station"></i>
        <h4><?= $search ? 'No stations match your search' : 'No active stations available' ?></h4>
        <p><?= $search ? 'Try different keywords.' : 'Check back soon.' ?></p>
    </div>
<?php else: ?>
    <div style="color:var(--text-muted); font-size:.82rem; margin-bottom:1rem; font-family:var(--font-display); text-transform:uppercase; letter-spacing:.06em;">
        <?= count($stations) ?> station<?= count($stations) !== 1 ? 's' : '' ?> found
    </div>
    <div class="station-grid">
        <?php foreach ($stations as $s):
            $types = $s['connector_types'] ? explode('|', $s['connector_types']) : [];
            $avail = (int)$s['available_connectors'];
            $total = (int)$s['total_connectors'];
        ?>
        <div class="station-card">
            <div class="station-card-header">
                <div class="station-icon"><i class="fas fa-bolt"></i></div>
                <span class="status-badge status-<?= $avail > 0 ? 'available' : 'occupied' ?>">
                    <?= $avail > 0 ? "$avail Available" : 'Full' ?>
                </span>
            </div>

            <h3 class="station-name"><?= htmlspecialchars($s['name']) ?></h3>
            <div class="station-location">
                <i class="fas fa-location-dot"></i>
                <?= htmlspecialchars($s['location']) ?>
                <?php if ($s['address']): ?>
                    &nbsp;·&nbsp; <span style="font-size:.78rem;"><?= htmlspecialchars($s['address']) ?></span>
                <?php endif; ?>
            </div>

            <div class="connector-list">
                <?php foreach ($types as $t): ?>
                    <span class="connector-chip available"><?= htmlspecialchars($t) ?></span>
                <?php endforeach; ?>
                <?php if (empty($types)): ?>
                    <span style="color:var(--text-faint); font-size:.8rem;">No connectors</span>
                <?php endif; ?>
            </div>

            <div style="display:flex; justify-content:space-between; align-items:center; margin-top:.25rem;">
                <span style="color:var(--text-muted); font-size:.82rem;">
                    <i class="fas fa-plug me-1"></i><?= $total ?> total connector<?= $total !== 1 ? 's' : '' ?>
                </span>
                <?php if ($avail > 0 && $total > 0): ?>
                    <a href="<?= BASE_URL ?>/user/book.php?station_id=<?= $s['id'] ?>" class="btn-ev btn-ev-primary btn-ev-sm">
                        <i class="fas fa-bolt"></i> Book Now
                    </a>
                <?php else: ?>
                    <span class="btn-ev btn-ev-secondary btn-ev-sm" style="opacity:.5; cursor:not-allowed;">
                        <i class="fas fa-ban"></i> Unavailable
                    </span>
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