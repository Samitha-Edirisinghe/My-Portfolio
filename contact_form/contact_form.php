<?php
// ======= CONFIGURATION ======= //
$recipientEmail = "randikanilupul123@gmail.com";
$emailSubjectPrefix = "My Website Contact:";
$minMessageLength = 10;
$maxMessageLength = 1000;
// ============================= //

header('Content-Type: application/json');

// Only process POST requests
if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    http_response_code(403);
    echo json_encode([
        'type' => 'error',
        'message' => 'Access forbidden: Invalid request method'
    ]);
    exit;
}

// Get and sanitize form data
$name = filter_input(INPUT_POST, 'name', FILTER_SANITIZE_FULL_SPECIAL_CHARS);
$email = filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL);
$subject = filter_input(INPUT_POST, 'subject', FILTER_SANITIZE_FULL_SPECIAL_CHARS);
$message = filter_input(INPUT_POST, 'message', FILTER_SANITIZE_FULL_SPECIAL_CHARS);

// Error container
$errors = [];

// Validate inputs
if (empty($name)) {
    $errors['name'] = 'Name is required';
} elseif (strlen($name) < 2) {
    $errors['name'] = 'Name must be at least 2 characters';
}

if (empty($email)) {
    $errors['email'] = 'Email is required';
} elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $errors['email'] = 'Invalid email format';
}

if (empty($subject)) {
    $errors['subject'] = 'Subject is required';
} elseif (strlen($subject) < 5) {
    $errors['subject'] = 'Subject must be at least 5 characters';
}

if (empty($message)) {
    $errors['message'] = 'Message is required';
} elseif (strlen($message) < $minMessageLength) {
    $errors['message'] = "Message must be at least $minMessageLength characters";
} elseif (strlen($message) > $maxMessageLength) {
    $errors['message'] = "Message exceeds maximum length of $maxMessageLength characters";
}

// Return errors if any
if (!empty($errors)) {
    http_response_code(400);
    echo json_encode([
        'type' => 'error',
        'message' => 'Please correct the highlighted errors',
        'errors' => $errors
    ]);
    exit;
}

// Build email content
$emailSubject = $emailSubjectPrefix . " " . $subject;
$emailBody = "New contact form submission:\n\n";
$emailBody .= "Name: $name\n";
$emailBody .= "Email: $email\n";
$emailBody .= "Subject: $subject\n\n";
$emailBody .= "Message:\n" . wordwrap($message, 70) . "\n";

// Set secure headers
$headers = "From: $name <$email>" . "\r\n";
$headers .= "Reply-To: $email" . "\r\n";
$headers .= "X-Mailer: PHP/" . phpversion();
$headers .= "MIME-Version: 1.0\r\n";
$headers .= "Content-Type: text/plain; charset=utf-8\r\n";

// Attempt to send email
try {
    $mailSent = mail($recipientEmail, $emailSubject, $emailBody, $headers);
    
    if (!$mailSent) {
        throw new Exception('Mail function failed');
    }

    http_response_code(200);
    echo json_encode([
        'type' => 'success',
        'message' => 'Thank you! Your message has been sent successfully'
    ]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'type' => 'error',
        'message' => 'Message could not be sent. Please try again later',
        'debug' => $e->getMessage()
    ]);
}
?>