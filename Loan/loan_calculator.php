<?php include 'db_connect.php' ?>

<div class="container-fluid">
	<div class="col-lg-12">
		<div class="row">
			
			<!-- Left: Calculator -->
			<div class="col-md-6">
				<div class="card">
					<div class="card-header bg-gradient-primary text-white">
						<h5><i class="fa fa-calculator"></i> Loan Calculator</h5>
					</div>
					<div class="card-body">
						
						<!-- Calculation Type -->
						<div class="form-group">
							<label>Calculation Type</label>
							<div class="btn-group btn-group-toggle d-flex" data-toggle="buttons">
								<label class="btn btn-outline-primary active flex-fill">
									<input type="radio" name="calc_type" id="simple_interest" value="simple" checked> Simple Interest
								</label>
								<label class="btn btn-outline-primary flex-fill">
									<input type="radio" name="calc_type" id="compound_interest" value="compound"> Compound Interest
								</label>
							</div>
							<small class="form-text text-muted">
								<b>Simple:</b> Interest = Amount × Rate (like your Excel tracker)<br>
								<b>Compound:</b> Interest calculated monthly over time
							</small>
						</div>

						<!-- Amount -->
						<div class="form-group">
							<label>Loan Amount (K)</label>
							<input type="number" class="form-control form-control-lg" id="amount" value="500" min="100" step="100">
						</div>

						<!-- Interest Rate -->
						<div class="form-group">
							<label>Interest Rate (%)</label>
							<input type="number" class="form-control form-control-lg" id="interest_rate" value="30" min="1" max="100">
						</div>

						<!-- Months (for compound only) -->
						<div class="form-group" id="months_group">
							<label>Number of Months</label>
							<input type="number" class="form-control form-control-lg" id="months" value="1" min="1" max="36">
							<small class="text-muted">For compound interest calculations</small>
						</div>

						<button class="btn btn-success btn-lg btn-block" onclick="calculateLoan()">
							<i class="fa fa-calculator"></i> Calculate
						</button>

					</div>
				</div>

				<!-- Quick Presets -->
				<div class="card mt-3">
					<div class="card-header">
						<h6>Quick Presets</h6>
					</div>
					<div class="card-body">
						<div class="row">
							<div class="col-6">
								<button class="btn btn-sm btn-outline-primary btn-block mb-2" onclick="setPreset(500, 30, 'simple')">
									K500 @ 30%
								</button>
								<button class="btn btn-sm btn-outline-primary btn-block mb-2" onclick="setPreset(1000, 30, 'simple')">
									K1,000 @ 30%
								</button>
								<button class="btn btn-sm btn-outline-primary btn-block" onclick="setPreset(2000, 30, 'simple')">
									K2,000 @ 30%
								</button>
							</div>
							<div class="col-6">
								<button class="btn btn-sm btn-outline-info btn-block mb-2" onclick="setPreset(5000, 35, 'simple')">
									K5,000 @ 35%
								</button>
								<button class="btn btn-sm btn-outline-info btn-block mb-2" onclick="setPreset(10000, 40, 'simple')">
									K10,000 @ 40%
								</button>
								<button class="btn btn-sm btn-outline-info btn-block" onclick="setPreset(20000, 45, 'simple')">
									K20,000 @ 45%
								</button>
							</div>
						</div>
					</div>
				</div>
			</div>

			<!-- Right: Results -->
			<div class="col-md-6">
				<div class="card">
					<div class="card-header bg-gradient-success text-white">
						<h5><i class="fa fa-chart-pie"></i> Calculation Results</h5>
					</div>
					<div class="card-body">
						
						<div id="results_container" style="display: none;">
							
							<!-- Simple Interest Results -->
							<div id="simple_results">
								<div class="alert alert-info">
									<h6><i class="fa fa-info-circle"></i> Simple Interest Calculation</h6>
									<p class="mb-0">Interest = Amount Given × Interest Rate</p>
								</div>

								<table class="table table-bordered table-lg">
									<tr>
										<td><strong>Amount Given:</strong></td>
										<td class="text-right"><h5 id="simple_principal" class="mb-0"></h5></td>
									</tr>
									<tr>
										<td><strong>Interest Rate:</strong></td>
										<td class="text-right"><h5 id="simple_rate" class="mb-0"></h5></td>
									</tr>
									<tr class="table-warning">
										<td><strong>Cash Interest:</strong></td>
										<td class="text-right"><h5 id="simple_interest" class="mb-0"></h5></td>
									</tr>
									<tr class="table-success">
										<td><strong>Total to Collect:</strong></td>
										<td class="text-right"><h4 id="simple_total" class="mb-0"></h4></td>
									</tr>
								</table>

								<!-- Installment Suggestions -->
								<div class="card bg-light mt-3">
									<div class="card-body">
										<h6>Suggested Installments:</h6>
										<table class="table table-sm mb-0">
											<tr>
												<td>1 Installment:</td>
												<td class="text-right"><strong id="inst_1"></strong></td>
											</tr>
											<tr>
												<td>2 Installments:</td>
												<td class="text-right"><strong id="inst_2"></strong></td>
											</tr>
											<tr>
												<td>3 Installments:</td>
												<td class="text-right"><strong id="inst_3"></strong></td>
											</tr>
											<tr>
												<td>4 Installments:</td>
												<td class="text-right"><strong id="inst_4"></strong></td>
											</tr>
										</table>
									</div>
								</div>
							</div>

							<!-- Compound Interest Results -->
							<div id="compound_results" style="display: none;">
								<div class="alert alert-info">
									<h6><i class="fa fa-info-circle"></i> Compound Interest Calculation</h6>
									<p class="mb-0">Interest calculated monthly over the loan period</p>
								</div>

								<table class="table table-bordered table-lg">
									<tr>
										<td><strong>Loan Amount:</strong></td>
										<td class="text-right"><h5 id="compound_principal" class="mb-0"></h5></td>
									</tr>
									<tr>
										<td><strong>Interest Rate:</strong></td>
										<td class="text-right"><h5 id="compound_rate" class="mb-0"></h5></td>
									</tr>
									<tr>
										<td><strong>Loan Period:</strong></td>
										<td class="text-right"><h5 id="compound_months" class="mb-0"></h5></td>
									</tr>
									<tr class="table-warning">
										<td><strong>Total Interest:</strong></td>
										<td class="text-right"><h5 id="compound_interest" class="mb-0"></h5></td>
									</tr>
									<tr class="table-success">
										<td><strong>Total Payable:</strong></td>
										<td class="text-right"><h4 id="compound_total" class="mb-0"></h4></td>
									</tr>
									<tr class="table-primary">
										<td><strong>Monthly Payment:</strong></td>
										<td class="text-right"><h4 id="compound_monthly" class="mb-0"></h4></td>
									</tr>
								</table>
							</div>

							<!-- Visual Breakdown -->
							<div class="card mt-3">
								<div class="card-body">
									<canvas id="breakdownChart" height="200"></canvas>
								</div>
							</div>

						</div>

						<!-- Initial Message -->
						<div id="initial_message" class="text-center text-muted py-5">
							<i class="fa fa-calculator fa-3x mb-3"></i>
							<h5>Enter loan details and click Calculate</h5>
						</div>

					</div>
				</div>

				<!-- Comparison Table -->
				<div class="card mt-3" id="comparison_table" style="display: none;">
					<div class="card-header">
						<h6>Simple vs Compound Comparison</h6>
					</div>
					<div class="card-body">
						<table class="table table-sm">
							<thead>
								<tr>
									<th></th>
									<th class="text-center">Simple</th>
									<th class="text-center">Compound</th>
								</tr>
							</thead>
							<tbody>
								<tr>
									<td>Total Interest</td>
									<td class="text-center" id="comp_simple_int"></td>
									<td class="text-center" id="comp_compound_int"></td>
								</tr>
								<tr>
									<td>Total Payable</td>
									<td class="text-center" id="comp_simple_total"></td>
									<td class="text-center" id="comp_compound_total"></td>
								</tr>
								<tr>
									<td>Difference</td>
									<td colspan="2" class="text-center" id="comp_difference"></td>
								</tr>
							</tbody>
						</table>
					</div>
				</div>

			</div>

		</div>
	</div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js@3.9.1/dist/chart.min.js"></script>
