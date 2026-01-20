<?php
include 'db_connect.php';
require_once 'includes/helpers.php';
?>

<style>
    .status-badge {
        padding: 5px 12px;
        border-radius: 20px;
        font-size: 0.85rem;
        font-weight: 600;
    }
    .status-submitted { background: #fff3cd; color: #856404; }
    .status-under-review { background: #d1ecf1; color: #0c5460; }
    .status-approved { background: #d4edda; color: #155724; }
    .status-denied { background: #f8d7da; color: #721c24; }
    .status-pending { background: #e2e3e5; color: #383d41; }

    .doc-card {
        border: 1px solid #dee2e6;
        border-radius: 8px;
        padding: 15px;
        margin-bottom: 10px;
        transition: all 0.3s;
    }
    .doc-card:hover {
        box-shadow: 0 2px 8px rgba(0,0,0,0.1);
    }
    .doc-status-verified { border-left: 4px solid #28a745; }
    .doc-status-pending { border-left: 4px solid #ffc107; }
    .doc-status-rejected { border-left: 4px solid #dc3545; }

    .loan-detail-row {
        padding: 10px 0;
        border-bottom: 1px solid #f0f0f0;
    }
    .loan-detail-row:last-child {
        border-bottom: none;
    }

    .action-buttons {
        position: sticky;
        bottom: 0;
        background: white;
        padding: 20px;
        border-top: 2px solid #dee2e6;
        margin: 0 -15px -15px -15px;
    }
</style>

<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <h4 class="mb-4"><i class="fa fa-clipboard-check"></i> Loan Applications Review</h4>

            <!-- Filter Tabs -->
            <ul class="nav nav-tabs mb-3" role="tablist">
                <li class="nav-item">
                    <a class="nav-link active" data-toggle="tab" href="#pending-applications">
                        Pending Review <span class="badge badge-warning" id="pending-count">0</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" data-toggle="tab" href="#approved-applications">
                        Approved <span class="badge badge-success" id="approved-count">0</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" data-toggle="tab" href="#denied-applications">
                        Denied <span class="badge badge-danger" id="denied-count">0</span>
                    </a>
                </li>
            </ul>

            <!-- Tab Content -->
            <div class="tab-content">
                <!-- Pending Applications -->
                <div id="pending-applications" class="tab-pane fade show active">
                    <div class="card">
                        <div class="card-body">
                            <table class="table table-hover table-bordered" id="pending-table">
                                <thead class="thead-light">
                                    <tr>
                                        <th width="10%">Ref No.</th>
                                        <th width="20%">Borrower</th>
                                        <th width="12%">Amount</th>
                                        <th width="10%">Interest</th>
                                        <th width="10%">Duration</th>
                                        <th width="12%">Date Applied</th>
                                        <th width="10%">Documents</th>
                                        <th width="16%">Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php
                                    $stmt = $conn->prepare("
                                        SELECT l.*,
                                            CONCAT(b.lastname, ', ', b.firstname, ' ', b.middlename) as borrower_name,
                                            b.email, b.contact_no,
                                            lt.type_name,
                                            (SELECT COUNT(*) FROM borrower_documents WHERE borrower_id = l.borrower_id AND status = 0) as pending_docs,
                                            (SELECT COUNT(*) FROM borrower_documents WHERE borrower_id = l.borrower_id AND status = 1) as verified_docs,
                                            (SELECT COUNT(*) FROM borrower_documents WHERE borrower_id = l.borrower_id) as total_docs
                                        FROM loan_list l
                                        INNER JOIN borrowers b ON l.borrower_id = b.id
                                        LEFT JOIN loan_types lt ON l.loan_type_id = lt.id
                                        WHERE l.status = 0
                                        ORDER BY l.date_created DESC
                                    ");
                                    $stmt->execute();
                                    $pending = $stmt->get_result();
                                    $pending_count = $pending->num_rows;

                                    while($row = $pending->fetch_assoc()):
                                        $interest_display = $row['interest_rate'] > 0 ? $row['interest_rate'] . '%' : '<span class="text-warning">Not Set</span>';
                                        $duration_display = $row['duration_months'] ?? 'N/A';
                                    ?>
                                    <tr>
                                        <td><strong><?php echo $row['ref_no'] ?></strong></td>
                                        <td>
                                            <?php echo $row['borrower_name'] ?><br>
                                            <small class="text-muted"><?php echo $row['email'] ?></small>
                                        </td>
                                        <td><strong><?php echo formatCurrency($row['amount']) ?></strong></td>
                                        <td><?php echo $interest_display ?></td>
                                        <td><?php echo $duration_display ?> months</td>
                                        <td><?php echo date('M d, Y', strtotime($row['date_created'])) ?></td>
                                        <td>
                                            <span class="badge badge-success"><?php echo $row['verified_docs'] ?></span> /
                                            <span class="badge badge-warning"><?php echo $row['pending_docs'] ?></span> /
                                            <span class="badge badge-secondary"><?php echo $row['total_docs'] ?></span>
                                        </td>
                                        <td>
                                            <button class="btn btn-sm btn-primary" onclick="reviewLoan(<?php echo $row['id'] ?>)">
                                                <i class="fa fa-eye"></i> Review
                                            </button>
                                        </td>
                                    </tr>
                                    <?php endwhile; ?>
                                    <?php if($pending_count == 0): ?>
                                    <tr>
                                        <td colspan="8" class="text-center text-muted py-4">
                                            <i class="fa fa-inbox fa-3x mb-3"></i><br>
                                            No pending applications
                                        </td>
                                    </tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <!-- Approved Applications -->
                <div id="approved-applications" class="tab-pane fade">
                    <div class="card">
                        <div class="card-body">
                            <table class="table table-hover table-bordered">
                                <thead class="thead-light">
                                    <tr>
                                        <th>Ref No.</th>
                                        <th>Borrower</th>
                                        <th>Amount</th>
                                        <th>Interest</th>
                                        <th>Status</th>
                                        <th>Date Approved</th>
                                        <th>Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php
                                    $stmt = $conn->prepare("
                                        SELECT l.*,
                                            CONCAT(b.lastname, ', ', b.firstname, ' ', b.middlename) as borrower_name,
                                            lt.type_name
                                        FROM loan_list l
                                        INNER JOIN borrowers b ON l.borrower_id = b.id
                                        LEFT JOIN loan_types lt ON l.loan_type_id = lt.id
                                        WHERE l.status IN (1, 2, 3)
                                        ORDER BY l.date_created DESC
                                    ");
                                    $stmt->execute();
                                    $approved = $stmt->get_result();
                                    $approved_count = $approved->num_rows;

                                    while($row = $approved->fetch_assoc()):
                                        $status_labels = [1 => 'Approved', 2 => 'Released', 3 => 'Completed'];
                                        $status_classes = [1 => 'success', 2 => 'info', 3 => 'secondary'];
                                    ?>
                                    <tr>
                                        <td><strong><?php echo $row['ref_no'] ?></strong></td>
                                        <td><?php echo $row['borrower_name'] ?></td>
                                        <td><?php echo formatCurrency($row['amount']) ?></td>
                                        <td><?php echo $row['interest_rate'] ?>%</td>
                                        <td>
                                            <span class="badge badge-<?php echo $status_classes[$row['status']] ?>">
                                                <?php echo $status_labels[$row['status']] ?>
                                            </span>
                                        </td>
                                        <td><?php echo date('M d, Y', strtotime($row['date_created'])) ?></td>
                                        <td>
                                            <button class="btn btn-sm btn-info" onclick="viewLoan(<?php echo $row['id'] ?>)">
                                                <i class="fa fa-eye"></i> View
                                            </button>
                                        </td>
                                    </tr>
                                    <?php endwhile; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <!-- Denied Applications -->
                <div id="denied-applications" class="tab-pane fade">
                    <div class="card">
                        <div class="card-body">
                            <table class="table table-hover table-bordered">
                                <thead class="thead-light">
                                    <tr>
                                        <th>Ref No.</th>
                                        <th>Borrower</th>
                                        <th>Amount</th>
                                        <th>Date Applied</th>
                                        <th>Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php
                                    $stmt = $conn->prepare("
                                        SELECT l.*,
                                            CONCAT(b.lastname, ', ', b.firstname, ' ', b.middlename) as borrower_name
                                        FROM loan_list l
                                        INNER JOIN borrowers b ON l.borrower_id = b.id
                                        WHERE l.status = 4
                                        ORDER BY l.date_created DESC
                                    ");
                                    $stmt->execute();
                                    $denied = $stmt->get_result();
                                    $denied_count = $denied->num_rows;

                                    while($row = $denied->fetch_assoc()):
                                    ?>
                                    <tr>
                                        <td><strong><?php echo $row['ref_no'] ?></strong></td>
                                        <td><?php echo $row['borrower_name'] ?></td>
                                        <td><?php echo formatCurrency($row['amount']) ?></td>
                                        <td><?php echo date('M d, Y', strtotime($row['date_created'])) ?></td>
                                        <td>
                                            <button class="btn btn-sm btn-secondary" onclick="viewLoan(<?php echo $row['id'] ?>)">
                                                <i class="fa fa-eye"></i> View
                                            </button>
                                        </td>
                                    </tr>
                                    <?php endwhile; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Review Modal -->
<div class="modal fade" id="reviewModal" tabindex="-1" role="dialog" style="overflow-y: auto;">
    <div class="modal-dialog modal-xl" role="document">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title"><i class="fa fa-clipboard-check"></i> Loan Application Review</h5>
                <button type="button" class="close text-white" data-dismiss="modal">
                    <span>&times;</span>
                </button>
            </div>
            <div class="modal-body" id="review-content" style="max-height: 70vh; overflow-y: auto;">
                <div class="text-center py-5">
                    <i class="fa fa-spinner fa-spin fa-3x text-primary"></i>
                    <p class="mt-3">Loading application details...</p>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// Update badge counts
document.getElementById('pending-count').textContent = <?php echo $pending_count ?>;
document.getElementById('approved-count').textContent = <?php echo $approved_count ?>;
document.getElementById('denied-count').textContent = <?php echo $denied_count ?>;

// Review loan application
function reviewLoan(loanId) {
    $('#reviewModal').modal('show');
    $('#review-content').html('<div class="text-center py-5"><i class="fa fa-spinner fa-spin fa-3x text-primary"></i><p class="mt-3">Loading...</p></div>');

    $.ajax({
        url: 'loan_review_details.php',
        method: 'GET',
        data: { loan_id: loanId },
        success: function(response) {
            $('#review-content').html(response);
        },
        error: function() {
            $('#review-content').html('<div class="alert alert-danger">Error loading application details</div>');
        }
    });
}

// View loan (for approved/denied)
function viewLoan(loanId) {
    uni_modal("Loan Details", "manage_loan.php?id=" + loanId, 'large');
}
</script>
