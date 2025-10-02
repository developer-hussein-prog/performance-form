<?php
require_once 'config.php';
require_once 'auth.php';

requireRole('admin');

if (!isset($_GET['report_id'])) {
    die('Report ID not specified');
}

$report_id = intval($_GET['report_id']);
$report = $conn->query("
    SELECT dr.*, u.full_name as created_by_name 
    FROM daily_reports dr 
    LEFT JOIN users u ON dr.created_by = u.id 
    WHERE dr.id = $report_id
")->fetch_assoc();

if (!$report) {
    die('Report not found');
}

// Include TCPDF library
require_once('tcpdf/tcpdf.php');

// Create new PDF document
$pdf = new TCPDF('P', 'mm', 'A4', true, 'UTF-8', false);

// Set document information
$pdf->SetCreator('Eden Miracle Ministries');
$pdf->SetAuthor('Eden Church Admin');
$pdf->SetTitle('Daily Performance Report');
$pdf->SetSubject('Daily Performance Report');

// Remove default header/footer
$pdf->setPrintHeader(false);
$pdf->setPrintFooter(false);

// Add a page
$pdf->AddPage();

// Set font
$pdf->SetFont('helvetica', 'B', 16);

// Title
$pdf->Cell(0, 10, 'EDEN MIRACLE MINISTRIES', 0, 1, 'C');
$pdf->SetFont('helvetica', 'B', 14);
$pdf->Cell(0, 10, 'DAILY PERFORMANCE REPORT', 0, 1, 'C');
$pdf->Ln(5);

// Report date
$pdf->SetFont('helvetica', '', 12);
$pdf->Cell(0, 10, 'Date: ' . date('F d, Y', strtotime($report['report_date'])), 0, 1);

// Basic Information
$pdf->SetFont('helvetica', 'B', 12);
$pdf->Cell(0, 10, 'Basic Information', 0, 1);
$pdf->SetFont('helvetica', '', 10);

$basic_info = [
    'Preacher' => $report['preacher_name'],
    'Coordinator' => $report['coordinator_name'],
    'Session' => $report['session']
];

foreach ($basic_info as $label => $value) {
    $pdf->Cell(40, 6, $label . ':', 0, 0);
    $pdf->Cell(0, 6, $value, 0, 1);
}
$pdf->Ln(5);

// Sermon and Prayer Times
$pdf->SetFont('helvetica', 'B', 12);
$pdf->Cell(0, 10, 'Service Times', 0, 1);
$pdf->SetFont('helvetica', '', 10);

if ($report['sermon_start']) {
    $pdf->Cell(40, 6, 'Sermon:', 0, 0);
    $pdf->Cell(0, 6, $report['sermon_start'] . ' - ' . $report['sermon_end'], 0, 1);
}

if ($report['prayer_start']) {
    $pdf->Cell(40, 6, 'Prayer:', 0, 0);
    $pdf->Cell(0, 6, $report['prayer_start'] . ' - ' . $report['prayer_end'], 0, 1);
}
$pdf->Ln(5);

// Department Evaluations
$pdf->SetFont('helvetica', 'B', 12);
$pdf->Cell(0, 10, 'Department Evaluations', 0, 1);

$departments = [
    'Worship Team' => $report['worship_team_score'],
    'Instrumentalists' => $report['instrumentalists_score'],
    'Technical Sound' => $report['technical_sound_score'],
    'Prayer Leaders' => $report['prayer_leaders_score'],
    'Security' => $report['security_score'],
    'Interpretation' => $report['interpretation_score'],
    'Media' => $report['media_score'],
    'Ushering' => $report['ushering_score'],
    'Bible Reading' => $report['bible_reading_score']
];

$pdf->SetFont('helvetica', '', 10);
foreach ($departments as $dept => $score) {
    $pdf->Cell(50, 6, $dept . ':', 0, 0);
    $pdf->Cell(0, 6, $score . '/5', 0, 1);
}
$pdf->Ln(5);

// Pastors in Attendance
$pdf->SetFont('helvetica', 'B', 12);
$pdf->Cell(0, 10, 'Pastors in Attendance', 0, 1);
$pdf->SetFont('helvetica', '', 10);

if (!empty($report['pastors_attendance'])) {
    $pastors = json_decode($report['pastors_attendance'], true);
    $pastors = array_filter($pastors); // Remove empty values
    
    if (count($pastors) > 0) {
        foreach ($pastors as $index => $pastor) {
            $pdf->Cell(0, 6, ($index + 1) . '. ' . $pastor, 0, 1);
        }
    } else {
        $pdf->Cell(0, 6, 'No pastors recorded', 0, 1);
    }
} else {
    $pdf->Cell(0, 6, 'No pastors recorded', 0, 1);
}
$pdf->Ln(10);

// Footer
$pdf->SetFont('helvetica', 'I', 8);
$pdf->Cell(0, 10, 'Generated on ' . date('Y-m-d H:i:s') . ' by ' . $report['created_by_name'], 0, 1, 'C');

// Output PDF
$pdf->Output('daily_report_' . $report_id . '.pdf', 'I');
?>