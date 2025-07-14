# Passo à Frente - Plataforma de Acompanhamento do Desenvolvimento Motor Infantil

## 📋 Sobre o Projeto

O **Passo à Frente** é uma plataforma web dedicada ao acompanhamento e avaliação do desenvolvimento motor infantil. A aplicação utiliza escalas científicas validadas (AIMS - Alberta Infant Motor Scale e Denver II) para fornecer análises personalizadas sobre o desenvolvimento de bebês de 0 a 18 meses.

## 🎯 Funcionalidades Principais

### 🔍 Quiz de Avaliação Interativo
- Questionários específicos por faixa etária (0-18 meses)
- Perguntas baseadas nas escalas AIMS e Denver II
- Análise automatizada com Inteligência Artificial (Google Gemini)
- Geração de relatórios personalizados em PDF

### 📊 Sistema de Análise
- **Análise por IA** com recomendações específicas
- **Identificação de sinais de alerta** para desenvolvimento atípico
- **Suporte para bebês prematuros** com idade corrigida
- **Adaptações para PCD** (Pessoas com Deficiência)

### 🔎 Busca de Avaliações
- Histórico completo de avaliações por email
- Visualização de relatórios anteriores
- Acompanhamento da evolução do desenvolvimento

### 💡 Recursos Educativos
- **Dicas para tutores** sobre estimulação motora
- **Informações científicas** sobre desenvolvimento infantil
- **Orientações práticas** para cada fase do desenvolvimento

## 🛠️ Tecnologias Utilizadas

### Backend
- **PHP 8+** - Linguagem principal
- **Slim Framework 4** - Framework web minimalista
- **MySQL** - Banco de dados relacional
- **Composer** - Gerenciador de dependências PHP
- **mPDF** - Geração de relatórios PDF
- **Guzzle HTTP** - Cliente HTTP para APIs

### Frontend
- **HTML5/CSS3** - Estrutura e estilização
- **JavaScript (Vanilla)** - Interatividade
- **Responsive Design** - Interface adaptável

### Integrações
- **Google Gemini AI** - Análise inteligente das avaliações
- **API REST** - Comunicação entre frontend e backend
- **Google Places API** - Comunicação entre frontend e backend

### Dependências Principais
```json
{
  "slim/slim": "^4.14",
  "mpdf/mpdf": "^8.2",
  "guzzlehttp/guzzle": "^7.9",
  "vlucas/phpdotenv": "^5.5"
}
```

## 📁 Estrutura do Projeto

```
Passoafrente/
├── api/                    # Endpoints da API
│   ├── buscar_avaliacoes.php
│   ├── generate_pdf.php
│   └── get_assessments.php
├── controllers/            # Controladores
│   └── webhook.php
├── database/              # Configuração do banco
│   ├── database_schemaatt.sql
│   └── novo_db_manager.php
├── lib/                   # Assets estáticos
│   ├── css/
│   ├── js/
│   └── img/
├── views/                 # Templates HTML
│   ├── index.html
│   ├── quiz.html
│   ├── busca.html
│   ├── dicas.html
│   └── sobre.html
├── vendor/                # Dependências do Composer
├── index.php             # Arquivo principal (rotas)
├── composer.json         # Configuração PHP
└── .env                  # Variáveis de ambiente
```

## 🚀 Como Executar o Projeto

### Pré-requisitos
- PHP 8.0 ou superior
- MySQL 5.7 ou superior
- Composer
- Servidor web (Apache/Nginx) ou PHP built-in server

## 📖 Como Usar

### Para Pais/Cuidadores

1. **Acesse a página inicial** e clique em "Iniciar Avaliação"
2. **Preencha os dados do bebê**:
   - Nome e email
   - Idade em meses
   - Informações sobre prematuridade (se aplicável)
   - Informações sobre PCD (se aplicável)
3. **Responda ao questionário** específico para a idade
4. **Receba a análise** com:
   - Pontuação percentual
   - Análise detalhada da IA
   - Recomendações personalizadas
   - Relatório em PDF

### Para Profissionais de Saúde

- **Utilize a busca** para acompanhar o histórico de desenvolvimento
- **Analise os relatórios** gerados para orientar intervenções
- **Use as escalas validadas** como base para avaliações clínicas

## 🔬 Base Científica

### Escalas Utilizadas

**AIMS (Alberta Infant Motor Scale)**
- Avalia desenvolvimento motor de 0-18 meses
- Observa posturas em 4 posições: prono, supino, sentado, em pé
- Validada cientificamente para população brasileira

**Denver II (TTDD-II)**
- Avalia 4 áreas: pessoal-social, motor fino-adaptativo, linguagem, motor grosso
- Ferramenta de triagem para identificação precoce de atrasos
- Amplamente utilizada em pediatria

### Referências Científicas
- Piper MC, Darrah J. Motor Assessment of the Developing Infant. Philadelphia: W.B. Saunders Company; 1994.
- Valentini NC, Saccani R. Escala Motora Infantil de Alberta: validação para uma população gaúcha. Rev Paul Pediatr. 2011;29(2):231-8.
- Frankenburg WK, Dodds J, Archer P, et al. The Denver II: A major revision and restandardization of the Denver Developmental Screening Test. Pediatrics. 1992;89(1):91-7.

## ⚠️ Importante

- **Esta plataforma é uma ferramenta de triagem**, não substitui avaliação profissional
- **Sempre consulte um pediatra ou fisioterapeuta** em caso de preocupações
- **Os resultados são orientativos** e baseados em escalas científicas validadas
- **Mantenha os dados atualizados** para análises mais precisas

## 🤝 Contribuição

Este projeto foi desenvolvido como parte de um trabalho acadêmico focado no desenvolvimento motor infantil. Contribuições são bem-vindas para melhorar a plataforma e beneficiar mais famílias.