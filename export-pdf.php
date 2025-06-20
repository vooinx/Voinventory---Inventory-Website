<?php
require 'function.php';

// Check if user is logged in
if(!isset($_SESSION['loggedin'])){
    header('location:login.php');
    exit();
}

// Include TCPDF library
require_once('tcpdf/tcpdf.php');

// Get all stock data
$result = mysqli_query($conn, "SELECT * FROM stok ORDER BY id_stok ASC");

// Create new PDF document - LANDSCAPE untuk lebih luas
$pdf = new TCPDF('L', 'mm', 'A4', true, 'UTF-8', false);

// Set document information
$pdf->SetCreator('VOINVENTORY');
$pdf->SetAuthor('VOINVENTORY System');
$pdf->SetTitle('Laporan Stok Barang');
$pdf->SetSubject('Inventory Report');

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
$pdf->Cell(0, 10, 'LAPORAN PENITIPAN BARANG', 0, 1, 'C');

$pdf->Ln(5);

// ===== SUMMARY CARDS DENGAN TEMA PURPLE =====
// Calculate summary untuk sistem penitipan
$total_items = 0;
$tersimpan_items = 0;
$sudah_diambil_items = 0;
$total_stock = 0;

mysqli_data_seek($result, 0);
while($row = mysqli_fetch_assoc($result)) {
    $total_items++;
    $total_stock += $row['stok'];
    if($row['stok'] == 0) {
        $sudah_diambil_items++;
    } else {
        $tersimpan_items++;
    }
}

// Summary cards dengan gradient purple theme
$card_width = 62;
$card_height = 18;
$start_x = 20;
$y_pos = $pdf->GetY();

// Card 1 - Total Items (Dark Purple)
$pdf->SetFillColor(102, 51, 153);
$pdf->RoundedRect($start_x, $y_pos, $card_width, $card_height, 4, '1111', 'F');
$pdf->SetTextColor(255, 255, 255);
$pdf->SetFont('helvetica', 'B', 16);
$pdf->SetXY($start_x, $y_pos + 4);
$pdf->Cell($card_width, 6, $total_items, 0, 0, 'C');
$pdf->SetFont('helvetica', '', 9);
$pdf->SetXY($start_x, $y_pos + 11);
$pdf->Cell($card_width, 4, 'TOTAL BARANG', 0, 0, 'C');

// Card 2 - Tersimpan (Green)
$pdf->SetFillColor(40, 167, 69);
$pdf->RoundedRect($start_x + 67, $y_pos, $card_width, $card_height, 4, '1111', 'F');
$pdf->SetFont('helvetica', 'B', 16);
$pdf->SetXY($start_x + 67, $y_pos + 4);
$pdf->Cell($card_width, 6, $tersimpan_items, 0, 0, 'C');
$pdf->SetFont('helvetica', '', 9);
$pdf->SetXY($start_x + 67, $y_pos + 11);
$pdf->Cell($card_width, 4, 'TERSIMPAN', 0, 0, 'C');

// Card 3 - Sudah Diambil (Red)
$pdf->SetFillColor(220, 53, 69);
$pdf->RoundedRect($start_x + 134, $y_pos, $card_width, $card_height, 4, '1111', 'F');
$pdf->SetFont('helvetica', 'B', 16);
$pdf->SetXY($start_x + 134, $y_pos + 4);
$pdf->Cell($card_width, 6, $sudah_diambil_items, 0, 0, 'C');
$pdf->SetFont('helvetica', '', 9);
$pdf->SetXY($start_x + 134, $y_pos + 11);
$pdf->Cell($card_width, 4, 'SUDAH DIAMBIL', 0, 0, 'C');

// Card 4 - Total Stock (Light Purple)
$pdf->SetFillColor(147, 112, 219);
$pdf->RoundedRect($start_x + 201, $y_pos, $card_width, $card_height, 4, '1111', 'F');
$pdf->SetFont('helvetica', 'B', 16);
$pdf->SetXY($start_x + 201, $y_pos + 4);
$pdf->Cell($card_width, 6, $total_stock, 0, 0, 'C');
$pdf->SetFont('helvetica', '', 9);
$pdf->SetXY($start_x + 201, $y_pos + 11);
$pdf->Cell($card_width, 4, 'TOTAL ITEM', 0, 0, 'C');

