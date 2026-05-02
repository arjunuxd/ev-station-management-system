<?php
require_once __DIR__ . '/../includes/auth.php';
requireAdmin();
$pageTitle = 'Manage Stations';

$msg = '';
$msgType = 'success';

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'add') {
        $name     = trim($_POST['name'] ?? '');
        $location = trim($_POST['location'] ?? '');
        $address  = trim($_POST['address'] ?? '');
        $status   = $_POST['status'] ?? 'active';

        if ($name && $location) {
            $stmt = $pdo->prepare("INSERT INTO stations (name, location, address, status) VALUES (?, ?, ?, ?)");
            $stmt->execute([$name, $location, $address, $status]);
            $msg = 'Station added successfully.';
        } else {
            $msg = 'Name and location are required.';
            $msgType = 'error';
        }
    } elseif ($action === 'edit') {
        $id       = (int)$_POST['id'];
        $name     = trim($_POST['name'] ?? '');
        $location = trim($_POST['location'] ?? '');
        $address  = trim($_POST['address'] ?? '');
        $status   = $_POST['status'] ?? 'active';

        if ($name && $location && $id) {
            $stmt = $pdo->prepare("UPDATE stations SET name=?, location=?, address=?, status=? WHERE id=?");
            $stmt->execute([$name, $location, $address, $status, $id]);
            $msg = 'Station updated successfully.';
        }
    } elseif ($action === 'delete') {
        $id = (int)$_POST['id'];
        if ($id) {
            $pdo->prepare("DELETE FROM stations WHERE id=?")->execute([$id]);
            $msg = 'Station deleted.';
        }
    }
}

$stations = $pdo->query("
    SELECT s.*, 
        COUNT(c.id) AS total_connectors,
        SUM(c.status = 'available') AS available_connectors
    FROM stations s
    LEFT JOIN connectors c ON s.id = c.station_id
    GROUP BY s.id
    ORDER BY s.created_at DESC
")->fetchAll();

require_once __DIR__ . '/../includes/header.php';
?>

<div class="page-header">
    <div>
        <h1 class="page-title"><i class="fas fa-charging-station me-2" style="color:var(--accent);"></i>Stations</h1>
        <p class="page-subtitle"><?= count($stations) ?> stations in your network</p>
    </div>
    <button class="btn-ev btn-ev-primary" data-bs-toggle="modal" data-bs-target="#addModal">
        <i class="fas fa-plus"></i> Add Station
    </button>
</div>

<?php if ($msg): ?>
    <div class="ev-alert ev-alert-<?= $msgType ?>">
        <i class="fas fa-<?= $msgType === 'success' ? 'circle-check' : 'circle-exclamation' ?>"></i>
        <?= htmlspecialchars($msg) ?>
    </div>
<?php endif; ?>

<div class="ev-card" style="padding:0;">
    <?php if (empty($stations)): ?>
        <div class="empty-state">
            <i class="fas fa-charging-station"></i>
            <h4>No stations yet</h4>
            <p>Add your first charging station to get started.</p>
        </div>
    <?php else: ?>
        <div class="table-wrapper">
            <table class="ev-table">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Station Name</th>
                        <th>Location</th>
                        <th>Address</th>
                        <th>Connectors</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($stations as $s): ?>
                    <tr>
                        <td style="color:var(--text-muted); font-size:.82rem;"><?= $s['id'] ?></td>
                        <td>
                            <strong style="font-family:var(--font-display);"><?= htmlspecialchars($s['name']) ?></strong>
                        </td>
                        <td><?= htmlspecialchars($s['location']) ?></td>
                        <td style="color:var(--text-muted); font-size:.85rem; max-width:180px;"><?= htmlspecialchars($s['address']) ?></td>
                        <td>
                            <span style="color:var(--accent); font-weight:700;"><?= $s['available_connectors'] ?></span>
                            <span style="color:var(--text-muted);">/<?= $s['total_connectors'] ?> available</span>
                        </td>
                        <td><span class="status-badge status-<?= $s['status'] ?>"><?= ucfirst($s['status']) ?></span></td>
                        <td>
                            <div style="display:flex; gap:.5rem; align-items:center; flex-wrap:wrap;">
                                <a href="<?= BASE_URL ?>/admin/connectors.php?station_id=<?= $s['id'] ?>" 
                                   class="btn-ev btn-ev-secondary btn-ev-sm">
                                    <i class="fas fa-plug"></i> Connectors
                                </a>
                                <button class="btn-ev btn-ev-warning btn-ev-sm"
                                    onclick='openEditModal(<?= json_encode($s) ?>)'>
                                    <i class="fas fa-pen"></i> Edit
                                </button>
                                <form method="POST" onsubmit="return confirm('Delete this station and all its connectors?');" style="display:inline;">
                                    <input type="hidden" name="action" value="delete">
                                    <input type="hidden" name="id" value="<?= $s['id'] ?>">
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
                <h5 class="modal-title"><i class="fas fa-plus me-2" style="color:var(--accent);"></i>Add New Station</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST">
                <input type="hidden" name="action" value="add">
                <div class="modal-body" style="display:flex; flex-direction:column; gap:1rem; padding:1.5rem;">
                    <div>
                        <label class="ev-label">Station Name *</label>
                        <input class="ev-input" type="text" name="name" placeholder="e.g. Central Hub Station" required>
                    </div>
                    <div>
                        <label class="ev-label">Location / Zone *</label>
                        <input class="ev-input" type="text" name="location" placeholder="e.g. Downtown" required>
                    </div>
                    <div>
                        <label class="ev-label">Full Address</label>
                        <input class="ev-input" type="text" name="address" placeholder="Street, City">
                    </div>
                    <div>
                        <label class="ev-label">Status</label>
                        <select class="ev-input" name="status">
                            <option value="active">Active</option>
                            <option value="inactive">Inactive</option>
                            <option value="maintenance">Maintenance</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn-ev btn-ev-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn-ev btn-ev-primary"><i class="fas fa-plus"></i> Add Station</button>
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
                <h5 class="modal-title"><i class="fas fa-pen me-2" style="color:var(--warning);"></i>Edit Station</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST">
                <input type="hidden" name="action" value="edit">
                <input type="hidden" name="id" id="edit_id">
                <div class="modal-body" style="display:flex; flex-direction:column; gap:1rem; padding:1.5rem;">
                    <div>
                        <label class="ev-label">Station Name *</label>
                        <input class="ev-input" type="text" name="name" id="edit_name" required>
                    </div>
                    <div>
                        <label class="ev-label">Location / Zone *</label>
                        <input class="ev-input" type="text" name="location" id="edit_location" required>
                    </div>
                    <div>
                        <label class="ev-label">Full Address</label>
                        <input class="ev-input" type="text" name="address" id="edit_address">
                    </div>
                    <div>
                        <label class="ev-label">Status</label>
                        <select class="ev-input" name="status" id="edit_status">
                            <option value="active">Active</option>
                            <option value="inactive">Inactive</option>
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
function openEditModal(s) {
    document.getElementById('edit_id').value       = s.id;
    document.getElementById('edit_name').value     = s.name;
    document.getElementById('edit_location').value = s.location;
    document.getElementById('edit_address').value  = s.address || '';
    document.getElementById('edit_status').value   = s.status;
    new bootstrap.Modal(document.getElementById('editModal')).show();
}
</script>
</body>
</html>