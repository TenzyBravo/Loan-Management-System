<?php
session_start();
if(!isset($_SESSION['customer_id'])){
    header('location: customer_login.php');
    exit;
}

include('db_connect.php');
require_once __DIR__ . '/includes/finance.php';
require_once __DIR__ . '/includes/borrower_rules.php';
require_once __DIR__ . '/includes/portal_layout.php';

$customer_id = $_SESSION['customer_id'];

// One loan at a time: hide the form while the customer has an open loan
$open_loan = findOpenLoan($conn, (int) $customer_id);

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

// Page-specific layout: steps on the left, sticky summary on the right (desktop only)
$page_head = <<<'HTML'
<style>
    .step-num {
        width: 28px; height: 28px; border-radius: 50%; flex: none;
        display: inline-grid; place-items: center;
        background: var(--brand); color: #fff; font-size: .85rem; font-weight: 700;
    }
    .rate-ref { background: var(--brand-50); border-radius: var(--radius-sm); padding: 14px 16px; }
    .rate-ref-value { font-size: 1.2rem; font-weight: 700; color: var(--brand); }
    .summary-card { border-top: 4px solid var(--gold); }
    .calc-heading { font-size: .95rem; font-weight: 600; color: var(--brand); margin-bottom: 12px; }
    #calculation-box.is-pending .calc-heading { color: var(--warn); }
    #calculation-box .p-kv dt { padding-top: 2px; }
    #calculation-box .calc-total-label { color: var(--ink); font-weight: 600; border-top: 1px solid var(--line); padding-top: 10px; }
    #calculation-box .calc-total-value { font-size: 1.25rem; color: var(--brand); border-top: 1px solid var(--line); padding-top: 8px; }
    @media (min-width: 992px) {
        .apply-grid { display: grid; grid-template-columns: minmax(0, 1fr) 340px; column-gap: 24px; align-items: start; }
        .apply-grid > .apply-step { grid-column: 1; }
        .apply-grid > .apply-summary { grid-column: 2; grid-row: 1 / span 4; position: sticky; top: 24px; }
    }
</style>
HTML;

