<?php
session_start();
include 'db_connect.php';
require_once 'includes/helpers.php';

if(!isset($_SESSION['login_id'])){
    echo '<div class="alert alert-danger">Unauthorized access</div>';
    exit;
}

$loan_id = $_GET['loan_id'] ?? 0;

// Get comprehensive loan details
$stmt = $conn->prepare("
    SELECT l.*,
        CONCAT(b.lastname, ', ', b.firstname, ' ', b.middlename) as borrower_name,
        b.email, b.contact_no, b.address, b.tax_id,
        lt.type_name, lt.description as type_description,
        lp.months as plan_months, lp.interest_percentage as plan_interest, lp.penalty_rate as plan_penalty
    FROM loan_list l
    INNER JOIN borrowers b ON l.borrower_id = b.id
    LEFT JOIN loan_types lt ON l.loan_type_id = lt.id
    LEFT JOIN loan_plan lp ON l.plan_id = lp.id
    WHERE l.id = ?
");
$stmt->bind_param("i", $loan_id);
$stmt->execute();
$loan = $stmt->get_result()->fetch_assoc();
$stmt->close();

if(!$loan) {
    echo '<div class="alert alert-danger">Loan application not found</div>';
    exit;
}

// Get borrower documents
$stmt = $conn->prepare("SELECT * FROM borrower_documents WHERE borrower_id = ? ORDER BY upload_date DESC");
$stmt->bind_param("i", $loan['borrower_id']);
$stmt->execute();
$documents = $stmt->get_result();
$stmt->close();

// Determine if interest rate needs to be set
$needs_rate_assignment = ($loan['amount'] > 5000 && ($loan['interest_rate'] ?? 0) == 0);
$auto_assigned_rate = ($loan['amount'] <= 5000 && ($loan['interest_rate'] ?? 0) == 18);
?>

<style>
    .info-section {
        background: #f8f9fa;
        border-radius: 8px;
        padding: 20px;
        margin-bottom: 20px;
    }
    .info-label {
        font-weight: 600;
        color: #666;
        font-size: 0.9rem;
    }
    .info-value {
        font-size: 1.1rem;
        color: #333;
        margin-bottom: 15px;
    }
    .doc-viewer {
        border: 2px solid #dee2e6;
        border-radius: 8px;
        padding: 10px;
        text-align: center;
        min-height: 200px;
        background: #f8f9fa;
    }
    .doc-viewer img {
        max-width: 100%;
        max-height: 400px;
        border-radius: 4px;
    }
</style>

<div class="container-fluid">
    <div class="row">
        <!-- Left Column: Loan & Borrower Details -->
        <div class="col-md-6">
            <!-- Borrower Information -->
            <div class="info-section">
                <h5 class="mb-3"><i class="fa fa-user"></i> Borrower Information</h5>
                <div class="row">
                    <div class="col-md-6">
                        <div class="info-label">Name</div>
                        <div class="info-value"><?php echo $loan['borrower_name'] ?></div>
                    </div>
                    <div class="col-md-6">
                        <div class="info-label">Tax ID</div>
                        <div class="info-value"><?php echo $loan['tax_id'] ?? 'N/A' ?></div>
                    </div>
                    <div class="col-md-6">
                        <div class="info-label">Email</div>
                        <div class="info-value"><?php echo $loan['email'] ?></div>
                    </div>
                    <div class="col-md-6">
                        <div class="info-label">Contact</div>
                        <div class="info-value"><?php echo $loan['contact_no'] ?></div>
                    </div>
                    <div class="col-md-12">
                        <div class="info-label">Address</div>
                        <div class="info-value"><?php echo $loan['address'] ?? 'N/A' ?></div>
                    </div>
                </div>
            </div>

            <!-- Loan Details -->
            <div class="info-section">
                <h5 class="mb-3"><i class="fa fa-file-contract"></i> Loan Details</h5>
                <div class="row">
                    <div class="col-md-6">
                        <div class="info-label">Reference Number</div>
                        <div class="info-value"><strong><?php echo $loan['ref_no'] ?></strong></div>
                    </div>
                    <div class="col-md-6">
                        <div class="info-label">Loan Type</div>
                        <div class="info-value"><?php echo $loan['type_name'] ?></div>
                    </div>
                    <div class="col-md-6">
                        <div class="info-label">Loan Amount</div>
                        <div class="info-value"><strong class="text-primary"><?php echo formatCurrency($loan['amount']) ?></strong></div>
                    </div>
                    <div class="col-md-6">
                        <div class="info-label">Duration</div>
                        <div class="info-value"><?php echo $loan['duration_months'] ?? 'N/A' ?> months</div>
                    </div>
                    <div class="col-md-12">
                        <div class="info-label">Purpose</div>
                        <div class="info-value"><?php echo nl2br(htmlspecialchars($loan['purpose'])) ?></div>
                    </div>
                    <div class="col-md-12">
                        <div class="info-label">Application Date</div>
                        <div class="info-value"><?php echo date('F d, Y g:i A', strtotime($loan['date_created'])) ?></div>
                    </div>
                </div>
            </div>

            <!-- Interest Rate Assignment -->
            <div class="info-section">
                <h5 class="mb-3"><i class="fa fa-percent"></i> Interest Rate Assignment</h5>

                <?php if($auto_assigned_rate): ?>
                    <div class="alert alert-success">
                        <i class="fa fa-check-circle"></i>
                        <strong>Auto-Assigned Rate:</strong> 18% (Loan amount ≤ K5,000)
                    </div>
                <?php elseif($needs_rate_assignment): ?>
                    <div class="alert alert-warning">
                        <i class="fa fa-exclamation-triangle"></i>
                        <strong>Action Required:</strong> This loan requires manual interest rate assignment (Amount > K5,000)
                    </div>
                    <div class="form-group">
                        <label>Assign Interest Rate</label>
                        <select id="assign_interest_rate" class="form-control">
                            <option value="">Select Interest Rate</option>
                            <option value="25.0">25%</option>
                            <option value="28.0">28%</option>
                            <option value="30.0">30%</option>
                            <option value="35.0">35%</option>
                            <option value="40.0">40%</option>
                        </select>
                    </div>
                <?php else: ?>
                    <div class="alert alert-info">
                        <strong>Current Rate:</strong> <?php echo $loan['interest_rate'] ?? 0 ?>%
                    </div>
                <?php endif; ?>

                <div id="calculation-preview" style="display: none;">
                    <div class="card mt-3">
                        <div class="card-body">
                            <h6>Loan Calculation Preview</h6>
                            <table class="table table-sm">
                                <tr>
                                    <td>Principal:</td>
                                    <td class="text-right"><strong><?php echo formatCurrency($loan['amount']) ?></strong></td>
                                </tr>
                                <tr>
                                    <td>Interest Rate:</td>
                                    <td class="text-right"><strong id="preview-rate">0%</strong></td>
                                </tr>
                                <tr>
                                    <td>Total Interest:</td>
                                    <td class="text-right"><strong id="preview-interest">K 0.00</strong></td>
                                </tr>
                                <tr class="table-primary">
                                    <td><strong>Total Payable:</strong></td>
                                    <td class="text-right"><strong id="preview-total">K 0.00</strong></td>
                                </tr>
                                <tr>
                                    <td>Monthly Payment:</td>
                                    <td class="text-right"><strong id="preview-monthly">K 0.00</strong></td>
                                </tr>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Right Column: Documents & Actions -->
        <div class="col-md-6">
            <!-- Documents Section -->
            <div class="info-section">
                <h5 class="mb-3"><i class="fa fa-folder-open"></i> Uploaded Documents</h5>

                <?php if($documents->num_rows == 0): ?>
                    <div class="alert alert-warning">
                        <i class="fa fa-exclamation-triangle"></i> No documents uploaded yet
                    </div>
                <?php else: ?>
                    <?php while($doc = $documents->fetch_assoc()):
                        $status_class = ['pending' => 'warning', 'verified' => 'success', 'rejected' => 'danger'][$doc['status'] == 1 ? 'verified' : ($doc['status'] == 2 ? 'rejected' : 'pending')];
                        $status_text = ['0' => 'Pending', '1' => 'Verified', '2' => 'Rejected'][$doc['status']];
                        $doc_type_labels = ['id' => 'ID Document', 'employment_proof' => 'Employment Proof', 'payslip' => 'Pay Slip'];
                        $doc_type_label = $doc_type_labels[$doc['document_type']] ?? $doc['document_type'];
                    ?>
                    <div class="doc-card doc-status-<?php echo $status_text == 'Verified' ? 'verified' : ($status_text == 'Pending' ? 'pending' : 'rejected') ?>">
                        <div class="row align-items-center">
                            <div class="col-md-5">
                                <strong><?php echo $doc_type_label ?></strong><br>
                                <small class="text-muted"><?php echo date('M d, Y', strtotime($doc['upload_date'])) ?></small>
                            </div>
                            <div class="col-md-3">
                                <span class="badge badge-<?php echo $status_class ?>"><?php echo $status_text ?></span>
                            </div>
                            <div class="col-md-4 text-right">
                                <button class="btn btn-sm btn-info" onclick="viewDocument('<?php echo $doc['file_path'] ?>', '<?php echo $doc_type_label ?>')">
                                    <i class="fa fa-eye"></i> View
                                </button>
                                <?php if($doc['status'] == 0): ?>
                                <button class="btn btn-sm btn-success" onclick="verifyDocument(<?php echo $doc['id'] ?>)">
                                    <i class="fa fa-check"></i>
                                </button>
                                <button class="btn btn-sm btn-danger" onclick="rejectDocument(<?php echo $doc['id'] ?>)">
                                    <i class="fa fa-times"></i>
                                </button>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                    <?php endwhile; ?>
                <?php endif; ?>
            </div>

            <!-- Document Viewer -->
            <div class="info-section">
                <h5 class="mb-3"><i class="fa fa-file-image"></i> Document Viewer</h5>
                <div class="doc-viewer" id="doc-viewer">
                    <p class="text-muted mt-5">Click "View" on a document to preview it here</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Action Buttons -->
    <div class="row mt-4">
        <div class="col-12">
            <div class="action-buttons text-right">
                <form id="review-action-form">
                    <input type="hidden" name="loan_id" value="<?php echo $loan_id ?>">
                    <input type="hidden" name="interest_rate" id="final_interest_rate" value="<?php echo $loan['interest_rate'] ?? 0 ?>">

                    <button type="button" class="btn btn-secondary" data-dismiss="modal">
                        <i class="fa fa-times"></i> Cancel
                    </button>

                    <button type="button" class="btn btn-danger" onclick="denyLoan()">
                        <i class="fa fa-ban"></i> Deny Application
                    </button>

                    <button type="button" class="btn btn-success" onclick="approveLoan()" <?php echo $needs_rate_assignment ? 'id="approve-btn" disabled' : '' ?>>
                        <i class="fa fa-check"></i> Approve Loan
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
// View document
function viewDocument(filePath, docName) {
    const extension = filePath.split('.').pop().toLowerCase();
    let html = '';

    if(['jpg', 'jpeg', 'png', 'gif'].includes(extension)) {
        html = `<h6>${docName}</h6><img src="${filePath}" alt="${docName}" class="img-fluid">`;
    } else if(extension === 'pdf') {
        html = `<h6>${docName}</h6><embed src="${filePath}" type="application/pdf" width="100%" height="500px">`;
    } else {
        html = `<h6>${docName}</h6><p class="text-muted">Preview not available. <a href="${filePath}" target="_blank">Download</a></p>`;
    }

    $('#doc-viewer').html(html);
}

// Verify document
function verifyDocument(docId) {
    if(!confirm('Verify this document?')) return;

    $.ajax({
        url: 'ajax.php?action=update_document_status',
        method: 'POST',
        data: {
            document_id: docId,
            status: 1
        },
        success: function(resp) {
            if(resp == 1) {
                alert_toast('Document verified', 'success');
                location.reload();
            } else {
                alert_toast('Error verifying document', 'error');
            }
        }
    });
}

// Reject document
function rejectDocument(docId) {
    const reason = prompt('Enter reason for rejection:');
    if(!reason) return;

    $.ajax({
        url: 'ajax.php?action=update_document_status',
        method: 'POST',
        data: {
            document_id: docId,
            status: 2,
            verification_notes: reason
        },
        success: function(resp) {
            if(resp == 1) {
                alert_toast('Document rejected', 'success');
                location.reload();
            } else {
                alert_toast('Error rejecting document', 'error');
            }
        }
    });
}

// Interest rate calculation preview
<?php if($needs_rate_assignment): ?>
$('#assign_interest_rate').change(function() {
    const rate = parseFloat($(this).val());
    if(rate > 0) {
        const amount = <?php echo $loan['amount'] ?>;
        const months = <?php echo $loan['duration_months'] ?? 1 ?>;

        // Calculate using simple interest
        const monthlyRate = rate / 100 / 12;
        const totalInterest = amount * monthlyRate * months;
        const totalPayable = amount + totalInterest;
        const monthlyPayment = totalPayable / months;

        $('#preview-rate').text(rate + '%');
        $('#preview-interest').text('K ' + totalInterest.toLocaleString('en-US', {minimumFractionDigits: 2}));
        $('#preview-total').text('K ' + totalPayable.toLocaleString('en-US', {minimumFractionDigits: 2}));
        $('#preview-monthly').text('K ' + monthlyPayment.toLocaleString('en-US', {minimumFractionDigits: 2}));

        $('#calculation-preview').slideDown();
        $('#final_interest_rate').val(rate);
        $('#approve-btn').prop('disabled', false);
    } else {
        $('#calculation-preview').slideUp();
        $('#approve-btn').prop('disabled', true);
    }
});
<?php endif; ?>

// Approve loan
function approveLoan() {
    const interestRate = $('#final_interest_rate').val();

    if(!interestRate || parseFloat(interestRate) == 0) {
        alert_toast('Please assign an interest rate first', 'warning');
        return;
    }

    if(!confirm('Approve this loan application?')) return;

    start_load();

    $.ajax({
        url: 'ajax.php?action=approve_loan_application',
        method: 'POST',
        data: {
            loan_id: <?php echo $loan_id ?>,
            interest_rate: interestRate
        },
        success: function(resp) {
            if(resp == 1) {
                alert_toast('Loan approved successfully', 'success');
                setTimeout(function() {
                    location.reload();
                }, 1500);
            } else {
                alert_toast('Error approving loan: ' + resp, 'error');
                end_load();
            }
        },
        error: function() {
            alert_toast('Server error occurred', 'error');
            end_load();
        }
    });
}

// Deny loan
function denyLoan() {
    const reason = prompt('Enter reason for denial:');
    if(!reason) return;

    start_load();

    $.ajax({
        url: 'ajax.php?action=deny_loan_application',
        method: 'POST',
        data: {
            loan_id: <?php echo $loan_id ?>,
            denial_reason: reason
        },
        success: function(resp) {
            if(resp == 1) {
                alert_toast('Loan application denied', 'success');
                setTimeout(function() {
                    location.reload();
                }, 1500);
            } else {
                alert_toast('Error denying loan', 'error');
                end_load();
            }
        }
    });
}
</script>
