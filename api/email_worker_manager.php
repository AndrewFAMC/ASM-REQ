<?php
/**
 * Email Worker Manager API
 *
 * Manages the email queue background worker through web interface
 * Actions: start, stop, status, check
 */

require_once __DIR__ . '/../config.php';

header('Content-Type: application/json');

// Check if user is logged in and has permission (admin or custodian)
if (!isLoggedIn()) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$userRole = $_SESSION['role'] ?? '';
if (!in_array(strtolower($userRole), ['admin', 'super_admin', 'custodian'])) {
    echo json_encode(['success' => false, 'message' => 'Insufficient permissions']);
    exit;
}

$action = $_GET['action'] ?? 'status';

// Path to worker script
$workerScript = realpath(__DIR__ . '/../cron/process_email_queue.php');
$phpPath = 'C:\\xampp\\php\\php.exe';

switch ($action) {
    case 'start':
        // Check if already running
        if (isWorkerRunning()) {
            echo json_encode([
                'success' => false,
                'message' => 'Email worker is already running',
                'status' => 'running'
            ]);
            exit;
        }

        // Start worker in background
        $command = sprintf(
            'start /B "" "%s" "%s" > nul 2>&1',
            $phpPath,
            $workerScript
        );

        pclose(popen($command, 'r'));

        // Wait a moment and check if it started
        sleep(2);

        if (isWorkerRunning()) {
            echo json_encode([
                'success' => true,
                'message' => 'Email worker started successfully',
                'status' => 'running'
            ]);
        } else {
            echo json_encode([
                'success' => false,
                'message' => 'Failed to start email worker',
                'status' => 'stopped'
            ]);
        }
        break;

    case 'stop':
        // Stop worker (Windows taskkill)
        $command = 'taskkill /F /FI "WINDOWTITLE eq process_email_queue.php*" > nul 2>&1';
        exec($command);

        sleep(1);

        if (!isWorkerRunning()) {
            echo json_encode([
                'success' => true,
                'message' => 'Email worker stopped successfully',
                'status' => 'stopped'
            ]);
        } else {
            echo json_encode([
                'success' => false,
                'message' => 'Failed to stop email worker',
                'status' => 'running'
            ]);
        }
        break;

    case 'status':
    case 'check':
        $running = isWorkerRunning();

        // Get queue statistics
        $stats = getQueueStats($pdo);

        echo json_encode([
            'success' => true,
            'status' => $running ? 'running' : 'stopped',
            'worker_running' => $running,
            'queue_stats' => $stats,
            'message' => $running ? 'Email worker is running' : 'Email worker is stopped'
        ]);
        break;

    case 'auto_start':
        // Auto-start worker if not running and there are pending emails
        $stats = getQueueStats($pdo);

        if (!isWorkerRunning() && $stats['pending'] > 0) {
            // Start worker
            $command = sprintf(
                'start /B "" "%s" "%s" > nul 2>&1',
                $phpPath,
                $workerScript
            );
            pclose(popen($command, 'r'));

            sleep(1);

            echo json_encode([
                'success' => true,
                'message' => 'Email worker auto-started',
                'status' => 'running',
                'queue_stats' => $stats,
                'auto_started' => true
            ]);
        } else {
            echo json_encode([
                'success' => true,
                'message' => $stats['pending'] === 0 ? 'No emails to process' : 'Worker already running',
                'status' => isWorkerRunning() ? 'running' : 'stopped',
                'queue_stats' => $stats,
                'auto_started' => false
            ]);
        }
        break;

    default:
        echo json_encode([
            'success' => false,
            'message' => 'Invalid action. Use: start, stop, status, auto_start'
        ]);
}

/**
 * Check if the email worker is currently running
 */
function isWorkerRunning() {
    // Check using tasklist (Windows)
    exec('tasklist /FI "IMAGENAME eq php.exe" /FO CSV', $output);

    foreach ($output as $line) {
        if (stripos($line, 'php.exe') !== false) {
            // Check if it's running process_email_queue.php
            exec('wmic process where "name=\'php.exe\'" get commandline /format:csv', $cmdOutput);
            foreach ($cmdOutput as $cmd) {
                if (stripos($cmd, 'process_email_queue.php') !== false) {
                    return true;
                }
            }
        }
    }

    return false;
}

/**
 * Get queue statistics
 */
function getQueueStats($pdo) {
    try {
        $stmt = $pdo->query("
            SELECT
                status,
                priority,
                COUNT(*) as count
            FROM email_queue
            GROUP BY status, priority
        ");

        $stats = [
            'pending' => 0,
            'sent' => 0,
            'failed' => 0,
            'total' => 0,
            'by_priority' => []
        ];

        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $stats[$row['status']] += $row['count'];
            $stats['total'] += $row['count'];
            $stats['by_priority'][] = $row;
        }

        // Get oldest pending email
        $oldestStmt = $pdo->query("
            SELECT created_at
            FROM email_queue
            WHERE status = 'pending'
            ORDER BY created_at ASC
            LIMIT 1
        ");
        $oldest = $oldestStmt->fetch(PDO::FETCH_ASSOC);

        if ($oldest) {
            $stats['oldest_pending'] = $oldest['created_at'];
        }

        return $stats;
    } catch (PDOException $e) {
        return [
            'pending' => 0,
            'sent' => 0,
            'failed' => 0,
            'total' => 0,
            'error' => $e->getMessage()
        ];
    }
}
?>
