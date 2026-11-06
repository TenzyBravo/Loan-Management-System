<?php
include 'db_connect.php';
require_once 'includes/security.php';

// Require admin access
Security::requireAdmin();

// Pagination settings
$limit = 50;
$p = isset($_GET['p']) ? max(1, intval($_GET['p'])) : 1;
$offset = ($p - 1) * $limit;

// Filter settings
$filter_event = $_GET['event'] ?? '';
$filter_user = $_GET['user'] ?? '';
$filter_ip = $_GET['ip'] ?? '';
$filter_date_from = $_GET['date_from'] ?? '';
$filter_date_to = $_GET['date_to'] ?? '';

// Build query
$where_conditions = [];
$params = [];
$param_types = '';

if (!empty($filter_event)) {
    $where_conditions[] = "action LIKE ?";
    $params[] = "%$filter_event%";
    $param_types .= 's';
}

if (!empty($filter_user)) {
    $where_conditions[] = "user_id = ?";
    $params[] = $filter_user;
    $param_types .= 'i';
}

if (!empty($filter_ip)) {
    $where_conditions[] = "ip_address LIKE ?";
    $params[] = "%$filter_ip%";
    $param_types .= 's';
}

if (!empty($filter_date_from)) {
    $where_conditions[] = "DATE(created_at) >= ?";
    $params[] = $filter_date_from;
    $param_types .= 's';
}

if (!empty($filter_date_to)) {
    $where_conditions[] = "DATE(created_at) <= ?";
    $params[] = $filter_date_to;
    $param_types .= 's';
}

$where_clause = !empty($where_conditions) ? 'WHERE ' . implode(' AND ', $where_conditions) : '';

// Check if audit_log table exists
$tableCheck = @$conn->query("SHOW TABLES LIKE 'audit_log'");
$audit_log_exists = ($tableCheck && $tableCheck->num_rows > 0);

// Get total count
if ($audit_log_exists) {
    $count_query = "SELECT COUNT(*) as total FROM audit_log $where_clause";
    if (!empty($params)) {
        $stmt = $conn->prepare($count_query);
        $stmt->bind_param($param_types, ...$params);
        $stmt->execute();
        $total_records = $stmt->get_result()->fetch_assoc()['total'];
        $stmt->close();
    } else {
        $total_records = $conn->query($count_query)->fetch_assoc()['total'];
    }

    $total_pages = ceil($total_records / $limit);

    // Get audit logs
    $query = "SELECT a.*, u.name as user_name, u.username
              FROM audit_log a
              LEFT JOIN users u ON a.user_id = u.id
              $where_clause
              ORDER BY a.created_at DESC
              LIMIT $limit OFFSET $offset";

    if (!empty($params)) {
        $stmt = $conn->prepare($query);
        $stmt->bind_param($param_types, ...$params);
        $stmt->execute();
        $logs = $stmt->get_result();
        $stmt->close();
    } else {
        $logs = $conn->query($query);
    }
} else {
    $total_records = 0;
    $total_pages = 0;
    $logs = null;
}

// Get event types for filter
$event_types = [];
if ($audit_log_exists) {
    $event_query = $conn->query("SELECT DISTINCT action FROM audit_log ORDER BY action");
    while ($row = $event_query->fetch_assoc()) {
        $event_types[] = $row['action'];
    }
}
?>

