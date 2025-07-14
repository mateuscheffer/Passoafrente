-- Esquema de Banco de Dados Simplificado (MySQL compatível)

-- Tabela de Usuários
CREATE TABLE usuarios (
    id_usuario INT AUTO_INCREMENT PRIMARY KEY,
    email VARCHAR(255) NOT NULL UNIQUE
);

-- Tabela de Crianças
CREATE TABLE criancas (
    id_crianca INT AUTO_INCREMENT PRIMARY KEY,
    id_usuario INT NOT NULL,
    nome VARCHAR(255) NOT NULL,
    data_nascimento DATE NOT NULL,
    prematuro BOOLEAN DEFAULT FALSE,
    semanas_gestacao INT,
    pcd BOOLEAN DEFAULT FALSE,
    descricao_pcd TEXT,
    FOREIGN KEY (id_usuario) REFERENCES usuarios(id_usuario) ON DELETE CASCADE
);

-- Tabela de Formulários
CREATE TABLE formularios (
    id_formulario INT AUTO_INCREMENT PRIMARY KEY,
    idade_meses INT NOT NULL UNIQUE,
    titulo VARCHAR(255) NOT NULL,
    descricao TEXT,
    ativo BOOLEAN DEFAULT TRUE,
    data_criacao TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Tabela de Perguntas
CREATE TABLE perguntas (
    id_pergunta INT AUTO_INCREMENT PRIMARY KEY,
    id_formulario INT NOT NULL,
    texto_pergunta TEXT NOT NULL,
    ordem INT NOT NULL,
    tipo_resposta VARCHAR(50) NOT NULL,
    opcoes_resposta JSON,
    obrigatoria BOOLEAN DEFAULT TRUE,
    FOREIGN KEY (id_formulario) REFERENCES formularios(id_formulario) ON DELETE CASCADE
);
ALTER TABLE perguntas ADD COLUMN identificador_string VARCHAR(20) NULL;
-- Adicione um índice para otimizar a busca
CREATE INDEX idx_perguntas_identificador ON perguntas(id_formulario, identificador_string);
--

-- Tabela de Avaliações
CREATE TABLE avaliacoes (
    id_avaliacao INT AUTO_INCREMENT PRIMARY KEY,
    id_crianca INT NOT NULL,
    id_formulario INT NOT NULL,
    data_avaliacao TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    idade_meses INT NOT NULL,
    parecer_ia TEXT,
    pdf_url VARCHAR(255),
    FOREIGN KEY (id_crianca) REFERENCES criancas(id_crianca) ON DELETE CASCADE,
    FOREIGN KEY (id_formulario) REFERENCES formularios(id_formulario)
);

-- Tabela de Respostas
CREATE TABLE respostas (
    id_resposta INT AUTO_INCREMENT PRIMARY KEY,
    id_avaliacao INT NOT NULL,
    id_pergunta INT NOT NULL,
    resposta_texto TEXT,
    resposta_opcao VARCHAR(255),
    FOREIGN KEY (id_avaliacao) REFERENCES avaliacoes(id_avaliacao) ON DELETE CASCADE,
    FOREIGN KEY (id_pergunta) REFERENCES perguntas(id_pergunta)
);

-- Índices para otimização
CREATE INDEX idx_criancas_usuario ON criancas(id_usuario);
CREATE INDEX idx_perguntas_formulario ON perguntas(id_formulario);
CREATE INDEX idx_avaliacoes_crianca ON avaliacoes(id_crianca);
CREATE INDEX idx_avaliacoes_data ON avaliacoes(data_avaliacao);
CREATE INDEX idx_respostas_avaliacao ON respostas(id_avaliacao);
CREATE INDEX idx_respostas_pergunta ON respostas(id_pergunta);

-- View para análises estatísticas
DROP VIEW IF EXISTS vw_estatisticas_respostas;

CREATE VIEW vw_estatisticas_respostas AS
SELECT 
    p.id_pergunta,
    p.texto_pergunta,
    f.idade_meses AS idade_meses_formulario,
    a.idade_meses AS idade_meses_avaliacao,
    c.prematuro,
    c.semanas_gestacao,
    c.pcd,
    r.resposta_texto,
    r.resposta_opcao,
    COUNT(*) as total_respostas,
    a.data_avaliacao
FROM 
    respostas r
JOIN 
    perguntas p ON r.id_pergunta = p.id_pergunta
JOIN 
    avaliacoes a ON r.id_avaliacao = a.id_avaliacao
JOIN 
    formularios f ON p.id_formulario = f.id_formulario
JOIN
    criancas c ON a.id_crianca = c.id_crianca
GROUP BY 
    p.id_pergunta, p.texto_pergunta, f.idade_meses, a.idade_meses,
    c.prematuro, c.semanas_gestacao, c.pcd, r.resposta_texto, r.resposta_opcao,
    a.data_avaliacao;
