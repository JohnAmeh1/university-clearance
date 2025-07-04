<?php
require_once '../includes/config.php';
require_once '../includes/auth.php';

if (!is_logged_in() || !is_admin()) {
    redirect('../login.php');
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_requirement'])) {
    $department_id = $_POST['department_id'];
    $document_name = trim($_POST['document_name']);
    $description = trim($_POST['description']);

    try {
        $stmt = $pdo->prepare("INSERT INTO clearance_requirements (department_id, document_name, description, created_by) VALUES (?, ?, ?, ?)");
        $stmt->execute([$department_id, $document_name, $description, $_SESSION['user_id']]);

        $_SESSION['success'] = "Requirement added successfully";
        redirect('requirements.php');
    } catch (PDOException $e) {
        $_SESSION['error'] = "Error adding requirement: " . $e->getMessage();
    }
}

// Handle delete
if (isset($_GET['delete'])) {
    $id = $_GET['delete'];

    try {
        $stmt = $pdo->prepare("DELETE FROM clearance_requirements WHERE id = ?");
        $stmt->execute([$id]);

        $_SESSION['success'] = "Requirement deleted successfully";
        redirect('requirements.php');
    } catch (PDOException $e) {
        $_SESSION['error'] = "Error deleting requirement: " . $e->getMessage();
    }
}

// Get all requirements with department names
$requirements = $pdo->query("
    SELECT cr.*, d.name as department_name 
    FROM clearance_requirements cr
    JOIN departments d ON cr.department_id = d.id
    ORDER BY d.name, cr.document_name
")->fetchAll(PDO::FETCH_ASSOC);

// Get all departments
$departments = $pdo->query("SELECT * FROM departments ORDER BY name")->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en" class="h-full">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="University Clearance System Requirements Management">
    <title>Manage Requirements | University Clearance System</title>
    
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

        /* Focus styles for accessibility */
        a:focus, button:focus, input:focus, select:focus, textarea:focus {
            outline: 2px solid var(--university-primary-600);
            outline-offset: 2px;
        }

        @media (max-width: 767px) {
            table {
                display: block;
                overflow-x: auto;
                white-space: nowrap;
            }
        }
    </style>
</head>

<body class="bg-gradient-to-br from-slate-50 to-blue-50 font-sans h-full">
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
                
                <a href="dashboard.php" class="nav-item flex items-center space-x-3 px-4 py-3 hover:bg-white hover:bg-opacity-20 rounded-xl transition-all duration-200 group">
                    <i class="fas fa-tachometer-alt text-lg group-hover:scale-110 transition-transform"></i>
                    <span class="font-medium">Dashboard</span>
                </a>

                <a href="requirements.php" class="nav-item flex items-center space-x-3 px-4 py-3 bg-white bg-opacity-20 rounded-xl transition-all duration-200 group">
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
                        <h1 class="text-2xl md:text-3xl font-bold text-gray-900">Manage Requirements</h1>
                        <p class="text-gray-600 mt-1">Add and manage clearance requirements for departments</p>
                    </div>
                </div>
            </header>

            <!-- Dashboard Content -->
            <main class="p-6 md:p-8 space-y-8">
                <?php if (isset($_SESSION['error'])): ?>
                    <div class="bg-red-100 border border-red-200 text-red-700 px-4 py-3 rounded-lg fade-in">
                        <?= $_SESSION['error'];
                        unset($_SESSION['error']); ?>
                    </div>
                <?php endif; ?>

                <?php if (isset($_SESSION['success'])): ?>
                    <div class="bg-green-100 border border-green-200 text-green-700 px-4 py-3 rounded-lg fade-in">
                        <?= $_SESSION['success'];
                        unset($_SESSION['success']); ?>
                    </div>
                <?php endif; ?>

                <!-- Add Requirement Form -->
                <div class="bg-white p-6 rounded-2xl shadow-sm border border-gray-100 fade-in hover-lift">
                    <h2 class="text-xl font-bold text-gray-900 mb-6 flex items-center">
                        <i class="fas fa-plus-circle text-university-primary-600 mr-2"></i>
                        Add New Requirement
                    </h2>
                    <form method="POST" class="space-y-4">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div>
                                <label for="department_id" class="block text-gray-700 font-medium mb-2">Department</label>
                                <select id="department_id" name="department_id" required
                                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-university-primary-600 focus:border-transparent transition">
                                    <option value="">Select Department</option>
                                    <?php foreach ($departments as $dept): ?>
                                        <option value="<?= $dept['id'] ?>"><?= htmlspecialchars($dept['name']) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div>
                                <label for="document_name" class="block text-gray-700 font-medium mb-2">Document Name</label>
                                <input type="text" id="document_name" name="document_name" required
                                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-university-primary-600 focus:border-transparent transition">
                            </div>
                        </div>
                        <div>
                            <label for="description" class="block text-gray-700 font-medium mb-2">Description (Optional)</label>
                            <textarea id="description" name="description" rows="3"
                                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-university-primary-600 focus:border-transparent transition"></textarea>
                        </div>
                        <div class="pt-2">
                            <button type="submit" name="add_requirement" 
                                class="bg-university-primary-600 hover:bg-university-primary-700 text-white py-2 px-6 rounded-lg transition duration-200 flex items-center">
                                <i class="fas fa-save mr-2"></i>
                                Add Requirement
                            </button>
                        </div>
                    </form>
                </div>

                <!-- Requirements List -->
                <div class="bg-white rounded-2xl shadow-sm border border-gray-100 fade-in">
                    <div class="px-6 py-4 border-b border-gray-200">
                        <h2 class="text-xl font-bold text-gray-900 flex items-center">
                            <i class="fas fa-list-check text-university-primary-600 mr-2"></i>
                            Current Requirements
                        </h2>
                    </div>
                    
                    <?php if (empty($requirements)): ?>
                        <div class="p-6 text-center text-gray-500">
                            <i class="fas fa-inbox text-3xl mb-2 opacity-50"></i>
                            <p>No requirements added yet</p>
                        </div>
                    <?php else: ?>
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Department</th>
                                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Document</th>
                                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Description</th>
                                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white divide-y divide-gray-200">
                                    <?php foreach ($requirements as $req): ?>
                                        <tr class="hover:bg-gray-50 transition-colors">
                                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900"><?= htmlspecialchars($req['department_name']) ?></td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500"><?= htmlspecialchars($req['document_name']) ?></td>
                                            <td class="px-6 py-4 text-sm text-gray-500"><?= htmlspecialchars($req['description']) ?: 'N/A' ?></td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                                <a href="requirements.php?delete=<?= $req['id'] ?>"
                                                    class="text-red-600 hover:text-red-800 transition-colors"
                                                    onclick="return confirm('Are you sure you want to delete this requirement?')">
                                                    <i class="fas fa-trash-alt"></i>
                                                </a>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
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

            // Add confirmation for delete actions
            const deleteLinks = document.querySelectorAll('a[href*="delete"]');
            deleteLinks.forEach(link => {
                link.addEventListener('click', function(e) {
                    if (!confirm('Are you sure you want to delete this requirement?')) {
                        e.preventDefault();
                    }
                });
            });
        });
    </script>
</body>
</html>