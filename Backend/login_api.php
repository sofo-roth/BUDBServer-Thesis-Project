<?php

include 'db_connection.php';

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

session_start(); 

header('Content-Type: application/json'); 

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    
    $input = json_decode(file_get_contents('php://input'), true);

    
    if ($input === null) {
        echo json_encode(['success' => false, 'message' => 'Invalid input format']);
        exit;
    }

    $username = trim($input['username'] ?? ''); 
    $password = trim($input['password'] ?? '');

    // Ελεγχος στοιχειων εισοδου
    if (empty($username) || empty($password)) {
        echo json_encode(['success' => false, 'message' => 'Username and password are required!']);
        exit;
    }

    
    try {
        $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ?");
        $stmt->execute([$username]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        // Ελεγχος κωδικου
        if ($user && password_verify($password, $user['passwd'])) {
            // αποθηκευση στοιχειων χρηστη στο session
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];

            
            echo json_encode(['success' => true, 'message' => 'Login successful', 'user' => ['id' => $user['id'], 'username' => $user['username']]]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Invalid Username or Password']);
        }
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
    }
}
?>
