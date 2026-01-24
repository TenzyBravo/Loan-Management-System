<?php
include 'db_connect.php';
require_once 'includes/security.php';

// Check if user is admin (type 1)
if(!isset($_SESSION['login_type']) || $_SESSION['login_type'] != 1) {
    echo '<div class="alert alert-danger">Access denied. Administrator privileges required.</div>';
    exit;
}
?>

<style>
    .backup-container {
        max-width: 1000px;
        margin: 0 auto;
    }
    .backup-card {
        background: white;
        border-radius: 12px;
        box-shadow: 0 2px 10px rgba(0,0,0,0.08);
        overflow: hidden;
        margin-bottom: 20px;
    }
    .backup-card-header {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
        padding: 20px 25px;
    }
    .backup-card-header h5 {
        margin: 0;
        font-weight: 600;
    }
    .backup-card-header p {
        margin: 5px 0 0;
        opacity: 0.9;
        font-size: 0.9rem;
    }
    .backup-card-body {
        padding: 25px;
    }
    .export-option {
        display: flex;
        align-items: center;
        padding: 15px;
        border: 1px solid #e5e7eb;
        border-radius: 8px;
        margin-bottom: 12px;
        transition: all 0.2s;
        cursor: pointer;
    }
    .export-option:hover {
        border-color: #667eea;
        background: #f8f9ff;
    }
    .export-option .icon {
        width: 50px;
        height: 50px;
        border-radius: 10px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.5rem;
        margin-right: 15px;
    }
    .export-option .icon.green {
        background: #d1fae5;
        color: #10b981;
    }
    .export-option .icon.blue {
        background: #dbeafe;
        color: #2563eb;
    }
    .export-option .icon.purple {
        background: #ede9fe;
        color: #7c3aed;
    }
    .export-option .icon.orange {
        background: #ffedd5;
        color: #f97316;
    }
    .export-option .details {
        flex: 1;
    }
    .export-option .details h6 {
        margin: 0 0 5px;
        font-weight: 600;
        color: #1f2937;
    }
    .export-option .details p {
        margin: 0;
        font-size: 0.85rem;
        color: #6b7280;
    }
    .export-option .btn-export {
        padding: 8px 20px;
        border-radius: 6px;
        font-weight: 500;
    }
    .backup-info {
        background: #f0f9ff;
        border: 1px solid #bae6fd;
        border-radius: 8px;
        padding: 15px;
        margin-bottom: 20px;
    }
    .backup-info i {
        color: #0284c7;
        margin-right: 10px;
    }
    .backup-warning {
        background: #fef3c7;
        border: 1px solid #fcd34d;
        border-radius: 8px;
        padding: 15px;
        margin-bottom: 20px;
    }
    .backup-warning i {
        color: #d97706;
        margin-right: 10px;
    }
    .recent-backups {
        margin-top: 20px;
    }
    .recent-backups table {
        margin-bottom: 0;
    }
</style>

