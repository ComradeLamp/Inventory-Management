<?php
include 'db.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = $_POST['username'];
    $email    = $_POST['email'];
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
    $role = isset($_POST['role']) ? $_POST['role'] : 'customer';

    $stmt = $conn->prepare("INSERT INTO users (username, email, password, role) VALUES (?, ?, ?, ?)");
    $stmt->bind_param("ssss", $username, $email, $password, $role);
    $stmt->execute();

    echo "<script>
        alert('Account created successfully!');
        window.location.href = 'login.php';
    </script>";
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sign Up - Optima Flow</title>
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

        /* Background decorative blobs */
        .geo-bg {
            position: fixed;
            inset: 0;
            z-index: 0;
            pointer-events: none;
        }

        /* ── CARD ── */
        .signup-card {
            position: relative;
            z-index: 1;
            width: 100%;
            max-width: 440px;
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
            margin-bottom: 2rem;
        }

        .brand {
            font-family: 'Playfair Display', serif;
            font-size: 2rem;
            font-weight: 900;
            color: var(--dark);
            letter-spacing: -0.02em;
            display: block;
            margin-bottom: 0.3rem;
            text-decoration: none;
        }

        .brand span { color: var(--blue); }

        .brand-underline {
            display: block;
            width: 40px;
            height: 3px;
            background: var(--gold);
            border-radius: 2px;
            margin: 0.5rem auto 0.8rem;
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
        .password-wrap { position: relative; }

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

        .submit-button.loading {
            position: relative;
            pointer-events: none;
            opacity: 0.92;
            color: transparent;
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

        /* ── FOOTER LINK ── */
        .login-link {
            display: block;
            margin-top: 1.4rem;
            text-align: center;
            font-size: 0.82rem;
            color: var(--muted);
            text-decoration: none;
            transition: color 0.2s;
        }

        .login-link a {
            color: var(--blue);
            font-weight: 500;
            text-decoration: none;
        }

        .login-link a:hover {
            color: var(--blue-dk);
            text-decoration: underline;
        }

        /* ── DIVIDER ── */
        .divider {
            border: none;
            border-top: 1px solid rgba(43,94,171,0.1);
            margin: 1.6rem 0 1.2rem;
        }

        /* ── ANIMATIONS ── */
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
            .signup-card { padding: 2rem 1.4rem; }
            .brand { font-size: 1.7rem; }
        }
    </style>
</head>

<body>

    <div class="geo-bg">
        <svg width="100%" height="100%" xmlns="http://www.w3.org/2000/svg">
            <defs>
                <pattern id="tri" x="0" y="0" width="60" height="60" patternUnits="userSpaceOnUse">
                    <polygon points="30,4 56,56 4,56" fill="none" stroke="rgba(43,94,171,0.07)" stroke-width="1"/>
                </pattern>
                <pattern id="dots" x="0" y="0" width="30" height="30" patternUnits="userSpaceOnUse">
                    <circle cx="15" cy="15" r="1.3" fill="rgba(43,94,171,0.12)"/>
                </pattern>
            </defs>
            <rect width="100%" height="100%" fill="url(#tri)"/>
            <rect width="100%" height="100%" fill="url(#dots)"/>
            <circle cx="18%" cy="18%" r="220" fill="none" stroke="rgba(43,94,171,0.06)" stroke-width="1.5"/>
            <circle cx="18%" cy="18%" r="150" fill="none" stroke="rgba(43,94,171,0.05)" stroke-width="1"/>
            <circle cx="85%" cy="82%" r="200" fill="none" stroke="rgba(201,168,76,0.1)" stroke-width="1.5"/>
            <circle cx="85%" cy="82%" r="130" fill="none" stroke="rgba(201,168,76,0.07)" stroke-width="1"/>
        </svg>
    </div>

    <div class="signup-card">

        <div class="card-header">
            <a class="brand" href="index.php">Optima <span>Flow</span></a>
            <span class="brand-underline"></span>
            <p class="card-subtitle">Create your account to get started</p>
        </div>

        <form action="signup.php" method="POST" id="signupForm">
            <div class="form-group">
                <label for="username" class="form-label">Username</label>
                <input type="text" id="username" name="username" class="form-input" placeholder="Enter your username" required>
            </div>

            <div class="form-group">
                <label for="email" class="form-label">Email Address</label>
                <input type="email" id="email" name="email" class="form-input" placeholder="Enter your email" required>
            </div>

            <div class="form-group">
                <label for="password" class="form-label">Password</label>
                <div class="password-wrap">
                    <input type="password" id="password" name="password" class="form-input" placeholder="Create a password" required style="padding-right: 3.5rem;">
                    <button type="button" class="toggle-password" onclick="togglePassword('password', this)" id="toggleBtnSignup" aria-label="Show password" title="Show password">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                            <path d="M2.062 12.348a1 1 0 0 1 0-.696A10.94 10.94 0 0 1 12 5c4.9 0 9.27 3 10.94 6.652a1 1 0 0 1 0 .696A10.94 10.94 0 0 1 12 19c-4.9 0-9.27-3-10.94-6.652Z"></path>
                            <path d="M4 4l16 16"></path>
                            <circle cx="12" cy="12" r="3"></circle>
                        </svg>
                    </button>
                </div>
            </div>

            <!--THE ROLES
            <div class="form-group">
                <label for="role" class="form-label">Role</label>
                <select id="role" name="role" class="form-input" required>
                    <option value="customer">Customer</option>
                    <option value="admin">Admin</option>
                </select>
            </div>
            -->

            <button type="submit" class="submit-button" id="signupSubmitBtn">Create Account →</button>
        </form>

        <hr class="divider">

        <p class="login-link">Already have an account? <a href="login.php">Log in</a></p>

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

        const signupForm = document.getElementById('signupForm');
        const signupSubmitBtn = document.getElementById('signupSubmitBtn');

        signupForm.addEventListener('submit', () => {
            signupSubmitBtn.classList.add('loading');
            signupSubmitBtn.textContent = 'Creating Account...';
            signupSubmitBtn.disabled = true;
        });
    </script>

</body>
</html>