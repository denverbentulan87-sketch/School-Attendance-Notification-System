<?php
require('fpdf/fpdf.php');
include 'includes/db.php';

$pdf = new FPDF();
$pdf->AddPage();

$pdf->SetFont('Arial','B',16);
$pdf->Cell(0,10,'Attendance Report',0,1,'C');

$pdf->SetFont('Arial','B',12);
$pdf->Cell(60,10,'Name',1);
$pdf->Cell(40,10,'Date',1);
$pdf->Cell(40,10,'Status',1);
$pdf->Ln();

$data = $conn->query("
    SELECT user.name, attendance.date_added, attendance.status
    FROM attendance
    JOIN user ON user.id = attendance.student_id
");

$pdf->SetFont('Arial','',12);

while($row = $data->fetch_assoc()){
    $pdf->Cell(60,10,$row['name'],1);
    $pdf->Cell(40,10,$row['date_added'],1);
    $pdf->Cell(40,10,$row['status'],1);
    $pdf->Ln();
}

$pdf->Output();