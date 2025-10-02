<?php
require_once 'config.php';
require_once 'auth.php';

requireRole('admin');

// Handle delete action
if (isset($_GET['delete_id'])) {
    $delete_id = intval($_GET['delete_id']);
    $conn->query("DELETE FROM daily_reports WHERE id = $delete_id");
    header("Location: daily_report.php?success=Report deleted successfully");
    exit();
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $report_date = $_POST['report_date'];
    $preacher_name = $_POST['preacher_name'];
    $coordinator_name = $_POST['coordinator_name'];
    $session = $_POST['session'];
    $sermon_start = $_POST['sermon_start'];
    $sermon_end = $_POST['sermon_end'];
    $prayer_start = $_POST['prayer_start'];
    $prayer_end = $_POST['prayer_end'];
    
    // Department scores
    $worship_team_score = intval($_POST['worship_team_score']);
    $instrumentalists_score = intval($_POST['instrumentalists_score']);
    $technical_sound_score = intval($_POST['technical_sound_score']);
    $prayer_leaders_score = intval($_POST['prayer_leaders_score']);
    $security_score = intval($_POST['security_score']);
    $interpretation_score = intval($_POST['interpretation_score']);
    $media_score = intval($_POST['media_score']);
    $ushering_score = intval($_POST['ushering_score']);
    $bible_reading_score = intval($_POST['bible_reading_score']);
    
    // Pastors attendance
    $pastors_attendance = [];
    for ($i = 1; $i <= 18; $i++) {
        if (!empty($_POST["pastor_$i"])) {
            $pastors_attendance[] = $_POST["pastor_$i"];
        }
    }
    $pastors_json = json_encode($pastors_attendance);
    
    $created_by = $_SESSION['user_id'];
    
    try {
        // Check if updating existing record
        if (isset($_POST['report_id']) && !empty($_POST['report_id'])) {
            $report_id = intval($_POST['report_id']);
            $stmt = $conn->prepare("
                UPDATE daily_reports SET 
                report_date = ?, preacher_name = ?, coordinator_name = ?, session = ?,
                sermon_start = ?, sermon_end = ?, prayer_start = ?, prayer_end = ?,
                worship_team_score = ?, instrumentalists_score = ?, technical_sound_score = ?,
                prayer_leaders_score = ?, security_score = ?, interpretation_score = ?,
                media_score = ?, ushering_score = ?, bible_reading_score = ?,
                pastors_attendance = ?, updated_at = CURRENT_TIMESTAMP
                WHERE id = ?
            ");
            
            $stmt->bind_param(
                "ssssssssiiiiiiiiisii",
                $report_date, $preacher_name, $coordinator_name, $session,
                $sermon_start, $sermon_end, $prayer_start, $prayer_end,
                $worship_team_score, $instrumentalists_score, $technical_sound_score,
                $prayer_leaders_score, $security_score, $interpretation_score,
                $media_score, $ushering_score, $bible_reading_score,
                $pastors_json, $report_id
            );
        } else {
            // Insert new record
            $stmt = $conn->prepare("
                INSERT INTO daily_reports (
                    report_date, preacher_name, coordinator_name, session,
                    sermon_start, sermon_end, prayer_start, prayer_end,
                    worship_team_score, instrumentalists_score, technical_sound_score,
                    prayer_leaders_score, security_score, interpretation_score,
                    media_score, ushering_score, bible_reading_score,
                    pastors_attendance, created_by
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");
            
            $stmt->bind_param(
                "ssssssssiiiiiiiiisi",
                $report_date, $preacher_name, $coordinator_name, $session,
                $sermon_start, $sermon_end, $prayer_start, $prayer_end,
                $worship_team_score, $instrumentalists_score, $technical_sound_score,
                $prayer_leaders_score, $security_score, $interpretation_score,
                $media_score, $ushering_score, $bible_reading_score,
                $pastors_json, $created_by
            );
        }
        
        $stmt->execute();
        header('Location: daily_report.php?success=Report ' . (isset($_POST['report_id']) ? 'updated' : 'submitted') . ' successfully');
        exit;
        
    } catch (Exception $e) {
        $error = "Error submitting report: " . $e->getMessage();
    }
}

// Get report for editing
$edit_report = null;
if (isset($_GET['edit_id'])) {
    $edit_id = intval($_GET['edit_id']);
    $edit_report = $conn->query("SELECT * FROM daily_reports WHERE id = $edit_id")->fetch_assoc();
}

// Get report for viewing
$view_report = null;
if (isset($_GET['view_id'])) {
    $view_id = intval($_GET['view_id']);
    $view_report = $conn->query("SELECT * FROM daily_reports WHERE id = $view_id")->fetch_assoc();
}

// Get previous reports
$previous_reports = $conn->query("
    SELECT dr.*, u.full_name as created_by_name
    FROM daily_reports dr
    LEFT JOIN users u ON dr.created_by = u.id
    ORDER BY dr.report_date DESC, dr.created_at DESC
    LIMIT 10
")->fetch_all(MYSQLI_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="shortcut icon" href="eden.jpg" type="image/x-icon">
    <title>Daily Performance Report - New</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.8.1/font/bootstrap-icons.css">
    <style>
        :root {
            --primary: #6a11cb;
            --secondary: #2575fc;
            --success: #28a745;
            --info: #17a2b8;
            --warning: #ffc107;
            --danger: #dc3545;
            --light: #f8f9fa;
            --dark: #343a40;
            --sidebar-bg: #2c3e50;
            --sidebar-text: #ecf0f1;
            --card-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
            --transition: all 0.3s ease;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: #f5f7fa;
            overflow-x: hidden;
        }

        /* Sidebar Styles */
        .sidebar {
            width: 250px;
            min-height: 100vh;
            background: var(--sidebar-bg);
            color: var(--sidebar-text);
            position: fixed;
            transition: var(--transition);
            z-index: 1000;
        }

        .sidebar-header {
            padding: 20px;
            background: rgba(0, 0, 0, 0.1);
        }

        .brand-link {
            color: white;
            text-decoration: none;
            font-weight: 600;
            font-size: 1.2rem;
            display: flex;
            align-items: center;
        }

        .brand-link i {
            margin-right: 10px;
            font-size: 1.5rem;
        }

        .nav-link {
            color: var(--sidebar-text);
            padding: 12px 20px;
            margin: 2px 0;
            border-radius: 0;
            display: flex;
            align-items: center;
            transition: var(--transition);
        }

        .nav-link i {
            margin-right: 10px;
            font-size: 1.1rem;
        }

        .nav-link:hover, .nav-link.active {
            background: rgba(255, 255, 255, 0.1);
            color: white;
        }

        .nav-link.active {
            border-left: 4px solid var(--primary);
        }

        /* Main Content Styles */
        .main-content {
            margin-left: 250px;
            transition: var(--transition);
            min-height: 100vh;
        }

        /* Navbar Styles */
        .navbar {
            background: white;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
            padding: 10px 20px;
        }

        .theme-toggle {
            color: var(--dark);
            font-size: 1.2rem;
            margin-right: 15px;
        }

        .form-section {
            background: #fff;
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 30px;
            box-shadow: 0 2px 6px rgba(0,0,0,0.1);
        }

        h5 {
            margin-top: 20px;
            font-weight: 600;
            color: #0d6efd;
            border-bottom: 2px solid #0d6efd;
            padding-bottom: 10px;
        }

        .table th {
            background-color: #f8f9fa;
            font-weight: 600;
        }

        .score-input {
            max-width: 80px;
            margin: 0 auto;
        }

        @media (max-width: 768px) {
            .sidebar {
                margin-left: -250px;
            }
            .sidebar.active {
                margin-left: 0;
            }
            .main-content {
                margin-left: 0;
            }
            .main-content.active {
                margin-left: 250px;
            }
        }

        .evaluation-badge {
            font-size: 0.9rem;
            padding: 5px 10px;
        }
        
        .view-mode .form-control {
            background-color: #f8f9fa;
            border: 1px solid #dee2e6;
            pointer-events: none;
        }
        
        .view-mode .form-select {
            background-color: #f8f9fa;
            border: 1px solid #dee2e6;
            pointer-events: none;
        }
        
        .view-mode .score-input {
            background-color: #f8f9fa;
            border: 1px solid #dee2e6;
            pointer-events: none;
        }
        
        .view-mode .btn-submit {
            display: none;
        }
    </style>
</head>
<body>
    <!-- Sidebar -->
    <div class="sidebar">
        <div class="sidebar-header">
            <a href="dashboard.php" class="brand-link">
                <i class="bi bi-house-church"></i>
                Eden Church Admin
            </a>
        </div>
        <ul class="nav flex-column">
            <li class="nav-item">
                <a class="nav-link" href="dashboard.php">
                    <i class="bi bi-speedometer2"></i> Dashboard
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="users.php">
                    <i class="bi bi-people"></i> Users
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="ministers.php">
                    <i class="bi bi-person-badge"></i> Ministers
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="departments.php">
                    <i class="bi bi-diagram-3"></i> Departments
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="generations.php">
                    <i class="bi bi-collection"></i> Generations
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="assets.php">
                    <i class="bi bi-box-seam"></i> Assets
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="projects.php">
                    <i class="bi bi-buildings"></i> Church Projects
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link active" href="reports.php">
                    <i class="bi bi-file-earmark-text"></i> Reports
                </a>
            </li>
        </ul>
    </div>

    <!-- Main Content -->
    <div class="main-content">
        <!-- Navbar -->
        <nav class="navbar navbar-expand-lg navbar-light bg-light">
            <div class="container-fluid">
                <button type="button" id="sidebarToggle" class="btn btn-primary">
                    <i class="bi bi-list"></i>
                </button>
                <div class="ms-auto d-flex align-items-center">
                    <button class="btn btn-link theme-toggle" id="themeToggle">
                        <i class="bi bi-moon-fill" id="themeIcon"></i>
                    </button>
                    <div class="dropdown">
                        <button class="btn btn-link text-decoration-none" data-bs-toggle="dropdown">
                            <i class="bi bi-person-circle fs-4"></i>
                        </button>
                        <ul class="dropdown-menu dropdown-menu-end">
                            <li><a class="dropdown-item" href="profile.php">Profile</a></li>
                            <li><a class="dropdown-item" href="settings.php">Settings</a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item" href="logout.php">Sign out</a></li>
                        </ul>
                    </div>
                </div>
            </div>
        </nav>

        <div class="container-fluid mt-4">
            <div class="row">
                <div class="col-12">
                    <div class="card">
                        <div class="card-header">
                            <h4 class="card-title mb-0">EDEN MIRACLE MINISTRIES - DAILY PERFORMANCE REPORT</h4>
                            <?php if ($view_report): ?>
                                <div class="mt-2">
                                    <span class="badge bg-info">View Mode</span>
                                    <a href="daily_report.php" class="btn btn-sm btn-primary ms-2">
                                        <i class="bi bi-arrow-left"></i> Back to Reports
                                    </a>
                                </div>
                            <?php endif; ?>
                        </div>
                        <div class="card-body">
                            <?php if (isset($_GET['success'])): ?>
                                <div class="alert alert-success"><?php echo htmlspecialchars($_GET['success']); ?></div>
                            <?php endif; ?>
                            
                            <?php if (isset($error)): ?>
                                <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
                            <?php endif; ?>

                            <form method="POST" action="" class="<?php echo $view_report ? 'view-mode' : ''; ?>">
                                <?php if ($edit_report): ?>
                                    <input type="hidden" name="report_id" value="<?php echo $edit_report['id']; ?>">
                                <?php endif; ?>

                                <!-- General Info -->
                                <div class="form-section">
                                    <div class="text-center mb-4">
                                        <h2 class="fw-bold">EDEN MIRACLE MINISTRIES</h2>
                                        <h4 class="text-primary">DAILY PERFORMANCE REPORT</h4>
                                    </div>

                                    <div class="row mb-3">
                                        <div class="col-md-6">
                                            <label class="form-label">Date *</label>
                                            <input type="date" class="form-control" name="report_date" 
                                                   value="<?php 
                                                   if ($edit_report) echo $edit_report['report_date']; 
                                                   elseif ($view_report) echo $view_report['report_date'];
                                                   else echo date('Y-m-d'); 
                                                   ?>" 
                                                   <?php if ($view_report) echo 'readonly'; else echo 'required'; ?>>
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label">Preacher's Name *</label>
                                            <input type="text" class="form-control" name="preacher_name" 
                                                   value="<?php 
                                                   if ($edit_report) echo htmlspecialchars($edit_report['preacher_name']); 
                                                   elseif ($view_report) echo htmlspecialchars($view_report['preacher_name']);
                                                   else echo ''; 
                                                   ?>" 
                                                   <?php if ($view_report) echo 'readonly'; else echo 'required'; ?>>
                                        </div>
                                    </div>
                                    <div class="row mb-3">
                                        <div class="col-md-6">
                                            <label class="form-label">Coordinator's Name *</label>
                                            <input type="text" class="form-control" name="coordinator_name" 
                                                   value="<?php 
                                                   if ($edit_report) echo htmlspecialchars($edit_report['coordinator_name']); 
                                                   elseif ($view_report) echo htmlspecialchars($view_report['coordinator_name']);
                                                   else echo ''; 
                                                   ?>" 
                                                   <?php if ($view_report) echo 'readonly'; else echo 'required'; ?>>
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label">Session *</label>
                                            <select class="form-select" name="session" <?php if ($view_report) echo 'disabled'; else echo 'required'; ?>>
                                                <option value="">-- Select Session --</option>
                                                <?php
                                                $sessions = [
                                                    'Morning Glory', 'First Service', 'Lunch Hour', 'Mid Service',
                                                    'Evening Service', 'Generals Overnight', 'Billionaires',
                                                    'Youth Saturday Service', 'Morning Sunday Service',
                                                    'Main Sunday Service', 'Third Sunday Service'
                                                ];
                                                
                                                $current_session = '';
                                                if ($edit_report) $current_session = $edit_report['session'];
                                                elseif ($view_report) $current_session = $view_report['session'];
                                                
                                                foreach ($sessions as $session_option): 
                                                ?>
                                                <option value="<?php echo $session_option; ?>" 
                                                    <?php echo ($current_session == $session_option) ? 'selected' : ''; ?>>
                                                    <?php echo $session_option; ?>
                                                </option>
                                                <?php endforeach; ?>
                                            </select>
                                            <?php if ($view_report): ?>
                                                <input type="hidden" name="session" value="<?php echo htmlspecialchars($current_session); ?>">
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>

                                <!-- Sermon & Prayer -->
                                <div class="form-section">
                                    <h5>Sermon</h5>
                                    <div class="row mb-3">
                                        <div class="col-md-6">
                                            <label class="form-label">Start Time</label>
                                            <input type="time" class="form-control" name="sermon_start" 
                                                   value="<?php 
                                                   if ($edit_report) echo $edit_report['sermon_start']; 
                                                   elseif ($view_report) echo $view_report['sermon_start'];
                                                   else echo ''; 
                                                   ?>" 
                                                   <?php if ($view_report) echo 'readonly'; ?>>
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label">End Time</label>
                                            <input type="time" class="form-control" name="sermon_end" 
                                                   value="<?php 
                                                   if ($edit_report) echo $edit_report['sermon_end']; 
                                                   elseif ($view_report) echo $view_report['sermon_end'];
                                                   else echo ''; 
                                                   ?>" 
                                                   <?php if ($view_report) echo 'readonly'; ?>>
                                        </div>
                                    </div>

                                    <h5>Prayer</h5>
                                    <div class="row">
                                        <div class="col-md-6">
                                            <label class="form-label">Start Time</label>
                                            <input type="time" class="form-control" name="prayer_start" 
                                                   value="<?php 
                                                   if ($edit_report) echo $edit_report['prayer_start']; 
                                                   elseif ($view_report) echo $view_report['prayer_start'];
                                                   else echo ''; 
                                                   ?>" 
                                                   <?php if ($view_report) echo 'readonly'; ?>>
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label">End Time</label>
                                            <input type="time" class="form-control" name="prayer_end" 
                                                   value="<?php 
                                                   if ($edit_report) echo $edit_report['prayer_end']; 
                                                   elseif ($view_report) echo $view_report['prayer_end'];
                                                   else echo ''; 
                                                   ?>" 
                                                   <?php if ($view_report) echo 'readonly'; ?>>
                                        </div>
                                    </div>
                                </div>

                                <!-- Departments -->
                                <div class="form-section">
                                    <h5>Department Evaluation</h5>
                                    <p class="small text-muted">Rate performance from Poor (1) to Excellent (5)</p>
                                    <div class="table-responsive">
                                        <table class="table table-bordered align-middle">
                                            <thead class="table-light text-center">
                                                <tr>
                                                    <th>Department</th>
                                                    <th>Key Performance Indicators</th>
                                                    <th>Score (1-5)</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php
                                                $departments = [
                                                    'worship_team' => ['Worship Team', 'Time management, Appearance, Conduct, Engaging congregation, Teamwork'],
                                                    'instrumentalists' => ['Instrumentalists', 'Time management, Teamwork, Customer Care, Presence, Conduct, Engagement'],
                                                    'technical_sound' => ['Technical Sound', 'Time management, Teamwork, Customer Care, Presence, Problem Solving'],
                                                    'prayer_leaders' => ['Prayer Leaders', 'Time management, Presence, Engagement, Relevant scriptures'],
                                                    'security' => ['Security', 'Time management, Teamwork, Conduct, Positioning, Customer Care'],
                                                    'interpretation' => ['Interpretation', 'Time management, Teamwork, Appearance, Clarity, Biblical Knowledge'],
                                                    'media' => ['Media', 'Time management, Teamwork, Customer Care, Code of Conduct, Problem Solving'],
                                                    'ushering' => ['Ushering', 'Time management, Teamwork, Communication, Appearance, Cleanliness, Order'],
                                                    'bible_reading' => ['Bible Reading', 'Time management, Clarity in speech, Communication Skills, Teamwork']
                                                ];
                                                
                                                foreach ($departments as $key => $dept): 
                                                    $score_value = '';
                                                    if ($edit_report) $score_value = $edit_report[$key.'_score'];
                                                    elseif ($view_report) $score_value = $view_report[$key.'_score'];
                                                ?>
                                                <tr>
                                                    <td><?php echo $dept[0]; ?></td>
                                                    <td><?php echo $dept[1]; ?></td>
                                                    <td>
                                                        <input type="number" min="1" max="5" class="form-control text-center score-input" 
                                                               name="<?php echo $key; ?>_score" 
                                                               value="<?php echo $score_value; ?>" 
                                                               <?php if ($view_report) echo 'readonly'; else echo 'required'; ?>>
                                                    </td>
                                                </tr>
                                                <?php endforeach; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>

                                <!-- Pastors in Attendance -->
                                <div class="form-section">
                                    <h5>Pastors in Attendance</h5>
                                    <div class="row g-2">
                                        <?php
                                        $pastors_data = [];
                                        if ($edit_report && !empty($edit_report['pastors_attendance'])) {
                                            $pastors_data = json_decode($edit_report['pastors_attendance'], true);
                                        } elseif ($view_report && !empty($view_report['pastors_attendance'])) {
                                            $pastors_data = json_decode($view_report['pastors_attendance'], true);
                                        }
                                        
                                        for ($i = 1; $i <= 18; $i++): 
                                            $pastor_value = isset($pastors_data[$i-1]) ? htmlspecialchars($pastors_data[$i-1]) : '';
                                        ?>
                                        <div class="col-md-3">
                                            <input type="text" class="form-control" name="pastor_<?php echo $i; ?>" 
                                                   placeholder="<?php echo $i; ?>." value="<?php echo $pastor_value; ?>"
                                                   <?php if ($view_report) echo 'readonly'; ?>>
                                        </div>
                                        <?php endfor; ?>
                                    </div>
                                </div>

                                <!-- Submit Button -->
                                <?php if (!$view_report): ?>
                                <div class="text-center mt-4 btn-submit">
                                    <button type="submit" class="btn btn-primary px-5">
                                        <?php echo $edit_report ? 'Update Report' : 'Submit Report'; ?>
                                    </button>
                                    <?php if ($edit_report): ?>
                                        <a href="daily_report.php" class="btn btn-secondary px-5">Cancel</a>
                                    <?php else: ?>
                                        <button type="reset" class="btn btn-secondary px-5">Clear & New</button>
                                    <?php endif; ?>
                                </div>
                                <?php endif; ?>
                            </form>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Previous Reports Section -->
            <?php if (!$view_report): ?>
            <div class="row mt-4">
                <div class="col-12">
                    <div class="card">
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <h5 class="card-title mb-0">Previous Reports</h5>
                            <div>
                                <button class="btn btn-sm btn-outline-secondary" onclick="window.print()">
                                    <i class="bi bi-printer"></i> Print
                                </button>
                            </div>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-striped">
                                    <thead>
                                        <tr>
                                            <th>Date</th>
                                            <th>Preacher</th>
                                            <th>Coordinator</th>
                                            <th>Session</th>
                                            <th>Average Score</th>
                                            <th>Created By</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($previous_reports as $report): 
                                            // Calculate average score
                                            $scores = [
                                                $report['worship_team_score'],
                                                $report['instrumentalists_score'],
                                                $report['technical_sound_score'],
                                                $report['prayer_leaders_score'],
                                                $report['security_score'],
                                                $report['interpretation_score'],
                                                $report['media_score'],
                                                $report['ushering_score'],
                                                $report['bible_reading_score']
                                            ];
                                            $valid_scores = array_filter($scores);
                                            $average = count($valid_scores) > 0 ? round(array_sum($valid_scores) / count($valid_scores), 1) : 0;
                                            
                                            $score_class = $average >= 4 ? 'success' : ($average >= 3 ? 'warning' : 'danger');
                                        ?>
                                            <tr>
                                                <td><?php echo date('M d, Y', strtotime($report['report_date'])); ?></td>
                                                <td><?php echo htmlspecialchars($report['preacher_name']); ?></td>
                                                <td><?php echo htmlspecialchars($report['coordinator_name']); ?></td>
                                                <td><?php echo htmlspecialchars($report['session']); ?></td>
                                                <td>
                                                    <span class="badge bg-<?php echo $score_class; ?> evaluation-badge">
                                                        <?php echo $average; ?>/5
                                                    </span>
                                                </td>
                                                <td><?php echo htmlspecialchars($report['created_by_name'] ?? 'System'); ?></td>
                                                <td>
                                                    <a href="daily_report.php?view_id=<?php echo $report['id']; ?>" class="btn btn-sm btn-outline-info">
                                                        <i class="bi bi-eye"></i> View
                                                    </a>
                                                    <a href="daily_report.php?edit_id=<?php echo $report['id']; ?>" class="btn btn-sm btn-outline-primary">
                                                        <i class="bi bi-pencil"></i> Edit
                                                    </a>
                                                    <a href="generate_pdf.php?report_id=<?php echo $report['id']; ?>" class="btn btn-sm btn-outline-secondary" target="_blank">
                                                        <i class="bi bi-file-pdf"></i> PDF
                                                    </a>
                                                    <button class="btn btn-sm btn-outline-danger delete-report" data-id="<?php echo $report['id']; ?>">
                                                        <i class="bi bi-trash"></i> Delete
                                                    </button>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Delete Confirmation Modal -->
    <div class="modal fade" id="deleteConfirmModal" tabindex="-1" aria-labelledby="deleteConfirmModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="deleteConfirmModalLabel">Confirm Deletion</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    Are you sure you want to delete this report? This action cannot be undone.
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <a href="#" id="confirmDeleteBtn" class="btn btn-danger">Delete</a>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script>
        // Toggle sidebar
        document.getElementById('sidebarToggle').addEventListener('click', function() {
            document.querySelector('.sidebar').classList.toggle('active');
            document.querySelector('.main-content').classList.toggle('active');
        });

        // Toggle theme
        document.getElementById('themeToggle').addEventListener('click', function() {
            document.body.classList.toggle('dark-theme');
            const icon = document.getElementById('themeIcon');
            if (document.body.classList.contains('dark-theme')) {
                icon.classList.remove('bi-moon-fill');
                icon.classList.add('bi-sun-fill');
            } else {
                icon.classList.remove('bi-sun-fill');
                icon.classList.add('bi-moon-fill');
            }
        });

        // Delete confirmation modal
        document.querySelectorAll('.delete-report').forEach(button => {
            button.addEventListener('click', function() {
                const reportId = this.getAttribute('data-id');
                document.getElementById('confirmDeleteBtn').href = 'daily_report.php?delete_id=' + reportId;
                const modal = new bootstrap.Modal(document.getElementById('deleteConfirmModal'));
                modal.show();
            });
        });

        // Validate score inputs
        document.querySelectorAll('.score-input').forEach(input => {
            input.addEventListener('change', function() {
                let value = parseInt(this.value);
                if (value < 1) this.value = 1;
                if (value > 5) this.value = 5;
            });
        });
    </script>
</body>
</html>