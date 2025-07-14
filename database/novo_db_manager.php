<?php
/**
 * Novo Gerenciador de Banco de Dados para o Sistema de Avaliação de Desenvolvimento Infantil
 * 
 * Este arquivo contém funções para conectar ao banco de dados MySQL e realizar operações CRUD
 * baseado no novo schema database_schemaatt.sql
 */

class NovoDBManager {
    private $conn;
    private $host = 'localhost';
    private $dbname = '';
    private $username = '';
    private $password = '';
    
    /**
     * Construtor - Estabelece conexão com o banco de dados
     */
    public function __construct() {
        try {
            $this->conn = new PDO(
                "mysql:host={$this->host};dbname={$this->dbname};charset=utf8mb4",
                $this->username,
                $this->password,
                [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
            );
        } catch (PDOException $e) {
            error_log("Erro de conexão com o banco de dados: " . $e->getMessage());
            throw new Exception("Não foi possível conectar ao banco de dados. Por favor, tente novamente mais tarde.");
        }
    }
    
    /**
     * Salva uma nova avaliação completa no banco de dados
     * 
     * @param array $dadosAvaliacao Dados completos da avaliação
     * @return int|bool ID da avaliação criada ou false em caso de erro
     */
    public function salvarAvaliacaoCompleta($dadosAvaliacao) {
        try {
            $this->conn->beginTransaction();
            
            // 1. Criar/obter usuário
            $idUsuario = $this->criarOuObterUsuario($dadosAvaliacao['email']);
            
            // 2. Criar/obter criança
            $idCrianca = $this->criarOuObterCrianca($idUsuario, $dadosAvaliacao);
            
            // 3. Obter/criar formulário para a idade
            $idFormulario = $this->obterOuCriarFormulario($dadosAvaliacao['idade_meses']);
            
            // 4. Criar avaliação
            $idAvaliacao = $this->criarAvaliacao($idCrianca, $idFormulario, $dadosAvaliacao);
            
            // 5. Salvar respostas
            $this->salvarRespostas($idAvaliacao, $idFormulario, $dadosAvaliacao['respostas']);
            
            // 6. Gerar PDF automaticamente
            $this->gerarEArmazenarPdf($idAvaliacao);
            
            $this->conn->commit();
            return $idAvaliacao;
            
        } catch (Exception $e) {
            $this->conn->rollBack();
            error_log("Erro ao salvar avaliação: " . $e->getMessage());
            throw new Exception("Erro ao salvar avaliação: " . $e->getMessage());
        }
    }
    
    /**
     * Cria ou obtém um usuário pelo email
     */
    private function criarOuObterUsuario($email) {
        // Verificar se usuário já existe
        $stmt = $this->conn->prepare("SELECT id_usuario FROM usuarios WHERE email = ?");
        $stmt->execute([$email]);
        $usuario = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($usuario) {
            return $usuario['id_usuario'];
        }
        
        // Criar novo usuário
        $stmt = $this->conn->prepare("INSERT INTO usuarios (email) VALUES (?)");
        $stmt->execute([$email]);
        return $this->conn->lastInsertId();
    }
    
    /**
     * Cria ou obtém uma criança
     */
    private function criarOuObterCrianca($idUsuario, $dados) {
        // Calcular data de nascimento baseada na idade em meses
        $idadeMeses = $dados['idade_meses'];
        $dataNascimento = date('Y-m-d', strtotime("-{$idadeMeses} months"));
        
        // Verificar se criança já existe (mesmo nome e usuário)
        $stmt = $this->conn->prepare(
            "SELECT id_crianca FROM criancas WHERE id_usuario = ? AND nome = ?"
        );
        $stmt->execute([$idUsuario, $dados['nome_bebe']]);
        $crianca = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($crianca) {
            return $crianca['id_crianca'];
        }
        
        // Criar nova criança
        $stmt = $this->conn->prepare(
            "INSERT INTO criancas (id_usuario, nome, data_nascimento, prematuro, semanas_gestacao, pcd, descricao_pcd) 
             VALUES (?, ?, ?, ?, ?, ?, ?)"
        );
        
        $stmt->execute([
            $idUsuario,
            $dados['nome_bebe'],
            $dataNascimento,
            $dados['eh_prematuro'] ?? false,
            $dados['semanas_gestacao'] ?? null,
            $dados['eh_pcd'] ?? false,
            $dados['descricao_pcd'] ?? null
        ]);
        
        return $this->conn->lastInsertId();
    }
    
    /**
     * Obtém ou cria um formulário para a idade específica
     */
    private function obterOuCriarFormulario($idadeMeses) {
        // Verificar se formulário já existe para esta idade
        $stmt = $this->conn->prepare("SELECT id_formulario FROM formularios WHERE idade_meses = ?");
        $stmt->execute([$idadeMeses]);
        $formulario = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($formulario) {
            return $formulario['id_formulario'];
        }
        
        // Criar novo formulário
        $stmt = $this->conn->prepare(
            "INSERT INTO formularios (idade_meses, titulo, descricao, ativo) 
             VALUES (?, ?, ?, ?)"
        );
        
        $titulo = "Avaliação para {$idadeMeses} meses";
        $descricao = "Formulário de avaliação de desenvolvimento para bebês de {$idadeMeses} meses";
        
        $stmt->execute([$idadeMeses, $titulo, $descricao, true]);
        return $this->conn->lastInsertId();
    }
    
    /**
     * Cria uma nova avaliação
     */
    private function criarAvaliacao($idCrianca, $idFormulario, $dados) {
        $stmt = $this->conn->prepare(
            "INSERT INTO avaliacoes (id_crianca, id_formulario, idade_meses, parecer_ia, pdf_url) 
             VALUES (?, ?, ?, ?, ?)"
        );
        
        $stmt->execute([
            $idCrianca,
            $idFormulario,
            $dados['idade_meses'],
            $dados['parecer_ia'] ?? null,
            $dados['pdf_url'] ?? null
        ]);
        
        return $this->conn->lastInsertId();
    }
    
    /**
     * Salva as respostas da avaliação
     */
    private function salvarRespostas($idAvaliacao, $idFormulario, $respostas) {
        foreach ($respostas as $resposta) {
            // Obter ou criar pergunta
            $idPergunta = $this->obterOuCriarPergunta($idFormulario, $resposta['pergunta']);
            
            // Salvar resposta
            $stmt = $this->conn->prepare(
                "INSERT INTO respostas (id_avaliacao, id_pergunta, resposta_opcao) 
                 VALUES (?, ?, ?)"
            );
            
            $stmt->execute([
                $idAvaliacao,
                $idPergunta,
                $resposta['resposta']
            ]);
        }
    }
    
    /**
     * Obtém ou cria uma pergunta
     */
    private function obterOuCriarPergunta($idFormulario, $textoPergunta) {
        // Verificar se pergunta já existe
        $stmt = $this->conn->prepare(
            "SELECT id_pergunta FROM perguntas WHERE id_formulario = ? AND texto_pergunta = ?"
        );
        $stmt->execute([$idFormulario, $textoPergunta]);
        $pergunta = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($pergunta) {
            return $pergunta['id_pergunta'];
        }
        
        // Criar nova pergunta
        $stmt = $this->conn->prepare(
            "INSERT INTO perguntas (id_formulario, texto_pergunta, ordem, tipo_resposta, opcoes_resposta) 
             VALUES (?, ?, ?, ?, ?)"
        );
        
        // Obter próxima ordem
        $stmtOrdem = $this->conn->prepare(
            "SELECT COALESCE(MAX(ordem), 0) + 1 as proxima_ordem FROM perguntas WHERE id_formulario = ?"
        );
        $stmtOrdem->execute([$idFormulario]);
        $ordem = $stmtOrdem->fetch(PDO::FETCH_ASSOC)['proxima_ordem'];
        
        $opcoesResposta = json_encode(['Sim', 'Não']);
        
        $stmt->execute([
            $idFormulario,
            $textoPergunta,
            $ordem,
            'opcao_multipla',
            $opcoesResposta
        ]);
        
        return $this->conn->lastInsertId();
    }
    
    /**
     * Busca avaliações por email do usuário
     */
    public function buscarAvaliacoesPorEmail($email) {
        $stmt = $this->conn->prepare(
            "SELECT a.*, c.nome as nome_crianca, u.email 
             FROM avaliacoes a 
             JOIN criancas c ON a.id_crianca = c.id_crianca 
             JOIN usuarios u ON c.id_usuario = u.id_usuario 
             WHERE u.email = ? 
             ORDER BY a.data_avaliacao DESC"
        );
        
        $stmt->execute([$email]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * Busca todas as avaliações do banco de dados
     */
    public function buscarTodasAvaliacoes() {
        $stmt = $this->conn->prepare(
            "SELECT a.*, c.nome as nome_crianca, u.email 
             FROM avaliacoes a 
             JOIN criancas c ON a.id_crianca = c.id_crianca 
             JOIN usuarios u ON c.id_usuario = u.id_usuario 
             ORDER BY a.data_avaliacao DESC"
        );
        
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * Busca uma avaliação específica por ID
     */
    public function buscarAvaliacaoPorId($idAvaliacao) {
        $stmt = $this->conn->prepare(
            "SELECT a.*, c.nome as nome_crianca, c.prematuro, c.semanas_gestacao, 
                    c.pcd, c.descricao_pcd, u.email 
             FROM avaliacoes a 
             JOIN criancas c ON a.id_crianca = c.id_crianca 
             JOIN usuarios u ON c.id_usuario = u.id_usuario 
             WHERE a.id_avaliacao = ?"
        );
        
        $stmt->execute([$idAvaliacao]);
        $avaliacao = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($avaliacao) {
            // Buscar respostas
            $stmtRespostas = $this->conn->prepare(
                "SELECT p.texto_pergunta, r.resposta_opcao 
                 FROM respostas r 
                 JOIN perguntas p ON r.id_pergunta = p.id_pergunta 
                 WHERE r.id_avaliacao = ? 
                 ORDER BY p.ordem"
            );
            
            $stmtRespostas->execute([$idAvaliacao]);
            $avaliacao['respostas'] = $stmtRespostas->fetchAll(PDO::FETCH_ASSOC);
        }
        
        return $avaliacao;
    }
    
    /**
     * Atualiza o URL do PDF de uma avaliação
     */
    public function atualizarUrlPdf($idAvaliacao, $urlPdf) {
        $stmt = $this->conn->prepare("UPDATE avaliacoes SET pdf_url = ? WHERE id_avaliacao = ?");
        return $stmt->execute([$urlPdf, $idAvaliacao]);
    }
    
    /**
     * Gera e armazena o PDF de uma avaliação automaticamente
     */
    private function gerarEArmazenarPdf($idAvaliacao) {
        try {
            // Incluir o gerador de PDF
            require_once __DIR__ . '/../api/generate_pdf_from_db.php';
            
            // Gerar PDF e salvar no servidor
            $pdfPath = generatePdfFromDatabase($idAvaliacao, true);
            
            if ($pdfPath !== false) {
                // Atualizar URL do PDF no banco de dados
                $this->atualizarUrlPdf($idAvaliacao, $pdfPath);
                error_log("PDF gerado e salvo automaticamente para avaliação ID: " . $idAvaliacao . " - Caminho: " . $pdfPath);
            } else {
                error_log("Erro ao gerar PDF automaticamente para avaliação ID: " . $idAvaliacao);
            }
            
        } catch (Exception $e) {
            error_log("Erro ao gerar PDF automaticamente: " . $e->getMessage());
            // Não interrompe o processo de salvamento da avaliação
        }
    }
    
    /**
     * Fecha a conexão com o banco de dados
     */
    public function fecharConexao() {
        $this->conn = null;
    }
}