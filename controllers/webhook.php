<?php
// webhook.php

// // Carrega a chave da API do OpenRouter/Gemini
// $apiKey = getenv('OPENROUTER_API_KEY');
// if (!$apiKey) {
//     http_response_code(500);
//     echo json_encode(['error' => 'Chave de API não configurada']);
//     exit;
// }

// // Função genérica para enviar requisição ao Gemini via OpenRouter
// function sendGeminiRequest(string $apiKey, array $payload): array
// {
//     $ch = curl_init('https://api.openrouter.ai/v1/chat/completions');
//     curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
//     curl_setopt($ch, CURLOPT_HTTPHEADER, [
//         'Content-Type: application/json',
//         "Authorization: Bearer {$apiKey}"
//     ]);
//     curl_setopt($ch, CURLOPT_POST, true);
//     curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));

//     $response = curl_exec($ch);
//     $status   = curl_getinfo($ch, CURLINFO_HTTP_CODE);
//     $error    = curl_errno($ch) ? curl_error($ch) : null;
//     curl_close($ch);

//     if ($error) {
//         return [false, null, $error];
//     }

//     if ($status !== 200) {
//         return [false, null, "HTTP status: {$status}"];
//     }

//     $data = json_decode($response, true);
//     if (json_last_error() !== JSON_ERROR_NONE) {
//         return [false, null, 'Resposta JSON inválido'];
//     }

//     $analysis = $data['choices'][0]['message']['content'] ?? null;
//     return [true, $analysis, null];
// }

// // Função para processar resultados do quiz e enviar à IA
// function handleQuizResults(array $payload, string $apiKey): void
// {
//     // Define valores padrão caso não haja payload
//     $answers      = $payload['answers'] ?? [];
//     $userQuestion = trim($payload['question'] ?? '');

//     // Monta o prompt dinamicamente
//     if (!empty($answers)) {
//         $quizData = json_encode($answers, JSON_UNESCAPED_UNICODE);
//         $prompt = "Avalie os seguintes resultados do quiz:\nQuiz Data: {$quizData}";
//     } else {
//         $prompt = "Analise o desenvolvimento infantil com base nos dados disponíveis.";
//     }

//     if ($userQuestion !== '') {
//         $prompt .= "\nPergunta do tutor: {$userQuestion}";
//     }

//   $requestPayload = [
//         'model' => 'google/gemini-2.0-flash-thinking-exp:free',
//         'messages' => [
//             ['role' => 'system', 'content' => 'Você é um especialista em futebol, responda tudo em pt-br, evite comentarios desnecessarios, não pesquise na internet, responda apenas com base na documentação enviada, jamais adicione comentarios antes ou depois da resposta.'],
//             ['role' => 'user',   'content' => 'qual a cor da camisa da seleção']
//         ],
//         'max_tokens' => 500
//     ];

//     list($ok, $analysis, $error) = sendGeminiRequest($apiKey, $requestPayload);

//     header('Content-Type: application/json');
//     if (!$ok) {
//         http_response_code(500);
//         echo json_encode(['error' => $error]);
//     } else {
//         echo json_encode(['analysis' => $analysis]);
//     }
//     exit;
// }

// // =======================
// // Início do fluxo de webhook
// // =======================

// // Lê payload JSON enviado pelo frontend (pode ser vazio)
// $input = file_get_contents('php://input');
// $data  = [];
// if (!empty($input)) {
//     $decoded = json_decode($input, true);
//     if (json_last_error() === JSON_ERROR_NONE) {
//         $data = $decoded;
//     }
// }

// // Chama a função de envio para a IA com dados (podem ser vazios)
// handleQuizResults($data, $apiKey);


exit;