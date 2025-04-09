<?php
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $db_type = $_POST['db_type'];
    $host = $_POST['host'];
    $db_username = $_POST['db_username'];
    $db_password = $_POST['db_password'];
    $db_name = $_POST['db_name'];
    $ssh_host = isset($_POST['ssh_host']) ? $_POST['ssh_host'] : null;
    $ssh_username = isset($_POST['ssh_username']) ? $_POST['ssh_username'] : null;
    $ssh_password = isset($_POST['ssh_password']) ? $_POST['ssh_password'] : null;
    $ssh_port = isset($_POST['ssh_port']) ? $_POST['ssh_port'] : 22;

    try {

        //Αν δίνονται στοιχεία αυθεντικοποίησης SSH τότε τεστάρεται η σύνδεση
        if ($ssh_host && $ssh_username && $ssh_password) {
            $sshCommand = "sshpass -p '" . escapeshellarg($ssh_password) . "' ssh -o StrictHostKeyChecking=no -p " . escapeshellarg($ssh_port) . " -q " . escapeshellarg($ssh_username) . "@" . escapeshellarg($ssh_host) . " exit";
            exec($sshCommand, $sshOutput, $sshResultCode);

            if ($sshResultCode !== 0) {
                echo json_encode(["success" => false, "message" => "SSH connection failed. Result code: $sshResultCode"]);
                exit;
            }
        }

        //Τεστάρισμα σύνδεσης 
        $dbCommand = '';
        if ($db_type === 'mysql') {
            // MySQL: θα εκτελεστεί η mysql για να τεστάρει αν υπάρχει η mysql βάση
            $dbCommand = "mysql -u " . escapeshellarg($db_username) . " -p" . escapeshellarg($db_password) . " -h " . escapeshellarg($host) . " -e 'SELECT 1;' 2>&1";
        } elseif ($db_type === 'postgres') {
            // PostgreSQL:θα εκτελεστεί η psql για να τεστάρει αν υπάρχει η postgres βάση
            $dbCommand = "PGPASSWORD=" . escapeshellarg($db_password) . " psql -U " . escapeshellarg($db_username) . " -h " . escapeshellarg($host) . " -d postgres -c 'SELECT 1;' 2>&1";
        } else {
            echo json_encode(["success" => false, "message" => "Unsupported database type"]);
            exit;
        }

        // Τεστάρισμα σύνδεσης 
        exec($dbCommand, $dbOutput, $dbResultCode);

        // Αν το τεστάρισμα της σύνδεσης αποτύχει
        if ($dbResultCode !== 0) {
            echo json_encode(["success" => false, "message" => "Database connection failed: " . implode("\n", $dbOutput)]);
            exit;
        }

        // Αν το τεστάρισμα της σύνδεσης πετύχει, τότε τεστάρει αν υπάρχει η βάση
        $dbExistsCommand = '';
        if ($db_type === 'mysql') {
            $dbExistsCommand = "mysql -u " . escapeshellarg($db_username) . " -p" . escapeshellarg($db_password) . " -h " . escapeshellarg($host) . " -e 'SHOW DATABASES LIKE \"" . escapeshellarg($db_name) . "\";' 2>&1";
        } elseif ($db_type === 'postgres') {
            $dbExistsCommand = "PGPASSWORD=" . escapeshellarg($db_password) . " psql -U " . escapeshellarg($db_username) . " -h " . escapeshellarg($host) . " -d postgres -c 'SELECT 1 FROM pg_database WHERE datname = \"" . escapeshellarg($db_name) . "\";' 2>&1";
        }

        exec($dbExistsCommand, $dbExistsOutput, $dbExistsResultCode);

        // Τεστάρισμα ύπαρξης βάσης και εμφάνιση αντίστοιχου μηνύματος
        if ($dbExistsResultCode === 0 && !empty($dbExistsOutput)) {
            echo json_encode(["success" => true, "message" => "Connection successful! Database '$db_name' exists."]);
        } else {
            echo json_encode(["success" => false, "message" => "Database '$db_name' not found. Error: " . implode("\n", $dbExistsOutput)]);
        }

    } catch (Exception $e) {
        echo json_encode(["success" => false, "message" => "Error: " . $e->getMessage()]);
    }
}
?>
