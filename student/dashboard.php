<?php
require_once '../includes/config.php';
require_once '../includes/auth.php';

if (!is_logged_in() || !is_student()) {
    redirect('../login.php');
}

// Get clearance status and form path if exists
$stmt = $pdo->prepare("
    SELECT cs.is_complete, cs.clearance_form_path 
    FROM clearance_status cs 
    WHERE cs.student_id = ?
");
$stmt->execute([$_SESSION['user_id']]);
$clearance_status = $stmt->fetch();
$is_complete = $clearance_status ? $clearance_status['is_complete'] : false;
$clearance_form_path = $clearance_status ? $clearance_status['clearance_form_path'] : null;

// Get pending documents count
$stmt = $pdo->prepare("SELECT COUNT(*) FROM student_documents WHERE student_id = ? AND status = 'pending'");
$stmt->execute([$_SESSION['user_id']]);
$pending_count = $stmt->fetchColumn();

// Get requirements count
$stmt = $pdo->prepare("SELECT COUNT(*) FROM clearance_requirements WHERE department_id = (SELECT id FROM departments WHERE name = ?)");
$stmt->execute([$_SESSION['department']]);
$requirements_count = $stmt->fetchColumn();

// Get submitted documents count
$stmt = $pdo->prepare("SELECT COUNT(*) FROM student_documents WHERE student_id = ?");
$stmt->execute([$_SESSION['user_id']]);
$submitted_count = $stmt->fetchColumn();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Dashboard - University Clearance System</title>
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

        .slide-in {
            animation: slideIn 0.3s ease-out;
        }

        @keyframes slideIn {
            from { transform: translateX(-100%); }
            to { transform: translateX(0); }
        }

        /* Sidebar transitions */
        .sidebar-transition {
            transition: transform 0.3s cubic-bezier(0.4, 0, 0.2, 1);
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

        /* Responsive grid adjustments */
        @media (max-width: 640px) {
            .mobile-padding { padding: 1rem; }
            .mobile-text { font-size: 0.875rem; }
            .quick-actions {
                flex-direction: column;
                gap: 0.5rem;
            }
            .quick-actions a {
                width: 100%;
                text-align: center;
            }
        }

        @media (max-width: 767px) {
            #sidebar {
                transition: transform 0.3s ease-in-out;
                z-index: 40;
            }
        }
    </style>
</head>

<body class="bg-gradient-to-br from-slate-50 to-blue-50 font-sans">
    <!-- Loading Overlay -->
    <div id="loading-overlay" class="fixed inset-0 bg-white z-50 flex items-center justify-center opacity-0 pointer-events-none transition-opacity duration-300">
        <div class="text-center">
            <div class="animate-spin rounded-full h-16 w-16 border-b-2 border-university-blue mb-4"></div>
            <p class="text-university-blue font-medium">Loading Dashboard...</p>
        </div>
    </div>

    <div class="flex h-screen overflow-hidden">
        <!-- Mobile Menu Button -->
        <button id="sidebar-toggle" class="md:hidden fixed z-30 top-4 left-4 bg-university-blue hover:bg-university-dark text-white p-3 rounded-xl shadow-lg transition-all duration-200 hover:shadow-xl">
            <i class="fas fa-bars text-lg"></i>
        </button>

        <!-- Sidebar -->
        <div id="sidebar" class="bg-gradient-to-b from-university-blue to-university-dark text-white w-72 p-6 fixed md:relative h-full -translate-x-full md:translate-x-0 sidebar-transition z-40 shadow-2xl">
            <!-- University Header -->
            <div class="mb-8 text-center">
                <div class="w-16 h-16 bg-white rounded-full mx-auto mb-4 flex items-center justify-center">
                    <i class="fas fa-graduation-cap text-university-blue text-2xl"></i>
                </div>
                <h1 class="text-xl font-bold mb-1">Student Portal</h1>
                <p class="text-blue-200 text-sm">Clearance System</p>
            </div>

            <!-- Navigation Menu -->
            <nav class="space-y-2">
                <div class="text-blue-200 text-xs font-semibold uppercase tracking-wider mb-3 px-3">Main Menu</div>
                
                <a href="dashboard.php" class="nav-item active flex items-center space-x-3 px-4 py-3 bg-white bg-opacity-20 rounded-xl transition-all duration-200 hover:bg-opacity-30 group">
                    <i class="fas fa-tachometer-alt text-lg group-hover:scale-110 transition-transform"></i>
                    <span class="font-medium">Dashboard</span>
                </a>

                <a href="clearance.php" class="nav-item flex items-center space-x-3 px-4 py-3 hover:bg-white hover:bg-opacity-20 rounded-xl transition-all duration-200 group">
                    <i class="fas fa-list-check text-lg group-hover:scale-110 transition-transform"></i>
                    <span class="font-medium">Clearance Checklist</span>
                </a>

                <a href="../includes/logout.php" class="nav-item flex items-center space-x-3 px-4 py-3 hover:bg-red-500 hover:bg-opacity-20 rounded-xl transition-all duration-200 group text-red-200">
                    <i class="fas fa-sign-out-alt text-lg group-hover:scale-110 transition-transform"></i>
                    <span class="font-medium">Logout</span>
                </a>
            </nav>

            <!-- User Profile Section -->
            <div class="absolute bottom-6 left-6 right-6">
                <div class="bg-white bg-opacity-10 rounded-xl p-4 backdrop-blur-sm">
                    <div class="flex items-center space-x-3">
                        <div class="w-10 h-10 bg-accent-gold rounded-full flex items-center justify-center">
                            <i class="fas fa-user text-white text-sm"></i>
                        </div>
                        <div>
                            <p class="font-medium text-sm"><?= htmlspecialchars($_SESSION['full_name']) ?></p>
                            <p class="text-blue-200 text-xs"><?= htmlspecialchars($_SESSION['department']) ?></p>
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
                        <h1 class="text-2xl md:text-3xl font-bold text-gray-900">Student Dashboard</h1>
                        <p class="text-gray-600 mt-1">Track your clearance progress and submit required documents.</p>
                    </div>
                    <div class="flex items-center space-x-4">
                        <div class="text-right">
                            <p class="text-sm font-medium text-gray-900">Today</p>
                            <p class="text-sm text-gray-500" id="current-date"></p>
                        </div>
                    </div>
                </div>
            </header>

            <!-- Dashboard Content -->
            <main class="p-6 md:p-8 space-y-8">
                <!-- Welcome Message -->
                <div class="bg-white p-6 rounded-2xl shadow-sm border border-gray-100 fade-in">
                    <h2 class="text-xl font-bold text-gray-900 mb-2">Welcome, <?= htmlspecialchars($_SESSION['full_name']) ?>!</h2>
                    <p class="text-gray-600 mb-4">Department: <?= htmlspecialchars($_SESSION['department']) ?></p>

                    <div class="mt-3 md:mt-4">
                        <?php if ($is_complete): ?>
                            <span class="bg-green-100 text-green-800 px-3 py-1 rounded-full text-sm font-medium">
                                <i class="fas fa-check-circle mr-1"></i> Clearance Completed
                            </span>
                        <?php else: ?>
                            <span class="bg-yellow-100 text-yellow-800 px-3 py-1 rounded-full text-sm font-medium">
                                <i class="fas fa-exclamation-circle mr-1"></i> Clearance Incomplete
                            </span>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Stats Cards -->
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
                    <div class="bg-white p-6 rounded-2xl shadow-sm hover-lift border border-gray-100 fade-in">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-gray-500 text-sm font-medium">Total Requirements</p>
                                <h3 class="text-3xl font-bold text-gray-900 mt-1" id="total-requirements"><?= $requirements_count ?></h3>
                                <p class="text-blue-600 text-sm mt-1">
                                    <i class="fas fa-list-check"></i> For your department
                                </p>
                            </div>
                            <div class="bg-blue-50 p-4 rounded-2xl">
                                <i class="fas fa-list-check text-blue-600 text-2xl"></i>
                            </div>
                        </div>
                    </div>

                    <div class="bg-white p-6 rounded-2xl shadow-sm hover-lift border border-gray-100 fade-in">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-gray-500 text-sm font-medium">Submitted Documents</p>
                                <h3 class="text-3xl font-bold text-gray-900 mt-1" id="submitted-documents"><?= $submitted_count ?></h3>
                                <p class="text-green-600 text-sm mt-1">
                                    <i class="fas fa-check-circle"></i> Documents uploaded
                                </p>
                            </div>
                            <div class="bg-green-50 p-4 rounded-2xl">
                                <i class="fas fa-file-upload text-green-600 text-2xl"></i>
                            </div>
                        </div>
                    </div>

                    <div class="bg-white p-6 rounded-2xl shadow-sm hover-lift border border-gray-100 fade-in">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-gray-500 text-sm font-medium">Pending Approval</p>
                                <h3 class="text-3xl font-bold text-gray-900 mt-1" id="pending-approval"><?= $pending_count ?></h3>
                                <p class="text-yellow-600 text-sm mt-1">
                                    <i class="fas fa-clock"></i> Awaiting review
                                </p>
                            </div>
                            <div class="bg-yellow-50 p-4 rounded-2xl">
                                <i class="fas fa-clock text-yellow-600 text-2xl status-indicator"></i>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Quick Actions -->
                <div class="bg-white p-6 rounded-2xl shadow-sm border border-gray-100 fade-in">
                    <h2 class="text-xl font-bold text-gray-900 mb-6">Quick Actions</h2>
                    <div class="flex flex-col sm:flex-row gap-4 quick-actions">
                        <a href="clearance.php" class="px-4 py-3 bg-university-blue text-white rounded-xl hover:bg-university-dark transition-colors duration-200 text-center hover-lift">
                            <i class="fas fa-list-check text-lg mr-2"></i>
                            <span class="font-medium">Go to Clearance Checklist</span>
                        </a>
                        <?php if ($is_complete): ?>
                            <a href="../download_clearance.php?student_id=<?= $_SESSION['user_id'] ?>" 
                               class="px-4 py-3 bg-green-600 text-white rounded-xl hover:bg-green-700 transition-colors duration-200 text-center hover-lift">
                                <i class="fas fa-file-download text-lg mr-2"></i>
                                <span class="font-medium">Download Clearance Form</span>
                            </a>
                        <?php endif; ?>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <!-- Mobile Sidebar Overlay -->
    <div id="sidebar-overlay" class="fixed inset-0 bg-black bg-opacity-50 z-30 opacity-0 pointer-events-none transition-opacity duration-300 md:hidden"></div>

    <script>
        // Enhanced Dashboard JavaScript
        class StudentDashboard {
            constructor() {
                this.init();
                this.loadDashboardData();
                this.setupEventListeners();
                this.updateDateTime();
            }

            init() {
                // Initialize dashboard components
                this.sidebar = document.getElementById('sidebar');
                this.sidebarToggle = document.getElementById('sidebar-toggle');
                this.mainContent = document.getElementById('main-content');
                this.sidebarOverlay = document.getElementById('sidebar-overlay');
                this.loadingOverlay = document.getElementById('loading-overlay');
                
                // Show loading animation
                this.showLoading();
                
                // Hide loading after initialization
                setTimeout(() => {
                    this.hideLoading();
                }, 1000);
            }

            setupEventListeners() {
                // Sidebar toggle
                this.sidebarToggle.addEventListener('click', () => {
                    this.toggleSidebar();
                });

                // Sidebar overlay click
                this.sidebarOverlay.addEventListener('click', () => {
                    this.closeSidebar();
                });

                // Handle window resize
                window.addEventListener('resize', () => {
                    if (window.innerWidth >= 768) {
                        this.closeSidebar();
                    }
                });

                // Smooth scrolling for navigation
                document.querySelectorAll('.nav-item').forEach(item => {
                    item.addEventListener('click', (e) => {
                        if (item.getAttribute('href') === '#') {
                            e.preventDefault();
                        }
                        this.handleNavigation(item);
                    });
                });
            }

            toggleSidebar() {
                const isOpen = !this.sidebar.classList.contains('-translate-x-full');
                
                if (isOpen) {
                    this.closeSidebar();
                } else {
                    this.openSidebar();
                }
            }

            openSidebar() {
                this.sidebar.classList.remove('-translate-x-full');
                this.sidebar.classList.add('slide-in');
                this.sidebarOverlay.classList.remove('opacity-0', 'pointer-events-none');
                this.sidebarOverlay.classList.add('opacity-100');
                document.body.classList.add('overflow-hidden');
            }

            closeSidebar() {
                this.sidebar.classList.add('-translate-x-full');
                this.sidebar.classList.remove('slide-in');
                this.sidebarOverlay.classList.add('opacity-0', 'pointer-events-none');
                this.sidebarOverlay.classList.remove('opacity-100');
                document.body.classList.remove('overflow-hidden');
            }

            handleNavigation(item) {
                // Remove active class from all items
                document.querySelectorAll('.nav-item').forEach(navItem => {
                    navItem.classList.remove('active', 'bg-white', 'bg-opacity-20');
                });

                // Add active class to clicked item
                item.classList.add('active', 'bg-white', 'bg-opacity-20');

                // Close sidebar on mobile
                if (window.innerWidth < 768) {
                    this.closeSidebar();
                }
            }

            loadDashboardData() {
                // Animate counter updates
                this.animateCounter('total-requirements', <?= $requirements_count ?>);
                this.animateCounter('submitted-documents', <?= $submitted_count ?>);
                this.animateCounter('pending-approval', <?= $pending_count ?>);
            }

            animateCounter(elementId, targetValue) {
                const element = document.getElementById(elementId);
                if (!element) return;

                const startValue = 0;
                const duration = 1000;
                const startTime = performance.now();

                const updateCounter = (currentTime) => {
                    const elapsed = currentTime - startTime;
                    const progress = Math.min(elapsed / duration, 1);
                    const currentValue = Math.floor(startValue + (targetValue - startValue) * progress);
                    
                    element.textContent = currentValue.toLocaleString();
                    
                    if (progress < 1) {
                        requestAnimationFrame(updateCounter);
                    }
                };

                requestAnimationFrame(updateCounter);
            }

            updateDateTime() {
                const now = new Date();
                const options = { 
                    weekday: 'long', 
                    year: 'numeric', 
                    month: 'long', 
                    day: 'numeric' 
                };
                
                const dateElement = document.getElementById('current-date');
                if (dateElement) {
                    dateElement.textContent = now.toLocaleDateString('en-US', options);
                }

                // Update every minute
                setTimeout(() => {
                    this.updateDateTime();
                }, 60000);
            }

            showLoading() {
                this.loadingOverlay.classList.remove('opacity-0', 'pointer-events-none');
                this.loadingOverlay.classList.add('opacity-100');
            }

            hideLoading() {
                this.loadingOverlay.classList.add('opacity-0', 'pointer-events-none');
                this.loadingOverlay.classList.remove('opacity-100');
            }
        }

        // Initialize dashboard when DOM is loaded
        document.addEventListener('DOMContentLoaded', () => {
            new StudentDashboard();
            
            // Add hover effects to cards
            const cards = document.querySelectorAll('.hover-lift');
            cards.forEach(card => {
                card.addEventListener('mouseenter', () => {
                    card.style.transform = 'translateY(-4px)';
                    card.style.boxShadow = '0 20px 40px rgba(0, 0, 0, 0.1)';
                });

                card.addEventListener('mouseleave', () => {
                    card.style.transform = 'translateY(0)';
                    card.style.boxShadow = '0 1px 3px rgba(0, 0, 0, 0.1)';
                });
            });

            // Add accessibility improvements
            const addAccessibility = () => {
                // Add skip navigation link
                const skipLink = document.createElement('a');
                skipLink.href = '#main-content';
                skipLink.className = 'sr-only focus:not-sr-only focus:absolute focus:top-0 focus:left-0 bg-university-blue text-white p-2 z-50';
                skipLink.textContent = 'Skip to main content';
                document.body.insertBefore(skipLink, document.body.firstChild);

                // Add ARIA labels
                document.getElementById('sidebar-toggle').setAttribute('aria-label', 'Toggle navigation menu');
                document.getElementById('sidebar').setAttribute('aria-label', 'Navigation menu');
                
                // Add focus indicators
                const focusableElements = document.querySelectorAll('button, a, input');
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

            console.log('🎓 Student Dashboard Initialized');
        });
    </script>
</body>
</html>