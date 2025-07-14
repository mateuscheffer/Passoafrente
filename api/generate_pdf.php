<?php
// /api/generate_pdf.php (Simplificado v3 - Alinhamento consistente)

// --- Error Reporting & Logging Setup ---
ini_set("display_errors", 0);
ini_set("log_errors", 1);
$log_file = __DIR__ . "/../php_error.log"; // Define log file path
ini_set("error_log", $log_file);
error_reporting(E_ALL);

function log_message($message) {
    $log_dir = dirname(ini_get("error_log"));
    if (!is_writable($log_dir)) {
        error_log("Log Directory Not Writable: " . $log_dir . " | Message: " . $message);
        return;
    }
    error_log(date("[Y-m-d H:i:s] ") . $message . "\n", 3, ini_get("error_log"));
}

log_message("--- generate_pdf_simplificado_v3.php accessed ---");

// Set default content type to JSON for potential errors initially
header("Content-Type: application/json");

// Check if it's a POST request
if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    http_response_code(405);
    log_message("Error: Request method was " . $_SERVER["REQUEST_METHOD"] . ", not POST.");
    echo json_encode(["error" => "Método não permitido. Use POST."]);
    exit;
}

// Get raw POST data
$rawData = file_get_contents("php://input");
log_message("Raw POST data received. Length: " . strlen($rawData) . " bytes");

// Decode JSON data with detailed error handling
$data = json_decode($rawData, true);
$jsonError = json_last_error();
$jsonErrorMsg = json_last_error_msg();

if ($jsonError !== JSON_ERROR_NONE) {
    http_response_code(400);
    log_message("JSON decode error: " . $jsonErrorMsg . " (code: " . $jsonError . ")");
    echo json_encode([
        "error" => "Erro ao decodificar JSON: " . $jsonErrorMsg,
        "code" => $jsonError,
        "raw_length" => strlen($rawData)
    ]);
    exit;
}

// Log received data structure
if (is_array($data)) {
    log_message("JSON decoded successfully. Keys received: " . implode(', ', array_keys($data)));
} else {
    http_response_code(400);
    log_message("Error: Decoded data is not an array");
    echo json_encode(["error" => "Dados decodificados não são um array"]);
    exit;
}

// Validate required fields - Simplificado para apenas resultContentHtml
if (!isset($data["resultContentHtml"])) {
    http_response_code(400);
    log_message("Error: Missing required field: resultContentHtml");
    echo json_encode([
        "error" => "Campo obrigatório ausente: resultContentHtml",
        "received_data_keys" => array_keys($data)
    ]);
    exit;
}

// Include Composer's autoloader
$autoloaderPath = __DIR__ . '/../vendor/autoload.php';
if (!file_exists($autoloaderPath)) {
     http_response_code(500);
     log_message("Error: vendor/autoload.php not found at: " . $autoloaderPath);
     echo json_encode(["error" => "Erro interno do servidor: Autoloader não encontrado."]);
     exit;
}
require $autoloaderPath;
log_message("Composer autoloader included.");

use Mpdf\Mpdf;
use Mpdf\Output\Destination;
use Mpdf\Config\ConfigVariables;
use Mpdf\Config\FontVariables;

// Extract data with fallbacks
$resultContentHtml = $data["resultContentHtml"];
$currentDate = $data["currentDate"] ?? date("d/m/Y");

// Pré-processamento do HTML para garantir alinhamento consistente
// Remover qualquer estilo inline que possa afetar o alinhamento
$resultContentHtml = preg_replace('/text-align\s*:\s*[^;]*;?/', '', $resultContentHtml);
$resultContentHtml = preg_replace('/margin-left\s*:\s*[^;]*;?/', '', $resultContentHtml);
$resultContentHtml = preg_replace('/padding-left\s*:\s*[^;]*;?/', '', $resultContentHtml);
$resultContentHtml = preg_replace('/justify\s*:\s*[^;]*;?/', '', $resultContentHtml);

