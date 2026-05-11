<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>OptimaFlow - Login or Sign Up</title>
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@700;900&family=DM+Sans:wght@300;400;500&display=swap" rel="stylesheet">
    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

        :root {
            --red:    #2B5EAB;
            --red-dk: #1A3F7A;
            --gold:   #C9A84C;
            --cream:  #F5F0DC;
            --dark:   #0E1F3D;
            --mid:    #2C3A5E;
        }

        body {
            font-family: 'DM Sans', sans-serif;
            background: var(--cream);
            color: var(--dark);
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            overflow-x: hidden;
        }

        /* ── NAV ── */
        nav {
            position: fixed;
            top: 0; left: 0; right: 0;
            z-index: 100;
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 1.2rem 2.5rem;
            background: rgba(245,240,220,0.88);
            backdrop-filter: blur(12px);
            border-bottom: 1px solid rgba(43,94,171,0.12);
        }

        .brand {
            font-family: 'Playfair Display', serif;
            font-size: 1.5rem;
            font-weight: 900;
            color: var(--red);
            letter-spacing: -0.02em;
            text-decoration: none;
        }

        .brand span { color: var(--gold); }

        .nav-tagline {
            font-size: 0.72rem;
            font-weight: 500;
            letter-spacing: 0.18em;
            text-transform: uppercase;
            color: var(--mid);
            opacity: 0.6;
        }

        /* ── HERO ── */
        header {
            flex: 1;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 7rem 2rem 4rem;
            position: relative;
            overflow: hidden;
            min-height: 100vh;
        }

        /* textured warm background */
        header::before {
            content: '';
            position: absolute;
            inset: 0;
            background:
                radial-gradient(ellipse 80% 60% at 70% 30%, rgba(43,94,171,0.12) 0%, transparent 70%),
                radial-gradient(ellipse 60% 50% at 20% 80%, rgba(201,168,76,0.16) 0%, transparent 60%);
            pointer-events: none;
        }

        /* decorative large circle */
        header::after {
            content: '';
            position: absolute;
            width: 700px; height: 700px;
            border-radius: 50%;
            border: 1.5px solid rgba(43,94,171,0.1);
            right: -200px; top: 50%;
            transform: translateY(-50%);
            pointer-events: none;
        }

        .hero-inner {
            position: relative;
            z-index: 2;
            max-width: 780px;
            text-align: center;
        }

        .hero-label {
            display: inline-block;
            font-size: 0.7rem;
            font-weight: 500;
            letter-spacing: 0.22em;
            text-transform: uppercase;
            color: var(--red);
            background: rgba(43,94,171,0.08);
            border: 1px solid rgba(43,94,171,0.2);
            border-radius: 999px;
            padding: 0.35rem 1rem;
            margin-bottom: 1.8rem;
            animation: fadeUp 0.6s ease both;
        }

        h1 {
            font-family: 'Playfair Display', serif;
            font-size: clamp(2.8rem, 7vw, 5.5rem);
            font-weight: 900;
            line-height: 1.05;
            color: var(--dark);
            margin-bottom: 1.4rem;
            animation: fadeUp 0.7s 0.1s ease both;
        }

        h1 em {
            font-style: normal;
            color: var(--red);
            position: relative;
        }

        /* underline squiggle */
        h1 em::after {
            content: '';
            position: absolute;
            left: 0; right: 0;
            bottom: -6px;
            height: 4px;
            background: var(--gold);
            border-radius: 2px;
            transform: scaleX(0);
            transform-origin: left;
            animation: growLine 0.5s 0.9s ease forwards;
        }

        .hero-sub {
            font-size: 1.05rem;
            font-weight: 300;
            color: var(--mid);
            line-height: 1.7;
            max-width: 480px;
            margin: 0 auto 2.8rem;
            animation: fadeUp 0.7s 0.2s ease both;
        }

        .btn-group {
            display: flex;
            gap: 1rem;
            justify-content: center;
            flex-wrap: wrap;
            animation: fadeUp 0.7s 0.35s ease both;
        }

        .btn {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            font-family: 'DM Sans', sans-serif;
            font-size: 0.9rem;
            font-weight: 500;
            letter-spacing: 0.03em;
            padding: 0.85rem 2rem;
            border-radius: 6px;
            border: 2px solid transparent;
            cursor: pointer;
            text-decoration: none;
            transition: all 0.22s ease;
        }

        .btn-primary {
            background: var(--red);
            color: white;
            box-shadow: 0 4px 18px rgba(43,94,171,0.3);
        }

        .btn-primary:hover {
            background: var(--red-dk);
            transform: translateY(-2px);
            box-shadow: 0 8px 28px rgba(43,94,171,0.4);
        }

        .btn-secondary {
            background: transparent;
            color: var(--dark);
            border-color: rgba(14,31,61,0.25);
        }

        .btn-secondary:hover {
            border-color: var(--red);
            color: var(--red);
            transform: translateY(-2px);
        }

        /* ── FEATURES STRIP ── */
        .features {
            display: flex;
            justify-content: center;
            gap: 2.5rem;
            flex-wrap: wrap;
            padding: 0 2rem 5rem;
            position: relative;
            z-index: 2;
        }

        .feature {
            display: flex;
            align-items: center;
            gap: 0.6rem;
            font-size: 0.82rem;
            color: var(--mid);
            opacity: 0;
            animation: fadeUp 0.5s ease forwards;
        }

        .feature:nth-child(1) { animation-delay: 0.5s; }
        .feature:nth-child(2) { animation-delay: 0.65s; }
        .feature:nth-child(3) { animation-delay: 0.8s; }

        .feature-dot {
            width: 6px; height: 6px;
            border-radius: 50%;
            background: var(--gold);
            flex-shrink: 0;
        }

        /* ── FOOTER ── */
        footer {
            background: var(--dark);
            color: rgba(255,255,255,0.45);
            text-align: center;
            padding: 1.4rem;
            font-size: 0.78rem;
            letter-spacing: 0.04em;
        }

        footer strong { color: var(--gold); font-weight: 500; }

        /* ── ANIMATIONS ── */
        @keyframes fadeUp {
            from { opacity: 0; transform: translateY(18px); }
            to   { opacity: 1; transform: translateY(0); }
        }

        @keyframes growLine {
            to { transform: scaleX(1); }
        }

        /* ── RESPONSIVE ── */
        @media (max-width: 500px) {
            nav { padding: 1rem 1.2rem; }
            .nav-tagline { display: none; }
            .btn-group { flex-direction: column; align-items: stretch; }
            .btn { justify-content: center; }
        }
    </style>
