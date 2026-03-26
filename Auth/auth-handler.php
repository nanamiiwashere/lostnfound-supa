<?php
require_once './Auth/auth3thparty.php';
require_once './connect.php';
require_once './Auth/turnstile.php';
$error = '';

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email']);
    $password = trim($_POST['password']);

    if (!$email || !$password) {
        $error = "Please fill in all fields.";
    } else{

        $token = $_POST['cf-turnstile-response'] ?? '';
        if (!verifyTurnstile($token, $_SERVER['REMOTE_ADDR'])){
            $error = "Human verification failed. Please Try Again.";
        } else {

            $stmt = $pdo->prepare("SELECT * FROM users WHERE email = :email");
            $stmt->execute(['email' => $email]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            if ($user && password_verify($password, $user['password'])) {
                session_regenerate_id(true);
                $_SESSION['user_id'] = $user['id_user'];
                $_SESSION['email'] = $user['email'];
                loginUser($user);
                $_SESSION['role'] = $user['role'];
                if ($user['role'] === 'staff') {
                    header('Location: ' . APP_URL . '/staff/index.php');
                } else {
                    header('Location: ' . APP_URL . '/dashboard/index.php');
                }
                exit();
            } else {
                $error = "Invalid email or password.";
            }

        } 
    }
}