<div class="container-fluid p-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h4 class="mb-1"><i class="fa fa-database"></i> Backup & Export</h4>
            <p class="text-muted mb-0">Create database backups and export data to CSV</p>
        </div>
    </div>

    <div class="backup-container">
        <!-- Database Backup Section -->
        <div class="backup-card">
            <div class="backup-card-header">
                <h5><i class="fa fa-hdd mr-2"></i> Database Backup</h5>
                <p>Create a full backup of your database</p>
            </div>
            <div class="backup-card-body">
                <div class="backup-warning">
                    <i class="fa fa-exclamation-triangle"></i>
                    <strong>Important:</strong> Database backups contain sensitive information. Store them securely and delete old backups regularly.
                </div>

                <div class="export-option" onclick="createDatabaseBackup()">
                    <div class="icon purple">
                        <i class="fa fa-download"></i>
                    </div>
                    <div class="details">
                        <h6>Full Database Backup (SQL)</h6>
                        <p>Downloads a complete SQL backup file that can be used to restore the database</p>
                    </div>
                    <button class="btn btn-primary btn-export" id="btnBackup">
                        <i class="fa fa-download mr-1"></i> Download Backup
                    </button>
                </div>
            </div>
        </div>

        <!-- CSV Export Section -->
        <div class="backup-card">
            <div class="backup-card-header" style="background: linear-gradient(135deg, #10b981 0%, #059669 100%);">
                <h5><i class="fa fa-file-csv mr-2"></i> Export Data to CSV</h5>
                <p>Export specific data tables to CSV format for Excel/spreadsheet use</p>
            </div>
            <div class="backup-card-body">
                <div class="backup-info">
                    <i class="fa fa-info-circle"></i>
                    CSV files can be opened in Microsoft Excel, Google Sheets, or any spreadsheet application.
                </div>

                <!-- Borrowers Export -->
                <div class="export-option" onclick="exportCSV('borrowers')">
                    <div class="icon blue">
                        <i class="fa fa-users"></i>
                    </div>
                    <div class="details">
                        <h6>Export Borrowers</h6>
                        <p>All registered borrowers with their contact information</p>
                    </div>
                    <button class="btn btn-success btn-export">
                        <i class="fa fa-file-export mr-1"></i> Export CSV
                    </button>
                </div>

                <!-- Loans Export -->
                <div class="export-option" onclick="exportCSV('loans')">
                    <div class="icon green">
                        <i class="fa fa-file-invoice-dollar"></i>
                    </div>
                    <div class="details">
                        <h6>Export Loans</h6>
                        <p>All loan records with amounts, interest rates, and status</p>
                    </div>
                    <button class="btn btn-success btn-export">
                        <i class="fa fa-file-export mr-1"></i> Export CSV
                    </button>
                </div>

                <!-- Payments Export -->
                <div class="export-option" onclick="exportCSV('payments')">
                    <div class="icon orange">
                        <i class="fa fa-money-bill"></i>
                    </div>
                    <div class="details">
                        <h6>Export Payments</h6>
                        <p>All payment transactions with dates and amounts</p>
                    </div>
                    <button class="btn btn-success btn-export">
                        <i class="fa fa-file-export mr-1"></i> Export CSV
                    </button>
                </div>

                <!-- Loan Types Export -->
                <div class="export-option" onclick="exportCSV('loan_types')">
                    <div class="icon purple">
                        <i class="fa fa-th-list"></i>
                    </div>
                    <div class="details">
                        <h6>Export Loan Types</h6>
                        <p>All loan type configurations</p>
                    </div>
                    <button class="btn btn-success btn-export">
                        <i class="fa fa-file-export mr-1"></i> Export CSV
                    </button>
                </div>

                <!-- Loan Plans Export -->
                <div class="export-option" onclick="exportCSV('loan_plans')">
                    <div class="icon blue">
                        <i class="fa fa-list-alt"></i>
                    </div>
                    <div class="details">
                        <h6>Export Loan Plans</h6>
                        <p>All loan plan configurations with interest rates</p>
                    </div>
                    <button class="btn btn-success btn-export">
                        <i class="fa fa-file-export mr-1"></i> Export CSV
                    </button>
                </div>

                <!-- Full Report Export -->
                <div class="export-option" onclick="exportCSV('full_report')">
                    <div class="icon green">
                        <i class="fa fa-chart-bar"></i>
                    </div>
                    <div class="details">
                        <h6>Export Full Loan Report</h6>
                        <p>Comprehensive report with borrower names, loan details, and payment status</p>
                    </div>
                    <button class="btn btn-success btn-export">
                        <i class="fa fa-file-export mr-1"></i> Export CSV
                    </button>
                </div>
            </div>
        </div>

        <!-- Quick Stats -->
        <div class="backup-card">
            <div class="backup-card-header" style="background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%);">
                <h5><i class="fa fa-chart-pie mr-2"></i> Data Summary</h5>
                <p>Overview of data that will be exported</p>
            </div>
            <div class="backup-card-body">
                <div class="row">
                    <?php
                    // Get counts
                    $borrower_count = $conn->query("SELECT COUNT(*) as cnt FROM borrowers")->fetch_assoc()['cnt'];
                    $loan_count = $conn->query("SELECT COUNT(*) as cnt FROM loan_list")->fetch_assoc()['cnt'];
                    $payment_count = $conn->query("SELECT COUNT(*) as cnt FROM payments")->fetch_assoc()['cnt'];
                    $active_loans = $conn->query("SELECT COUNT(*) as cnt FROM loan_list WHERE status IN (1,2)")->fetch_assoc()['cnt'];
                    ?>
                    <div class="col-md-3 col-6 mb-3">
                        <div class="text-center p-3" style="background: #f3f4f6; border-radius: 8px;">
                            <h3 class="mb-1" style="color: #2563eb;"><?php echo $borrower_count; ?></h3>
                            <small class="text-muted">Total Borrowers</small>
                        </div>
                    </div>
                    <div class="col-md-3 col-6 mb-3">
                        <div class="text-center p-3" style="background: #f3f4f6; border-radius: 8px;">
                            <h3 class="mb-1" style="color: #10b981;"><?php echo $loan_count; ?></h3>
                            <small class="text-muted">Total Loans</small>
                        </div>
                    </div>
                    <div class="col-md-3 col-6 mb-3">
                        <div class="text-center p-3" style="background: #f3f4f6; border-radius: 8px;">
                            <h3 class="mb-1" style="color: #f59e0b;"><?php echo $payment_count; ?></h3>
                            <small class="text-muted">Total Payments</small>
                        </div>
                    </div>
                    <div class="col-md-3 col-6 mb-3">
                        <div class="text-center p-3" style="background: #f3f4f6; border-radius: 8px;">
                            <h3 class="mb-1" style="color: #7c3aed;"><?php echo $active_loans; ?></h3>
                            <small class="text-muted">Active Loans</small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function createDatabaseBackup() {
    if(!confirm('Create a full database backup? This may take a moment.')) {
        return;
    }

    start_load();
    window.location.href = 'export.php?type=database_backup';

    // End loading after a delay (file download doesn't trigger normal AJAX callback)
    setTimeout(function() {
        end_load();
    }, 3000);
}

function exportCSV(type) {
    start_load();
    window.location.href = 'export.php?type=' + type;

    // End loading after a delay
    setTimeout(function() {
        end_load();
    }, 2000);
}
</script>
