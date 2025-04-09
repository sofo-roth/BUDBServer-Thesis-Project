<?php

include 'db_connection.php';

session_start(); 

if (!isset($_SESSION['email'])) {
    header("Location: login.php"); 
    exit;
}

$email = $_SESSION['email']; 

$emailParts = explode('@', $email);
$username = $emailParts[0]; 

try {
    
    $stmt = $pdo->prepare("SELECT connection_id, connection_name, host, db_username, db_name FROM user_db_connections WHERE email = ?");
    $stmt->execute([$email]);
    $connections = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    echo "Error fetching connections: " . $e->getMessage();
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Your Saved Connections</title>
    <link rel="stylesheet" href="table_styles.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600&display=swap" rel="stylesheet">
    <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
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

    <div class="wrapper">
        <h2>Saved Database Connections</h2>

        
        <div class="bulk-backup-container" style="margin-bottom: 15px;">
            <button id="bulk-backup-btn" style="padding: 10px 20px; background-color: #28a745; color: white; border: none; cursor: pointer; border-radius: 5px;">BACKUP ALL CONNECTIONS (Full Backup)</button>
        </div>

        <div class="table-wrapper">
            <table>
                <thead>
                    <tr>
                        <th>Connection Name</th>
                        <th>Host</th>
                        <th>Username</th>
                        <th>Database Name</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (count($connections) > 0): ?>
                        <?php foreach ($connections as $connection): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($connection['connection_name']); ?></td>
                                <td><?php echo htmlspecialchars($connection['host']); ?></td>
                                <td><?php echo htmlspecialchars($connection['db_username']); ?></td>
                                <td><?php echo htmlspecialchars($connection['db_name']); ?></td>
                                <td>
                                    <div class="button-container">
                                        <form class="backup-form" action="backup.php" method="POST">
                                            <input type="hidden" name="connection_id" value="<?php echo htmlspecialchars($connection['connection_id']); ?>">
                                            <select name="backup_type" required>
                                                <option value="" disabled selected>Select Backup Type</option>
                                                <option value="full">Full Backup</option>
                                                <option value="incremental">Incremental Backup</option>
                                                <option value="differential">Differential Backup</option>
                                                <option value="snapshot">Snapshot Backup</option>
                                            </select>
                                            <button type="submit">Backup</button>
                                        </form>
                                        <button type="button" class="delete-btn" data-connection-id="<?php echo $connection['connection_id']; ?>">Delete Connection</button>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="5" style="text-align:center;">No connections found.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Δυναμικο πεδιο εισαγωγης κωδικου -->
    <div id="password-modal" style="display:none;">
        <div class="wrapper">
            <div class="modal-content">
                <h3>Please enter your password to confirm deletion</h3>
                <form id="password-form" method="POST">
                    <div class="input-box">
                        <input type="password" name="password" placeholder="Enter your password" required>
                    </div>    
                    <button type="submit">Confirm</button>
                    <button type="button" onclick="closePasswordModal()">Cancel</button>
                </form>
                <p id="password-error" style="color:red; display:none;">Incorrect password. Try again.</p>
            </div>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script>

$(document).ready(function() {
    function fetchConnections() {
        $.ajax({
            type: "GET",
            url: "get_connections.php",
            dataType: "json",
            success: function(response) {
                if (response.success) {
                    var tableBody = $("#connections-table");
                    tableBody.empty(); 

                    if (response.connections.length > 0) {
                        response.connections.forEach(function(connection) {
                            var row = `
                                <tr>
                                    <td>${connection.connection_name}</td>
                                    <td>${connection.host}</td>
                                    <td>${connection.db_username}</td>
                                    <td>${connection.db_name}</td>
                                    <td>
                                        <div class="button-container">
                                            <form class="backup-form" action="backup.php" method="POST">
                                                <input type="hidden" name="connection_id" value="${connection.connection_id}">
                                                <select name="backup_type" required>
                                                    <option value="" disabled selected>Select Backup Type</option>
                                                    <option value="full">Full Backup</option>
                                                    <option value="incremental">Incremental Backup</option>
                                                    <option value="differential">Differential Backup</option>
                                                    <option value="snapshot">Snapshot Backup</option>
                                                </select>
                                                <button type="submit">Backup</button>
                                            </form>
                                            <button type="button" class="delete-btn" data-connection-id="${connection.connection_id}">Delete Connection</button>
                                        </div>
                                    </td>
                                </tr>`;
                            tableBody.append(row);
                        });
                    } else {
                        tableBody.append('<tr><td colspan="5" style="text-align:center;">No connections found.</td></tr>');
                    }
                } else {
                    $("#connections-table").html(`<tr><td colspan="5" style="text-align:center;">${response.message}</td></tr>`);
                }
            },
            error: function() {
                $("#connections-table").html('<tr><td colspan="5" style="text-align:center;">Failed to load connections.</td></tr>');
            }
        });
    }

    
    fetchConnections();
});

    $(document).ready(function() {
        
        $("#bulk-backup-btn").on("click", function() {
            Swal.fire({
                title: 'Are you sure?',
                text: "This will trigger a Full Backup for all connections.",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonText: 'Yes, Backup All!',
                cancelButtonText: 'Cancel'
            }).then((result) => {
                if (result.isConfirmed) {
                    $(".backup-form").each(function(index, form) {
                        $(form).find("select[name='backup_type']").val('full'); // Βάζω το dropdown σε 'Full Backup'
                        $(form).trigger("submit"); // εκτελω submit
                    });
                }
            });
        });

        
        $(".backup-form").on("submit", function(event) {
            event.preventDefault();

            var form = $(this);
            var connectionId = form.find("input[name='connection_id']").val();
            var backupType = form.find("select[name='backup_type']").val();

            $.ajax({
                type: "POST",
                url: "backup.php",
                data: { connection_id: connectionId, backup_type: backupType },
                dataType: "json",
                success: function(response) {
                    if (response.success) {
                        Swal.fire('Backup Successful!', response.message, 'success');
                    } else {
                        Swal.fire('Error!', response.message, 'error');
                    }
                },
                error: function() {
                    Swal.fire('Error!', 'An unexpected error occurred.', 'error');
                }
            });
        });

        // Φορμα επιβεβαιωσης διαγραφης
        $(document).on("click", ".delete-btn", function() {
            var connectionId = $(this).data('connection-id');
            $('#password-modal').data('connectionId', connectionId).show();
        });

        $("#password-form").on("submit", function(event) {
            event.preventDefault();
            var password = $("input[name='password']").val();
            var connectionId = $('#password-modal').data('connectionId');

            $.ajax({
                type: "POST",
                url: "delete_connection.php",
                data: { password: password, connection_id: connectionId },
                dataType: "json",
                success: function(response) {
                    if (response.success) {
                        Swal.fire('Deleted!', response.message, 'success').then(() => location.reload());
                    } else {
                        $("#password-error").show();
                    }
                }
            });
        });
    });
    function closePasswordModal() {
    $('#password-modal').hide(); // κρύβω την φορμα εισαγωγης κωδικου
    $('#password-error').hide(); // κρυβω τα μηνυματα λαθους
    $('#password-form')[0].reset(); // Reset πεδιου κωδικου
    }

    </script>
</body>
</html>
