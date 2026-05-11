<?php
session_start();

if (!isset($_SESSION['user'])) {
    header("Location: login.php");
    exit();
}

include 'db.php';

$success_message = "";
$error_message = "";

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['submit_message'])) {
    $subject = isset($_POST['subject']) ? $_POST['subject'] : '';
    $message = isset($_POST['message']) ? $_POST['message'] : '';
    $user_id = $_SESSION['user']['id'];

    if (empty($subject) || empty($message)) {
        $error_message = "Please fill in all fields";
    } else {
        $subject = mysqli_real_escape_string($conn, $subject);
        $message = mysqli_real_escape_string($conn, $message);

        $columnCheckQuery = "DESCRIBE messages";
        $tableCheck = $conn->query("SHOW TABLES LIKE 'messages'");
        $columnResult = ($tableCheck && $tableCheck->num_rows > 0) ? $conn->query($columnCheckQuery) : false;

        if (!$columnResult) {
            $createTableQuery = "CREATE TABLE messages (
                id INT(11) AUTO_INCREMENT PRIMARY KEY,
                user_id INT(11) NOT NULL,
                subject VARCHAR(255) NOT NULL,
                content TEXT NOT NULL,
                is_read TINYINT(1) DEFAULT 0,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            )";
            $userTableCheck = $conn->query("SHOW TABLES LIKE 'users'");
            if ($userTableCheck && $userTableCheck->num_rows > 0) {
                $createTableQuery = "CREATE TABLE messages (
                    id INT(11) AUTO_INCREMENT PRIMARY KEY,
                    user_id INT(11) NOT NULL,
                    subject VARCHAR(255) NOT NULL,
                    content TEXT NOT NULL,
                    is_read TINYINT(1) DEFAULT 0,
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
                )";
            }
            if (!$conn->query($createTableQuery)) {
                $error_message = "Error creating messages table: " . $conn->error;
            } else {
                $columnResult = $conn->query($columnCheckQuery);
            }
        }

        if ($columnResult) {
            $columns = []; $hasSubject = $hasTitle = $hasContent = $hasMessage = false;
            while ($row = $columnResult->fetch_assoc()) {
                $columns[] = $row['Field'];
                if ($row['Field'] == 'subject') $hasSubject = true;
                if ($row['Field'] == 'title')   $hasTitle   = true;
                if ($row['Field'] == 'content') $hasContent = true;
                if ($row['Field'] == 'message') $hasMessage = true;
            }
            $subjectColumn = $hasSubject ? 'subject' : ($hasTitle   ? 'title'   : 'subject');
            $messageColumn = $hasContent ? 'content' : ($hasMessage ? 'message' : 'content');
            try {
                if (!$hasSubject && !$hasTitle) { $conn->query("ALTER TABLE messages ADD COLUMN subject VARCHAR(255) NOT NULL AFTER user_id"); $subjectColumn = 'subject'; }
                if (!$hasContent && !$hasMessage) { $conn->query("ALTER TABLE messages ADD COLUMN content TEXT NOT NULL AFTER $subjectColumn"); $messageColumn = 'content'; }
                $stmt = $conn->prepare("INSERT INTO messages (user_id, $subjectColumn, $messageColumn) VALUES (?, ?, ?)");
                if ($stmt) {
                    $stmt->bind_param("iss", $user_id, $subject, $message);
                    if ($stmt->execute()) { $success_message = "Your message has been sent to the administrator."; }
                    else { throw new Exception("Error: " . $stmt->error); }
                    $stmt->close();
                } else { throw new Exception("Prepare error: " . $conn->error); }
            } catch (Exception $e) {
                $q = "INSERT INTO messages (user_id, $subjectColumn, $messageColumn) VALUES ('$user_id', '$subject', '$message')";
                if ($conn->query($q)) { $success_message = "Your message has been sent to the administrator."; }
                else { $error_message = "Error sending message: " . $conn->error; }
            }
        } else {
            $error_message = "Could not determine table structure: " . $conn->error;
        }
    }
}

