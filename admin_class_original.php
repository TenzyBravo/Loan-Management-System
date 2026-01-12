<?php
session_start();
ini_set('display_errors', 1);
Class Action {
	private $db;

	public function __construct() {
		ob_start();
   	include 'db_connect.php';
    
    $this->db = $conn;
	}
	function __destruct() {
	    $this->db->close();
	    ob_end_flush();
	}

	function login(){
		extract($_POST);
		$qry = $this->db->query("SELECT * FROM users where username = '".$username."' and password = '".$password."' ");
		if($qry->num_rows > 0){
			foreach ($qry->fetch_array() as $key => $value) {
				if($key != 'passwors' && !is_numeric($key))
					$_SESSION['login_'.$key] = $value;
			}
				return 1;
		}else{
			return 3;
		}
	}
	function login2(){
		extract($_POST);
		$qry = $this->db->query("SELECT * FROM users where username = '".$email."' and password = '".md5($password)."' ");
		if($qry->num_rows > 0){
			foreach ($qry->fetch_array() as $key => $value) {
				if($key != 'passwors' && !is_numeric($key))
					$_SESSION['login_'.$key] = $value;
			}
				return 1;
		}else{
			return 3;
		}
	}
	function logout(){
		session_destroy();
		foreach ($_SESSION as $key => $value) {
			unset($_SESSION[$key]);
		}
		header("location:login.php");
	}
	function logout2(){
		session_destroy();
		foreach ($_SESSION as $key => $value) {
			unset($_SESSION[$key]);
		}
		header("location:../index.php");
	}

	function save_user(){
		extract($_POST);
		$data = " name = '$name' ";
		$data .= ", username = '$username' ";
		$data .= ", password = '$password' ";
		$data .= ", type = '$type' ";
		if(empty($id)){
			$save = $this->db->query("INSERT INTO users set ".$data);
		}else{
			$save = $this->db->query("UPDATE users set ".$data." where id = ".$id);
		}
		if($save){
			return 1;
		}
	}
	function signup(){
		extract($_POST);
		$data = " name = '$name' ";
		$data .= ", contact = '$contact' ";
		$data .= ", address = '$address' ";
		$data .= ", username = '$email' ";
		$data .= ", password = '".md5($password)."' ";
		$data .= ", type = 3";
		$chk = $this->db->query("SELECT * FROM users where username = '$email' ")->num_rows;
		if($chk > 0){
			return 2;
			exit;
		}
			$save = $this->db->query("INSERT INTO users set ".$data);
		if($save){
			$qry = $this->db->query("SELECT * FROM users where username = '".$email."' and password = '".md5($password)."' ");
			if($qry->num_rows > 0){
				foreach ($qry->fetch_array() as $key => $value) {
					if($key != 'passwors' && !is_numeric($key))
						$_SESSION['login_'.$key] = $value;
				}
			}
			return 1;
		}
	}

	function save_settings(){
		extract($_POST);
		$data = " name = '".str_replace("'","&#x2019;",$name)."' ";
		$data .= ", email = '$email' ";
		$data .= ", contact = '$contact' ";
		$data .= ", about_content = '".htmlentities(str_replace("'","&#x2019;",$about))."' ";
		if($_FILES['img']['tmp_name'] != ''){
						$fname = strtotime(date('y-m-d H:i')).'_'.$_FILES['img']['name'];
						$move = move_uploaded_file($_FILES['img']['tmp_name'],'../assets/img/'. $fname);
					$data .= ", cover_img = '$fname' ";

		}
		
		// echo "INSERT INTO system_settings set ".$data;
		$chk = $this->db->query("SELECT * FROM system_settings");
		if($chk->num_rows > 0){
			$save = $this->db->query("UPDATE system_settings set ".$data);
		}else{
			$save = $this->db->query("INSERT INTO system_settings set ".$data);
		}
		if($save){
		$query = $this->db->query("SELECT * FROM system_settings limit 1")->fetch_array();
		foreach ($query as $key => $value) {
			if(!is_numeric($key))
				$_SESSION['setting_'.$key] = $value;
		}

			return 1;
				}
	}

	
	function save_loan_type(){
		extract($_POST);
		$data = " type_name = '$type_name' ";
		$data .= " , description = '$description' ";
		if(empty($id)){
			$save = $this->db->query("INSERT INTO loan_types set ".$data);
		}else{
			$save = $this->db->query("UPDATE loan_types set ".$data." where id=".$id);
		}
		if($save)
			return 1;
	}
	function delete_loan_type(){
		extract($_POST);
		$delete = $this->db->query("DELETE FROM loan_types where id = ".$id);
		if($delete)
			return 1;
	}
	function save_plan(){
		extract($_POST);
		$data = " months = '$months' ";
		$data .= ", interest_percentage = '$interest_percentage' ";
		$data .= ", penalty_rate = '$penalty_rate' ";
		
		if(empty($id)){
			$save = $this->db->query("INSERT INTO loan_plan set ".$data);
		}else{
			$save = $this->db->query("UPDATE loan_plan set ".$data." where id=".$id);
		}
		if($save)
			return 1;
	}
	function delete_plan(){
		extract($_POST);
		$delete = $this->db->query("DELETE FROM loan_plan where id = ".$id);
		if($delete)
			return 1;
	}
	function save_borrower(){
		extract($_POST);
		$data = " lastname = '$lastname' ";
		$data .= ", firstname = '$firstname' ";
		$data .= ", middlename = '$middlename' ";
		$data .= ", address = '$address' ";
		$data .= ", contact_no = '$contact_no' ";
		$data .= ", email = '$email' ";
		$data .= ", tax_id = '$tax_id' ";
		
		if(empty($id)){
			$save = $this->db->query("INSERT INTO borrowers set ".$data);
		}else{
			$save = $this->db->query("UPDATE borrowers set ".$data." where id=".$id);
		}
		if($save)
			return 1;
	}
	function delete_borrower(){
		extract($_POST);
		$delete = $this->db->query("DELETE FROM borrowers where id = ".$id);
		if($delete)
			return 1;
	}
	function save_loan(){
		extract($_POST);
			$data = " borrower_id = $borrower_id ";
			$data .= " , loan_type_id = '$loan_type_id' ";
			$data .= " , plan_id = '$plan_id' ";
			$data .= " , amount = '$amount' ";
			$data .= " , purpose = '$purpose' ";
			if(isset($status)){
				$data .= " , status = '$status' ";
				if($status == 2){
					$plan = $this->db->query("SELECT * FROM loan_plan where id = $plan_id ")->fetch_array();
					for($i= 1; $i <= $plan['months'];$i++){
						$date = date("Y-m-d",strtotime(date("Y-m-d")." +".$i." months"));
					$chk = $this->db->query("SELECT * FROM loan_schedules where loan_id = $id and date(date_due) ='$date'  ");
					if($chk->num_rows > 0){
						$ls_id = $chk->fetch_array()['id'];
						$this->db->query("UPDATE loan_schedules set loan_id = $id, date_due ='$date' where id = $ls_id ");
					}else{
						$this->db->query("INSERT INTO loan_schedules set loan_id = $id, date_due ='$date' ");
						$ls_id = $this->db->insert_id;
					}
					$sid[] = $ls_id;
					}
					$sid = implode(",",$sid);
					$this->db->query("DELETE FROM loan_schedules where loan_id = $id and id not in ($sid) ");
				$data .= " , date_released = '".date("Y-m-d H:i")."' ";

				}else{
					$chk = $this->db->query("SELECT * FROM loan_schedules where loan_id = $id")->num_rows;
					if($chk > 0){
						$thi->db->query("DELETE FROM loan_schedules where loan_id = $id ");
					}

				}
			}
			if(empty($id)){
				$ref_no = mt_rand(1,99999999);
				$i= 1;

				while($i== 1){
					$check = $this->db->query("SELECT * FROM loan_list where ref_no ='$ref_no' ")->num_rows;
					if($check > 0){
					$ref_no = mt_rand(1,99999999);
					}else{
						$i = 0;
					}
				}
				$data .= " , ref_no = '$ref_no' ";
			}
			if(empty($id))
			$save = $this->db->query("INSERT INTO loan_list set ".$data);
			else
			$save = $this->db->query("UPDATE loan_list set ".$data." where id=".$id);
		if($save)
			return 1;
	}
	function delete_loan(){
		extract($_POST);
		$delete = $this->db->query("DELETE FROM loan_list where id = ".$id);
		if($delete)
			return 1;
	}
	function save_payment(){
		extract($_POST);
			$data = " loan_id = $loan_id ";
			$data .= " , payee = '$payee' ";
			$data .= " , amount = '$amount' ";
			$data .= " , penalty_amount = '$penalty_amount' ";
			$data .= " , overdue = '$overdue' ";
		if(empty($id)){
			$save = $this->db->query("INSERT INTO payments set ".$data);
		}else{
			$save = $this->db->query("UPDATE payments set ".$data." where id = ".$id);

		}
		if($save)
			return 1;

	}
	function delete_payment(){
		extract($_POST);
		$delete = $this->db->query("DELETE FROM payments where id = ".$id);
		if($delete)
			return 1;
	}

	// ========== CUSTOMER PORTAL FUNCTIONS ==========
	
	function update_document_status(){
		extract($_POST);
		
		$data = " status = '$status' ";
		
		$update = $this->db->query("UPDATE borrower_documents SET $data WHERE id = $id");
		
		if($update){
			// Send notification to customer
			$doc = $this->db->query("SELECT borrower_id, document_type FROM borrower_documents WHERE id = $id")->fetch_array();
			$borrower_id = $doc['borrower_id'];
			$doc_type = $doc['document_type'];
			
			$doc_type_labels = array(
				'id' => 'Government ID',
				'employment_proof' => 'Employment Proof',
				'payslip' => 'Pay Slip'
			);
			
			if($status == 1) {
				$title = "Document Verified";
				$message = "Your " . $doc_type_labels[$doc_type] . " has been verified successfully.";
				$type = "success";
			} else {
				$title = "Document Rejected";
				$message = "Your " . $doc_type_labels[$doc_type] . " was rejected. Reason: " . (isset($reason) ? $reason : 'Quality issues');
				$type = "error";
			}
			
			$this->db->query("INSERT INTO customer_notifications (borrower_id, title, message, type) 
							 VALUES ($borrower_id, '$title', '$message', '$type')");
			
			return 1;
		}
	}

	function get_loan_review_details(){
		extract($_POST);
		
		// Get loan details
		$loan = $this->db->query("SELECT l.*, CONCAT(b.firstname, ' ', b.middlename, ' ', b.lastname) as customer_name,
								  b.email, b.contact_no, b.address, b.tax_id,
								  lt.type_name, lt.description as type_desc,
								  lp.months, lp.interest_percentage, lp.penalty_rate,
								  u.name as reviewed_by_name
								  FROM loan_list l
								  INNER JOIN borrowers b ON l.borrower_id = b.id
								  LEFT JOIN loan_types lt ON l.loan_type_id = lt.id
								  LEFT JOIN loan_plan lp ON l.plan_id = lp.id
								  LEFT JOIN users u ON l.reviewed_by = u.id
								  WHERE l.id = $loan_id")->fetch_array();
		
		// Get documents
		$documents = $this->db->query("SELECT * FROM borrower_documents WHERE borrower_id = {$loan['borrower_id']}");
		
		// Get checklist
		$checklist = $this->db->query("SELECT * FROM loan_application_checklist WHERE loan_id = $loan_id");
		
		// Calculate loan details
		$principal = $loan['amount'];
		$interest = ($principal * $loan['interest_percentage']) / 100;
		$total = $principal + $interest;
		$monthly = $total / $loan['months'];
		
		$status_badges = array(
			0 => '<span class="badge badge-secondary">Draft</span>',
			1 => '<span class="badge badge-warning">Submitted</span>',
			2 => '<span class="badge badge-info">Under Review</span>',
			3 => '<span class="badge badge-success">Approved</span>',
			4 => '<span class="badge badge-danger">Denied</span>'
		);
		
		$doc_type_labels = array(
			'id' => 'Government ID',
			'employment_proof' => 'Employment Proof',
			'payslip' => 'Pay Slip'
		);
		
		ob_start();
		?>
		
		<div class="row">
			<!-- Left Column -->
			<div class="col-md-8">
				
				<!-- Application Status -->
				<div class="info-section">
					<h6><i class="fa fa-info-circle"></i> Application Status</h6>
					<div class="row">
						<div class="col-md-6">
							<div class="info-item">
								<label>Reference Number:</label>
								<p><b><?php echo $loan['ref_no'] ?></b></p>
							</div>
						</div>
						<div class="col-md-6">
							<div class="info-item">
								<label>Current Status:</label>
								<p><?php echo $status_badges[$loan['application_status']] ?></p>
							</div>
						</div>
					</div>
				</div>
				
				<!-- Customer Information -->
				<div class="info-section">
					<h6><i class="fa fa-user"></i> Customer Information</h6>
					<div class="row">
						<div class="col-md-6">
							<div class="info-item">
								<label>Full Name:</label>
								<p><?php echo $loan['customer_name'] ?></p>
							</div>
						</div>
						<div class="col-md-6">
							<div class="info-item">
								<label>Email:</label>
								<p><?php echo $loan['email'] ?></p>
							</div>
						</div>
					</div>
				</div>
				
				<!-- Loan Details -->
				<div class="info-section">
					<h6><i class="fa fa-money-bill-wave"></i> Loan Details</h6>
					<div class="row">
						<div class="col-md-6">
							<div class="info-item">
								<label>Loan Type:</label>
								<p><b><?php echo $loan['type_name'] ?></b></p>
							</div>
						</div>
						<div class="col-md-6">
							<div class="info-item">
								<label>Amount:</label>
								<p><b>$<?php echo number_format($principal, 2) ?></b></p>
							</div>
						</div>
					</div>
				</div>
				
				<!-- Calculation -->
				<div class="info-section" style="background: #fff3e0;">
					<h6><i class="fa fa-calculator"></i> Payment Plan</h6>
					<div class="row">
						<div class="col-md-3">
							<div class="info-item">
								<label>Principal:</label>
								<p>$<?php echo number_format($principal, 2) ?></p>
							</div>
						</div>
						<div class="col-md-3">
							<div class="info-item">
								<label>Interest:</label>
								<p>$<?php echo number_format($interest, 2) ?></p>
							</div>
						</div>
						<div class="col-md-3">
							<div class="info-item">
								<label>Total:</label>
								<p><b>$<?php echo number_format($total, 2) ?></b></p>
							</div>
						</div>
						<div class="col-md-3">
							<div class="info-item">
								<label>Monthly:</label>
								<p><b>$<?php echo number_format($monthly, 2) ?></b></p>
							</div>
						</div>
					</div>
				</div>
				
			</div>
			
			<!-- Right Column - Checklist -->
			<div class="col-md-4">
				<div class="card">
					<div class="card-header bg-info text-white">
						<h6 class="mb-0"><i class="fa fa-tasks"></i> Review Checklist</h6>
					</div>
					<div class="card-body p-0">
						<?php while($item = $checklist->fetch_assoc()): ?>
						<div class="checklist-item">
							<div class="custom-control custom-checkbox">
								<input type="checkbox" 
									   class="custom-control-input checklist-checkbox" 
									   id="check_<?php echo $item['id'] ?>"
									   data-id="<?php echo $item['id'] ?>"
									   <?php echo $item['checked'] ? 'checked' : '' ?>>
								<label class="custom-control-label" for="check_<?php echo $item['id'] ?>">
									<?php echo $item['item'] ?>
								</label>
							</div>
						</div>
						<?php endwhile; ?>
					</div>
				</div>
			</div>
		</div>
		
		<!-- Action Buttons -->
		<div class="action-buttons">
			<div class="text-right">
				<?php if($loan['application_status'] == 1): ?>
					<button class="btn btn-info update-status-btn" 
							data-loan-id="<?php echo $loan_id ?>" 
							data-status="2" 
							data-status-text="Move to Under Review">
						<i class="fa fa-search"></i> Move to Under Review
					</button>
				<?php endif; ?>
				
				<?php if($loan['application_status'] <= 2): ?>
					<button class="btn btn-success update-status-btn" 
							data-loan-id="<?php echo $loan_id ?>" 
							data-status="3" 
							data-status-text="Approve">
						<i class="fa fa-check"></i> Approve Application
					</button>
					<button class="btn btn-danger update-status-btn" 
							data-loan-id="<?php echo $loan_id ?>" 
							data-status="4" 
							data-status-text="Deny">
						<i class="fa fa-times"></i> Deny Application
					</button>
				<?php endif; ?>
				
				<button class="btn btn-secondary" data-dismiss="modal">
					<i class="fa fa-times"></i> Close
				</button>
			</div>
		</div>
		
		<?php
		return ob_get_clean();
	}

	function update_loan_application_status(){
		extract($_POST);
		
		$user_id = $_SESSION['login_id'];
		
		$data = " application_status = $status, 
				  reviewed_by = $user_id,
				  review_date = NOW()";
		
		if(isset($notes) && !empty($notes)) {
			$notes = $this->db->real_escape_string($notes);
			$data .= ", review_notes = '$notes'";
		}
		
		if(isset($denial_reason) && !empty($denial_reason)) {
			$denial_reason = $this->db->real_escape_string($denial_reason);
			$data .= ", denial_reason = '$denial_reason'";
		}
		
		if($status == 3) {
			$data .= ", status = 1";
		}
		
		$update = $this->db->query("UPDATE loan_list SET $data WHERE id = $loan_id");
		
		if($update){
			$loan = $this->db->query("SELECT ref_no, borrower_id FROM loan_list WHERE id = $loan_id")->fetch_array();
			$borrower_id = $loan['borrower_id'];
			$ref_no = $loan['ref_no'];
			
			$notif_messages = array(
				2 => array('title' => 'Under Review', 'message' => "Your application (Ref: $ref_no) is under review.", 'type' => 'info'),
				3 => array('title' => 'Approved!', 'message' => "Your application (Ref: $ref_no) has been approved!", 'type' => 'success'),
				4 => array('title' => 'Denied', 'message' => "Your application (Ref: $ref_no) was denied.", 'type' => 'error')
			);
			
			if(isset($notif_messages[$status])) {
				$notif = $notif_messages[$status];
				$this->db->query("INSERT INTO customer_notifications (borrower_id, title, message, type) 
								 VALUES ($borrower_id, '{$notif['title']}', '{$notif['message']}', '{$notif['type']}')");
			}
			
			return 1;
		}
	}

	function update_checklist_item(){
		extract($_POST);
		
		$user_id = $_SESSION['login_id'];
		
		if($checked == 1) {
			$data = "checked = 1, checked_by = $user_id, checked_date = NOW()";
		} else {
			$data = "checked = 0, checked_by = NULL, checked_date = NULL";
		}
		
		$update = $this->db->query("UPDATE loan_application_checklist SET $data WHERE id = $item_id");
		
		if($update){
			return 1;
		}
	}

}
?>