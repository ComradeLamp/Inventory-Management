<?php
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $email = $_POST['email'];

    // Redirect to reset_password.php with email passed via GET
    header("Location: reset_password.php?email=" . urlencode($email));
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Forgot Password - Optima Flow</title>
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

        .forgot-password-container {
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
            text-align: center;
        }

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

        .forgot-password-title {
            font-size: 0.85rem;
            font-weight: 400;
            color: var(--muted);
            letter-spacing: 0.04em;
            margin-bottom: 1.8rem;
        }

        .form-group {
            margin-bottom: 1.1rem;
            text-align: left;
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

        .back-link {
            display: inline-block;
            margin-top: 1.4rem;
            color: var(--blue);
            font-size: 0.82rem;
            font-weight: 500;
            text-decoration: none;
            transition: color 0.2s;
        }

        .back-link:hover {
            color: var(--blue-dk);
            text-decoration: underline;
        }

        @keyframes fadeUp {
            from { opacity: 0; transform: translateY(20px); }
            to   { opacity: 1; transform: translateY(0); }
        }

        @keyframes growLine {
            to { transform: scaleX(1); }
        }

        @keyframes spin {
            to { transform: rotate(360deg); }
        }

        @media (max-width: 480px) {
            .forgot-password-container { padding: 2rem 1.4rem; }
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

    <div class="forgot-password-container">
        <div class="card-header">
            <a class="brand" href="index.php">Optima <span>Flow</span></a>
            <span class="brand-underline"></span>
            <p class="forgot-password-title">Enter your email to reset your password</p>
        </div>

        <form method="POST" action="forgot_password.php" id="forgotPasswordForm">
            <div class="form-group">
                <label for="email" class="form-label">Email Address</label>
                <input
                    type="email"
                    id="email"
                    name="email"
                    class="form-input"
                    placeholder="Enter your email"
                    required>
            </div>

            <button type="submit" class="submit-button" id="forgotSubmitBtn">Send Reset Link</button>
        </form>

        <a href="login.php" class="back-link">Back to Login</a>
    </div>

    <script>
        const forgotPasswordForm = document.getElementById('forgotPasswordForm');
        const forgotSubmitBtn = document.getElementById('forgotSubmitBtn');

        forgotPasswordForm.addEventListener('submit', () => {
            forgotSubmitBtn.classList.add('loading');
            forgotSubmitBtn.textContent = 'Sending Link...';
            forgotSubmitBtn.disabled = true;
        });
    </script>
</body>

</html>
