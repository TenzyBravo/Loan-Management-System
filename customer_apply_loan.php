<?php
session_start();
if(!isset($_SESSION['customer_id'])){
    header('location: customer_login.php');
    exit;
}

include('db_connect.php');

$customer_id = $_SESSION['customer_id'];

// Get customer info
$stmt = $conn->prepare("SELECT * FROM borrowers WHERE id = ?");
$stmt->bind_param("i", $customer_id);
$stmt->execute();
$customer = $stmt->get_result()->fetch_assoc();
$stmt->close();

// Check if profile is complete (skip this check if profile_complete field doesn't exist)
// Check if profile is complete (check if required fields are filled)
// Skip this check if the profile_complete field doesn't exist in the database
// For now, we'll assume the profile is complete if basic info exists

// Check if all documents are verified
$stmt = $conn->prepare("SELECT COUNT(*) as count FROM borrower_documents
                                 WHERE borrower_id = ? AND status != 1");
$stmt->bind_param("i", $customer_id);
$stmt->execute();
$doc_check = $stmt->get_result()->fetch_assoc();
$stmt->close();

if($doc_check['count'] > 0) {
    $_SESSION['warning_msg'] = 'Some of your documents are pending verification. You can still apply, but approval may take longer.';
}

// Get loan types and plans
$stmt = $conn->prepare("SELECT * FROM loan_types ORDER BY type_name ASC");
$stmt->execute();
$loan_types = $stmt->get_result();
$stmt->close();

$stmt = $conn->prepare("SELECT * FROM loan_plan ORDER BY months ASC");
$stmt->execute();
$loan_plans = $stmt->get_result();
$stmt->close();
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
                                <small class="form-text text-muted">Minimum: K 1,000 | Maximum: K 1,000,000</small>
                            </div>

                            <!-- Purpose -->
                            <div class="form-group">
                                <label class="form-label">
                                    <i class="fas fa-bullseye"></i> Purpose of Loan *
                                </label>
                                <textarea class="form-control" name="purpose" rows="4" 
                                          placeholder="Describe the purpose of this loan..." required></textarea>
                            </div>

                            <!-- Duration Selection -->
                            <div class="form-group">
                                <label class="form-label">
                                    <i class="fas fa-calendar-alt"></i> Loan Duration (Months) *
                                </label>
                                <input type="number" class="form-control" name="duration_months" id="duration_months"
                                       placeholder="Enter loan duration in months" min="1" max="120" required>
                                <small class="form-text text-muted">Enter the number of months for loan repayment</small>
                            </div>

                            <!-- Predefined Loan Plans (for reference only) -->
                            <div class="form-group">
                                <label class="form-label">
                                    <i class="fas fa-list"></i> Available Loan Plans (Reference Only)
                                </label>

                                <div class="plan-card" style="border: 1px solid #e0e0e0; border-radius: 10px; padding: 15px; margin-bottom: 10px; background: #d1fae5; border-left: 4px solid #10b981;">
                                    <div class="row">
                                        <div class="col-md-4">
                                            <h5 class="mb-1"><i class="fas fa-check-circle text-success"></i> 1 Month</h5>
                                            <small class="text-muted">Payment Period</small>
                                        </div>
                                        <div class="col-md-4">
                                            <h5 class="mb-1">18% Total</h5>
                                            <small class="text-success"><strong>Fixed Rate (Auto-Applied)</strong></small>
                                        </div>
                                        <div class="col-md-4">
                                            <h5 class="mb-1">5%</h5>
                                            <small class="text-muted">Penalty Rate</small>
                                        </div>
                                    </div>
                                </div>

                                <div class="plan-card" style="border: 1px solid #e0e0e0; border-radius: 10px; padding: 15px; margin-bottom: 10px; background: #fff3cd; border-left: 4px solid #f59e0b;">
                                    <div class="row">
                                        <div class="col-md-4">
                                            <h5 class="mb-1"><i class="fas fa-clock text-warning"></i> 2+ Months</h5>
                                            <small class="text-muted">Payment Period</small>
                                        </div>
                                        <div class="col-md-8">
                                            <h5 class="mb-1">Rate Determined by Administrator</h5>
                                            <small class="text-warning"><strong>Rate will be assigned based on your credit assessment (typically 10-40%)</strong></small>
                                        </div>
                                    </div>
                                </div>

                                <small class="form-text text-muted">
                                    <i class="fas fa-info-circle"></i>
                                    <strong>Interest Rate Policy:</strong><br>
                                    • <strong>1-month loans:</strong> Fixed 18% total interest (automatically applied to full loan amount)<br>
                                    • <strong>Multi-month loans:</strong> Annual interest rate (10-40%) determined by administrator based on credit assessment and risk profile
                                </small>
                            </div>

                            <!-- Loan Calculation Display -->
                            <div class="calculation-box" id="calculation-box" style="display: none;">
                                <h5><i class="fas fa-calculator"></i> Loan Calculation</h5>
                                <div class="calc-item">
                                    <span>Loan Amount:</span>
                                    <span id="calc-principal">K 0.00</span>
                                </div>
                                <div class="calc-item">
                                    <span>Interest (<span id="calc-interest-rate">0</span>%):</span>
                                    <span id="calc-interest">K 0.00</span>
                                </div>
                                <div class="calc-item">
                                    <span>Total Amount:</span>
                                    <span id="calc-total">K 0.00</span>
                                </div>
                                <div class="calc-item">
                                    <span>Monthly Payment (<span id="calc-months">0</span> months):</span>
                                    <span id="calc-monthly">K 0.00</span>
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
        // Calculate loan when amount or duration changes
        $('#loan_amount, #duration_months').on('change', function(){
            calculateLoan();
        });

        function calculateLoan() {
            var amount = parseFloat($('#loan_amount').val());
            var durationMonths = parseInt($('#duration_months').val());

            if(amount && durationMonths) {
                // BUSINESS RULE: Interest rate based on DURATION, not amount
                var interestRate;
                var rateNote;
                var canCalculate = true;

                if(durationMonths === 1) {
                    // 1-month loans: ALWAYS 18% (auto-applied)
                    interestRate = 18.0;
                    rateNote = '18% (Fixed for 1-month loans)';
                } else {
                    // Multi-month loans: Rate determined by administrator
                    // Show message instead of estimating
                    canCalculate = false;
                }

                if(canCalculate) {
                    // Calculate using simple interest (only for 1-month loans)
                    // For 1-month loans: 18% is the TOTAL interest, not annual
                    var totalInterest = amount * (interestRate / 100); // Direct percentage
                    var totalAmount = amount + totalInterest;
                    var monthlyPayment = totalAmount / durationMonths;

                    // Display calculations
                    $('#calc-principal').text('K ' + amount.toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2}));
                    $('#calc-interest-rate').text(rateNote);
                    $('#calc-interest').text('K ' + totalInterest.toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2}));
                    $('#calc-total').text('K ' + totalAmount.toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2}));
                    $('#calc-months').text(durationMonths);
                    $('#calc-monthly').text('K ' + monthlyPayment.toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2}));

                    // Change calculation box styling to success (green) for 1-month loans
                    $('#calculation-box').css({
                        'background': '#d1fae5',
                        'border-left': '4px solid #10b981'
                    });
                    $('#calculation-box h5').css('color', '#059669');
                } else {
                    // For multi-month loans, show informational message
                    $('#calc-principal').text('K ' + amount.toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2}));
                    $('#calc-interest-rate').text('To be determined by administrator');
                    $('#calc-interest').html('<em>Pending admin review</em>');
                    $('#calc-total').html('<em>Will be calculated after rate assignment</em>');
                    $('#calc-months').text(durationMonths);
                    $('#calc-monthly').html('<em>Pending admin review</em>');

                    // Change calculation box styling to warning (yellow) for multi-month loans
                    $('#calculation-box').css({
                        'background': '#fff3cd',
                        'border-left': '4px solid #f59e0b'
                    });
                    $('#calculation-box h5').html('<i class="fas fa-info-circle"></i> Loan Information');
                    $('#calculation-box h5').css('color', '#f57c00');
                }

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
