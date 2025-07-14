<?php
/**
 * Proxy para a API Places do Google
 * Este arquivo serve como intermediário para ocultar a chave da API do Google
 */

// Desativar exibição de erros para evitar corromper a saída JSON
ini_set('display_errors', 0);
error_reporting(E_ALL & ~E_NOTICE & ~E_WARNING);

// Verificar se é uma requisição GET para fotos
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['action']) && $_GET['action'] === 'getPhoto') {
    // Chave da API do Google (mantida segura no servidor)
    $apiKey = '';
    
    // Verificar se o photo_reference foi enviado
    if (!isset($_GET['photo_reference'])) {
        header('HTTP/1.1 400 Bad Request');
        echo json_encode(['error' => 'Referência da foto não fornecida']);
        exit;
    }
    
    // Parâmetros da requisição
    $photoUrl = 'https://maps.googleapis.com/maps/api/place/photo';
    $params = [
        'photoreference' => $_GET['photo_reference'],
        'maxwidth' => isset($_GET['maxwidth'] ) ? $_GET['maxwidth'] : 400,
        'key' => $apiKey
    ];
    
    // Redirecionar para a URL da foto
    header('Location: ' . $photoUrl . '?' . http_build_query($params ));
    exit;
}

// Para outras requisições, definir cabeçalhos para JSON
header('Content-Type: application/json');

// Permitir acesso via POST para as ações de busca e detalhes
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['error' => 'Método não permitido']);
    exit;
}

// Obter dados da requisição
$input = file_get_contents('php://input');
$data = json_decode($input, true);

// Verificar se os dados são válidos
if (!$data || !isset($data['action'])) {
    echo json_encode(['error' => 'Parâmetros incompletos']);
    exit;
}

// Processar diferentes tipos de ações
try {
    switch ($data['action']) {
        case 'nearbySearch':
            // Verificar se a localização foi enviada
            if (!isset($data['location'])) {
                echo json_encode(['error' => 'Localização não fornecida']);
                exit;
            }
            
            // Buscar lugares próximos
            $url = 'https://maps.googleapis.com/maps/api/place/nearbysearch/json';
            
            // Parâmetros da requisição
            $params = [
                'location' => $data['location'],
                'radius' => isset($data['radius'] ) ? $data['radius'] : 5000,
                'keyword' => isset($data['keyword']) ? $data['keyword'] : '',
                'key' => $apiKey
            ];
            
            // Fazer a requisição para a API do Google
            $result = makeRequest($url, $params);
            echo $result;
            break;
            
        case 'getDetails':
            // Obter detalhes de um lugar específico
            $url = 'https://maps.googleapis.com/maps/api/place/details/json';
            
            // Verificar se o placeId foi enviado
            if (!isset($data['placeId'] )) {
                echo json_encode(['error' => 'ID do lugar não fornecido']);
                exit;
            }
            
            // Parâmetros da requisição
            $params = [
                'place_id' => $data['placeId'],
                'fields' => isset($data['fields']) ? $data['fields'] : 'name,vicinity,formatted_phone_number,rating,photos,user_ratings_total',
                'key' => $apiKey
            ];
            
            // Fazer a requisição para a API do Google
            $result = makeRequest($url, $params);
            echo $result;
            break;
            
        default:
            echo json_encode(['error' => 'Ação não reconhecida']);
            exit;
    }
} catch (Exception $e) {
    echo json_encode(['error' => 'Erro no servidor: ' . $e->getMessage()]);
    exit;
}

/**
 * Função para fazer requisições à API do Google
 */
function makeRequest($url, $params) {
    // Construir a URL com os parâmetros
    $requestUrl = $url . '?' . http_build_query($params );
    
    // Inicializar cURL
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $requestUrl);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    
    // Executar a requisição
    $response = curl_exec($ch);
    
    // Verificar erros
    if (curl_errno($ch)) {
        curl_close($ch);
        return json_encode(['error' => 'Erro ao conectar com a API do Google: ' . curl_error($ch)]);
    }
    
    // Fechar a conexão cURL
    curl_close($ch);
    
    // Verificar se a resposta é JSON válido
    json_decode($response);
    if (json_last_error() !== JSON_ERROR_NONE) {
        return json_encode(['error' => 'Resposta inválida da API do Google']);
    }
    
    // Retornar a resposta
    return $response;
}
?>
