<?php
session_start();
include 'db.php';

$success_message = $_SESSION['message'] ?? null;
$error_message = $_SESSION['error'] ?? null;

unset($_SESSION['message']);
unset($_SESSION['error']);

$user_id = $_SESSION['user']['id'] ?? null;
$reservations = [];
if ($user_id) {
    $reservationQuery = $conn->prepare("
        SELECT r.*, p.name as product_name, p.price, p.image 
        FROM reservations r
        JOIN products p ON r.product_id = p.id
        WHERE r.user_id = ? AND r.status = 'pending'
        ORDER BY r.reserved_at DESC
    ");
    $reservationQuery->bind_param("i", $user_id);
    $reservationQuery->execute();
    $result = $reservationQuery->get_result();
    $reservations = $result->fetch_all(MYSQLI_ASSOC);
}
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
            --card-bg: rgba(255,255,255,0.82);
        }

        body { font-family: 'DM Sans', sans-serif; background: var(--beige); color: var(--dark); min-height: 100vh; }

        .geo-fixed { position: fixed; inset: 0; z-index: 0; pointer-events: none; overflow: hidden; }

        /* ── NAVBAR ── */
        .navbar { background: rgba(245,240,220,0.92) !important; backdrop-filter: blur(14px); border-bottom: 1px solid rgba(43,94,171,0.1); padding: 0.9rem 0; position: sticky; top: 0; z-index: 999; }
        .navbar-brand { font-family: 'Playfair Display', serif; font-size: 1.4rem; font-weight: 900; color: var(--dark) !important; letter-spacing: -0.02em; }
        .navbar-brand span { color: var(--blue); }
        .nav-link { font-size: 0.88rem; font-weight: 500; color: var(--mid) !important; padding: 0.4rem 0.9rem !important; border-radius: 6px; transition: all 0.2s; }
        .nav-link:hover, .nav-link.active { color: var(--blue) !important; background: var(--blue-lt); }
        .btn-nav-cart { font-size: 0.82rem; font-weight: 500; color: var(--blue) !important; border: 1.5px solid rgba(43,94,171,0.35) !important; border-radius: 6px !important; padding: 0.38rem 1rem !important; transition: all 0.2s; text-decoration: none; }
        .btn-nav-cart:hover { background: var(--blue) !important; color: white !important; }
        .btn-nav-logout { font-size: 0.82rem; font-weight: 500; color: #922B21 !important; border: 1.5px solid rgba(146,43,33,0.3) !important; border-radius: 6px !important; padding: 0.38rem 1rem !important; transition: all 0.2s; text-decoration: none; }
        .btn-nav-logout:hover { background: #922B21 !important; color: white !important; }

        .main-content { position: relative; z-index: 1; padding-bottom: 4rem; }

        /* ── PAGE HEADER ── */
        .page-header { padding: 3.5rem 0 2rem; text-align: center; }
        .page-pill { display: inline-block; font-size: 0.7rem; font-weight: 500; letter-spacing: 0.2em; text-transform: uppercase; color: var(--blue); background: rgba(43,94,171,0.08); border: 1px solid rgba(43,94,171,0.2); border-radius: 999px; padding: 0.35rem 1rem; margin-bottom: 1rem; }
        .page-header h1 { font-family: 'Playfair Display', serif; font-size: clamp(2rem, 4vw, 3rem); font-weight: 900; color: var(--dark); margin-bottom: 0.5rem; }
        .page-header h1 span { color: var(--blue); position: relative; }
        .page-header h1 span::after { content: ''; position: absolute; left: 0; right: 0; bottom: -4px; height: 3px; background: var(--gold); border-radius: 2px; }
        .page-header p { font-size: 0.92rem; font-weight: 300; color: var(--muted); }

        /* ── ALERTS ── */
        .themed-alert { padding: 0.8rem 1rem; border-radius: 8px; font-size: 0.85rem; margin-bottom: 1.4rem; display: flex; align-items: center; gap: 0.6rem; }
        .alert-ok  { background: rgba(39,174,96,0.08);  border: 1px solid rgba(39,174,96,0.25);  color: #1E8449; }
        .alert-err { background: rgba(192,57,43,0.08);  border: 1px solid rgba(192,57,43,0.2);   color: #922B21; }

        /* ── RESERVATION CARDS ── */
        .res-card { background: var(--card-bg); border: 1px solid rgba(43,94,171,0.1); border-left: 4px solid var(--blue); border-radius: 12px; overflow: hidden; transition: transform 0.25s, box-shadow 0.25s; height: 100%; display: flex; flex-direction: column; }
        .res-card:hover { transform: translateY(-5px); box-shadow: 0 14px 32px rgba(14,31,61,0.11); }
        .res-card-body { padding: 1.3rem; flex: 1; }
        .res-card-footer { padding: 0.9rem 1.3rem; border-top: 1px solid rgba(43,94,171,0.08); background: transparent; }

        .product-img { width: 100%; height: 110px; object-fit: cover; border-radius: 8px; }

        .product-title { font-family: 'Playfair Display', serif; font-size: 0.98rem; font-weight: 700; color: var(--dark); margin-bottom: 0.3rem; }
        .res-meta { font-size: 0.78rem; color: var(--muted); margin-bottom: 0.5rem; }

        .status-badge { display: inline-block; font-size: 0.68rem; font-weight: 500; letter-spacing: 0.08em; text-transform: uppercase; padding: 0.22rem 0.65rem; border-radius: 999px; background: rgba(201,168,76,0.12); color: #7A5C10; border: 1px solid rgba(201,168,76,0.3); }

        .res-detail { font-size: 0.82rem; color: var(--mid); margin-bottom: 0.2rem; }
        .res-total { font-size: 0.95rem; font-weight: 700; color: var(--blue); margin-top: 0.4rem; }

        .btn-cancel { width: 100%; display: flex; align-items: center; justify-content: center; gap: 0.4rem; font-family: 'DM Sans', sans-serif; font-size: 0.82rem; font-weight: 500; color: #922B21; background: transparent; border: 1.5px solid rgba(146,43,33,0.25); border-radius: 6px; padding: 0.6rem; cursor: pointer; transition: all 0.2s; }
        .btn-cancel:hover { background: #922B21; color: white; border-color: #922B21; }

        /* ── EMPTY STATE ── */
        .empty-state { background: var(--card-bg); border: 1px dashed rgba(43,94,171,0.2); border-radius: 12px; padding: 4rem 2rem; text-align: center; }
        .empty-state i { font-size: 3rem; color: rgba(43,94,171,0.2); display: block; margin-bottom: 1rem; }
        .empty-state h3 { font-family: 'Playfair Display', serif; font-size: 1.3rem; font-weight: 700; color: var(--dark); margin-bottom: 0.5rem; }
        .empty-state p { font-size: 0.88rem; color: var(--muted); margin-bottom: 1.4rem; }

        .btn-browse { display: inline-flex; align-items: center; gap: 0.4rem; font-size: 0.88rem; font-weight: 500; color: white; background: var(--blue); border: none; border-radius: 6px; padding: 0.7rem 1.6rem; text-decoration: none; transition: all 0.22s; box-shadow: 0 4px 14px rgba(43,94,171,0.28); }
        .btn-browse:hover { background: var(--blue-dk); color: white; transform: translateY(-2px); }

        .btn-add-more { display: inline-flex; align-items: center; gap: 0.4rem; font-size: 0.85rem; font-weight: 500; color: var(--blue); background: var(--blue-lt); border: 1px solid rgba(43,94,171,0.2); border-radius: 6px; padding: 0.6rem 1.2rem; text-decoration: none; transition: all 0.2s; }
        .btn-add-more:hover { background: var(--blue); color: white; }

        /* ── FOOTER ── */
        footer { position: relative; z-index: 1; background: var(--dark); color: rgba(255,255,255,0.5); padding: 2rem 0; font-size: 0.8rem; text-align: center; margin-top: 2rem; }
        footer strong { color: var(--gold); }
        footer a { color: rgba(255,255,255,0.5); text-decoration: none; }
        footer a:hover { color: white; }
    </style>
</head>
<body>

    <!-- GEO BACKGROUND -->
    <div class="geo-fixed">
        <svg width="100%" height="100%" xmlns="http://www.w3.org/2000/svg">
            <defs>
                <pattern id="rHex" x="0" y="0" width="60" height="52" patternUnits="userSpaceOnUse">
                    <polygon points="30,2 58,17 58,47 30,62 2,47 2,17" fill="none" stroke="rgba(43,94,171,0.06)" stroke-width="1"/>
                </pattern>
                <pattern id="rDots" x="0" y="0" width="38" height="38" patternUnits="userSpaceOnUse">
                    <circle cx="19" cy="19" r="1.3" fill="rgba(201,168,76,0.17)"/>
                </pattern>
            </defs>
            <rect width="100%" height="100%" fill="url(#rHex)"/>
            <rect width="100%" height="100%" fill="url(#rDots)"/>
            <circle cx="88%" cy="10%" r="240" fill="none" stroke="rgba(43,94,171,0.05)" stroke-width="1.5"/>
            <circle cx="10%" cy="90%" r="200" fill="none" stroke="rgba(201,168,76,0.08)" stroke-width="1.5"/>
        </svg>
    </div>

    <!-- NAVBAR -->
    <nav class="navbar navbar-expand-lg">
        <div class="container">
            <div class="d-flex align-items-center gap-2">
                <a href="assets/img/BPOLD.jpg" target="_blank">
                    <img src="assets/img/BPOLD.jpg" alt="Logo" style="height:36px;" class="img-fluid">
                </a>
                <a class="navbar-brand mb-0" href="dashboard_customer.php">Bicol <span>Depot</span></a>
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
                    <li class="nav-item ms-2"><a class="btn-nav-cart active" href="reserved.php"><i class="bi bi-cart me-1"></i>Cart</a></li>
                    <li class="nav-item ms-1"><a class="btn-nav-logout" href="logout.php"><i class="bi bi-box-arrow-right me-1"></i>Logout</a></li>
                </ul>
            </div>
        </div>
    </nav>

    <div class="main-content">
        <div class="container">

            <!-- PAGE HEADER -->
            <div class="page-header">
                <span class="page-pill"><i class="bi bi-cart me-1"></i>My Cart</span>
                <h1>My <span>Reservations</span></h1>
                <p>Review and manage your pending reservations below.</p>
            </div>

            <!-- ALERTS -->
            <?php if ($success_message): ?>
            <div class="themed-alert alert-ok">
                <i class="bi bi-check-circle-fill"></i> <?= htmlspecialchars($success_message) ?>
            </div>
            <?php endif; ?>
            <?php if ($error_message): ?>
            <div class="themed-alert alert-err">
                <i class="bi bi-exclamation-circle-fill"></i> <?= htmlspecialchars($error_message) ?>
            </div>
            <?php endif; ?>

            <?php if (empty($reservations)): ?>
            <!-- EMPTY STATE -->
            <div class="empty-state">
                <i class="bi bi-cart-x"></i>
                <h3>Your cart is empty</h3>
                <p>Start shopping to add items to your cart</p>
                <a href="products.php" class="btn-browse"><i class="bi bi-grid"></i> Browse Products</a>
            </div>

            <?php else: ?>
            <!-- HEADER ROW -->
            <div class="d-flex justify-content-between align-items-center mb-4">
                <span style="font-size:0.85rem; color:var(--muted);"><?= count($reservations) ?> item(s) pending</span>
                <a href="products.php" class="btn-add-more"><i class="bi bi-plus-circle"></i> Add More Items</a>
            </div>

            <!-- RESERVATION CARDS -->
            <div class="row g-4">
                <?php foreach ($reservations as $reservation):
                    $imageName = preg_replace('/[^a-zA-Z0-9]/', '', $reservation['product_name']) . '.jpg';
                    $imagePath = file_exists("assets/img/$imageName") ? "assets/img/$imageName" : 'assets/img/placeholder.jpg';
                ?>
                <div class="col-md-6">
                    <div class="res-card">
                        <div class="res-card-body">
                            <div class="row g-3">
                                <div class="col-4">
                                    <img src="<?= htmlspecialchars($imagePath) ?>" class="product-img" alt="Product Image">
                                </div>
                                <div class="col-8">
                                    <h5 class="product-title"><?= htmlspecialchars($reservation['product_name']) ?></h5>
                                    <p class="res-meta"><i class="bi bi-clock me-1"></i><?= date('M d, Y h:i A', strtotime($reservation['reserved_at'])) ?></p>
                                    <span class="status-badge">Pending</span>
                                    <div class="mt-3">
                                        <p class="res-detail">Quantity: <strong><?= $reservation['quantity'] ?></strong></p>
                                        <p class="res-detail">Price: <strong>₱<?= number_format($reservation['price'], 2) ?></strong></p>
                                        <p class="res-total">Total: ₱<?= number_format($reservation['price'] * $reservation['quantity'], 2) ?></p>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="res-card-footer">
                            <form action="cancel_reservation.php" method="POST">
                                <input type="hidden" name="reservation_id" value="<?= $reservation['id'] ?>">
                                <button type="submit" class="btn-cancel">
                                    <i class="bi bi-trash"></i> Cancel Reservation
                                </button>
                            </form>
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
            <p class="mb-1">&copy; 2025 <strong>Bicol Depot</strong>. All rights reserved.</p>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.querySelectorAll('form[action="cancel_reservation.php"]').forEach(form => {
            form.addEventListener('submit', function(e) {
                if (!confirm('Are you sure you want to cancel this reservation?')) {
                    e.preventDefault();
                }
            });
        });
    </script>
</body>
</html>
<?php $conn->close(); ?>