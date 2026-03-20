<?php
require_once '../includes/functions.php';
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Terms and Conditions - BLOCKNET</title>
    <link
        href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&family=Space+Grotesk:wght@400;500;600;700;800&display=swap"
        rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="public/css/style.css">
    <link rel="icon" type="image/png" href="public/Block.png">
    <style>
        body {
            background: #080808;
            color: #efefef;
            line-height: 1.6;
        }

        .terms-container {
            max-width: 800px;
            margin: 4rem auto;
            padding: 2.5rem;
            background: rgba(255, 255, 255, 0.03);
            border: 1px solid rgba(255, 255, 255, 0.08);
            border-radius: 20px;
            backdrop-filter: blur(10px);
        }

        h1 {
            font-family: 'Space Grotesk', sans-serif;
            font-size: 2.5rem;
            font-weight: 800;
            margin-bottom: 2rem;
            background: linear-gradient(135deg, #fff 0%, #888 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        h2 {
            font-family: 'Space Grotesk', sans-serif;
            font-size: 1.5rem;
            font-weight: 700;
            margin-top: 2rem;
            margin-bottom: 1rem;
            color: #fff;
        }

        p {
            margin-bottom: 1.25rem;
            color: rgba(255, 255, 255, 0.7);
        }

        .back-link {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            color: var(--primary);
            text-decoration: none;
            font-weight: 600;
            margin-bottom: 2rem;
            transition: all 0.2s;
        }

        .back-link:hover {
            opacity: 0.8;
            transform: translateX(-4px);
        }
    </style>
</head>

<body>
    <div class="terms-container">
        <a href="../index.php" class="back-link">
            <i class="fa-solid fa-arrow-left"></i>
            Go Back
        </a>
        <h1>Terms and Conditions</h1>

        <p>Welcome to BLOCKNET. By accessing or using our platform, you agree to comply with and be bound by the following
            terms and conditions.</p>

        <h2>1. Acceptance of Terms</h2>
        <p>By creating an account on BLOCKNET, you agree to these Terms and Conditions and our Privacy Policy. If you do
            not agree, please do not use our services.</p>

        <h2>2. User Conduct</h2>
        <p>Users are responsible for their own content and interactions. Harassment, hate speech, and illegal activities
            are strictly prohibited. We reserve the right to terminate accounts that violate these rules.</p>

        <h2>3. Account Security</h2>
        <p>You are responsible for maintaining the confidentiality of your account credentials. BLOCKNET is not liable for
            any loss resulting from unauthorized access to your account.</p>

        <h2>4. Community Standards</h2>
        <p>Our goal is to foster a positive, interest-based community. Please treat other members with respect. Content
            that is deemed inappropriate may be removed without notice.</p>

        <h2>5. Limitation of Liability</h2>
        <p>BLOCKNET provides the service "as is" without any warranties. We are not responsible for any damages resulting
            from the use or inability to use the platform.</p>

        <h2>6. Changes to Terms</h2>
        <p>We may update these terms from time to time. Continued use of the platform after changes constitutes
            acceptance of the new terms.</p>

        <div
            style="margin-top: 3rem; padding-top: 2rem; border-top: 1px solid rgba(255,255,255,0.1); text-align: center;">
            <p style="font-size: 0.9rem;">Last Updated:
                <?php echo date('F j, Y'); ?>
            </p>
        </div>
    </div>
</body>

</html>