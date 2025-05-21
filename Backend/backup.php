<?php

include 'db_connection.php';

session_start(); 

date_default_timezone_set('Europe/Athens'); 

define('ENCRYPTION_KEY', 'your-secure-encryption-key');

function decryptPassword($encryptedPassword) {
    return openssl_decrypt($encryptedPassword, 'AES-128-ECB', ENCRYPTION_KEY);
}

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $connectionId = $_POST['connection_id'];
    $backupType = $_POST['backup_type'];

    try {
        $stmt = $pdo->prepare("SELECT host, db_username, db_password, db_name, connection_name, email, ssh_host, ssh_username, ssh_password, ssh_port, db_type FROM user_db_connections WHERE connection_id = ?");
        $stmt->execute([$connectionId]);
        $connectionDetails = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($connectionDetails) {
            $host = $connectionDetails['host'];
            $username = $connectionDetails['db_username'];
            $encryptedPassword = $connectionDetails['db_password'];
            $database = $connectionDetails['db_name'];
            $connectionName = $connectionDetails['connection_name'];
            $email = $connectionDetails['email'];
            $password = decryptPassword($encryptedPassword);

            $encryptedSshPassword = $connectionDetails['ssh_password'];
            $sshPassword = decryptPassword($encryptedSshPassword);

            $sshHost = $connectionDetails['ssh_host'];
            $sshUsername = $connectionDetails['ssh_username'];
            $sshPort = $connectionDetails['ssh_port'] ?: 22; 
            $dbType = $connectionDetails['db_type']; 

            $userFolder = 'backups/' . preg_replace('/[^a-zA-Z0-9]/', '_', $email);
            if (!is_dir($userFolder)) {
                mkdir($userFolder, 0777, true);
            }

            $connectionFolder = $userFolder . '/' . preg_replace('/[^a-zA-Z0-9]/', '_', $connectionName);
            if (!is_dir($connectionFolder)) {
                mkdir($connectionFolder, 0777, true);
            }

            $timestamp = date("Y-m-d_H-i-s"); 
            $backupFileName = $database . "_" . $backupType . "_backup_" . $timestamp . ".sql";
            $backupFilePath = $connectionFolder . "/" . $backupFileName;

            $command = '';
            if ($dbType === 'mysql') {
                if ($sshHost && $sshUsername && $sshPassword && $backupType === 'full') {
                    $command = sprintf(
                        "sshpass -p '%s' ssh -o StrictHostKeyChecking=no -p 22 -v %s@%s 'mysqldump --no-tablespaces -u %s -p%s -h %s %s' > %s",
                        escapeshellarg($sshPassword), 
                        escapeshellarg($sshUsername), 
                        escapeshellarg($sshHost),     
                        escapeshellarg($username),    
                        escapeshellarg($password),    
                        escapeshellarg($host),        
                        escapeshellarg($database),    
                        escapeshellarg($backupFilePath)
                    );                                                       
                } elseif ($backupType === 'full') {
                    $command = "mysqldump -u " . escapeshellarg($username) . " -p" . escapeshellarg($password) . " " . escapeshellarg($database) . " > " . escapeshellarg($backupFilePath);
                }
            } elseif ($dbType === 'postgres') {
                if ($sshHost && $sshUsername && $sshPassword && $backupType === 'full') {
                    $command = sprintf(
                        "sshpass -p '%s' ssh -o StrictHostKeyChecking=no -p 22 %s@%s 'export PGPASSWORD=%s; pg_dump -U %s -h %s -d %s' > %s",
                        escapeshellarg($sshPassword),
                        escapeshellarg($sshUsername),
                        escapeshellarg($sshHost),
                        escapeshellarg($password),
                        escapeshellarg($username),
                        escapeshellarg($host),
                        escapeshellarg($database),
                        escapeshellarg($backupFilePath)
                    );                                      
                } elseif ($backupType === 'full') {
                    $command = "pg_dump -U " . escapeshellarg($username) . " -h " . escapeshellarg($host) . " " . escapeshellarg($database) . " > " . escapeshellarg($backupFilePath);
                }
            }

            exec($command . " 2>&1", $output, $return_var);
            $errorOutput = implode("\n", $output);

            if ($return_var === 0) {
                $stmt = $pdo->prepare("INSERT INTO backup_logs (file_name, email, backup_type, last_backup) VALUES (:file_name, :email, :backup_type, NOW())");
                $stmt->execute([
                    ':file_name' => $backupFileName,
                    ':email' => $email,
                    ':backup_type' => $backupType
                ]);

                echo json_encode(['success' => true, 'message' => "Backup successful. Backup saved to: " . htmlspecialchars($backupFilePath)]);
            } else {
                echo json_encode(['success' => false, 'message' => "Error executing backup command: " . $errorOutput]);
            }
        } else {
            echo json_encode(['success' => false, 'message' => "Connection details not found for the given ID."]);
        }
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'message' => "Database error: " . $e->getMessage()]);
    }
}

exit;