</head>

<body>

    <!-- Navigation -->
    <nav>
        <a class="brand" href="#">Optima <span>Flow</span></a>
        <span class="nav-tagline">Bicol Region's Store</span>
    </nav>

    <!-- Hero -->
    <header>
        <div class="hero-inner">
            <span class="hero-label">&#127968; Welcome back</span>
            <h1>Your Bicol<br>Store, <em>Online</em></h1>
            <p class="hero-sub">
                Log in to your account or join thousands of customers
                enjoying the best of Bicol — delivered to your door.
            </p>
            <div class="btn-group">
                <a href="login.php" class="btn btn-primary">
                    &#8594;&nbsp; Log In
                </a>
                <a href="signup.php" class="btn btn-secondary">
                    Create Account
                </a>
            </div>
        </div>
    </header>

    <!-- Feature strip -->
    <div class="features">
        <div class="feature"><span class="feature-dot"></span> Fast local delivery</div>
        <div class="feature"><span class="feature-dot"></span> Authentic Bicolano products</div>
        <div class="feature"><span class="feature-dot"></span> Secure &amp; easy checkout</div>
    </div>

    <!-- Footer -->
    <footer>
        &copy; 2025 <strong>Optima Flow</strong>. All rights reserved.
    </footer>

</body>
</html>