<?php
require_once __DIR__ . '/../includes/auth.php';
requireUser();

$stationId = (int)($_GET['station_id'] ?? 0);
if (!$stationId) {
    header('Location: ' . BASE_URL . '/user/index.php');
    exit();
}

// Load station
$stmt = $pdo->prepare("SELECT * FROM stations WHERE id = ? AND status = 'active'");
$stmt->execute([$stationId]);
$station = $stmt->fetch();
if (!$station) {
    header('Location: ' . BASE_URL . '/user/index.php');
    exit();
}

// Load available connectors
$connectors = $pdo->prepare("SELECT * FROM connectors WHERE station_id = ? AND status = 'available' ORDER BY connector_type");
$connectors->execute([$stationId]);
$connectors = $connectors->fetchAll();

$pageTitle = 'Book — ' . $station['name'];
$errors    = [];
$success   = false;
$bookingId = null;
$conflictMsg = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $connectorId   = (int)$_POST['connector_id'];
    $bookingDate   = $_POST['booking_date'] ?? '';
    $startTime     = $_POST['start_time'] ?? '';
    $endTime       = $_POST['end_time'] ?? '';
    $vehicleNumber = trim($_POST['vehicle_number'] ?? '');
    $notes         = trim($_POST['notes'] ?? '');

    // Validate
    if (!$connectorId)    $errors[] = 'Please select a connector.';
    if (!$bookingDate)    $errors[] = 'Please select a date.';
    if (!$startTime)      $errors[] = 'Please set a start time.';
    if (!$endTime)        $errors[] = 'Please set an end time.';

    if ($bookingDate && strtotime($bookingDate) < strtotime(date('Y-m-d'))) {
        $errors[] = 'Booking date cannot be in the past.';
    }

    if ($startTime && $endTime && $startTime >= $endTime) {
        $errors[] = 'End time must be after start time.';
    }

    if (empty($errors)) {
        // Conflict check
        $conflict = $pdo->prepare("
            SELECT id FROM bookings
            WHERE connector_id = ?
              AND booking_date = ?
              AND status NOT IN ('cancelled')
              AND NOT (end_time <= ? OR start_time >= ?)
        ");
        $conflict->execute([$connectorId, $bookingDate, $startTime, $endTime]);

        if ($conflict->fetch()) {
            $conflictMsg = 'This connector is already booked for the selected time slot. Please choose a different time or connector.';
        } else {
            // Verify connector belongs to this station
            $checkConn = $pdo->prepare("SELECT id FROM connectors WHERE id = ? AND station_id = ?");
            $checkConn->execute([$connectorId, $stationId]);
            if (!$checkConn->fetch()) {
                $errors[] = 'Invalid connector selected.';
            } else {
                $insert = $pdo->prepare("
                    INSERT INTO bookings (user_id, connector_id, station_id, booking_date, start_time, end_time, vehicle_number, notes, status)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'upcoming')
                ");
                $insert->execute([
                    $_SESSION['user_id'], $connectorId, $stationId,
                    $bookingDate, $startTime, $endTime,
                    $vehicleNumber, $notes
                ]);
                $bookingId = $pdo->lastInsertId();
                $success   = true;
            }
        }
    }
}

require_once __DIR__ . '/../includes/header.php';
?>

<div class="page-header">
    <div>
        <h1 class="page-title"><i class="fas fa-bolt me-2" style="color:var(--accent);"></i>Book a Slot</h1>
        <p class="page-subtitle">
            <a href="<?= BASE_URL ?>/user/index.php" style="color:var(--accent); text-decoration:none;">← Back to stations</a>
        </p>
    </div>
</div>

<?php if ($success): ?>
    <div class="ev-alert ev-alert-success" style="font-size:.95rem; padding:1.2rem 1.5rem;">
        <div>
            <i class="fas fa-circle-check" style="font-size:1.2rem;"></i>
        </div>
        <div>
            <strong style="font-family:var(--font-display);">Booking confirmed!</strong><br>
            <span style="font-size:.88rem;">Your slot has been reserved. Booking ID: <strong>#<?= $bookingId ?></strong></span><br>
            <div style="margin-top:.75rem; display:flex; gap:.75rem; flex-wrap:wrap;">
                <a href="<?= BASE_URL ?>/user/my_bookings.php" class="btn-ev btn-ev-primary btn-ev-sm">
                    <i class="fas fa-calendar-check"></i> View My Bookings
                </a>
                <a href="<?= BASE_URL ?>/user/index.php" class="btn-ev btn-ev-secondary btn-ev-sm">
                    Book Another
                </a>
            </div>
        </div>
    </div>
<?php else: ?>

