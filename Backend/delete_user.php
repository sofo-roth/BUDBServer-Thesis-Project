<?php

include 'db_connection.php';  

session_start(); 

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);


if (!isset($_SESSION['email'])) {
    echo json_encode(['success' => false, 'message' => 'User not logged in.']);
    exit;
}

$email = $_SESSION['email']; 


$emailParts = explode('@', $email);
$username = $emailParts[0]; 


$stmt = $pdo->prepare("SELECT passwd, email FROM users WHERE email = ?");
$stmt->execute([$email]);
$user = $stmt->fetch();


if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Ελεγχος κωδικου και επιλογης απαντησης
    if (isset($_POST['password']) && !isset($_POST['confirm_delete'])) {
        $password = $_POST['password'];

        // Ελεγχος αν ο χρηστης υπαρχει και ο κωδικος ειναι σωστος
        if ($user && password_verify($password, $user['passwd'])) {
            // Με σωστο κωδικο, εμφανιση φορμας επιβεβαιωσης
            echo json_encode(['success' => true, 'message' => 'Password correct. Please confirm deletion.']);
            exit;
        } else {
            // Λαθος κωδικος
            echo json_encode(['success' => false, 'message' => 'Invalid password']);
            exit;
        }
    }
    
    
    if (isset($_POST['confirm_delete']) && $_POST['confirm_delete'] == 'yes' && isset($_POST['password'])) {
        
        $pdo->beginTransaction();

        try {
            // Διαγραφη συνδεσεων του χρηστ
            $deleteConnectionsStmt = $pdo->prepare("DELETE FROM user_db_connections WHERE email = ?");
            $deleteConnectionsStmt->execute([$email]);

            // Διαγραφη στοιχεων χρηστη
            $deleteUserStmt = $pdo->prepare("DELETE FROM users WHERE email = ?");
            $deleteUserStmt->execute([$email]);

            // Διαγραφη backup logs του χρηστη
            $deleteLogsStmt = $pdo->prepare("DELETE FROM backup_logs WHERE email = ?");
            $deleteLogsStmt->execute([$email]);

            // Διαγραφη φακελου αρχειων ασφαλειας του χρηστη
            $sanitizedEmail = str_replace(['@', '.'], '_', $email);
            $userBackupDir = "backups/{$sanitizedEmail}/";
            deleteDirectory($userBackupDir); 

            
            $pdo->commit();

            
            session_destroy();

            
            echo json_encode(['success' => true, 'message' => 'Your Account and Connections Have Been Deleted Successfully']);
            exit; 
        } catch (Exception $e) {
            
            $pdo->rollBack();

            
            echo json_encode(['success' => false, 'message' => 'An error occurred while deleting your account. Please try again later.']);
            exit;
        }
    } elseif (isset($_POST['confirm_delete']) && $_POST['confirm_delete'] == 'no') {
        // Εμφανιση μηνυματος ακυρωσης διαγραφης χρηστη
        echo json_encode(['success' => false, 'message' => 'Account deletion was canceled']);
        exit; 
    }
}


function deleteDirectory($dirPath) {
    if (!is_dir($dirPath)) {
        return;  
    }
    
    $files = array_diff(scandir($dirPath), array('.', '..'));
    
    foreach ($files as $file) {
        $filePath = $dirPath . DIRECTORY_SEPARATOR . $file;
        
        if (is_dir($filePath)) {
            deleteDirectory($filePath);  // διαγραφη ολοκληρου path
        } else {
            unlink($filePath);  // διαγραφη φακελων
        }
    }
    
    rmdir($dirPath);  // διαγραφη κενου directory 
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Delete Account</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="styles.css">
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
            <li class="logged-in">Logged in as: <?php echo htmlspecialchars($username); ?></li>
            <li><a href="logout.php" class="logout-btn">Logout</a></li>
        </ul>
    </nav>

    <div class="wrapper">
        <!-- Φορμα ελεγχου κωδικου -->
        <div class="form-container">
            <h2>Delete Your Account</h2>
            <form method="POST" action="" id="delete-form">
                <p>To proceed with deleting your account, please re-enter your password:</p>
                <div class="input-box">
                    <input type="password" name="password" placeholder="Enter your password" required>
                </div>
                <button type="submit" class="btn">Verify Password</button>
            </form>
        </div>
    </div>

    <!-- Εμφανιση φορμας μολις δωθει ο σωστος κωδικος -->
    <div id="delete-confirm-form" class="form-container" style="display: none;">
        <div class="wrapper">
            <h3>Are you sure you want to delete your account?</h3>
            <form method="POST" action="" id="confirm-delete-form">
                <input type="hidden" name="password" value="<?php echo htmlspecialchars($password); ?>"> <!-- Hidden field to pass password -->
                <input type="radio" name="confirm_delete" value="yes" required> Yes, delete my account<br>
                <input type="radio" name="confirm_delete" value="no" required> No, keep my account<br>
                <button type="submit" class="btn">Confirm</button>
            </form>
        </div>
    </div>

    <script>
            const deleteForm = document.getElementById('delete-form');
        deleteForm.addEventListener('submit', function(event) {
            event.preventDefault(); 

            const formData = new FormData(deleteForm);

            fetch('delete_user.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())  
            .then(data => {
                if (data.success) {
                    if (data.message === 'Password correct. Please confirm deletion.') {
                        
                        alert(data.message);  
                        
                        // εμφανιση φορμας επιβεβαιωσης
                        document.getElementById('delete-confirm-form').style.display = 'block';
                    } else {
                        alert(data.message);  // Εμφανιση μηνυματος επιλογης
                        if (data.success) {
                            
                            window.location.href = 'login.php';  
                        }
                    }
                } else {
                    alert(data.message);  
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('An error occurred. Please try again later.');
            });
        });

        const confirmDeleteForm = document.getElementById('confirm-delete-form');
        confirmDeleteForm.addEventListener('submit', function(event) {
            event.preventDefault(); 

            const formData = new FormData(confirmDeleteForm);

            fetch('delete_user.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())  
            .then(data => {
                if (data.success) {
                    alert(data.message);
                    
                    window.location.href = 'login.php';  
                } else {
                    alert(data.message);  
                    
                    window.location.href = 'dashboard.php';  
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('An error occurred. Please try again later.');
            });
        });
    </script>
</body>
</html>
