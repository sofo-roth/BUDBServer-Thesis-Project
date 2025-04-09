<?php

include 'db_connection.php';

session_start(); 


if (!isset($_SESSION['email'])) {
    header("Location: login.php"); 
    exit;
}

$email = $_SESSION['email']; 


$sanitizedEmail = str_replace(['@', '.'], '_', $email);


$userBackupDir = "backups/{$sanitizedEmail}/";


$data = json_decode(file_get_contents('php://input'), true);
$fileName = $data['file'] ?? '';

if (empty($fileName)) {
    echo json_encode(['success' => false, 'message' => 'No file specified']);
    exit;
}

// Full path του αρχειου
$filePath = $userBackupDir . $fileName;


if (unlink($filePath)) {
    try {
        
        $pdo->beginTransaction();
        error_log("File {$fileName} deleted successfully. Proceeding to delete from backup_logs...");

        
        $stmt = $pdo->prepare("DELETE FROM backup_logs WHERE file_name = :file_name AND email = :email");
        $stmt->bindParam(':file_name', $fileName, PDO::PARAM_STR);
        $stmt->bindParam(':email', $email, PDO::PARAM_STR);

    
        $executionResult = $stmt->execute();

        
        if ($executionResult) {
            error_log("Database entry for file {$fileName} deleted successfully.");
        } else {
            error_log("Failed to delete database entry for file {$fileName}. Query not executed.");
        }

        
        if ($stmt->rowCount() > 0) {
            error_log("Successfully deleted a row from backup_logs for file: {$fileName}");
        } else {
            error_log("No row found to delete in backup_logs for file: {$fileName}");
        }

        // Ελεγχος αν ο φακελος ειναι κενος
        $folderPath = dirname($filePath); // path φακελου
        if (is_dir_empty($folderPath)) {
            rmdir($folderPath); // Διαγραφη φακελου
            error_log("Deleted empty folder: " . $folderPath);
        }

        
        $pdo->commit();
        echo json_encode(['success' => true, 'message' => 'File and log entry deleted successfully']);
    } catch (PDOException $e) {
        
        $pdo->rollBack();
        error_log("Database error: " . $e->getMessage());
        echo json_encode(['success' => false, 'message' => 'File deleted, but a database error occurred']);
    }
} else {
    // Εμφανιση μηνυματος αποτυχιας διαγραφης
    error_log("Failed to delete the file at path: " . $filePath);
    echo json_encode(['success' => false, 'message' => 'Failed to delete the file']);
}

// Ελεγχος κενου directory
function is_dir_empty($dir) {
    if (!is_readable($dir)) return false;
    return count(scandir($dir)) === 2; 
}
?>
