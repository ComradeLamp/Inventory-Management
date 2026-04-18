<?php
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $email = $_POST['email'];

    //Redirect to reset_password.php with email passed via GET
    header("Location: reset_password.php?email=" . urlencode($email));
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Forgot Password</title>
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
            background-image: url('assets/img/BGP.jpg'); 
            background-size: cover; 
            background-position: center;
        }

        .forgot-password-container {
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            padding: 20px;
            text-align: center;
            margin-bottom: 2rem;
        }

        .forgot-password-card {
            width: 100%;
            max-width: 480px;
            background-color: #ffffff;
            border-radius: 12px;
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.05);
            padding: 40px;
            text-align: center;
        }

        .forgot-password-title {
            color: var(--primary-blue);
            font-size: 2rem;
            font-weight: 700;
            margin: 0;
        }

        .forgot-password-subtitle {
            color: #5a6474;
            font-size: 16px;
            margin-bottom: 32px;
        }

        .form-group {
            margin-bottom: 24px;
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
            border-color: #254b87;
            box-shadow: 0 0 0 4px rgba(37, 75, 135, 0.1);
        }

        .form-input::placeholder {
            color: #98a2b3;
        }

        .submit-button {
            width: 100%;
            padding: 14px;
            font-size: 16px;
            font-weight: 600;
            color: #ffffff;
            background-color: #254b87;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            transition: background-color 0.2s ease, transform 0.1s ease;
        }

        .submit-button:hover {
            background-color: var(--dark-blue);
            transform: translateY(-1px);
        }

        .submit-button:active {
            transform: scale(0.98);
        }

        .back-link {
            display: inline-block;
            margin-top: 24px;
            color: #254b87;
            font-size: 16px;
            font-weight: 500;
            text-decoration: none;
            transition: color 0.2s ease;
        }

        .back-link:hover {
            color: #1a3c6d;
            text-decoration: underline;
        }

        @media (max-width: 576px) {
            .forgot-password-card {
                padding: 24px;
            }

            .forgot-password-title {
                font-size: 24px;
            }

            .forgot-password-subtitle {
                font-size: 14px;
                margin-bottom: 24px;
            }
        }
    </style>
</head>

<body>
    <div class="background"></div>
    <div class="forgot-password-container">
        <div class="forgot-password-card">
            <h1 class="forgot-password-title">Bicol Depot</h1>
            <p class="forgot-password-subtitle">Enter your email to reset your password</p>

            <form method="POST" action="forgot_password.php">
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

                <button type="submit" class="submit-button">
                    Send Reset Link
                </button>
            </form>

            <a href="login.php" class="back-link">
                Back to Login
            </a>
        </div>
    </div>
</body>

</html>