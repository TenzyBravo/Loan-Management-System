<?php
include 'db_connect.php';
require_once 'includes/helpers.php';
?>

<style>
    .doc-card {
        border: 1px solid #dee2e6;
        border-radius: 8px;
        padding: 15px;
        margin-bottom: 15px;
        transition: all 0.3s;
    }
    .doc-card:hover {
        box-shadow: 0 2px 8px rgba(0,0,0,0.1);
    }
    .doc-status-verified { border-left: 4px solid #28a745; background: #f0f9f4; }
    .doc-status-pending { border-left: 4px solid #ffc107; background: #fffbf0; }
    .doc-status-rejected { border-left: 4px solid #dc3545; background: #fdf0f0; }

    .borrower-card {
        background: white;
        border-radius: 10px;
        padding: 20px;
        margin-bottom: 20px;
        box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    }
    .borrower-card:hover {
        box-shadow: 0 4px 12px rgba(0,0,0,0.15);
    }
    .stat-badge {
        display: inline-block;
        padding: 5px 12px;
        border-radius: 20px;
        font-size: 0.85rem;
        font-weight: 600;
        margin-right: 5px;
    }
    .doc-viewer-modal .modal-dialog {
        max-width: 80%;
    }
    .doc-viewer-content {
        text-align: center;
        min-height: 400px;
        background: #f8f9fa;
        padding: 20px;
        border-radius: 8px;
    }
    .doc-viewer-content img {
        max-width: 100%;
        max-height: 600px;
        border-radius: 4px;
        box-shadow: 0 2px 8px rgba(0,0,0,0.2);
    }
</style>

<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <h4 class="mb-4"><i class="fa fa-folder-open"></i> Customer Documents Management</h4>

            <!-- Filter Tabs -->
            <ul class="nav nav-tabs mb-3" role="tablist">
                <li class="nav-item">
                    <a class="nav-link active" data-toggle="tab" href="#pending-docs">
                        Pending Verification <span class="badge badge-warning" id="pending-count">0</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" data-toggle="tab" href="#verified-docs">
                        Verified <span class="badge badge-success" id="verified-count">0</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" data-toggle="tab" href="#rejected-docs">
                        Rejected <span class="badge badge-danger" id="rejected-count">0</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" data-toggle="tab" href="#all-borrowers">
                        All Borrowers <span class="badge badge-secondary" id="borrowers-count">0</span>
                    </a>
                </li>
            </ul>

            <!-- Tab Content -->
            <div class="tab-content">
                <!-- Pending Documents -->
                <div id="pending-docs" class="tab-pane fade show active">
                    <div class="card">
                        <div class="card-body">
                            <?php
                            $stmt = $conn->prepare("
                                SELECT bd.*,
                                    CONCAT(b.lastname, ', ', b.firstname, ' ', COALESCE(b.middlename, '')) as borrower_name,
                                    b.email, b.contact_no
                                FROM borrower_documents bd
                                INNER JOIN borrowers b ON bd.borrower_id = b.id
                                WHERE bd.status = 0
                                ORDER BY bd.upload_date DESC
                            ");
                            $stmt->execute();
                            $pending_docs = $stmt->get_result();
                            $pending_count = $pending_docs->num_rows;

                            $doc_type_labels = [
                                'id' => 'ID Document',
                                'employment_proof' => 'Employment Proof',
                                'payslip' => 'Pay Slip'
                            ];
                            ?>

                            <?php if($pending_count == 0): ?>
                                <div class="text-center text-muted py-5">
                                    <i class="fa fa-check-circle fa-3x mb-3"></i><br>
                                    <h5>All documents verified!</h5>
                                    <p>No pending documents to review</p>
                                </div>
                            <?php else: ?>
                                <div class="row">
                                    <?php while($doc = $pending_docs->fetch_assoc()):
                                        $doc_type_label = $doc_type_labels[$doc['document_type']] ?? $doc['document_type'];
                                    ?>
                                    <div class="col-md-6 col-lg-4">
                                        <div class="doc-card doc-status-pending">
                                            <h6><?php echo $doc_type_label ?></h6>
                                            <p class="mb-2">
                                                <strong><?php echo $doc['borrower_name'] ?></strong><br>
                                                <small class="text-muted"><?php echo $doc['email'] ?></small>
                                            </p>
                                            <p class="mb-2">
                                                <small>
                                                    <i class="fa fa-calendar"></i>
                                                    <?php echo date('M d, Y g:i A', strtotime($doc['upload_date'])) ?>
                                                </small><br>
                                                <small>
                                                    <i class="fa fa-file"></i>
                                                    <?php echo round($doc['file_size']/1024, 2) ?> KB
                                                </small>
                                            </p>
                                            <div class="btn-group btn-block">
                                                <button class="btn btn-sm btn-info" onclick="viewDocument('<?php echo $doc['file_path'] ?>', '<?php echo $doc_type_label ?>', '<?php echo addslashes($doc['borrower_name']) ?>')">
                                                    <i class="fa fa-eye"></i> View
                                                </button>
                                                <button class="btn btn-sm btn-success" onclick="verifyDocument(<?php echo $doc['id'] ?>)">
                                                    <i class="fa fa-check"></i> Verify
                                                </button>
                                                <button class="btn btn-sm btn-danger" onclick="rejectDocument(<?php echo $doc['id'] ?>)">
                                                    <i class="fa fa-times"></i> Reject
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                    <?php endwhile; ?>
                                </div>
                            <?php endif; ?>
                            <?php $stmt->close(); ?>
                        </div>
                    </div>
                </div>

                <!-- Verified Documents -->
                <div id="verified-docs" class="tab-pane fade">
                    <div class="card">
                        <div class="card-body">
                            <?php
                            $stmt = $conn->prepare("
                                SELECT bd.*,
                                    CONCAT(b.lastname, ', ', b.firstname, ' ', COALESCE(b.middlename, '')) as borrower_name,
                                    b.email
                                FROM borrower_documents bd
                                INNER JOIN borrowers b ON bd.borrower_id = b.id
                                WHERE bd.status = 1
                                ORDER BY bd.verification_date DESC
                            ");
                            $stmt->execute();
                            $verified_docs = $stmt->get_result();
                            $verified_count = $verified_docs->num_rows;
                            ?>

                            <div class="row">
                                <?php while($doc = $verified_docs->fetch_assoc()):
                                    $doc_type_label = $doc_type_labels[$doc['document_type']] ?? $doc['document_type'];
                                ?>
                                <div class="col-md-6 col-lg-4">
                                    <div class="doc-card doc-status-verified">
                                        <div class="d-flex justify-content-between align-items-start">
                                            <h6><?php echo $doc_type_label ?></h6>
                                            <span class="badge badge-success"><i class="fa fa-check"></i> Verified</span>
                                        </div>
                                        <p class="mb-2">
                                            <strong><?php echo $doc['borrower_name'] ?></strong><br>
                                            <small class="text-muted"><?php echo $doc['email'] ?></small>
                                        </p>
                                        <p class="mb-2">
                                            <small>
                                                <i class="fa fa-check-circle text-success"></i>
                                                Verified: <?php echo date('M d, Y', strtotime($doc['verification_date'])) ?>
                                            </small>
                                        </p>
                                        <button class="btn btn-sm btn-info btn-block" onclick="viewDocument('<?php echo $doc['file_path'] ?>', '<?php echo $doc_type_label ?>', '<?php echo addslashes($doc['borrower_name']) ?>')">
                                            <i class="fa fa-eye"></i> View Document
                                        </button>
                                    </div>
                                </div>
                                <?php endwhile; ?>
                            </div>
                            <?php $stmt->close(); ?>
                        </div>
                    </div>
                </div>

                <!-- Rejected Documents -->
                <div id="rejected-docs" class="tab-pane fade">
                    <div class="card">
                        <div class="card-body">
                            <?php
                            $stmt = $conn->prepare("
                                SELECT bd.*,
                                    CONCAT(b.lastname, ', ', b.firstname, ' ', COALESCE(b.middlename, '')) as borrower_name,
                                    b.email
                                FROM borrower_documents bd
                                INNER JOIN borrowers b ON bd.borrower_id = b.id
                                WHERE bd.status = 2
                                ORDER BY bd.verification_date DESC
                            ");
                            $stmt->execute();
                            $rejected_docs = $stmt->get_result();
                            $rejected_count = $rejected_docs->num_rows;
                            ?>

                            <div class="row">
                                <?php while($doc = $rejected_docs->fetch_assoc()):
                                    $doc_type_label = $doc_type_labels[$doc['document_type']] ?? $doc['document_type'];
                                ?>
                                <div class="col-md-6 col-lg-4">
                                    <div class="doc-card doc-status-rejected">
                                        <div class="d-flex justify-content-between align-items-start">
                                            <h6><?php echo $doc_type_label ?></h6>
                                            <span class="badge badge-danger"><i class="fa fa-times"></i> Rejected</span>
                                        </div>
                                        <p class="mb-2">
                                            <strong><?php echo $doc['borrower_name'] ?></strong><br>
                                            <small class="text-muted"><?php echo $doc['email'] ?></small>
                                        </p>
                                        <p class="mb-2">
                                            <small>
                                                <i class="fa fa-times-circle text-danger"></i>
                                                Rejected: <?php echo date('M d, Y', strtotime($doc['verification_date'])) ?>
                                            </small><br>
                                            <?php if($doc['verification_notes']): ?>
                                            <small class="text-danger">
                                                <strong>Reason:</strong> <?php echo htmlspecialchars($doc['verification_notes']) ?>
                                            </small>
                                            <?php endif; ?>
                                        </p>
                                        <div class="btn-group btn-block">
                                            <button class="btn btn-sm btn-info" onclick="viewDocument('<?php echo $doc['file_path'] ?>', '<?php echo $doc_type_label ?>', '<?php echo addslashes($doc['borrower_name']) ?>')">
                                                <i class="fa fa-eye"></i> View
                                            </button>
                                            <button class="btn btn-sm btn-success" onclick="verifyDocument(<?php echo $doc['id'] ?>)">
                                                <i class="fa fa-check"></i> Verify Now
                                            </button>
                                        </div>
                                    </div>
                                </div>
                                <?php endwhile; ?>
                            </div>
                            <?php $stmt->close(); ?>
                        </div>
                    </div>
                </div>

                <!-- All Borrowers -->
                <div id="all-borrowers" class="tab-pane fade">
                    <div class="card">
                        <div class="card-body">
                            <?php
                            $stmt = $conn->prepare("
                                SELECT b.id, b.firstname, b.lastname, COALESCE(b.middlename, '') as middlename, b.email, b.contact_no,
                                    COUNT(bd.id) as total_docs,
                                    SUM(CASE WHEN bd.status = 0 THEN 1 ELSE 0 END) as pending_docs,
                                    SUM(CASE WHEN bd.status = 1 THEN 1 ELSE 0 END) as verified_docs,
                                    SUM(CASE WHEN bd.status = 2 THEN 1 ELSE 0 END) as rejected_docs
                                FROM borrowers b
                                LEFT JOIN borrower_documents bd ON b.id = bd.borrower_id
                                GROUP BY b.id
                                ORDER BY b.lastname ASC
                            ");
                            $stmt->execute();
                            $borrowers = $stmt->get_result();
                            $borrowers_count = $borrowers->num_rows;
                            ?>

                            <div class="row">
                                <?php while($borrower = $borrowers->fetch_assoc()): ?>
                                <div class="col-md-6 col-lg-4">
                                    <div class="borrower-card">
                                        <h6><?php echo $borrower['lastname'] . ', ' . $borrower['firstname'] ?></h6>
                                        <p class="mb-2">
                                            <small class="text-muted">
                                                <i class="fa fa-envelope"></i> <?php echo $borrower['email'] ?><br>
                                                <i class="fa fa-phone"></i> <?php echo $borrower['contact_no'] ?>
                                            </small>
                                        </p>
                                        <div class="mb-3">
                                            <span class="stat-badge badge badge-secondary">
                                                Total: <?php echo $borrower['total_docs'] ?>
                                            </span>
                                            <span class="stat-badge badge badge-warning">
                                                Pending: <?php echo $borrower['pending_docs'] ?>
                                            </span>
                                            <span class="stat-badge badge badge-success">
                                                Verified: <?php echo $borrower['verified_docs'] ?>
                                            </span>
                                            <?php if($borrower['rejected_docs'] > 0): ?>
                                            <span class="stat-badge badge badge-danger">
                                                Rejected: <?php echo $borrower['rejected_docs'] ?>
                                            </span>
                                            <?php endif; ?>
                                        </div>
                                        <a href="admin.php?page=borrowers#borrower_<?php echo $borrower['id'] ?>" class="btn btn-sm btn-primary btn-block">
                                            <i class="fa fa-user"></i> View Profile
                                        </a>
                                    </div>
                                </div>
                                <?php endwhile; ?>
                            </div>
                            <?php $stmt->close(); ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Document Viewer Modal -->
<div class="modal fade doc-viewer-modal" id="docViewerModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="docViewerTitle">Document Viewer</h5>
                <button type="button" class="close" data-dismiss="modal">
                    <span>&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <div class="doc-viewer-content" id="docViewerContent">
                    <p class="text-muted">Loading document...</p>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<script>
// Update badge counts
document.getElementById('pending-count').textContent = <?php echo $pending_count ?>;
document.getElementById('verified-count').textContent = <?php echo $verified_count ?>;
document.getElementById('rejected-count').textContent = <?php echo $rejected_count ?>;
document.getElementById('borrowers-count').textContent = <?php echo $borrowers_count ?>;

// View document
function viewDocument(filePath, docName, borrowerName) {
    $('#docViewerModal').modal('show');
    $('#docViewerTitle').text(docName + ' - ' + borrowerName);

    const extension = filePath.split('.').pop().toLowerCase();
    let html = '';

    if(['jpg', 'jpeg', 'png', 'gif'].includes(extension)) {
        html = `<img src="${filePath}" alt="${docName}" class="img-fluid">`;
    } else if(extension === 'pdf') {
        html = `<embed src="${filePath}" type="application/pdf" width="100%" height="600px">`;
    } else {
        html = `<p class="text-muted">Preview not available. <a href="${filePath}" target="_blank" class="btn btn-primary">Download Document</a></p>`;
    }

    $('#docViewerContent').html(html);
}

// Verify document
function verifyDocument(docId) {
    if(!confirm('Verify this document as authentic and valid?')) return;

    start_load();
    $.ajax({
        url: 'ajax.php?action=update_document_status',
        method: 'POST',
        data: {
            document_id: docId,
            status: 1
        },
        success: function(resp) {
            end_load();
            if(resp == 1) {
                alert_toast('Document verified successfully', 'success');
                setTimeout(function() {
                    location.reload();
                }, 1500);
            } else {
                alert_toast('Error verifying document', 'error');
            }
        },
        error: function() {
            end_load();
            alert_toast('Server error occurred', 'error');
        }
    });
}

// Reject document
function rejectDocument(docId) {
    const reason = prompt('Enter reason for rejection:');
    if(!reason || reason.trim() === '') {
        alert_toast('Please provide a reason for rejection', 'warning');
        return;
    }

    start_load();
    $.ajax({
        url: 'ajax.php?action=update_document_status',
        method: 'POST',
        data: {
            document_id: docId,
            status: 2,
            verification_notes: reason
        },
        success: function(resp) {
            end_load();
            if(resp == 1) {
                alert_toast('Document rejected', 'success');
                setTimeout(function() {
                    location.reload();
                }, 1500);
            } else {
                alert_toast('Error rejecting document', 'error');
            }
        },
        error: function() {
            end_load();
            alert_toast('Server error occurred', 'error');
        }
    });
}
</script>
