<?php include 'includes/config.php'; 
if (is_logged_in()) {
    if (is_admin()) {
        redirect('admin/dashboard.php');
    } else {
        redirect('student/dashboard.php');
    }
}

// Fetch departments for dropdown
$departments = $pdo->query("SELECT * FROM departments")->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sign Up - University Clearance System</title>
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
        /* Custom animations and transitions */
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

        /* Custom scrollbar */
        .custom-scrollbar::-webkit-scrollbar {
            width: 6px;
        }

        .custom-scrollbar::-webkit-scrollbar-track {
            background: #f1f5f9;
            border-radius: 3px;
        }

        .custom-scrollbar::-webkit-scrollbar-thumb {
            background: #cbd5e1;
            border-radius: 3px;
        }

        .custom-scrollbar::-webkit-scrollbar-thumb:hover {
            background: #94a3b8;
        }

        /* Dark mode support */
        @media (prefers-color-scheme: dark) {
            .dark-mode-card {
                background: #1f2937;
                border-color: #374151;
            }
        }
    </style>
</head>

<body class="bg-gradient-to-br from-slate-50 to-blue-50 font-sans min-h-screen flex items-center justify-center p-4">
    <!-- Loading Overlay -->
    <div id="loading-overlay" class="fixed inset-0 bg-white z-50 flex items-center justify-center opacity-0 pointer-events-none transition-opacity duration-300">
        <div class="text-center">
            <div class="animate-spin rounded-full h-16 w-16 border-b-2 border-university-blue mb-4"></div>
            <p class="text-university-blue font-medium">Processing Registration...</p>
        </div>
    </div>

    <div class="w-full max-w-md fade-in">
        <div class="bg-white p-8 rounded-2xl shadow-lg border border-gray-100 hover-lift">
            <!-- University Header -->
            <div class="text-center mb-8">
                <div class="w-16 h-16 bg-blue-50 rounded-full mx-auto mb-4 flex items-center justify-center">
                    <i class="fas fa-graduation-cap text-university-blue text-2xl"></i>
                </div>
                <h1 class="text-2xl font-bold text-gray-900">University Portal</h1>
                <p class="text-blue-600 mt-1">Student Registration</p>
            </div>
            
            <?php if (isset($_SESSION['error'])): ?>
                <div class="bg-red-100 border border-red-200 text-red-700 px-4 py-3 rounded-xl mb-6 flex items-start">
                    <i class="fas fa-exclamation-circle mt-1 mr-2"></i>
                    <div>
                        <?= htmlspecialchars($_SESSION['error']); unset($_SESSION['error']); ?>
                    </div>
                </div>
            <?php endif; ?>
            
            <form action="includes/auth.php" method="POST" id="registration-form">
                <div class="space-y-4">
                    <div>
                        <label for="full_name" class="block text-sm font-medium text-gray-700 mb-1">Full Name</label>
                        <div class="relative">
                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                <i class="fas fa-user text-gray-400"></i>
                            </div>
                            <input type="text" id="full_name" name="full_name" required 
                                   class="w-full pl-10 pr-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-university-blue focus:border-transparent">
                        </div>
                    </div>
                    
                    <div>
                        <label for="username" class="block text-sm font-medium text-gray-700 mb-1">Matriculation Number</label>
                        <div class="relative">
                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                <i class="fas fa-id-card text-gray-400"></i>
                            </div>
                            <input type="text" id="username" name="username" required 
                                   class="w-full pl-10 pr-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-university-blue focus:border-transparent">
                        </div>
                    </div>
                    
                    <div>
                        <label for="email" class="block text-sm font-medium text-gray-700 mb-1">Email</label>
                        <div class="relative">
                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                <i class="fas fa-envelope text-gray-400"></i>
                            </div>
                            <input type="email" id="email" name="email" required 
                                   class="w-full pl-10 pr-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-university-blue focus:border-transparent">
                        </div>
                    </div>
                    
                    <div>
                        <label for="department" class="block text-sm font-medium text-gray-700 mb-1">Department</label>
                        <div class="relative">
                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                <i class="fas fa-building text-gray-400"></i>
                            </div>
                            <select id="department" name="department" required 
                                    class="w-full pl-10 pr-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-university-blue focus:border-transparent appearance-none bg-white">
                                <option value="">Select Department</option>
                                <?php foreach ($departments as $dept): ?>
                                    <option value="<?= htmlspecialchars($dept['name']) ?>"><?= htmlspecialchars($dept['name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                            <div class="absolute inset-y-0 right-0 flex items-center pr-3 pointer-events-none">
                                <i class="fas fa-chevron-down text-gray-400"></i>
                            </div>
                        </div>
                    </div>
                    
                    <div>
                        <label for="password" class="block text-sm font-medium text-gray-700 mb-1">Password</label>
                        <div class="relative">
                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                <i class="fas fa-lock text-gray-400"></i>
                            </div>
                            <input type="password" id="password" name="password" required 
                                   class="w-full pl-10 pr-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-university-blue focus:border-transparent">
                        </div>
                    </div>
                    
                    <div>
                        <label for="confirm_password" class="block text-sm font-medium text-gray-700 mb-1">Confirm Password</label>
                        <div class="relative">
                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                <i class="fas fa-lock text-gray-400"></i>
                            </div>
                            <input type="password" id="confirm_password" name="confirm_password" required 
                                   class="w-full pl-10 pr-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-university-blue focus:border-transparent">
                        </div>
                    </div>
                </div>

                <button type="submit" name="signup" 
                        class="w-full mt-6 bg-university-blue text-white py-3 px-4 rounded-xl hover:bg-university-dark transition-colors duration-200 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-university-blue">
                    <i class="fas fa-user-plus mr-2"></i> Register
                </button>
            </form>
            
            <div class="mt-6 text-center text-sm text-gray-600">
                <p>Already have an account? 
                    <a href="login.php" class="font-medium text-university-blue hover:text-university-dark hover:underline transition-colors">
                        <i class="fas fa-sign-in-alt mr-1"></i> Login
                    </a>
                </p>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const form = document.getElementById('registration-form');
            const loadingOverlay = document.getElementById('loading-overlay');
            
            if (form) {
                form.addEventListener('submit', function() {
                    loadingOverlay.classList.remove('opacity-0', 'pointer-events-none');
                    loadingOverlay.classList.add('opacity-100');
                });
            }
            
            // Add password strength indicator
            const passwordInput = document.getElementById('password');
            if (passwordInput) {
                passwordInput.addEventListener('input', function() {
                    const password = this.value;
                    const strengthIndicator = document.getElementById('password-strength');
                    
                    if (!strengthIndicator) {
                        const indicator = document.createElement('div');
                        indicator.id = 'password-strength';
                        indicator.className = 'mt-1 text-xs';
                        this.parentNode.insertBefore(indicator, this.nextSibling);
                    }
                    
                    // Simple password strength check
                    let strength = 0;
                    if (password.length >= 8) strength++;
                    if (password.match(/[A-Z]/)) strength++;
                    if (password.match(/[0-9]/)) strength++;
                    if (password.match(/[^A-Za-z0-9]/)) strength++;
                    
                    let message = '';
                    let color = '';
                    
                    switch(strength) {
                        case 0:
                        case 1:
                            message = 'Weak';
                            color = 'text-red-600';
                            break;
                        case 2:
                            message = 'Moderate';
                            color = 'text-yellow-600';
                            break;
                        case 3:
                            message = 'Strong';
                            color = 'text-green-600';
                            break;
                        case 4:
                            message = 'Very Strong';
                            color = 'text-green-700';
                            break;
                    }
                    
                    strengthIndicator.innerHTML = `Password strength: <span class="font-medium ${color}">${message}</span>`;
                });
            }
            
            // Add confirm password validation
            const confirmPassword = document.getElementById('confirm_password');
            if (confirmPassword) {
                confirmPassword.addEventListener('input', function() {
                    const password = document.getElementById('password').value;
                    const confirm = this.value;
                    const errorElement = document.getElementById('password-match-error');
                    
                    if (password !== confirm) {
                        if (!errorElement) {
                            const errorDiv = document.createElement('div');
                            errorDiv.id = 'password-match-error';
                            errorDiv.className = 'mt-1 text-xs text-red-600';
                            errorDiv.innerHTML = '<i class="fas fa-exclamation-circle mr-1"></i> Passwords do not match';
                            this.parentNode.insertBefore(errorDiv, this.nextSibling);
                        }
                    } else if (errorElement) {
                        errorElement.remove();
                    }
                });
            }
            
            // Add accessibility improvements
            const addAccessibility = () => {
                // Add focus indicators
                const focusableElements = document.querySelectorAll('button, input, select, a');
                focusableElements.forEach(el => {
                    el.addEventListener('focus', () => {
                        el.classList.add('ring-2', 'ring-university-blue', 'ring-offset-2');
                    });
                    el.addEventListener('blur', () => {
                        el.classList.remove('ring-2', 'ring-university-blue', 'ring-offset-2');
                    });
                });
            };
            
            addAccessibility();
        });
    </script>
</body>

</html>