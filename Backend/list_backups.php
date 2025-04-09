<?php

include 'db_connection.php';
session_start();

if (!isset($_SESSION['email'])) {
    echo json_encode(['success' => false, 'message' => 'User not logged in.']);
    exit;
}

// Path στο directory των αντιγραφων ασφαλειας
$userFolder = 'backups/' . preg_replace('/[^a-zA-Z0-9]/', '_', $_SESSION['email']);

if (!is_dir($userFolder)) {
    echo json_encode(['success' => false, 'message' => 'No backups found for this user.']);
    exit;
}


$backupFiles = array_diff(scandir($userFolder), array('.', '..'));

if ($backupFiles) {
    echo json_encode(['success' => true, 'backupFiles' => array_values($backupFiles)]);
} else {
    echo json_encode(['success' => false, 'message' => 'No backup files found.']);
}
?>