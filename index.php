<?php include 'includes/config.php'; ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>University Clearance System</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body class="bg-gray-100">
    <div class="min-h-screen flex items-center justify-center">
        <div class="bg-white p-8 rounded-lg shadow-md w-full max-w-md">
            <h1 class="text-2xl font-bold text-center mb-6 text-blue-600">University Clearance System</h1>
            <div class="flex justify-center space-x-4 mb-6">
                <a href="login.php" class="px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700 transition">Login</a>
                <a href="signup.php" class="px-4 py-2 bg-gray-600 text-white rounded hover:bg-gray-700 transition">Sign Up</a>
            </div>
            <p class="text-center text-gray-600">Welcome to the university clearance portal for final year students.</p>
        </div>
    </div>
</body>
</html>