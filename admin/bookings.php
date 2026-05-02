<?php
require_once __DIR__ . '/../includes/auth.php';
requireAdmin();
$pageTitle = 'All Bookings';

$msg = '';

// Handle status update
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $id     = (int)$_POST['id'];

    if ($action === 'update_status' && $id) {
        $newStatus = $_POST['status'] ?? '';
        $allowed   = ['upcoming', 'active', 'completed', 'cancelled'];
        if (in_array($newStatus, $allowed)) {
            $pdo->prepare("UPDATE bookings SET status=? WHERE id=?")->execute([$newStatus, $id]);
            $msg = 'Booking status updated.';
        }
    } elseif ($action === 'delete' && $id) {
        $pdo->prepare("DELETE FROM bookings WHERE id=?")->execute([$id]);
        $msg = 'Booking deleted.';
    }
}

// Filters
$filterStatus  = $_GET['status'] ?? '';
$filterStation = (int)($_GET['station_id'] ?? 0);
$filterDate    = $_GET['date'] ?? '';

$where  = [];
$params = [];

if ($filterStatus) { $where[] = "b.status = ?"; $params[] = $filterStatus; }
if ($filterStation){ $where[] = "b.station_id = ?"; $params[] = $filterStation; }
if ($filterDate)   { $where[] = "b.booking_date = ?"; $params[] = $filterDate; }

$whereSQL = $where ? 'WHERE ' . implode(' AND ', $where) : '';

$bookings = $pdo->prepare("
    SELECT b.*, u.name AS user_name, u.email AS user_email,
           s.name AS station_name, c.connector_type, c.power_kw
    FROM bookings b
    JOIN users u ON b.user_id = u.id
    JOIN stations s ON b.station_id = s.id
    JOIN connectors c ON b.connector_id = c.id
    $whereSQL
    ORDER BY b.booking_date DESC, b.start_time DESC
");
$bookings->execute($params);
$bookings = $bookings->fetchAll();

$stations = $pdo->query("SELECT id, name FROM stations ORDER BY name")->fetchAll();

require_once __DIR__ . '/../includes/header.php';
?>

<div class="page-header">
    <div>
        <h1 class="page-title"><i class="fas fa-calendar-check me-2" style="color:var(--accent);"></i>All Bookings</h1>
        <p class="page-subtitle"><?= count($bookings) ?> booking<?= count($bookings) !== 1 ? 's' : '' ?> found</p>
    </div>
</div>

<!-- Filters -->
<div class="ev-card" style="margin-bottom:1.5rem; padding:1.25rem 1.5rem;">
    <form method="GET" style="display:flex; gap:1rem; flex-wrap:wrap; align-items:flex-end;">
        <div style="flex:1; min-width:140px;">
            <label class="ev-label">Status</label>
            <select class="ev-input" name="status">
                <option value="">All Statuses</option>
                <?php foreach (['upcoming','active','completed','cancelled'] as $s): ?>
                    <option value="<?= $s ?>" <?= $filterStatus === $s ? 'selected' : '' ?>><?= ucfirst($s) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div style="flex:1; min-width:160px;">
            <label class="ev-label">Station</label>
            <select class="ev-input" name="station_id">
                <option value="">All Stations</option>
                <?php foreach ($stations as $st): ?>
                    <option value="<?= $st['id'] ?>" <?= $filterStation == $st['id'] ? 'selected' : '' ?>><?= htmlspecialchars($st['name']) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div style="flex:1; min-width:140px;">
            <label class="ev-label">Date</label>
            <input class="ev-input" type="date" name="date" value="<?= htmlspecialchars($filterDate) ?>">
        </div>
        <div style="display:flex; gap:.5rem;">
            <button type="submit" class="btn-ev btn-ev-primary"><i class="fas fa-filter"></i> Filter</button>
            <a href="<?= BASE_URL ?>/admin/bookings.php" class="btn-ev btn-ev-secondary"><i class="fas fa-xmark"></i> Clear</a>
        </div>
    </form>
</div>

<?php if ($msg): ?>
    <div class="ev-alert ev-alert-success"><i class="fas fa-circle-check"></i><?= htmlspecialchars($msg) ?></div>
<?php endif; ?>

<div class="ev-card" style="padding:0;">
    <?php if (empty($bookings)): ?>
        <div class="empty-state">
            <i class="fas fa-calendar-xmark"></i>
            <h4>No bookings found</h4>
            <p>Try adjusting your filters.</p>
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
                        <th>Time</th>
                        <th>Vehicle</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($bookings as $b): ?>
                    <tr>
                        <td style="color:var(--text-muted); font-size:.82rem;">#<?= $b['id'] ?></td>
                        <td>
                            <div style="font-weight:500;"><?= htmlspecialchars($b['user_name']) ?></div>
                            <div style="font-size:.78rem; color:var(--text-muted);"><?= htmlspecialchars($b['user_email']) ?></div>
                        </td>
                        <td style="font-size:.88rem;"><?= htmlspecialchars($b['station_name']) ?></td>
                        <td>
                            <span class="connector-chip available"><?= htmlspecialchars($b['connector_type']) ?></span>
                            <span style="color:var(--text-muted); font-size:.78rem;"> <?= $b['power_kw'] ?>kW</span>
                        </td>
                        <td style="font-size:.88rem; white-space:nowrap;"><?= date('d M Y', strtotime($b['booking_date'])) ?></td>
                        <td style="font-size:.88rem; white-space:nowrap;">
                            <?= date('H:i', strtotime($b['start_time'])) ?> – <?= date('H:i', strtotime($b['end_time'])) ?>
                        </td>
                        <td style="color:var(--text-muted); font-size:.85rem;"><?= htmlspecialchars($b['vehicle_number'] ?: '—') ?></td>
                        <td><span class="status-badge status-<?= $b['status'] ?>"><?= ucfirst($b['status']) ?></span></td>
                        <td>
                            <div style="display:flex; gap:.4rem; flex-wrap:wrap;">
                                <form method="POST" style="display:flex; gap:.4rem; align-items:center;">
                                    <input type="hidden" name="action" value="update_status">
                                    <input type="hidden" name="id" value="<?= $b['id'] ?>">
                                    <select class="ev-input" name="status" style="padding:4px 8px; font-size:.78rem; width:auto;">
                                        <?php foreach (['upcoming','active','completed','cancelled'] as $s): ?>
                                            <option value="<?= $s ?>" <?= $b['status'] === $s ? 'selected' : '' ?>><?= ucfirst($s) ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                    <button type="submit" class="btn-ev btn-ev-secondary btn-ev-sm"><i class="fas fa-check"></i></button>
                                </form>
                                <form method="POST" onsubmit="return confirm('Delete this booking?');" style="display:inline;">
                                    <input type="hidden" name="action" value="delete">
                                    <input type="hidden" name="id" value="<?= $b['id'] ?>">
                                    <button type="submit" class="btn-ev btn-ev-danger btn-ev-sm"><i class="fas fa-trash"></i></button>
                                </form>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>

</div>
<script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.2/js/bootstrap.bundle.min.js"></script>
</body>
</html>