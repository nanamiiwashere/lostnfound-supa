<?php
require_once './Auth/auth3thparty.php';
require_once './connect.php';
$error = '';

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email']);
    $password = trim($_POST['password']);

    if (!$email || !$password) {
        $error = "Please fill in all fields.";
    } else{

    $stmt = $pdo->prepare("SELECT * FROM users WHERE email = :email");
    $stmt->execute(['email' => $email]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($user && password_verify($password, $user['password'])) {

        session_regenerate_id(true); // Prevent session fixation attacks
        $_SESSION['user_id'] = $user['id_user'];
        $_SESSION['email'] = $user['email'];
        loginUser($user);
        $_SESSION['role'] = $user['role'];
        if ($user['role'] === 'staff') {
            header('Location: ' . APP_URL . '/staff/index.php');
        } else {
            header('Location: ' . APP_URL . '/dashboard/index.php');
        }
        header('Location: '. APP_URL . '/dashboard/index.php'); // Redirect to a protected page
        exit();

        } else {
            // Authentication failed
            $error = "Invalid email or password.";
            }
    }
}
