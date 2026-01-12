<?php include 'db_connect.php' ?>

<div class="container-fluid">
    <div class="col-lg-12">
        <div class="card">
            <div class="card-header">
                <h4><b>Customer Documents</b></h4>
            </div>
            <div class="card-body">
                <table class="table table-bordered table-hover" id="documents-table">
                    <thead>
                        <tr>
                            <th class="text-center">#</th>
                            <th>Customer Name</th>
                            <th>Email</th>
                            <th>Document Type</th>
                            <th>Upload Date</th>
                            <th>Status</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                        $i = 1;
                        $documents = $conn->query("SELECT bd.*, CONCAT(b.firstname, ' ', b.lastname) as customer_name, b.email 
                                                   FROM borrower_documents bd 
                                                   INNER JOIN borrowers b ON bd.borrower_id = b.id 
                                                   ORDER BY bd.upload_date DESC");
                        while($row = $documents->fetch_assoc()):
                            $doc_type_labels = array(
                                'id' => 'Government ID',
                                'employment_proof' => 'Employment Proof',
                                'payslip' => 'Pay Slip'
                            );
                            
                            $status_class = $row['status'] == 0 ? 'warning' : ($row['status'] == 1 ? 'success' : 'danger');
                            $status_text = $row['status'] == 0 ? 'Pending' : ($row['status'] == 1 ? 'Verified' : 'Rejected');
                        ?>
                        <tr>
                            <td class="text-center"><?php echo $i++ ?></td>
                            <td><?php echo $row['customer_name'] ?></td>
                            <td><?php echo $row['email'] ?></td>
                            <td><?php echo $doc_type_labels[$row['document_type']] ?></td>
                            <td><?php echo date('M d, Y h:i A', strtotime($row['upload_date'])) ?></td>
                            <td class="text-center">
                                <span class="badge badge-<?php echo $status_class ?>">
                                    <?php echo $status_text ?>
                                </span>
                            </td>
                            <td class="text-center">
                                <button class="btn btn-sm btn-primary view-document" 
                                        data-id="<?php echo $row['id'] ?>"
                                        data-path="<?php echo $row['file_path'] ?>"
                                        data-name="<?php echo $row['file_name'] ?>"
                                        title="View Document">
                                    <i class="fa fa-eye"></i>
                                </button>
                                
                                <?php if($row['status'] == 0): ?>
                                <button class="btn btn-sm btn-success verify-document" 
                                        data-id="<?php echo $row['id'] ?>"
                                        title="Verify Document">
                                    <i class="fa fa-check"></i>
                                </button>
                                <button class="btn btn-sm btn-danger reject-document" 
                                        data-id="<?php echo $row['id'] ?>"
                                        title="Reject Document">
                                    <i class="fa fa-times"></i>
                                </button>
                                <?php endif; ?>
                                
                                <a href="<?php echo $row['file_path'] ?>" 
                                   class="btn btn-sm btn-info" 
                                   download
                                   title="Download Document">
                                    <i class="fa fa-download"></i>
                                </a>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Document Viewer Modal -->
<div class="modal fade" id="document-modal" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Document Viewer</h5>
                <button type="button" class="close" data-dismiss="modal">
                    <span>&times;</span>
                </button>
            </div>
            <div class="modal-body text-center">
                <div id="document-container"></div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<style>
    #document-container img {
        max-width: 100%;
        height: auto;
    }
    #document-container iframe {
        width: 100%;
        height: 600px;
        border: none;
    }
</style>

<script>
$(document).ready(function(){
    // Initialize DataTable
    $('#documents-table').DataTable({
        "order": [[ 4, "desc" ]]
    });
    
    // View Document
    $('.view-document').click(function(){
        var path = $(this).data('path');
        var filename = $(this).data('name');
        var ext = filename.split('.').pop().toLowerCase();
        
        var container = $('#document-container');
        container.html('');
        
        if(ext === 'pdf') {
            container.html('<iframe src="' + path + '"></iframe>');
        } else {
            container.html('<img src="' + path + '" alt="Document">');
        }
        
        $('#document-modal').modal('show');
    });
    
    // Verify Document
    $('.verify-document').click(function(){
        var id = $(this).data('id');
        
        if(confirm('Are you sure you want to verify this document?')) {
            start_load();
            $.ajax({
                url: 'ajax.php?action=update_document_status',
                method: 'POST',
                data: {id: id, status: 1},
                success: function(resp){
                    if(resp == 1){
                        alert_toast("Document verified successfully", 'success');
                        setTimeout(function(){
                            location.reload();
                        }, 1500);
                    }
                }
            });
        }
    });
    
    // Reject Document
    $('.reject-document').click(function(){
        var id = $(this).data('id');
        
        var reason = prompt('Please enter rejection reason:');
        if(reason) {
            start_load();
            $.ajax({
                url: 'ajax.php?action=update_document_status',
                method: 'POST',
                data: {id: id, status: 2, reason: reason},
                success: function(resp){
                    if(resp == 1){
                        alert_toast("Document rejected", 'success');
                        setTimeout(function(){
                            location.reload();
                        }, 1500);
                    }
                }
            });
        }
    });
});
</script>
