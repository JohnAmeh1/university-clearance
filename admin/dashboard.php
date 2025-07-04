<?php
require_once '../includes/config.php';
require_once '../includes/auth.php';

if (!is_logged_in() || !is_admin()) {
    redirect('../login.php');
}

// Get stats for dashboard
$total_students = $pdo->query("SELECT COUNT(*) FROM users WHERE user_role = 'student'")->fetchColumn();
$pending_submissions = $pdo->query("SELECT COUNT(*) FROM student_documents WHERE status = 'pending'")->fetchColumn();
$completed_clearances = $pdo->query("SELECT COUNT(*) FROM clearance_status WHERE is_complete = TRUE")->fetchColumn();

// Get recent activities
$activities = $pdo->query("
    SELECT 'document' as type, u.full_name, sd.uploaded_at 
    FROM student_documents sd
    JOIN users u ON sd.student_id = u.id
    ORDER BY sd.uploaded_at DESC
    LIMIT 5
")->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en" class="h-full">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="University Clearance System Admin Dashboard">
    <title>Admin Dashboard | University Clearance System</title>
    
    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        'university-primary': {
                            '800': '#075985',
                            '700': '#0369a1',
                            '600': '#0284c7',
                        },
                        'accent-yellow': '#f59e0b',
                        'accent-green': '#10b981',
                    },
                    fontFamily: {
                        'sans': ['Inter', 'system-ui', 'sans-serif']
                    }
                }
            }
        }
    </script>
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
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

        .slide-in {
            animation: slideIn 0.3s ease-out;
        }

        @keyframes slideIn {
            from { transform: translateX(-100%); }
            to { transform: translateX(0); }
        }

        /* Sidebar transitions */
        #sidebar {
            transition: transform 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            will-change: transform;
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

        /* Hover effects */
        .hover-lift {
            transition: transform 0.2s ease-in-out, box-shadow 0.2s ease-in-out;
        }

        .hover-lift:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1);
        }

        /* Status indicators */
        .status-indicator {
            animation: pulse 2s infinite;
        }

        @keyframes pulse {
            0%, 100% { opacity: 1; }
            50% { opacity: 0.7; }
        }

        /* Focus styles for accessibility */
        a:focus, button:focus {
            outline: 2px solid var(--university-primary-600);
            outline-offset: 2px;
        }
    </style>
</head>

