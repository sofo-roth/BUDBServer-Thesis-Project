<?php

include 'db_connection.php';
session_start();

if (!isset($_SESSION['email'])) {
    echo json_encode(['success' => false, 'message' => 'User not logged in.']);
    exit;
}


if (isset($_GET['file'])) {
    $file = $_GET['file'];
    $userFolder = 'backups/' . preg_replace('/[^a-zA-Z0-9]/', '_', $_SESSION['email']);
    $filePath = $userFolder . '/' . $file;

    // Ελεγχος υπαρξης φακελου
    if (file_exists($filePath)) {
        // εκτελεση ληψης
        header('Content-Description: File Transfer');
        header('Content-Type: application/octet-stream');
        header('Content-Disposition: attachment; filename="' . basename($filePath) . '"');
        header('Content-Length: ' . filesize($filePath));
        readfile($filePath);
        exit;
    } else {
        echo json_encode(['success' => false, 'message' => 'File not found.']);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'No file specified for download.']);
}
?>