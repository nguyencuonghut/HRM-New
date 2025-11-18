<?php
/**
 * Debug Vietnamese Font Rendering Issue
 * Tests the DOCX→HTML→PDF pipeline to identify where Vietnamese characters get corrupted
 */

require __DIR__ . '/vendor/autoload.php';

use PhpOffice\PhpWord\IOFactory;
use Dompdf\Dompdf;

// Create sample test data with Vietnamese characters
$testData = [
    'contract_number' => 'HĐLĐ-2024-001',
    'employee_full_name' => 'Nguyễn Văn A',
    'base_salary' => '10,000,000',
    'insurance_salary' => '9,000,000',
    'position_allowance' => '1,500,000',
    'working_time' => 'Toàn thời gian',
    'work_location' => 'Hà Nội',
    'other_allowances_text' => 'Hỗ trợ xăng xăng',
    'contract_start_date' => '01/01/2024',
    'contract_end_date' => '31/12/2024',
    'department_name' => 'Phòng Nhân sự',
    'position_title' => 'Nhân viên Kinh doanh',
    'employee_code' => 'NV-001',
    'company_name' => 'Công ty TNHH Hồng Hà'
];

echo "=== DEBUGGING VIETNAMESE FONT RENDERING ===\n\n";

// Step 1: Load DOCX
$docxPath = storage_path('app/public/templates/contracts/probation.docx');
if (!file_exists($docxPath)) {
    echo "❌ DOCX file not found: $docxPath\n";
    exit(1);
}

echo "✓ Loading DOCX: $docxPath\n";
$phpWord = IOFactory::load($docxPath);

// Step 2: Merge data
echo "✓ Merging data (14 keys)...\n";
$phpWord->getActiveSection()->getHeader()->getFirstSection();
foreach ($testData as $key => $value) {
    foreach ($phpWord->getWordSectionObject(0)->getAllElements() as $element) {
        if (method_exists($element, 'findAndReplace')) {
            $element->findAndReplace('${'.$key.'}', $value);
        }
    }
}

// Step 3: Convert DOCX→HTML
echo "✓ Converting DOCX to HTML...\n";
$htmlWriter = IOFactory::createWriter($phpWord, 'HTML');
$tmpHtmlPath = storage_path('app/debug_temp_test.html');
$htmlWriter->save($tmpHtmlPath);

// Step 4: Read HTML and inspect it
echo "✓ Reading HTML output...\n";
$htmlContent = file_get_contents($tmpHtmlPath);
$htmlSize = strlen($htmlContent);

// Check if Vietnamese characters are in HTML
echo "\n--- HTML INSPECTION ---\n";
echo "HTML file size: $htmlSize bytes\n";

// Look for specific Vietnamese characters
$vietnameseTest = [
    'Nguyễn' => 'Nguyễn',
    'Văn' => 'Văn',
    'Hà Nội' => 'Hà Nội',
    'HỢP ĐỒNG THỬ VIỆC' => 'HỢP ĐỒNG THỬ VIỆC',
    'ế' => 'ế',
    'ư' => 'ư',
    'ơ' => 'ơ'
];

foreach ($vietnameseTest as $label => $char) {
    $found = strpos($htmlContent, $char) !== false;
    echo "Search '$label': " . ($found ? "✓ FOUND" : "❌ NOT FOUND") . "\n";
}

// Extract first 2000 chars and check encoding
echo "\n--- FIRST 2000 CHARS OF HTML ---\n";
$preview = substr($htmlContent, 0, 2000);
echo $preview . "\n";

// Check for charset declarations
echo "\n--- CHARSET DECLARATIONS ---\n";
echo (strpos($htmlContent, 'UTF-8') !== false ? "✓ UTF-8 in HTML\n" : "❌ No UTF-8\n");
echo (strpos($htmlContent, 'charset') !== false ? "✓ Charset meta found\n" : "❌ No charset meta\n");

// Step 5: Try rendering with DomPDF
echo "\n--- DOMPDF RENDERING ---\n";

// Add font CSS if not present
$vietnameseCss = '<style>
    body, *, p, td, th, div, span {
        font-family: "DejaVu Sans", "Times New Roman", serif !important;
        font-size: 11pt;
    }
</style>';

if (strpos($htmlContent, '<meta charset') === false) {
    $headPos = strpos($htmlContent, '</head>');
    if ($headPos !== false) {
        $htmlContent = substr_replace(
            $htmlContent,
            '<meta charset="UTF-8">' . $vietnameseCss,
            $headPos,
            0
        );
    }
}

$dompdf = new Dompdf();
$dompdf->loadHtml($htmlContent, 'UTF-8');
$dompdf->setPaper('A4', 'portrait');
$dompdf->render();

$pdfPath = storage_path('app/debug_test_output.pdf');
file_put_contents($pdfPath, $dompdf->output());
echo "✓ PDF generated: $pdfPath\n";

// Cleanup
@unlink($tmpHtmlPath);

echo "\n=== DIAGNOSIS COMPLETE ===\n";
echo "Open the HTML and PDF files to visually inspect Vietnamese character rendering.\n";
echo "HTML: $tmpHtmlPath (deleted after this script)\n";
echo "PDF: $pdfPath\n";
?>
