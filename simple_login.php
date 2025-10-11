<?php
// Simple login for development/testing
session_start();
require_once 'setup_simple.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = $_POST['email'] ?? '';
    $password = $_POST['password'] ?? '';
    
    if ($email && $password) {
        $pdo = db();
        $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch();
        
        if ($user && password_verify($password, $user['pass_hash'])) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['user_email'] = $user['email'];
            $_SESSION['user_role'] = $user['role'];
            $_SESSION['logged_in'] = true;
            
            // Redirect based on role
            if ($user['role'] === 'admin') {
                header('Location: /admin/');
                exit;
            } else {
                header('Location: /buyer/wallet.php');
                exit;
            }
        } else {
            $error = 'Invalid credentials';
        }
    } else {
        $error = 'Please fill all fields';
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Simple Login</title>
    <style>
        body { font-family: Arial, sans-serif; max-width: 400px; margin: 50px auto; padding: 20px; }
        .form-group { margin: 15px 0; }
        label { display: block; margin-bottom: 5px; }
        input[type="email"], input[type="password"] { width: 100%; padding: 10px; box-sizing: border-box; }
        button { background: #007cba; color: white; padding: 10px 20px; border: none; cursor: pointer; }
        .error { color: red; padding: 10px; background: #ffe6e6; margin: 10px 0; }
        .users { background: #f0f0f0; padding: 15px; margin: 20px 0; }
    </style>
</head>
<body>
    <h1>Development Login</h1>
    
    <?php if (isset($error)): ?>
        <div class="error"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>
    
    <div class="users">
        <strong>Test Accounts:</strong><br>
        Admin: admin@example.com / admin123<br>
        Customer: customer@example.com / customer123
    </div>
    
    <form method="POST">
        <div class="form-group">
            <label>Email:</label>
            <input type="email" name="email" required>
        </div>
        <div class="form-group">
            <label>Password:</label>
            <input type="password" name="password" required>
        </div>
        <button type="submit">Login</button>
    </form>
    
    <hr>
    <p><a href="/">← Back to Homepage</a></p>
</body>
</html>