// Adicionar classes de alinhamento a todos os elementos principais
$resultContentHtml = preg_replace('/<(h[1-6]|p|div|ul|ol|li|span)([^>]*)>/', '<$1$2 class="force-left">', $resultContentHtml);

// Generate filename
$safeDate = str_replace('/', '-', $currentDate);
$filename = "relatorio-resultados-" . $safeDate . ".pdf";
log_message("PDF filename set to: " . $filename);

// --- Start HTML Generation ---
log_message("Starting HTML generation...");
$html = '<!DOCTYPE html><html><head><meta charset="UTF-8"><style>
';
// CSS ajustado para garantir alinhamento consistente em todas as seções
$html .= '
body {
    font-family: sans-serif;
    font-size: 11pt;
    color: #333333;
    line-height: 1.4;
    margin: 0;
    padding: 0;
    text-align: left !important;
}

/* Reset completo para garantir consistência */
* {
    text-align: left !important;
    text-justify: none !important;
    margin-left: 0 !important;
    padding-left: 0 !important;
}

/* Todos os elementos alinhados à esquerda */
h1, h2, h3, h4, h5, h6, p, ul, ol, li, div, span, table, tr, td, th {
    text-align: left !important;
    margin-left: 0 !important;
    padding-left: 0 !important;
    text-justify: none !important;
}
p:nth-of-type(1){
    color: #A8434A;
}

/* Estilos específicos para títulos */
h1, h2, h3, h4, h5, h6 {
    color: #A8434A;
    font-weight: bold;
    text-align: left !important;
}

h1 { 
    font-size: 16pt; 
    margin-top: 0;
}

h2 { 
    font-size: 14pt; 
    color: #A8434A;
}

h3 { 
    font-size: 12pt; 
    color: #A8434A;
}

/* Estilos para parágrafos */
p {
    text-align: left !important;
    text-justify: none !important;
}

/* Estilos para listas - importante para manter alinhamento após Pontos Fortes */
ul, ol {
    margin: 0 !important;
    padding: 0 !important;
    margin-left: 0 !important;
    padding-left: 0 !important;
    text-align: left !important;
}

ul li, ol li {
    margin-left: 15px !important; /* Apenas um pequeno recuo para os marcadores */
    text-align: left !important;
    display: list-item;
}

/* Linha horizontal após títulos de seção */
.section-divider {
    border-top: 1px solid #DDDDDD;
    width: 100%;
}

/* Estilos para tabelas */
table {
    border-collapse: collapse;
    width: 100%;
    text-align: left !important;
}

th, td {
    border: 1px solid #ccc;
    padding: 6px 8px;
    text-align: left !important;
}

th {
    background-color: #f2f2f2;
    font-weight: bold;
}

/* Rodapé */
.footer {
    font-size: 9pt;
    color: #666666;
    text-align: center;
    border-top: 0.5px solid #CCCCCC;
    padding-top: 2mm;
    margin-top: 10mm;
}

/* Classe para forçar alinhamento à esquerda */
.force-left {
    text-align: left !important;
    justify-content: flex-start !important;
    margin-left: 0 !important;
    padding-left: 0 !important;
}

/* Classe para container principal */
.content-container {
    width: 100%;
    margin: 0;
    padding: 0;
    text-align: left !important;
}

/* Correção específica para listas após Pontos Fortes */
h2 + ul, h2 + ol, 
h3 + ul, h3 + ol,
div + ul, div + ol {
    margin-left: 0 !important;
    padding-left: 0 !important;
}
';
$html .= '</style></head><body>';

// Título simples
$html .= '<h1 class="force-left">Passo à Frente</h1>';
$html .= '<p class="force-left">Plataforma de acompanhamento do desenvolvimento motor infantil</p>';
$html .= '<p class="force-left" style="text-align:left; font-size:10pt;">Gerado em: ' . htmlspecialchars($currentDate) . '</p>';

// Conteúdo principal com container para garantir alinhamento
$html .= '<div class="content-container force-left">' . $resultContentHtml . '</div>';

// Footer Definition
$footerHtml = '<div class="footer">
    © ' . date("Y") . ' | Relatório gerado automaticamente<br>
    Página {PAGENO} de {nbpg}
