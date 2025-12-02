<?php
// Error handling for production
error_reporting(0);
ini_set('display_errors', 0);

$lang = isset($_GET['lang']) ? $_GET['lang'] : 'en';
?>
<!DOCTYPE html>
<html lang="<?php echo $lang; ?>">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SmartShop - Modern Point of Sale System</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', sans-serif;
            line-height: 1.6;
            color: #333;
        }

        .hero {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            position: relative;
            overflow: hidden;
        }

        .hero::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100"><defs><pattern id="grid" width="10" height="10" patternUnits="userSpaceOnUse"><path d="M 10 0 L 0 0 0 10" fill="none" stroke="rgba(255,255,255,0.1)" stroke-width="0.5"/></pattern></defs><rect width="100" height="100" fill="url(%23grid)"/></svg>');
            opacity: 0.3;
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 2rem;
            position: relative;
            z-index: 2;
        }

        .hero-content {
            text-align: center;
            color: white;
        }

        .brand {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 20px;
            margin-bottom: 2rem;
        }

        .brand i {
            font-size: 4rem;
        }

        .brand h1 {
            font-size: 4rem;
            font-weight: 700;
        }

        .hero h2 {
            font-size: 2.5rem;
            font-weight: 600;
            margin-bottom: 1rem;
        }

        .hero p {
            font-size: 1.2rem;
            margin-bottom: 3rem;
            opacity: 0.9;
            max-width: 600px;
            margin-left: auto;
            margin-right: auto;
        }

        .cta-buttons {
            display: flex;
            gap: 1rem;
            justify-content: center;
            flex-wrap: wrap;
        }

        .btn {
            padding: 14px 28px;
            border-radius: 10px;
            text-decoration: none;
            font-weight: 600;
            font-size: 16px;
            transition: all 0.3s ease;
            display: inline-flex;
            align-items: center;
            gap: 10px;
        }

        .btn-primary {
            background: rgba(255, 255, 255, 0.2);
            color: white;
            border: 2px solid rgba(255, 255, 255, 0.3);
            backdrop-filter: blur(10px);
        }

        .btn-primary:hover {
            background: rgba(255, 255, 255, 0.3);
            transform: translateY(-2px);
        }

        .btn-secondary {
            background: white;
            color: #667eea;
            border: 2px solid white;
        }

        .btn-secondary:hover {
            background: transparent;
            color: white;
            transform: translateY(-2px);
        }

        .features {
            padding: 5rem 0;
            background: #f8fafc;
        }

        .features h2 {
            text-align: center;
            font-size: 2.5rem;
            margin-bottom: 3rem;
            color: #2d3748;
        }

        .features-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 2rem;
        }

        .feature-card {
            background: white;
            padding: 2rem;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
            text-align: center;
            transition: transform 0.3s ease;
        }

        .feature-card:hover {
            transform: translateY(-5px);
        }

        .feature-card i {
            font-size: 3rem;
            color: #667eea;
            margin-bottom: 1rem;
        }

        .feature-card h3 {
            font-size: 1.5rem;
            margin-bottom: 1rem;
            color: #2d3748;
        }

        .feature-card p {
            color: #718096;
            line-height: 1.6;
        }

        .language-selector {
            position: fixed;
            top: 20px;
            right: 20px;
            z-index: 1000;
        }

        .language-selector select {
            background: rgba(255, 255, 255, 0.9);
            border: none;
            padding: 8px 12px;
            border-radius: 8px;
            font-size: 14px;
            cursor: pointer;
            backdrop-filter: blur(10px);
        }

        @media (max-width: 768px) {
            .brand h1 {
                font-size: 2.5rem;
            }

            .brand i {
                font-size: 2.5rem;
            }

            .hero h2 {
                font-size: 2rem;
            }

            .hero p {
                font-size: 1.1rem;
            }

            .cta-buttons {
                flex-direction: column;
                align-items: center;
            }

            .container {
                padding: 0 1rem;
            }
        }
    </style>
</head>

<body>
    <div class="language-selector">
        <select onchange="changeLanguage(this.value)">
            <option value="en" <?php echo $lang === 'en' ? 'selected' : ''; ?>>🇺🇸 English</option>
            <option value="rw" <?php echo $lang === 'rw' ? 'selected' : ''; ?>>🇷🇼 Kinyarwanda</option>
        </select>
    </div>

    <section class="hero">
        <div class="container">
            <div class="hero-content">
                <div class="brand">
                    <i class="fas fa-shopping-cart"></i>
                    <h1>SmartShop</h1>
                </div>
                <h2>Modern Point of Sale System</h2>
                <p>Streamline your retail operations with our comprehensive POS solution. Manage inventory, process sales, track customers, and generate detailed reports - all in one powerful platform.</p>
                <div class="cta-buttons">
                    <a href="app/views/auth/login.php?lang=<?php echo $lang; ?>" class="btn btn-primary">
                        <i class="fas fa-sign-in-alt"></i>
                        Sign In
                    </a>
                    <a href="app/views/auth/register.php?lang=<?php echo $lang; ?>" class="btn btn-secondary">
                        <i class="fas fa-user-plus"></i>
                        Get Started
                    </a>
                </div>

            </div>
        </div>
    </section>

    <section class="features">
        <div class="container">
            <h2>Powerful Features for Your Business</h2>
            <div class="features-grid">
                <div class="feature-card">
                    <i class="fas fa-cash-register"></i>
                    <h3>Fast Sales Processing</h3>
                    <p>Lightning-fast checkout process with barcode scanning, multiple payment methods, and instant receipt generation.</p>
                </div>
                <div class="feature-card">
                    <i class="fas fa-box"></i>
                    <h3>Inventory Management</h3>
                    <p>Real-time stock tracking, low stock alerts, automated reordering, and comprehensive product management.</p>
                </div>
                <div class="feature-card">
                    <i class="fas fa-users"></i>
                    <h3>Customer Management</h3>
                    <p>Customer profiles, purchase history, loyalty programs, and personalized service capabilities.</p>
                </div>
                <div class="feature-card">
                    <i class="fas fa-chart-line"></i>
                    <h3>Analytics & Reports</h3>
                    <p>Detailed sales reports, performance analytics, trend analysis, and business intelligence insights.</p>
                </div>
                <div class="feature-card">
                    <i class="fas fa-undo"></i>
                    <h3>Returns & Exchanges</h3>
                    <p>Streamlined return processing with reason tracking, refund management, and exchange handling.</p>
                </div>
                <div class="feature-card">
                    <i class="fas fa-user-friends"></i>
                    <h3>Multi-User Support</h3>
                    <p>Role-based access control, staff management, shift tracking, and performance monitoring.</p>
                </div>
            </div>
        </div>
    </section>

    <script>
        function changeLanguage(lang) {
            window.location.href = '?lang=' + lang;
        }
    </script>
</body>

</html>