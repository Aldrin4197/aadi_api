<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Aadi Admin</title>
    
    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=quicksand:300,400,500,600,700&display=swap" rel="stylesheet" />
    
    @vite('resources/js/admin/main.js')
</head>
<body class="font-sans antialiased">
    <div id="app"></div>
</body>
</html>