portal_header('Apply for Loan', 'apply', ['head' => $page_head]);
?>
    <div class="page-head">
        <div>
            <h1>Apply for a Loan</h1>
            <p>Fill out the form below to submit your loan application</p>
        </div>
    </div>

    <?php if(isset($_SESSION['error_msg'])): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="fas fa-exclamation-circle"></i>
            <?php echo $_SESSION['error_msg']; unset($_SESSION['error_msg']); ?>
            <button type="button" class="close" data-dismiss="alert" aria-label="Close">&times;</button>
        </div>
    <?php endif; ?>

    <?php if(isset($_SESSION['warning_msg'])): ?>
        <div class="alert alert-warning alert-dismissible fade show" role="alert">
            <i class="fas fa-exclamation-triangle"></i>
            <?php echo $_SESSION['warning_msg']; unset($_SESSION['warning_msg']); ?>
            <button type="button" class="close" data-dismiss="alert" aria-label="Close">&times;</button>
        </div>
    <?php endif; ?>

    <?php if ($open_loan !== null): ?>
        <!-- One loan at a time: no form while a loan is open -->
        <div class="alert alert-info">
            <i class="fas fa-info-circle"></i>
            <?php echo openLoanMessage($open_loan, true); ?>
            <div class="mt-3">
                <a href="customer_my_loans.php" class="btn btn-primary btn-sm">View My Loans</a>
            </div>
        </div>
    <?php else: ?>
    <div class="alert alert-info">
        <i class="fas fa-info-circle"></i>
        <strong>Before you apply:</strong> Make sure all your documents are uploaded and verified.
        Applications are reviewed within 24-48 hours.
    </div>

    <form id="loan-application-form" method="POST" action="customer_apply_loan_process.php">
        <div class="apply-grid">

            <!-- Step 1: Loan details -->
            <section class="p-card apply-step" aria-labelledby="step1-title">
                <h2 class="p-card-title" id="step1-title"><span class="step-num">1</span> Loan details</h2>

                <div class="form-group">
                    <label class="req" for="loan_type">Loan Type</label>
                    <select class="form-control" name="loan_type_id" id="loan_type" required>
                        <option value="">Select Loan Type</option>
                        <?php while($type = $loan_types->fetch_assoc()): ?>
                            <option value="<?php echo $type['id']; ?>">
                                <?php echo htmlspecialchars($type['type_name']); ?> - <?php echo htmlspecialchars((string) $type['description']); ?>
                            </option>
                        <?php endwhile; ?>
                    </select>
                </div>

                <div class="form-group">
                    <label class="req" for="loan_amount">Loan Amount</label>
                    <div class="input-group">
                        <div class="input-group-prepend"><span class="input-group-text">K</span></div>
                        <input type="number" class="form-control tabular" name="amount" id="loan_amount"
                               placeholder="Enter amount" min="1000" max="1000000" inputmode="numeric" required>
                    </div>
                    <small class="form-text text-muted">Minimum: K 1,000 | Maximum: K 1,000,000</small>
                </div>

                <div class="form-group mb-0">
                    <label class="req" for="purpose">Purpose of Loan</label>
                    <textarea class="form-control" name="purpose" id="purpose" rows="4"
                              placeholder="Describe the purpose of this loan..." required></textarea>
                </div>
            </section>

            <!-- Step 2: Repayment term -->
            <section class="p-card apply-step" aria-labelledby="step2-title">
                <h2 class="p-card-title" id="step2-title"><span class="step-num">2</span> Repayment term</h2>

                <div class="form-group">
                    <label class="req" for="term_choice">Repayment Term</label>
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
                    <label class="req" for="duration_months">Number of Months</label>
                    <input type="number" class="form-control tabular" name="duration_months" id="duration_months"
                           placeholder="Enter number of months" min="2" max="120" inputmode="numeric">
                    <small class="form-text text-muted">Loans of 2 or more months are repaid monthly; the interest rate is set by our team after review.</small>
                </div>

                <!-- Current Rates (reference) -->
                <div class="rate-ref">
                    <h3 class="h6 mb-2"><i class="fas fa-percent text-success"></i> Current Rates <small class="text-muted">(effective 1 November 2026)</small></h3>
                    <div class="row text-center">
                        <?php foreach (AppConfig::WEEKLY_RATES as $weeks => $rate): ?>
                            <div class="col-6 col-md-3 mb-2">
                                <div class="rate-ref-value tabular"><?php echo rtrim(rtrim(number_format($rate, 2), '0'), '.'); ?>%</div>
                                <small class="text-muted"><?php echo formatLoanTerm($weeks, 'week'); ?></small>
                            </div>
                        <?php endforeach; ?>
                    </div>
                    <small class="text-muted">
                        1-month loans: 18% total interest. 2+ month loans: rate set after review.
                        <strong>All loans are collateral based.</strong>
                    </small>
                </div>
            </section>

            <!-- Step 3: Collateral -->
            <section class="p-card apply-step" aria-labelledby="step3-title">
                <h2 class="p-card-title" id="step3-title"><span class="step-num">3</span> Collateral</h2>

                <div class="form-group">
                    <label class="req" for="collateral_description">Collateral Offered</label>
                    <textarea class="form-control" name="collateral_description" id="collateral_description" rows="3"
                              placeholder="Describe the item(s) you are offering as collateral, e.g. Toyota Corolla 2012, reg. ABC 1234" required></textarea>
                    <small class="form-text text-muted">All loans are collateral based. Our team will verify the collateral before approval.</small>
                </div>

                <div class="form-group mb-0">
                    <label for="collateral_value">Estimated Collateral Value (K) <small class="text-muted">(optional)</small></label>
                    <input type="number" class="form-control tabular" name="collateral_value" id="collateral_value"
                           placeholder="Estimated value in Kwacha" min="0" step="0.01" inputmode="decimal">
                </div>
            </section>

            <!-- Loan summary: sticky on desktop, above the submit step on phones -->
            <aside class="apply-summary" aria-live="polite">
                <div class="p-card summary-card">
                    <h2 class="p-card-title"><i class="fas fa-receipt"></i> Loan summary</h2>
                    <p class="text-muted mb-0" id="calc-empty">Enter a loan amount and choose a repayment term to see your total payable.</p>

                    <!-- Loan Calculation Display -->
                    <div id="calculation-box" style="display: none;">
                        <h5 class="calc-heading"><i class="fas fa-calculator"></i> Loan Calculation</h5>
                        <dl class="p-kv">
                            <dt>Loan Amount:</dt>
                            <dd id="calc-principal" class="tabular">K 0.00</dd>

                            <dt>Interest (<span id="calc-interest-rate">0</span>):</dt>
                            <dd id="calc-interest" class="tabular">K 0.00</dd>

                            <dt class="calc-total-label">Total Amount:</dt>
                            <dd id="calc-total" class="calc-total-value tabular">K 0.00</dd>

                            <dt id="calc-installment-label">Repayment:</dt>
                            <dd id="calc-monthly" class="tabular">K 0.00</dd>
                        </dl>
                    </div>
                </div>
            </aside>

            <!-- Step 4: Review & submit -->
            <section class="p-card apply-step" aria-labelledby="step4-title">
                <h2 class="p-card-title" id="step4-title"><span class="step-num">4</span> Review &amp; submit</h2>

                <!-- Terms and Conditions -->
                <div class="form-group">
                    <div class="custom-control custom-checkbox">
                        <input type="checkbox" class="custom-control-input" id="terms" name="terms" required>
                        <label class="custom-control-label" for="terms">
                            I confirm that all information provided is accurate and I agree to the
                            <a href="#" target="_blank">Terms and Conditions</a>
                        </label>
                    </div>
                </div>

                <!-- Submit Button -->
                <div class="d-flex flex-column-reverse flex-sm-row justify-content-sm-end gap-2">
                    <a href="customer_dashboard.php" class="btn btn-outline-secondary">
                        <i class="fas fa-times"></i> Cancel
                    </a>
                    <button type="submit" class="btn btn-gold btn-lg">
                        <i class="fas fa-paper-plane"></i> Submit Application
                    </button>
                </div>
            </section>

        </div>
    </form>
    <?php endif; ?>
<?php
$page_scripts = <<<'HTML'
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
            $('#calc-empty').show();
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
            $('#calculation-box').addClass('is-pending');
            $('#calculation-box h5').html('<i class="fas fa-info-circle"></i> Loan Information');
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

            $('#calculation-box').removeClass('is-pending');
            $('#calculation-box h5').html('<i class="fas fa-calculator"></i> Loan Calculation');
        }

        $('#calc-empty').hide();
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
</script>
HTML;

portal_footer(['scripts' => $page_scripts]);
