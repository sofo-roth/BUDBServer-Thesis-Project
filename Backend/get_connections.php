<?php
include 'db_connection.php';
session_start();

if (!isset($_SESSION['email'])) {
    echo json_encode(["success" => false, "message" => "Unauthorized access"]);
    exit;
}

$email = $_SESSION['email'];

try {
    $stmt = $pdo->prepare("SELECT connection_id, connection_name, host, db_username, db_name FROM user_db_connections WHERE email = ?");
    $stmt->execute([$email]);
    $connections = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode(["success" => true, "connections" => $connections]);
} catch (PDOException $e) {
    echo json_encode(["success" => false, "message" => "Error fetching connections: " . $e->getMessage()]);
}
?>
