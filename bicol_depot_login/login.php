<?php
session_start();
include 'db.php';

$error_message = "";

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    
    if (empty($email) || empty($password)) {
        $error_message = "Email and password are required";
    } else {
        $stmt = $conn->prepare("SELECT * FROM users WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows == 1) {
            $user = $result->fetch_assoc();
            
            if (isset($user['status']) && $user['status'] !== 'active') {
                $error_message = "Your account has been deactivated. Please contact the administrator.";
            } elseif (password_verify($password, $user['password'])) {
                $_SESSION['user'] = [
                    'id' => $user['id'],
                    'email' => $user['email'],
                    'username' => $user['username'],
                    'role' => $user['role']
                ];
                $_SESSION['just_logged_in'] = true;

                $updateLoginTime = "UPDATE users SET login_at = NOW() WHERE id = ?";
                $updateStmt = $conn->prepare($updateLoginTime);
                $updateStmt->bind_param("i", $user['id']);
                $updateStmt->execute();
                $updateStmt->close();
                
                if ($user['role'] === 'admin') {
                    header("Location: dashboard_admin.php");
                } else {
                    header("Location: dashboard_customer.php");
                }
                exit();
            } else {
                $error_message = "Invalid email or password";
            }
        } else {
            $error_message = "Invalid email or password";
        }
        $stmt->close();
    }
}
$conn->close();

$resetSuccess = isset($_GET['reset']) && $_GET['reset'] == 'success';
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Optima Flow</title>
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@700;900&family=DM+Sans:wght@300;400;500&display=swap" rel="stylesheet">
    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

        :root {
            --blue:    #2B5EAB;
            --blue-dk: #1A3F7A;
            --gold:    #C9A84C;
            --beige:   #F5F0DC;
            --dark:    #0E1F3D;
            --mid:     #2C3A5E;
            --muted:   #6B7A99;
        }

        body {
            font-family: 'DM Sans', sans-serif;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            background: var(--beige);
            position: relative;
            overflow: hidden;
        }

        .geo-bg {
            position: fixed;
            inset: 0;
            z-index: 0;
            pointer-events: none;
        }

        /* ── CARD ── */
        .login-card {
            position: relative;
            z-index: 1;
            width: 100%;
            max-width: 420px;
            margin: 2rem 1.2rem;
            background: rgba(255,255,255,0.75);
            backdrop-filter: blur(18px);
            -webkit-backdrop-filter: blur(18px);
            border: 1px solid rgba(43,94,171,0.12);
            border-radius: 16px;
            box-shadow: 0 20px 60px rgba(14,31,61,0.1), 0 4px 16px rgba(14,31,61,0.06);
            padding: 2.8rem 2.4rem 2.4rem;
            animation: fadeUp 0.6s ease both;
        }

        /* ── HEADER ── */
        .card-header {
            text-align: center;
            margin-bottom: 1.8rem;
        }

        .brand {
            font-family: 'Playfair Display', serif;
            font-size: 2rem;
            font-weight: 900;
            color: var(--dark);
            letter-spacing: -0.02em;
            display: block;
            text-decoration: none;
            margin-bottom: 0.3rem;
        }

        .brand span { color: var(--blue); }

        .brand-underline {
            display: block;
            width: 40px;
            height: 3px;
            background: var(--gold);
            border-radius: 2px;
            margin: 0.5rem auto 0.75rem;
            animation: growLine 0.5s 0.7s ease both;
            transform: scaleX(0);
            transform-origin: center;
        }

        .card-subtitle {
            font-size: 0.85rem;
            font-weight: 400;
            color: var(--muted);
            letter-spacing: 0.04em;
        }

        /* ── ALERTS ── */
        .alert {
            padding: 0.7rem 1rem;
            border-radius: 8px;
            font-size: 0.82rem;
            margin-bottom: 1.2rem;
            text-align: center;
        }

        .alert-danger {
            background: rgba(192,57,43,0.08);
            border: 1px solid rgba(192,57,43,0.2);
            color: #922B21;
        }

        .alert-success {
            background: rgba(39,174,96,0.08);
            border: 1px solid rgba(39,174,96,0.2);
            color: #1E8449;
        }

        /* ── FORM ── */
        .form-group {
            margin-bottom: 1.1rem;
        }

        .form-label {
            display: block;
            font-size: 0.78rem;
            font-weight: 500;
            letter-spacing: 0.08em;
            text-transform: uppercase;
            color: var(--mid);
            margin-bottom: 0.45rem;
        }

        .form-input {
            width: 100%;
            padding: 0.78rem 1rem;
            font-family: 'DM Sans', sans-serif;
            font-size: 0.9rem;
            color: var(--dark);
            background: rgba(245,240,220,0.5);
            border: 1.5px solid rgba(43,94,171,0.18);
            border-radius: 8px;
            transition: all 0.2s ease;
        }

        .form-input:focus {
            outline: none;
            border-color: var(--blue);
            background: white;
            box-shadow: 0 0 0 4px rgba(43,94,171,0.1);
        }

        .form-input::placeholder {
            color: #aab2c8;
            font-weight: 300;
        }

        /* ── PASSWORD TOGGLE ── */
        .password-wrap {
            position: relative;
        }

        .toggle-password {
            position: absolute;
            right: 12px;
            top: 50%;
            transform: translateY(-50%);
            background: none;
            border: none;
            font-family: 'DM Sans', sans-serif;
            font-size: 0.75rem;
            font-weight: 500;
            color: var(--muted);
            cursor: pointer;
            padding: 0.2rem 0.4rem;
            letter-spacing: 0.04em;
            transition: color 0.2s;
        }

        .toggle-password:hover { color: var(--blue); }

        /* ── SUBMIT ── */
        .submit-button {
            width: 100%;
            margin-top: 0.6rem;
            padding: 0.9rem;
            font-family: 'DM Sans', sans-serif;
            font-size: 0.9rem;
            font-weight: 500;
            letter-spacing: 0.04em;
            color: white;
            background: var(--blue);
            border: none;
            border-radius: 8px;
            cursor: pointer;
            box-shadow: 0 4px 18px rgba(43,94,171,0.3);
            transition: all 0.22s ease;
        }

        .submit-button:hover {
            background: var(--blue-dk);
            transform: translateY(-2px);
            box-shadow: 0 8px 28px rgba(43,94,171,0.4);
        }

        .submit-button:active { transform: scale(0.98); }

        /* ── DIVIDER ── */
        .divider {
            border: none;
            border-top: 1px solid rgba(43,94,171,0.1);
            margin: 1.5rem 0 1.2rem;
        }

        /* ── FOOTER LINKS ── */
        .footer-links {
            display: flex;
            justify-content: center;
            align-items: center;
            gap: 0.6rem;
            font-size: 0.82rem;
            color: var(--muted);
        }

        .footer-links a {
            color: var(--blue);
            font-weight: 500;
            text-decoration: none;
            transition: color 0.2s;
        }

        .footer-links a:hover {
            color: var(--blue-dk);
            text-decoration: underline;
        }

        .footer-links .sep {
            color: rgba(43,94,171,0.25);
        }

        /* ── ANIMATIONS ── */
        @keyframes fadeUp {
            from { opacity: 0; transform: translateY(20px); }
            to   { opacity: 1; transform: translateY(0); }
        }

        @keyframes growLine {
            to { transform: scaleX(1); }
        }

        @media (max-width: 480px) {
            .login-card { padding: 2rem 1.4rem; }
            .brand { font-size: 1.7rem; }
        }
    </style>
