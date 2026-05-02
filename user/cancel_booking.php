<?php
require_once __DIR__ . '/../includes/auth.php';
requireUser();

$id = (int)($_GET['id'] ?? 0);

if (!$id) {
    header('Location: ' . BASE_URL . '/user/my_bookings.php');
    exit();
}

// Verify ownership and cancellable status
$stmt = $pdo->prepare("SELECT * FROM bookings WHERE id = ? AND user_id = ?");
$stmt->execute([$id, $_SESSION['user_id']]);
$booking = $stmt->fetch();

if (!$booking) {
    header('Location: ' . BASE_URL . '/user/my_bookings.php?msg=notfound');
    exit();
}

if (!in_array($booking['status'], ['upcoming', 'active'])) {
    header('Location: ' . BASE_URL . '/user/my_bookings.php?msg=cannotcancel');
    exit();
}

// Confirm page or process
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['confirm'] ?? '') === 'yes') {
    $pdo->prepare("UPDATE bookings SET status='cancelled' WHERE id=?")->execute([$id]);
    header('Location: ' . BASE_URL . '/user/my_bookings.php?filter=cancelled&msg=cancelled');
    exit();
}

// Load station & connector info for display
$stmt = $pdo->prepare("
    SELECT b.*, s.name AS station_name, s.location AS station_location,
           c.connector_type, c.power_kw
    FROM bookings b
    JOIN stations s ON b.station_id = s.id
    JOIN connectors c ON b.connector_id = c.id
    WHERE b.id = ?
");
$stmt->execute([$id]);
$detail = $stmt->fetch();

$pageTitle = 'Cancel Booking';
require_once __DIR__ . '/../includes/header.php';
?>

<div class="page-header">
    <div>
        <h1 class="page-title" style="color:var(--danger);">
            <i class="fas fa-ban me-2"></i>Cancel Booking
        </h1>
        <p class="page-subtitle">
            <a href="<?= BASE_URL ?>/user/my_bookings.php" style="color:var(--accent); text-decoration:none;">← Back to My Bookings</a>
        </p>
    </div>
</div>

<div style="max-width:540px;">
    <div class="ev-alert ev-alert-warning" style="margin-bottom:1.5rem;">
        <i class="fas fa-triangle-exclamation"></i>
        <div>
            <strong>Are you sure?</strong><br>
            <span style="font-size:.88rem;">This action cannot be undone. The slot will be freed for others.</span>
        </div>
    </div>

    <div class="form-section" style="margin-bottom:1.5rem;">
        <div class="booking-summary">
            <div class="booking-summary-row">
                <span class="label">Booking ID</span>
                <span class="value">#<?= $detail['id'] ?></span>
            </div>
            <div class="booking-summary-row">
                <span class="label">Station</span>
                <span class="value"><?= htmlspecialchars($detail['station_name']) ?></span>
            </div>
            <div class="booking-summary-row">
                <span class="label">Date</span>
                <span class="value"><?= date('d M Y', strtotime($detail['booking_date'])) ?></span>
            </div>
            <div class="booking-summary-row">
                <span class="label">Time</span>
                <span class="value"><?= date('H:i', strtotime($detail['start_time'])) ?> – <?= date('H:i', strtotime($detail['end_time'])) ?></span>
            </div>
            <div class="booking-summary-row">
                <span class="label">Connector</span>
                <span class="value"><?= htmlspecialchars($detail['connector_type']) ?> · <?= $detail['power_kw'] ?>kW</span>
            </div>
        </div>
    </div>

    <div style="display:flex; gap:1rem;">
        <form method="POST" style="display:inline;">
            <input type="hidden" name="confirm" value="yes">
            <button type="submit" class="btn-ev btn-ev-danger" style="padding:10px 24px;">
                <i class="fas fa-ban"></i> Yes, Cancel Booking
            </button>
        </form>
        <a href="<?= BASE_URL ?>/user/my_bookings.php" class="btn-ev btn-ev-secondary" style="padding:10px 24px;">
            Keep Booking
        </a>
    </div>
</div>

</div>
<script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.2/js/bootstrap.bundle.min.js"></script>
</body>
</html>