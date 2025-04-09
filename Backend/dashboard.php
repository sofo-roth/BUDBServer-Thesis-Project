z<?php
session_start(); 

if (!isset($_SESSION['email'])) {
    header("Location: login.php"); 
    exit;
}


if (isset($_SESSION['success_message'])) {
    unset($_SESSION['success_message']); 
}


$email = $_SESSION['email']; 


$emailParts = explode('@', $email);
$username = $emailParts[0]; 
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Home Page</title>
    <link rel="stylesheet" href="styles.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600&display=swap" rel="stylesheet">
    <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
</head>
<body>
    <nav class="menu">
        <ul>
        <li><a href="dashboard.php">HOME</a></li>
        <li><a href="new_db_connection.php">New DB connection</a></li>
        <li><a href="my_connections.php">My connections</a></li>
        <li><a href="my_backups.php">My Backup Files</a></li>
        <li><a href="delete_user.php" class="logout-btn">DELETE USER</a></li> <!-- Added Delete User -->
        </ul>
        <ul class="user-info">
            <li class="logged-in">Logged in as:   <?php echo htmlspecialchars($username); ?></li>
            <li><a href="logout.php" class="logout-btn">Logout</a></li>
        </ul>
    </nav>

    <h2 class="title_home">BUDBs Project</h2>
    <div class="wrapper_home">
        <h1>Welcome to my official BUDB Thesis project</h1>
    </div>
</body>
</html>