</head>

<body>

    <div class="geo-bg">
        <svg width="100%" height="100%" xmlns="http://www.w3.org/2000/svg">
            <defs>
                <pattern id="hex" x="0" y="0" width="60" height="52" patternUnits="userSpaceOnUse">
                    <polygon points="30,2 58,17 58,47 30,62 2,47 2,17" fill="none" stroke="rgba(43,94,171,0.08)" stroke-width="1"/>
                </pattern>
                <pattern id="dots" x="0" y="0" width="40" height="40" patternUnits="userSpaceOnUse">
                    <circle cx="20" cy="20" r="1.4" fill="rgba(201,168,76,0.22)"/>
                </pattern>
            </defs>
            <rect width="100%" height="100%" fill="url(#hex)"/>
            <rect width="100%" height="100%" fill="url(#dots)"/>
            <circle cx="82%" cy="14%" r="210" fill="none" stroke="rgba(43,94,171,0.06)" stroke-width="1.5"/>
            <circle cx="82%" cy="14%" r="140" fill="none" stroke="rgba(43,94,171,0.05)" stroke-width="1"/>
            <circle cx="12%" cy="86%" r="190" fill="none" stroke="rgba(201,168,76,0.1)" stroke-width="1.5"/>
            <circle cx="12%" cy="86%" r="120" fill="none" stroke="rgba(201,168,76,0.07)" stroke-width="1"/>
        </svg>
    </div>

    <div class="login-card">

        <div class="card-header">
            <a class="brand" href="index.php">Optima <span>Flow</span></a>
            <span class="brand-underline"></span>
            <p class="card-subtitle">Welcome back! Please sign in to continue</p>
        </div>

        <?php if (!empty($error_message)): ?>
            <div class="alert alert-danger"><?= htmlspecialchars($error_message) ?></div>
        <?php endif; ?>

        <?php if ($resetSuccess): ?>
            <div class="alert alert-success">Password reset successful! You can now log in.</div>
        <?php endif; ?>

        <form action="login.php" method="POST">
            <div class="form-group">
                <label for="email" class="form-label">Email Address</label>
                <input type="email" id="email" name="email" class="form-input" placeholder="Enter your email" required>
            </div>

            <div class="form-group">
                <label for="password" class="form-label">Password</label>
                <div class="password-wrap">
                    <input type="password" id="password" name="password" class="form-input" placeholder="Enter your password" required style="padding-right: 3.5rem;">
                    <button type="button" class="toggle-password" onclick="togglePassword('password')" id="toggleBtn">Show</button>
                </div>
            </div>

            <button type="submit" class="submit-button">Sign In →</button>
        </form>

        <hr class="divider">

        <div class="footer-links">
            <a href="signup.php">Create Account</a>
            <span class="sep">|</span>
            <a href="forgot_password.php">Forgot Password?</a>
        </div>

    </div>

    <script>
        function togglePassword(inputId) {
            const input = document.getElementById(inputId);
            const btn = document.getElementById('toggleBtn');
            if (input.type === 'password') {
                input.type = 'text';
                btn.textContent = 'Hide';
            } else {
                input.type = 'password';
                btn.textContent = 'Show';
            }
        }
    </script>

</body>
</html>