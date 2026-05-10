<?php
require('fpdf/fpdf.php');
include 'includes/db.php';

$pdf = new FPDF();
$pdf->AddPage();
$pdf->SetMargins(15, 15, 15);

// ── Title ──────────────────────────────────────────────────────────────────
$pdf->SetFont('Arial', 'B', 18);
$pdf->SetTextColor(0, 0, 0);
$pdf->Cell(0, 12, 'SCHOOL ATTENDANCE REPORT', 0, 1, 'C');
$pdf->Ln(2);

// ── Generated date ─────────────────────────────────────────────────────────
$pdf->SetFont('Arial', '', 10);
$pdf->Cell(0, 6, 'Generated: ' . date('F d, Y h:i A'), 0, 1, 'C');
$pdf->Ln(6);

// ── Fetch data ─────────────────────────────────────────────────────────────
$data = $conn->query("
    SELECT users.fullname, attendance.scan_date, attendance.scan_time, attendance.status
    FROM attendance
    JOIN users ON users.id = attendance.student_id
    ORDER BY attendance.scan_date DESC, attendance.scan_time DESC
");

$rows    = [];
$present = 0;
$absent  = 0;
$total   = 0;

while ($row = $data->fetch_assoc()) {
    $rows[] = $row;
    $s = strtolower($row['status']);
    if ($s === 'present' || $s === 'late') $present++;
    elseif ($s === 'absent') $absent++;
    $total++;
}

$rate = ($total > 0) ? round(($present / $total) * 100) . '%' : '0%';

// ── Summary row ────────────────────────────────────────────────────────────
$colW = (int)(($pdf->GetPageWidth() - 30) / 3);

$pdf->SetTextColor(0, 0, 0);
$pdf->SetFont('Arial', 'B', 12);

$pdf->SetFillColor(198, 239, 206);
$pdf->Cell($colW, 14, 'Present (incl. Late): ' . $present, 1, 0, 'C', true);

$pdf->SetFillColor(255, 199, 206);
$pdf->Cell($colW, 14, 'Absent: ' . $absent, 1, 0, 'C', true);

$pdf->SetFillColor(240, 240, 240);
$pdf->Cell($colW, 14, 'Rate: ' . $rate, 1, 1, 'C', true);

$pdf->Ln(6);

// ── Table header ────────────────────────────────────────────────────────────
$pdf->SetFillColor(66, 133, 244);
$pdf->SetTextColor(0, 0, 0);
$pdf->SetFont('Arial', 'B', 11);

$numW    = 12;  // # column
$nameW   = 62;
$dateW   = 36;
$timeW   = 30;
$statusW = (int)($pdf->GetPageWidth() - 30 - $numW - $nameW - $dateW - $timeW);

$pdf->Cell($numW,    11, 'No.',      1, 0, 'C', true);
$pdf->Cell($nameW,   11, 'Name',   1, 0, 'C', true);
$pdf->Cell($dateW,   11, 'Date',   1, 0, 'C', true);
$pdf->Cell($timeW,   11, 'Time',   1, 0, 'C', true);
$pdf->Cell($statusW, 11, 'Status', 1, 1, 'C', true);

// ── Table rows ──────────────────────────────────────────────────────────────
$pdf->SetFont('Arial', '', 11);
$pdf->SetTextColor(0, 0, 0);

$i = 1;
foreach ($rows as $row) {
    $statusRaw = strtolower($row['status']);
    $statusTxt = ucfirst($statusRaw);
    $date      = date('M d, Y', strtotime($row['scan_date']));
    $time      = (!empty($row['scan_time']) && $row['scan_time'] !== '00:00:00')
                 ? date('h:i A', strtotime($row['scan_time']))
                 : '—';

    $pdf->SetFillColor(255, 255, 255);

    // #, Name, Date, Time — black
    $pdf->SetTextColor(0, 0, 0);
    $pdf->Cell($numW,  10, $i,                1, 0, 'C');
    $pdf->Cell($nameW, 10, $row['fullname'],  1, 0, 'L');
    $pdf->Cell($dateW, 10, $date,             1, 0, 'L');
    $pdf->Cell($timeW, 10, $time,             1, 0, 'C');

    // Status — colored
    if ($statusRaw === 'absent') {
        $pdf->SetTextColor(192, 0, 0);
    } elseif ($statusRaw === 'late') {
        $pdf->SetTextColor(180, 130, 0);
    } elseif ($statusRaw === 'present') {
        $pdf->SetTextColor(0, 128, 0);
    } else {
        $pdf->SetTextColor(0, 0, 0);
    }

    $pdf->Cell($statusW, 10, $statusTxt, 1, 1, 'C');

    $pdf->SetTextColor(0, 0, 0);
    $i++;
}

$pdf->Output();