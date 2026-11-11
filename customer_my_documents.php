<?php
session_start();
if(!isset($_SESSION['customer_id'])){
    header('location: customer_login.php');
    exit;
}

include('db_connect.php');

$customer_id = $_SESSION['customer_id'];

// Get all documents for this customer
$docs_query = "SELECT * FROM borrower_documents
               WHERE borrower_id = $customer_id
               ORDER BY upload_date DESC";
$docs_result = $conn->query($docs_query);

require_once __DIR__ . '/includes/portal_layout.php';

$page_head = <<<'HTML'
<style>
    .doc-row-head { display: flex; align-items: flex-start; gap: 14px; }
    .doc-row-icon {
        flex: 0 0 44px; width: 44px; height: 44px; border-radius: 12px;
        display: flex; align-items: center; justify-content: center;
        background: var(--brand-50); color: var(--brand); font-size: 1.3rem;
    }
    .doc-row-main { flex: 1; min-width: 0; }
    .doc-row-type { font-weight: 600; font-size: 1rem; margin: 0; }
    .doc-row-file { font-size: .82rem; color: var(--muted); overflow-wrap: anywhere; }
    .doc-row-meta { display: flex; flex-wrap: wrap; gap: 4px 18px; font-size: .84rem; color: var(--muted); margin: 12px 0 0; padding-top: 12px; border-top: 1px solid var(--line); }
    .doc-row-meta strong { color: var(--ink-2); font-weight: 500; }
    .doc-row-actions { display: flex; flex-wrap: wrap; gap: 10px; margin-top: 14px; }
    .doc-row-actions .btn { flex: 1 1 auto; }
    @media (min-width: 576px) { .doc-row-actions .btn { flex: 0 0 auto; } }
    .doc-required { margin: 0; padding-left: 18px; font-size: .88rem; color: var(--ink-2); }
    .doc-required li { margin-bottom: 4px; }
</style>
HTML;