<body class="bg-gradient-to-br from-slate-50 to-blue-50 font-sans h-full">
    <!-- Loading Overlay -->
    <div id="loading-overlay" class="fixed inset-0 bg-white bg-opacity-90 z-50 flex items-center justify-center opacity-0 pointer-events-none transition-opacity duration-300">
        <div class="text-center">
            <div class="animate-spin rounded-full h-16 w-16 border-b-2 border-university-primary-600 mb-4"></div>
            <p class="text-university-primary-600 font-medium">Loading Dashboard...</p>
        </div>
    </div>

    <div class="flex h-full overflow-hidden">
        <!-- Mobile Menu Button -->
        <button id="sidebar-toggle" 
                class="md:hidden fixed z-30 top-4 left-4 bg-university-primary-800 hover:bg-university-primary-700 text-white p-3 rounded-lg shadow-lg transition-all duration-200"
                aria-label="Toggle sidebar">
            <i class="fas fa-bars"></i>
        </button>

        <!-- Sidebar -->
        <div id="sidebar" class="bg-gradient-to-b from-university-primary-800 to-university-primary-700 text-white w-72 p-6 fixed md:relative h-full -translate-x-full md:translate-x-0 z-20 shadow-xl">
            <!-- University Header -->
            <div class="mb-8">
                <div class="w-16 h-16 bg-white rounded-full mx-auto mb-4 flex items-center justify-center">
                    <i class="fas fa-graduation-cap text-university-primary-600 text-2xl"></i>
                </div>
                <h1 class="text-xl font-bold text-center">University Portal</h1>
                <p class="text-blue-200 text-sm text-center">Administration Panel</p>
            </div>

            <!-- Navigation Menu -->
            <nav class="space-y-2">
                <div class="text-blue-200 text-xs font-semibold uppercase tracking-wider mb-3 px-3">Main Menu</div>
                
                <a href="dashboard.php" class="nav-item flex items-center space-x-3 px-4 py-3 bg-white bg-opacity-20 rounded-xl transition-all duration-200 group">
                    <i class="fas fa-tachometer-alt text-lg group-hover:scale-110 transition-transform"></i>
                    <span class="font-medium">Dashboard</span>
                </a>

                <a href="requirements.php" class="nav-item flex items-center space-x-3 px-4 py-3 hover:bg-white hover:bg-opacity-20 rounded-xl transition-all duration-200 group">
                    <i class="fas fa-list-check text-lg group-hover:scale-110 transition-transform"></i>
                    <span class="font-medium">Manage Requirements</span>
                </a>

                <a href="submissions.php" class="nav-item flex items-center space-x-3 px-4 py-3 hover:bg-white hover:bg-opacity-20 rounded-xl transition-all duration-200 group">
                    <i class="fas fa-file-upload text-lg group-hover:scale-110 transition-transform"></i>
                    <span class="font-medium">Student Submissions</span>
                </a>

                <div class="border-t border-blue-400 border-opacity-30 my-4"></div>

                <a href="../includes/logout.php" class="nav-item flex items-center space-x-3 px-4 py-3 hover:bg-red-500 hover:bg-opacity-20 rounded-xl transition-all duration-200 group text-red-200">
                    <i class="fas fa-sign-out-alt text-lg group-hover:scale-110 transition-transform"></i>
                    <span class="font-medium">Logout</span>
                </a>
            </nav>

            <!-- User Profile Section -->
            <div class="absolute bottom-6 left-6 right-6">
                <div class="bg-white bg-opacity-10 rounded-xl p-4 backdrop-blur-sm">
                    <div class="flex items-center space-x-3">
                        <div class="w-10 h-10 bg-accent-yellow rounded-full flex items-center justify-center">
                            <i class="fas fa-user text-white text-sm"></i>
                        </div>
                        <div>
                            <p class="font-medium text-sm">Admin User</p>
                            <p class="text-blue-200 text-xs">System Administrator</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Main Content -->
        <div id="main-content" class="flex-1 overflow-auto custom-scrollbar">
            <!-- Header -->
            <header class="bg-white shadow-sm border-b border-gray-200 px-6 py-4 md:px-8">
                <div class="flex items-center justify-between">
                    <div>
                        <h1 class="text-2xl md:text-3xl font-bold text-gray-900">Admin Dashboard</h1>
                        <p class="text-gray-600 mt-1">Welcome back! Here's what's happening with your clearance system today.</p>
                    </div>
                    <div class="flex items-center space-x-4">
                        <div class="relative">
                            <button class="p-2 text-gray-400 hover:text-gray-600 relative">
                                <i class="fas fa-bell text-xl"></i>
                                <span class="absolute -top-1 -right-1 bg-red-500 text-white text-xs rounded-full w-5 h-5 flex items-center justify-center">3</span>
                            </button>
                        </div>
                        <div class="text-right">
                            <p class="text-sm font-medium text-gray-900">Today</p>
                            <p class="text-sm text-gray-500" id="current-date"></p>
                        </div>
                    </div>
                </div>
            </header>

            <!-- Dashboard Content -->
            <main class="p-6 md:p-8 space-y-8">
                <!-- Statistics Cards -->
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
                    <div class="bg-white p-6 rounded-2xl shadow-sm hover-lift border border-gray-100 fade-in">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-gray-500 text-sm font-medium">Total Students</p>
                                <h3 class="text-3xl font-bold text-gray-900 mt-1"><?= number_format($total_students) ?></h3>
                                <p class="text-xs text-gray-400 mt-1">Registered in system</p>
                            </div>
                            <div class="bg-blue-50 p-4 rounded-2xl">
                                <i class="fas fa-users text-university-primary-600 text-2xl"></i>
                            </div>
                        </div>
                    </div>

                    <div class="bg-white p-6 rounded-2xl shadow-sm hover-lift border border-gray-100 fade-in">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-gray-500 text-sm font-medium">Pending Submissions</p>
                                <h3 class="text-3xl font-bold text-gray-900 mt-1"><?= number_format($pending_submissions) ?></h3>
                                <p class="text-xs text-gray-400 mt-1">Awaiting review</p>
                            </div>
                            <div class="bg-yellow-50 p-4 rounded-2xl">
                                <i class="fas fa-clock text-accent-yellow text-2xl status-indicator"></i>
                            </div>
                        </div>
                    </div>

                    <div class="bg-white p-6 rounded-2xl shadow-sm hover-lift border border-gray-100 fade-in">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-gray-500 text-sm font-medium">Completed Clearances</p>
                                <h3 class="text-3xl font-bold text-gray-900 mt-1"><?= number_format($completed_clearances) ?></h3>
                                <p class="text-xs text-gray-400 mt-1">Fully processed</p>
                            </div>
                            <div class="bg-green-50 p-4 rounded-2xl">
                                <i class="fas fa-check-circle text-accent-green text-2xl"></i>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Recent Activities -->
                <div class="bg-white rounded-2xl shadow-sm border border-gray-100 fade-in">
                    <div class="px-6 py-4 border-b border-gray-200">
                        <h2 class="text-xl font-bold text-gray-900 flex items-center">
                            <i class="fas fa-history mr-2 text-university-primary-600"></i>
                            Recent Activities
                        </h2>
                    </div>
                    
                    <div class="divide-y divide-gray-200">
                        <?php if (empty($activities)): ?>
                            <div class="p-6 text-center text-gray-500">
                                <i class="fas fa-inbox text-3xl mb-2 opacity-50"></i>
                                <p>No recent activities found</p>
                            </div>
                        <?php else: ?>
                            <?php foreach ($activities as $activity): ?>
                                <div class="p-4 hover:bg-gray-50 transition-colors">
                                    <div class="flex items-start space-x-4">
                                        <div class="bg-blue-50 p-3 rounded-full text-university-primary-600 flex-shrink-0">
                                            <i class="fas fa-file-upload"></i>
                                        </div>
                                        <div class="flex-1 min-w-0">
                                            <p class="font-medium text-gray-900">
                                                <?= htmlspecialchars($activity['full_name']) ?>
                                                <span class="text-gray-500 font-normal">uploaded a document</span>
                                            </p>
                                            <time datetime="<?= date('Y-m-d\TH:i:s', strtotime($activity['uploaded_at'])) ?>" 
                                                  class="text-xs text-gray-500 mt-1">
                                                <?= date('F j, Y \a\t g:i A', strtotime($activity['uploaded_at'])) ?>
                                            </time>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                    
                    <?php if (!empty($activities)): ?>
                        <div class="px-6 py-3 border-t border-gray-200 text-right">
                            <a href="submissions.php" 
                               class="text-sm font-medium text-university-primary-600 hover:text-university-primary-800 hover:underline">
                                View all submissions →
                            </a>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- Quick Actions -->
                <div class="bg-white p-6 rounded-2xl shadow-sm border border-gray-100 fade-in">
                    <h2 class="text-xl font-bold text-gray-900 mb-6">Quick Actions</h2>
                    <div class="grid grid-cols-2 gap-4">
                        <a href="requirements.php" class="p-4 bg-university-primary-600 text-white rounded-xl hover:bg-university-primary-700 transition-colors duration-200 text-center hover-lift">
                            <i class="fas fa-list-check text-2xl mb-2"></i>
                            <p class="font-medium">Manage Requirements</p>
                        </a>
                        <a href="submissions.php" class="p-4 bg-green-600 text-white rounded-xl hover:bg-green-700 transition-colors duration-200 text-center hover-lift">
                            <i class="fas fa-file-upload text-2xl mb-2"></i>
                            <p class="font-medium">Review Submissions</p>
                        </a>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <!-- Mobile Sidebar Overlay -->
    <div id="sidebar-overlay" class="fixed inset-0 bg-black bg-opacity-50 z-10 opacity-0 pointer-events-none transition-opacity duration-300 md:hidden"></div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Elements
            const sidebarToggle = document.getElementById('sidebar-toggle');
            const sidebar = document.getElementById('sidebar');
            const sidebarOverlay = document.getElementById('sidebar-overlay');
            const mainContent = document.getElementById('main-content');
            const loadingOverlay = document.getElementById('loading-overlay');

            // Show loading animation initially
            loadingOverlay.classList.remove('opacity-0', 'pointer-events-none');
            loadingOverlay.classList.add('opacity-100');

            // Hide loading after 1 second (simulate loading)
            setTimeout(() => {
                loadingOverlay.classList.add('opacity-0', 'pointer-events-none');
                loadingOverlay.classList.remove('opacity-100');
            }, 1000);

            // Toggle sidebar
            function toggleSidebar() {
                const isOpen = !sidebar.classList.contains('-translate-x-full');
                
                if (isOpen) {
                    closeSidebar();
                } else {
                    openSidebar();
                }
            }

            function openSidebar() {
                sidebar.classList.remove('-translate-x-full');
                sidebarOverlay.classList.remove('opacity-0', 'pointer-events-none');
                sidebarOverlay.classList.add('opacity-100');
                document.body.classList.add('overflow-hidden');
            }

            function closeSidebar() {
                sidebar.classList.add('-translate-x-full');
                sidebarOverlay.classList.add('opacity-0', 'pointer-events-none');
                sidebarOverlay.classList.remove('opacity-100');
                document.body.classList.remove('overflow-hidden');
            }

            // Event listeners
            sidebarToggle.addEventListener('click', toggleSidebar);
            sidebarOverlay.addEventListener('click', closeSidebar);

            // Close sidebar when clicking outside on mobile
            document.addEventListener('click', function(event) {
                const isClickInsideSidebar = sidebar.contains(event.target);
                const isClickOnToggle = sidebarToggle.contains(event.target);
                
                if (!isClickInsideSidebar && !isClickOnToggle && window.innerWidth < 768) {
                    closeSidebar();
                }
            });

            // Close sidebar on Escape key press
            document.addEventListener('keydown', function(event) {
                if (event.key === 'Escape' && window.innerWidth < 768 && !sidebar.classList.contains('-translate-x-full')) {
                    closeSidebar();
                }
            });

            // Handle window resize
            window.addEventListener('resize', function() {
                if (window.innerWidth >= 768) {
                    closeSidebar();
                }
            });

            // Update current date
            function updateCurrentDate() {
                const now = new Date();
                const options = { 
                    weekday: 'long', 
                    year: 'numeric', 
                    month: 'long', 
                    day: 'numeric' 
                };
                document.getElementById('current-date').textContent = now.toLocaleDateString('en-US', options);
            }
            updateCurrentDate();

            // Add hover effects to cards
            const cards = document.querySelectorAll('.hover-lift');
            cards.forEach(card => {
                card.addEventListener('mouseenter', () => {
                    card.style.transform = 'translateY(-4px)';
                    card.style.boxShadow = '0 10px 25px rgba(0, 0, 0, 0.1)';
                });

                card.addEventListener('mouseleave', () => {
                    card.style.transform = 'translateY(0)';
                    card.style.boxShadow = '0 1px 3px rgba(0, 0, 0, 0.1)';
                });
            });

            // Notification system
            function showNotification(message, type = 'info') {
                const notification = document.createElement('div');
                notification.className = `fixed top-4 right-4 z-50 p-4 rounded-lg shadow-lg transition-all duration-300 transform translate-x-full ${
                    type === 'success' ? 'bg-green-500 text-white' :
                    type === 'error' ? 'bg-red-500 text-white' :
                    type === 'warning' ? 'bg-yellow-500 text-white' :
                    'bg-blue-500 text-white'
                }`;
                notification.innerHTML = `
                    <div class="flex items-center space-x-2">
                        <i class="fas fa-${type === 'success' ? 'check-circle' : type === 'error' ? 'exclamation-triangle' : type === 'warning' ? 'exclamation-circle' : 'info-circle'}"></i>
                        <span>${message}</span>
                        <button onclick="this.parentElement.parentElement.remove()" class="ml-2 text-white hover:text-gray-200">
                            <i class="fas fa-times"></i>
                        </button>
                    </div>
                `;
                
                document.body.appendChild(notification);
                
                // Animate in
                setTimeout(() => {
                    notification.classList.remove('translate-x-full');
                }, 100);
                
                // Auto remove after 5 seconds
                setTimeout(() => {
                    notification.classList.add('translate-x-full');
                    setTimeout(() => {
                        notification.remove();
                    }, 300);
                }, 5000);
            }

            // Add click handlers for quick actions
            document.querySelectorAll('button').forEach(button => {
                if (button.textContent.includes('Add Student')) {
                    button.addEventListener('click', () => {
                        showNotification('Add Student feature coming soon!', 'info');
                    });
                }
            });

            // Add accessibility improvements
            const skipLink = document.createElement('a');
            skipLink.href = '#main-content';
            skipLink.className = 'sr-only focus:not-sr-only focus:absolute focus:top-0 focus:left-0 bg-university-primary-600 text-white p-2 z-50';
            skipLink.textContent = 'Skip to main content';
            document.body.insertBefore(skipLink, document.body.firstChild);

            // Add ARIA labels
            sidebarToggle.setAttribute('aria-label', 'Toggle navigation menu');
            sidebar.setAttribute('aria-label', 'Navigation menu');
            
            // Add focus indicators
            const focusableElements = document.querySelectorAll('button, a, input');
            focusableElements.forEach(el => {
                el.addEventListener('focus', () => {
                    el.classList.add('ring-2', 'ring-university-primary-600', 'ring-offset-2');
                });
                el.addEventListener('blur', () => {
                    el.classList.remove('ring-2', 'ring-university-primary-600', 'ring-offset-2');
                });
            });

            console.log('University Clearance System Dashboard Initialized');
        });
    </script>
</body>
</html>