<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta content="width=device-width, initial-scale=1.0" name="viewport">
    <title>Brian Investments - Quick Loans in Zambia | Fast Cash When You Need It</title>
    <meta name="description" content="Get quick personal loans in Zambia. Low interest rates, fast approval, easy repayment. Apply online today!">

    <!-- Bootstrap CSS -->
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700;800&display=swap" rel="stylesheet">

    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Poppins', sans-serif;
            overflow-x: hidden;
        }

        /* Navbar */
        .navbar-custom {
            background: rgba(255,255,255,0.95);
            padding: 15px 0;
            box-shadow: 0 2px 20px rgba(0,0,0,0.1);
            position: fixed;
            width: 100%;
            top: 0;
            z-index: 1000;
        }

        .navbar-brand {
            font-weight: 800;
            font-size: 1.8rem;
            color: #1a5f2a !important;
        }

        .navbar-brand span {
            color: #d4af37;
        }

        .nav-link {
            color: #333 !important;
            font-weight: 500;
            margin: 0 10px;
        }

        .btn-apply-nav {
            background: linear-gradient(135deg, #1a5f2a 0%, #2d8a3e 100%);
            color: white !important;
            padding: 10px 25px !important;
            border-radius: 25px;
            font-weight: 600;
        }

        /* Hero Section */
        .hero-section {
            min-height: 100vh;
            background: linear-gradient(135deg, #1a5f2a 0%, #0d3d16 100%);
            display: flex;
            align-items: center;
            position: relative;
            padding-top: 80px;
            overflow: hidden;
        }

        .hero-section::before {
            content: '';
            position: absolute;
            top: 0;
            right: 0;
            width: 50%;
            height: 100%;
            background: url('https://images.unsplash.com/photo-1579621970563-ebec7560ff3e?ixlib=rb-4.0.3&auto=format&fit=crop&w=1000&q=80') center/cover;
            opacity: 0.3;
        }

        .hero-content {
            position: relative;
            z-index: 2;
            color: white;
        }

        .hero-badge {
            display: inline-block;
            background: rgba(212, 175, 55, 0.2);
            color: #d4af37;
            padding: 8px 20px;
            border-radius: 25px;
            font-size: 0.9rem;
            font-weight: 600;
            margin-bottom: 20px;
            border: 1px solid rgba(212, 175, 55, 0.3);
        }

        .hero-title {
            font-size: 3.5rem;
            font-weight: 800;
            line-height: 1.2;
            margin-bottom: 20px;
        }

        .hero-title .highlight {
            color: #d4af37;
        }

        .hero-subtitle {
            font-size: 1.2rem;
            opacity: 0.9;
            margin-bottom: 30px;
            max-width: 500px;
        }

        .hero-stats {
            display: flex;
            gap: 40px;
            margin-top: 40px;
        }

        .stat-item {
            text-align: center;
        }

        .stat-number {
            font-size: 2.5rem;
            font-weight: 800;
            color: #d4af37;
        }

        .stat-label {
            font-size: 0.9rem;
            opacity: 0.8;
        }

        .btn-hero {
            padding: 15px 40px;
            font-size: 1.1rem;
            font-weight: 600;
            border-radius: 30px;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 10px;
            transition: all 0.3s ease;
        }

        .btn-hero-primary {
            background: #d4af37;
            color: #1a5f2a;
        }

        .btn-hero-primary:hover {
            background: #e8c547;
            color: #1a5f2a;
            transform: translateY(-3px);
            box-shadow: 0 10px 30px rgba(212, 175, 55, 0.4);
            text-decoration: none;
        }

        .btn-hero-secondary {
            background: transparent;
            color: white;
            border: 2px solid rgba(255,255,255,0.5);
            margin-left: 15px;
        }

        .btn-hero-secondary:hover {
            background: rgba(255,255,255,0.1);
            color: white;
            text-decoration: none;
        }

        /* Money Image Section */
        .hero-image-box {
            position: relative;
            z-index: 2;
        }

        .money-image {
            width: 100%;
            max-width: 500px;
            border-radius: 20px;
            box-shadow: 0 30px 60px rgba(0,0,0,0.3);
        }

        .floating-card {
            position: absolute;
            background: white;
            padding: 20px;
            border-radius: 15px;
            box-shadow: 0 15px 40px rgba(0,0,0,0.2);
        }

        .floating-card.card-1 {
            bottom: 20px;
            left: -30px;
        }

        .floating-card.card-2 {
            top: 20px;
            right: -20px;
        }

        .floating-card i {
            font-size: 2rem;
            color: #1a5f2a;
        }

        .floating-card .amount {
            font-size: 1.5rem;
            font-weight: 700;
            color: #1a5f2a;
        }

        .floating-card .label {
            font-size: 0.8rem;
            color: #666;
        }

        /* Loan Types Section */
        .loan-types-section {
            padding: 100px 0;
            background: #f8f9fa;
        }

        .section-header {
            text-align: center;
            margin-bottom: 60px;
        }

        .section-header h2 {
            font-size: 2.8rem;
            font-weight: 700;
            color: #1a5f2a;
            margin-bottom: 15px;
        }

        .section-header p {
            font-size: 1.1rem;
            color: #666;
            max-width: 600px;
            margin: 0 auto;
        }

        .loan-card {
            background: white;
            border-radius: 20px;
            padding: 40px 30px;
            text-align: center;
            box-shadow: 0 10px 40px rgba(0,0,0,0.08);
            transition: all 0.3s ease;
            height: 100%;
            border: 2px solid transparent;
        }

        .loan-card:hover {
            transform: translateY(-10px);
            border-color: #1a5f2a;
        }

        .loan-card.featured {
            background: linear-gradient(135deg, #1a5f2a 0%, #2d8a3e 100%);
            color: white;
        }

        .loan-card.featured .loan-title,
        .loan-card.featured .loan-desc {
            color: white;
        }

        .loan-icon {
            width: 80px;
            height: 80px;
            background: rgba(26, 95, 42, 0.1);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 25px;
        }

        .loan-card.featured .loan-icon {
            background: rgba(255,255,255,0.2);
        }

        .loan-icon i {
            font-size: 2rem;
            color: #1a5f2a;
        }

        .loan-card.featured .loan-icon i {
            color: #d4af37;
        }

        .loan-title {
            font-size: 1.4rem;
            font-weight: 700;
            color: #333;
            margin-bottom: 15px;
        }

        .loan-desc {
            color: #666;
            margin-bottom: 20px;
        }

        .loan-rate {
            font-size: 2rem;
            font-weight: 800;
            color: #d4af37;
        }

        .loan-rate span {
            font-size: 1rem;
            font-weight: 400;
        }

        /* Why Choose Us */
        .why-us-section {
            padding: 100px 0;
            background: white;
        }

        .why-card {
            display: flex;
            gap: 20px;
            margin-bottom: 30px;
        }

        .why-icon {
            width: 60px;
            height: 60px;
            background: linear-gradient(135deg, #1a5f2a 0%, #2d8a3e 100%);
            border-radius: 15px;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
        }

        .why-icon i {
            font-size: 1.5rem;
            color: white;
        }

        .why-content h4 {
            font-size: 1.2rem;
            font-weight: 600;
            color: #333;
            margin-bottom: 8px;
        }

        .why-content p {
            color: #666;
            margin: 0;
        }

        .kwacha-showcase {
            position: relative;
        }

        .kwacha-image {
            width: 100%;
            border-radius: 20px;
            box-shadow: 0 20px 50px rgba(0,0,0,0.15);
        }

        /* How It Works */
        .how-it-works {
            padding: 100px 0;
            background: linear-gradient(135deg, #1a5f2a 0%, #0d3d16 100%);
            color: white;
        }

        .how-it-works .section-header h2 {
            color: white;
        }

        .how-it-works .section-header p {
            color: rgba(255,255,255,0.8);
        }

        .step-card {
            text-align: center;
            padding: 30px;
        }

        .step-number {
            width: 70px;
            height: 70px;
            background: #d4af37;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.8rem;
            font-weight: 800;
            color: #1a5f2a;
            margin: 0 auto 25px;
        }

        .step-card h4 {
            font-size: 1.3rem;
            font-weight: 600;
            margin-bottom: 15px;
        }

        .step-card p {
            opacity: 0.8;
        }

        /* CTA Section */
        .cta-section {
            padding: 80px 0;
            background: #f8f9fa;
            text-align: center;
        }

        .cta-section h2 {
            font-size: 2.5rem;
            font-weight: 700;
            color: #1a5f2a;
            margin-bottom: 20px;
        }

        .cta-section p {
            font-size: 1.2rem;
            color: #666;
            margin-bottom: 30px;
        }

        .btn-cta {
            background: linear-gradient(135deg, #1a5f2a 0%, #2d8a3e 100%);
            color: white;
            padding: 18px 50px;
            font-size: 1.2rem;
            font-weight: 600;
            border-radius: 30px;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 10px;
            transition: all 0.3s ease;
        }

        .btn-cta:hover {
            transform: translateY(-3px);
            box-shadow: 0 15px 40px rgba(26, 95, 42, 0.4);
            color: white;
            text-decoration: none;
        }

        /* Footer */
        .footer {
            background: #0d3d16;
            color: white;
            padding: 60px 0 30px;
        }

        .footer-logo {
            font-size: 2rem;
            font-weight: 800;
            margin-bottom: 15px;
        }

        .footer-logo span {
            color: #d4af37;
        }

        .footer-desc {
            opacity: 0.8;
            margin-bottom: 20px;
        }

        .footer-title {
            font-size: 1.2rem;
            font-weight: 600;
            margin-bottom: 20px;
            color: #d4af37;
        }

        .footer-links {
            list-style: none;
            padding: 0;
        }

        .footer-links li {
            margin-bottom: 10px;
        }

        .footer-links a {
            color: rgba(255,255,255,0.8);
            text-decoration: none;
            transition: color 0.3s;
        }

        .footer-links a:hover {
            color: #d4af37;
        }

        .footer-contact i {
            color: #d4af37;
            margin-right: 10px;
        }

        .footer-bottom {
            border-top: 1px solid rgba(255,255,255,0.1);
            padding-top: 30px;
            margin-top: 40px;
            text-align: center;
            opacity: 0.7;
        }

        /* Requirements Slideshow */
        .requirements-section {
            padding: 100px 0;
            background: linear-gradient(135deg, #d4af37 0%, #c9a227 100%);
        }

        .requirements-section .section-header h2 {
            color: #1a5f2a;
        }

        .requirements-section .section-header p {
            color: rgba(26, 95, 42, 0.8);
        }

        .requirement-card {
            background: white;
            border-radius: 25px;
            padding: 60px 40px;
            text-align: center;
            max-width: 500px;
            margin: 0 auto;
            box-shadow: 0 20px 60px rgba(0,0,0,0.2);
            position: relative;
        }

        .req-number {
            position: absolute;
            top: -25px;
            left: 50%;
            transform: translateX(-50%);
            width: 50px;
            height: 50px;
            background: #1a5f2a;
            color: white;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
            font-weight: 800;
            box-shadow: 0 5px 20px rgba(26, 95, 42, 0.4);
        }

        .req-icon {
            width: 100px;
            height: 100px;
            background: linear-gradient(135deg, #1a5f2a 0%, #2d8a3e 100%);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 25px;
        }

        .req-icon i {
            font-size: 2.5rem;
            color: white;
        }

        .requirement-card h3 {
            font-size: 1.8rem;
            font-weight: 700;
            color: #1a5f2a;
            margin-bottom: 15px;
        }

        .requirement-card p {
            color: #666;
            font-size: 1.1rem;
            line-height: 1.6;
        }

        .carousel-control-prev,
        .carousel-control-next {
            width: 60px;
            height: 60px;
            background: #1a5f2a;
            border-radius: 50%;
            top: 50%;
            transform: translateY(-50%);
            opacity: 1;
        }

        .carousel-control-prev {
            left: 10%;
        }

        .carousel-control-next {
            right: 10%;
        }

        .carousel-control-prev:hover,
        .carousel-control-next:hover {
            background: #0d3d16;
        }

        .carousel-indicators li {
            width: 12px;
            height: 12px;
            border-radius: 50%;
            background: rgba(26, 95, 42, 0.3);
            border: none;
            margin: 0 5px;
        }

        .carousel-indicators li.active {
            background: #1a5f2a;
        }

        .requirements-grid {
            display: flex;
            justify-content: center;
            flex-wrap: wrap;
            gap: 15px;
        }

        .req-grid-item {
            background: white;
            padding: 15px 25px;
            border-radius: 50px;
            display: flex;
            align-items: center;
            gap: 10px;
            box-shadow: 0 5px 20px rgba(0,0,0,0.1);
        }

        .req-grid-item i {
            color: #1a5f2a;
            font-size: 1.2rem;
        }

        .req-grid-item span {
            font-weight: 600;
            color: #333;
        }

        /* Responsive */
        @media (max-width: 768px) {
            .hero-title {
                font-size: 2.5rem;
            }

            .hero-stats {
                flex-wrap: wrap;
                gap: 20px;
            }

            .stat-item {
                width: 45%;
            }

            .btn-hero-secondary {
                margin-left: 0;
                margin-top: 15px;
            }

            .floating-card {
                display: none;
            }

            .section-header h2 {
                font-size: 2rem;
            }

            .carousel-control-prev,
            .carousel-control-next {
                display: none;
            }

            .requirement-card {
                padding: 50px 25px;
            }

            .requirements-grid {
                gap: 10px;
            }

            .req-grid-item {
                padding: 10px 15px;
                font-size: 0.9rem;
            }
        }
    </style>
</head>
<body>
    <!-- Navbar -->
    <nav class="navbar navbar-expand-lg navbar-custom">
        <div class="container">
            <a class="navbar-brand" href="#">Brian <span>Investments</span></a>
            <button class="navbar-toggler" type="button" data-toggle="collapse" data-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ml-auto">
                    <li class="nav-item"><a class="nav-link" href="#loans">Loan Types</a></li>
                    <li class="nav-item"><a class="nav-link" href="#requirements">Requirements</a></li>
                    <li class="nav-item"><a class="nav-link" href="#why-us">Why Us</a></li>
                    <li class="nav-item"><a class="nav-link" href="#how-it-works">How It Works</a></li>
                    <li class="nav-item"><a class="nav-link" href="customer_login.php">Login</a></li>
                    <li class="nav-item"><a class="nav-link btn-apply-nav" href="customer_register.php">Apply Now</a></li>
                </ul>
            </div>
        </div>
    </nav>

    <!-- Hero Section -->
    <section class="hero-section">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-lg-6 hero-content">
                    <div class="hero-badge">
                        <i class="fas fa-star"></i> Trusted by 1000+ Zambians
                    </div>
                    <h1 class="hero-title">
                        Get Quick <span class="highlight">Cash Loans</span> in Zambia
                    </h1>
                    <p class="hero-subtitle">
                        Need money fast? We offer affordable personal loans with low interest rates and flexible repayment options. Get approved within 24 hours!
                    </p>
                    <div>
                        <a href="customer_register.php" class="btn-hero btn-hero-primary">
                            <i class="fas fa-paper-plane"></i> Apply for Loan
                        </a>
                        <a href="customer_login.php" class="btn-hero btn-hero-secondary">
                            <i class="fas fa-sign-in-alt"></i> Customer Login
                        </a>
                    </div>
                    <div class="hero-stats">
                        <div class="stat-item">
                            <div class="stat-number">K50,000+</div>
                            <div class="stat-label">Maximum Loan</div>
                        </div>
                        <div class="stat-item">
                            <div class="stat-number">18%</div>
                            <div class="stat-label">Interest Rate</div>
                        </div>
                        <div class="stat-item">
                            <div class="stat-number">24hrs</div>
                            <div class="stat-label">Fast Approval</div>
                        </div>
                    </div>
                </div>
                <div class="col-lg-6 hero-image-box d-none d-lg-block">
                    <!-- REPLACE THIS IMAGE: Add your own Kwacha money image -->
                    <!-- Upload image to: assets/img/kwacha-hero.jpg -->
                    <img src="https://images.unsplash.com/photo-1621981386829-9b458a2cddde?ixlib=rb-4.0.3&auto=format&fit=crop&w=800&q=80" alt="Cash Loans Zambia" class="money-image">
                    <div class="floating-card card-1">
                        <i class="fas fa-check-circle"></i>
                        <div class="amount">K5,000</div>
                        <div class="label">Loan Approved!</div>
                    </div>
                    <div class="floating-card card-2">
                        <i class="fas fa-clock"></i>
                        <div class="amount">24 hrs</div>
                        <div class="label">Quick Processing</div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Loan Types Section -->
    <section class="loan-types-section" id="loans">
        <div class="container">
            <div class="section-header">
                <h2>Our Loan Products</h2>
                <p>Choose the loan that fits your needs. Simple, transparent, and affordable.</p>
            </div>
            <div class="row">
                <div class="col-md-4 mb-4">
                    <div class="loan-card">
                        <div class="loan-icon">
                            <i class="fas fa-user"></i>
                        </div>
                        <h3 class="loan-title">Personal Loans</h3>
                        <p class="loan-desc">For personal expenses, emergencies, or any purpose you need. Quick and easy application.</p>
                        <div class="loan-rate">18% <span>interest</span></div>
                        <p class="mt-2 text-muted">1 month term</p>
                    </div>
                </div>
                <div class="col-md-4 mb-4">
                    <div class="loan-card featured">
                        <div class="loan-icon">
                            <i class="fas fa-briefcase"></i>
                        </div>
                        <h3 class="loan-title">Business Loans</h3>
                        <p class="loan-desc">Grow your business with affordable capital. Flexible terms for small businesses.</p>
                        <div class="loan-rate">From 10% <span>interest</span></div>
                        <p class="mt-2" style="opacity: 0.8">Multi-month terms</p>
                    </div>
                </div>
                <div class="col-md-4 mb-4">
                    <div class="loan-card">
                        <div class="loan-icon">
                            <i class="fas fa-graduation-cap"></i>
                        </div>
                        <h3 class="loan-title">Student Loans</h3>
                        <p class="loan-desc">Invest in education. Cover school fees, books, and other educational expenses.</p>
                        <div class="loan-rate">From 10% <span>interest</span></div>
                        <p class="mt-2 text-muted">Flexible terms</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Requirements Slideshow -->
    <section class="requirements-section" id="requirements">
        <div class="container">
            <div class="section-header">
                <h2>Loan Requirements</h2>
                <p>What you need to apply for a loan at Brian Investments</p>
            </div>

            <div id="requirementsCarousel" class="carousel slide" data-ride="carousel" data-interval="3000">
                <ol class="carousel-indicators">
                    <li data-target="#requirementsCarousel" data-slide-to="0" class="active"></li>
                    <li data-target="#requirementsCarousel" data-slide-to="1"></li>
                    <li data-target="#requirementsCarousel" data-slide-to="2"></li>
                    <li data-target="#requirementsCarousel" data-slide-to="3"></li>
                    <li data-target="#requirementsCarousel" data-slide-to="4"></li>
                </ol>

                <div class="carousel-inner">
                    <div class="carousel-item active">
                        <div class="requirement-card">
                            <div class="req-number">1</div>
                            <div class="req-icon">
                                <i class="fas fa-briefcase"></i>
                            </div>
                            <h3>Formally Employed</h3>
                            <p>You must be currently employed with a registered company or organization in Zambia</p>
                        </div>
                    </div>

                    <div class="carousel-item">
                        <div class="requirement-card">
                            <div class="req-number">2</div>
                            <div class="req-icon">
                                <i class="fas fa-file-invoice-dollar"></i>
                            </div>
                            <h3>Latest Payslip</h3>
                            <p>Provide your most recent payslip as proof of income and employment</p>
                        </div>
                    </div>

                    <div class="carousel-item">
                        <div class="requirement-card">
                            <div class="req-number">3</div>
                            <div class="req-icon">
                                <i class="fas fa-university"></i>
                            </div>
                            <h3>Bank Statement</h3>
                            <p>Submit your latest month's bank statement showing your transaction history</p>
                        </div>
                    </div>

                    <div class="carousel-item">
                        <div class="requirement-card">
                            <div class="req-number">4</div>
                            <div class="req-icon">
                                <i class="fas fa-id-card"></i>
                            </div>
                            <h3>Original NRC</h3>
                            <p>Valid National Registration Card (NRC) for identity verification</p>
                        </div>
                    </div>

                    <div class="carousel-item">
                        <div class="requirement-card">
                            <div class="req-number">5</div>
                            <div class="req-icon">
                                <i class="fas fa-mobile-alt"></i>
                            </div>
                            <h3>Active Phone Number</h3>
                            <p>A working mobile number for communication and verification purposes</p>
                        </div>
                    </div>
                </div>

                <a class="carousel-control-prev" href="#requirementsCarousel" role="button" data-slide="prev">
                    <span class="carousel-control-prev-icon" aria-hidden="true"></span>
                </a>
                <a class="carousel-control-next" href="#requirementsCarousel" role="button" data-slide="next">
                    <span class="carousel-control-next-icon" aria-hidden="true"></span>
                </a>
            </div>

            <!-- Requirements Grid (Always visible) -->
            <div class="requirements-grid mt-5">
                <div class="req-grid-item">
                    <i class="fas fa-check-circle"></i>
                    <span>Formally Employed</span>
                </div>
                <div class="req-grid-item">
                    <i class="fas fa-check-circle"></i>
                    <span>Latest Payslip</span>
                </div>
                <div class="req-grid-item">
                    <i class="fas fa-check-circle"></i>
                    <span>Bank Statement</span>
                </div>
                <div class="req-grid-item">
                    <i class="fas fa-check-circle"></i>
                    <span>Original NRC</span>
                </div>
                <div class="req-grid-item">
                    <i class="fas fa-check-circle"></i>
                    <span>Active Phone</span>
                </div>
            </div>
        </div>
    </section>

    <!-- Why Choose Us -->
    <section class="why-us-section" id="why-us">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-lg-6 mb-4">
                    <div class="section-header text-left">
                        <h2>Why Choose Brian Investments?</h2>
                        <p>We're committed to helping Zambians achieve their financial goals with trusted loan services.</p>
                    </div>

                    <div class="why-card">
                        <div class="why-icon">
                            <i class="fas fa-bolt"></i>
                        </div>
                        <div class="why-content">
                            <h4>Fast Approval</h4>
                            <p>Get your loan approved within 24 hours. No long waiting times.</p>
                        </div>
                    </div>

                    <div class="why-card">
                        <div class="why-icon">
                            <i class="fas fa-percentage"></i>
                        </div>
                        <div class="why-content">
                            <h4>Low Interest Rates</h4>
                            <p>Competitive rates starting from 10% for multi-month loans.</p>
                        </div>
                    </div>

                    <div class="why-card">
                        <div class="why-icon">
                            <i class="fas fa-file-alt"></i>
                        </div>
                        <div class="why-content">
                            <h4>Minimal Paperwork</h4>
                            <p>Simple online application. Just ID, employment proof, and payslip.</p>
                        </div>
                    </div>

                    <div class="why-card">
                        <div class="why-icon">
                            <i class="fas fa-shield-alt"></i>
                        </div>
                        <div class="why-content">
                            <h4>Secure & Trusted</h4>
                            <p>Your data is protected with bank-level security.</p>
                        </div>
                    </div>
                </div>
                <div class="col-lg-6 kwacha-showcase">
                    <!-- REPLACE THIS IMAGE: Add your own Kwacha money image -->
                    <!-- Upload image to: assets/img/kwacha-money.jpg -->
                    <img src="https://images.unsplash.com/photo-1554768804-50c1e2b50a6e?ixlib=rb-4.0.3&auto=format&fit=crop&w=800&q=80" alt="Zambian Kwacha" class="kwacha-image">
                </div>
            </div>
        </div>
    </section>

    <!-- How It Works -->
    <section class="how-it-works" id="how-it-works">
        <div class="container">
            <div class="section-header">
                <h2>How It Works</h2>
                <p>Getting a loan is easy. Just follow these simple steps.</p>
            </div>
            <div class="row">
                <div class="col-md-3">
                    <div class="step-card">
                        <div class="step-number">1</div>
                        <h4>Register Online</h4>
                        <p>Create your account in just 2 minutes with basic information.</p>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="step-card">
                        <div class="step-number">2</div>
                        <h4>Upload Documents</h4>
                        <p>Submit your ID, employment proof, and recent payslip.</p>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="step-card">
                        <div class="step-number">3</div>
                        <h4>Apply for Loan</h4>
                        <p>Choose your loan amount and repayment term.</p>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="step-card">
                        <div class="step-number">4</div>
                        <h4>Get Your Money</h4>
                        <p>Once approved, receive cash directly to your account.</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- CTA Section -->
    <section class="cta-section">
        <div class="container">
            <h2>Ready to Get Started?</h2>
            <p>Join thousands of Zambians who trust Brian Investments for their financial needs.</p>
            <a href="customer_register.php" class="btn-cta">
                <i class="fas fa-paper-plane"></i> Apply for a Loan Today
            </a>
            <p class="mt-4 text-muted">
                <i class="fas fa-lock"></i> Your information is safe and secure with us
            </p>
        </div>
    </section>

    <!-- Footer -->
    <footer class="footer">
        <div class="container">
            <div class="row">
                <div class="col-lg-4 mb-4">
                    <div class="footer-logo">Brian <span>Investments</span></div>
                    <p class="footer-desc">Providing reliable and affordable loan services to the people of Zambia since 2020.</p>
                    <div class="social-links">
                        <a href="#" class="text-white mr-3"><i class="fab fa-facebook fa-lg"></i></a>
                        <a href="#" class="text-white mr-3"><i class="fab fa-whatsapp fa-lg"></i></a>
                        <a href="#" class="text-white"><i class="fab fa-instagram fa-lg"></i></a>
                    </div>
                </div>
                <div class="col-lg-2 col-md-4 mb-4">
                    <h5 class="footer-title">Quick Links</h5>
                    <ul class="footer-links">
                        <li><a href="customer_register.php">Apply for Loan</a></li>
                        <li><a href="customer_login.php">Customer Login</a></li>
                        <li><a href="#loans">Loan Products</a></li>
                        <li><a href="#how-it-works">How It Works</a></li>
                    </ul>
                </div>
                <div class="col-lg-3 col-md-4 mb-4">
                    <h5 class="footer-title">Loan Types</h5>
                    <ul class="footer-links">
                        <li><a href="#">Personal Loans</a></li>
                        <li><a href="#">Business Loans</a></li>
                        <li><a href="#">Student Loans</a></li>
                        <li><a href="#">Emergency Loans</a></li>
                    </ul>
                </div>
                <div class="col-lg-3 col-md-4 mb-4">
                    <h5 class="footer-title">Contact Us</h5>
                    <div class="footer-contact">
                        <p><i class="fas fa-map-marker-alt"></i> Lusaka, Zambia</p>
                        <p><i class="fas fa-phone"></i> +260 XXX XXX XXX</p>
                        <p><i class="fas fa-envelope"></i> info@brianinvestments.com</p>
                    </div>
                </div>
            </div>
            <div class="footer-bottom">
                <p>&copy; 2026 Brian Investments. All rights reserved. | Registered in Zambia</p>
            </div>
        </div>
    </footer>

    <!-- Admin Link (Hidden) -->
    <div class="text-center py-2 bg-dark">
        <a href="login.php" class="text-muted small">Staff Portal</a>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@4.5.2/dist/js/bootstrap.bundle.min.js"></script>

    <script>
        // Smooth scroll for anchor links
        document.querySelectorAll('a[href^="#"]').forEach(anchor => {
            anchor.addEventListener('click', function (e) {
                e.preventDefault();
                const target = document.querySelector(this.getAttribute('href'));
                if (target) {
                    target.scrollIntoView({ behavior: 'smooth', block: 'start' });
                }
            });
        });

        // Navbar background on scroll
        window.addEventListener('scroll', function() {
            const navbar = document.querySelector('.navbar-custom');
            if (window.scrollY > 50) {
                navbar.style.boxShadow = '0 2px 30px rgba(0,0,0,0.15)';
            } else {
                navbar.style.boxShadow = '0 2px 20px rgba(0,0,0,0.1)';
            }
        });
    </script>
</body>
</html>
