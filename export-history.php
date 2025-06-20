<?php
require 'function.php';

// Check if user is logged in
if(!isset($_SESSION['loggedin'])){
    header('location:login.php');
    exit();
}

// Include TCPDF library
require_once('tcpdf/tcpdf.php');

// Fungsi untuk menghitung biaya berdasarkan durasi
function calculateStorageCost($days) {
    if ($days <= 1) {
        return 50000; // 1 hari = 50rb
    } elseif ($days <= 3) {
        return 120000; // 3 hari = 120rb
    } elseif ($days <= 7) {
        return 270000; // 1 minggu = 270rb
    } else {
        // Untuk lebih dari 1 minggu, hitung per minggu
        $weeks = ceil($days / 7);
        return $weeks * 270000;
    }
}

// Query untuk mendapatkan riwayat lengkap
$query_history = "
    SELECT 
        CONCAT('TRX', LPAD(ROW_NUMBER() OVER (ORDER BY bm.tanggal_masuk), 4, '0')) as transaction_id,
        s.pemilik_barang,
        s.nama_barang,
        s.no_telp,
        bm.tanggal_masuk,
        bk.tanggal_keluar,
        CASE 
            WHEN bk.tanggal_keluar IS NOT NULL THEN
                DATEDIFF(bk.tanggal_keluar, bm.tanggal_masuk)
            ELSE
                DATEDIFF(NOW(), bm.tanggal_masuk)
        END as durasi_hari,
        CASE 
            WHEN bk.tanggal_keluar IS NOT NULL THEN 'Selesai'
            ELSE 'Masih Disimpan'
        END as status_penyimpanan,
        bm.id_stok,
        bm.qty
    FROM barang_masuk bm
    JOIN stok s ON bm.id_stok = s.id_stok
    LEFT JOIN barang_keluar bk ON bm.id_stok = bk.id_stok 
        AND DATE(bm.tanggal_masuk) <= DATE(bk.tanggal_keluar)
    ORDER BY bm.tanggal_masuk ASC
";

$result_history = mysqli_query($conn, $query_history);

// Create new PDF document - LANDSCAPE untuk lebih luas
$pdf = new TCPDF('L', 'mm', 'A4', true, 'UTF-8', false);

// Set document information
$pdf->SetCreator('VOINVENTORY');
$pdf->SetAuthor('VOINVENTORY System');
$pdf->SetTitle('Laporan Riwayat Penyimpanan Barang');
$pdf->SetSubject('Inventory History Report');

// Remove default header/footer
$pdf->setPrintHeader(false);
$pdf->setPrintFooter(false);

// Set margins
$pdf->SetMargins(15, 15, 15);
$pdf->SetAutoPageBreak(TRUE, 15);

// Add a page
$pdf->AddPage();

// ===== HEADER DENGAN TEMA PURPLE =====
// Background header
$pdf->SetFillColor(102, 51, 153); // Purple theme color
$pdf->Rect(0, 0, 297, 35, 'F'); // Full width purple header

// Company name
$pdf->SetFont('helvetica', 'B', 24);
$pdf->SetTextColor(255, 255, 255); // White text
$pdf->SetXY(15, 8);
$pdf->Cell(0, 10, 'VOINVENTORY', 0, 1, 'L');

// Tagline
$pdf->SetFont('helvetica', '', 11);
$pdf->SetXY(15, 18);
$pdf->Cell(0, 6, 'SIMPLIFY WITH VXKNET', 0, 1, 'L');

// Date on the right
$pdf->SetFont('helvetica', '', 10);
$pdf->SetXY(200, 12);
$pdf->Cell(0, 6, 'Tanggal: ' . date('d F Y, H:i:s'), 0, 1, 'R');

// Reset position after header
$pdf->SetXY(15, 45);

// Report title
$pdf->SetFont('helvetica', 'B', 18);
$pdf->SetTextColor(102, 51, 153); // Purple
$pdf->Cell(0, 10, 'LAPORAN RIWAYAT PENYIMPANAN BARANG', 0, 1, 'C');

$pdf->Ln(5);

// ===== SUMMARY CARDS DENGAN TEMA PURPLE =====
// Calculate summary
$total_transactions = 0;
$active_storage = 0;
$completed_storage = 0;
$total_revenue = 0;

mysqli_data_seek($result_history, 0);
while($row = mysqli_fetch_assoc($result_history)) {
    $cost = calculateStorageCost($row['durasi_hari']);
    $total_revenue += $cost;
    $total_transactions++;
    
    if($row['status_penyimpanan'] == 'Masih Disimpan') {
        $active_storage++;
    } else {
        $completed_storage++;
    }
}