$user_id = $_SESSION['user']['id'];
$previousMessages = [];
$tableExists = $conn->query("SHOW TABLES LIKE 'messages'");
$columnResult = ($tableExists && $tableExists->num_rows > 0) ? $conn->query("DESCRIBE messages") : false;
if ($columnResult) {
    $hasSubject = $hasTitle = $hasContent = $hasMessage = false;
    while ($row = $columnResult->fetch_assoc()) {
        if ($row['Field'] == 'subject') $hasSubject = true;
        if ($row['Field'] == 'title')   $hasTitle   = true;
        if ($row['Field'] == 'content') $hasContent = true;
        if ($row['Field'] == 'message') $hasMessage = true;
    }
    $subjectColumn = $hasSubject ? 'subject' : ($hasTitle   ? 'title'   : '');
    $messageColumn = $hasContent ? 'content' : ($hasMessage ? 'message' : '');
    if (!empty($subjectColumn) && !empty($messageColumn)) {
        try {
            $stmt = $conn->prepare("SELECT id, user_id, $subjectColumn as subject, $messageColumn as content, is_read, created_at FROM messages WHERE user_id = ? ORDER BY created_at DESC");
            if ($stmt) {
                $stmt->bind_param("i", $user_id);
                $stmt->execute();
                $result = $stmt->get_result();
                while ($row = $result->fetch_assoc()) { $previousMessages[] = $row; }
                $stmt->close();
            }
        } catch (Exception $e) {}
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Contact - Optima Flow</title>
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
            --gold-lt: #F5EDD0;
            --beige:   #F5F0DC;
            --dark:    #0E1F3D;
            --mid:     #2C3A5E;
            --muted:   #6B7A99;
            --card-bg: rgba(255,255,255,0.82);
        }

        body { font-family: 'DM Sans', sans-serif; background: var(--beige); color: var(--dark); min-height: 100vh; }

        .geo-fixed { position: fixed; inset: 0; z-index: 0; pointer-events: none; overflow: hidden; }

        /* ── NAVBAR ── */
        .navbar {
            background: rgba(245,240,220,0.92) !important;
            backdrop-filter: blur(14px);
            border-bottom: 1px solid rgba(43,94,171,0.1);
            padding: 0.9rem 0;
            position: sticky; top: 0; z-index: 999;
        }
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
        .page-header p { font-size: 0.92rem; font-weight: 300; color: var(--muted); max-width: 480px; margin: 0 auto; }

        /* ── ALERTS ── */
        .themed-alert { padding: 0.8rem 1rem; border-radius: 8px; font-size: 0.85rem; margin-bottom: 1.4rem; display: flex; align-items: center; gap: 0.6rem; }
        .alert-ok { background: rgba(39,174,96,0.08); border: 1px solid rgba(39,174,96,0.25); color: #1E8449; }
        .alert-err { background: rgba(192,57,43,0.08); border: 1px solid rgba(192,57,43,0.2); color: #922B21; }

        /* ── CARDS ── */
        .themed-card { background: var(--card-bg); border: 1px solid rgba(43,94,171,0.1); border-radius: 12px; overflow: hidden; height: 100%; }

        .card-head { padding: 1rem 1.4rem; display: flex; align-items: center; gap: 0.6rem; border-bottom: 1px solid rgba(43,94,171,0.08); }
        .card-head-icon { width: 32px; height: 32px; border-radius: 7px; background: var(--blue-lt); display: flex; align-items: center; justify-content: center; color: var(--blue); font-size: 0.95rem; flex-shrink: 0; }
        .card-head-title { font-family: 'Playfair Display', serif; font-size: 1rem; font-weight: 700; color: var(--dark); margin: 0; }

        .card-head.gold-head { background: var(--gold-lt); border-bottom: 1px solid rgba(201,168,76,0.2); }
        .card-head.gold-head .card-head-icon { background: rgba(201,168,76,0.15); color: #7A5C10; }
        .card-head.gold-head .card-head-title { color: #5C3D00; }

        .card-head.mid-head { background: var(--blue-lt); }

        .card-body-pad { padding: 1.4rem; }

        /* ── FORM ELEMENTS ── */
        .form-lbl { display: block; font-size: 0.75rem; font-weight: 500; letter-spacing: 0.08em; text-transform: uppercase; color: var(--mid); margin-bottom: 0.4rem; }
        .form-inp { width: 100%; padding: 0.72rem 1rem; font-family: 'DM Sans', sans-serif; font-size: 0.88rem; color: var(--dark); background: rgba(245,240,220,0.5); border: 1.5px solid rgba(43,94,171,0.18); border-radius: 8px; transition: all 0.2s; resize: vertical; }
        .form-inp:focus { outline: none; border-color: var(--blue); background: white; box-shadow: 0 0 0 4px rgba(43,94,171,0.1); }
        .form-inp::placeholder { color: #aab2c8; font-weight: 300; }
        .form-group { margin-bottom: 1.1rem; }

        .btn-send { width: 100%; padding: 0.85rem; font-family: 'DM Sans', sans-serif; font-size: 0.9rem; font-weight: 500; color: white; background: var(--blue); border: none; border-radius: 8px; cursor: pointer; box-shadow: 0 4px 18px rgba(43,94,171,0.28); transition: all 0.22s; display: flex; align-items: center; justify-content: center; gap: 0.5rem; }
        .btn-send:hover { background: var(--blue-dk); transform: translateY(-2px); box-shadow: 0 8px 28px rgba(43,94,171,0.38); }

        /* ── USER INFO ── */
        .info-row { display: flex; align-items: flex-start; gap: 0.5rem; font-size: 0.85rem; color: var(--mid); padding: 0.55rem 0; border-bottom: 1px solid rgba(43,94,171,0.07); }
        .info-row:last-child { border-bottom: none; padding-bottom: 0; }
        .info-label { font-weight: 500; color: var(--dark); min-width: 90px; }
        .info-note { font-size: 0.78rem; color: var(--muted); margin-top: 0.8rem; line-height: 1.5; }

        /* ── CONTACT OPTIONS ── */
        .contact-item { display: flex; align-items: center; gap: 0.8rem; font-size: 0.85rem; color: var(--mid); padding: 0.6rem 0; border-bottom: 1px solid rgba(43,94,171,0.07); }
        .contact-item:last-child { border-bottom: none; }
        .contact-icon { width: 32px; height: 32px; border-radius: 7px; background: var(--blue-lt); display: flex; align-items: center; justify-content: center; color: var(--blue); font-size: 0.9rem; flex-shrink: 0; }
        .contact-item a { color: var(--blue); text-decoration: none; font-weight: 500; }
        .contact-item a:hover { text-decoration: underline; }

        /* ── SECTION HEADING ── */
        .section-heading { font-family: 'Playfair Display', serif; font-size: 1.5rem; font-weight: 900; color: var(--dark); margin-bottom: 0.4rem; position: relative; display: inline-block; }
        .section-heading::after { content: ''; position: absolute; left: 0; bottom: -7px; width: 36px; height: 3px; background: var(--gold); border-radius: 2px; }

        /* ── MESSAGE HISTORY ── */
        .msg-card { background: var(--card-bg); border: 1px solid rgba(43,94,171,0.1); border-left: 4px solid var(--blue); border-radius: 10px; padding: 1.2rem 1.4rem; margin-bottom: 1rem; transition: box-shadow 0.2s; }
        .msg-card:hover { box-shadow: 0 8px 24px rgba(14,31,61,0.09); }
        .msg-card.msg-read { border-left-color: rgba(43,94,171,0.25); opacity: 0.85; }
        .msg-meta { display: flex; justify-content: space-between; align-items: center; margin-bottom: 0.6rem; font-size: 0.75rem; color: var(--muted); }
        .msg-subject { font-family: 'Playfair Display', serif; font-size: 0.98rem; font-weight: 700; color: var(--dark); margin-bottom: 0.4rem; }
        .msg-body { font-size: 0.83rem; color: var(--mid); line-height: 1.6; }
        .status-pill { display: inline-block; font-size: 0.68rem; font-weight: 500; letter-spacing: 0.08em; text-transform: uppercase; padding: 0.2rem 0.6rem; border-radius: 999px; }
        .status-unread { background: rgba(43,94,171,0.1); color: var(--blue); border: 1px solid rgba(43,94,171,0.2); }
        .status-read { background: rgba(107,122,153,0.1); color: var(--muted); border: 1px solid rgba(107,122,153,0.2); }

        .empty-msg { background: var(--card-bg); border: 1px dashed rgba(43,94,171,0.2); border-radius: 10px; padding: 2rem; text-align: center; color: var(--muted); font-size: 0.88rem; }

        /* ── FOOTER ── */
        footer { position: relative; z-index: 1; background: var(--dark); color: rgba(255,255,255,0.5); padding: 2rem 0; font-size: 0.8rem; text-align: center; margin-top: 2rem; }
        footer strong { color: var(--gold); }
        footer a { color: rgba(255,255,255,0.5); text-decoration: none; transition: color 0.2s; }
        footer a:hover { color: white; }
    </style>
</head>
<body>

    <!-- GEO BACKGROUND -->
    <div class="geo-fixed">
        <svg width="100%" height="100%" xmlns="http://www.w3.org/2000/svg">
            <defs>
                <pattern id="cHex" x="0" y="0" width="60" height="52" patternUnits="userSpaceOnUse">
                    <polygon points="30,2 58,17 58,47 30,62 2,47 2,17" fill="none" stroke="rgba(43,94,171,0.06)" stroke-width="1"/>
                </pattern>
                <pattern id="cDots" x="0" y="0" width="38" height="38" patternUnits="userSpaceOnUse">
                    <circle cx="19" cy="19" r="1.3" fill="rgba(201,168,76,0.17)"/>
                </pattern>
            </defs>
            <rect width="100%" height="100%" fill="url(#cHex)"/>
            <rect width="100%" height="100%" fill="url(#cDots)"/>
            <circle cx="85%" cy="12%" r="240" fill="none" stroke="rgba(43,94,171,0.05)" stroke-width="1.5"/>
            <circle cx="85%" cy="12%" r="160" fill="none" stroke="rgba(43,94,171,0.04)" stroke-width="1"/>
            <circle cx="10%" cy="88%" r="200" fill="none" stroke="rgba(201,168,76,0.08)" stroke-width="1.5"/>
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
                    <li class="nav-item"><a class="nav-link active" href="contact.php"><i class="bi bi-envelope me-1"></i>Contact</a></li>
                    <li class="nav-item ms-2"><a class="btn-nav-cart" href="reservations.php"><i class="bi bi-cart me-1"></i>Cart</a></li>
                    <li class="nav-item ms-1"><a class="btn-nav-logout" href="logout.php"><i class="bi bi-box-arrow-right me-1"></i>Logout</a></li>
                </ul>
            </div>
        </div>
    </nav>

    <div class="main-content">
        <div class="container">

            <!-- PAGE HEADER -->
            <div class="page-header">
                <span class="page-pill"><i class="bi bi-envelope me-1"></i>Get in Touch</span>
                <h1>Contact <span>Admin</span></h1>
                <p>Have a question or concern? Send a message directly to our administrators.</p>
            </div>

            <!-- ALERTS -->
            <?php if (!empty($success_message)): ?>
            <div class="themed-alert alert-ok">
                <i class="bi bi-check-circle-fill"></i>
                <?= htmlspecialchars($success_message) ?>
            </div>
            <?php endif; ?>
            <?php if (!empty($error_message)): ?>
            <div class="themed-alert alert-err">
                <i class="bi bi-exclamation-circle-fill"></i>
                <?= htmlspecialchars($error_message) ?>
            </div>
            <?php endif; ?>

            <!-- FORM + INFO -->
            <div class="row g-4 mb-4">

                <!-- Message Form -->
                <div class="col-md-6">
                    <div class="themed-card">
                        <div class="card-head mid-head">
                            <div class="card-head-icon"><i class="bi bi-pencil-square"></i></div>
                            <h4 class="card-head-title">Send New Message</h4>
                        </div>
                        <div class="card-body-pad">
                            <form action="<?= htmlspecialchars($_SERVER["PHP_SELF"]) ?>" method="post">
                                <div class="form-group">
                                    <label class="form-lbl" for="subject">Subject</label>
                                    <input type="text" class="form-inp" id="subject" name="subject" placeholder="What is this regarding?" required>
                                </div>
                                <div class="form-group">
                                    <label class="form-lbl" for="message">Message</label>
                                    <textarea class="form-inp" id="message" name="message" rows="6" placeholder="Write your message here..." required></textarea>
                                </div>
                                <button type="submit" name="submit_message" class="btn-send">
                                    <i class="bi bi-send-fill"></i> Send Message
                                </button>
                            </form>
                        </div>
                    </div>
                </div>

                <!-- Right Column -->
                <div class="col-md-6 d-flex flex-column gap-4">

                    <!-- User Info -->
                    <div class="themed-card">
                        <div class="card-head">
                            <div class="card-head-icon"><i class="bi bi-person-circle"></i></div>
                            <h4 class="card-head-title">Your Information</h4>
                        </div>
                        <div class="card-body-pad">
                            <div class="info-row">
                                <span class="info-label">Username</span>
                                <span><?= htmlspecialchars($_SESSION['user']['username']) ?></span>
                            </div>
                            <?php if (isset($_SESSION['user']['email'])): ?>
                            <div class="info-row">
                                <span class="info-label">Email</span>
                                <span><?= htmlspecialchars($_SESSION['user']['email']) ?></span>
                            </div>
                            <?php endif; ?>
                            <div class="info-row">
                                <span class="info-label">Account Type</span>
                                <span><?= ucfirst($_SESSION['user']['role']) ?></span>
                            </div>
                            <p class="info-note">This information will be included with your message to help administrators identify you.</p>
                        </div>
                    </div>

                    <!-- Contact Options -->
                    <div class="themed-card">
                        <div class="card-head gold-head">
                            <div class="card-head-icon"><i class="bi bi-telephone"></i></div>
                            <h4 class="card-head-title">Contact Options</h4>
                        </div>
                        <div class="card-body-pad">
                            <div class="contact-item">
                                <div class="contact-icon"><i class="bi bi-envelope-fill"></i></div>
                                <div>Email: <a href="mailto:support@optimaflow.com">support@optimaflow.com</a></div>
                            </div>
                            <div class="contact-item">
                                <div class="contact-icon"><i class="bi bi-telephone-fill"></i></div>
                                <div>Phone: +63 912 345 6789</div>
                            </div>
                            <div class="contact-item">
                                <div class="contact-icon"><i class="bi bi-clock-fill"></i></div>
                                <div>Response time: Within 24–48 hours</div>
                            </div>
                        </div>
                    </div>

                </div>
            </div>

            <!-- MESSAGE HISTORY -->
            <div class="mb-2 mt-3">
                <h2 class="section-heading">Previous Messages</h2>
            </div>
            <div style="margin-top: 1.6rem;">
                <?php if (empty($previousMessages)): ?>
                <div class="empty-msg">
                    <i class="bi bi-inbox" style="font-size:2rem; display:block; margin-bottom:0.6rem; opacity:0.3;"></i>
                    You haven't sent any messages yet.
                </div>
                <?php else: ?>
                    <?php foreach ($previousMessages as $msg): ?>
                    <div class="msg-card <?= $msg['is_read'] ? 'msg-read' : '' ?>">
                        <div class="msg-meta">
                            <span><i class="bi bi-clock me-1"></i><?= date('F d, Y — h:i A', strtotime($msg['created_at'])) ?></span>
                            <span class="status-pill <?= $msg['is_read'] ? 'status-read' : 'status-unread' ?>">
                                <?= $msg['is_read'] ? 'Read' : 'Unread' ?>
                            </span>
                        </div>
                        <p class="msg-subject"><?= htmlspecialchars($msg['subject']) ?></p>
                        <p class="msg-body"><?= nl2br(htmlspecialchars($msg['content'])) ?></p>
                    </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>

        </div>
    </div>

    <!-- FOOTER -->
    <footer>
        <div class="container">
            <p class="mb-1">&copy; 2025 <strong>Bicol Depot</strong>. All rights reserved.</p>
            <p>
                <a href="privacy.html">Privacy Policy</a>
                <span class="mx-2" style="opacity:0.3;">|</span>
                <a href="terms.html">Terms of Service</a>
            </p>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
<?php $conn->close(); ?>