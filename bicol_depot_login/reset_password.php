<?php
include 'db.php';

$message = "";
$messageType = "";
$email = $_GET['email'] ?? '';
$token = $_GET['token'] ?? '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $email = $_POST['email'];
    $new_password = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];

    if ($new_password !== $confirm_password) {
        $message = "Passwords do not match.";
        $messageType = "error";
    } else {
        // Ensure the email exists in the database
        $stmt = $conn->prepare("SELECT * FROM users WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
            $update_stmt = $conn->prepare("UPDATE users SET password = ? WHERE email = ?");
            $update_stmt->bind_param("ss", $hashed_password, $email);
            $update_stmt->execute();

            if ($update_stmt->affected_rows > 0) {
                // Redirect to login page after successful password reset
                header("Location: login.php?reset=success");
                exit();
            } else {
                $message = "Password reset failed. Please try again.";
                $messageType = "error";
            }
        } else {
            $message = "Email not found.";
            $messageType = "error";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reset Password - Optima Flow</title>
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@700;900&family=DM+Sans:wght@300;400;500&display=swap" rel="stylesheet">
    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

        :root {
            --blue: #2B5EAB;
            --blue-dk: #1A3F7A;
            --gold: #C9A84C;
            --beige: #F5F0DC;
            --dark: #0E1F3D;
            --mid: #2C3A5E;
            --muted: #6B7A99;
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

        .reset-card {
            position: relative;
            z-index: 1;
            width: 100%;
            max-width: 480px;
            margin: 2rem 1.2rem;
            background: rgba(255,255,255,0.75);
            backdrop-filter: blur(18px);
            -webkit-backdrop-filter: blur(18px);
            border: 1px solid rgba(43,94,171,0.12);
            border-radius: 16px;
            box-shadow: 0 20px 60px rgba(14,31,61,0.1), 0 4px 16px rgba(14,31,61,0.06);
            padding: 3rem 2.6rem 2.5rem;
            animation: fadeUp 0.6s ease both;
        }

        .card-header {
            text-align: center;
            margin-bottom: 1.6rem;
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

        .alert {
            padding: 0.7rem 1rem;
            border-radius: 8px;
            font-size: 0.82rem;
            margin-bottom: 1.2rem;
            text-align: center;
        }

        .alert.error {
            background: rgba(192,57,43,0.08);
            border: 1px solid rgba(192,57,43,0.2);
            color: #922B21;
        }

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

        .form-input[readonly] {
            opacity: 0.85;
        }

        .password-wrap {
            position: relative;
        }

        .toggle-password {
            position: absolute;
            right: 12px;
            top: 50%;
            transform: translateY(-50%);
            width: 2.15rem;
            height: 2.15rem;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            background: none;
            border: none;
            color: var(--muted);
            cursor: pointer;
            padding: 0;
            line-height: 0;
            transition: color 0.2s;
        }

        .toggle-password svg {
            width: 20px;
            height: 20px;
            display: block;
        }

        .toggle-password:hover { color: var(--blue); }

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
            position: relative;
        }

        .submit-button.loading {
            pointer-events: none;
            color: transparent;
            opacity: 0.92;
        }

        .submit-button.loading::before {
            content: '';
            position: absolute;
            top: 50%;
            left: 50%;
            width: 1.25rem;
            height: 1.25rem;
            margin-top: -0.625rem;
            margin-left: -0.625rem;
            border: 3px solid #F5F0DC;
            border-top-color: #C9A84C;
            border-right-color: #1A3F7A;
            border-bottom-color: #C9A84C;
            border-left-color: #F5F0DC;
            border-radius: 50%;
            animation: spin 1.35s linear infinite;
        }

        .submit-button:hover {
            background: var(--blue-dk);
            transform: translateY(-2px);
            box-shadow: 0 8px 28px rgba(43,94,171,0.4);
        }

        .submit-button:active {
            transform: scale(0.98);
        }

        @keyframes fadeUp {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }

        @keyframes growLine {
            to { transform: scaleX(1); }
        }

        @keyframes spin {
            to { transform: rotate(360deg); }
        }

        @media (max-width: 480px) {
            .reset-card { padding: 2rem 1.4rem; }
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

    <div class="reset-card">
        <div class="card-header">
            <a class="brand" href="index.php">Optima <span>Flow</span></a>
            <span class="brand-underline"></span>
            <p class="card-subtitle">Reset your password</p>
        </div>

        <?php if ($message): ?>
            <div class="alert <?php echo $messageType; ?>">
                <?php echo htmlspecialchars($message); ?>
            </div>
        <?php endif; ?>

        <form method="POST" action="reset_password.php" id="resetForm">
            <div class="form-group">
                <label for="email" class="form-label">Email Address</label>
                <input
                    type="email"
                    id="email"
                    name="email"
                    class="form-input"
                    value="<?php echo htmlspecialchars($email); ?>"
                    <?php echo $email ? 'readonly' : ''; ?>
                    required
                    placeholder="Enter your email">
            </div>

            <div class="form-group">
                <label for="new_password" class="form-label">New Password</label>
                <div class="password-wrap">
                    <input
                        type="password"
                        id="new_password"
                        name="new_password"
                        class="form-input"
                        required
                        minlength="8"
                        placeholder="Enter new password"
                        style="padding-right: 3.5rem;">
                    <button type="button" class="toggle-password" onclick="togglePassword('new_password', this)" aria-label="Show password" title="Show password">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                            <path d="M2.062 12.348a1 1 0 0 1 0-.696A10.94 10.94 0 0 1 12 5c4.9 0 9.27 3 10.94 6.652a1 1 0 0 1 0 .696A10.94 10.94 0 0 1 12 19c-4.9 0-9.27-3-10.94-6.652Z"></path>
                            <path d="M4 4l16 16"></path>
                            <circle cx="12" cy="12" r="3"></circle>
                        </svg>
                    </button>
                </div>
            </div>

            <div class="form-group">
                <label for="confirm_password" class="form-label">Confirm New Password</label>
                <div class="password-wrap">
                    <input
                        type="password"
                        id="confirm_password"
                        name="confirm_password"
                        class="form-input"
                        required
                        minlength="8"
                        placeholder="Confirm new password"
                        style="padding-right: 3.5rem;">
                    <button type="button" class="toggle-password" onclick="togglePassword('confirm_password', this)" aria-label="Show password" title="Show password">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                            <path d="M2.062 12.348a1 1 0 0 1 0-.696A10.94 10.94 0 0 1 12 5c4.9 0 9.27 3 10.94 6.652a1 1 0 0 1 0 .696A10.94 10.94 0 0 1 12 19c-4.9 0-9.27-3-10.94-6.652Z"></path>
                            <path d="M4 4l16 16"></path>
                            <circle cx="12" cy="12" r="3"></circle>
                        </svg>
                    </button>
                </div>
            </div>

            <button type="submit" class="submit-button" id="resetSubmitBtn">Reset Password</button>
        </form>
    </div>

    <script>
        const eyeIcon = '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M1.5 12s4-7.5 10.5-7.5S22.5 12 22.5 12 18.5 19.5 12 19.5 1.5 12 1.5 12Z"></path><circle cx="12" cy="12" r="3"></circle></svg>';
        const eyeOffIcon = '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M2.062 12.348a1 1 0 0 1 0-.696A10.94 10.94 0 0 1 12 5c4.9 0 9.27 3 10.94 6.652a1 1 0 0 1 0 .696A10.94 10.94 0 0 1 12 19c-4.9 0-9.27-3-10.94-6.652Z"></path><path d="M4 4l16 16"></path><circle cx="12" cy="12" r="3"></circle></svg>';

        function togglePassword(inputId, btn) {
            const input = document.getElementById(inputId);
            if (input.type === 'password') {
                input.type = 'text';
                btn.innerHTML = eyeIcon;
                btn.setAttribute('aria-label', 'Hide password');
                btn.setAttribute('title', 'Hide password');
            } else {
                input.type = 'password';
                btn.innerHTML = eyeOffIcon;
                btn.setAttribute('aria-label', 'Show password');
                btn.setAttribute('title', 'Show password');
            }
        }

        const resetForm = document.getElementById('resetForm');
        const resetSubmitBtn = document.getElementById('resetSubmitBtn');

        resetForm.addEventListener('submit', () => {
            resetSubmitBtn.classList.add('loading');
            resetSubmitBtn.textContent = 'Resetting Password...';
            resetSubmitBtn.disabled = true;
        });
    </script>
</body>

</html>
