<?php

use Slim\Factory\AppFactory;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

require __DIR__ . '/vendor/autoload.php';  // Auto-load do Composer

// Carrega as dependências do Composer
require_once __DIR__ . '/vendor/autoload.php';

// Carrega variáveis de ambiente
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->safeLoad();

// Incluir o novo gerenciador de banco de dados
require_once __DIR__ . '/database/novo_db_manager.php';

// Criando o app Slim
$app = AppFactory::create();

// Adiciona middleware para servir arquivos estáticos
$app->addRoutingMiddleware();

// Rota para a página inicial
$app->get('/', function (Request $request, Response $response, $args) {


    $title   = 'Home - Passo a Frente';
    $tpl     = file_get_contents(__DIR__ . '/views/topo.html');

    $header  = str_replace('{{title}}', $title, $tpl);

    $content = file_get_contents(__DIR__ . '/views/index.html');
    $footer  = file_get_contents(__DIR__ . '/views/footer.html');

    $html = $header . $content . $footer;
    $response->getBody()->write($html);
    return $response->withHeader('Content-Type', 'text/html');
});

$app->get('/dicas', function (Request $request, Response $response, $args) {
    $title   = 'Dicas – Passo a Frente';
    $tpl     = file_get_contents(__DIR__ . '/views/topo.html');

    $header  = str_replace('{{title}}', $title, $tpl);

    $content = file_get_contents(__DIR__ . '/views/dicas.html');
    $footer  = file_get_contents(__DIR__ . '/views/footer.html');

    $html = $header . $content . $footer;
    $response->getBody()->write($html);
    return $response->withHeader('Content-Type', 'text/html');
});



// Rota para a página de quiz
$app->get('/quiz', function (Request $request, Response $response, $args) {
    $title   = 'Quiz de Avaliação – Passo a Frente';
    $tpl     = file_get_contents(__DIR__ . '/views/topo.html');
    // substitui {{title}} pelo título da página
    $header  = str_replace('{{title}}', $title, $tpl);

    $content = file_get_contents(__DIR__ . '/views/quiz.html');
    $footer  = file_get_contents(__DIR__ . '/views/footer.html');

    $html = $header . $content . $footer;
    $response->getBody()->write($html);
    return $response->withHeader('Content-Type', 'text/html');
});

// Rota para a página sobre
$app->get('/sobre', function (Request $request, Response $response, $args) {
    $title   = 'Sobre – Passo a Frente';
    $tpl     = file_get_contents(__DIR__ . '/views/topo.html');
    // substitui {{title}} pelo título da página
    $header  = str_replace('{{title}}', $title, $tpl);

    $content = file_get_contents(__DIR__ . '/views/sobre.html');
    $footer  = file_get_contents(__DIR__ . '/views/footer.html');

    $html = $header . $content . $footer;
    $response->getBody()->write($html);
    return $response->withHeader('Content-Type', 'text/html');
});
// Rota para a página busca
$app->get('/busca', function (Request $request, Response $response, $args) {
    $title   = 'Busca de avaliações – Passo a Frente';
    $tpl     = file_get_contents(__DIR__ . '/views/topo.html');
    // substitui {{title}} pelo título da página
    $header  = str_replace('{{title}}', $title, $tpl);

    $content = file_get_contents(__DIR__ . '/views/busca.html');
    $footer  = file_get_contents(__DIR__ . '/views/footer.html');

    $html = $header . $content . $footer;
    $response->getBody()->write($html);
    return $response->withHeader('Content-Type', 'text/html');
});

// Rota para testar a conexão com a API
// $app->get('/webhook/test-api', function (Request $request, Response $response, $args) {
//     $apiKey = getenv('OPENROUTER_API_KEY');
//     if (!$apiKey) {
//         $response->getBody()->write(json_encode(["success" => false, "message" => "API key not found."]));
//         return $response->withHeader('Content-Type', 'application/json');
//     }

//     // Teste simples da API
//     // (Aqui você pode adicionar a lógica de requisição à API externa como no código Flask)

//     return $response;
// });

