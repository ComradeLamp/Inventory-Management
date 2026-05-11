<?php
session_start();
include 'db.php';

if (!isset($_SESSION['user']) || !isset($_SESSION['user']['id'])) {
    header("Location: login.php?message=" . urlencode("Please log in to view your reservations."));
    exit();
}

$user_id = $_SESSION['user']['id'];

// Handle cancellation
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['cancel_id'])) {
    $cancel_id = intval($_POST['cancel_id']);
    $conn->begin_transaction();
    try {
        $q = $conn->prepare("SELECT product_id, quantity FROM reservations WHERE id = ? AND user_id = ? AND status = 'pending'");
        $q->bind_param("ii", $cancel_id, $user_id);
        $q->execute();
        $res = $q->get_result()->fetch_assoc();

        if ($res) {
            $del = $conn->prepare("DELETE FROM reservations WHERE id = ? AND user_id = ?");
            $del->bind_param("ii", $cancel_id, $user_id);
            $del->execute();

            $upd = $conn->prepare("UPDATE products SET quantity = quantity + ? WHERE id = ?");
            $upd->bind_param("ii", $res['quantity'], $res['product_id']);
            $upd->execute();

            $conn->commit();
            $_SESSION['message'] = "Reservation cancelled successfully.";
        } else {
            throw new Exception("Reservation not found or already processed.");
        }
    } catch (Exception $e) {
        $conn->rollback();
        $_SESSION['error'] = $e->getMessage();
    }
    header("Location: reservations.php");
    exit();
}

