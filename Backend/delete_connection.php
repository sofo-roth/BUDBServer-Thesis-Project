<?php

include 'db_connection.php';

session_start(); 


$email = $_SESSION['email']; 


if (!isset($_POST['connection_id'], $_POST['password'])) {
    echo json_encode(['success' => false, 'message' => 'Missing connection ID or password.']);
    exit;
}

$connectionId = $_POST['connection_id'];
$password = $_POST['password'];

try {
    
    $stmt = $pdo->prepare("SELECT passwd FROM users WHERE email = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch();

    // Ελεγχος κωδικου
    if (!$user || !password_verify($password, $user['passwd'])) {
        echo json_encode(['success' => false, 'message' => 'Incorrect password.']);
        exit;
    }

    // Αν ο κωδικος ειναι σωστος, διεγραψε την συνδεση
    $deleteStmt = $pdo->prepare("DELETE FROM user_db_connections WHERE connection_id = ? AND email = ?");
    $deleteStmt->execute([$connectionId, $email]);

    
    if ($deleteStmt->rowCount() > 0) {
        echo json_encode(['success' => true, 'message' => 'Connection deleted successfully.']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Connection not found or already deleted.']);
    }
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'An error occurred: ' . $e->getMessage()]);
}
?>
