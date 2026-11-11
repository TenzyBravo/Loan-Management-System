<?php
session_start();
if(!isset($_SESSION['customer_id'])){
    header('location: customer_login.php');
    exit;
}

include('db_connect.php');
require_once __DIR__ . '/includes/finance.php';

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
    <title>Apply for Loan | Basils Money Lenders</title>
    
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

        /* Mobile Menu Toggle */
        .mobile-menu-toggle {
            display: none;
            position: fixed;
            top: 15px;
            left: 15px;
            z-index: 1001;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            padding: 12px 15px;
            border-radius: 8px;
            font-size: 1.2rem;
            cursor: pointer;
            box-shadow: 0 4px 15px rgba(102, 126, 234, 0.4);
        }

        .sidebar-overlay {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.5);
            z-index: 999;
        }

        /* Mobile Responsive */
        @media (max-width: 768px) {
            .mobile-menu-toggle {
                display: block;
            }

            .sidebar {
                left: -250px;
                transition: left 0.3s ease;
                z-index: 1000;
            }

            .sidebar.active {
                left: 0;
            }

            .sidebar-overlay.active {
                display: block;
            }

            .main-content {
                margin-left: 0;
                padding: 80px 15px 30px 15px;
            }

            .application-card {
                padding: 20px;
            }

            .card-header-custom {
                margin: -20px -20px 20px -20px;
                padding: 15px;
            }

            .plan-card {
                padding: 15px;
            }
        }
    </style>
