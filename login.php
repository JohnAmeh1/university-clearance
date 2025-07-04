<?php 
include 'includes/config.php'; 
if (is_logged_in()) {
    if (is_admin()) {
        redirect('admin/dashboard.php');
    } else {
        redirect('student/dashboard.php');
    }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - University Clearance System</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        'university-blue': '#1e40af',
                        'university-light': '#3b82f6',
                        'university-dark': '#1e3a8a',
                        'accent-gold': '#f59e0b'
                    },
                    fontFamily: {
                        'sans': ['Inter', 'system-ui', 'sans-serif']
                    }
                }
            }
        }
    </script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        .fade-in {
            animation: fadeIn 0.5s ease-in-out;
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .hover-lift {
            transition: transform 0.2s ease-in-out, box-shadow 0.2s ease-in-out;
        }

        .hover-lift:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1);
        }
    </style>
</head>

<body class="bg-gradient-to-br from-slate-50 to-blue-50 font-sans min-h-screen flex items-center justify-center p-4">
    <!-- Loading Overlay -->
    <div id="loading-overlay" class="fixed inset-0 bg-white z-50 flex items-center justify-center opacity-0 pointer-events-none transition-opacity duration-300">
        <div class="text-center">
            <div class="animate-spin rounded-full h-16 w-16 border-b-2 border-university-blue mb-4"></div>
            <p class="text-university-blue font-medium">Loading...</p>
        </div>
    </div>

    <div class="w-full max-w-md fade-in">
        <div class="bg-white p-8 rounded-2xl shadow-lg border border-gray-100 hover-lift">
            <!-- University Logo -->
            <div class="text-center mb-8">
                <div class="w-20 h-20 bg-university-blue rounded-full mx-auto mb-4 flex items-center justify-center">
                    <i class="fas fa-graduation-cap text-white text-3xl"></i>
                </div>
                <h1 class="text-2xl font-bold text-gray-900">University Portal</h1>
                <p class="text-gray-600 mt-1">Clearance System Login</p>
            </div>
            
            <!-- Messages -->
            <?php if (isset($_SESSION['error'])): ?>
                <div class="bg-red-50 border-l-4 border-red-500 text-red-700 p-4 rounded-lg mb-6 flex items-start">
                    <i class="fas fa-exclamation-circle text-red-500 mt-1 mr-3"></i>
                    <div>
                        <p class="font-medium"><?= $_SESSION['error']; unset($_SESSION['error']); ?></p>
                    </div>
                </div>
            <?php endif; ?>
            
            <?php if (isset($_SESSION['success'])): ?>
                <div class="bg-green-50 border-l-4 border-green-500 text-green-700 p-4 rounded-lg mb-6 flex items-start">
                    <i class="fas fa-check-circle text-green-500 mt-1 mr-3"></i>
                    <div>
                        <p class="font-medium"><?= $_SESSION['success']; unset($_SESSION['success']); ?></p>
                    </div>
                </div>
            <?php endif; ?>
            
            <!-- Login Form -->
            <form action="includes/auth.php" method="POST" id="login-form">
                <div class="mb-6">
                    <label for="username" class="block text-gray-700 font-medium mb-2">Matriculation Number</label>
                    <div class="relative">
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                            <i class="fas fa-user text-gray-400"></i>
                        </div>
                        <input type="text" id="username" name="username" required 
                               class="w-full pl-10 pr-4 py-3 border border-gray-300 rounded-xl focus:outline-none focus:ring-2 focus:ring-university-blue focus:border-transparent transition"
                               placeholder="Enter your matric number">
                    </div>
                </div>
                
                <div class="mb-6">
                    <label for="password" class="block text-gray-700 font-medium mb-2">Password</label>
                    <div class="relative">
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                            <i class="fas fa-lock text-gray-400"></i>
                        </div>
                        <input type="password" id="password" name="password" required 
                               class="w-full pl-10 pr-4 py-3 border border-gray-300 rounded-xl focus:outline-none focus:ring-2 focus:ring-university-blue focus:border-transparent transition"
                               placeholder="Enter your password">
                    </div>
                </div>
                
                <button type="submit" name="login" 
                        class="w-full bg-university-blue text-white py-3 px-4 rounded-xl hover:bg-university-dark transition duration-200 font-medium flex items-center justify-center">
                    <span id="login-text">Login</span>
                    <span id="login-spinner" class="ml-2 hidden">
                        <i class="fas fa-spinner fa-spin"></i>
                    </span>
                </button>
            </form>
            
            <div class="mt-6 text-center">
                <p class="text-gray-600">
                    Don't have an account? 
                    <a href="signup.php" class="text-university-blue hover:text-university-dark font-medium transition">
                        Sign up
                    </a>
                </p>
                <p class="text-gray-600 mt-2">
                    <a href="forgot-password.php" class="text-gray-500 hover:text-gray-700 text-sm transition">
                        Forgot password?
                    </a>
                </p>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Form submission handler
            const loginForm = document.getElementById('login-form');
            if (loginForm) {
                loginForm.addEventListener('submit', function() {
                    document.getElementById('login-text').classList.add('hidden');
                    document.getElementById('login-spinner').classList.remove('hidden');
                    document.getElementById('loading-overlay').classList.remove('opacity-0', 'pointer-events-none');
                    document.getElementById('loading-overlay').classList.add('opacity-100');
                });
            }

            // Add accessibility features
            const inputs = document.querySelectorAll('input');
            inputs.forEach(input => {
                input.addEventListener('focus', function() {
                    this.parentElement.classList.add('ring-2', 'ring-university-blue', 'ring-offset-2', 'rounded-xl');
                });
                input.addEventListener('blur', function() {
                    this.parentElement.classList.remove('ring-2', 'ring-university-blue', 'ring-offset-2');
                });
            });

            // Add keyboard shortcut for demo
            document.addEventListener('keydown', function(e) {
                if (e.ctrlKey && e.key === 'k') {
                    e.preventDefault();
                    document.getElementById('username').focus();
                }
            });
        });
    </script>
</body>
</html>