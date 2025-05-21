<?php
session_start();

if (!isset($_SESSION['email'])) {
    header("Location: login.php"); 
    exit;
}

$email = $_SESSION['email']; 

// Κραταμε το κομμάτι του email πριν το @
$emailParts = explode('@', $email);
$username = $emailParts[0]; // Παίρνουμε το πρώτο κομμάτι του email
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>New Database Connection</title>
    <link rel="stylesheet" href="styles.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600&display=swap" rel="stylesheet">
    <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
</head>
<body>
    <nav class="menu">
        <ul>
            <li><a href="dashboard.php">HOME</a></li>
            <li><a href="new_db_connection.php">New DB connection</a></li>
            <li><a href="my_connections.php">My connections</a></li>
            <li><a href="my_backups.php">My Backup Files</a></li>
            <li><a href="delete_user.php" class="logout-btn">DELETE USER</a></li>
        </ul>
        <ul class="user-info">
            <li class="logged-in">Logged in as: <?php echo htmlspecialchars($username); ?></li>
            <li><a href="logout.php" class="logout-btn">Logout</a></li>
        </ul>
    </nav>

    <div id="db-connection-wrapper" class="wrapper">
        <h2>Add a New Database Connection</h2>
        <form id="addConnectionForm" method="POST" action="save_connection.php">
            <label>Database Type</label>
            <div>
                <input type="radio" id="mysql" name="db_type" value="mysql" checked>
                <label for="mysql">MySQL</label>

                <input type="radio" id="postgres" name="db_type" value="postgres">
                <label for="postgres">PostgreSQL</label>
            </div>

            <div class="input-box">
                <label>Connection Name</label>
                <input type="text" name="connection_name" placeholder="Enter connection name" required>
            </div>
            <div class="input-box">
                <label>Host</label>
                <input type="text" name="host" placeholder="Enter host (e.g., localhost)" required>
            </div>
            <div class="input-box">
                <label>DB Username</label>
                <input type="text" name="db_username" placeholder="Enter DB username" required>
            </div>
            <div class="input-box">
                <label>DB Password</label>
                <input type="password" name="db_password" placeholder="Enter DB password (leave empty if none)">
                </div>
            <div class="input-box">
                <label>Database Name</label>
                <input type="text" name="db_name" placeholder="Enter database name" required>
            </div>

            <h3>SSH Connection (Optional)</h3>
            <div class="input-box">
                <label>SSH Host</label>
                <input type="text" name="ssh_host" placeholder="Enter SSH host">
            </div>
            <div class="input-box">
                <label>SSH Username</label>
                <input type="text" name="ssh_username" placeholder="Enter SSH username">
            </div>
            <div class="input-box">
                <label>SSH Password</label>
                <input type="password" name="ssh_password" placeholder="Enter SSH password">
            </div>
            <div class="input-box">
                <label>SSH Port</label>
                <input type="number" name="ssh_port" placeholder="Enter SSH port (default: 22)" value="22">
            </div>

            <button type="button" id="testConnectionBtn" class="btn">Test Connection</button>
            <button type="submit" id="saveConnectionBtn" class="btn" disabled>Save Connection</button>
            <p><strong>CONNECTION STATUS:</strong> <span id="connectionMessage">Not tested</span></p>
        </form>
    </div>

    <script>
    $('#testConnectionBtn').on('click', function() {
        const host = $("input[name='host']").val().trim();
        const dbUsername = $("input[name='db_username']").val().trim();
        const dbPassword = $("input[name='db_password']").val().trim();
        const dbName = $("input[name='db_name']").val().trim();
        const dbType = $("input[name='db_type']:checked").val();
        
        // ssh δεδομενα
        const sshHost = $("input[name='ssh_host']").val().trim();
        const sshUsername = $("input[name='ssh_username']").val().trim();
        const sshPassword = $("input[name='ssh_password']").val().trim();
        const sshPort = $("input[name='ssh_port']").val().trim();

        // Ελεγχος κενών κελιών
        if (!host || !dbUsername || !dbName) {
            $('#connectionMessage').text("Please fill in all required fields before testing.").css("color", "red");
            $('#saveConnectionBtn').prop("disabled", true).css("background-color", "#b0b0b0");
            return;
        }

        $('#connectionMessage').text("Testing connection...").css("color", "blue");
        $('#saveConnectionBtn').prop("disabled", true).css("background-color", "#b0b0b0");

        const formData = {
            db_type: dbType,
            host: host,
            db_username: dbUsername,
            db_password: dbPassword,
            db_name: dbName,
            ssh_host: sshHost,
            ssh_username: sshUsername,
            ssh_password: sshPassword,
            ssh_port: sshPort
        };

        $.ajax({
    type: 'POST',
    url: 'test_connection.php',
    data: formData,
    success: function(response) {
        if (typeof response === "string") {
            response = JSON.parse(response);
        }

        $('#connectionMessage').text(response.message).css("color", response.success ? "green" : "red");

        if (response.success) {
            $('#saveConnectionBtn').prop("disabled", false).css("background-color", ""); // Επαναφορά αρχικού χρώματος κουμπιού
        } else {
            $('#saveConnectionBtn').prop("disabled", true).css("background-color", "#b0b0b0");
        }
    },
    error: function(xhr) {
        console.log("Error: ", xhr.responseText);  
        $('#connectionMessage').text("Error: " + xhr.responseText).css("color", "red");
        $('#saveConnectionBtn').prop("disabled", true).css("background-color", "#b0b0b0");
    }
});

    });

    const email = '<?php echo $email; ?>';

    $('#addConnectionForm').on('submit', function(e) {
        e.preventDefault();

        const formData = {
            email: email,
            connection_name: $("input[name='connection_name']").val(),
            host: $("input[name='host']").val(),
            db_username: $("input[name='db_username']").val(),
            db_password: $("input[name='db_password']").val(),
            db_name: $("input[name='db_name']").val(),
            db_type: $("input[name='db_type']:checked").val(),
            ssh_host: $("input[name='ssh_host']").val() || null,
            ssh_username: $("input[name='ssh_username']").val() || null,
            ssh_password: $("input[name='ssh_password']").val() || null,
            ssh_port: $("input[name='ssh_port']").val() || null
        };

        $.ajax({
            type: 'POST',
            url: 'save_connection.php',
            data: formData,
            success: function(response) {
                if (typeof response === "string") {
                    response = JSON.parse(response);
                }

                if (response.success) {
                    alert(response.message);
                    window.location.href = 'my_connections.php';
                } else {
                    alert('Error: ' + response.message);
                }
            },
            error: function(xhr) {
                alert('Error saving connection: ' + xhr.responseText);
            }
        });
    });
</script>

</body>
</html>
