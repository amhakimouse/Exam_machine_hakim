<?php
require_once __DIR__ . '/../vendor/autoload.php';

function generatePDF($html, $filename = 'document.pdf', $outputMode = 'I') {
    try {
        $mpdf = new \Mpdf\Mpdf([
            'margin_left' => 20,
            'margin_right' => 20,
            'margin_top' => 25,
            'margin_bottom' => 25,
            'margin_header' => 10,
            'margin_footer' => 10,
        ]);

        $mpdf->SetProtected(['print']);
        $mpdf->SetTitle("Exam Generated Document");
        $mpdf->SetAuthor("Exam Machine");
        $mpdf->SetDisplayMode('fullpage');
        
        $mpdf->WriteHTML($html);
        
        // Mode I: send the file inline to the browser
        // Mode D: download the file
        // Mode F: save to a local file
        $mpdf->Output($filename, $outputMode);
        
    } catch (\Mpdf\MpdfException $e) {
        error_log("mPDF Error: " . $e->getMessage());
        echo "An error occurred while generating the PDF.";
    }
}