// Rota para processar resultados do quiz
$app->post('/webhook/quiz-results', function (Request $request, Response $response) {
    $data = json_decode($request->getBody(), true);
    
    if (!$data) {
        $response->getBody()->write(json_encode(['error' => 'Dados inválidos']));
        return $response->withStatus(400)->withHeader('Content-Type', 'application/json');
    }

    // Extrair informações do bebê do frontend
    $babyEmail = $_POST['babyEmail'] ?? $data['babyEmail'] ?? null;
    $babyName = $_POST['babyName'] ?? $data['babyName'] ?? null;
    $age = $data['age'] ?? null;
    $isPremature = $data['isPremature'] ?? false;
    $prematureWeeks = $data['prematureWeeks'] ?? null;
    $isPCD = $data['isPCD'] ?? false;
    $pcdDescription = $data['pcdDescription'] ?? null;
    $userQuestion = $data['userQuestion'] ?? null;
    $answers = $data['questionsAndAnswers'] ?? [];

    // Validação básica
    if (!$babyEmail || !$babyName || !$age || empty($answers)) {
        $response->getBody()->write(json_encode(['error' => 'Dados obrigatórios não fornecidos']));
        return $response->withStatus(400)->withHeader('Content-Type', 'application/json');
    }

    // Preparar informações do bebê para a IA
    $babyInfo = [
        'email' => $babyEmail,
        'name' => $babyName,
        'isPremature' => $isPremature,
        'prematureWeeks' => $prematureWeeks,
        'isPCD' => $isPCD,
        'pcdDescription' => $pcdDescription,
        'userQuestion' => $userQuestion
    ];

    // Gerar prompt para IA
    $prompt = format_prompt_for_ai($age, $answers, $babyInfo);
    
    // Obter análise da IA
    $apiKey = '';
    $ai_response = get_ai_analysis($apiKey, $prompt);
    
    // Calcular pontuação
    $positiveCount = 0;
    foreach ($answers as $answer) {
        if (($answer['answer'] ?? '') === 'Sim') {
            $positiveCount++;
        }
    }
    $totalQuestions = count($answers);
    $scorePct = $totalQuestions > 0 ? round(($positiveCount / $totalQuestions) * 100) : 0;
    
    // Definir mensagem de pontuação
    if ($scorePct >= 90) {
        $scoreMsg = "Parabéns! O desenvolvimento do bebê está adequado para a idade.";
    } elseif ($scorePct >= 80) {
        $scoreMsg = "Alerta! Fique de olho no desenvolvimento do bebê e considere consultar um profissional.";
    } else {
        $scoreMsg = "Recomendamos que procure um profissional da área para avaliar o desenvolvimento do bebê.";
    }

    // Salvar no banco de dados usando o novo sistema
    $assessmentId = null;
    try {
        $novoDb = new NovoDBManager();
        
        // Preparar dados para salvamento
        $dadosAvaliacao = [
            'email' => $babyEmail,
            'nome_bebe' => $babyName,
            'idade_meses' => intval($age),
            'eh_prematuro' => $isPremature,
            'semanas_gestacao' => $prematureWeeks,
            'eh_pcd' => $isPCD,
            'descricao_pcd' => $pcdDescription,
            'pergunta_usuario' => $userQuestion,
            'parecer_ia' => $ai_response,
            'respostas' => array_map(function($answer) {
                return [
                    'pergunta' => $answer['question'] ?? '',
                    'resposta' => $answer['answer'] ?? '',
                    'area' => $answer['area'] ?? ''
                ];
            }, $answers)
        ];
        
        $assessmentId = $novoDb->salvarAvaliacaoCompleta($dadosAvaliacao);
        $novoDb->fecharConexao();
        
    } catch (Exception $e) {
        error_log("Erro ao salvar avaliação no banco: " . $e->getMessage());
        // Continua mesmo se não conseguir salvar no banco
    }

    // Resposta final
    $payload = [
        'success'        => true,
        'ai_analysis'    => $ai_response,
        'scorePercentage'=> $scorePct,
        'scoreMessage'   => $scoreMsg,
    ];
    if ($assessmentId) {
        $payload['assessmentId'] = $assessmentId;
        $payload['dbSaved'] = true;
    } else {
        $payload['dbSaved'] = false;
    }

    $response->getBody()->write(json_encode($payload));
    return $response->withHeader('Content-Type', 'application/json');
});


