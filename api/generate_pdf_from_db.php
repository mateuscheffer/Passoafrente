<?php
// Configuração de log e tratamento de erros
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/pdf_generation.log');

// Função para log
function log_message($message) {
    $timestamp = date('Y-m-d H:i:s');
    error_log("[$timestamp] $message" . PHP_EOL, 3, __DIR__ . '/pdf_generation.log');
}

// Incluir o gerenciador de banco de dados
require_once __DIR__ . '/../database/novo_db_manager.php';

// Incluir o autoloader do Composer
require_once __DIR__ . '/../vendor/autoload.php';

use Mpdf\Mpdf;
use Mpdf\Output\Destination;
use Mpdf\Config\ConfigVariables;
use Mpdf\Config\FontVariables;

/**
 * Gera PDF a partir de dados de uma avaliação no banco de dados
 * 
 * @param int $idAvaliacao ID da avaliação
 * @param bool $saveToFile Se deve salvar o arquivo no servidor
 * @return string|bool Caminho do arquivo salvo ou false em caso de erro
 */
function generatePdfFromDatabase($idAvaliacao, $saveToFile = true) {
    try {
        log_message("Generating PDF for evaluation ID: " . $idAvaliacao);
        
        // Conectar ao banco de dados
        $dbManager = new NovoDBManager();
        
        // Buscar dados da avaliação
        $avaliacao = $dbManager->buscarAvaliacaoPorId($idAvaliacao);
        
        if (!$avaliacao) {
            log_message("Error: Evaluation not found for ID: " . $idAvaliacao);
            return false;
        }
        
        log_message("Evaluation data retrieved successfully");
        
        // Preparar dados para o PDF
        $currentDate = date("d/m/Y");
        $dataAvaliacao = date("d/m/Y", strtotime($avaliacao['data_avaliacao']));
        
        // Construir HTML do conteúdo
        $resultContentHtml = buildHtmlContent($avaliacao);
        
        // Gerar nome do arquivo
        $dataFormatada = date('d-m-Y');
        $nomeCrianca = $avaliacao['nome_crianca'];
        $idadeMeses = $avaliacao['idade_meses'] ?? $avaliacao['idade_bebe'] ?? 'N/A';
        
        // Criar nome mais descritivo
        $filename = "Passo_a_Frente_-_{$nomeCrianca}_{$idadeMeses}meses_{$dataFormatada}.pdf";
        
        // Sanitizar nome do arquivo mantendo acentos mas removendo caracteres problemáticos
        $filename = preg_replace('/[\\\/:*?"<>|]/', '_', $filename);
        $filename = str_replace(' ', '_', $filename);
        $filename = preg_replace('/_+/', '_', $filename); // Remover underscores duplos
        
        log_message("PDF filename set to: " . $filename);
        
        // Gerar PDF
        $pdfContent = generatePdfContent($resultContentHtml, $currentDate, $filename);
        
        if ($saveToFile) {
            // Criar diretório de PDFs se não existir
            $pdfDir = __DIR__ . '/../pdfs/';
            if (!is_dir($pdfDir)) {
                mkdir($pdfDir, 0755, true);
                log_message("Created PDF directory: " . $pdfDir);
            }
            
            // Salvar arquivo
            $filePath = $pdfDir . $filename;
            file_put_contents($filePath, $pdfContent);
            
            // Retornar caminho relativo para armazenar no banco
            $relativePath = 'pdfs/' . $filename;
            log_message("PDF saved to: " . $filePath);
            
            return $relativePath;
        }
        
        return $pdfContent;
        
    } catch (Exception $e) {
        log_message("Error generating PDF: " . $e->getMessage());
        return false;
    }
}

/**
 * Constrói o HTML do conteúdo da avaliação
 */
