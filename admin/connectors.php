<?php
require_once __DIR__ . '/../includes/auth.php';
requireAdmin();

$stationId = (int)($_GET['station_id'] ?? 0);
$station   = null;

if ($stationId) {
    $stmt = $pdo->prepare("SELECT * FROM stations WHERE id = ?");
    $stmt->execute([$stationId]);
    $station = $stmt->fetch();
}

$pageTitle = $station ? 'Connectors — ' . $station['name'] : 'Manage Connectors';
$msg = '';
$msgType = 'success';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'add') {
        $sid   = (int)$_POST['station_id'];
        $type  = $_POST['connector_type'] ?? '';
        $power = (float)$_POST['power_kw'];
        $status= $_POST['status'] ?? 'available';

        if ($sid && $type && $power > 0) {
            $stmt = $pdo->prepare("INSERT INTO connectors (station_id, connector_type, power_kw, status) VALUES (?, ?, ?, ?)");
            $stmt->execute([$sid, $type, $power, $status]);
            $msg = 'Connector added.';
            if (!$stationId) $stationId = $sid;
        } else {
            $msg = 'All fields are required.'; $msgType = 'error';
        }
    } elseif ($action === 'edit') {
        $id    = (int)$_POST['id'];
        $type  = $_POST['connector_type'] ?? '';
        $power = (float)$_POST['power_kw'];
        $status= $_POST['status'] ?? 'available';

        if ($id && $type && $power > 0) {
            $stmt = $pdo->prepare("UPDATE connectors SET connector_type=?, power_kw=?, status=? WHERE id=?");
            $stmt->execute([$type, $power, $status, $id]);
            $msg = 'Connector updated.';
        }
    } elseif ($action === 'delete') {
        $id = (int)$_POST['id'];
        if ($id) {
            $pdo->prepare("DELETE FROM connectors WHERE id=?")->execute([$id]);
            $msg = 'Connector deleted.';
        }
    }
}

$allStations = $pdo->query("SELECT * FROM stations ORDER BY name")->fetchAll();

if ($stationId) {
    $stmt = $pdo->prepare("SELECT c.*, s.name AS station_name FROM connectors c JOIN stations s ON c.station_id = s.id WHERE c.station_id = ? ORDER BY c.id DESC");
    $stmt->execute([$stationId]);
} else {
    $stmt = $pdo->query("SELECT c.*, s.name AS station_name FROM connectors c JOIN stations s ON c.station_id = s.id ORDER BY s.name, c.id DESC");
}
$connectors = $stmt->fetchAll();

$connectorTypes = ['Type 1', 'Type 2', 'CCS', 'CHAdeMO', 'GB/T'];

require_once __DIR__ . '/../includes/header.php';
?>

<div class="page-header">
    <div>
        <h1 class="page-title">
            <i class="fas fa-plug me-2" style="color:var(--accent);"></i>
            <?= $station ? htmlspecialchars($station['name']) . ' — Connectors' : 'All Connectors' ?>
        </h1>
        <p class="page-subtitle">
            <?php if ($station): ?>
                <a href="<?= BASE_URL ?>/admin/stations.php" style="color:var(--accent); text-decoration:none;">← Back to stations</a>
                &nbsp;·&nbsp; <?= count($connectors) ?> connectors
            <?php else: ?>
                Manage connectors across all stations
            <?php endif; ?>
        </p>
    </div>
    <button class="btn-ev btn-ev-primary" data-bs-toggle="modal" data-bs-target="#addModal">
        <i class="fas fa-plus"></i> Add Connector
    </button>
</div>

<?php if (!$stationId): ?>
<div style="margin-bottom:1.5rem; display:flex; gap:.75rem; flex-wrap:wrap; align-items:center;">
    <span style="color:var(--text-muted); font-size:.85rem;">Filter by station:</span>
    <a href="<?= BASE_URL ?>/admin/connectors.php" 
       class="btn-ev btn-ev-sm <?= !$stationId ? 'btn-ev-primary' : 'btn-ev-secondary' ?>">All</a>
    <?php foreach ($allStations as $st): ?>
        <a href="<?= BASE_URL ?>/admin/connectors.php?station_id=<?= $st['id'] ?>" 
           class="btn-ev btn-ev-sm btn-ev-secondary">
            <?= htmlspecialchars($st['name']) ?>
        </a>
    <?php endforeach; ?>
</div>
<?php endif; ?>

<?php if ($msg): ?>
    <div class="ev-alert ev-alert-<?= $msgType ?>">
        <i class="fas fa-<?= $msgType === 'success' ? 'circle-check' : 'circle-exclamation' ?>"></i>
        <?= htmlspecialchars($msg) ?>
    </div>
<?php endif; ?>

