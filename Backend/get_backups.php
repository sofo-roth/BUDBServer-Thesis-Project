<?php
include 'db_connection.php';
session_start();

if (!isset($_SESSION['email'])) {
    echo json_encode(["success" => false, "message" => "Unauthorized access"]);
    exit;
}

$email = $_SESSION['email'];
$sanitizedEmail = str_replace(['@', '.'], '_', $email);
$userBackupDir = "backups/{$sanitizedEmail}/";

function getBackupsByConnection($baseDir) {
    $backups = [];
    if (is_dir($baseDir)) {
        $connections = array_filter(scandir($baseDir), function ($item) use ($baseDir) {
            return $item !== '.' && $item !== '..' && is_dir($baseDir . $item);
        });
        foreach ($connections as $connection) {
            $connectionDir = $baseDir . $connection . '/';
            $files = array_filter(scandir($connectionDir), function ($file) use ($connectionDir) {
                return is_file($connectionDir . $file);
            });
            $backups[$connection] = array_values($files);
        }
    }
    return $backups;
}

$backups = getBackupsByConnection($userBackupDir);
echo json_encode($backups);
?>