<script>
let breakdownChart = null;

// Toggle months field
$('input[name="calc_type"]').change(function() {
	if($(this).val() === 'simple') {
		$('#months_group').slideUp();
	} else {
		$('#months_group').slideDown();
	}
});

// Set preset values
function setPreset(amount, rate, type) {
	$('#amount').val(amount);
	$('#interest_rate').val(rate);
	if(type === 'simple') {
		$('#simple_interest').prop('checked', true).parent().addClass('active');
		$('#compound_interest').prop('checked', false).parent().removeClass('active');
		$('#months_group').slideUp();
	}
	calculateLoan();
}

// Calculate loan
function calculateLoan() {
	const amount = parseFloat($('#amount').val());
	const rate = parseFloat($('#interest_rate').val());
	const months = parseInt($('#months').val());
	const type = $('input[name="calc_type"]:checked').val();

	if(!amount || !rate) {
		alert('Please enter amount and interest rate');
		return;
	}

	$('#initial_message').hide();
	$('#results_container').show();

	if(type === 'simple') {
		calculateSimple(amount, rate);
	} else {
		calculateCompound(amount, rate, months);
	}
}

// Simple Interest Calculation
function calculateSimple(amount, rate) {
	const interest = amount * (rate / 100);
	const total = amount + interest;

	$('#simple_results').show();
	$('#compound_results').hide();

	$('#simple_principal').text('K ' + amount.toLocaleString('en-US', {minimumFractionDigits: 2}));
	$('#simple_rate').text(rate + '%');
	$('#simple_interest').text('K ' + interest.toLocaleString('en-US', {minimumFractionDigits: 2}));
	$('#simple_total').text('K ' + total.toLocaleString('en-US', {minimumFractionDigits: 2}));

	// Installment suggestions
	$('#inst_1').text('K ' + total.toLocaleString('en-US', {minimumFractionDigits: 2}));
	$('#inst_2').text('K ' + (total / 2).toLocaleString('en-US', {minimumFractionDigits: 2}) + ' each');
	$('#inst_3').text('K ' + (total / 3).toLocaleString('en-US', {minimumFractionDigits: 2}) + ' each');
	$('#inst_4').text('K ' + (total / 4).toLocaleString('en-US', {minimumFractionDigits: 2}) + ' each');

	// Update chart
	updateChart('Simple Interest Breakdown', amount, interest);
}