<style>
    .audit-card {
        border-left: 4px solid #007bff;
        margin-bottom: 15px;
        transition: all 0.3s;
    }
    .audit-card:hover {
        box-shadow: 0 4px 12px rgba(0,0,0,0.15);
        transform: translateX(3px);
    }
    .event-badge {
        font-size: 0.85rem;
        padding: 5px 10px;
        border-radius: 12px;
    }
    .event-login_success { background: #d4edda; color: #155724; }
    .event-login_failed { background: #f8d7da; color: #721c24; }
    .event-login_rate_limited { background: #fff3cd; color: #856404; }
    .event-password_changed { background: #d1ecf1; color: #0c5460; }
    .event-password_upgraded { background: #d1ecf1; color: #0c5460; }
    .event-logout { background: #e2e3e5; color: #383d41; }
    .event-user_saved { background: #cce5ff; color: #004085; }
    .event-user_deleted { background: #f5c6cb; color: #721c24; }
    .event-payment_saved { background: #d4edda; color: #155724; }

    .filter-card {
        background: #f8f9fa;
        border-radius: 10px;
        padding: 20px;
        margin-bottom: 20px;
    }
    .stat-card {
        border-radius: 10px;
        padding: 20px;
        text-align: center;
        margin-bottom: 15px;
    }
    .stat-card h3 {
        font-size: 2.5rem;
        font-weight: bold;
        margin-bottom: 5px;
    }
    .stat-card p {
        margin: 0;
        color: #666;
    }
    .timeline-dot {
        width: 12px;
        height: 12px;
        border-radius: 50%;
        display: inline-block;
        margin-right: 8px;
    }
    .dot-success { background: #28a745; }
    .dot-danger { background: #dc3545; }
    .dot-warning { background: #ffc107; }
    .dot-info { background: #17a2b8; }
    .dot-secondary { background: #6c757d; }

    .details-json {
        background: #f8f9fa;
        border-left: 3px solid #007bff;
        padding: 10px;
        border-radius: 5px;
        font-family: 'Courier New', monospace;
        font-size: 0.85rem;
        max-height: 200px;
        overflow-y: auto;
    }
</style>

<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <h2 class="mb-4"><i class="fas fa-shield-alt"></i> Security Audit Log</h2>

            <?php if (!$audit_log_exists): ?>
                <div class="alert alert-warning">
                    <i class="fas fa-exclamation-triangle"></i>
                    <strong>Audit Log Table Not Found</strong><br>
                    The audit_log table doesn't exist in your database. Security events are being logged to files only.
                    <br><br>
                    To enable database logging, run the SQL file: <code>database/create_audit_log.sql</code>
                </div>
            <?php else: ?>

            <!-- Statistics Cards -->
            <div class="row mb-4">
                <div class="col-md-3">
                    <div class="stat-card" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white;">
                        <h3><?php echo number_format($total_records); ?></h3>
                        <p>Total Events</p>
                    </div>
                </div>
                <div class="col-md-3">
                    <?php
                    $today_count = $conn->query("SELECT COUNT(*) as cnt FROM audit_log WHERE DATE(created_at) = CURDATE()")->fetch_assoc()['cnt'];
                    ?>
                    <div class="stat-card" style="background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%); color: white;">
                        <h3><?php echo number_format($today_count); ?></h3>
                        <p>Today's Events</p>
                    </div>
                </div>
                <div class="col-md-3">
                    <?php
                    $failed_logins = $conn->query("SELECT COUNT(*) as cnt FROM audit_log WHERE action = 'login_failed' AND DATE(created_at) >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)")->fetch_assoc()['cnt'];
                    ?>
                    <div class="stat-card" style="background: linear-gradient(135deg, #fa709a 0%, #fee140 100%); color: white;">
                        <h3><?php echo number_format($failed_logins); ?></h3>
                        <p>Failed Logins (7d)</p>
                    </div>
                </div>
                <div class="col-md-3">
                    <?php
                    $unique_ips = $conn->query("SELECT COUNT(DISTINCT ip_address) as cnt FROM audit_log WHERE DATE(created_at) >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)")->fetch_assoc()['cnt'];
                    ?>
                    <div class="stat-card" style="background: linear-gradient(135deg, #30cfd0 0%, #330867 100%); color: white;">
                        <h3><?php echo number_format($unique_ips); ?></h3>
                        <p>Unique IPs (7d)</p>
                    </div>
                </div>
            </div>

            <!-- Filters -->
            <div class="filter-card">
                <h5 class="mb-3"><i class="fas fa-filter"></i> Filters</h5>
                <form method="GET" action="admin.php">
                    <input type="hidden" name="page" value="security_audit_log">
                    <div class="row">
                        <div class="col-md-3">
                            <div class="form-group">
                                <label>Event Type</label>
                                <select class="form-control" name="event">
                                    <option value="">All Events</option>
                                    <?php foreach ($event_types as $event): ?>
                                        <option value="<?php echo htmlspecialchars($event); ?>" <?php echo $filter_event == $event ? 'selected' : ''; ?>>
                                            <?php echo ucwords(str_replace('_', ' ', $event)); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-2">
                            <div class="form-group">
                                <label>User ID</label>
                                <input type="number" class="form-control" name="user" value="<?php echo htmlspecialchars($filter_user); ?>" placeholder="User ID">
                            </div>
                        </div>
                        <div class="col-md-2">
                            <div class="form-group">
                                <label>IP Address</label>
                                <input type="text" class="form-control" name="ip" value="<?php echo htmlspecialchars($filter_ip); ?>" placeholder="IP Address">
                            </div>
                        </div>
                        <div class="col-md-2">
                            <div class="form-group">
                                <label>Date From</label>
                                <input type="date" class="form-control" name="date_from" value="<?php echo htmlspecialchars($filter_date_from); ?>">
                            </div>
                        </div>
                        <div class="col-md-2">
                            <div class="form-group">
                                <label>Date To</label>
                                <input type="date" class="form-control" name="date_to" value="<?php echo htmlspecialchars($filter_date_to); ?>">
                            </div>
                        </div>
                        <div class="col-md-1">
                            <div class="form-group">
                                <label>&nbsp;</label>
                                <button type="submit" class="btn btn-primary btn-block">
                                    <i class="fas fa-search"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                    <?php if (!empty($filter_event) || !empty($filter_user) || !empty($filter_ip) || !empty($filter_date_from) || !empty($filter_date_to)): ?>
                        <a href="admin.php?page=security_audit_log" class="btn btn-secondary btn-sm">
                            <i class="fas fa-times"></i> Clear Filters
                        </a>
                    <?php endif; ?>
                </form>
            </div>

            <!-- Audit Log Entries -->
            <div class="card">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0"><i class="fas fa-list"></i> Audit Log Entries</h5>
                </div>
                <div class="card-body">
                    <?php if ($logs && $logs->num_rows > 0): ?>
                        <?php while ($log = $logs->fetch_assoc()):
                            // Determine dot color
                            $dot_class = 'dot-secondary';
                            if (strpos($log['action'], 'success') !== false) $dot_class = 'dot-success';
                            elseif (strpos($log['action'], 'failed') !== false || strpos($log['action'], 'denied') !== false) $dot_class = 'dot-danger';
                            elseif (strpos($log['action'], 'rate_limited') !== false || strpos($log['action'], 'locked') !== false) $dot_class = 'dot-warning';
                            elseif (strpos($log['action'], 'changed') !== false || strpos($log['action'], 'upgraded') !== false) $dot_class = 'dot-info';
                        ?>
                        <div class="audit-card card">
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-md-8">
                                        <div class="d-flex align-items-center mb-2">
                                            <span class="timeline-dot <?php echo $dot_class; ?>"></span>
                                            <span class="event-badge event-<?php echo $log['action']; ?>">
                                                <?php echo ucwords(str_replace('_', ' ', $log['action'])); ?>
                                            </span>
                                            <span class="ml-auto text-muted">
                                                <i class="far fa-clock"></i> <?php echo date('M d, Y g:i:s A', strtotime($log['created_at'])); ?>
                                            </span>
                                        </div>

                                        <div class="mb-2">
                                            <strong><i class="fas fa-user"></i> User:</strong>
                                            <?php
                                            if ($log['user_id']) {
                                                echo htmlspecialchars($log['user_name'] ?? 'Unknown') . " (ID: " . $log['user_id'] . ", Username: " . htmlspecialchars($log['username'] ?? 'N/A') . ")";
                                            } else {
                                                echo '<span class="text-muted">Guest / Not logged in</span>';
                                            }
                                            ?>
                                        </div>

                                        <div class="mb-2">
                                            <strong><i class="fas fa-network-wired"></i> IP Address:</strong>
                                            <code><?php echo htmlspecialchars($log['ip_address']); ?></code>
                                        </div>

                                        <?php if (!empty($log['details'])): ?>
                                            <div class="mt-3">
                                                <strong><i class="fas fa-info-circle"></i> Details:</strong>
                                                <div class="details-json">
                                                    <?php
                                                    $details = json_decode($log['details'], true);
                                                    if ($details) {
                                                        echo '<pre style="margin: 0;">' . htmlspecialchars(json_encode($details, JSON_PRETTY_PRINT)) . '</pre>';
                                                    } else {
                                                        echo htmlspecialchars($log['details']);
                                                    }
                                                    ?>
                                                </div>
                                            </div>
                                        <?php endif; ?>
                                    </div>

                                    <div class="col-md-4">
                                        <small class="text-muted">
                                            <strong>User Agent:</strong><br>
                                            <?php echo htmlspecialchars(substr($log['user_agent'], 0, 100)); ?>
                                            <?php if (strlen($log['user_agent']) > 100) echo '...'; ?>
                                        </small>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <?php endwhile; ?>

                        <!-- Pagination -->
                        <?php if ($total_pages > 1): ?>
                            <nav aria-label="Audit log pagination" class="mt-4">
                                <ul class="pagination justify-content-center">
                                    <?php if ($p > 1): ?>
                                        <li class="page-item">
                                            <a class="page-link" href="admin.php?page=security_audit_log&p=<?php echo $p - 1; ?><?php echo !empty($filter_event) ? '&event=' . urlencode($filter_event) : ''; ?><?php echo !empty($filter_user) ? '&user=' . urlencode($filter_user) : ''; ?><?php echo !empty($filter_ip) ? '&ip=' . urlencode($filter_ip) : ''; ?><?php echo !empty($filter_date_from) ? '&date_from=' . urlencode($filter_date_from) : ''; ?><?php echo !empty($filter_date_to) ? '&date_to=' . urlencode($filter_date_to) : ''; ?>">
                                                Previous
                                            </a>
                                        </li>
                                    <?php endif; ?>

                                    <?php for ($i = max(1, $p - 2); $i <= min($total_pages, $p + 2); $i++): ?>
                                        <li class="page-item <?php echo $i == $p ? 'active' : ''; ?>">
                                            <a class="page-link" href="admin.php?page=security_audit_log&p=<?php echo $i; ?><?php echo !empty($filter_event) ? '&event=' . urlencode($filter_event) : ''; ?><?php echo !empty($filter_user) ? '&user=' . urlencode($filter_user) : ''; ?><?php echo !empty($filter_ip) ? '&ip=' . urlencode($filter_ip) : ''; ?><?php echo !empty($filter_date_from) ? '&date_from=' . urlencode($filter_date_from) : ''; ?><?php echo !empty($filter_date_to) ? '&date_to=' . urlencode($filter_date_to) : ''; ?>">
                                                <?php echo $i; ?>
                                            </a>
                                        </li>
                                    <?php endfor; ?>

                                    <?php if ($p < $total_pages): ?>
                                        <li class="page-item">
                                            <a class="page-link" href="admin.php?page=security_audit_log&p=<?php echo $p + 1; ?><?php echo !empty($filter_event) ? '&event=' . urlencode($filter_event) : ''; ?><?php echo !empty($filter_user) ? '&user=' . urlencode($filter_user) : ''; ?><?php echo !empty($filter_ip) ? '&ip=' . urlencode($filter_ip) : ''; ?><?php echo !empty($filter_date_from) ? '&date_from=' . urlencode($filter_date_from) : ''; ?><?php echo !empty($filter_date_to) ? '&date_to=' . urlencode($filter_date_to) : ''; ?>">
                                                Next
                                            </a>
                                        </li>
                                    <?php endif; ?>
                                </ul>
                            </nav>
                        <?php endif; ?>

                    <?php else: ?>
                        <div class="text-center text-muted py-5">
                            <i class="fas fa-inbox fa-3x mb-3"></i>
                            <p>No audit log entries found</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <?php endif; ?>

            <!-- Additional Info -->
            <div class="card mt-4">
                <div class="card-header bg-info text-white">
                    <h5 class="mb-0"><i class="fas fa-question-circle"></i> About Security Audit Log</h5>
                </div>
                <div class="card-body">
                    <p><strong>What is logged:</strong></p>
                    <ul>
                        <li><strong>Login Events:</strong> Successful logins, failed login attempts, rate limiting triggers</li>
                        <li><strong>Security Events:</strong> Password changes, password upgrades, account lockouts</li>
                        <li><strong>User Management:</strong> User creation, modification, deletion</li>
                        <li><strong>Session Events:</strong> Logout actions, session timeout warnings</li>
                        <li><strong>Critical Actions:</strong> Payment records, loan modifications, important system changes</li>
                    </ul>

                    <p class="mb-0"><strong>Retention:</strong> Audit logs are stored indefinitely. Consider archiving old logs periodically for optimal database performance.</p>
                </div>
            </div>

        </div>
    </div>
</div>
