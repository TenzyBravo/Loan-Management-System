<?php include 'db_connect.php' ?>

<div class="container-fluid">
	<div class="col-lg-12">
		<div class="card">
			<div class="card-header bg-gradient-success">
				<h5 class="card-title text-white">
					<i class="fa fa-chart-line"></i> Investment Tracker Dashboard
				</h5>
			</div>
			<div class="card-body">
				
				<!-- Monthly Summary Cards -->
				<div class="row mb-4">
					<?php
					$current_month = date('Y-m');
					$monthly_stats = $conn->query("
						SELECT 
							DATE_FORMAT(FROM_UNIXTIME(date_created), '%M %Y') as month_name,
							COUNT(*) as total_loans,
							SUM(amount) as total_given,
							SUM(cash_interest) as total_interest,
							SUM(total_payable) as total_collect,
							SUM(balance_remaining) as outstanding
						FROM loan_list
						WHERE DATE_FORMAT(FROM_UNIXTIME(date_created), '%Y-%m') = '$current_month'
					")->fetch_assoc();
					?>
					
					<div class="col-md-3">
						<div class="card bg-primary text-white">
							<div class="card-body">
								<h6>Total Loans This Month</h6>
								<h3><?php echo $monthly_stats['total_loans'] ?? 0 ?></h3>
							</div>
						</div>
					</div>
					
					<div class="col-md-3">
						<div class="card bg-info text-white">
							<div class="card-body">
								<h6>Amount Given</h6>
								<h3>K <?php echo number_format($monthly_stats['total_given'] ?? 0, 2) ?></h3>
							</div>
						</div>
					</div>
					
					<div class="col-md-3">
						<div class="card bg-success text-white">
							<div class="card-body">
								<h6>Cash Interest</h6>
								<h3>K <?php echo number_format($monthly_stats['total_interest'] ?? 0, 2) ?></h3>
							</div>
						</div>
					</div>
					
					<div class="col-md-3">
						<div class="card bg-warning text-white">
							<div class="card-body">
								<h6>Outstanding Balance</h6>
								<h3>K <?php echo number_format($monthly_stats['outstanding'] ?? 0, 2) ?></h3>
							</div>
						</div>
					</div>
				</div>

				<!-- Filter by Month -->
				<div class="row mb-3">
					<div class="col-md-6">
						<div class="input-group">
							<div class="input-group-prepend">
								<span class="input-group-text"><i class="fa fa-calendar"></i></span>
							</div>
							<input type="month" class="form-control" id="filter_month" value="<?php echo date('Y-m') ?>">
							<div class="input-group-append">
								<button class="btn btn-primary" onclick="filterByMonth()">Filter</button>
								<button class="btn btn-secondary" onclick="exportToExcel()"><i class="fa fa-file-excel"></i> Export</button>
							</div>
						</div>
					</div>
				</div>

				<!-- Investment Tracker Table (Excel-like) -->
				<div class="table-responsive">
					<table class="table table-bordered table-hover" id="investment-tracker">
						<thead class="thead-dark">
							<tr>
								<th>S/N</th>
								<th>Client Name</th>
								<th>Guarantor</th>
								<th>Contact Number</th>
								<th>Amount Given</th>
								<th>Date</th>
								<th>Interest (%)</th>
								<th>Cash Interest</th>
								<th>Total</th>
								<th>Total Paid</th>
								<th>Balance</th>
								<th>Status</th>
								<th>Action</th>
							</tr>
						</thead>
						<tbody>
							<?php
							$i = 1;
							$filter_month = isset($_GET['month']) ? $_GET['month'] : date('Y-m');
							
							$qry = $conn->query("
								SELECT 
									l.*,
									CONCAT(b.lastname, ', ', b.firstname, ' ', b.middlename) as client_name,
									b.guarantor_name,
									b.contact_no,
									lp.interest_percentage,
									lp.calculation_type,
									COALESCE((SELECT SUM(amount) FROM payments WHERE loan_id = l.id), 0) as total_paid
								FROM loan_list l
								INNER JOIN borrowers b ON l.borrower_id = b.id
								LEFT JOIN loan_plan lp ON l.plan_id = lp.id
								WHERE DATE_FORMAT(FROM_UNIXTIME(l.date_created), '%Y-%m') = '$filter_month'
								ORDER BY l.date_created DESC
							");
							
							$total_given = 0;
							$total_interest = 0;
							$total_collect = 0;
							$total_paid_sum = 0;
							$total_balance = 0;
							
							while($row = $qry->fetch_assoc()):
								$cash_interest = $row['cash_interest'];
								$total_payable = $row['total_payable'];
								$balance = $total_payable - $row['total_paid'];
								
								$total_given += $row['amount'];
								$total_interest += $cash_interest;
								$total_collect += $total_payable;
								$total_paid_sum += $row['total_paid'];
								$total_balance += $balance;
								
								// Status badge
								$status_class = '';
								$status_text = '';
								if($balance <= 0) {
									$status_class = 'success';
									$status_text = 'Paid';
								} elseif($row['total_paid'] > 0) {
									$status_class = 'warning';
									$status_text = 'Partial';
								} else {
									$status_class = 'danger';
									$status_text = 'Unpaid';
								}
							?>
							<tr>
								<td><?php echo $i++ ?></td>
								<td><b><?php echo $row['client_name'] ?></b></td>
								<td><?php echo $row['guarantor_name'] ?? 'N/A' ?></td>
								<td><?php echo $row['contact_no'] ?></td>
								<td class="text-right">K <?php echo number_format($row['amount'], 2) ?></td>
								<td><?php echo date('d-m-Y', $row['date_created']) ?></td>
								<td class="text-center"><?php echo $row['interest_percentage'] ?>%</td>
								<td class="text-right">K <?php echo number_format($cash_interest, 2) ?></td>
								<td class="text-right"><b>K <?php echo number_format($total_payable, 2) ?></b></td>
								<td class="text-right text-success">K <?php echo number_format($row['total_paid'], 2) ?></td>
								<td class="text-right text-danger"><b>K <?php echo number_format($balance, 2) ?></b></td>
								<td class="text-center">
									<span class="badge badge-<?php echo $status_class ?>"><?php echo $status_text ?></span>
								</td>
								<td class="text-center">
									<button class="btn btn-sm btn-primary view-details" data-id="<?php echo $row['id'] ?>">
										<i class="fa fa-eye"></i>
									</button>
									<button class="btn btn-sm btn-success add-payment" data-id="<?php echo $row['id'] ?>">
										<i class="fa fa-money-bill"></i>
									</button>
								</td>
							</tr>
							<?php endwhile; ?>
						</tbody>
						<tfoot class="thead-light">
							<tr>
								<th colspan="4" class="text-right">TOTALS:</th>
								<th class="text-right">K <?php echo number_format($total_given, 2) ?></th>
								<th></th>
								<th></th>
								<th class="text-right">K <?php echo number_format($total_interest, 2) ?></th>
								<th class="text-right">K <?php echo number_format($total_collect, 2) ?></th>
								<th class="text-right text-success">K <?php echo number_format($total_paid_sum, 2) ?></th>
								<th class="text-right text-danger">K <?php echo number_format($total_balance, 2) ?></th>
								<th colspan="2"></th>
							</tr>
						</tfoot>
					</table>
				</div>

				<!-- Monthly Trend Chart -->
				<div class="row mt-4">
					<div class="col-md-12">
						<div class="card">
							<div class="card-header">
								<h6>6-Month Lending Trend</h6>
							</div>
							<div class="card-body">
								<canvas id="lendingChart" height="80"></canvas>
							</div>
						</div>
					</div>
				</div>

			</div>
		</div>
	</div>
</div>

<style>
	.table td {
		vertical-align: middle;
		font-size: 0.9rem;
	}
	.table th {
		font-size: 0.85rem;
	}
</style>

<script src="https://cdn.jsdelivr.net/npm/chart.js@3.9.1/dist/chart.min.js"></script>
<script>
	// Initialize DataTable
	$('#investment-tracker').DataTable({
		order: [[5, 'desc']], // Sort by date
		pageLength: 50,
		dom: 'Bfrtip',
		buttons: ['copy', 'csv', 'excel', 'pdf', 'print']
	});

	// Filter by month
	function filterByMonth() {
		var month = $('#filter_month').val();
		window.location.href = 'index.php?page=investment_tracker&month=' + month;
	}

	// View loan details
	$('.view-details').click(function(){
		var id = $(this).data('id');
		uni_modal("Loan Details", "view_loan_details.php?id=" + id, 'large');
	});

	// Add payment
	$('.add-payment').click(function(){
		var id = $(this).data('id');
		uni_modal("Add Payment", "add_payment.php?loan_id=" + id, 'mid-large');
	});

	// Export to Excel
	function exportToExcel() {
		window.location.href = 'export_investment_tracker.php?month=' + $('#filter_month').val();
	}

	// Load monthly trend chart
	<?php
	$trend_data = $conn->query("
		SELECT 
			DATE_FORMAT(FROM_UNIXTIME(date_created), '%b %Y') as month,
			SUM(amount) as total_given,
			SUM(cash_interest) as total_interest,
			SUM(balance_remaining) as outstanding
		FROM loan_list
		WHERE date_created >= UNIX_TIMESTAMP(DATE_SUB(NOW(), INTERVAL 6 MONTH))
		GROUP BY DATE_FORMAT(FROM_UNIXTIME(date_created), '%Y-%m')
		ORDER BY date_created ASC
	");
	
	$months = [];
	$given = [];
	$interest = [];
	$outstanding = [];
	
	while($row = $trend_data->fetch_assoc()) {
		$months[] = $row['month'];
		$given[] = $row['total_given'];
		$interest[] = $row['total_interest'];
		$outstanding[] = $row['outstanding'];
	}
	?>

	const ctx = document.getElementById('lendingChart').getContext('2d');
	const lendingChart = new Chart(ctx, {
		type: 'line',
		data: {
			labels: <?php echo json_encode($months) ?>,
			datasets: [{
				label: 'Amount Given',
				data: <?php echo json_encode($given) ?>,
				borderColor: 'rgb(54, 162, 235)',
				backgroundColor: 'rgba(54, 162, 235, 0.1)',
				tension: 0.1
			}, {
				label: 'Cash Interest',
				data: <?php echo json_encode($interest) ?>,
				borderColor: 'rgb(75, 192, 192)',
				backgroundColor: 'rgba(75, 192, 192, 0.1)',
				tension: 0.1
			}, {
				label: 'Outstanding',
				data: <?php echo json_encode($outstanding) ?>,
				borderColor: 'rgb(255, 99, 132)',
				backgroundColor: 'rgba(255, 99, 132, 0.1)',
				tension: 0.1
			}]
		},
		options: {
			responsive: true,
			plugins: {
				legend: {
					position: 'top',
				}
			},
			scales: {
				y: {
					beginAtZero: true
				}
			}
		}
	});
</script>