portal_header('My Documents', 'documents', ['head' => $page_head]);
?>

        <!-- Page Header -->
        <div class="page-head">
            <div>
                <h1>My Documents</h1>
                <p>Upload and manage your required documents</p>
            </div>
            <a href="#upload-document" class="btn btn-gold">
                <i class="fas fa-cloud-upload-alt"></i> Upload New Document
            </a>
        </div>

        <!-- Success Message -->
        <?php if(isset($_SESSION['success_msg'])): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="fas fa-check-circle"></i> <?php echo $_SESSION['success_msg']; ?>
            <button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button>
        </div>
        <?php unset($_SESSION['success_msg']); endif; ?>

        <!-- Error Message -->
        <?php if(isset($_SESSION['error_msg'])): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="fas fa-exclamation-circle"></i> <?php echo $_SESSION['error_msg']; ?>
            <button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button>
        </div>
        <?php unset($_SESSION['error_msg']); endif; ?>

        <!-- Documents List -->
        <h2 class="p-card-title"><i class="fas fa-folder-open"></i> Uploaded Documents</h2>

        <?php if($docs_result && $docs_result->num_rows > 0): ?>
            <?php while($doc = $docs_result->fetch_assoc()):
                // Format document type
                $type_labels = [
                    'id' => 'National ID / Passport',
                    'employment_proof' => 'Employment Proof',
                    'payslip' => 'Recent Pay Slip'
                ];
                $type_display = $type_labels[$doc['document_type']] ?? ucfirst(str_replace('_', ' ', $doc['document_type']));

                // Status display
                $status_text = 'Pending Verification';
                $status_class = 'p-badge-pending';
                $status_icon = 'fa-clock';

                switch($doc['status']) {
                    case 1:
                        $status_text = 'Verified';
                        $status_class = 'p-badge-done';
                        $status_icon = 'fa-check-circle';
                        break;
                    case 2:
                        $status_text = 'Rejected';
                        $status_class = 'p-badge-denied';
                        $status_icon = 'fa-times-circle';
                        break;
                }

                // File size in KB/MB
                $file_size = $doc['file_size'];
                if($file_size < 1024) {
                    $size_display = $file_size . ' B';
                } elseif($file_size < 1048576) {
                    $size_display = round($file_size / 1024, 2) . ' KB';
                } else {
                    $size_display = round($file_size / 1048576, 2) . ' MB';
                }

                // File icon based on extension
                $ext = pathinfo($doc['file_name'], PATHINFO_EXTENSION);
                $file_icon = 'fa-file-alt';
                if(in_array($ext, ['jpg', 'jpeg', 'png'])) {
                    $file_icon = 'fa-file-image';
                } elseif($ext == 'pdf') {
                    $file_icon = 'fa-file-pdf';
                }
            ?>

            <div class="p-card">
                <div class="doc-row-head">
                    <span class="doc-row-icon"><i class="fas <?php echo $file_icon; ?>"></i></span>
                    <div class="doc-row-main">
                        <h3 class="doc-row-type"><?php echo htmlspecialchars($type_display); ?></h3>
                        <div class="doc-row-file"><?php echo htmlspecialchars($doc['file_name']); ?></div>
                    </div>
                    <span class="p-badge <?php echo $status_class; ?>"><?php echo $status_text; ?></span>
                </div>

                <div class="doc-row-meta">
                    <span><strong>Uploaded:</strong> <?php echo date('M d, Y h:i A', strtotime($doc['upload_date'])); ?></span>
                    <span><strong>File Size:</strong> <?php echo $size_display; ?></span>
                    <?php if($doc['status'] != 0 && $doc['verification_date']): ?>
                    <span><strong>Reviewed:</strong> <?php echo date('M d, Y', strtotime($doc['verification_date'])); ?></span>
                    <?php endif; ?>
                </div>

                <?php if($doc['status'] == 2 && !empty($doc['verification_notes'])): ?>
                <div class="alert alert-warning mt-3 mb-0">
                    <strong><i class="fas fa-exclamation-triangle"></i> Rejection Reason:</strong><br>
                    <?php echo htmlspecialchars($doc['verification_notes']); ?>
                    <br><small>Please upload a new document to replace this one.</small>
                </div>
                <?php endif; ?>

                <div class="doc-row-actions">
                    <?php if($doc['status'] == 0 || $doc['status'] == 2): ?>
                    <a href="?type=<?php echo urlencode($doc['document_type']); ?>#upload-document"
                       class="btn btn-gold btn-lg">
                        <i class="fas fa-sync-alt"></i> Replace
                    </a>
                    <?php endif; ?>

                    <a href="<?php echo htmlspecialchars($doc['file_path']); ?>" target="_blank" rel="noopener" class="btn btn-outline-primary">
                        <i class="fas fa-eye"></i> View
                    </a>

                    <a href="<?php echo htmlspecialchars($doc['file_path']); ?>" download class="btn btn-outline-secondary">
                        <i class="fas fa-download"></i> Download
                    </a>
                </div>
            </div>

            <?php endwhile; ?>

        <?php else: ?>

            <!-- Empty State -->
            <div class="p-card">
                <div class="p-empty">
                    <i class="fas fa-file-upload"></i>
                    <h4>No documents uploaded yet</h4>
                    <p class="mb-0">Use the form below to upload your required documents</p>
                </div>
            </div>

        <?php endif; ?>

        <!-- Upload New Document -->
        <div class="p-card" id="upload-document">
            <h2 class="p-card-title">
                <i class="fas fa-cloud-upload-alt"></i> Upload New Document
            </h2>

            <div class="alert alert-info">
                <strong><i class="fas fa-info-circle"></i> Required Documents:</strong>
                <ul class="doc-required mt-2">
                    <li><strong>National ID / Passport</strong> - Government-issued identification</li>
                    <li><strong>Employment Proof</strong> - Employment letter or contract</li>
                    <li><strong>Recent Pay Slip</strong> - Last 3 months payslip</li>
                </ul>
            </div>

            <form action="customer_update_document.php" method="POST" enctype="multipart/form-data">
                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label class="form-label req" for="upload-document-type">Document Type</label>
                            <select class="custom-select" id="upload-document-type" name="document_type" required>
                                <?php
                                // "Replace" links arrive with ?type=... so the right type is pre-selected
                                $preselect_type = $_GET['type'] ?? '';
                                $upload_types = ['id' => 'National ID / Passport', 'employment_proof' => 'Employment Proof', 'payslip' => 'Recent Pay Slip'];
                                ?>
                                <option value="">Select Document Type</option>
                                <?php foreach ($upload_types as $type_value => $type_label): ?>
                                <option value="<?php echo $type_value; ?>"<?php echo $preselect_type === $type_value ? ' selected' : ''; ?>><?php echo $type_label; ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>

                    <div class="col-md-6">
                        <div class="form-group">
                            <label class="form-label req" for="upload-document-file">Choose File</label>
                            <input type="file" class="form-control" id="upload-document-file" name="document"
                                   accept=".pdf,.jpg,.jpeg,.png" required>
                            <small class="form-text text-muted">Accepted: PDF, JPG, PNG (Max 5MB)</small>
                        </div>
                    </div>
                </div>

                <button type="submit" class="btn btn-primary btn-lg btn-block">
                    <i class="fas fa-upload"></i> Upload
                </button>
            </form>
        </div>

<?php portal_footer(); ?>