// Compound Interest Calculation
function calculateCompound(amount, rate, months) {
	const monthlyRate = rate / 100 / 12;
	const totalInterest = amount * (rate / 100);
	const total = amount + totalInterest;
	const monthly = total / months;

	$('#simple_results').hide();
	$('#compound_results').show();

	$('#compound_principal').text('K ' + amount.toLocaleString('en-US', {minimumFractionDigits: 2}));
	$('#compound_rate').text(rate + '% per annum');
	$('#compound_months').text(months + ' months');
	$('#compound_interest').text('K ' + totalInterest.toLocaleString('en-US', {minimumFractionDigits: 2}));
	$('#compound_total').text('K ' + total.toLocaleString('en-US', {minimumFractionDigits: 2}));
	$('#compound_monthly').text('K ' + monthly.toLocaleString('en-US', {minimumFractionDigits: 2}));

	// Update chart
	updateChart('Compound Interest Breakdown', amount, totalInterest);
}

// Update breakdown chart
function updateChart(title, principal, interest) {
	const ctx = document.getElementById('breakdownChart').getContext('2d');
	
	if(breakdownChart) {
		breakdownChart.destroy();
	}

	breakdownChart = new Chart(ctx, {
		type: 'doughnut',
		data: {
			labels: ['Principal', 'Interest'],
			datasets: [{
				data: [principal, interest],
				backgroundColor: ['#36a2eb', '#ff6384']
			}]
		},
		options: {
			responsive: true,
			plugins: {
				title: {
					display: true,
					text: title
				},
				legend: {
					position: 'bottom'
				}
			}
		}
	});
}

// Initialize on page load
$(document).ready(function() {
	$('#months_group').hide();
});
</script>

<style>
	.table-lg td {
		padding: 1rem;
		font-size: 1rem;
	}
	.btn-group-toggle .btn {
		padding: 10px 20px;
	}
</style>
