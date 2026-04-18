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
        //Check if user exists & verify status
        $stmt = $conn->prepare("SELECT * FROM users WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows == 1) {
            $user = $result->fetch_assoc();
            
            //Check if account is active
            if (isset($user['status']) && $user['status'] !== 'active') {
                $error_message = "Your account has been deactivated. Please contact the administrator.";
            }
            //Verify password if account is active or not
            elseif (password_verify($password, $user['password'])) {
                //Set session variables with only necessary data
                $_SESSION['user'] = [
                    'id' => $user['id'],
                    'email' => $user['email'],
                    'username' => $user['username'],
                    'role' => $user['role']
                ];
                
                //Set a flag indicating this is a new login
                $_SESSION['just_logged_in'] = true;

                //Update login_at field with the current timestamp
                $updateLoginTime = "UPDATE users SET login_at = NOW() WHERE id = ?";
                $updateStmt = $conn->prepare($updateLoginTime);
                $updateStmt->bind_param("i", $user['id']);
                $updateStmt->execute();
                $updateStmt->close();
                
                //Redirect user based on role
                if ($user['role'] === 'admin') {
                    header("Location: dashboard_admin.php"); //Manager
                } else {
                    header("Location: dashboard_customer.php"); //Customer
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

//Check if coming from password reset page or not
$resetSuccess = isset($_GET['reset']) && $_GET['reset'] == 'success';
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Bicol Depot</title>
    <!--Bootstrap CSS-->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        :root {
            --primary-blue: #1a4b84;
            --secondary-blue: #2563eb;
            --dark-blue: #0f2d4e;
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

        .background {
            background-image: url('assets/img/BGP.jpg');
            background-size: cover;
            background-position: center;
            position: fixed; 
            top: 0; left: 0; right: 0; bottom: 0;
            z-index: -1; 
        }

        .login-container {
            background: rgb(255, 255, 255);
            border-radius: 16px;
            box-shadow: 0 4px 24px rgba(0, 0, 0, 0.1);
            padding: 2.5rem;
            max-width: 450px;
            width: 100%;
            margin: 0 auto;
        }

        .logo-text {
            color: var(--primary-blue);
            text-align: center;
            font-size: 2rem;
            font-weight: 700;
            margin: 0;
        }

        .text-muted {
            text-align: center;
        }

        .form-control {
            border: 2px solid #e2e8f0;
            padding: 0.75rem 1rem;
            border-radius: 8px;
            transition: all 0.3s ease;
        }

        .form-control:focus {
            border-color: var(--secondary-blue);
            box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.1);
        }

        .btn-primary {
            background-color: var(--primary-blue);
            border: none;
            padding: 0.75rem 1.5rem;
            border-radius: 8px;
            font-weight: 600;
            transition: all 0.3s ease;
        }

        .btn-primary:hover {
            background-color: var(--dark-blue);
            transform: translateY(-1px);
        }

        .links-container a {
            color: var(--secondary-blue);
            text-decoration: none;
            font-weight: 500;
            transition: color 0.3s ease;
        }

        .links-container a:hover {
            color: var(--dark-blue);
        }

        .alert {
            border-radius: 8px;
            font-weight: 500;
        }

        /*Password input styles*/
        .password-input {
            position: relative;
        }

        .toggle-password {
            position: absolute;
            right: 12px;
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
    </style>
</head>

<body>
    <div class="background"></div>
    <div class="container">
        <div class="login-container">
            <div class="logo-container">
                <h1 class="logo-text">Bicol Depot</h1>
                <p class="text-muted mb-4">Welcome back! Please login to your account.</p>
            </div>

            <?php if (!empty($error_message)): ?>
                <div class="alert alert-danger text-center"><?= htmlspecialchars($error_message) ?></div>
            <?php endif; ?>

            <?php if ($resetSuccess): ?>
                <div class="alert alert-success text-center">Password reset successful! You can now log in with your new password.</div>
            <?php endif; ?>

            <form action="login.php" method="POST">
                <div class="mb-4">
                    <label for="email" class="form-label text-dark fw-medium">Email Address</label>
                    <input type="email" class="form-control" id="email" name="email" required
                        placeholder="Enter your email">
                </div>
                <div class="mb-4">
                    <label for="password" class="form-label text-dark fw-medium">Password</label>
                    <div class="password-input">
                        <input type="password" class="form-control" id="password" name="password" required
                            placeholder="Enter your password">
                        <button type="button" class="toggle-password" onclick="togglePassword('password')">
                            Show
                        </button>
                    </div>
                </div>
                <button type="submit" class="btn btn-primary w-100 mb-4">Sign In</button>
                <div class="links-container text-center">
                    <a href="signup.php" class="me-3">Create Account</a>
                    <span class="text-muted">|</span>
                    <a href="forgot_password.php" class="ms-3">Forgot Password?</a>
                </div>
            </form>
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