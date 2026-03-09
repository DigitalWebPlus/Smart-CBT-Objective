<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Server Error | {{ config('app.name', 'Smart CBT Objective') }}</title>
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=space-grotesk:500,600,700|plus-jakarta-sans:400,500,600&display=swap" rel="stylesheet" />
    <style>
        :root {
            --ink: #101827;
            --muted: #5f6b7d;
            --accent: #0b5fb0;
            --accent-2: #0c8f8d;
            --soft: #e9f3ff;
            --paper: #f5faff;
            --white: #ffffff;
        }

        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            min-height: 100vh;
            font-family: "Plus Jakarta Sans", "Segoe UI", Tahoma, Geneva, Verdana, sans-serif;
            color: var(--ink);
            background:
                radial-gradient(circle at 12% 18%, rgba(11, 95, 176, 0.26), transparent 46%),
                radial-gradient(circle at 88% 15%, rgba(12, 143, 141, 0.22), transparent 42%),
                linear-gradient(180deg, var(--paper), #edf6ff);
            display: grid;
            place-items: center;
            padding: 24px;
        }

        .card {
            width: min(720px, 100%);
            background: linear-gradient(145deg, rgba(255, 255, 255, 0.98), rgba(241, 250, 255, 0.95));
            border: 1px solid rgba(11, 95, 176, 0.12);
            border-radius: 24px;
            box-shadow: 0 28px 65px rgba(16, 24, 39, 0.2);
            padding: 30px;
            position: relative;
            overflow: hidden;
        }

        .card::before {
            content: "";
            position: absolute;
            inset: -120px auto auto -120px;
            width: 220px;
            height: 220px;
            border-radius: 999px;
            background: radial-gradient(circle, rgba(11, 95, 176, 0.2), transparent 66%);
        }

        .label {
            display: inline-block;
            background: var(--soft);
            color: var(--accent);
            border-radius: 999px;
            padding: 6px 12px;
            font-size: 0.75rem;
            letter-spacing: 0.08em;
            font-weight: 800;
            text-transform: uppercase;
            position: relative;
            z-index: 1;
        }

        h1 {
            margin: 16px 0 8px;
            font-size: clamp(1.75rem, 2.5vw, 2.4rem);
            line-height: 1.2;
            font-family: "Space Grotesk", "Segoe UI", Tahoma, Geneva, Verdana, sans-serif;
            position: relative;
            z-index: 1;
        }

        p {
            margin: 0;
            color: var(--muted);
            font-size: 1rem;
            line-height: 1.6;
            position: relative;
            z-index: 1;
        }

        .code {
            margin-top: 18px;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            border: 1px solid rgba(15, 76, 129, 0.2);
            border-radius: 10px;
            background: var(--white);
            padding: 10px 12px;
            color: var(--accent);
            font-weight: 600;
            font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, "Liberation Mono", "Courier New", monospace;
            position: relative;
            z-index: 1;
        }

        .actions {
            display: flex;
            flex-wrap: wrap;
            gap: 12px;
            margin-top: 24px;
            position: relative;
            z-index: 1;
        }

        .btn {
            appearance: none;
            border: 0;
            border-radius: 12px;
            padding: 12px 18px;
            font-size: 0.95rem;
            font-weight: 600;
            cursor: pointer;
            text-decoration: none;
            transition: transform 0.2s ease, box-shadow 0.2s ease, opacity 0.2s ease;
            display: inline-flex;
            align-items: center;
            justify-content: center;
        }

        .btn:hover {
            transform: translateY(-1px);
        }

        .btn-primary {
            background: linear-gradient(120deg, var(--accent), #1f77c8);
            color: #fff;
            box-shadow: 0 12px 26px rgba(11, 95, 176, 0.28);
        }

        .btn-secondary {
            background: #fff;
            color: var(--accent);
            border: 1px solid rgba(11, 95, 176, 0.24);
        }

        .btn-support {
            background: linear-gradient(120deg, #0c8f8d, #26a69a);
            color: #ffffff;
            box-shadow: 0 12px 24px rgba(12, 143, 141, 0.26);
        }

        .hint {
            margin-top: 20px;
            font-size: 0.9rem;
            color: #6b778d;
            position: relative;
            z-index: 1;
        }

        .links {
            margin-top: 14px;
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            position: relative;
            z-index: 1;
        }

        .credit {
            margin-top: 14px;
            font-size: 0.9rem;
            color: #4f5f77;
            position: relative;
            z-index: 1;
        }

        .credit a {
            color: var(--accent);
            font-weight: 700;
            text-decoration: none;
        }

        .credit a:hover {
            text-decoration: underline;
        }

        @media (max-width: 640px) {
            .card {
                padding: 22px;
                border-radius: 16px;
            }

            .btn {
                width: 100%;
            }
        }
    </style>
</head>
<body>
    <main class="card" role="main" aria-labelledby="error-title">
        <span class="label">Internal Server Error</span>
        <h1 id="error-title">Something went wrong on our side.</h1>
        <p>
            The request could not be completed due to a temporary server issue.
            Please try again in a moment.
        </p>

        <div class="code" aria-label="Error code">Error 500</div>

        <p class="hint">If this keeps happening, contact the developer team with the time the error occurred.</p>

        <div class="links">
            <a href="https://digitalwebplus.com" target="_blank" rel="noopener noreferrer" class="btn btn-primary">Contact Developer</a>
            <a href="https://wa.me/message/7CPGQYDRMUXWD1" target="_blank" rel="noopener noreferrer" class="btn btn-support">WhatsApp Support</a>
        </div>

        <p class="credit">This is a product of <a href="https://digitalwebplus.com/" target="_blank" rel="noopener noreferrer">DigitalWeb Plus</a>. You can contact us for support and modifications.</p>
    </main>
</body>
</html>