function buildHtmlContent($avaliacao) {
    $dataAvaliacao = date("d/m/Y", strtotime($avaliacao['data_avaliacao']));
    
    $html = '<div class="assessment-content">';
    
    // Cabeçalho
    $html .= '<div class="header-section">';
    $html .= '<h1 style="text-align: center; color: #A8434A; font-size: 18pt; margin-bottom: 5px;">Passo à Frente</h1>';
    $html .= '<p style="text-align: center; font-size: 12pt; margin-bottom: 5px;">Plataforma de acompanhamento do desenvolvimento motor infantil</p>';
    $html .= '<p style="text-align: center; font-size: 11pt; margin-bottom: 20px;">(' . $dataAvaliacao . ')</p>';
    $html .= '</div>';
    
    // Informações básicas
    $html .= '<div class="basic-info">';
    $html .= '<p><strong>Email:</strong> ' . htmlspecialchars($avaliacao['email']) . '</p>';
    $html .= '<p><strong>Nome do Bebê:</strong> ' . htmlspecialchars($avaliacao['nome_crianca']) . '</p>';
    $html .= '<p><strong>Idade do bebê:</strong> ' . htmlspecialchars($avaliacao['idade_bebe']) . '</p>';
    
    if ($avaliacao['pcd'] == 1) {
        $html .= '<p><strong>PCD:</strong> Sim';
        if (!empty($avaliacao['descricao_pcd'])) {
            $html .= ' - ' . htmlspecialchars($avaliacao['descricao_pcd']);
        }
        $html .= '</p>';
    } else {
        $html .= '<p><strong>PCD:</strong> Não</p>';
    }
    
    if ($avaliacao['prematuro'] == 1) {
        $html .= '<p><strong>Bebê Prematuro:</strong> Sim';
        if (!empty($avaliacao['semanas_gestacao'])) {
            $html .= ' - ' . htmlspecialchars($avaliacao['semanas_gestacao']) . ' semanas';
        }
        $html .= '</p>';
    } else {
        $html .= '<p><strong>Bebê Prematuro:</strong> Não</p>';
    }
    
    $html .= '</div>';
    
    // Dúvida do usuário
    if (!empty($avaliacao['pergunta_usuario'])) {
        $html .= '<div class="user-question-section">';
        $html .= '<h3>Dúvida do Usuário:</h3>';
        $html .= '<p>' . htmlspecialchars($avaliacao['pergunta_usuario']) . '</p>';
        $html .= '</div>';
    }
    
    // Perguntas e respostas do sistema
    if (!empty($avaliacao['respostas'])) {
        $html .= '<div class="questions-answers-section">';
        $html .= '<h3>Perguntas e Respostas:</h3>';
        $html .= '<ul>';
        
        foreach ($avaliacao['respostas'] as $resposta) {
            $html .= '<li>';
            $html .= '<p><strong>Pergunta:</strong> ' . htmlspecialchars($resposta['texto_pergunta']) . '</p>';
            $html .= '<p><strong>Resposta:</strong> ' . htmlspecialchars($resposta['resposta_opcao']) . '</p>';
            $html .= '</li>';
        }
        
        $html .= '</ul>';
        $html .= '</div>';
    }
    
    // Análise da IA
    if (!empty($avaliacao['parecer_ia'])) {
        $html .= '<div class="ai-analysis-section">';
        $html .= '<h3>Análise da IA:</h3>';
        
        // Processar markdown do parecer da IA
        $parecerFormatado = processMarkdown($avaliacao['parecer_ia']);
        $html .= '<div>' . $parecerFormatado . '</div>';
        $html .= '</div>';
    }
    
    $html .= '</div>';
    
    return $html;
}

/**
 * Processa markdown básico para HTML com estilização padronizada
 */
function processMarkdown($text) {
    // Limpar texto inicial
    $text = trim($text);
    
    // Remover linhas com apenas "---" (separadores desnecessários)
    $text = preg_replace('/^\s*---+\s*$/m', '', $text);
    
    // Primeiro, processar títulos markdown antes de outras transformações
    $text = preg_replace('/^####\s+(.+)$/m', '<h5 class="markdown-h5">$1</h5>', $text);
    $text = preg_replace('/^###\s+(.+)$/m', '<h4 class="markdown-h4">$1</h4>', $text);
    $text = preg_replace('/^##\s+(.+)$/m', '<h3 class="markdown-h3">$1</h3>', $text);
    $text = preg_replace('/^#\s+(.+)$/m', '<h2 class="markdown-h2">$1</h2>', $text);
    
    // Converter texto em negrito **texto**
    $text = preg_replace('/\*\*(.*?)\*\*/', '<strong>$1</strong>', $text);
    
    // Converter texto em itálico *texto*
    $text = preg_replace('/\*(.*?)\*/', '<em>$1</em>', $text);
    
    // Processar listas com - ou *
    $lines = explode("\n", $text);
    $processedLines = [];
    $inList = false;
    
    foreach ($lines as $line) {
        $trimmedLine = trim($line);
        
        // Verificar se é item de lista
        if (preg_match('/^[-\*]\s+(.+)$/', $trimmedLine, $matches)) {
            if (!$inList) {
                $processedLines[] = '<ul class="markdown-list">';
                $inList = true;
            }
            $processedLines[] = '<li class="markdown-list-item">' . trim($matches[1]) . '</li>';
        } else {
            // Se não é item de lista, fechar lista se estava aberta
            if ($inList) {
                $processedLines[] = '</ul>';
                $inList = false;
            }
            
            // Processar linha normal
            if (!empty($trimmedLine) && !preg_match('/^<h[2-5]/', $trimmedLine)) {
                // Se não é título, envolver em parágrafo
                $processedLines[] = '<p class="markdown-paragraph">' . $trimmedLine . '</p>';
            } else {
                $processedLines[] = $line;
            }
        }
    }
    
    // Fechar lista se ainda estiver aberta
    if ($inList) {
        $processedLines[] = '</ul>';
    }
    
    $text = implode("\n", $processedLines);
    
    // Limpar parágrafos vazios
    $text = preg_replace('/<p class="markdown-paragraph">\s*<\/p>/', '', $text);
    
    return $text;
}

