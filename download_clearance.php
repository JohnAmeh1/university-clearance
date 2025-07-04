<?php
require_once 'includes/config.php';
require_once 'includes/auth.php';

if (!is_logged_in() || !is_student()) {
    redirect('login.php');
}

$student_id = $_GET['student_id'] ?? 0;

// Verify the student is requesting their own clearance form
if ($student_id != $_SESSION['user_id']) {
    die("Unauthorized access");
}

// Get student and clearance info
$stmt = $pdo->prepare("
    SELECT u.username, u.full_name, u.department, cs.approved_at 
    FROM users u
    JOIN clearance_status cs ON u.id = cs.student_id
    WHERE u.id = ?
");
$stmt->execute([$student_id]);
$student = $stmt->fetch();

if (!$student) {
    die("Clearance information not found");
}

// Generate HTML that can be printed as PDF
header('Content-Type: text/html');
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <title>Clearance Certificate - University Clearance System</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        'certificate-blue': '#1a3e72',
                        'certificate-light-blue': '#2c5a96'
                    },
                    fontFamily: {
                        'sans': ['Inter', 'system-ui', 'sans-serif'],
                        'serif': ['"Playfair Display"', 'serif']
                    },
                    backgroundImage: {
                        'certificate-pattern': "url('data:image/svg+xml;utf8,<svg width=\"100\" height=\"100\" viewBox=\"0 0 100 100\" xmlns=\"http://www.w3.org/2000/svg\"><path fill=\"%23f0f4ff\" d=\"M30,10 L70,10 L90,30 L90,70 L70,90 L30,90 L10,70 L10,30 Z\" /></svg>')"
                    }
                }
            }
        }
    </script>
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@700&family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        @page {
            size: A4;
            margin: 0;
        }
        
        body {
            width: 100%;
            min-height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
            margin: 0;
            padding: 20px;
            background-color: #f3f4f6;
        }

        .certificate {
            width: 210mm;
            height: 297mm;
            position: relative;
            box-sizing: border-box;
            margin: 0 auto;
        }

        @media print {
            body {
                width: 210mm;
                height: 297mm;
                background: white !important;
                padding: 0;
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
                display: block;
            }

            .certificate {
                border: 15px solid #1a3e72 !important;
                box-shadow: none !important;
                page-break-after: avoid;
                page-break-inside: avoid;
                background-image: none !important;
                margin: 0;
            }

            .no-print {
                display: none !important;
            }
        }

        .certificate::before {
            content: "";
            position: absolute;
            top: 20px;
            left: 20px;
            right: 20px;
            bottom: 20px;
            border: 1px solid rgba(26, 62, 114, 0.2);
            pointer-events: none;
        }

        .certificate-title::after {
            content: "";
            display: block;
            width: 100px;
            height: 3px;
            background: linear-gradient(90deg, #1a3e72, #4a89dc);
            margin: 15px auto 0;
        }

        .watermark {
            position: absolute;
            font-size: 120px;
            color: rgba(26, 62, 114, 0.05);
            font-weight: bold;
            transform: rotate(-30deg);
            z-index: 0;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%) rotate(-30deg);
            pointer-events: none;
            user-select: none;
        }

        /* Ensure content fits on one A4 page */
        .certificate-content {
            height: calc(100% - 200px);
            display: flex;
            flex-direction: column;
            justify-content: space-between;
        }
    </style>
</head>

<body class="font-sans">
    <div class="certificate bg-white border-[15px] border-certificate-blue bg-certificate-pattern bg-[length:150px] bg-center p-12">
        <div class="watermark">CLEARANCE</div>

        <div class="certificate-content">
            <div>
                <div class="header text-center mb-8">
                    <img src="img/download.jpeg" alt="University Logo" class="university-logo h-20 mx-auto mb-3" />
                    <div class="university-name font-serif text-3xl font-bold text-certificate-blue tracking-wider mb-1 uppercase">Bingham University</div>
                    <div class="certificate-title text-xl font-semibold text-certificate-blue tracking-wide uppercase relative inline-block">
                        Official Clearance Certificate
                    </div>
                    <div class="certificate-id text-xs text-gray-600 mt-2 tracking-wide">
                        Certificate #: <?= date('Y') . '-' . str_pad($student_id, 5, '0', STR_PAD_LEFT) ?>
                    </div>
                </div>

                <div class="content">
                    <strong class="block text-xl text-certificate-blue text-center mb-4 font-medium">This is to certify that:</strong>
                    <table class="details-table my-6 w-full max-w-lg mx-auto">
                        <tr class="border-b border-gray-200">
                            <td class="label py-2 px-4 font-semibold text-certificate-blue w-40">Student Name:</td>
                            <td class="py-2 px-4"><?= htmlspecialchars($student['full_name']) ?></td>
                        </tr>
                        <tr class="border-b border-gray-200">
                            <td class="label py-2 px-4 font-semibold text-certificate-blue">Matriculation Number:</td>
                            <td class="py-2 px-4"><?= htmlspecialchars($student['username'] ?? 'N/A') ?></td>
                        </tr>
                        <tr class="border-b border-gray-200">
                            <td class="label py-2 px-4 font-semibold text-certificate-blue">Department:</td>
                            <td class="py-2 px-4"><?= htmlspecialchars($student['department']) ?></td>
                        </tr>
                        <tr>
                            <td class="label py-2 px-4 font-semibold text-certificate-blue">Date of Approval:</td>
                            <td class="py-2 px-4"><?= date('F j, Y', strtotime($student['approved_at'])) ?></td>
                        </tr>
                    </table>

                    <div class="success-statement mt-8 text-center font-semibold text-xl text-green-800 tracking-wide py-4 px-5 bg-green-50 border-l-4 border-green-800 max-w-2xl mx-auto">
                        HAS SUCCESSFULLY COMPLETED ALL INSTITUTIONAL CLEARANCE REQUIREMENTS
                    </div>
                </div>
            </div>

            <div class="signature-section mt-12 text-right relative">
                <div class="signature-label text-lg font-semibold text-certificate-blue mb-4">FOR: BINGHAM UNIVERSITY</div>
                <div class="flex justify-end items-end">
                    <div class="text-right mr-4">
                        <img src="img/seal.jpeg" alt="University Seal" class="official-seal w-24 opacity-80 mb-2 mx-auto" />
                        <div class="signature-line border-t-2 border-certificate-blue w-64 inline-block my-2"></div>
                        <div class="signatory text-gray-700 text-sm italic">Professor John Ameh</div>
                        <div class="signatory text-gray-700 text-sm italic">Registrar / Authorized Signatory</div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="no-print fixed bottom-8 right-8">
        <button onclick="window.print()" class="px-6 py-2 text-base bg-gradient-to-br from-certificate-blue to-certificate-light-blue text-white rounded-lg shadow-lg hover:shadow-xl transition-all duration-200">
            <i class="fas fa-print mr-2"></i> Print Certificate
        </button>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Focus print button for better UX
            const printBtn = document.querySelector('button');
            if (printBtn) printBtn.focus();
        });
    </script>
</body>

</html>