<div style="display:grid; grid-template-columns: 1fr 340px; gap:1.5rem; align-items:start;">
    <div>
        <!-- Errors -->
        <?php if (!empty($errors)): ?>
            <div class="ev-alert ev-alert-error">
                <i class="fas fa-circle-exclamation"></i>
                <div><?= implode('<br>', array_map('htmlspecialchars', $errors)) ?></div>
            </div>
        <?php endif; ?>

        <?php if ($conflictMsg): ?>
            <div class="ev-alert ev-alert-warning">
                <i class="fas fa-triangle-exclamation"></i>
                <?= htmlspecialchars($conflictMsg) ?>
            </div>
        <?php endif; ?>

        <div class="form-section">
            <div class="form-section-title">
                <i class="fas fa-plug"></i> Booking Details
            </div>
            <form method="POST" id="bookingForm">
                <div style="display:grid; grid-template-columns:1fr 1fr; gap:1.1rem; margin-bottom:1.1rem;">
                    <div style="grid-column:1/-1;">
                        <label class="ev-label">Select Connector *</label>
                        <?php if (empty($connectors)): ?>
                            <div class="ev-alert ev-alert-warning"><i class="fas fa-triangle-exclamation"></i> No available connectors at this station.</div>
                        <?php else: ?>
                            <div style="display:flex; flex-direction:column; gap:.6rem;" id="connectorOptions">
                                <?php foreach ($connectors as $c): ?>
                                    <label style="display:flex; align-items:center; gap:.75rem; padding:.9rem 1rem; background:var(--bg-elevated); border:1px solid var(--border); border-radius:var(--radius-sm); cursor:pointer; transition:all .2s;" class="connector-option">
                                        <input type="radio" name="connector_id" value="<?= $c['id'] ?>"
                                            <?= (isset($_POST['connector_id']) && $_POST['connector_id'] == $c['id']) ? 'checked' : '' ?>
                                            style="accent-color:var(--accent);">
                                        <div style="flex:1;">
                                            <span style="font-family:var(--font-display); font-weight:700;"><?= htmlspecialchars($c['connector_type']) ?></span>
                                            <span style="margin-left:.5rem; font-size:.85rem; color:var(--text-muted);"><?= $c['power_kw'] ?> kW</span>
                                        </div>
                                        <span class="status-badge status-available">Available</span>
                                    </label>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>

                    <div>
                        <label class="ev-label" for="booking_date">Date *</label>
                        <input class="ev-input" type="date" name="booking_date" id="booking_date"
                            value="<?= htmlspecialchars($_POST['booking_date'] ?? date('Y-m-d')) ?>"
                            min="<?= date('Y-m-d') ?>" required>
                    </div>
                    <div></div>

                    <div>
                        <label class="ev-label" for="start_time">Start Time *</label>
                        <input class="ev-input" type="time" name="start_time" id="start_time"
                            value="<?= htmlspecialchars($_POST['start_time'] ?? '') ?>" required>
                    </div>
                    <div>
                        <label class="ev-label" for="end_time">End Time *</label>
                        <input class="ev-input" type="time" name="end_time" id="end_time"
                            value="<?= htmlspecialchars($_POST['end_time'] ?? '') ?>" required>
                    </div>

                    <div>
                        <label class="ev-label" for="vehicle_number">Vehicle Number</label>
                        <input class="ev-input" type="text" name="vehicle_number" id="vehicle_number"
                            value="<?= htmlspecialchars($_POST['vehicle_number'] ?? '') ?>"
                            placeholder="e.g. KL 01 AB 1234">
                    </div>

                    <div style="grid-column:1/-1;">
                        <label class="ev-label" for="notes">Notes <span style="color:var(--text-faint);">(optional)</span></label>
                        <textarea class="ev-input" name="notes" id="notes" rows="2"
                            placeholder="Any special requirements…"
                            style="resize:vertical;"><?= htmlspecialchars($_POST['notes'] ?? '') ?></textarea>
                    </div>
                </div>

                <?php if (!empty($connectors)): ?>
                <button type="submit" class="btn-ev btn-ev-primary" style="width:100%; justify-content:center; padding:12px;">
                    <i class="fas fa-bolt"></i> Confirm Booking
                </button>
                <?php endif; ?>
            </form>
        </div>
    </div>

    <!-- Sidebar Summary -->
    <div>
        <div class="form-section">
            <div class="form-section-title"><i class="fas fa-charging-station"></i> Station Info</div>
            <div class="booking-summary">
                <div class="booking-summary-row">
                    <span class="label">Station</span>
                    <span class="value" style="text-align:right; max-width:180px;"><?= htmlspecialchars($station['name']) ?></span>
                </div>
                <div class="booking-summary-row">
                    <span class="label">Location</span>
                    <span class="value"><?= htmlspecialchars($station['location']) ?></span>
                </div>
                <?php if ($station['address']): ?>
                <div class="booking-summary-row">
                    <span class="label">Address</span>
                    <span class="value" style="font-size:.82rem; text-align:right; max-width:180px;"><?= htmlspecialchars($station['address']) ?></span>
                </div>
                <?php endif; ?>
                <div class="booking-summary-row">
                    <span class="label">Available</span>
                    <span class="value" style="color:var(--accent);"><?= count($connectors) ?> connector<?= count($connectors) !== 1 ? 's' : '' ?></span>
                </div>
            </div>

            <div class="form-section-title" style="margin-top:1.5rem; margin-bottom:.75rem;"><i class="fas fa-circle-info"></i> Tips</div>
            <ul style="list-style:none; padding:0; margin:0; display:flex; flex-direction:column; gap:.6rem; color:var(--text-muted); font-size:.84rem;">
                <li><i class="fas fa-check me-2" style="color:var(--accent);"></i>Arrive 5 min before your slot</li>
                <li><i class="fas fa-check me-2" style="color:var(--accent);"></i>Cancel if plans change to free the slot</li>
                <li><i class="fas fa-check me-2" style="color:var(--accent);"></i>Minimum booking duration: 30 min</li>
            </ul>
        </div>
    </div>
</div>

<?php endif; ?>

</div>
<script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.2/js/bootstrap.bundle.min.js"></script>
<script>
// Highlight selected connector
document.querySelectorAll('.connector-option input').forEach(radio => {
    radio.addEventListener('change', () => {
        document.querySelectorAll('.connector-option').forEach(l => {
            l.style.borderColor = 'var(--border)';
            l.style.background  = 'var(--bg-elevated)';
        });
        if (radio.checked) {
            radio.closest('.connector-option').style.borderColor = 'var(--accent)';
            radio.closest('.connector-option').style.background  = 'var(--accent-dim)';
        }
    });
    if (radio.checked) {
        radio.closest('.connector-option').style.borderColor = 'var(--accent)';
        radio.closest('.connector-option').style.background  = 'var(--accent-dim)';
    }
});
</script>
</body>
</html>