<?php
require_once 'services/mailer.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $to = filter_var($_POST['email'], FILTER_SANITIZE_EMAIL);
    $msg = htmlspecialchars($_POST['message']);

    if (sendEmail($to, 'Exam Notification', "Message: " . nl2br($msg))) {
        header("Location: index.php?status=success");
    } else {
        header("Location: index.php?status=error");
    }
    exit;
}
header("Location: index.php");
exit;