<div class="ev-card" style="padding:0;">
    <?php if (empty($connectors)): ?>
        <div class="empty-state">
            <i class="fas fa-plug"></i>
            <h4>No connectors found</h4>
            <p>Add connectors to your stations to enable bookings.</p>
        </div>
    <?php else: ?>
        <div class="table-wrapper">
            <table class="ev-table">
                <thead>
                    <tr>
                        <th>#</th>
                        <?php if (!$stationId): ?><th>Station</th><?php endif; ?>
                        <th>Type</th>
                        <th>Power</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($connectors as $c): ?>
                    <tr>
                        <td style="color:var(--text-muted); font-size:.82rem;"><?= $c['id'] ?></td>
                        <?php if (!$stationId): ?>
                        <td style="font-family:var(--font-display); font-size:.88rem;"><?= htmlspecialchars($c['station_name']) ?></td>
                        <?php endif; ?>
                        <td>
                            <?php
                                $typeClass = strtolower(str_replace([' ', '/'], ['-', ''], $c['connector_type']));
                                $classMap  = ['type-1'=>'type-type1','type-2'=>'type-type2','ccs'=>'type-ccs','chademo'=>'type-chademo','gbt'=>'type-gbt'];
                                $cls       = $classMap[$typeClass] ?? 'connector-chip';
                            ?>
                            <span class="connector-chip <?= $cls ?>"><?= htmlspecialchars($c['connector_type']) ?></span>
                        </td>
                        <td>
                            <strong style="color:var(--text-primary);"><?= $c['power_kw'] ?></strong>
                            <span style="color:var(--text-muted); font-size:.8rem;">kW</span>
                        </td>
                        <td><span class="status-badge status-<?= $c['status'] ?>"><?= ucfirst($c['status']) ?></span></td>
                        <td>
                            <div style="display:flex; gap:.5rem;">
                                <button class="btn-ev btn-ev-warning btn-ev-sm"
                                    onclick='openEditModal(<?= json_encode($c) ?>)'>
                                    <i class="fas fa-pen"></i> Edit
                                </button>
                                <form method="POST" onsubmit="return confirm('Delete this connector?');" style="display:inline;">
                                    <input type="hidden" name="action" value="delete">
                                    <input type="hidden" name="id" value="<?= $c['id'] ?>">
                                    <button type="submit" class="btn-ev btn-ev-danger btn-ev-sm">
                                        <i class="fas fa-trash"></i>
                                    </button>
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

<!-- Add Modal -->
<div class="modal fade" id="addModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="fas fa-plus me-2" style="color:var(--accent);"></i>Add Connector</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST">
                <input type="hidden" name="action" value="add">
                <div class="modal-body" style="display:flex; flex-direction:column; gap:1rem; padding:1.5rem;">
                    <div>
                        <label class="ev-label">Station *</label>
                        <select class="ev-input" name="station_id" required>
                            <option value="">Select station...</option>
                            <?php foreach ($allStations as $st): ?>
                                <option value="<?= $st['id'] ?>" <?= $stationId == $st['id'] ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($st['name']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div>
                        <label class="ev-label">Connector Type *</label>
                        <select class="ev-input" name="connector_type" required>
                            <?php foreach ($connectorTypes as $t): ?>
                                <option value="<?= $t ?>"><?= $t ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div>
                        <label class="ev-label">Power (kW) *</label>
                        <input class="ev-input" type="number" name="power_kw" step="0.01" min="1" max="350" placeholder="e.g. 50" required>
                    </div>
                    <div>
                        <label class="ev-label">Status</label>
                        <select class="ev-input" name="status">
                            <option value="available">Available</option>
                            <option value="occupied">Occupied</option>
                            <option value="maintenance">Maintenance</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn-ev btn-ev-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn-ev btn-ev-primary"><i class="fas fa-plus"></i> Add Connector</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Edit Modal -->
<div class="modal fade" id="editModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="fas fa-pen me-2" style="color:var(--warning);"></i>Edit Connector</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST">
                <input type="hidden" name="action" value="edit">
                <input type="hidden" name="id" id="edit_id">
                <div class="modal-body" style="display:flex; flex-direction:column; gap:1rem; padding:1.5rem;">
                    <div>
                        <label class="ev-label">Connector Type *</label>
                        <select class="ev-input" name="connector_type" id="edit_type" required>
                            <?php foreach ($connectorTypes as $t): ?>
                                <option value="<?= $t ?>"><?= $t ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div>
                        <label class="ev-label">Power (kW) *</label>
                        <input class="ev-input" type="number" name="power_kw" id="edit_power" step="0.01" min="1" max="350" required>
                    </div>
                    <div>
                        <label class="ev-label">Status</label>
                        <select class="ev-input" name="status" id="edit_status">
                            <option value="available">Available</option>
                            <option value="occupied">Occupied</option>
                            <option value="maintenance">Maintenance</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn-ev btn-ev-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn-ev btn-ev-primary"><i class="fas fa-save"></i> Save Changes</button>
                </div>
            </form>
        </div>
    </div>
</div>

</div>
<script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.2/js/bootstrap.bundle.min.js"></script>
<script>
function openEditModal(c) {
    document.getElementById('edit_id').value     = c.id;
    document.getElementById('edit_type').value   = c.connector_type;
    document.getElementById('edit_power').value  = c.power_kw;
    document.getElementById('edit_status').value = c.status;
    new bootstrap.Modal(document.getElementById('editModal')).show();
}
</script>
</body>
</html>