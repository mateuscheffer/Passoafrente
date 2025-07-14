<?php
/**
 * API para buscar avaliações salvas no banco de dados
 * 
 * Endpoints:
 * GET /api/buscar_avaliacoes.php?email=xxx - Busca todas as avaliações de um email
 * GET /api/buscar_avaliacoes.php?id=xxx - Busca uma avaliação específica por ID
 */

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Tratar requisições OPTIONS (CORS preflight)
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Incluir o gerenciador de banco de dados
require_once __DIR__ . '/../database/novo_db_manager.php';

try {
    $db = new NovoDBManager();
    
    // Verificar se é busca por email ou por ID
    if (isset($_GET['email']) && !empty($_GET['email'])) {
        // Buscar por email
        $email = filter_var($_GET['email'], FILTER_SANITIZE_EMAIL);
        
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            http_response_code(400);
            echo json_encode([
                'success' => false,
                'error' => 'Email inválido'
            ]);
            exit();
        }
        
        $avaliacoes = $db->buscarAvaliacoesPorEmail($email);
        
        echo json_encode([
            'success' => true,
            'avaliacoes' => $avaliacoes,
            'total' => count($avaliacoes)
        ]);
        
    } elseif (isset($_GET['id']) && !empty($_GET['id'])) {
        // Buscar por ID
        $id = intval($_GET['id']);
        
        if ($id <= 0) {
            http_response_code(400);
            echo json_encode([
                'success' => false,
                'error' => 'ID inválido'
            ]);
            exit();
        }
        
        $avaliacao = $db->buscarAvaliacaoPorId($id);
        
        if ($avaliacao) {
            echo json_encode([
                'success' => true,
                'avaliacao' => $avaliacao
            ]);
        } else {
            http_response_code(404);
            echo json_encode([
                'success' => false,
                'error' => 'Avaliação não encontrada'
            ]);
        }
        
    } else {
        // Buscar todas as avaliações (sem parâmetros)
        $avaliacoes = $db->buscarTodasAvaliacoes();
        
        echo json_encode([
            'success' => true,
            'avaliacoes' => $avaliacoes,
            'total' => count($avaliacoes)
        ]);
    }
    
    $db->fecharConexao();
    
} catch (Exception $e) {
    error_log("Erro na API buscar_avaliacoes: " . $e->getMessage());
    
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Erro interno do servidor'
    ]);
}