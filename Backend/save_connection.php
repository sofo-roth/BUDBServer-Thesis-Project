<?php

session_start();

include 'db_connection.php'; 

define('ENCRYPTION_KEY', 'your-secure-encryption-key');

// Κρυπτογράφηση κωδικού
function encryptPassword($plainPassword) {
    $encryptedPassword = openssl_encrypt($plainPassword, 'AES-128-ECB', ENCRYPTION_KEY);
    return $encryptedPassword;
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $user_id = $_SESSION['email']; 
    $connection_name = trim($_POST['connection_name']);
    $host = trim($_POST['host']);
    $db_username = trim($_POST['db_username']);
    $db_password = trim($_POST['db_password']);
    $db_name = trim($_POST['db_name']);
    $db_type = $_POST['db_type']; 

    // SSH credentials με null αρχικοποιήσεις
    $ssh_host = !empty(trim($_POST['ssh_host'])) ? trim($_POST['ssh_host']) : null;
    $ssh_username = !empty(trim($_POST['ssh_username'])) ? trim($_POST['ssh_username']) : null;
    $ssh_password = !empty(trim($_POST['ssh_password'])) ? trim($_POST['ssh_password']) : null;
    $ssh_port = !empty(trim($_POST['ssh_port'])) ? trim($_POST['ssh_port']) : null;

    // Έλεγχος στοιχείων
    if (empty($connection_name) || empty($host) || empty($db_username) || empty($db_password) || empty($db_name) || empty($db_type)) {
        echo json_encode(['success' => false, 'message' => 'All fields are required']);
        exit;
    }

    // Κρυπτογράφηση κωδικου βασης
    $encryptedDbPassword = encryptPassword($db_password);

    // Κρυπτογράφηση κωδικού ssh σύνδεσης
    $encryptedSshPassword = encryptPassword($ssh_password);

    // Δημιουργία webtoken
    $webtoken = bin2hex(random_bytes(32));

    try {
        $stmt = $pdo->prepare("INSERT INTO user_db_connections (email, connection_name, host, db_username, db_password, db_name, db_type, ssh_host, ssh_username, ssh_password, ssh_port, webtoken) 
                               VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([
            $user_id, 
            $connection_name, 
            $host, 
            $db_username, 
            $encryptedDbPassword, 
            $db_name, 
            $db_type, 
            $ssh_host, 
            $ssh_username, 
            $encryptedSshPassword, 
            $ssh_port, 
            $webtoken
        ]);

        
        echo json_encode(['success' => true, 'message' => 'Database connection saved successfully', 'webtoken' => $webtoken]);
    } catch (PDOException $e) {
        http_response_code(500); 
        echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
    }
}
?>