</head>
<body>
    <!-- Mobile Menu Toggle -->
    <button class="mobile-menu-toggle" onclick="toggleMobileMenu()">
        <i class="fas fa-bars"></i>
    </button>
    <div class="sidebar-overlay" onclick="toggleMobileMenu()"></div>

    <!-- Sidebar -->
    <div class="sidebar">
        <div class="brand">
            <i class="fas fa-hand-holding-usd"></i><br>
            Basils Money Lenders
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
            <hr style="border-color: rgba(255,255,255,0.2); margin: 15px;">
            <a class="nav-link" href="customer_logout.php" style="color: #ff6b6b !important;">
                <i class="fas fa-sign-out-alt"></i> Logout
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

                            <!-- Repayment Term -->
                            <div class="form-group">
                                <label class="form-label">
                                    <i class="fas fa-calendar-alt"></i> Repayment Term *
                                </label>
                                <select class="form-control" name="term_choice" id="term_choice" required>
                                    <option value="">Select repayment term</option>
                                    <optgroup label="Weekly loans - fixed rate, one repayment at the end">
                                        <?php foreach (AppConfig::WEEKLY_RATES as $weeks => $rate): ?>
                                            <option value="week:<?php echo $weeks; ?>" data-rate="<?php echo $rate; ?>" data-count="<?php echo $weeks; ?>" data-unit="week">
                                                <?php echo formatLoanTerm($weeks, 'week'); ?> - <?php echo rtrim(rtrim(number_format($rate, 2), '0'), '.'); ?>% total interest
                                            </option>
                                        <?php endforeach; ?>
                                    </optgroup>
                                    <optgroup label="Monthly loans">
                                        <option value="month:1" data-rate="18" data-count="1" data-unit="month">1 month - 18% total interest</option>
                                        <option value="month:multi" data-unit="month">2 or more months - rate set by our team</option>
                                    </optgroup>
                                </select>
                            </div>

                            <div class="form-group" id="months-group" style="display: none;">
                                <label class="form-label">
                                    <i class="fas fa-hourglass-half"></i> Number of Months *
                                </label>
                                <input type="number" class="form-control" name="duration_months" id="duration_months"
                                       placeholder="Enter number of months" min="2" max="120">
                                <small class="form-text text-muted">Loans of 2 or more months are repaid monthly; the interest rate is set by our team after review.</small>
                            </div>

                            <!-- Current Rates (reference) -->
                            <div class="form-group">
                                <div class="plan-card" style="border: 1px solid #e0e0e0; border-radius: 10px; padding: 15px; margin-bottom: 10px; background: #d1fae5; border-left: 4px solid #10b981;">
                                    <h6 class="mb-2"><i class="fas fa-percent text-success"></i> Current Rates <small class="text-muted">(effective 1 November 2026)</small></h6>
                                    <div class="row text-center">
                                        <?php foreach (AppConfig::WEEKLY_RATES as $weeks => $rate): ?>
                                            <div class="col-6 col-md-3 mb-2">
                                                <h5 class="mb-0"><?php echo rtrim(rtrim(number_format($rate, 2), '0'), '.'); ?>%</h5>
                                                <small class="text-muted"><?php echo formatLoanTerm($weeks, 'week'); ?></small>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                    <small class="text-muted">
                                        1-month loans: 18% total interest. 2+ month loans: rate set after review.
                                        <strong>All loans are collateral based.</strong>
                                    </small>
                                </div>
                            </div>

                            <!-- Collateral -->
                            <div class="form-group">
                                <label class="form-label">
                                    <i class="fas fa-shield-alt"></i> Collateral Offered *
                                </label>
                                <textarea class="form-control" name="collateral_description" id="collateral_description" rows="3"
                                          placeholder="Describe the item(s) you are offering as collateral, e.g. Toyota Corolla 2012, reg. ABC 1234" required></textarea>
                                <small class="form-text text-muted">All loans are collateral based. Our team will verify the collateral before approval.</small>
                            </div>

                            <div class="form-group">
                                <label class="form-label">
                                    <i class="fas fa-coins"></i> Estimated Collateral Value (K) <small class="text-muted">(optional)</small>
                                </label>
                                <input type="number" class="form-control" name="collateral_value" id="collateral_value"
                                       placeholder="Estimated value in Kwacha" min="0" step="0.01">
                            </div>

                            <!-- Loan Calculation Display -->
                            <div class="calculation-box" id="calculation-box" style="display: none;">
                                <h5><i class="fas fa-calculator"></i> Loan Calculation</h5>
                                <div class="calc-item">
                                    <span>Loan Amount:</span>
                                    <span id="calc-principal">K 0.00</span>
                                </div>
                                <div class="calc-item">
                                    <span>Interest (<span id="calc-interest-rate">0</span>):</span>
                                    <span id="calc-interest">K 0.00</span>
                                </div>
                                <div class="calc-item">
                                    <span>Total Amount:</span>
                                    <span id="calc-total">K 0.00</span>
                                </div>
                                <div class="calc-item">
                                    <span id="calc-installment-label">Repayment:</span>
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
        // Show the months field only for 2+ month loans
        $('#term_choice').on('change', function(){
            var isMulti = $(this).val() === 'month:multi';
            $('#months-group').toggle(isMulti);
            $('#duration_months').prop('required', isMulti);
            calculateLoan();
        });

        // Recalculate when amount or months change
        $('#loan_amount, #duration_months').on('change keyup', function(){
            calculateLoan();
        });

        function formatK(value) {
            return 'K ' + value.toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2});
        }

        function calculateLoan() {
            // Display only - the server recalculates every figure on submission
            var amount = parseFloat($('#loan_amount').val());
            var option = $('#term_choice option:selected');
            var choice = option.val();

            if(!amount || !choice) {
                $('#calculation-box').slideUp();
                return;
            }

            var rate = parseFloat(option.data('rate'));
            var unit = option.data('unit');

            if(choice === 'month:multi') {
                var months = parseInt($('#duration_months').val());
                $('#calc-principal').text(formatK(amount));
                $('#calc-interest-rate').text('rate set by our team after review');
                $('#calc-interest').html('<em>Pending review</em>');
                $('#calc-total').html('<em>Calculated once the rate is set</em>');
                $('#calc-installment-label').text('Monthly Payment' + (months ? ' (' + months + ' months):' : ':'));
                $('#calc-monthly').html('<em>Pending review</em>');
                $('#calculation-box').css({'background': '#fff3cd', 'border-left': '4px solid #f59e0b'});
                $('#calculation-box h5').html('<i class="fas fa-info-circle"></i> Loan Information').css('color', '#f57c00');
            } else {
                var count = parseInt(option.data('count'));
                // TOTAL interest on the principal (not annual)
                var totalInterest = amount * (rate / 100);
                var totalAmount = amount + totalInterest;

                $('#calc-principal').text(formatK(amount));
                $('#calc-interest-rate').text(rate + '% total');
                $('#calc-interest').text(formatK(totalInterest));
                $('#calc-total').text(formatK(totalAmount));

                if(unit === 'week') {
                    var due = new Date();
                    due.setDate(due.getDate() + count * 7);
                    $('#calc-installment-label').text('One repayment, due ' + count + ' week' + (count > 1 ? 's' : '') + ' after release (about ' + due.toLocaleDateString('en-GB', {day: 'numeric', month: 'short', year: 'numeric'}) + '):');
                    $('#calc-monthly').text(formatK(totalAmount));
                } else {
                    $('#calc-installment-label').text('Repayment after 1 month:');
                    $('#calc-monthly').text(formatK(totalAmount));
                }

                $('#calculation-box').css({'background': '#d1fae5', 'border-left': '4px solid #10b981'});
                $('#calculation-box h5').html('<i class="fas fa-calculator"></i> Loan Calculation').css('color', '#059669');
            }

            $('#calculation-box').slideDown();
        }

        // Form validation
        $('#loan-application-form').on('submit', function(e){
            if(!$('input[name="terms"]').is(':checked')) {
                e.preventDefault();
                alert('Please accept the terms and conditions');
                return false;
            }
            if(!$.trim($('#collateral_description').val())) {
                e.preventDefault();
                alert('All loans are collateral based. Please describe the collateral you are offering.');
                return false;
            }
        });

        // Mobile menu toggle
        function toggleMobileMenu() {
            $('.sidebar').toggleClass('active');
            $('.sidebar-overlay').toggleClass('active');
        }

        // Close sidebar when clicking a nav link on mobile
        $('.sidebar .nav-link').on('click', function() {
            if ($(window).width() <= 768) {
                $('.sidebar').removeClass('active');
                $('.sidebar-overlay').removeClass('active');
            }
        });
    </script>
</body>
</html>