function format_prompt_for_ai($age, $answers, $babyInfo = null)
{
    $ageM = intval($age);
    $ageText = "$ageM " . ($ageM === 1 ? "mês" : "meses");
    $prompt = "Analise as respostas de um questionário sobre o desenvolvimento de um bebê de $ageText. " .
              "Evite introduções ou agradecimentos, mantenha o retorno focado e educativo, sem diagnósticos definitivos.\n\n";

    if ($babyInfo) {
        $prompt .= "Dados do Bebê:\n";
        if (!empty($babyInfo['email'])) {
            $prompt .= "Email: {$babyInfo['email']}\n";
        }
        if (!empty($babyInfo['name'])) {
            $prompt .= "Nome: {$babyInfo['name']}\n";
        }
        if ($babyInfo['isPremature']) {
            $prompt .= "Bebê Prematuro: Sim ({$babyInfo['prematureWeeks']} semanas)\n";
            $prompt .= "Considere idade corrigida.\n";
        }
        if ($babyInfo['isPCD']) {
            $prompt .= "PCD: Sim ({$babyInfo['pcdDescription']})\n";
            $prompt .= "Adapte análise sem alarmismo.\n";
        }
        $prompt .= "\n";
    }

    $prompt .= "Perguntas e Respostas:\n";
    foreach ($answers as $ans) {
        $q = $ans['question'] ?? 'Sem pergunta';
        $a = $ans['answer']   ?? 'Sem resposta';
        $prompt .= "- $q: $a\n";
    }

    // Instruções para formatação estruturada
    $prompt .= "\nIMPORTANTE: Formate sua resposta de forma estruturada usando os seguintes elementos:";
    $prompt .= "\n1. Use '## Título' para títulos principais";
    $prompt .= "\n2. Use '### Subtítulo' para subtítulos";
    $prompt .= "\n3. Use parágrafos separados por linhas em branco";
    $prompt .= "\n4. Use listas com '- ' para pontos importantes";
    $prompt .= "\n5. Use '---' para criar separadores entre seções";
    $prompt .= "\n6. Estruture sua análise com pelo menos estas seções:";
    $prompt .= "\n   - '## Análise Geral'";
    $prompt .= "\n   - '## Pontos Fortes'";
    $prompt .= "\n   - '## Pontos de Atenção' (se houver)";
    $prompt .= "\n   - '## Recomendações'";

    if (!empty($babyInfo['userQuestion'])) {
        $prompt .= "\n\nPergunta do Usuário: {$babyInfo['userQuestion']}\n";
        $prompt .= "\nSe houver pergunta do usuário, adicione uma seção separada no final:";
        $prompt .= "\n   - '---'";
        $prompt .= "\n   - '## Resposta à Pergunta do Usuário'";
        $prompt .= "\n   - Repita a pergunta e forneça uma resposta detalhada";
    }

    $prompt .= "\n\nForneça uma análise educativa, seja objetivo e encorajador. Se identificar sinais de alerta, incentive busca por profissional de saúde.\n";
    return $prompt;
}
/**
 * Envia o prompt para a API Google Gemini e retorna a análise da IA.
 */