$pdf->Ln(25);

// ===== TABLE DENGAN TEMA PURPLE =====
// Table header
$pdf->SetFont('helvetica', 'B', 10);
$pdf->SetFillColor(102, 51, 153); // Purple header
$pdf->SetTextColor(255, 255, 255);
$pdf->SetDrawColor(102, 51, 153);

// Column widths untuk landscape
$widths = [18, 28, 70, 28, 40, 38, 22, 28];

$headers = ['No', 'ID Stok', 'Nama Barang', 'Kategori', 'Pemilik', 'No. Telepon', 'Qty', 'Status'];

// Draw header
for($i = 0; $i < count($headers); $i++) {
    $pdf->Cell($widths[$i], 10, $headers[$i], 1, 0, 'C', true);
}
$pdf->Ln();

// Table rows
$pdf->SetFont('helvetica', '', 9);
$pdf->SetDrawColor(200, 200, 200);

mysqli_data_seek($result, 0);
$no = 1;
while($row = mysqli_fetch_assoc($result)) {
    // Alternating row colors dengan tema purple
    if($no % 2 == 0) {
        $pdf->SetFillColor(248, 246, 252); // Very light purple
        $pdf->SetTextColor(51, 51, 51);
    } else {
        $pdf->SetFillColor(255, 255, 255); // White
        $pdf->SetTextColor(51, 51, 51);
    }
    
    // Row number
    $pdf->Cell($widths[0], 8, $no, 1, 0, 'C', true);
    
    // ID Stok
    $pdf->SetFont('helvetica', 'B', 9);
    $pdf->Cell($widths[1], 8, $row['id_stok'], 1, 0, 'C', true);
    
    // Nama Barang
    $pdf->SetFont('helvetica', '', 9);
    $nama = strlen($row['nama_barang']) > 32 ? substr($row['nama_barang'], 0, 29) . '...' : $row['nama_barang'];
    $pdf->Cell($widths[2], 8, $nama, 1, 0, 'L', true);
    
    // Kategori
    $pdf->Cell($widths[3], 8, $row['kategori'], 1, 0, 'C', true);
    
    // Pemilik
    $pemilik = strlen($row['pemilik_barang']) > 18 ? substr($row['pemilik_barang'], 0, 15) . '...' : $row['pemilik_barang'];
    $pdf->Cell($widths[4], 8, $pemilik, 1, 0, 'C', true);
    
    // No Telepon
    $pdf->Cell($widths[5], 8, $row['no_telp'], 1, 0, 'C', true);
    
    // Qty (Bold)
    $pdf->SetFont('helvetica', 'B', 10);
    $pdf->Cell($widths[6], 8, $row['stok'], 1, 0, 'C', true);
    
    // Status dengan warna - DISESUAIKAN UNTUK SISTEM PENITIPAN
    $stok = (int)$row['stok'];
    $pdf->SetFont('helvetica', 'B', 8);

    if($stok == 0) {
        $status = 'SUDAH DIAMBIL';
        $pdf->SetTextColor(220, 53, 69); // Red - sudah diambil
    } else {
        $status = 'TERSIMPAN';
        $pdf->SetTextColor(40, 167, 69); // Green - masih tersimpan
    }
    
    $pdf->Cell($widths[7], 8, $status, 1, 1, 'C', true);
    
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
$pdf->Cell(125, 5, 'Total ' . $total_items . ' barang penitipan tercatat dalam sistem', 0, 0, 'L');
$pdf->Cell(132, 5, 'Dicetak pada: ' . date('d/m/Y H:i:s'), 0, 1, 'R');

// Company branding
$pdf->SetFont('helvetica', 'B', 10);
$pdf->SetTextColor(102, 51, 153);
$pdf->SetXY(15, $current_y + 15);
$pdf->Cell(0, 5, 'VOINVENTORY - SIMPLIFY WITH VXKNET', 0, 1, 'C');

// Output PDF
$filename = 'Laporan_Penitipan_' . date('Y-m-d_H-i-s') . '.pdf';
$pdf->Output($filename, 'D');
?>