// Debug: Reset pointer dan hitung ulang untuk memastikan
mysqli_data_seek($result_history, 0);
$debug_total = 0;
$debug_count = 0;
while($debug_row = mysqli_fetch_assoc($result_history)) {
    $debug_cost = calculateStorageCost($debug_row['durasi_hari']);
    $debug_total += $debug_cost;
    $debug_count++;
}
$total_revenue = $debug_total; // Pastikan menggunakan nilai yang benar

// Summary cards dengan gradient purple theme
$card_width = 62;
$card_height = 18;
$start_x = 20;
$y_pos = $pdf->GetY();

// Card 1 - Total Transactions (Dark Purple)
$pdf->SetFillColor(102, 51, 153);
$pdf->RoundedRect($start_x, $y_pos, $card_width, $card_height, 4, '1111', 'F');
$pdf->SetTextColor(255, 255, 255);
$pdf->SetFont('helvetica', 'B', 16);
$pdf->SetXY($start_x, $y_pos + 4);
$pdf->Cell($card_width, 6, $total_transactions, 0, 0, 'C');
$pdf->SetFont('helvetica', '', 9);
$pdf->SetXY($start_x, $y_pos + 11);
$pdf->Cell($card_width, 4, 'TOTAL TRANSAKSI', 0, 0, 'C');

// Card 2 - Active Storage (Medium Purple)
$pdf->SetFillColor(123, 104, 238);
$pdf->RoundedRect($start_x + 67, $y_pos, $card_width, $card_height, 4, '1111', 'F');
$pdf->SetFont('helvetica', 'B', 16);
$pdf->SetXY($start_x + 67, $y_pos + 4);
$pdf->Cell($card_width, 6, $active_storage, 0, 0, 'C');
$pdf->SetFont('helvetica', '', 9);
$pdf->SetXY($start_x + 67, $y_pos + 11);
$pdf->Cell($card_width, 4, 'MASIH DISIMPAN', 0, 0, 'C');

// Card 3 - Completed (Red-Purple)
$pdf->SetFillColor(186, 85, 211);
$pdf->RoundedRect($start_x + 134, $y_pos, $card_width, $card_height, 4, '1111', 'F');
$pdf->SetFont('helvetica', 'B', 16);
$pdf->SetXY($start_x + 134, $y_pos + 4);
$pdf->Cell($card_width, 6, $completed_storage, 0, 0, 'C');
$pdf->SetFont('helvetica', '', 9);
$pdf->SetXY($start_x + 134, $y_pos + 11);
$pdf->Cell($card_width, 4, 'SELESAI', 0, 0, 'C');

// Card 4 - Total Revenue (Light Purple)
$pdf->SetFillColor(147, 112, 219);
$pdf->RoundedRect($start_x + 201, $y_pos, $card_width, $card_height, 4, '1111', 'F');
$pdf->SetTextColor(255, 255, 255);
$pdf->SetFont('helvetica', 'B', 10);
$pdf->SetXY($start_x + 201, $y_pos + 3);
$revenue_display = 'Rp ' . number_format($total_revenue, 0, ',', '.');
$pdf->Cell($card_width, 6, $revenue_display, 0, 0, 'C');
$pdf->SetFont('helvetica', '', 9);
$pdf->SetXY($start_x + 201, $y_pos + 11);
$pdf->Cell($card_width, 4, 'TOTAL PENDAPATAN', 0, 0, 'C');

$pdf->Ln(25);

// ===== TABLE DENGAN TEMA PURPLE =====
// Table header
$pdf->SetFont('helvetica', 'B', 9);
$pdf->SetFillColor(102, 51, 153); // Purple header
$pdf->SetTextColor(255, 255, 255);
$pdf->SetDrawColor(102, 51, 153);

// Column widths untuk landscape - adjusted for history data
$widths = [15, 25, 35, 45, 30, 30, 25, 30, 27];

$headers = ['No', 'ID Trx', 'Pemilik', 'Nama Barang', 'Tgl Masuk', 'Tgl Keluar', 'Durasi', 'Biaya', 'Status'];

// Draw header
for($i = 0; $i < count($headers); $i++) {
    $pdf->Cell($widths[$i], 10, $headers[$i], 1, 0, 'C', true);
}
$pdf->Ln();

// Table rows
$pdf->SetFont('helvetica', '', 8);
$pdf->SetDrawColor(200, 200, 200);

