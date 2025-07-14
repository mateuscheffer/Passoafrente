<?php
/**
 * Gerenciador de Banco de Dados para o Sistema de Avaliação de Desenvolvimento Motor Infantil
 * 
 * Este arquivo contém funções para conectar ao banco de dados MySQL e realizar operações CRUD
 * nas tabelas do sistema. Ele permite salvar resultados de avaliações e recuperar históricos
 * de avaliações anteriores por email do tutor.
 */

class DBManager {
    private $conn;
    private $host = 'localhost'; // Altere conforme necessário para o ambiente de produção
    private $dbname = '';
    private $username = '';
    private $password = ''; // Defina a senha no ambiente de produção
    
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
            // Log do erro e retorno de mensagem amigável
            error_log("Erro de conexão com o banco de dados: " . $e->getMessage());
            throw new Exception("Não foi possível conectar ao banco de dados. Por favor, tente novamente mais tarde.");
        }
    }
    
    /**
     * Salva os resultados de uma avaliação no banco de dados
     * 
     * @param array $data Dados da avaliação (age, answers, babyInfo, ai_analysis)
     * @return int|bool ID da avaliação criada ou false em caso de erro
     */
    public function saveAssessment($data) {
        try {
            $this->conn->beginTransaction();
            
            // 1. Verifica/Cria o tutor
            $tutorId = $this->getOrCreateTutor($data['babyInfo']);
            
            // 2. Verifica/Cria o bebê
            $babyId = $this->getOrCreateBaby($data['babyInfo'], $tutorId);
            
            // 3. Cria a avaliação
            $stmt = $this->conn->prepare(
                "INSERT INTO assessments (baby_id, age_months, ai_analysis) 
                 VALUES (:baby_id, :age_months, :ai_analysis)"
            );
            
            $stmt->execute([
                ':baby_id' => $babyId,
                ':age_months' => intval($data['age']),
                ':ai_analysis' => $data['ai_analysis'] ?? null
            ]);
            
            $assessmentId = $this->conn->lastInsertId();
            
            // 4. Salva as respostas
            foreach ($data['answers'] as $answer) {
                // Busca o ID da pergunta pelo texto (ou cria se não existir)
                $questionId = $this->getOrCreateQuestion($answer);
                
                $stmt = $this->conn->prepare(
                    "INSERT INTO answers (assessment_id, question_id, answer_value, notes) 
                     VALUES (:assessment_id, :question_id, :answer_value, :notes)"
                );
                
                $stmt->execute([
                    ':assessment_id' => $assessmentId,
                    ':question_id' => $questionId,
                    ':answer_value' => $answer['answer'],
                    ':notes' => $answer['notes'] ?? null
                ]);
            }
            
            $this->conn->commit();
            return $assessmentId;
            
        } catch (Exception $e) {
            $this->conn->rollBack();
            error_log("Erro ao salvar avaliação: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Busca ou cria um registro de tutor
     * 
     * @param array $babyInfo Informações do bebê incluindo dados do tutor
     * @return int ID do tutor
     */
    private function getOrCreateTutor($babyInfo) {
        if (empty($babyInfo['email'])) {
            throw new Exception("Email do tutor é obrigatório");
        }
        
        // Verifica se o tutor já existe
        $stmt = $this->conn->prepare("SELECT id FROM tutors WHERE email = :email");
        $stmt->execute([':email' => $babyInfo['email']]);
        $tutor = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($tutor) {
            return $tutor['id'];
        }
        
        // Cria novo tutor
        $stmt = $this->conn->prepare(
            "INSERT INTO tutors (email, name, phone) 
             VALUES (:email, :name, :phone)"
        );
        
        $stmt->execute([
            ':email' => $babyInfo['email'],
            ':name' => $babyInfo['tutor_name'] ?? 'Não informado',
            ':phone' => $babyInfo['phone'] ?? null
        ]);
        
        return $this->conn->lastInsertId();
    }
    
    /**
     * Busca ou cria um registro de bebê
     * 
     * @param array $babyInfo Informações do bebê
     * @param int $tutorId ID do tutor
     * @return int ID do bebê
     */
    private function getOrCreateBaby($babyInfo, $tutorId) {
        if (empty($babyInfo['name'])) {
            throw new Exception("Nome do bebê é obrigatório");
        }
        
        // Verifica se o bebê já existe para este tutor
        $stmt = $this->conn->prepare(
            "SELECT id FROM babies 
             WHERE tutor_id = :tutor_id AND name = :name"
        );
        
        $stmt->execute([
            ':tutor_id' => $tutorId,
            ':name' => $babyInfo['name']
        ]);
        
        $baby = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($baby) {
            return $baby['id'];
        }
        
        // Calcula a data de nascimento baseada na idade em meses
        $birthDate = null;
        if (isset($babyInfo['birth_date'])) {
            $birthDate = $babyInfo['birth_date'];
        } else if (isset($babyInfo['age_months'])) {
            $birthDate = date('Y-m-d', strtotime("-{$babyInfo['age_months']} months"));
        } else {
            $birthDate = date('Y-m-d'); // Data atual como fallback
        }
        
        // Cria novo bebê
        $stmt = $this->conn->prepare(
            "INSERT INTO babies (tutor_id, name, birth_date, is_premature, premature_weeks, is_pcd, pcd_description) 
             VALUES (:tutor_id, :name, :birth_date, :is_premature, :premature_weeks, :is_pcd, :pcd_description)"
        );
        
        $stmt->execute([
            ':tutor_id' => $tutorId,
            ':name' => $babyInfo['name'],
            ':birth_date' => $birthDate,
            ':is_premature' => isset($babyInfo['isPremature']) && $babyInfo['isPremature'] ? 1 : 0,
            ':premature_weeks' => $babyInfo['prematureWeeks'] ?? null,
            ':is_pcd' => isset($babyInfo['isPCD']) && $babyInfo['isPCD'] ? 1 : 0,
            ':pcd_description' => $babyInfo['pcdDescription'] ?? null
        ]);
        
        return $this->conn->lastInsertId();
    }
    
    /**
     * Busca ou cria uma pergunta baseada no texto e área
     * 
     * @param array $answer Resposta contendo a pergunta e área
     * @return int ID da pergunta
     */
    private function getOrCreateQuestion($answer) {
        if (empty($answer['question']) || empty($answer['area'])) {
            throw new Exception("Pergunta e área são obrigatórias");
        }
        
        // Busca a área pelo nome
        $stmt = $this->conn->prepare("SELECT id FROM development_areas WHERE name = :name");
        $stmt->execute([':name' => $answer['area']]);
        $area = $stmt->fetch(PDO::FETCH_ASSOC);
        
        $areaId = null;
        if ($area) {
            $areaId = $area['id'];
        } else {
            // Cria nova área
            $stmt = $this->conn->prepare(
                "INSERT INTO development_areas (name) VALUES (:name)"
            );
            $stmt->execute([':name' => $answer['area']]);
            $areaId = $this->conn->lastInsertId();
        }
        
        // Busca a pergunta pelo texto
        $stmt = $this->conn->prepare(
            "SELECT id FROM questions 
             WHERE question_text = :question_text AND area_id = :area_id"
        );
        
        $stmt->execute([
            ':question_text' => $answer['question'],
            ':area_id' => $areaId
        ]);
        
        $question = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($question) {
            return $question['id'];
        }
        
        // Cria nova pergunta
        $stmt = $this->conn->prepare(
            "INSERT INTO questions (area_id, question_text, min_age_months, max_age_months) 
             VALUES (:area_id, :question_text, :min_age_months, :max_age_months)"
        );
        
        $stmt->execute([
            ':area_id' => $areaId,
            ':question_text' => $answer['question'],
            ':min_age_months' => $answer['min_age_months'] ?? 0,
            ':max_age_months' => $answer['max_age_months'] ?? 36
        ]);
        
        return $this->conn->lastInsertId();
    }
    
    /**
     * Busca avaliações por email do tutor
     * 
     * @param string $email Email do tutor
     * @return array Lista de avaliações
     */
    public function getAssessmentsByEmail($email) {
        try {
            $query = "
                SELECT 
                    a.id as assessment_id,
                    a.assessment_date,
                    a.age_months,
                    a.ai_analysis,
                    b.name as baby_name,
                    b.birth_date,
                    b.is_premature,
                    b.premature_weeks,
                    b.is_pcd,
                    b.pcd_description,
                    t.name as tutor_name,
                    t.email as tutor_email
                FROM 
                    assessments a
                JOIN 
                    babies b ON a.baby_id = b.id
                JOIN 
                    tutors t ON b.tutor_id = t.id
                WHERE 
                    t.email = :email
                ORDER BY 
                    a.assessment_date DESC
            ";
            
            $stmt = $this->conn->prepare($query);
            $stmt->execute([':email' => $email]);
            
            $assessments = [];
            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                $assessmentId = $row['assessment_id'];
                
                // Busca as respostas para esta avaliação
                $answersQuery = "
                    SELECT 
                        ans.answer_value,
                        ans.notes,
                        q.question_text,
                        da.name as area_name
                    FROM 
                        answers ans
                    JOIN 
                        questions q ON ans.question_id = q.id
                    JOIN 
                        development_areas da ON q.area_id = da.id
                    WHERE 
                        ans.assessment_id = :assessment_id
                ";
                
                $answersStmt = $this->conn->prepare($answersQuery);
                $answersStmt->execute([':assessment_id' => $assessmentId]);
                
                $answers = [];
                while ($answerRow = $answersStmt->fetch(PDO::FETCH_ASSOC)) {
                    $answers[] = [
                        'question' => $answerRow['question_text'],
                        'answer' => $answerRow['answer_value'],
                        'area' => $answerRow['area_name'],
                        'notes' => $answerRow['notes']
                    ];
                }
                
                // Adiciona esta avaliação com suas respostas ao resultado
                $assessments[] = [
                    'id' => $assessmentId,
                    'date' => $row['assessment_date'],
                    'age_months' => $row['age_months'],
                    'ai_analysis' => $row['ai_analysis'],
                    'baby' => [
                        'name' => $row['baby_name'],
                        'birth_date' => $row['birth_date'],
                        'is_premature' => (bool)$row['is_premature'],
                        'premature_weeks' => $row['premature_weeks'],
                        'is_pcd' => (bool)$row['is_pcd'],
                        'pcd_description' => $row['pcd_description']
                    ],
                    'tutor' => [
                        'name' => $row['tutor_name'],
                        'email' => $row['tutor_email']
                    ],
                    'answers' => $answers
                ];
            }
            
            return $assessments;
            
        } catch (Exception $e) {
            error_log("Erro ao buscar avaliações: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Busca uma avaliação específica pelo ID
     * 
     * @param int $assessmentId ID da avaliação
     * @return array|null Dados da avaliação ou null se não encontrada
     */
    public function getAssessmentById($assessmentId) {
        try {
            $query = "
                SELECT 
                    a.id as assessment_id,
                    a.assessment_date,
                    a.age_months,
                    a.ai_analysis,
                    b.name as baby_name,
                    b.birth_date,
                    b.is_premature,
                    b.premature_weeks,
                    b.is_pcd,
                    b.pcd_description,
                    t.name as tutor_name,
                    t.email as tutor_email
                FROM 
                    assessments a
                JOIN 
                    babies b ON a.baby_id = b.id
                JOIN 
                    tutors t ON b.tutor_id = t.id
                WHERE 
                    a.id = :assessment_id
            ";
            
            $stmt = $this->conn->prepare($query);
            $stmt->execute([':assessment_id' => $assessmentId]);
            
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$row) {
                return null;
            }
            
            // Busca as respostas para esta avaliação
            $answersQuery = "
                SELECT 
                    ans.answer_value,
                    ans.notes,
                    q.question_text,
                    da.name as area_name
                FROM 
                    answers ans
                JOIN 
                    questions q ON ans.question_id = q.id
                JOIN 
                    development_areas da ON q.area_id = da.id
                WHERE 
                    ans.assessment_id = :assessment_id
            ";
            
            $answersStmt = $this->conn->prepare($answersQuery);
            $answersStmt->execute([':assessment_id' => $assessmentId]);
            
            $answers = [];
            while ($answerRow = $answersStmt->fetch(PDO::FETCH_ASSOC)) {
                $answers[] = [
                    'question' => $answerRow['question_text'],
                    'answer' => $answerRow['answer_value'],
                    'area' => $answerRow['area_name'],
                    'notes' => $answerRow['notes']
                ];
            }
            
            // Retorna a avaliação com suas respostas
            return [
                'id' => $row['assessment_id'],
                'date' => $row['assessment_date'],
                'age_months' => $row['age_months'],
                'ai_analysis' => $row['ai_analysis'],
                'baby' => [
                    'name' => $row['baby_name'],
                    'birth_date' => $row['birth_date'],
                    'is_premature' => (bool)$row['is_premature'],
                    'premature_weeks' => $row['premature_weeks'],
                    'is_pcd' => (bool)$row['is_pcd'],
                    'pcd_description' => $row['pcd_description']
                ],
                'tutor' => [
                    'name' => $row['tutor_name'],
                    'email' => $row['tutor_email']
                ],
                'answers' => $answers
            ];
            
        } catch (Exception $e) {
            error_log("Erro ao buscar avaliação: " . $e->getMessage());
            return null;
        }
    }
}