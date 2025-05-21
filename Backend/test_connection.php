<?php
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $db_type = $_POST['db_type'];
    $host = $_POST['host'];
    $db_username = $_POST['db_username'];
    $db_password = $_POST['db_password'];
    $db_name = $_POST['db_name'];
    $ssh_host = $_POST['ssh_host'] ?? null;
    $ssh_username = $_POST['ssh_username'] ?? null;
    $ssh_password = $_POST['ssh_password'] ?? null;
    $ssh_port = $_POST['ssh_port'] ?? 22;

    // Pick a local port for tunnel (must be unused)
    $localPort = 3307;
    $sshTunnelStarted = false;

    try {
        // 1. SSH Tunnel Setup (if SSH credentials are provided)
        if ($ssh_host && $ssh_username && $ssh_password) {
            // Start SSH tunnel in background
            $sshCommand = "sshpass -p " . escapeshellarg($ssh_password) .
                " ssh -f -N -L {$localPort}:" . escapeshellarg($host) . ":3306 " .
                escapeshellarg($ssh_username) . "@" . escapeshellarg($ssh_host) .
                " -p " . escapeshellarg($ssh_port) . " -o StrictHostKeyChecking=no";

            exec($sshCommand, $sshOutput, $sshResultCode);

            if ($sshResultCode !== 0) {
                echo json_encode(["success" => false, "message" => "SSH tunnel failed. Code: $sshResultCode"]);
                exit;
            }

            $sshTunnelStarted = true;
            // Wait briefly to ensure tunnel is ready
            usleep(500000); // 0.5 seconds
        }

        // 2. Database Connection
        if ($db_type === 'mysql') {
            $mysqlHost = $sshTunnelStarted ? '127.0.0.1' : $host;
            $mysqlPort = $sshTunnelStarted ? "-P $localPort" : "";

            $dbCommand = "mysql -u " . escapeshellarg($db_username) .
                " -p" . escapeshellarg($db_password) .
                " -h " . escapeshellarg($mysqlHost) . " $mysqlPort -e 'SELECT 1;' 2>&1";

            exec($dbCommand, $dbOutput, $dbResultCode);

            if ($dbResultCode !== 0) {
                throw new Exception("Database connection failed:\n" . implode("\n", $dbOutput));
            }

            // 3. Check if DB exists
            $dbExistsCommand = "mysql -u " . escapeshellarg($db_username) .
                " -p" . escapeshellarg($db_password) .
                " -h " . escapeshellarg($mysqlHost) . " $mysqlPort -e " .
                escapeshellarg("SHOW DATABASES LIKE '$db_name';") . " 2>&1";

            exec($dbExistsCommand, $dbExistsOutput, $dbExistsResultCode);

            if ($dbExistsResultCode === 0 && count($dbExistsOutput) > 1) {
                echo json_encode(["success" => true, "message" => "Connection successful! Database '$db_name' exists."]);
            } else {
                echo json_encode(["success" => false, "message" => "Database '$db_name' not found. Output: " . implode("\n", $dbExistsOutput)]);
            }

        } elseif ($db_type === 'postgres') {
            $postgresHost = $sshTunnelStarted ? '127.0.0.1' : $host;
            $postgresPort = $sshTunnelStarted ? "-p $localPort" : "";

            $dbCommand = "PGPASSWORD=" . escapeshellarg($db_password) .
                " psql -U " . escapeshellarg($db_username) .
                " -h " . escapeshellarg($postgresHost) . " $postgresPort -d postgres -c 'SELECT 1;' 2>&1";

            exec($dbCommand, $dbOutput, $dbResultCode);

            if ($dbResultCode !== 0) {
                throw new Exception("Database connection failed:\n" . implode("\n", $dbOutput));
            }

            // Check if DB exists
            $dbExistsCommand = "PGPASSWORD=" . escapeshellarg($db_password) .
                " psql -U " . escapeshellarg($db_username) .
                " -h " . escapeshellarg($postgresHost) . " $postgresPort -d postgres -t -c " .
                escapeshellarg("SELECT 1 FROM pg_database WHERE datname = '$db_name';") . " 2>&1";

            exec($dbExistsCommand, $dbExistsOutput, $dbExistsResultCode);

            $dbExists = false;
            foreach ($dbExistsOutput as $line) {
                if (trim($line) === '1') {
                    $dbExists = true;
                    break;
                }
            }

            if ($dbExists) {
                echo json_encode(["success" => true, "message" => "Connection successful! Database '$db_name' exists."]);
            } else {
                echo json_encode(["success" => false, "message" => "Database '$db_name' not found. Output: " . implode("\n", $dbExistsOutput)]);
            }
        }

    } catch (Exception $e) {
        echo json_encode(["success" => false, "message" => $e->getMessage()]);
    } finally {
        // 4. Clean up SSH tunnel
        if ($sshTunnelStarted) {
            exec("ps aux | grep '{$localPort}:" . escapeshellarg($host) . ":3306' | grep -v grep", $sshProcesses);
            foreach ($sshProcesses as $proc) {
                if (preg_match('/^\S+\s+(\d+)/', $proc, $matches)) {
                    exec("kill " . intval($matches[1]));
                }
            }
        }
    }
}
?>
