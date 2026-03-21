<?php
// blocknet/download.php
require_once 'config/db.php';
require_once 'includes/functions.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Download BLOCKNET APK</title>
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&family=Space+Grotesk:wght@600;700;800&display=swap" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Favicon -->
    <link rel="icon" type="image/png" href="<?= BASE_URL?>public/Block.png">
    <style>
        :root {
            --bg-color: #000000;
            --bg-secondary: #0a0a0a;
            --primary: #ffffff;
            --primary-hover: #e5e5e5;
            --text: #ffffff;
            --text-muted: #888888;
            --border: #333333;
        }

        body {
            background-color: var(--bg-color);
            color: var(--text);
            font-family: 'Inter', sans-serif;
            margin: 0;
            padding: 0;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .container {
            width: 100%;
            max-width: 500px;
            padding: 2rem;
            text-align: center;
        }

        .logo {
            font-family: 'Space Grotesk', sans-serif;
            font-size: 2.5rem;
            font-weight: 800;
            letter-spacing: -0.05em;
            color: #ffffff;
            margin-bottom: 2rem;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
        }

        .logo span {
            color: var(--primary);
        }

        .card {
            background: var(--bg-secondary);
            border: 1px solid var(--border);
            border-radius: 24px;
            padding: 2.5rem 2rem;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.5);
            position: relative;
            overflow: hidden;
        }

        .card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: var(--primary);
        }

        .hero-icon {
            font-size: 3.5rem;
            color: var(--primary);
            margin-bottom: 1rem;
            background: rgba(255, 255, 255, 0.05);
            width: 90px;
            height: 90px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            border-radius: 50%;
            border: 1px solid rgba(255, 255, 255, 0.15);
        }

        h1 {
            font-size: 1.8rem;
            margin: 0 0 1rem 0;
            font-weight: 700;
            font-family: 'Space Grotesk', sans-serif;
        }

        p.desc {
            color: var(--text-muted);
            font-size: 1.05rem;
            line-height: 1.6;
            margin: 0 0 2rem 0;
        }

        .btn-download {
            background: var(--primary);
            color: #000000;
            font-size: 1.1rem;
            font-weight: 600;
            text-decoration: none;
            padding: 1rem 2rem;
            border-radius: 12px;
            display: inline-flex;
            align-items: center;
            gap: 0.75rem;
            width: 100%;
            justify-content: center;
            transition: all 0.2s ease;
            border: none;
            box-sizing: border-box;
            cursor: pointer;
        }

        .btn-download:hover {
            background: var(--primary-hover);
            transform: translateY(-2px);
            box-shadow: 0 10px 20px -10px rgba(255,255,255,0.3);
        }

        .qr-section {
            margin-top: 2rem;
            padding-top: 2rem;
            border-top: 1px solid var(--border);
        }

        .qr-code {
            width: 160px;
            height: 160px;
            background: #ffffff;
            padding: 10px;
            border-radius: 16px;
            margin: 0 auto 1rem auto;
            border: 2px solid var(--border);
        }

        .qr-code img {
            width: 100%;
            height: 100%;
            object-fit: contain;
            border-radius: 8px;
        }

        .qr-text {
            font-size: 0.9rem;
            color: var(--text-muted);
            margin: 0;
        }

        /* Features List */
        .features {
            text-align: left;
            margin-bottom: 2.5rem;
            background: rgba(255,255,255,0.03);
            border-radius: 16px;
            padding: 1.5rem;
            border: 1px solid var(--border);
        }

        .feature-item {
            display: flex;
            align-items: center;
            gap: 1rem;
            margin-bottom: 1rem;
        }

        .feature-item:last-child {
            margin-bottom: 0;
        }

        .feature-item i {
            color: var(--primary);
            font-size: 1.2rem;
            width: 24px;
            text-align: center;
        }

        .feature-item span {
            font-size: 0.95rem;
            font-weight: 500;
        }

        @media (max-width: 480px) {
            .container {
                padding: 1rem;
            }
            .card {
                padding: 2rem 1.5rem;
            }
            .logo {
                font-size: 2rem;
            }
        }
    </style>
</head>
<body>

    <div class="container">
        <div class="logo flex-col" style="display: flex; flex-direction: column; align-items: center;">
            <img src="<?= BASE_URL?>public/Block.png" alt="BLOCKNET Logo"
                        style="width: 4.5rem; height: 4.5rem; object-fit: contain; margin-bottom: 0.5rem; filter: grayscale(100%) brightness(200%);">
            <div style="font-family: 'Space Grotesk', sans-serif; font-size: 1.5rem; font-weight: 800; letter-spacing: 0.15em; text-transform: uppercase;">
                BLOCKNET
            </div>
        </div>

        <div class="card">
            <div class="hero-icon">
                <i class="fa-brands fa-android"></i>
            </div>
            
            <h1>Get the BLOCKNET App</h1>
            <p class="desc">Connect with interest-based communities on the go. Faster, smoother, and optimized for your Android device.</p>

            <div class="features">
                <div class="feature-item">
                    <i class="fa-solid fa-bolt"></i>
                    <span>Native performance and speed</span>
                </div>
                <div class="feature-item">
                    <i class="fa-solid fa-bell"></i>
                    <span>Real-time push notifications</span>
                </div>
                <div class="feature-item">
                    <i class="fa-solid fa-shield-halved"></i>
                    <span>Secure & fully encrypted</span>
                </div>
            </div>

            <a href="https://median.co/share/bnlzkxj#apk" class="btn-download" target="_blank" rel="noopener noreferrer">
                <i class="fa-solid fa-download"></i> Download APK Now
            </a>

            <div class="qr-section">
                <div class="qr-code">
                    <img src="<?= BASE_URL ?>public/blocknetapkdownloadqr.png" alt="Scan to download">
                </div>
                <p class="qr-text">Scan via mobile device to download directly</p>
            </div>

            <div style="margin-top: 1.5rem; border-top: 1px solid var(--border); padding-top: 1.5rem;">
                <a href="<?= BASE_URL ?>index.php" style="color: var(--text-muted); text-decoration: none; font-size: 0.95rem; font-weight: 500; display: inline-flex; align-items: center; gap: 0.5rem; transition: color 0.2s;" onmouseover="this.style.color='var(--primary)'" onmouseout="this.style.color='var(--text-muted)'">
                    <i class="fa-solid fa-arrow-left"></i> Return to Main Site
                </a>
            </div>
        </div>
    </div>

</body>
</html>
