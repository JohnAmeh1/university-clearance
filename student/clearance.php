<?php
require_once '../includes/config.php';
require_once '../includes/auth.php';

if (!is_logged_in() || !is_student()) {
    redirect('../login.php');
}

// Handle document upload
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['upload_document'])) {
    $requirement_id = $_POST['requirement_id'];

    // Check if file was uploaded
    if (isset($_FILES['document']) && $_FILES['document']['error'] === UPLOAD_ERR_OK) {
        $file = $_FILES['document'];

        // Validate file type and size
        $allowed_types = ['application/pdf', 'image/jpeg', 'image/png'];
        $max_size = 5 * 1024 * 1024; // 5MB

        if (!in_array($file['type'], $allowed_types)) {
            $_SESSION['error'] = "Only PDF, JPEG, and PNG files are allowed.";
        } elseif ($file['size'] > $max_size) {
            $_SESSION['error'] = "File size exceeds 5MB limit.";
        } else {
            // Generate unique filename
            $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
            $filename = uniqid() . '.' . $ext;
            $upload_path = '../uploads/' . $filename;

            if (move_uploaded_file($file['tmp_name'], $upload_path)) {
                try {
                    // Check if document already exists for this requirement
                    $stmt = $pdo->prepare("
                        SELECT id FROM student_documents 
                        WHERE student_id = ? AND requirement_id = ?
                    ");
                    $stmt->execute([$_SESSION['user_id'], $requirement_id]);

                    if ($stmt->fetch()) {
                        // Update existing document
                        $stmt = $pdo->prepare("
                            UPDATE student_documents 
                            SET document_path = ?, uploaded_at = NOW(), status = 'pending' 
                            WHERE student_id = ? AND requirement_id = ?
                        ");
                        $stmt->execute([$filename, $_SESSION['user_id'], $requirement_id]);
                    } else {
                        // Insert new document
                        $stmt = $pdo->prepare("
                            INSERT INTO student_documents 
                            (student_id, requirement_id, document_path, status) 
                            VALUES (?, ?, ?, 'pending')
                        ");
                        $stmt->execute([$_SESSION['user_id'], $requirement_id, $filename]);
                    }

                    $_SESSION['success'] = "Document uploaded successfully.";
                } catch (PDOException $e) {
                    $_SESSION['error'] = "Error uploading document: " . $e->getMessage();
                    // Delete the uploaded file if database operation failed
                    if (file_exists($upload_path)) {
                        unlink($upload_path);
                    }
                }
            } else {
                $_SESSION['error'] = "Error uploading file.";
            }
        }
    } else {
        $_SESSION['error'] = "Please select a file to upload.";
    }

    redirect('clearance.php');
}

// Get clearance requirements for student's department
$stmt = $pdo->prepare("
    SELECT cr.id, cr.document_name, cr.description, 
           sd.document_path, sd.status, sd.feedback
    FROM clearance_requirements cr
    LEFT JOIN student_documents sd ON cr.id = sd.requirement_id AND sd.student_id = ?
    WHERE cr.department_id = (SELECT id FROM departments WHERE name = ?)
    ORDER BY cr.document_name
");
$stmt->execute([$_SESSION['user_id'], $_SESSION['department']]);
$requirements = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get clearance status
$stmt = $pdo->prepare("
    SELECT is_complete FROM clearance_status WHERE student_id = ?
");
$stmt->execute([$_SESSION['user_id']]);
$clearance_status = $stmt->fetch();

$is_complete = $clearance_status ? $clearance_status['is_complete'] : false;
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Clearance Checklist - University Clearance System</title>
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
        @media (max-width: 767px) {
            #sidebar {
                transition: transform 0.3s ease-in-out;
                z-index: 40;
            }

            .upload-form {
                flex-direction: column;
                gap: 0.5rem;
            }

            .upload-form button,
            .upload-form input[type="file"] {
                width: 100%;
            }
        }
    </style>
</head>

<body class="bg-gradient-to-br from-slate-50 to-blue-50 font-sans">
    <!-- Loading Overlay -->
    <div id="loading-overlay" class="fixed inset-0 bg-white z-50 flex items-center justify-center opacity-0 pointer-events-none transition-opacity duration-300">
        <div class="text-center">
            <div class="animate-spin rounded-full h-16 w-16 border-b-2 border-university-blue mb-4"></div>
            <p class="text-university-blue font-medium">Loading Clearance...</p>
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
                
                <a href="dashboard.php" class="nav-item flex items-center space-x-3 px-4 py-3 hover:bg-white hover:bg-opacity-20 rounded-xl transition-all duration-200 group">
                    <i class="fas fa-tachometer-alt text-lg group-hover:scale-110 transition-transform"></i>
                    <span class="font-medium">Dashboard</span>
                </a>

                <a href="clearance.php" class="nav-item active flex items-center space-x-3 px-4 py-3 bg-white bg-opacity-20 rounded-xl transition-all duration-200 hover:bg-opacity-30 group">
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
                <div class="flex flex-col md:flex-row md:justify-between md:items-center gap-2">
                    <div>
                        <h1 class="text-2xl md:text-3xl font-bold text-gray-900">Clearance Checklist</h1>
                        <p class="text-gray-600 mt-1">Upload required documents for your department clearance</p>
                    </div>
                    <div>
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
            </header>

            <!-- Dashboard Content -->
            <main class="p-6 md:p-8 space-y-6">
                <?php if (isset($_SESSION['error'])): ?>
                    <div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 rounded-lg fade-in">
                        <div class="flex items-center">
                            <i class="fas fa-exclamation-circle mr-3"></i>
                            <div>
                                <p class="font-medium">Error</p>
                                <p><?= htmlspecialchars($_SESSION['error']) ?></p>
                            </div>
                        </div>
                    </div>
                    <?php unset($_SESSION['error']); ?>
                <?php endif; ?>

                <?php if (isset($_SESSION['success'])): ?>
                    <div class="bg-green-100 border-l-4 border-green-500 text-green-700 p-4 rounded-lg fade-in">
                        <div class="flex items-center">
                            <i class="fas fa-check-circle mr-3"></i>
                            <div>
                                <p class="font-medium">Success</p>
                                <p><?= htmlspecialchars($_SESSION['success']) ?></p>
                            </div>
                        </div>
                    </div>
                    <?php unset($_SESSION['success']); ?>
                <?php endif; ?>

                <!-- Requirements List -->
                <div class="bg-white p-6 rounded-2xl shadow-sm border border-gray-100 fade-in hover-lift">
                    <?php if (empty($requirements)): ?>
                        <p class="text-gray-500 text-center py-4">No clearance requirements found for your department.</p>
                    <?php else: ?>
                        <div class="space-y-6">
                            <?php foreach ($requirements as $req): ?>
                                <div class="border-b border-gray-200 pb-6 last:border-b-0 last:pb-0">
                                    <div class="flex flex-col md:flex-row md:justify-between md:items-start gap-4 mb-3">
                                        <div class="flex-1">
                                            <h3 class="font-bold text-lg text-gray-900"><?= htmlspecialchars($req['document_name']) ?></h3>
                                            <?php if ($req['description']): ?>
                                                <p class="text-gray-600 mt-1"><?= htmlspecialchars($req['description']) ?></p>
                                            <?php endif; ?>
                                        </div>
                                        <div>
                                            <?php if ($req['status'] === 'approved'): ?>
                                                <span class="bg-green-100 text-green-800 px-3 py-1 rounded-full text-sm font-medium">
                                                    Approved
                                                </span>
                                            <?php elseif ($req['status'] === 'rejected'): ?>
                                                <span class="bg-red-100 text-red-800 px-3 py-1 rounded-full text-sm font-medium">
                                                    Rejected
                                                </span>
                                            <?php else: ?>
                                                <span class="bg-yellow-100 text-yellow-800 px-3 py-1 rounded-full text-sm font-medium">
                                                    <?= $req['document_path'] ? 'Pending' : 'Missing' ?>
                                                </span>
                                            <?php endif; ?>
                                        </div>
                                    </div>

                                    <?php if ($req['feedback']): ?>
                                        <div class="bg-gray-50 p-4 rounded-lg mb-4">
                                            <p class="font-medium text-gray-700 mb-1">Feedback:</p>
                                            <p class="text-gray-600"><?= htmlspecialchars($req['feedback']) ?></p>
                                        </div>
                                    <?php endif; ?>

                                    <?php if ($is_complete || $req['status'] === 'approved'): ?>
                                        <div class="flex flex-col md:flex-row md:items-center gap-3 upload-form">
                                            <div class="flex-1">
                                                <input type="file"
                                                    disabled
                                                    class="block w-full text-sm text-gray-400
                                                              file:mr-4 file:py-2 file:px-4
                                                              file:rounded file:border-0
                                                              file:text-sm file:font-semibold
                                                              file:bg-gray-200 file:text-gray-500
                                                              cursor-not-allowed">
                                            </div>
                                            <button type="button"
                                                disabled
                                                class="px-4 py-2 bg-gray-400 text-white rounded-lg cursor-not-allowed text-sm">
                                                Upload Disabled
                                            </button>
                                        </div>
                                    <?php else: ?>
                                        <form method="POST" enctype="multipart/form-data" class="flex flex-col md:flex-row md:items-center gap-3 upload-form">
                                            <input type="hidden" name="requirement_id" value="<?= htmlspecialchars($req['id']) ?>">
                                            <div class="flex-1">
                                                <input type="file" name="document" id="document_<?= htmlspecialchars($req['id']) ?>"
                                                    class="block w-full text-sm text-gray-500
                                                              file:mr-4 file:py-2 file:px-4
                                                              file:rounded file:border-0
                                                              file:text-sm file:font-semibold
                                                              file:bg-blue-50 file:text-blue-700
                                                              hover:file:bg-blue-100" required>
                                            </div>
                                            <button type="submit" name="upload_document"
                                                class="px-4 py-2 bg-university-blue text-white rounded-lg hover:bg-university-dark transition-colors text-sm">
                                                Upload
                                            </button>
                                        </form>
                                    <?php endif; ?>

                                    <?php if ($req['document_path']): ?>
                                        <div class="mt-3">
                                            <a href="../uploads/<?= htmlspecialchars($req['document_path']) ?>"
                                                target="_blank"
                                                class="inline-flex items-center text-university-blue hover:text-university-dark hover:underline">
                                                <i class="fas fa-file-alt mr-2"></i> View Uploaded Document
                                            </a>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </main>
        </div>
    </div>

    <!-- Mobile Sidebar Overlay -->
    <div id="sidebar-overlay" class="fixed inset-0 bg-black bg-opacity-50 z-30 opacity-0 pointer-events-none transition-opacity duration-300 md:hidden"></div>

    <script>
        // Enhanced Dashboard JavaScript
        document.addEventListener('DOMContentLoaded', function() {
            // Initialize dashboard components
            const sidebar = document.getElementById('sidebar');
            const sidebarToggle = document.getElementById('sidebar-toggle');
            const sidebarOverlay = document.getElementById('sidebar-overlay');
            const loadingOverlay = document.getElementById('loading-overlay');
            
            // Show loading animation
            loadingOverlay.classList.remove('opacity-0', 'pointer-events-none');
            loadingOverlay.classList.add('opacity-100');
            
            // Hide loading after initialization
            setTimeout(() => {
                loadingOverlay.classList.add('opacity-0', 'pointer-events-none');
                loadingOverlay.classList.remove('opacity-100');
            }, 500);

            // Sidebar toggle
            sidebarToggle.addEventListener('click', () => {
                toggleSidebar();
            });

            // Sidebar overlay click
            sidebarOverlay.addEventListener('click', () => {
                closeSidebar();
            });

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
                sidebar.classList.add('slide-in');
                sidebarOverlay.classList.remove('opacity-0', 'pointer-events-none');
                sidebarOverlay.classList.add('opacity-100');
                document.body.classList.add('overflow-hidden');
            }

            function closeSidebar() {
                sidebar.classList.add('-translate-x-full');
                sidebar.classList.remove('slide-in');
                sidebarOverlay.classList.add('opacity-0', 'pointer-events-none');
                sidebarOverlay.classList.remove('opacity-100');
                document.body.classList.remove('overflow-hidden');
            }

            // Handle window resize
            window.addEventListener('resize', () => {
                if (window.innerWidth >= 768) {
                    closeSidebar();
                }
            });

            // Update current date
            function updateDateTime() {
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
            }

            updateDateTime();

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
        });
    </script>
</body>

</html>