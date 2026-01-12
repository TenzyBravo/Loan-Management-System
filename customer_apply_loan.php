<?php
session_start();
if(!isset($_SESSION['customer_id'])){
    header('location: customer_login.php');
    exit;
}

include('db_connect.php');

$customer_id = $_SESSION['customer_id'];

// Get customer info
$customer = $conn->query("SELECT * FROM borrowers WHERE id = $customer_id")->fetch_assoc();

// Check if profile is complete
if($customer['profile_complete'] != 1) {
    $_SESSION['error_msg'] = 'Please complete your profile before applying for a loan.';
    header('location: customer_profile.php');
    exit;
}

// Check if all documents are verified
$unverified_docs = $conn->query("SELECT COUNT(*) as count FROM borrower_documents 
                                 WHERE borrower_id = $customer_id AND status != 1");
$doc_check = $unverified_docs->fetch_assoc();

if($doc_check['count'] > 0) {
    $_SESSION['warning_msg'] = 'Some of your documents are pending verification. You can still apply, but approval may take longer.';
}

// Get loan types and plans
$loan_types = $conn->query("SELECT * FROM loan_types ORDER BY type_name ASC");
$loan_plans = $conn->query("SELECT * FROM loan_plan ORDER BY months ASC");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Apply for Loan | Loan Management System</title>
    
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    
    <style>
        body {
            background: #f5f6fa;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        .sidebar {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 20px 0;
            position: fixed;
            width: 250px;
        }
        .sidebar .brand {
            color: white;
            text-align: center;
            padding: 20px;
            font-size: 1.5rem;
            font-weight: bold;
        }
        .sidebar .nav-link {
            color: rgba(255,255,255,0.8);
            padding: 12px 25px;
            margin: 5px 15px;
            border-radius: 8px;
        }
        .sidebar .nav-link:hover, .sidebar .nav-link.active {
            background: rgba(255,255,255,0.2);
            color: white;
        }
        .main-content {
            margin-left: 250px;
            padding: 30px;
        }
        .application-card {
            background: white;
            border-radius: 15px;
            box-shadow: 0 5px 20px rgba(0,0,0,0.1);
            padding: 40px;
        }
        .card-header-custom {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 20px;
            border-radius: 10px 10px 0 0;
            margin: -40px -40px 30px -40px;
        }
        .form-label {
            font-weight: 600;
            color: #333;
            margin-bottom: 8px;
        }
        .form-control, .form-select {
            border-radius: 8px;
            padding: 12px 15px;
            border: 1px solid #ddd;
        }
        .form-control:focus {
            border-color: #667eea;
            box-shadow: 0 0 0 0.2rem rgba(102, 126, 234, 0.25);
        }
        .btn-submit {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border: none;
            padding: 12px 40px;
            border-radius: 8px;
            color: white;
            font-weight: 600;
        }
        .btn-submit:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(102, 126, 234, 0.4);
            color: white;
        }
        .info-box {
            background: #e3f2fd;
            border-left: 4px solid #2196f3;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
        }
        .plan-card {
            border: 2px solid #e0e0e0;
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 15px;
            cursor: pointer;
            transition: all 0.3s;
        }
        .plan-card:hover {
            border-color: #667eea;
            background: #f8f9ff;
        }
        .plan-card.selected {
            border-color: #667eea;
            background: #f8f9ff;
            box-shadow: 0 3px 10px rgba(102, 126, 234, 0.3);
        }
        .plan-card input[type="radio"] {
            display: none;
        }
        .calculation-box {
            background: #fff3e0;
            border-left: 4px solid #ff9800;
            padding: 20px;
            border-radius: 8px;
            margin-top: 20px;
        }
        .calculation-box h5 {
            color: #f57c00;
            margin-bottom: 15px;
        }
        .calc-item {
            display: flex;
            justify-content: space-between;
            padding: 8px 0;
            border-bottom: 1px solid #ffe0b2;
        }
        .calc-item:last-child {
            border-bottom: none;
            font-weight: bold;
            font-size: 1.1rem;
            color: #f57c00;
        }
    </style>