mysqli_data_seek($result_history, 0);
$no = 1;
while($row = mysqli_fetch_assoc($result_history)) {
    // Check if we need a new page
    if($pdf->GetY() > 180) {
        $pdf->AddPage();
        
        // Redraw header on new page
        $pdf->SetFont('helvetica', 'B', 9);
        $pdf->SetFillColor(102, 51, 153);
        $pdf->SetTextColor(255, 255, 255);
        for($i = 0; $i < count($headers); $i++) {
            $pdf->Cell($widths[$i], 10, $headers[$i], 1, 0, 'C', true);
        }
        $pdf->Ln();
        $pdf->SetFont('helvetica', '', 8);
    }
    
    // Alternating row colors dengan tema purple
    if($no % 2 == 0) {
        $pdf->SetFillColor(248, 246, 252); // Very light purple
        $pdf->SetTextColor(51, 51, 51);
    } else {
        $pdf->SetFillColor(255, 255, 255); // White
        $pdf->SetTextColor(51, 51, 51);
    }
    
    // Calculate cost and duration
    $cost = calculateStorageCost($row['durasi_hari']);
    $duration_text = $row['durasi_hari'] . ' hari';
    if($row['durasi_hari'] >= 7) {
        $weeks = floor($row['durasi_hari'] / 7);
        $remaining_days = $row['durasi_hari'] % 7;
        $duration_text = $weeks . ' minggu';
        if($remaining_days > 0) {
            $duration_text .= ' ' . $remaining_days . ' hari';
        }
    }
    
    // Row number
    $pdf->Cell($widths[0], 8, $no, 1, 0, 'C', true);
    
    // Transaction ID
    $pdf->SetFont('helvetica', 'B', 8);
    $pdf->Cell($widths[1], 8, $row['transaction_id'], 1, 0, 'C', true);
    
    // Pemilik
    $pdf->SetFont('helvetica', '', 8);
    $pemilik = strlen($row['pemilik_barang']) > 15 ? substr($row['pemilik_barang'], 0, 12) . '...' : $row['pemilik_barang'];
    $pdf->Cell($widths[2], 8, $pemilik, 1, 0, 'L', true);
    
    // Nama Barang
    $nama = strlen($row['nama_barang']) > 20 ? substr($row['nama_barang'], 0, 17) . '...' : $row['nama_barang'];
    $pdf->Cell($widths[3], 8, $nama, 1, 0, 'L', true);
    
    // Tanggal Masuk
    $pdf->Cell($widths[4], 8, date('d/m/y', strtotime($row['tanggal_masuk'])), 1, 0, 'C', true);
    
    // Tanggal Keluar
    $tgl_keluar = $row['tanggal_keluar'] ? date('d/m/y', strtotime($row['tanggal_keluar'])) : '-';
    $pdf->Cell($widths[5], 8, $tgl_keluar, 1, 0, 'C', true);
    
    // Durasi
    $pdf->SetFont('helvetica', 'B', 8);
    $pdf->Cell($widths[6], 8, $duration_text, 1, 0, 'C', true);
    
    // Biaya
    $pdf->SetFont('helvetica', 'B', 8);
    $pdf->SetTextColor(40, 167, 69); // Green for money
    if($cost >= 1000000) {
        $biaya_text = 'Rp ' . number_format($cost/1000000, 1) . 'M';
    } else {
        $biaya_text = 'Rp ' . number_format($cost/1000, 0) . 'K';
    }
    $pdf->Cell($widths[7], 8, $biaya_text, 1, 0, 'C', true);
    
    // Status dengan warna
    $pdf->SetFont('helvetica', 'B', 7);
    
    if($row['status_penyimpanan'] == 'Masih Disimpan') {
        $status = 'Masih Disimpan';
        $pdf->SetTextColor(255, 193, 7); // Yellow/Orange
    } else {
        $status = 'SELESAI';
        $pdf->SetTextColor(40, 167, 69); // Green
    }
    
    $pdf->Cell($widths[8], 8, $status, 1, 1, 'C', true);
    
    $no++;
}

$pdf->Ln(8);

// Footer background
$current_y = $pdf->GetY();
$pdf->SetFillColor(248, 246, 252); // Light purple background
$pdf->Rect(15, $current_y, 267, 20, 'F');

// Footer content
$pdf->SetTextColor(102, 51, 153); // Purple text
$pdf->SetFont('helvetica', '', 9);
$pdf->SetXY(20, $current_y + 5);
$pdf->Cell(0, 5, 'Laporan ini dibuat secara otomatis oleh sistem VOINVENTORY', 0, 1, 'L');

$pdf->SetXY(20, $current_y + 10);
$pdf->Cell(125, 5, 'Total ' . $total_transactions . ' transaksi tercatat dalam sistem', 0, 0, 'L');
$pdf->Cell(132, 5, 'Total Pendapatan: Rp ' . number_format($total_revenue, 0, ',', '.'), 0, 1, 'R');

// Company branding
$pdf->SetFont('helvetica', 'B', 10);
$pdf->SetTextColor(102, 51, 153);
$pdf->SetXY(15, $current_y + 15);
$pdf->Cell(0, 5, 'VOINVENTORY - SIMPLIFY WITH VXKNET', 0, 1, 'C');

// Output PDF
$filename = 'Laporan_Riwayat_Penyimpanan_' . date('Y-m-d_H-i-s') . '.pdf';
$pdf->Output($filename, 'D');
?>
