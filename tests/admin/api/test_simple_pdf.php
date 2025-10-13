<?php
// Test simple de generación de PDF

require_once __DIR__ . '/../../../vendor/autoload.php';

try {
    $pdf = new TCPDF('P', 'mm', 'A4', true, 'UTF-8', false);
    
    $pdf->SetCreator('Angelow');
    $pdf->SetAuthor('Sistema de Prueba');
    $pdf->SetTitle('Test PDF');
    
    $pdf->SetMargins(15, 15, 15);
    $pdf->SetAutoPageBreak(TRUE, 15);
    
    $pdf->AddPage();
    $pdf->SetFont('helvetica', 'B', 20);
    $pdf->Cell(0, 10, 'Test de PDF - Angelow', 0, 1, 'C');
    
    $pdf->SetFont('helvetica', '', 12);
    $pdf->Ln(10);
    $pdf->Write(0, 'Si puedes ver este PDF, significa que TCPDF está funcionando correctamente en tu servidor.', '', 0, 'L', true);
    
    $pdf->Ln(5);
    $pdf->Write(0, 'Fecha: ' . date('d/m/Y H:i:s'), '', 0, 'L', true);
    
    $pdf->Ln(10);
    $pdf->SetFont('helvetica', 'B', 14);
    $pdf->Write(0, '✓ Sistema listo para exportar PDFs de órdenes', '', 0, 'L', true);
    
    // Limpiar buffer
    while (ob_get_level()) {
        ob_end_clean();
    }
    
    // Enviar PDF
    header('Content-Type: application/pdf');
    header('Content-Disposition: inline; filename="test_pdf.pdf"');
    
    $pdf->Output('test_pdf.pdf', 'I');
    exit();
    
} catch (Exception $e) {
    echo "Error al generar PDF: " . $e->getMessage();
}
?>
