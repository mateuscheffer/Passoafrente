<?php
/**
 * API para buscar uma avaliação específica pelo ID
 * 
 * Endpoint: /api/get_assessment.php?id=123
 * Método: GET
 * 
 * Este endpoint retorna os detalhes de uma avaliação específica pelo seu ID,
 * permitindo que os usuários acessem informações detalhadas de uma avaliação.
 */

// Configura cabeçalhos para JSON e CORS
header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

// Verifica se o método é GET
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode(["success" => false, "error" => "Método não permitido. Utilize GET."]);
    exit;
}

// Verifica se o ID foi fornecido
if (!isset($_GET['id']) || empty($_GET['id'])) {
    http_response_code(400);
    echo json_encode(["success" => false, "error" => "ID da avaliação não fornecido."]);
    exit;
}

$assessmentId = filter_var($_GET['id'], FILTER_VALIDATE_INT);

// Valida o ID
if (!$assessmentId) {
    http_response_code(400);
    echo json_encode(["success" => false, "error" => "ID da avaliação inválido."]);
    exit;
}

try {
    // Carrega o gerenciador de banco de dados
    require_once __DIR__ . '/../database/db_manager.php';
    $dbManager = new DBManager();
    
    // Busca a avaliação pelo ID
    $assessment = $dbManager->getAssessmentById($assessmentId);
    
    if (!$assessment) {
        http_response_code(404);
        echo json_encode(["success" => false, "error" => "Avaliação não encontrada."]);
        exit;
    }
    
    // Retorna a avaliação encontrada
    echo json_encode([
        "success" => true,
        "assessment" => $assessment
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        "success" => false,
        "error" => "Erro ao buscar avaliação: " . $e->getMessage()
    ]);
}