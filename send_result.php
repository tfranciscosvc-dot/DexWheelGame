<?php
// send_result.php

// 1. Set response headers to JSON
header('Content-Type: application/json');

// 2. Get the POST data (sent as JSON body)
$json_data = file_get_contents('php://input');
$data = json_decode($json_data, true);

// 3. Validate data exists
if (!$data || !isset($data['address']) || !isset($data['email']) || !isset($data['prize']) || !isset($data['adminEmail'])) {
    echo json_encode(['success' => false, 'message' => 'Invalid data received.']);
    exit;
}

// 4. Sanitize input to prevent injection
$userAddress = filter_var($data['address'], FILTER_SANITIZE_STRING);
$userEmail = filter_var($data['email'], FILTER_SANITIZE_EMAIL);
$prizeWon = filter_var($data['prize'], FILTER_SANITIZE_NUMBER_INT);
$toAdmin = filter_var($data['adminEmail'], FILTER_SANITIZE_EMAIL);

// 5. Construct the Email
$subject = "Fortune Wheel Spin Result - Proof of Concept";

$message = "A user has spun the wheel of fortune.\r\n\r\n";
$message .= "--- Spin Details ---\r\n";
$message .= "User Email: " . $userEmail . "\r\n";
$message .= "User Address: " . $userAddress . "\r\n";
$message .= "Prize Won: " . $prizeWon . "\r\n\r\n";
$message .= "This is an automated message from your Proof of Concept website.";

// Important: Set correct headers (Prevents email from looking like spam)
$headers = "From: webmaster@yourdomain.com" . "\r\n" . // CHANGE THIS to your domain email
           "Reply-To: " . $userEmail . "\r\n" .
           "X-Mailer: PHP/" . phpversion();

// 6. Attempt to send the email
if (mail($toAdmin, $subject, $message, $headers)) {
    // Email sent successfully
    echo json_encode(['success' => true, 'message' => 'Result email sent to administrator.']);
} else {
    // Email failed (server configuration issue)
    echo json_encode(['success' => false, 'message' => 'The server failed to send the email. Check mail server logs.']);
}

?>
