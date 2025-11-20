<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Laravel on DigitalOcean App Platform</title>
    <style>
        body { font-family: sans-serif; max-width: 800px; margin: 50px auto; padding: 20px; }
        h1 { color: #0080FF; }
    </style>
</head>
<body>
    <h1>Laravel on DigitalOcean App Platform</h1>
    <p>Your Laravel application is running successfully!</p>
    <p>PHP Version: {{ PHP_VERSION }}</p>
    <p>Laravel Version: {{ app()->version() }}</p>
</body>
</html>
