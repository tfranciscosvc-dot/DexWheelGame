<?php
session_start();

$db_host = getenv('DB_HOST');
$db_name = getenv('DB_NAME');
$db_user = getenv('DB_USER');
$db_pass = getenv('DB_PASS');
$db_port = getenv('DB_PORT') ?: '5432';

header('Content-Type: application/json');

$input = json_decode(file_get_contents('php://input'), true);
$email = filter_var($input['email'] ?? '', FILTER_VALIDATE_EMAIL);

if (!$email) {
    echo json_encode(['success' => false, 'error' => 'Invalid email address.']);
    exit;
}

try {
    $dsn = "pgsql:host=$db_host;port=$db_port;dbname=$db_name";
    $pdo = new PDO($dsn, $db_user, $db_pass, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);

    // 1. STRICT CHECK: Does this email already exist in the database?
    $stmt = $pdo->prepare("SELECT id FROM spin_locks WHERE email = :email");
    $stmt->execute(['email' => $email]);
    
    if ($stmt->fetch()) {
        // Email found! Reject the spin request immediately.
        echo json_encode(['success' => false, 'error' => 'already_locked']);
        exit;
    }

    // 2. If it doesn't exist, lock them now and allow the spin
    $_SESSION['has_spun'] = true;
    $_SESSION['user_email'] = $email;

    $stmt = $pdo->prepare("INSERT INTO spin_locks (email) VALUES (:email)");
    $stmt->execute(['email' => $email]);

    echo json_encode(['success' => true]);
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'error' => 'Database failure.']);
}
?>
