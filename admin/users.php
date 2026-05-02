<?php
require_once __DIR__ . '/../includes/auth.php';
requireAdmin();
$pageTitle = 'Manage Users';

$msg = '';
$msgType = 'success';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $id     = (int)$_POST['id'];

    if ($action === 'toggle_role' && $id && $id !== (int)$_SESSION['user_id']) {
        $user = $pdo->prepare("SELECT role FROM users WHERE id=?");
        $user->execute([$id]);
        $user = $user->fetch();
        if ($user) {
            $newRole = $user['role'] === 'admin' ? 'user' : 'admin';
            $pdo->prepare("UPDATE users SET role=? WHERE id=?")->execute([$newRole, $id]);
            $msg = "User role changed to $newRole.";
        }
    } elseif ($action === 'delete' && $id && $id !== (int)$_SESSION['user_id']) {
        $pdo->prepare("DELETE FROM users WHERE id=?")->execute([$id]);
        $msg = 'User deleted.';
    }
}

$users = $pdo->query("
    SELECT u.*, 
        (SELECT COUNT(*) FROM bookings WHERE user_id = u.id) AS booking_count
    FROM users u
    ORDER BY u.created_at DESC
")->fetchAll();

require_once __DIR__ . '/../includes/header.php';
?>

<div class="page-header">
    <div>
        <h1 class="page-title"><i class="fas fa-users me-2" style="color:var(--accent);"></i>Users</h1>
        <p class="page-subtitle"><?= count($users) ?> registered users</p>
    </div>
</div>

<?php if ($msg): ?>
    <div class="ev-alert ev-alert-<?= $msgType ?>">
        <i class="fas fa-circle-check"></i><?= htmlspecialchars($msg) ?>
    </div>
<?php endif; ?>

<div class="ev-card" style="padding:0;">
    <div class="table-wrapper">
        <table class="ev-table">
            <thead>
                <tr>
                    <th>#</th>
                    <th>Name</th>
                    <th>Email</th>
                    <th>Phone</th>
                    <th>Role</th>
                    <th>Bookings</th>
                    <th>Joined</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($users as $u): ?>
                <tr>
                    <td style="color:var(--text-muted); font-size:.82rem;"><?= $u['id'] ?></td>
                    <td>
                        <div style="display:flex; align-items:center; gap:.6rem;">
                            <div style="width:32px;height:32px;border-radius:50%;background:var(--accent-dim);color:var(--accent);display:flex;align-items:center;justify-content:center;font-size:.8rem;font-weight:700;flex-shrink:0;">
                                <?= strtoupper(substr($u['name'], 0, 1)) ?>
                            </div>
                            <span style="font-weight:500;"><?= htmlspecialchars($u['name']) ?></span>
                            <?php if ($u['id'] == $_SESSION['user_id']): ?>
                                <span style="font-size:.7rem; color:var(--accent); font-weight:700;">(you)</span>
                            <?php endif; ?>
                        </div>
                    </td>
                    <td style="font-size:.88rem;"><?= htmlspecialchars($u['email']) ?></td>
                    <td style="color:var(--text-muted); font-size:.85rem;"><?= htmlspecialchars($u['phone'] ?: '—') ?></td>
                    <td>
                        <span class="badge-role <?= $u['role'] ?>"><?= ucfirst($u['role']) ?></span>
                    </td>
                    <td>
                        <span style="color:var(--accent); font-weight:700;"><?= $u['booking_count'] ?></span>
                    </td>
                    <td style="color:var(--text-muted); font-size:.85rem;"><?= date('d M Y', strtotime($u['created_at'])) ?></td>
                    <td>
                        <?php if ($u['id'] != $_SESSION['user_id']): ?>
                        <div style="display:flex; gap:.4rem;">
                            <form method="POST" style="display:inline;">
                                <input type="hidden" name="action" value="toggle_role">
                                <input type="hidden" name="id" value="<?= $u['id'] ?>">
                                <button type="submit" class="btn-ev btn-ev-warning btn-ev-sm"
                                    title="Toggle role">
                                    <i class="fas fa-user-gear"></i>
                                    <?= $u['role'] === 'admin' ? 'Make User' : 'Make Admin' ?>
                                </button>
                            </form>
                            <form method="POST" onsubmit="return confirm('Delete this user and all their bookings?');" style="display:inline;">
                                <input type="hidden" name="action" value="delete">
                                <input type="hidden" name="id" value="<?= $u['id'] ?>">
                                <button type="submit" class="btn-ev btn-ev-danger btn-ev-sm"><i class="fas fa-trash"></i></button>
                            </form>
                        </div>
                        <?php else: ?>
                            <span style="color:var(--text-faint); font-size:.8rem;">Current account</span>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

</div>
<script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.2/js/bootstrap.bundle.min.js"></script>
</body>
</html>