</div>';

$html .= '</body></html>';
log_message("HTML generation complete. Total length: " . strlen($html) . " chars.");

// --- mPDF Generation ---
try {
    log_message("Initializing mPDF...");
    $defaultConfig = (new ConfigVariables())->getDefaults();
    $fontDirs = $defaultConfig['fontDir'];
    $defaultFontConfig = (new FontVariables())->getDefaults();
    $fontData = $defaultFontConfig['fontdata'];

    $tempDir = sys_get_temp_dir() . '/mpdf_tmp';
    if (!is_dir($tempDir)) { 
        if (!mkdir($tempDir, 0777, true)) { 
            throw new \Exception("Failed to create mPDF temp directory: " . $tempDir); 
        } 
    }
    if (!is_writable($tempDir)) { 
        throw new \Exception("mPDF temporary directory is not writable: " . $tempDir); 
    }
    log_message("mPDF temp directory set to: " . $tempDir);

    $mpdf = new Mpdf([
        'mode' => 'utf-8',
        'format' => 'A4',
        'margin_left' => 25,
        'margin_right' => 20,
        'margin_top' => 20,
        'margin_bottom' => 25,
        'margin_header' => 10,
        'margin_footer' => 10,
        'tempDir' => $tempDir,
        'fontDir' => array_unique($fontDirs),
        'fontdata' => $fontData,
        'default_font' => 'sans-serif',
        'autoScriptToLang' => true,
        'autoLangToFont' => true,
    ]);

    // Configurações adicionais para garantir alinhamento à esquerda
    $mpdf->SetDefaultBodyCSS('text-align', 'left');
    $mpdf->SetDefaultBodyCSS('justify', 'none');
    
    // Configurações adicionais para listas
    $mpdf->SetDefaultBodyCSS('ul', 'padding-left', '0');
    $mpdf->SetDefaultBodyCSS('ol', 'padding-left', '0');
    $mpdf->SetDefaultBodyCSS('li', 'text-align', 'left');

    log_message("mPDF initialized. Setting properties...");
    $mpdf->SetTitle('Relatório de Resultados - ' . $currentDate);
    $mpdf->SetAuthor('Sistema de Geração Automática');
    $mpdf->SetHTMLFooter($footerHtml);

    log_message("Writing HTML to mPDF...");
    @$mpdf->WriteHTML($html);
    log_message("HTML written to mPDF successfully.");

    // --- Output PDF --- 
    log_message("Preparing PDF output...");
    if (headers_sent($file, $line)) {
        log_message("Error: Headers already sent in {$file} on line {$line}. Cannot output PDF.");
        if (!headers_sent()) {
             header('Content-Type: application/json');
             http_response_code(500);
             echo json_encode(['error' => 'Headers already sent, cannot output PDF. Check logs.']);
        }
        exit;
    }
    
    header_remove();
    header('Content-Type: application/pdf');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    header('Cache-Control: private, max-age=0, must-revalidate');
    header('Pragma: public');

    log_message("Sending PDF to browser...");
    $mpdf->Output($filename, Destination::INLINE);
    log_message("PDF output finished.");
    exit;

} catch (\Mpdf\MpdfException $e) {
    http_response_code(500);
    log_message("!!! mPDF Exception: " . $e->getMessage() . " in " . $e->getFile() . " on line " . $e->getLine());
    if (!headers_sent()) { header('Content-Type: application/json'); }
    echo json_encode([
        "error" => "Erro interno do servidor ao gerar o PDF (mPDF).",
        "details" => $e->getMessage()
    ]);
    exit;
} catch (\Exception $e) {
    http_response_code(500);
    log_message("!!! General Exception: " . $e->getMessage() . " in " . $e->getFile() . " on line " . $e->getLine());
    if (!headers_sent()) { header('Content-Type: application/json'); }
    echo json_encode([
        "error" => "Erro interno inesperado no servidor.",
        "details" => $e->getMessage()
    ]);
    exit;
}
?>