// Fetch reservations
$stmt = $conn->prepare("
    SELECT r.id, r.quantity, r.status, r.reserved_at,
           p.id AS product_id, p.name, p.description, p.price, p.image
    FROM reservations r
    JOIN products p ON r.product_id = p.id
    WHERE r.user_id = ?
    ORDER BY r.reserved_at DESC
");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$reservations = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

$grand_total   = array_sum(array_map(fn($r) => $r['price'] * $r['quantity'], $reservations));
$pending_count = count(array_filter($reservations, fn($r) => strtolower($r['status']) === 'pending'));
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <title>My Reservations - Bicol Depot</title>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@700;900&family=DM+Sans:wght@300;400;500&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/style.css">
    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

        :root {
            --blue:    #2B5EAB;
            --blue-dk: #1A3F7A;
            --blue-lt: #E8F0FA;
            --gold:    #C9A84C;
            --beige:   #F5F0DC;
            --dark:    #0E1F3D;
            --mid:     #2C3A5E;
            --muted:   #6B7A99;
            --card-bg: rgba(255,255,255,0.85);
        }

        body {
            font-family: 'DM Sans', sans-serif;
            background: var(--beige);
            color: var(--dark);
            min-height: 100vh;
        }

        /* ── GEO BACKGROUND ── */
        .geo-fixed {
            position: fixed;
            inset: 0;
            z-index: 0;
            pointer-events: none;
            overflow: hidden;
        }

        /* ── NAVBAR ── */
        .navbar {
            background: rgba(245,240,220,0.92) !important;
            backdrop-filter: blur(14px);
            border-bottom: 1px solid rgba(43,94,171,0.1);
            padding: 0.9rem 0;
            position: sticky;
            top: 0;
            z-index: 999;
        }

        .navbar-brand {
            font-family: 'Playfair Display', serif;
            font-size: 1.4rem;
            font-weight: 900;
            color: var(--dark) !important;
            letter-spacing: -0.02em;
        }

        .navbar-brand span { color: var(--blue); }

        .nav-link {
            font-size: 0.88rem;
            font-weight: 500;
            color: var(--mid) !important;
            padding: 0.4rem 0.9rem !important;
            border-radius: 6px;
            transition: all 0.2s;
        }

        .nav-link:hover, .nav-link.active {
            color: var(--blue) !important;
            background: var(--blue-lt);
        }

        .btn-nav-cart {
            font-size: 0.82rem;
            font-weight: 500;
            color: var(--blue) !important;
            border: 1.5px solid rgba(43,94,171,0.35) !important;
            border-radius: 6px !important;
            padding: 0.38rem 1rem !important;
            transition: all 0.2s;
            text-decoration: none;
        }

        .btn-nav-cart:hover { background: var(--blue) !important; color: white !important; }

        .btn-nav-logout {
            font-size: 0.82rem;
            font-weight: 500;
            color: #922B21 !important;
            border: 1.5px solid rgba(146,43,33,0.3) !important;
            border-radius: 6px !important;
            padding: 0.38rem 1rem !important;
            transition: all 0.2s;
            text-decoration: none;
        }

        .btn-nav-logout:hover { background: #922B21 !important; color: white !important; }

        /* ── PAGE HEADER ── */
        .page-header {
            position: relative;
            z-index: 1;
            padding: 3.5rem 0 2.5rem;
            text-align: center;
        }

        .page-header-pill {
            display: inline-block;
            font-size: 0.7rem;
            font-weight: 500;
            letter-spacing: 0.2em;
            text-transform: uppercase;
            color: var(--blue);
            background: rgba(43,94,171,0.08);
            border: 1px solid rgba(43,94,171,0.2);
            border-radius: 999px;
            padding: 0.35rem 1rem;
            margin-bottom: 1rem;
        }

        .page-header h1 {
            font-family: 'Playfair Display', serif;
            font-size: clamp(2rem, 4vw, 3rem);
            font-weight: 900;
            color: var(--dark);
            margin-bottom: 0.5rem;
        }

        .page-header h1 span {
            color: var(--blue);
            position: relative;
        }

        .page-header h1 span::after {
            content: '';
            position: absolute;
            left: 0; right: 0; bottom: -4px;
            height: 3px;
            background: var(--gold);
            border-radius: 2px;
        }

        .page-header p {
            color: var(--muted);
            font-size: 0.95rem;
            font-weight: 300;
        }

        /* ── SUMMARY CARDS ── */
        .summary-card {
            background: var(--card-bg);
            border: 1px solid rgba(43,94,171,0.1);
            border-radius: 12px;
            padding: 1.2rem 1.4rem;
        }

        .summary-label {
            font-size: 0.72rem;
            font-weight: 500;
            letter-spacing: 0.12em;
            text-transform: uppercase;
            color: var(--muted);
            margin-bottom: 0.3rem;
        }

        .summary-value {
            font-family: 'Playfair Display', serif;
            font-size: 1.8rem;
            font-weight: 900;
            color: var(--dark);
            line-height: 1;
        }

        .summary-value.blue { color: var(--blue); }

        /* ── SECTION LABEL ── */
        .category-label {
            display: flex;
            align-items: center;
            gap: 0.7rem;
            margin-bottom: 1.4rem;
        }

        .category-label h3 {
            font-family: 'Playfair Display', serif;
            font-size: 1.35rem;
            font-weight: 900;
            color: var(--dark);
            margin: 0;
        }

        .cat-icon-badge {
            width: 36px; height: 36px;
            border-radius: 8px;
            background: var(--blue-lt);
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--blue);
            font-size: 1.1rem;
            flex-shrink: 0;
        }

        .cat-divider {
            flex: 1;
            height: 1px;
            background: rgba(43,94,171,0.12);
            margin-left: 0.5rem;
        }

        /* ── RESERVATION CARD ── */
        .res-card {
            background: var(--card-bg);
            border: 1px solid rgba(43,94,171,0.1);
            border-radius: 12px;
            overflow: hidden;
            transition: transform 0.25s, box-shadow 0.25s;
            height: 100%;
            display: flex;
            flex-direction: column;
        }

        .res-card:hover {
            transform: translateY(-6px);
            box-shadow: 0 16px 36px rgba(14,31,61,0.12);
        }

        .res-card.status-pending   { border-top: 3px solid var(--gold); }
        .res-card.status-approved  { border-top: 3px solid #1E8449; }
        .res-card.status-cancelled { border-top: 3px solid #9CA3AF; }

        .res-img {
            height: 170px;
            overflow: hidden;
            background: var(--blue-lt);
        }

        .res-img img {
            width: 100%; height: 100%;
            object-fit: cover;
            transition: transform 0.5s;
        }

        .res-card:hover .res-img img { transform: scale(1.06); }

        .res-img-placeholder {
            width: 100%; height: 100%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: rgba(43,94,171,0.25);
            font-size: 2.5rem;
        }

        .res-body {
            padding: 1.1rem 1.2rem 1.3rem;
            flex: 1;
            display: flex;
            flex-direction: column;
        }

        .res-title {
            font-family: 'Playfair Display', serif;
            font-size: 0.95rem;
            font-weight: 700;
            color: var(--dark);
            margin-bottom: 0.2rem;
            line-height: 1.35;
        }

        .res-desc {
            font-size: 0.78rem;
            color: var(--muted);
            line-height: 1.5;
            margin-bottom: 0.6rem;
        }

        .res-meta {
            font-size: 0.75rem;
            color: var(--muted);
            margin-bottom: 0.5rem;
        }

        .status-badge {
            display: inline-flex;
            align-items: center;
            gap: 5px;
            font-size: 0.68rem;
            font-weight: 600;
            letter-spacing: 0.08em;
            text-transform: uppercase;
            padding: 0.2rem 0.65rem;
            border-radius: 999px;
            margin-bottom: 0.75rem;
            width: fit-content;
        }

        .badge-pending   { background: #FEF3C7; color: #92400E; }
        .badge-approved  { background: #D1FAE5; color: #065F46; }
        .badge-cancelled { background: #F3F4F6; color: #4B5563; }

        .res-divider {
            border: none;
            border-top: 1px solid rgba(43,94,171,0.08);
            margin: 0.6rem 0;
        }

        .res-row {
            display: flex;
            justify-content: space-between;
            font-size: 0.8rem;
            color: var(--muted);
            margin-bottom: 0.25rem;
        }

        .res-row span:last-child { font-weight: 500; color: var(--dark); }

        .res-total {
            display: flex;
            justify-content: space-between;
            align-items: baseline;
            margin-top: 0.4rem;
        }

        .res-total-label { font-size: 0.8rem; font-weight: 600; color: var(--dark); }

        .res-total-value {
            font-family: 'Playfair Display', serif;
            font-size: 1.1rem;
            font-weight: 700;
            color: var(--blue);
        }

        /* ── CARD FOOTER ── */
        .res-footer {
            padding: 0.9rem 1.2rem;
            border-top: 1px solid rgba(43,94,171,0.07);
        }

        .btn-cancel {
            width: 100%;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.4rem;
            font-family: 'DM Sans', sans-serif;
            font-size: 0.82rem;
            font-weight: 500;
            color: #922B21;
            background: transparent;
            border: 1.5px solid rgba(146,43,33,0.3);
            border-radius: 6px;
            padding: 0.5rem 0.8rem;
            cursor: pointer;
            transition: all 0.2s;
        }

        .btn-cancel:hover { background: #922B21; color: white; border-color: #922B21; }

        .btn-disabled {
            width: 100%;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.4rem;
            font-size: 0.82rem;
            font-weight: 500;
            color: #9CA3AF;
            background: #F3F4F6;
            border: 1.5px solid #E5E7EB;
            border-radius: 6px;
            padding: 0.5rem 0.8rem;
            cursor: not-allowed;
        }

        /* ── EMPTY STATE ── */
        .empty-state {
            text-align: center;
            padding: 5rem 2rem;
        }

        .empty-icon {
            width: 80px; height: 80px;
            background: var(--blue-lt);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 1.2rem;
            font-size: 2rem;
            color: var(--blue);
        }

        .empty-state h3 {
            font-family: 'Playfair Display', serif;
            font-size: 1.5rem;
            font-weight: 900;
            color: var(--dark);
            margin-bottom: 0.5rem;
        }

        .empty-state p {
            color: var(--muted);
            font-size: 0.9rem;
            max-width: 340px;
            margin: 0 auto 1.5rem;
        }

        .btn-browse {
            display: inline-flex;
            align-items: center;
            gap: 0.4rem;
            font-family: 'DM Sans', sans-serif;
            font-size: 0.88rem;
            font-weight: 500;
            color: white;
            background: var(--blue);
            border: none;
            border-radius: 6px;
            padding: 0.6rem 1.4rem;
            text-decoration: none;
            transition: background 0.2s;
        }

        .btn-browse:hover { background: var(--blue-dk); color: white; }

        /* ── FOOTER ── */
        footer {
            position: relative;
            z-index: 1;
            background: var(--dark);
            color: rgba(255,255,255,0.5);
            padding: 2rem 0;
            font-size: 0.8rem;
            text-align: center;
            margin-top: 2rem;
        }

        footer strong { color: var(--gold); }

        .main-content { position: relative; z-index: 1; }
    </style>
</head>
<body>

<!-- GEO BACKGROUND -->
<div class="geo-fixed">
    <svg width="100%" height="100%" xmlns="http://www.w3.org/2000/svg">
        <defs>
            <pattern id="gHex" x="0" y="0" width="60" height="52" patternUnits="userSpaceOnUse">
                <polygon points="30,2 58,17 58,47 30,62 2,47 2,17" fill="none" stroke="rgba(43,94,171,0.06)" stroke-width="1"/>
            </pattern>
            <pattern id="gDots" x="0" y="0" width="40" height="40" patternUnits="userSpaceOnUse">
                <circle cx="20" cy="20" r="1.3" fill="rgba(201,168,76,0.18)"/>
            </pattern>
        </defs>
        <rect width="100%" height="100%" fill="url(#gHex)"/>
        <rect width="100%" height="100%" fill="url(#gDots)"/>
        <circle cx="92%" cy="8%" r="280" fill="none" stroke="rgba(43,94,171,0.04)" stroke-width="1.5"/>
        <circle cx="92%" cy="8%" r="200" fill="none" stroke="rgba(43,94,171,0.03)" stroke-width="1"/>
        <circle cx="5%" cy="95%" r="240" fill="none" stroke="rgba(201,168,76,0.07)" stroke-width="1.5"/>
    </svg>
</div>

<!-- NAVBAR -->
<nav class="navbar navbar-expand-lg">
    <div class="container">
        <div class="d-flex align-items-center gap-2">
            <a href="assets/img/BPOLD.jpg" target="_blank">
                <img src="assets/img/BPOLD.jpg" alt="Logo" style="height:36px;" class="img-fluid">
            </a>
            <a class="navbar-brand mb-0" href="dashboard_customer.php">Optima <span>Flow</span></a>
        </div>
        <button class="navbar-toggler border-0" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav ms-auto align-items-center gap-1">
                <li class="nav-item"><a class="nav-link" href="dashboard_customer.php"><i class="bi bi-house me-1"></i>Home</a></li>
                <li class="nav-item"><a class="nav-link" href="products.php"><i class="bi bi-grid me-1"></i>Products</a></li>
                <li class="nav-item"><a class="nav-link" href="AboutUS.html"><i class="bi bi-info-circle me-1"></i>About Us</a></li>
                <li class="nav-item"><a class="nav-link" href="contact.php"><i class="bi bi-envelope me-1"></i>Contact</a></li>
                <li class="nav-item ms-2"><a class="btn-nav-cart" href="reservations.php"><i class="bi bi-cart me-1"></i>Cart</a></li>
                <li class="nav-item ms-1"><a class="btn-nav-logout" href="logout.php"><i class="bi bi-box-arrow-right me-1"></i>Logout</a></li>
            </ul>
        </div>
    </div>
</nav>

<!-- PAGE HEADER -->
<div class="page-header">
    <span class="page-header-pill"><i class="bi bi-bookmark-check me-1"></i>My Account</span>
    <h1>My <span>Reservations</span></h1>
    <p>Track and manage your reserved items</p>
</div>

<!-- ALERTS -->
<?php if (isset($_SESSION['message'])): ?>
<div class="main-content">
    <div class="container mb-3">
        <div class="alert alert-success alert-dismissible d-flex align-items-center gap-2 fade show" role="alert"
             style="border-radius:10px; border:1px solid #BBF7D0; background:#F0FDF4; color:#166534; font-size:.875rem;">
            <i class="bi bi-check-circle-fill"></i>
            <?= htmlspecialchars($_SESSION['message']) ?>
            <button type="button" class="btn-close ms-auto" data-bs-dismiss="alert" style="font-size:.75rem;"></button>
        </div>
    </div>
</div>
<?php unset($_SESSION['message']); endif; ?>

<?php if (isset($_SESSION['error'])): ?>
<div class="main-content">
    <div class="container mb-3">
        <div class="alert alert-danger alert-dismissible d-flex align-items-center gap-2 fade show" role="alert"
             style="border-radius:10px; font-size:.875rem;">
            <i class="bi bi-exclamation-circle-fill"></i>
            <?= htmlspecialchars($_SESSION['error']) ?>
            <button type="button" class="btn-close ms-auto" data-bs-dismiss="alert" style="font-size:.75rem;"></button>
        </div>
    </div>
</div>
<?php unset($_SESSION['error']); endif; ?>

<!-- MAIN CONTENT -->
<div class="main-content">
    <div class="container pb-5">

        <?php if (empty($reservations)): ?>
        <!-- EMPTY STATE -->
        <div class="empty-state">
            <div class="empty-icon">
                <i class="bi bi-bookmark-x"></i>
            </div>
            <h3>No reservations yet</h3>
            <p>You haven't reserved anything yet. Browse our products and reserve something you love.</p>
            <a href="products.php" class="btn-browse">
                <i class="bi bi-grid me-1"></i>Browse Products
            </a>
        </div>

        <?php else: ?>

        <!-- SUMMARY ROW -->
        <div class="row g-3 mb-4">
            <div class="col-6 col-md-4">
                <div class="summary-card">
                    <div class="summary-label">Total Reservations</div>
                    <div class="summary-value"><?= count($reservations) ?></div>
                </div>
            </div>
            <div class="col-6 col-md-4">
                <div class="summary-card">
                    <div class="summary-label">Pending</div>
                    <div class="summary-value blue"><?= $pending_count ?></div>
                </div>
            </div>
            <div class="col-12 col-md-4">
                <div class="summary-card">
                    <div class="summary-label">Grand Total</div>
                    <div class="summary-value">₱<?= number_format($grand_total, 2) ?></div>
                </div>
            </div>
        </div>

        <!-- SECTION LABEL -->
        <div class="category-label">
            <div class="cat-icon-badge">
                <i class="bi bi-bookmark-check"></i>
            </div>
            <h3>Reserved Items</h3>
            <div class="cat-divider"></div>
            <a href="products.php" class="btn-browse ms-2" style="white-space:nowrap; padding:.4rem 1rem; font-size:.8rem;">
                <i class="bi bi-plus me-1"></i>Add More
            </a>
        </div>

        <!-- RESERVATION GRID -->
        <div class="row g-4">
            <?php foreach ($reservations as $r):
                $status    = strtolower($r['status']);
                $total     = $r['price'] * $r['quantity'];
                $date      = date('M j, Y g:i A', strtotime($r['reserved_at']));
                $imageName = preg_replace('/[^a-zA-Z0-9]/', '', $r['name']) . '.jpg';
                $imagePath = file_exists("assets/img/$imageName") ? "assets/img/$imageName" : null;
                if (!$imagePath && !empty($r['image']) && file_exists($r['image'])) {
                    $imagePath = $r['image'];
                }
                if (!$imagePath && file_exists('assets/img/placeholder.jpg')) {
                    $imagePath = 'assets/img/placeholder.jpg';
                }
            ?>
            <div class="col-sm-6 col-md-4 col-lg-3">
                <div class="res-card status-<?= $status ?>">

                    <div class="res-img">
                        <?php if ($imagePath): ?>
                            <img src="<?= htmlspecialchars($imagePath) ?>" alt="<?= htmlspecialchars($r['name']) ?>">
                        <?php else: ?>
                            <div class="res-img-placeholder">
                                <i class="bi bi-laptop"></i>
                            </div>
                        <?php endif; ?>
                    </div>

                    <div class="res-body">
                        <h5 class="res-title"><?= htmlspecialchars($r['name']) ?></h5>
                        <?php if (!empty($r['description'])): ?>
                            <p class="res-desc"><?= htmlspecialchars($r['description']) ?></p>
                        <?php endif; ?>

                        <p class="res-meta">
                            <i class="bi bi-calendar3 me-1"></i><?= $date ?>
                        </p>

                        <span class="status-badge badge-<?= $status ?>">
                            <i class="bi <?= $status === 'pending' ? 'bi-clock' : ($status === 'approved' ? 'bi-check-circle' : 'bi-x-circle') ?> me-1"></i>
                            <?= ucfirst($status) ?>
                        </span>

                        <hr class="res-divider">

                        <div class="res-row">
                            <span>Quantity</span>
                            <span><?= $r['quantity'] ?> unit<?= $r['quantity'] > 1 ? 's' : '' ?></span>
                        </div>
                        <div class="res-row">
                            <span>Unit Price</span>
                            <span>₱<?= number_format($r['price'], 2) ?></span>
                        </div>
                        <div class="res-total mt-1">
                            <span class="res-total-label">Total</span>
                            <span class="res-total-value">₱<?= number_format($total, 2) ?></span>
                        </div>
                    </div>

                    <div class="res-footer">
                        <?php if ($status === 'pending'): ?>
                            <form method="POST" onsubmit="return confirm('Cancel this reservation?')">
                                <input type="hidden" name="cancel_id" value="<?= $r['id'] ?>">
                                <button type="submit" class="btn-cancel">
                                    <i class="bi bi-trash3 me-1"></i>Cancel Reservation
                                </button>
                            </form>
                        <?php else: ?>
                            <div class="btn-disabled">
                                <i class="bi <?= $status === 'approved' ? 'bi-check-circle' : 'bi-x-circle' ?> me-1"></i>
                                <?= $status === 'approved' ? 'Approved' : 'Cancelled' ?>
                            </div>
                        <?php endif; ?>
                    </div>

                </div>
            </div>
            <?php endforeach; ?>
        </div>

        <?php endif; ?>
    </div>
</div>

<!-- FOOTER -->
<footer>
    <div class="container">
        <p>&copy; <?= date('Y') ?> <strong>Bicol Depot</strong>. All rights reserved.</p>
    </div>
</footer>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
<?php $conn->close(); ?>