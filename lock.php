<?php
session_start();

$db_host = getenv('DB_HOST');
$db_name = getenv('DB_NAME');
$db_user = getenv('DB_USER');
$db_pass = getenv('DB_PASS');
$db_port = getenv('DB_PORT') ?: '5432';

header('Content-Type: application/json');

// Grab payload sent from frontend JavaScript
$input = json_decode(file_get_contents('php://input'), true);
$email = filter_var($input['email'] ?? '', FILTER_VALIDATE_EMAIL);

if (!$email) {
    echo json_encode(['success' => false, 'error' => 'Invalid email address provided.']);
    exit;
}

// Track email in current session state
$_SESSION['has_spun'] = true;
$_SESSION['user_email'] = $email;

try {
    $dsn = "pgsql:host=$db_host;port=$db_port;dbname=$db_name";
    $pdo = new PDO($dsn, $db_user, $db_pass, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);

    // Insert record or ignore if it already exists to prevent duplicates
    $stmt = $pdo->prepare("INSERT INTO spin_locks (email) VALUES (:email) ON CONFLICT (email) DO NOTHING");
    $stmt->execute(['email' => $email]);

    echo json_encode(['success' => true]);
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'error' => 'Database persistence failure.']);
}
?>