function get_ai_analysis($apiKey, $prompt)
{
    $url = "https://generativelanguage.googleapis.com/v1beta/models/gemini-2.0-flash:generateContent?key=AIzaSyA7HAoofPrE5PKr2O9fmOebxlDP20Or0kk";
    $maxRetries = 2; // Número máximo de tentativas
    $attempt = 0;
    $lastError = null;
    
    // Prompt do sistema combinado com o prompt do usuário
    $systemPrompt = "Você é um especialista em desenvolvimento motor infantil, com vasto conhecimento da Escala Motora Infantil de Alberta (AIMS) e da Denver II. Seu papel é:
1. Avaliar as perguntas e respostas do usuário e gerar um parecer preliminar para cada conjunto de respostas, apontando eventuais sinais de alerta.
2. Evitar comentários pessoais ou explicações excessivas; concentre-se em respostas objetivas.
3. Sempre que identificar possíveis atrasos ou dúvidas significativas, incentive a busca por avaliação profissional (fisioterapeuta ou pediatra).
4. Se o usuário fizer uma pergunta direta, inclua-a ao final do laudo, escrevendo:
   Pergunta do Usuário: \"...\"
   Resposta do Especialista: \"...\"
5. Responda tudo em pt-br.

Análise solicitada:
" . $prompt;
    
    while ($attempt <= $maxRetries) {
        $payload = [
            "contents" => [
                [
                    "parts" => [
                        [
                            "text" => $systemPrompt
                        ]
                    ]
                ]
            ],
            "generationConfig" => [
                "temperature" => 0.7,
                "topP" => 0.8,
                "topK" => 40,
                "maxOutputTokens" => 2048
            ],
            "safetySettings" => [
                [
                    "category" => "HARM_CATEGORY_HARASSMENT",
                    "threshold" => "BLOCK_MEDIUM_AND_ABOVE"
                ],
                [
                    "category" => "HARM_CATEGORY_HATE_SPEECH",
                    "threshold" => "BLOCK_MEDIUM_AND_ABOVE"
                ],
                [
                    "category" => "HARM_CATEGORY_SEXUALLY_EXPLICIT",
                    "threshold" => "BLOCK_MEDIUM_AND_ABOVE"
                ],
                [
                    "category" => "HARM_CATEGORY_DANGEROUS_CONTENT",
                    "threshold" => "BLOCK_MEDIUM_AND_ABOVE"
                ]
            ]
        ];
        
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            "Content-Type: application/json"
        ]);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
        curl_setopt($ch, CURLOPT_TIMEOUT, 30); // Timeout de 30 segundos
        
        // Adicionar log para diagnóstico
        error_log("Tentativa #" . ($attempt + 1) . " de envio para API Google Gemini");
        
        $result = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        
        if (!curl_errno($ch) && $http_code === 200) {
            curl_close($ch);
            $response_data = json_decode($result, true);
            
            if (isset($response_data['candidates']) && 
                count($response_data['candidates']) > 0 && 
                isset($response_data['candidates'][0]['content']['parts'][0]['text'])) {
                
                error_log("Análise da IA obtida com sucesso na tentativa #" . ($attempt + 1));
                return $response_data['candidates'][0]['content']['parts'][0]['text'];
            }
            
            // Se não encontrou o texto esperado na resposta
            $lastError = "Resposta da API não contém o texto esperado";
            error_log("Estrutura de resposta inesperada: " . json_encode($response_data));
        } else {
            // Registrar o erro para diagnóstico
            $lastError = curl_errno($ch) ? "Erro cURL: " . curl_error($ch) : "HTTP status: $http_code";
            
            // Se houve erro HTTP, tentar decodificar a resposta para mais detalhes
            if ($result && $http_code !== 200) {
                $error_data = json_decode($result, true);
                if (isset($error_data['error']['message'])) {
                    $lastError .= " - " . $error_data['error']['message'];
                }
            }
            
            error_log("Falha na tentativa #" . ($attempt + 1) . ": " . $lastError);
            curl_close($ch);
        }
        
        $attempt++;
        
        // Se ainda temos tentativas, aguardar antes da próxima
        if ($attempt <= $maxRetries) {
            // Backoff exponencial: 1s, 2s
            $sleepTime = pow(2, $attempt - 1);
            error_log("Aguardando {$sleepTime}s antes da próxima tentativa");
            sleep($sleepTime);
        }
    }
    
    // Todas as tentativas falharam, retornar resposta de fallback
    error_log("Todas as tentativas falharam. Último erro: " . $lastError);
    
    // Resposta de fallback baseada nas perguntas e respostas
    return "Não foi possível obter a análise detalhada da IA neste momento. Com base nas respostas fornecidas, recomendamos que você continue observando o desenvolvimento do bebê e, se tiver preocupações, consulte um profissional de saúde especializado em desenvolvimento infantil.";
}

// Rodando o servidor
$app->run();
