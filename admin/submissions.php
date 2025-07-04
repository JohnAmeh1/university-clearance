<?php
require_once '../includes/config.php';
require_once '../includes/auth.php';

if (!is_logged_in() || !is_admin()) {
    redirect('../login.php');
}

// Handle approval
if (isset($_GET['approve'])) {
    $student_id = $_GET['approve'];

    try {
        // Check if all documents are approved
        $stmt = $pdo->prepare("
            SELECT COUNT(*) FROM student_documents 
            WHERE student_id = ? AND status != 'approved'
        ");
        $stmt->execute([$student_id]);
        $pending_count = $stmt->fetchColumn();

        if ($pending_count > 0) {
            $_SESSION['error'] = "Cannot complete clearance. Some documents are still pending or rejected.";
        } else {
            // Update clearance status
            $stmt = $pdo->prepare("
                INSERT INTO clearance_status (student_id, is_complete, completed_at, approved_by, approved_at) 
                VALUES (?, TRUE, NOW(), ?, NOW())
                ON DUPLICATE KEY UPDATE is_complete = TRUE, completed_at = NOW(), approved_by = ?, approved_at = NOW()
            ");
            $stmt->execute([$student_id, $_SESSION['user_id'], $_SESSION['user_id']]);

            $_SESSION['success'] = "Clearance completed successfully for student ID: $student_id";
        }
    } catch (PDOException $e) {
        $_SESSION['error'] = "Error completing clearance: " . $e->getMessage();
    }

    redirect('submissions.php');
}

// Handle document approval/rejection
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_document'])) {
    $document_id = $_POST['document_id'];
    $status = $_POST['status'];
    $feedback = trim($_POST['feedback']);

    try {
        $stmt = $pdo->prepare("UPDATE student_documents SET status = ?, feedback = ? WHERE id = ?");
        $stmt->execute([$status, $feedback, $document_id]);

        $_SESSION['success'] = "Document status updated successfully";
    } catch (PDOException $e) {
        $_SESSION['error'] = "Error updating document: " . $e->getMessage();
    }

    redirect('submissions.php');
}

// Get all departments with their students
$departments = $pdo->query("
    SELECT d.id, d.name, 
           u.id as student_id, u.full_name, 
           cs.is_complete as clearance_status
    FROM departments d
    LEFT JOIN users u ON d.name = u.department AND u.user_role = 'student'
    LEFT JOIN clearance_status cs ON u.id = cs.student_id
    ORDER BY d.name, u.full_name
")->fetchAll(PDO::FETCH_ASSOC);

// Organize students by department
$departmentGroups = [];
foreach ($departments as $row) {
    $deptId = $row['id'];
    if (!isset($departmentGroups[$deptId])) {
        $departmentGroups[$deptId] = [
            'name' => $row['name'],
            'students' => []
        ];
    }

    if ($row['student_id']) {
        $departmentGroups[$deptId]['students'][$row['student_id']] = [
            'full_name' => $row['full_name'],
            'clearance_status' => $row['clearance_status']
        ];
    }
}

// Get all student documents
$documents = $pdo->query("
    SELECT sd.student_id, sd.id as document_id, sd.requirement_id, 
           sd.document_path, sd.status, sd.uploaded_at, sd.feedback,
           cr.document_name
    FROM student_documents sd
    JOIN clearance_requirements cr ON sd.requirement_id = cr.id
    ORDER BY sd.student_id, sd.uploaded_at DESC
")->fetchAll(PDO::FETCH_ASSOC);

// Add documents to students
foreach ($documents as $doc) {
    foreach ($departmentGroups as &$dept) {
        if (isset($dept['students'][$doc['student_id']])) {
            if (!isset($dept['students'][$doc['student_id']]['documents'])) {
                $dept['students'][$doc['student_id']]['documents'] = [];
            }
            $dept['students'][$doc['student_id']]['documents'][] = [
                'id' => $doc['document_id'],
                'requirement_id' => $doc['requirement_id'],
                'document_name' => $doc['document_name'],
                'document_path' => $doc['document_path'],
                'status' => $doc['status'],
                'uploaded_at' => $doc['uploaded_at'],
                'feedback' => $doc['feedback']
            ];
        }
    }
}
unset($dept);
?>

<!DOCTYPE html>
<html lang="en" class="h-full">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="University Clearance System Admin Dashboard">
    <title>Student Submissions | University Clearance System</title>

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
            from {
                opacity: 0;
                transform: translateY(10px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .slide-in {
            animation: slideIn 0.3s ease-out;
        }

        @keyframes slideIn {
            from {
                transform: translateX(-100%);
            }

            to {
                transform: translateX(0);
            }
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

            0%,
            100% {
                opacity: 1;
            }

            50% {
                opacity: 0.7;
            }
        }

        /* Focus styles for accessibility */
        a:focus,
        button:focus {
            outline: 2px solid var(--university-primary-600);
            outline-offset: 2px;
        }

        @media (max-width: 767px) {
            .student-card {
                flex-direction: column;
            }

            .status-badge {
                margin-top: 0.5rem;
                justify-content: flex-start;
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

                <a href="requirements.php" class="nav-item flex items-center space-x-3 px-4 py-3 hover:bg-white hover:bg-opacity-20 rounded-xl transition-all duration-200 group">
                    <i class="fas fa-list-check text-lg group-hover:scale-110 transition-transform"></i>
                    <span class="font-medium">Manage Requirements</span>
                </a>

                <a href="submissions.php" class="nav-item flex items-center space-x-3 px-4 py-3 bg-white bg-opacity-20 rounded-xl transition-all duration-200 group">
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
                        <h1 class="text-2xl md:text-3xl font-bold text-gray-900">Student Submissions</h1>
                        <p class="text-gray-600 mt-1">Review and manage student document submissions</p>
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
                <?php if (isset($_SESSION['error'])): ?>
                    <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded-lg fade-in">
                        <?= $_SESSION['error'];
                        unset($_SESSION['error']); ?>
                    </div>
                <?php endif; ?>

                <?php if (isset($_SESSION['success'])): ?>
                    <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded-lg fade-in">
                        <?= $_SESSION['success'];
                        unset($_SESSION['success']); ?>
                    </div>
                <?php endif; ?>

                <!-- Department Accordions -->
                <div class="space-y-6">
                    <?php foreach ($departmentGroups as $deptId => $department): ?>
                        <div class="bg-white rounded-2xl shadow-sm hover-lift border border-gray-100 fade-in">
                            <!-- Department Header -->
                            <div class="flex justify-between items-center p-4 md:p-5 cursor-pointer border-b border-gray-200"
                                onclick="toggleDepartment(<?= $deptId ?>)">
                                <h2 class="text-lg md:text-xl font-bold text-gray-900">
                                    <?= htmlspecialchars($department['name']) ?>
                                    <span class="text-xs md:text-sm font-normal text-gray-600 ml-2">
                                        (<?= count($department['students']) ?> students)
                                    </span>
                                </h2>
                                <i id="dept-icon-<?= $deptId ?>" class="fas fa-chevron-right text-gray-500"></i>
                            </div>

                            <!-- Department Content -->
                            <div id="dept-content-<?= $deptId ?>" class="hidden p-4 md:p-5">
                                <?php if (empty($department['students'])): ?>
                                    <p class="text-gray-500">No students in this department have submitted documents.</p>
                                <?php else: ?>
                                    <div class="space-y-5">
                                        <?php foreach ($department['students'] as $studentId => $student): ?>
                                            <div class="border rounded-xl p-4 md:p-5 bg-gray-50">
                                                <div class="flex flex-col md:flex-row md:justify-between md:items-center mb-4 student-card">
                                                    <div>
                                                        <h3 class="text-base md:text-lg font-bold text-gray-900"><?= htmlspecialchars($student['full_name']) ?></h3>
                                                    </div>
                                                    <div class="flex items-center status-badge">
                                                        <?php if ($student['clearance_status']): ?>
                                                            <span class="bg-green-100 text-green-800 px-3 py-1 rounded-full text-xs md:text-sm font-medium">
                                                                Clearance Completed
                                                            </span>
                                                        <?php else: ?>
                                                            <span class="bg-yellow-100 text-yellow-800 px-3 py-1 rounded-full text-xs md:text-sm font-medium">
                                                                Clearance Incomplete
                                                            </span>
                                                            <?php if (!empty($student['documents'])): ?>
                                                                <a href="submissions.php?approve=<?= $studentId ?>"
                                                                    class="ml-2 bg-university-primary-600 text-white px-3 py-1 rounded-lg text-xs md:text-sm font-medium hover:bg-university-primary-700 transition-colors"
                                                                    onclick="return confirm('Complete clearance for <?= htmlspecialchars($student['full_name']) ?>?')">
                                                                    Complete
                                                                </a>
                                                            <?php endif; ?>
                                                        <?php endif; ?>
                                                    </div>
                                                </div>

                                                <?php if (empty($student['documents'])): ?>
                                                    <p class="text-gray-500">No documents submitted yet.</p>
                                                <?php else: ?>
                                                    <div class="overflow-x-auto">
                                                        <table class="min-w-full bg-white text-sm rounded-lg overflow-hidden">
                                                            <thead class="bg-gray-100">
                                                                <tr>
                                                                    <th class="py-3 px-4 text-left font-semibold text-gray-700">Document</th>
                                                                    <th class="py-3 px-4 text-left font-semibold text-gray-700">Status</th>
                                                                    <th class="py-3 px-4 text-left font-semibold text-gray-700">Actions</th>
                                                                </tr>
                                                            </thead>
                                                            <tbody class="divide-y divide-gray-200">
                                                                <?php foreach ($student['documents'] as $doc): ?>
                                                                    <tr>
                                                                        <td class="py-3 px-4">
                                                                            <div class="font-medium text-gray-900"><?= htmlspecialchars($doc['document_name']) ?></div>
                                                                            <div class="text-gray-500 text-xs mt-1">
                                                                                <?= date('M d, Y \a\t g:i A', strtotime($doc['uploaded_at'])) ?>
                                                                            </div>
                                                                            <?php if ($doc['feedback']): ?>
                                                                                <div class="text-gray-600 mt-1 text-xs">
                                                                                    <span class="font-semibold">Feedback:</span>
                                                                                    <?= htmlspecialchars($doc['feedback']) ?>
                                                                                </div>
                                                                            <?php endif; ?>
                                                                        </td>
                                                                        <td class="py-3 px-4">
                                                                            <?php if ($doc['status'] === 'approved'): ?>
                                                                                <span class="bg-green-100 text-green-800 px-3 py-1 rounded-full text-xs">Approved</span>
                                                                            <?php elseif ($doc['status'] === 'rejected'): ?>
                                                                                <span class="bg-red-100 text-red-800 px-3 py-1 rounded-full text-xs">Rejected</span>
                                                                            <?php else: ?>
                                                                                <span class="bg-yellow-100 text-yellow-800 px-3 py-1 rounded-full text-xs">Pending</span>
                                                                            <?php endif; ?>
                                                                        </td>
                                                                        <td class="py-3 px-4">
                                                                            <div class="flex space-x-3">
                                                                                <a href="../uploads/<?= htmlspecialchars($doc['document_path']) ?>"
                                                                                    target="_blank"
                                                                                    class="text-university-primary-600 hover:text-university-primary-800"
                                                                                    title="View Document">
                                                                                    <i class="fas fa-eye"></i>
                                                                                </a>
                                                                                <button onclick="openModal('<?= $doc['id'] ?>', '<?= $doc['status'] ?>', '<?= htmlspecialchars(addslashes($doc['feedback'] ?? '')) ?>')"
                                                                                    class="text-university-primary-600 hover:text-university-primary-800"
                                                                                    title="Edit Status">
                                                                                    <i class="fas fa-edit"></i>
                                                                                </button>
                                                                            </div>
                                                                        </td>
                                                                    </tr>
                                                                <?php endforeach; ?>
                                                            </tbody>
                                                        </table>
                                                    </div>
                                                <?php endif; ?>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </main>
        </div>
    </div>

    <!-- Modal for updating document status -->
    <div id="documentModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center hidden z-50">
        <div class="bg-white p-6 rounded-2xl shadow-lg w-full max-w-md mx-4 fade-in">
            <div class="flex justify-between items-center mb-4">
                <h3 class="text-lg md:text-xl font-bold text-gray-900">Update Document Status</h3>
                <button onclick="closeModal()" class="text-gray-500 hover:text-gray-700">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <form method="POST" id="documentForm">
                <input type="hidden" name="document_id" id="modalDocumentId">
                <div class="mb-4">
                    <label class="block text-gray-700 mb-2 font-medium">Status</label>
                    <div class="flex flex-wrap gap-4">
                        <label class="inline-flex items-center">
                            <input type="radio" name="status" value="approved" class="form-radio text-green-600">
                            <span class="ml-2">Approved</span>
                        </label>
                        <label class="inline-flex items-center">
                            <input type="radio" name="status" value="rejected" class="form-radio text-red-600">
                            <span class="ml-2">Rejected</span>
                        </label>
                        <label class="inline-flex items-center">
                            <input type="radio" name="status" value="pending" class="form-radio text-yellow-600">
                            <span class="ml-2">Pending</span>
                        </label>
                    </div>
                </div>
                <div class="mb-5">
                    <label for="feedback" class="block text-gray-700 mb-2 font-medium">Feedback</label>
                    <textarea id="feedback" name="feedback" rows="3"
                        class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-university-primary-600 focus:border-transparent"></textarea>
                </div>
                <div class="flex justify-end space-x-3">
                    <button type="button" onclick="closeModal()"
                        class="px-4 py-2 bg-gray-200 text-gray-800 rounded-lg hover:bg-gray-300 transition-colors">
                        Cancel
                    </button>
                    <button type="submit" name="update_document"
                        class="px-4 py-2 bg-university-primary-600 text-white rounded-lg hover:bg-university-primary-700 transition-colors">
                        Update
                    </button>
                </div>
            </form>
        </div>
    </div>
    <!-- Continuing from previous code -->

    <script>
        // Enhanced JavaScript functionality
        document.addEventListener('DOMContentLoaded', function() {
            // Initialize elements
            const sidebarToggle = document.getElementById('sidebar-toggle');
            const sidebar = document.getElementById('sidebar');
            const mainContent = document.getElementById('main-content');
            const currentDateElement = document.getElementById('current-date');

            // Toggle sidebar with animation
            sidebarToggle.addEventListener('click', function() {
                sidebar.classList.toggle('-translate-x-full');
                mainContent.classList.toggle('md:ml-0');
                document.body.classList.toggle('overflow-hidden');
            });

            // Close sidebar when clicking outside on mobile
            document.addEventListener('click', function(event) {
                const isClickInsideSidebar = sidebar.contains(event.target);
                const isClickOnToggle = sidebarToggle.contains(event.target);

                if (!isClickInsideSidebar && !isClickOnToggle && window.innerWidth < 768) {
                    sidebar.classList.add('-translate-x-full');
                    mainContent.classList.remove('md:ml-0');
                    document.body.classList.remove('overflow-hidden');
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
                currentDateElement.textContent = now.toLocaleDateString('en-US', options);
            }
            updateCurrentDate();

            // Add hover effects to department cards
            const departmentCards = document.querySelectorAll('[onclick^="toggleDepartment"]');
            departmentCards.forEach(card => {
                card.addEventListener('mouseenter', () => {
                    card.style.transform = 'translateY(-2px)';
                    card.style.boxShadow = '0 4px 6px rgba(0, 0, 0, 0.1)';
                });
                card.addEventListener('mouseleave', () => {
                    card.style.transform = 'translateY(0)';
                    card.style.boxShadow = 'none';
                });
            });

            // Add loading state to form submission
            const documentForm = document.getElementById('documentForm');
            if (documentForm) {
                documentForm.addEventListener('submit', function() {
                    const submitButton = this.querySelector('button[type="submit"]');
                    submitButton.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Updating...';
                    submitButton.disabled = true;
                });
            }

            // Add keyboard navigation for modal
            document.addEventListener('keydown', function(event) {
                const modal = document.getElementById('documentModal');
                if (!modal.classList.contains('hidden')) {
                    if (event.key === 'Escape') {
                        closeModal();
                    }
                    if (event.key === 'Tab') {
                        const focusableElements = modal.querySelectorAll('button, input, textarea');
                        const firstElement = focusableElements[0];
                        const lastElement = focusableElements[focusableElements.length - 1];

                        if (event.shiftKey) {
                            if (document.activeElement === firstElement) {
                                lastElement.focus();
                                event.preventDefault();
                            }
                        } else {
                            if (document.activeElement === lastElement) {
                                firstElement.focus();
                                event.preventDefault();
                            }
                        }
                    }
                }
            });

            // Make department sections remember their state
            const departmentSections = document.querySelectorAll('[id^="dept-content-"]');
            departmentSections.forEach(section => {
                const deptId = section.id.split('-')[2];
                const storedState = localStorage.getItem(`deptState-${deptId}`);

                if (storedState === 'open') {
                    toggleDepartment(deptId);
                }

                // Add click handler to store state
                const header = document.querySelector(`[onclick="toggleDepartment(${deptId})"]`);
                header.addEventListener('click', function() {
                    setTimeout(() => {
                        const isOpen = !section.classList.contains('hidden');
                        localStorage.setItem(`deptState-${deptId}`, isOpen ? 'open' : 'closed');
                    }, 100);
                });
            });
        });

        // Enhanced department toggle with animation
        function toggleDepartment(deptId) {
            const content = document.getElementById('dept-content-' + deptId);
            const icon = document.getElementById('dept-icon-' + deptId);

            if (content.classList.contains('hidden')) {
                // Open department
                content.classList.remove('hidden');
                content.style.maxHeight = '0';
                content.style.overflow = 'hidden';
                content.style.transition = 'max-height 0.3s ease-out';

                setTimeout(() => {
                    content.style.maxHeight = content.scrollHeight + 'px';
                    setTimeout(() => {
                        content.style.maxHeight = 'none';
                    }, 300);
                }, 10);

                icon.classList.replace('fa-chevron-right', 'fa-chevron-down');
            } else {
                // Close department
                content.style.maxHeight = content.scrollHeight + 'px';
                content.style.overflow = 'hidden';

                setTimeout(() => {
                    content.style.maxHeight = '0';
                    setTimeout(() => {
                        content.classList.add('hidden');
                        content.style.maxHeight = 'none';
                    }, 300);
                }, 10);

                icon.classList.replace('fa-chevron-down', 'fa-chevron-right');
            }
        }

        // Enhanced modal functions
        function openModal(documentId, currentStatus, currentFeedback) {
            const modal = document.getElementById('documentModal');
            const form = document.getElementById('documentForm');

            // Set values
            document.getElementById('modalDocumentId').value = documentId;
            document.getElementById('feedback').value = currentFeedback || '';

            // Set status
            const statusRadios = form.elements['status'];
            for (let i = 0; i < statusRadios.length; i++) {
                if (statusRadios[i].value === currentStatus) {
                    statusRadios[i].checked = true;
                    break;
                }
            }

            // Show modal with animation
            modal.classList.remove('hidden');
            modal.querySelector('div').classList.add('animate-scale-in');

            // Focus first interactive element
            setTimeout(() => {
                const firstInput = modal.querySelector('input[type="radio"]:checked') ||
                    modal.querySelector('input[type="radio"]');
                if (firstInput) firstInput.focus();
            }, 100);
        }

        function closeModal() {
            const modal = document.getElementById('documentModal');
            modal.querySelector('div').classList.remove('animate-scale-in');
            modal.querySelector('div').classList.add('animate-scale-out');

            setTimeout(() => {
                modal.classList.add('hidden');
                modal.querySelector('div').classList.remove('animate-scale-out');
            }, 200);
        }

        // Add animation styles dynamically
        const style = document.createElement('style');
        style.textContent = `
        @keyframes scaleIn {
            from { transform: scale(0.95); opacity: 0; }
            to { transform: scale(1); opacity: 1; }
        }
        @keyframes scaleOut {
            from { transform: scale(1); opacity: 1; }
            to { transform: scale(0.95); opacity: 0; }
        }
        .animate-scale-in {
            animation: scaleIn 0.2s ease-out forwards;
        }
        .animate-scale-out {
            animation: scaleOut 0.2s ease-in forwards;
        }
    `;
        document.head.appendChild(style);
    </script>

    <!-- Add print styles -->
    <style>
        @media print {

            #sidebar,
            #sidebar-toggle,
            .no-print {
                display: none !important;
            }

            #main-content {
                margin-left: 0 !important;
                width: 100% !important;
            }

            .student-card {
                page-break-inside: avoid;
            }

            table {
                page-break-inside: auto;
            }

            tr {
                page-break-inside: avoid;
                page-break-after: auto;
            }
        }
    </style>

    <!-- Add a print button -->
    <div class="fixed bottom-6 right-6 no-print">
        <button onclick="window.print()" class="bg-university-primary-600 text-white p-3 rounded-full shadow-lg hover:bg-university-primary-700 transition-colors">
            <i class="fas fa-print"></i>
        </button>
    </div>

</body>

</html>