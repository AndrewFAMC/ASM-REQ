<!-- Email Worker Auto-Start Script -->
<script>
/**
 * Automatically starts the email worker if there are pending emails
 * This runs once when the page loads
 */
(function() {
    // Only run for admin and custodian roles
    const userRole = '<?php echo strtolower($_SESSION['role'] ?? ''); ?>';
    if (!['admin', 'super_admin', 'custodian'].includes(userRole)) {
        return;
    }

    // Check and auto-start worker on page load
    fetch('/AMS-REQ/api/email_worker_manager.php?action=auto_start', {
        method: 'GET',
        headers: {
            'Accept': 'application/json'
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success && data.auto_started) {
            console.log('✓ Email worker auto-started successfully');
            console.log('Pending emails:', data.queue_stats.pending);
        } else if (data.success) {
            console.log('✓ Email worker status:', data.status);
            if (data.queue_stats) {
                console.log('Queue stats:', data.queue_stats);
            }
        }
    })
    .catch(error => {
        console.error('Email worker check failed:', error);
    });

    // Optional: Check every 5 minutes and restart if needed
    setInterval(() => {
        fetch('/AMS-REQ/api/email_worker_manager.php?action=status', {
            method: 'GET',
            headers: {
                'Accept': 'application/json'
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success && !data.worker_running && data.queue_stats.pending > 0) {
                console.warn('⚠ Email worker stopped but emails are pending. Restarting...');
                return fetch('/AMS-REQ/api/email_worker_manager.php?action=start');
            }
        })
        .catch(error => {
            console.error('Email worker status check failed:', error);
        });
    }, 5 * 60 * 1000); // Every 5 minutes
})();
</script>
