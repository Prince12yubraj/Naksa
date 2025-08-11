<?php
session_start();
include('db.php');

if (isset($_SESSION['user_id'])) {
    header("Location: home.php");
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $email = $_POST['email'] ?? '';
    $password = $_POST['password'] ?? '';

    if (!empty($email) && !empty($password)) {
        $stmt = $con->prepare("SELECT id, password, first_name, last_name FROM users WHERE email = ? LIMIT 1");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result && $result->num_rows > 0) {
            $user_data = $result->fetch_assoc();

            // For simplicity, assuming plaintext password — better use hashing in production!
            if ($user_data['password'] === $password) {
                $_SESSION['user_id'] = $user_data['id'];
                // Combine first and last name if you want full name in session
                $_SESSION['user_name'] = $user_data['first_name'] . ' ' . $user_data['last_name'];
                header("Location: home.php");
                exit;
            } else {
                $error = "Wrong email or password";
            }
        } else {
            $error = "Wrong email or password";
        }
        $stmt->close();
    } else {
        $error = "Please enter email and password";
    }
}
?><!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Login</title>
  <style>
    /* Reset and base styles */
    * {
      margin: 0;
      padding: 0;
      box-sizing: border-box;
    }
    
    body {
      font-family: 'Segoe UI', Roboto, -apple-system, sans-serif;
      background-color: #f8f9fa;
      display: flex;
      justify-content: center;
      align-items: center;
      min-height: 100vh;
      line-height: 1.5;
      color: #212529;
    }

    /* Login container */
    .login-container {
      background-color: #ffffff;
      padding: 2.5rem;
      border-radius: 0.5rem;
      box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.1);
      width: 100%;
      max-width: 24rem;
      margin: 1rem;
    }

    /* Typography */
    h2 {
      text-align: center;
      margin-bottom: 1.5rem;
      font-size: 1.75rem;
      font-weight: 500;
      color: #343a40;
    }

    /* Form elements */
    input[type="email"],
    input[type="password"] {
      width: 100%;
      padding: 0.75rem 1rem;
      margin: 0.5rem 0 1rem;
      border: 1px solid #ced4da;
      border-radius: 0.375rem;
      font-size: 1rem;
      transition: border-color 0.15s ease-in-out, box-shadow 0.15s ease-in-out;
    }

    input[type="email"]:focus,
    input[type="password"]:focus {
      border-color: #80bdff;
      outline: 0;
      box-shadow: 0 0 0 0.2rem rgba(0, 123, 255, 0.25);
    }

    /* Button */
    button {
      width: 100%;
      padding: 0.75rem;
      background-color: #007bff;
      color: white;
      border: none;
      border-radius: 0.375rem;
      font-size: 1rem;
      font-weight: 500;
      cursor: pointer;
      transition: background-color 0.15s ease-in-out;
    }

    button:hover {
      background-color: #0069d9;
    }

    /* Error message */
    .error {
      color: #dc3545;
      text-align: center;
      margin-bottom: 1rem;
      padding: 0.5rem;
      background-color: #f8d7da;
      border: 1px solid #f5c6cb;
      border-radius: 0.25rem;
    }

    /* Signup link */
    .signup-link {
      text-align: center;
      margin-top: 1.5rem;
      color: #6c757d;
    }

    .signup-link a {
      color: #007bff;
      text-decoration: none;
      font-weight: 500;
      transition: color 0.15s ease-in-out;
    }

    .signup-link a:hover {
      color: #0056b3;
      text-decoration: underline;
    }
  </style>
</head>
<body>

<div class="login-container">
  <h2>Login</h2>

  <?php if (!empty($error)): ?>
    <div class="error"><?= htmlspecialchars($error) ?></div>
  <?php endif; ?>

  <form method="POST" action="">
    <input type="email" name="email" placeholder="Email" required />
    <input type="password" name="password" placeholder="Password" required />
    <button type="submit">Login</button>
  </form>

  <div class="signup-link">
    Don't have an account? <a href="signup.php">Register</a>
  </div>
</div>

</body>
</html>