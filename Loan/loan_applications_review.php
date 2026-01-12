<?php include 'db_connect.php' ?>

<div class="container-fluid">
    <div class="col-lg-12">
        <div class="row">
            <!-- Loan Applications List -->
            <div class="col-md-12">
                <div class="card">
                    <div class="card-header">
                        <h4><b>Loan Applications for Review</b></h4>
                        <div class="card-tools">
                            <button class="btn btn-sm btn-flat btn-primary" id="filter-btn">
                                <i class="fa fa-filter"></i> Filter
                            </button>
                        </div>
                    </div>
                    <div class="card-body">
                        <!-- Filter Options -->
                        <div id="filter-section" style="display: none;" class="mb-3">
                            <div class="row">
                                <div class="col-md-3">
                                    <label>Status</label>
                                    <select class="form-control form-control-sm" id="filter-status">
                                        <option value="">All</option>
                                        <option value="1" selected>Submitted</option>
                                        <option value="2">Under Review</option>
                                        <option value="3">Approved</option>
                                        <option value="4">Denied</option>
                                    </select>
                                </div>
                                <div class="col-md-3">
                                    <label>Loan Type</label>
                                    <select class="form-control form-control-sm" id="filter-type">
                                        <option value="">All Types</option>
                                        <?php
                                        $types = $conn->query("SELECT * FROM loan_types");
                                        while($row = $types->fetch_assoc()):
                                        ?>
                                            <option value="<?php echo $row['id'] ?>"><?php echo $row['type_name'] ?></option>
                                        <?php endwhile; ?>
                                    </select>
                                </div>
                                <div class="col-md-3">
                                    <label>&nbsp;</label><br>
                                    <button class="btn btn-sm btn-primary" id="apply-filter">Apply</button>
                                    <button class="btn btn-sm btn-secondary" id="reset-filter">Reset</button>
                                </div>
                            </div>
                        </div>

                        <table class="table table-bordered table-hover" id="loans-table">
                            <thead>
                                <tr>
                                    <th class="text-center">#</th>
                                    <th>Ref No.</th>
                                    <th>Customer</th>
                                    <th>Loan Type</th>
                                    <th>Amount</th>
                                    <th>Plan</th>
                                    <th>Applied Date</th>
                                    <th>App. Status</th>
                                    <th>Documents</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php 
                                $i = 1;
                                $loans = $conn->query("SELECT l.*, CONCAT(b.firstname, ' ', b.lastname) as customer_name, 
                                                       b.email, lt.type_name, lp.months, lp.interest_percentage,
                                                       (SELECT COUNT(*) FROM borrower_documents WHERE borrower_id = l.borrower_id AND status = 1) as verified_docs,
                                                       (SELECT COUNT(*) FROM borrower_documents WHERE borrower_id = l.borrower_id) as total_docs
                                                       FROM loan_list l 
                                                       INNER JOIN borrowers b ON l.borrower_id = b.id 
                                                       LEFT JOIN loan_types lt ON l.loan_type_id = lt.id 
                                                       LEFT JOIN loan_plan lp ON l.plan_id = lp.id 
                                                       WHERE l.application_source = 'customer'
                                                       ORDER BY l.date_created DESC");
                                while($row = $loans->fetch_assoc()):
                                    // Status badge
                                    $app_status_class = '';
                                    $app_status_text = '';
                                    switch($row['application_status']) {
                                        case 0: $app_status_class = 'secondary'; $app_status_text = 'Draft'; break;
                                        case 1: $app_status_class = 'warning'; $app_status_text = 'Submitted'; break;
                                        case 2: $app_status_class = 'info'; $app_status_text = 'Under Review'; break;
                                        case 3: $app_status_class = 'success'; $app_status_text = 'Approved'; break;
                                        case 4: $app_status_class = 'danger'; $app_status_text = 'Denied'; break;
                                    }
                                    
                                    $doc_status_class = $row['verified_docs'] == $row['total_docs'] ? 'success' : 'warning';
                                ?>
                                <tr>
                                    <td class="text-center"><?php echo $i++ ?></td>
                                    <td><b><?php echo $row['ref_no'] ?></b></td>
                                    <td>
                                        <?php echo $row['customer_name'] ?><br>
                                        <small class="text-muted"><?php echo $row['email'] ?></small>
                                    </td>
                                    <td><?php echo $row['type_name'] ?></td>
                                    <td><b>$<?php echo number_format($row['amount'], 2) ?></b></td>
                                    <td><?php echo $row['months'] ?> months @ <?php echo $row['interest_percentage'] ?>%</td>
                                    <td><?php echo date('M d, Y', strtotime($row['date_created'])) ?></td>
                                    <td class="text-center">
                                        <span class="badge badge-<?php echo $app_status_class ?>">
                                            <?php echo $app_status_text ?>
                                        </span>
                                    </td>
                                    <td class="text-center">
                                        <span class="badge badge-<?php echo $doc_status_class ?>">
                                            <?php echo $row['verified_docs'] ?>/<?php echo $row['total_docs'] ?> Verified
                                        </span>
                                    </td>
                                    <td class="text-center">
                                        <button class="btn btn-sm btn-primary view-application" 
                                                data-id="<?php echo $row['id'] ?>"
                                                title="Review Application">
                                            <i class="fa fa-eye"></i> Review
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

<!-- Loan Review Modal -->
<div class="modal fade" id="review-modal" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-xl" role="document">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title"><i class="fa fa-file-contract"></i> Loan Application Review</h5>
                <button type="button" class="close text-white" data-dismiss="modal">
                    <span>&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <div id="review-content">
                    <div class="text-center py-5">
                        <i class="fa fa-spinner fa-spin fa-3x"></i>
                        <p class="mt-3">Loading application details...</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
    .info-section {
        background: #f8f9fa;
        padding: 15px;
        border-radius: 8px;
        margin-bottom: 20px;
    }
    .info-section h6 {
        color: #495057;
        font-weight: 600;
        margin-bottom: 15px;
        border-bottom: 2px solid #dee2e6;
        padding-bottom: 8px;
    }
    .info-item {
        margin-bottom: 10px;
    }
    .info-item label {
        font-weight: 600;
        color: #6c757d;
        margin-bottom: 5px;
    }
    .info-item p {
        margin: 0;
        color: #212529;
    }
    .document-preview {
        max-width: 100%;
        max-height: 300px;
        border: 1px solid #dee2e6;
        border-radius: 5px;
    }
    .checklist-item {
        padding: 10px;
        border-bottom: 1px solid #dee2e6;
    }
    .checklist-item:last-child {
        border-bottom: none;
    }
    .action-buttons {
        position: sticky;
        bottom: 0;
        background: white;
        padding: 15px;
        border-top: 2px solid #dee2e6;
        margin: -15px;
        margin-top: 20px;
    }
</style>

<script>
$(document).ready(function(){
    // Initialize DataTable
    var table = $('#loans-table').DataTable({
        "order": [[ 6, "desc" ]]
    });
    
    // Filter toggle
    $('#filter-btn').click(function(){
        $('#filter-section').slideToggle();
    });
    
    // Apply filter
    $('#apply-filter').click(function(){
        table.draw();
    });
    
    // Reset filter
    $('#reset-filter').click(function(){
        $('#filter-status').val('');
        $('#filter-type').val('');
        table.draw();
    });
    
    // Custom filtering function
    $.fn.dataTable.ext.search.push(
        function(settings, data, dataIndex) {
            var statusFilter = $('#filter-status').val();
            var typeFilter = $('#filter-type').val();
            
            var status = data[7]; // Application status column
            var type = data[3];  // Loan type column
            
            if (statusFilter && !status.includes(statusFilter)) {
                return false;
            }
            
            if (typeFilter && !type.includes(typeFilter)) {
                return false;
            }
            
            return true;
        }
    );
    
    // View Application
    $('.view-application').click(function(){
        var loan_id = $(this).data('id');
        
        $('#review-modal').modal('show');
        $('#review-content').html('<div class="text-center py-5"><i class="fa fa-spinner fa-spin fa-3x"></i><p class="mt-3">Loading...</p></div>');
        
        $.ajax({
            url: 'ajax.php?action=get_loan_review_details',
            method: 'POST',
            data: {loan_id: loan_id},
            success: function(resp){
                $('#review-content').html(resp);
            },
            error: function(){
                $('#review-content').html('<div class="alert alert-danger">Error loading application details</div>');
            }
        });
    });
});

// Checklist toggle
$(document).on('change', '.checklist-checkbox', function(){
    var item_id = $(this).data('id');
    var checked = $(this).is(':checked') ? 1 : 0;
    
    $.ajax({
        url: 'ajax.php?action=update_checklist_item',
        method: 'POST',
        data: {item_id: item_id, checked: checked},
        success: function(resp){
            if(resp != 1) {
                alert('Error updating checklist');
            }
        }
    });
});

// Update application status
$(document).on('click', '.update-status-btn', function(){
    var loan_id = $(this).data('loan-id');
    var new_status = $(this).data('status');
    var status_text = $(this).data('status-text');
    
    if(confirm('Are you sure you want to ' + status_text + ' this application?')) {
        var notes = prompt('Please enter review notes:');
        var denial_reason = '';
        
        if(new_status == 4) {
            denial_reason = prompt('Please enter denial reason:');
            if(!denial_reason) {
                alert('Denial reason is required');
                return;
            }
        }
        
        start_load();
        $.ajax({
            url: 'ajax.php?action=update_loan_application_status',
            method: 'POST',
            data: {
                loan_id: loan_id, 
                status: new_status, 
                notes: notes,
                denial_reason: denial_reason
            },
            success: function(resp){
                if(resp == 1){
                    alert_toast(status_text + " successfully", 'success');
                    setTimeout(function(){
                        location.reload();
                    }, 1500);
                } else {
                    alert_toast("Error updating status", 'error');
                    end_load();
                }
            }
        });
    }
});
</script>
