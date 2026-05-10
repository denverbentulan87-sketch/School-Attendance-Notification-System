<?php
require('fpdf/fpdf.php');
include 'includes/db.php';

$pdf = new FPDF();
$pdf->AddPage();

// Title
$pdf->SetFont('Arial','B',16);
$pdf->Cell(0,10,'Attendance Report',0,1,'C');
$pdf->SetFont('Arial','',10);
$pdf->Cell(0,8,'Generated: ' . date('F d, Y h:i A'),0,1,'C');
$pdf->Ln(4);

// Table header
$pdf->SetFont('Arial','B',11);
$pdf->SetFillColor(240,242,245);
$pdf->Cell(70,10,'Name',1,0,'L',true);
$pdf->Cell(35,10,'Date',1,0,'L',true);
$pdf->Cell(30,10,'Time',1,0,'L',true);
$pdf->Cell(30,10,'Status',1,0,'L',true);
$pdf->Ln();

// FIX: was querying wrong table 'user' (should be 'users')
//      wrong column 'name' (should be 'fullname')
//      wrong column 'date_added' (should be scan_date + scan_time)
$data = $conn->query("
    SELECT users.fullname, attendance.scan_date, attendance.scan_time, attendance.status
    FROM attendance
    JOIN users ON users.id = attendance.student_id
    ORDER BY attendance.scan_date DESC, attendance.scan_time DESC
");

$pdf->SetFont('Arial','',11);

while ($row = $data->fetch_assoc()) {
    $status   = ucfirst($row['status']);
    $date     = date('M d, Y', strtotime($row['scan_date']));
    $time     = (!empty($row['scan_time']) && $row['scan_time'] !== '00:00:00')
                ? date('h:i A', strtotime($row['scan_time']))
                : '—';

    $pdf->Cell(70,10,$row['fullname'],1);
    $pdf->Cell(35,10,$date,1);
    $pdf->Cell(30,10,$time,1);
    $pdf->Cell(30,10,$status,1);
    $pdf->Ln();
}

$pdf->Output();
