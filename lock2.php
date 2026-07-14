<?php
// lock.php
session_start();

// Set the session lock
$_SESSION['has_spun'] = true;

// Create a permanent server file lock so closing the browser doesn't reset it
file_put_contents('lockout.txt', 'locked_at_' . time());

header('Content-Type: application/json');
echo json_encode(['success' => true]);
?>