/**
 * Gera o conteúdo do PDF usando mPDF
 */
function generatePdfContent($resultContentHtml, $currentDate, $filename) {
    // Pré-processamento do HTML
    $resultContentHtml = preg_replace('/text-align\s*:\s*[^;]*;?/', '', $resultContentHtml);
    $resultContentHtml = preg_replace('/margin-left\s*:\s*[^;]*;?/', '', $resultContentHtml);
    $resultContentHtml = preg_replace('/padding-left\s*:\s*[^;]*;?/', '', $resultContentHtml);
    $resultContentHtml = preg_replace('/justify\s*:\s*[^;]*;?/', '', $resultContentHtml);
    $resultContentHtml = preg_replace('/<(h[1-6]|p|div|ul|ol|li|span)([^>]*)>/', '<$1$2 class="force-left">', $resultContentHtml);
    
    // HTML completo
    $html = '<!DOCTYPE html><html><head><meta charset="UTF-8"><style>
    body {
        font-family: sans-serif;
        font-size: 11pt;
        color: #333333;
        line-height: 1.4;
        margin: 0;
        padding: 0;
        text-align: left !important;
    }
    
    * {
        text-align: left !important;
        text-justify: none !important;
        margin-left: 0 !important;
        padding-left: 0 !important;
    }
    
    h1, h2, h3, h4, h5, h6, p, ul, ol, li, div, span, table, tr, td, th {
        text-align: left !important;
        margin-left: 0 !important;
        padding-left: 0 !important;
        text-justify: none !important;
    }
    
    .header-section h1, .header-section p {
        text-align: center !important;
    }
    
    h1, h2, h3, h4, h5, h6 {
        color: #A8434A;
        font-weight: bold;
        text-align: left !important;
        margin-bottom: 10px;
        margin-top: 15px;
    }
    
    h1 { font-size: 16pt; margin-top: 0; }
    h2 { font-size: 14pt; color: #A8434A; }
    h3 { font-size: 12pt; color: #A8434A; }
    h4 { font-size: 11pt; color: #A8434A; }
    h5 { font-size: 10pt; color: #A8434A; }
    
    /* Estilos específicos para markdown */
    .markdown-h2 {
        font-size: 14pt;
        color: #A8434A;
        font-weight: bold;
        margin-top: 20px;
        margin-bottom: 12px;
        text-align: left !important;
    }
    
    .markdown-h3 {
        font-size: 12pt;
        color: #A8434A;
        font-weight: bold;
        margin-top: 15px;
        margin-bottom: 10px;
        text-align: left !important;
    }
    
    .markdown-h4 {
        font-size: 11pt;
        color: #A8434A;
        font-weight: bold;
        margin-top: 12px;
        margin-bottom: 8px;
        text-align: left !important;
    }
    
    .markdown-h5 {
        font-size: 10pt;
        color: #A8434A;
        font-weight: bold;
        margin-top: 10px;
        margin-bottom: 6px;
        text-align: left !important;
    }
    
    .markdown-paragraph {
        font-size: 11pt;
        color: #333333;
        line-height: 1.5;
        margin-bottom: 8px;
        text-align: left !important;
        text-justify: none !important;
    }
    
    .markdown-list {
        margin: 10px 0;
        padding-left: 20px;
        text-align: left !important;
    }
    
    .markdown-list-item {
        font-size: 11pt;
        color: #333333;
        margin: 4px 0;
        text-align: left !important;
        line-height: 1.4;
    }
    
    p {
        text-align: left !important;
        text-justify: none !important;
    }
    
    ul, ol {
        margin: 0 !important;
        padding: 0 !important;
        margin-left: 0 !important;
        padding-left: 0 !important;
        text-align: left !important;
    }
    
    ul li, ol li {
        margin-left: 15px !important;
        text-align: left !important;
        display: list-item;
    }
    
    .force-left {
        text-align: left !important;
        text-justify: none !important;
    }
    
    .ai-analysis-section {
        margin-top: 20px;
        padding: 10px;
        border-left: 3px solid #A8434A;
        background-color: #f9f9f9;
    }
    
    .ai-analysis-section h3 {
        margin-top: 0;
        color: #A8434A;
    }
    
    .ai-analysis-section ul {
        margin: 10px 0;
        padding-left: 20px;
    }
    
    .ai-analysis-section li {
        margin: 5px 0;
        text-align: left !important;
    }
    
    .basic-info {
        margin-bottom: 20px;
        padding: 10px;
        background-color: #f5f5f5;
        border-radius: 5px;
    }
    
    .user-question-section {
        margin: 20px 0;
        padding: 10px;
        background-color: #e8f4f8;
        border-left: 3px solid #2196F3;
    }
    
    .questions-answers-section {
        margin: 20px 0;
    }
    
    .questions-answers-section ul {
        list-style-type: none;
        padding: 0;
    }
    
    .questions-answers-section li {
        margin: 15px 0;
        padding: 10px;
        background-color: #f0f0f0;
        border-radius: 5px;
    }
    </style></head><body>';
    
    $html .= $resultContentHtml;
    $html .= '</body></html>';
    
    // Configurar mPDF
    $mpdf = new Mpdf([
        'mode' => 'utf-8',
        'format' => 'A4',
        'margin_left' => 15,
        'margin_right' => 15,
        'margin_top' => 20,
        'margin_bottom' => 20,
        'margin_header' => 10,
        'margin_footer' => 10
    ]);
    
    // Footer
    $footerHtml = '<div style="text-align: center; font-size: 9pt; color: #666; border-top: 1px solid #ccc; padding-top: 5px;">'
        . '© 2025 | Relatório gerado automaticamente<br>'
        . 'Página {PAGENO} de {nbpg}'
        . '</div>';
    
    $mpdf->SetDefaultBodyCSS('text-align', 'left');
    $mpdf->SetDefaultBodyCSS('justify', 'none');
    $mpdf->SetDefaultBodyCSS('ul', 'padding-left', '0');
    $mpdf->SetDefaultBodyCSS('ol', 'padding-left', '0');
    $mpdf->SetDefaultBodyCSS('li', 'text-align', 'left');
    
    $mpdf->SetTitle('Relatório de Resultados - ' . $currentDate);
    $mpdf->SetAuthor('Sistema de Avaliação de Desenvolvimento');
    $mpdf->SetHTMLFooter($footerHtml);
    
    $mpdf->WriteHTML($html);
    
    return $mpdf->Output('', Destination::STRING_RETURN);
}

// Se chamado diretamente via GET com parâmetro id
if (isset($_SERVER['REQUEST_METHOD']) && $_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['id'])) {
    $idAvaliacao = intval($_GET['id']);
    
    if ($idAvaliacao <= 0) {
        http_response_code(400);
        echo json_encode(["error" => "ID de avaliação inválido"]);
        exit;
    }
    
    try {
        $dbManager = new NovoDBManager();
        $avaliacao = $dbManager->buscarAvaliacaoPorId($idAvaliacao);
        
        if (!$avaliacao) {
            http_response_code(404);
            echo json_encode(["error" => "Avaliação não encontrada"]);
            exit;
        }
        
        // Gerar PDF sem salvar arquivo
        $pdfContent = generatePdfFromDatabase($idAvaliacao, false);
        
        if ($pdfContent === false) {
            http_response_code(500);
            echo json_encode(["error" => "Erro ao gerar PDF"]);
            exit;
        }
        
        // Enviar PDF para download
        $dataFormatada = date('d-m-Y');
        $nomeCrianca = $avaliacao['nome_crianca'];
        $idadeMeses = $avaliacao['idade_meses'] ?? $avaliacao['idade_bebe'] ?? 'N/A';
        
        // Criar nome mais descritivo
        $filename = "Passo_a_Frente_-_{$nomeCrianca}_{$idadeMeses}meses_{$dataFormatada}.pdf";
        
        // Sanitizar nome do arquivo mantendo acentos mas removendo caracteres problemáticos
        $filename = preg_replace('/[\\\/:*?"<>|]/', '_', $filename);
        $filename = str_replace(' ', '_', $filename);
        $filename = preg_replace('/_+/', '_', $filename); // Remover underscores duplos
        
        header('Content-Type: application/pdf');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Cache-Control: private, max-age=0, must-revalidate');
        header('Pragma: public');
        
        echo $pdfContent;
        exit;
        
    } catch (Exception $e) {
        log_message("Error in direct PDF generation: " . $e->getMessage());
        http_response_code(500);
        echo json_encode(["error" => "Erro interno do servidor"]);
        exit;
    }
}
?>