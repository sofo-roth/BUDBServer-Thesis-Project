<?php
session_start(); // Εκκινηση session

// μηδενισμος μεταβλητων session
$_SESSION = [];

// ληξη συνεδριας
session_destroy();

// ανακατευθυνση στο login με μηνυμα
header("Location: login.php?message=logout_successful");
exit;
?>
