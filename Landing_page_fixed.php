<?php require_once __DIR__ . '/config/constants.php'; ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>LoanPro — Landing</title>
    <link href="assets/vendor/bootstrap/css/bootstrap.min.css" rel="stylesheet">
    <link href="assets/css/style.css" rel="stylesheet">
</head>
<body>

<!-- (content omitted above) -->

    <section class="statistics-section">
        <div class="container">
            <div class="row">
                <div class="col-md-3">
                    <div class="stat-card">
                        <div class="stat-number">
                            <i class="fas fa-users"></i> 10K+
                        </div>
                        <div class="stat-label">Happy Customers</div>
                    </div>
                </div>
                
                <div class="col-md-3">
                    <div class="stat-card">
                        <div class="stat-number">
                            <i class="fas fa-money-bill-wave"></i> <?php echo AppConfig::CURRENCY_SYMBOL ?>50M+
                        </div>
                        <div class="stat-label">Loans Disbursed</div>
                    </div>
                </div>

                <!-- remaining content unchanged -->
            </div>
        </div>
    </section>

</body>
</html>