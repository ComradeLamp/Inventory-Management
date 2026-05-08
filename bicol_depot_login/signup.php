<?php
include 'db.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = $_POST['username'];
    $email    = $_POST['email'];
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
    $role = isset($_POST['role']) ? $_POST['role'] : 'customer';
    //'customer' or 'admin' but now just customer as default

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
    <title>Sign Up</title>
    <style>
        :root {
            --primary-blue: #1a4b84;
            --secondary-blue: #2563eb;
            --dark-blue: #0f2d4e;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
            line-height: 1.5;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0;
        }

        .gold-text {
            color: #FFD662;
        }

        .background {
            background-image: url('assets/img/BGP.jpg');
            background-size: cover;
            background-position: center;
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            z-index: -1;
        }

        .signup-container {
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 90vh;
            text-align: center;
            width: 480px;
        }

        .signup-card {
            width: 100%;
            max-width: 700px;
            background-color: #ffffff;
            border-radius: 12px;
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.05);
            padding: 30px;
            text-align: center;
        }

        .signup-title {
            color: var(--primary-blue);
            font-family: 'Playfair Display', serif;
            font-size: 2.3rem;
            font-weight: 1000;
            margin: 0;
        }

        .signup-subtitle {
            color: #5a6474;
            font-size: 16px;
            margin-bottom: 12px;
        }

        .form-group {
            margin-bottom: 15px;
            text-align: left;
        }

        .form-label {
            display: block;
            color: #344054;
            font-size: 16px;
            font-weight: 500;
            margin-bottom: 8px;
        }

        .form-input {
            width: 100%;
            padding: 14px 16px;
            font-size: 16px;
            color: #1c2b41;
            background-color: #ffffff;
            border: 1px solid #d0d5dd;
            border-radius: 8px;
            transition: all 0.2s ease;
        }

        .form-input:focus {
            outline: none;
            box-shadow: 0 0 6px rgba(255, 214, 98, 0.22);
            box-shadow: 0 0 6px #FFD662, 0 0 14px rgba(255, 214, 98, 0.45);
            /*border-color: #254b87;
            box-shadow: 0 0 0 4px rgba(37, 75, 135, 0.1);*/
        }

        .form-input::placeholder {
            color: #98a2b3;
        }

        .form-select {
            width: 100%;
            padding: 14px 16px;
            font-size: 16px;
            color: #1c2b41;
            background-color: #ffffff;
            border: 1px solid #d0d5dd;
            border-radius: 8px;
            background-image: url('assets/img/BGPic.jpg');
            background-image: url('assets/img/BGPic.jpg');
            background-repeat: no-repeat;
            background-position: cover;
        }

        .form-select:focus {
            outline: none;
            border-color: #254b87;
            box-shadow: 0 0 0 4px rgba(37, 75, 135, 0.1);
        }

        .submit-button {
            position: relative;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            width: 100%;
            padding: 14px;
            font-size: 16px;
            font-weight: 600;
            color: #ffffff;
            background-color: var(--primary-blue);
            border: none;
            border-radius: 8px;
            cursor: pointer;
            transition: background-color 0.2s ease, transform 0.1s ease;
            box-shadow: 0 0 8px rgba(255, 214, 98, 0.4);
        }

        .submit-button:hover {
            background-color: var(--dark-blue);
            transform: translateY(-1px);
            box-shadow: 0 0 12px #FFD662, 0 0 20px rgba(255, 214, 98, 0.6); 
        }

        .submit-button:active {
            transform: scale(0.98);
        }

        .button-spinner {
            position: absolute;
            width: 18px;
            height: 18px;
            border-radius: 50%;
            border: 3px solid rgba(255, 255, 255, 0.25);
            border-top-color: #ffffff;
            border-right-color: #FFD662;
            animation: spin 0.10s linear infinite;
            opacity: 0;
            transition: opacity 0.15s ease;
        }

        .submit-button.is-loading {
            cursor: wait;
        }

        .submit-button.is-loading .button-label {
            opacity: 0;
        }

        .submit-button.is-loading .button-spinner {
            opacity: 1;
        }

        @keyframes spin {
            to {
                transform: rotate(360deg);
            }
        
        }

        .login-link {
            display: block;
            margin: 24px auto 0;
            color: #254b87;
            font-size: 16px;
            font-weight: 500;
            text-decoration: none;
            transition: color 0.2s ease;
            text-align: center;
        }

        .login-link:hover {
            color: #1a3c6d;
            text-decoration: underline;
        }

        @media (max-width: 576px) {
            .signup-card {
                padding: 24px;
            }

            .signup-title {
                font-size: 24px;
            }

            .signup-subtitle {
                font-size: 14px;
                margin-bottom: 24px;
            }
        }
    </style>
</head>

<body>
    <div class="background"></div>
    <div class="background"></div>
    <div class="signup-container">
        <div class="signup-card">
            <h1 class="signup-title">Optima<span class="gold-text">Flow</span></h1>
            <p class="signup-subtitle">Create your account</p>

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
                    <input type="password" id="password" name="password" class="form-input" placeholder="Create a password" required>
                </div>
                <!--THE ROLES
                <div class="form-group">
                    <div class="form-group">
                        <label for="role" class="form-label">Role</label>
                        <select id="role" name="role" class="form-select" required>
                            <option value="customer">Customer</option>
                            <option value="admin">Admin</option>
                        </select>
                    </div>
            -->
                <button type="submit" class="submit-button" id="signupButton">
                    <span class="button-label">Create Account</span>
                    <span class="button-spinner" aria-hidden="true"></span>
                </button>
            </form>

            <a href="login.php" class="login-link">
                Already have an account? Login
            </a>
        </div>
    </div>

    <script>
        const signupForm = document.getElementById('signupForm');
        const signupButton = document.getElementById('signupButton');

        signupForm.addEventListener('submit', function (event) {
            event.preventDefault();
            signupButton.classList.add('is-loading');
            signupButton.disabled = true;
            signupButton.setAttribute('aria-busy', 'true');

            window.setTimeout(() => {
                signupForm.submit();
            }, 250);
        });
    </script>
</body>

</html>