</head>
<body>
    <!-- Sidebar -->
    <div class="sidebar">
        <div class="brand">
            <i class="fas fa-hand-holding-usd"></i><br>
            Loan Portal
        </div>
        <nav class="nav flex-column mt-4">
            <a class="nav-link" href="customer_dashboard.php">
                <i class="fas fa-tachometer-alt"></i> Dashboard
            </a>
            <a class="nav-link active" href="customer_apply_loan.php">
                <i class="fas fa-plus-circle"></i> Apply for Loan
            </a>
            <a class="nav-link" href="customer_my_loans.php">
                <i class="fas fa-list"></i> My Loans
            </a>
            <a class="nav-link" href="customer_my_documents.php">
                <i class="fas fa-file-alt"></i> My Documents
            </a>
            <a class="nav-link" href="customer_profile.php">
                <i class="fas fa-user"></i> My Profile
            </a>
        </nav>
    </div>

    <!-- Main Content -->
    <div class="main-content">
        <div class="container-fluid">
            <div class="row">
                <div class="col-12">
                    <h2 class="mb-4"><i class="fas fa-plus-circle"></i> Apply for a Loan</h2>
                    
                    <?php if(isset($_SESSION['error_msg'])): ?>
                        <div class="alert alert-danger alert-dismissible fade show">
                            <i class="fas fa-exclamation-circle"></i>
                            <?php echo $_SESSION['error_msg']; unset($_SESSION['error_msg']); ?>
                            <button type="button" class="close" data-dismiss="alert">&times;</button>
                        </div>
                    <?php endif; ?>

                    <?php if(isset($_SESSION['warning_msg'])): ?>
                        <div class="alert alert-warning alert-dismissible fade show">
                            <i class="fas fa-exclamation-triangle"></i>
                            <?php echo $_SESSION['warning_msg']; unset($_SESSION['warning_msg']); ?>
                            <button type="button" class="close" data-dismiss="alert">&times;</button>
                        </div>
                    <?php endif; ?>

                    <div class="application-card">
                        <div class="card-header-custom">
                            <h4 class="mb-0"><i class="fas fa-file-contract"></i> Loan Application Form</h4>
                            <p class="mb-0 mt-2" style="opacity: 0.9;">Fill out the form below to submit your loan application</p>
                        </div>

                        <div class="info-box">
                            <i class="fas fa-info-circle"></i>
                            <strong>Before you apply:</strong> Make sure all your documents are uploaded and verified. 
                            Applications are reviewed within 24-48 hours.
                        </div>

                        <form id="loan-application-form" method="POST" action="customer_apply_loan_process.php">
                            
                            <!-- Loan Type -->
                            <div class="form-group">
                                <label class="form-label">
                                    <i class="fas fa-tag"></i> Loan Type *
                                </label>
                                <select class="form-control" name="loan_type_id" id="loan_type" required>
                                    <option value="">Select Loan Type</option>
                                    <?php while($type = $loan_types->fetch_assoc()): ?>
                                        <option value="<?php echo $type['id']; ?>">
                                            <?php echo $type['type_name']; ?> - <?php echo $type['description']; ?>
                                        </option>
                                    <?php endwhile; ?>
                                </select>
                            </div>

                            <!-- Loan Amount -->
                            <div class="form-group">
                                <label class="form-label">
                                    <i class="fas fa-dollar-sign"></i> Loan Amount *
                                </label>
                                <input type="number" class="form-control" name="amount" id="loan_amount" 
                                       placeholder="Enter amount" min="1000" max="1000000" required>
                                <small class="form-text text-muted">Minimum: $1,000 | Maximum: $1,000,000</small>
                            </div>

                            <!-- Purpose -->
                            <div class="form-group">
                                <label class="form-label">
                                    <i class="fas fa-bullseye"></i> Purpose of Loan *
                                </label>
                                <textarea class="form-control" name="purpose" rows="4" 
                                          placeholder="Describe the purpose of this loan..." required></textarea>
                            </div>

                            <!-- Payment Plan Selection -->
                            <div class="form-group">
                                <label class="form-label">
                                    <i class="fas fa-calendar-alt"></i> Select Payment Plan *
                                </label>
                                
                                <?php 
                                $loan_plans->data_seek(0); // Reset pointer
                                while($plan = $loan_plans->fetch_assoc()): 
                                ?>
                                    <label class="plan-card" for="plan_<?php echo $plan['id']; ?>">
                                        <input type="radio" name="plan_id" id="plan_<?php echo $plan['id']; ?>" 
                                               value="<?php echo $plan['id']; ?>"
                                               data-months="<?php echo $plan['months']; ?>"
                                               data-interest="<?php echo $plan['interest_percentage']; ?>"
                                               data-penalty="<?php echo $plan['penalty_rate']; ?>"
                                               required>
                                        <div class="row">
                                            <div class="col-md-4">
                                                <h5 class="mb-1"><?php echo $plan['months']; ?> Months</h5>
                                                <small class="text-muted">Payment Period</small>
                                            </div>
                                            <div class="col-md-4">
                                                <h5 class="mb-1"><?php echo $plan['interest_percentage']; ?>%</h5>
                                                <small class="text-muted">Interest Rate</small>
                                            </div>
                                            <div class="col-md-4">
                                                <h5 class="mb-1"><?php echo $plan['penalty_rate']; ?>%</h5>
                                                <small class="text-muted">Penalty Rate</small>
                                            </div>
                                        </div>
                                    </label>
                                <?php endwhile; ?>
                            </div>

                            <!-- Loan Calculation Display -->
                            <div class="calculation-box" id="calculation-box" style="display: none;">
                                <h5><i class="fas fa-calculator"></i> Loan Calculation</h5>
                                <div class="calc-item">
                                    <span>Loan Amount:</span>
                                    <span id="calc-principal">$0.00</span>
                                </div>
                                <div class="calc-item">
                                    <span>Interest (<span id="calc-interest-rate">0</span>%):</span>
                                    <span id="calc-interest">$0.00</span>
                                </div>
                                <div class="calc-item">
                                    <span>Total Amount:</span>
                                    <span id="calc-total">$0.00</span>
                                </div>
                                <div class="calc-item">
                                    <span>Monthly Payment (<span id="calc-months">0</span> months):</span>
                                    <span id="calc-monthly">$0.00</span>
                                </div>
                            </div>

                            <!-- Terms and Conditions -->
                            <div class="form-group mt-4">
                                <div class="custom-control custom-checkbox">
                                    <input type="checkbox" class="custom-control-input" id="terms" name="terms" required>
                                    <label class="custom-control-label" for="terms">
                                        I confirm that all information provided is accurate and I agree to the 
                                        <a href="#" target="_blank">Terms and Conditions</a>
                                    </label>
                                </div>
                            </div>

                            <!-- Submit Button -->
                            <div class="text-right mt-4">
                                <a href="customer_dashboard.php" class="btn btn-secondary">
                                    <i class="fas fa-times"></i> Cancel
                                </a>
                                <button type="submit" class="btn btn-submit">
                                    <i class="fas fa-paper-plane"></i> Submit Application
                                </button>
                            </div>

                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@4.5.2/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
        // Plan card selection visual feedback
        $('.plan-card').click(function(){
            $('.plan-card').removeClass('selected');
            $(this).addClass('selected');
            $(this).find('input[type="radio"]').prop('checked', true);
            calculateLoan();
        });

        // Calculate loan when amount or plan changes
        $('#loan_amount, input[name="plan_id"]').on('change', function(){
            calculateLoan();
        });

        function calculateLoan() {
            var amount = parseFloat($('#loan_amount').val());
            var selectedPlan = $('input[name="plan_id"]:checked');
            
            if(amount && selectedPlan.length > 0) {
                var months = parseInt(selectedPlan.data('months'));
                var interestRate = parseFloat(selectedPlan.data('interest'));
                
                // Calculate total interest
                var interest = (amount * interestRate) / 100;
                var totalAmount = amount + interest;
                var monthlyPayment = totalAmount / months;
                
                // Display calculations
                $('#calc-principal').text('$' + amount.toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2}));
                $('#calc-interest-rate').text(interestRate);
                $('#calc-interest').text('$' + interest.toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2}));
                $('#calc-total').text('$' + totalAmount.toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2}));
                $('#calc-months').text(months);
                $('#calc-monthly').text('$' + monthlyPayment.toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2}));
                
                $('#calculation-box').slideDown();
            } else {
                $('#calculation-box').slideUp();
            }
        }

        // Form validation
        $('#loan-application-form').on('submit', function(e){
            if(!$('input[name="terms"]').is(':checked')) {
                e.preventDefault();
                alert('Please accept the terms and conditions');
                return false;
            }
        });
    </script>
</body>
</html>
