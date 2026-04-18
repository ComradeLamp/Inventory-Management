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
        //Ensure the email exists in the database
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
                //Redirect to login page after successful password reset
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
    <title>Reset Password - Bicol Depot</title>
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
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif;
            line-height: 1.5;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0;
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

        .container {
            width: 100%;
            max-width: 400px;
            margin: auto;
        }

        .card {
            background: white;
            border-radius: 12px;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
        }

        .card:hover {
            box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1);
        }

        .content {
            padding: 24px;
        }

        h1 {
            color: var(--primary-blue);
            font-size: 2rem;
            font-weight: 700;
            margin: 0;
            text-align: center;
        }

        h2 {
            text-align: center;
            color: #1f2937;
            font-size: 1.25rem;
            margin-bottom: 15px;
        }

        .alert {
            padding: 0.75rem;
            border-radius: 6px;
            margin-bottom: 1rem;
            font-size: 0.875rem;
        }

        .alert.success {
            background-color: #f0fdf4;
            border: 1px solid #dcfce7;
            color: #16a34a;
        }

        .alert.error {
            background-color: #fef2f2;
            border: 1px solid #fee2e2;
            color: #dc2626;
        }

        .alert a {
            color: #2563eb;
            text-decoration: underline;
        }

        .form-group {
            margin-bottom: 15px;
        }

        label {
            display: block;
            font-size: 0.875rem;
            font-weight: 500;
            color: #374151;
            margin-bottom: 0.25rem;
        }

        input {
            width: 100%;
            padding: 0.5rem 0.75rem;
            border: 1px solid #d1d5db;
            border-radius: 6px;
            font-size: 0.875rem;
            transition: all 0.15s ease;
        }

        input:focus {
            outline: none;
            border-color: #2563eb;
            box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.1);
        }

        input[readonly] {
            background-color: #f9fafb;
        }

        .password-input {
            position: relative;
        }

        .toggle-password {
            position: absolute;
            right: 0.75rem;
            top: 50%;
            transform: translateY(-50%);
            background: none;
            border: none;
            color: #6b7280;
            font-size: 0.875rem;
            cursor: pointer;
            padding: 0.25rem;
        }

        .toggle-password:hover {
            color: #374151;
        }

        .submit-btn {
            width: 100%;
            background-color: #1e40af;
            color: white;
            padding: 0.625rem;
            border: none;
            border-radius: 6px;
            font-weight: 500;
            cursor: pointer;
            transition: background-color 0.2s;
        }

        .submit-btn:hover {
            background-color: #1e3a8a;
        }

        @media (max-width: 640px) {
            .content {
                padding: 1.5rem;
            }
        }
    </style>
</head>

<body>
    <div class="background"></div>
    <div class="container">
        <div class="card">
            <div class="content">
                <h1>Bicol Depot</h1>
                <h2>Reset Your Password</h2>

                <?php if ($message): ?>
                    <div class="alert <?php echo $messageType; ?>">
                        <?php echo $message; ?>
                    </div>
                <?php endif; ?>

                <form method="POST" action="reset_password.php">
                    <div class="form-group">
                        <label for="email">Email Address</label>
                        <input
                            type="email"
                            id="email"
                            name="email"
                            value="<?php echo htmlspecialchars($email); ?>"
                            <?php echo $email ? 'readonly' : ''; ?>
                            required
                            placeholder="Enter your email">
                    </div>

                    <div class="form-group">
                        <label for="new_password">New Password</label>
                        <div class="password-input">
                            <input
                                type="password"
                                id="new_password"
                                name="new_password"
                                required
                                minlength="8"
                                placeholder="Enter new password">
                            <button type="button" class="toggle-password" onclick="togglePassword('new_password')">
                                Show
                            </button>
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="confirm_password">Confirm New Password</label>
                        <div class="password-input">
                            <input
                                type="password"
                                id="confirm_password"
                                name="confirm_password"
                                required
                                minlength="8"
                                placeholder="Confirm new password">
                            <button type="button" class="toggle-password" onclick="togglePassword('confirm_password')">
                                Show
                            </button>
                        </div>
                    </div>

                    <button type="submit" class="submit-btn">Reset Password</button>
                </form>
            </div>
        </div>
    </div>

    <script>
        function togglePassword(inputId) {
            const input = document.getElementById(inputId);
            const button = input.nextElementSibling;

            if (input.type === 'password') {
                input.type = 'text';
                button.textContent = 'Hide';
            } else {
                input.type = 'password';
                button.textContent = 'Show';
            }
        }
    </script>
</body>

</html>