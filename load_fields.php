<?php include 'db_connect.php' ?>
<?php
//extract($_POST);
$id = $_POST['id'] ?? null;
$loan_id = $_POST['loan_id'] ?? null;

if($id){
	$stmt = $conn->prepare("SELECT * FROM payments where id = ?");
	$stmt->bind_param("i", $id);
	$stmt->execute();
	$qry = $stmt->get_result();
	if($qry->num_rows > 0) {
		foreach($qry->fetch_array() as $k => $val){
			$$k = $val;
		}
	}
	$stmt->close();
}

$stmt = $conn->prepare("SELECT l.*,concat(b.lastname,', ',b.firstname,' ',b.middlename)as name, b.contact_no, b.address from loan_list l inner join borrowers b on b.id = l.borrower_id where l.id = ?");
$stmt->bind_param("i", $loan_id);
$stmt->execute();
$loan = $stmt->get_result();
foreach($loan->fetch_array() as $k => $v){
	$meta[$k] = $v;
}
$stmt->close();

$stmt = $conn->prepare("SELECT * FROM loan_types where id = ?");
$stmt->bind_param("i", $meta['loan_type_id']);
$stmt->execute();
$type_arr = $stmt->fetch_array();
$stmt->close();

$stmt = $conn->prepare("SELECT *,concat(months,' month/s [ ',interest_percentage,'%, ',penalty_rate,' ]') as plan FROM loan_plan where id = ?");
$stmt->bind_param("i", $meta['plan_id']);
$stmt->execute();
$plan_arr = $stmt->fetch_array();
$stmt->close();

// Calculate monthly payment correctly using annual interest rate
$annual_rate = $plan_arr['interest_percentage'];
$months = $plan_arr['months'];
$monthly_rate = $annual_rate / 12 / 100; // Convert annual to monthly rate

// Simple interest calculation (consistent with finance.php)
$total_interest = $meta['amount'] * $monthly_rate * $months;
$total_payable = $meta['amount'] + $total_interest;
$monthly = $total_payable / $months;

$penalty = $monthly * ($plan_arr['penalty_rate']/100);

$stmt = $conn->prepare("SELECT * from payments where loan_id = ?");
$stmt->bind_param("i", $loan_id);
$stmt->execute();
$payments = $stmt->get_result();
$paid = $payments->num_rows;

// Get next schedule date
$stmt2 = $conn->prepare("SELECT * FROM loan_schedules where loan_id = ? order by date(date_due) asc limit 1 offset ?");
$offset = $paid > 0 ? $paid : 0;
$stmt2->bind_param("ii", $loan_id, $offset);
$stmt2->execute();
$next = $stmt2->get_result()->fetch_assoc()['date_due'];
$stmt2->close();

$sum_paid = 0;
while($p = $payments->fetch_assoc()){
	$sum_paid += ($p['amount'] - $p['penalty_amount']);
}
$stmt->close();

?>
<div class="col-lg-12">
<hr>
<div class="row">
	<div class="col-md-5">
		<div class="form-group">
			<label for="">Payee</label>
			<input name="payee" class="form-control" required="" value="<?php echo isset($payee) ? $payee : (isset($meta['name']) ? $meta['name'] : '') ?>">
		</div>
	</div>
	
</div>
<hr>
<div class="row">
	<div class="col-md-5">
		<p><small>Monthly amount:<b><?php echo number_format($monthly,2) ?></b></small></p>
		<p><small>Penalty :<b><?php echo $add = (date('Ymd',strtotime($next)) < date("Ymd") ) ?  $penalty : 0; ?></b></small></p>
		<p><small>Payable Amount :<b><?php echo number_format($monthly + $add,2) ?></b></small></p>
	</div>
	<div class="col-md-5">
		<div class="form-group">
			<label for="">Amount</label>
			<input type="number" name="amount" step="any" min="" class="form-control text-right" required="" value="<?php echo isset($amount) ? $amount : '' ?>">
			<input type="hidden" name="penalty_amount" value="<?php echo $add ?>">
			<input type="hidden" name="loan_id" value="<?php echo $_POST['loan_id'] ?>">
			<input type="hidden" name="overdue" value="<?php echo $add > 0 ? 1 : 0 ?>">
		</div>
	</div>
</div>
</div>