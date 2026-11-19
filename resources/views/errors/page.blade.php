<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex, nofollow">
    <title>{{ $title }} — Aadi</title>
    <link rel="icon" type="image/png" href="/images/logos/Aadi_icon.png">
    <link rel="stylesheet" href="/css/error-page.css">
</head>
<body>
    <main class="error-page">
        <div class="error-content">
            <a class="error-brand" href="/login"><img src="/images/logos/Aadi_icon.png" alt="">Aadi</a>
            <p class="error-code">ERROR {{ $status }}</p>
            <h1>{{ $title }}</h1>
            <p class="error-message">{{ $message }}</p>
            <a class="error-link" href="/login">Go to sign in <span aria-hidden="true">→</span></a>
        </div>
    </main>
</body>
</html>
