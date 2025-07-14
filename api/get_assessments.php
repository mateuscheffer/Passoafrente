<?php
/**
 * API para buscar avaliações anteriores por email do tutor
 * 
 * Endpoint: /api/get_assessments.php?email=email@exemplo.com
 * Método: GET
 * 
 * Este endpoint retorna todas as avaliações associadas a um determinado email de tutor,
 * permitindo que os usuários acessem seu histórico de avaliações.
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

// Verifica se o email foi fornecido
if (!isset($_GET['email']) || empty($_GET['email'])) {
    http_response_code(400);
    echo json_encode(["success" => false, "error" => "Email não fornecido."]);
    exit;
}

$email = filter_var($_GET['email'], FILTER_SANITIZE_EMAIL);

// Valida o email
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    http_response_code(400);
    echo json_encode(["success" => false, "error" => "Email inválido."]);
    exit;
}

try {
    // Carrega o gerenciador de banco de dados
    require_once __DIR__ . '/../database/db_manager.php';
    $dbManager = new DBManager();
    
    // Busca as avaliações para este email
    $assessments = $dbManager->getAssessmentsByEmail($email);
    
    // Retorna as avaliações encontradas
    echo json_encode([
        "success" => true,
        "count" => count($assessments),
        "assessments" => $assessments
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        "success" => false,
        "error" => "Erro ao buscar avaliações: " . $e->getMessage()
    ]);
}