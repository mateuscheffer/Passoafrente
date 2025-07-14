function processAnswers() {
  const results = document.getElementById("results");
  const resultContent = document.getElementById("resultContent");

  // Obtém informações do bebê
  const ageSelect = document.getElementById("babyAge");
  const babyEmail = document.getElementById("babyEmail").value;
  const babyName = document.getElementById("babyName").value;
  const isPremature = document.getElementById("isPremature").checked;
  const prematureWeeks = isPremature ? document.getElementById("prematureWeeks").value : null;
  const isPCD = document.getElementById("isPCD").checked;
  const pcdDescription = isPCD ? document.getElementById("pcdDescription").value : null;
  const userQuestion = document.getElementById("userQuestion") ? document.getElementById("userQuestion").value : "";

  // Obtém a idade calculada (agora armazenada em campo oculto)
  const selectedAge = ageSelect.value;
  if (!selectedAge) {
    alert("Por favor, calcule a idade do bebê primeiro.");
    return;
  }
  // Valida informações obrigatórias do bebê
  if (!babyEmail || !babyName) {
    alert("Por favor, preencha o email e o nome do bebê.");
    return;
  }

  // Valida formato de email
  const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
  if (!emailRegex.test(babyEmail)) {
    alert("Por favor, insira um email válido.");
    return;
  }

  // Valida campos condicionais
  if (isPremature && !prematureWeeks) {
    alert("Por favor, informe as semanas de gestação do bebê prematuro.");
    return;
  }

  if (isPCD && !pcdDescription) {
    alert("Por favor, descreva a deficiência do bebê.");
    return;
  }

  // Calcula idade corrigida se o bebê for prematuro
  let correctedAge = parseInt(selectedAge);
  let isAgeAdjusted = false;

  if (isPremature && parseInt(prematureWeeks) < 37) {
    // Passo 1: obter a idade cronológica em meses (já temos em selectedAge)

    // Passo 2: calcular diferença entre 40 semanas e idade gestacional
    const weeksDifference = 40 - parseInt(prematureWeeks);

    // Converter diferença de semanas em meses (aprox. 4 semanas = 1 mês)
    const monthsToSubtract = Math.round(weeksDifference / 4);

    // Passo 3: subtrair o tempo equivalente da idade cronológica
    correctedAge = Math.max(0, parseInt(selectedAge) - monthsToSubtract);
    isAgeAdjusted = true;
  }

  // Seleciona todas as perguntas
  const questionElements = document.querySelectorAll(".question");

  // Verifica se todas as perguntas foram respondidas
  let allAnswered = true;
  questionElements.forEach((q) => {
    const questionId = q.getAttribute("data-id");
    const answered = document.querySelector(`input[name="${questionId}"]:checked`);
    if (!answered) {
      allAnswered = false;
    }
  });

  if (!allAnswered) {
    alert("Por favor, responda todas as perguntas.");
    return;
  }

  // Mostra estado de carregamento
  results.style.display = "block";
  resultContent.innerHTML = `
        <div class="loading-results">
            <div class="loader"></div>
            <p>Processando resultados...</p>
        </div>
    `;

  // Rola até os resultados
  results.scrollIntoView({ behavior: "smooth" });

  // Coleta as respostas
  const answers = [];
  let positiveCount = 0;

  questionElements.forEach((q) => {
    const questionId = q.getAttribute("data-id");
    const questionText = q.querySelector("h3").textContent;
    const area = q.getAttribute("data-area");
    const selectedOption = document.querySelector(`input[name="${questionId}"]:checked`).value;

    answers.push({
      area: area,
      question: questionText,
      answer: selectedOption,
    });

    if (selectedOption === "Sim") {
      positiveCount++;
    }
  });

  // Calcula pontuação
  const totalQuestions = questionElements.length;
  const scorePercentage = Math.round((positiveCount / totalQuestions) * 100);

  // Define mensagem com base na pontuação
  let scoreMessage = "";
  if (scorePercentage >= 90) {
    scoreMessage = "Parabéns! O desenvolvimento do bebê está adequado para a idade.";
  } else if (scorePercentage >= 80) {
    scoreMessage = "Alerta! Fique de olho no desenvolvimento do bebê e considere consultar um profissional.";
  } else {
    scoreMessage = "Recomendamos que procure um profissional da área para avaliar o desenvolvimento do bebê.";
  }

  // Oculta o botão de envio após submissão
  const submitButton = document.querySelector(".submit-btn");
  if (submitButton) {
    submitButton.style.display = "none";
  }

  // Obtém data atual para o relatório
  const currentDate = new Date();
  const formattedDate = currentDate.toLocaleDateString("pt-BR");

  // Exibe resultados básicos
  let basicResultsHTML = `
        <div class="basic-results">
            <h3>Resumo da Avaliação</h3>
            <p><strong>Email:</strong> ${babyEmail}</p>
            <p><strong>Data da Avaliação:</strong> ${formattedDate}</p>
            <p><strong>Nome do Bebê:</strong> ${babyName}</p>
    `;

  // Exibe idade cronológica e corrigida se ajustada
  if (isAgeAdjusted) {
    basicResultsHTML += `
            <p><strong>Idade cronológica:</strong> ${parseInt(selectedAge)} ${selectedAge == 1 ? "mês" : "meses"}</p>
            <p><strong>Idade corrigida:</strong> ${correctedAge} ${correctedAge == 1 ? "mês" : "meses"} (usada para avaliação)</p>
        `;
  } else {
    basicResultsHTML += `<p><strong>Idade do bebê:</strong> ${parseInt(selectedAge)} ${selectedAge == 1 ? "mês" : "meses"}</p>`;
  }

  // Se prematuro, exibe semanas de gestação
  if (isPremature) {
    basicResultsHTML += `<p><strong>Bebê Prematuro:</strong> Sim (${prematureWeeks} semanas)</p>`;
  }

  // Se PCD, exibe descrição
  if (isPCD) {
    basicResultsHTML += `<p><strong>PCD:</strong> Sim (${pcdDescription})</p>`;
  }

  // Exibe pergunta do usuário, se houver
  if (userQuestion) {
    basicResultsHTML += `
            <div class="user-question-section">
                <h4>Sua Pergunta:</h4>
                <p>${userQuestion}</p>
                <div id="userQuestionAnswer" class="user-question-answer">
                    <p><em>A resposta para sua pergunta será fornecida na análise detalhada abaixo.</em></p>
                </div>
            </div>
        `;
  }

  // Seção de perguntas e respostas
  basicResultsHTML += `
        <div class="questions-answers-section">
            <h4>Perguntas e Respostas:</h4>
            <ul>
    `;

  answers.forEach((answer) => {
    basicResultsHTML += `
            <li>
                <p><strong>Pergunta:</strong> ${answer.question}</p>
                <p><strong>Resposta:</strong> ${answer.answer}</p>
            </li>
        `;
  });

  basicResultsHTML += `
            </ul>
        </div>
    `;

  // Seção de carregamento da IA e containers de resultado
  basicResultsHTML += `
        <div class="ai-loading" id="aiLoading">
            <p>Gerando análise detalhada com IA...</p>
            <div class="loader"></div>
        </div>
        <div class="ai-analysis" id="aiAnalysis" style="display: none;">
            <h3>Análise Detalhada da IA</h3>
            <div class="analysis-content" id="analysisContent"></div>
        </div>
        <div class="ai-error" id="aiError" style="display: none;">
            <h3>Erro na Análise da IA, por favor tente novamente mais tarde</h3>
            <div class="analysis-content error" id="errorContent"></div>
        </div>
    `;

  // Atualiza o conteúdo dos resultados
  resultContent.innerHTML = basicResultsHTML;

  // Mostrar o estado de carregamento antes da chamada
  const aiLoading = document.getElementById("aiLoading");
  const aiAnalysis = document.getElementById("aiAnalysis");
  const aiError = document.getElementById("aiError");

  aiLoading.style.display = "block";
  aiAnalysis.style.display = "none";
  aiError.style.display = "none";

  // 1. Monta o payload
  const payload = {
    babyEmail: babyEmail,
    babyName: babyName,
    age: parseInt(selectedAge, 10),
    isPremature: isPremature,
    prematureWeeks: isPremature ? parseInt(prematureWeeks, 10) : null,
    isPCD: isPCD,
    pcdDescription: isPCD ? pcdDescription : null,
    userQuestion: userQuestion || null,
    questionsAndAnswers: answers, // cada item tem { area, question, answer }
  };

  // 2. Dispara o POST para o seu endpoint de IA
  fetch("/webhook/quiz-results", {
    method: "POST",
    headers: {
      "Content-Type": "application/json",
    },
    body: JSON.stringify(payload),
  })
    .then((res) => {
      if (!res.ok) throw new Error("Erro na resposta da IA");
      return res.json();
    })

// Modificação na parte do código que processa a resposta da IA
.then((iaResponse) => {
    aiLoading.style.display = 'none';
    aiAnalysis.style.display = 'block';

    // Verifica se iaResponse e iaResponse.ai_analysis existem e são válidos
    if (iaResponse && iaResponse.ai_analysis) {
        // Processar o texto markdown da IA
        const formattedHtml = processMarkdownResponse(iaResponse.ai_analysis);
        
        // Inserir o HTML formatado
        document.getElementById("analysisContent").innerHTML = formattedHtml;
    } else {
        // Trata o caso em que ai_analysis não existe ou é undefined
        document.getElementById("analysisContent").innerHTML = 
            "<p>Não foi possível obter a análise detalhada. Por favor, tente novamente mais tarde.</p>";
    }
})
.catch((err) => {
  aiLoading.style.display = "none";
  aiError.style.display = "block";
  document.getElementById("errorContent").textContent = 
    err.message || "Erro ao processar a resposta da IA";
});

}
// Função para processar o texto markdown da IA e convertê-lo em HTML formatado
function processMarkdownResponse(markdownText) {
    if (!markdownText) return "<p>Não foi possível obter a análise detalhada.</p>";
    
    // Converter markdown para HTML
    let html = markdownText
        // Converter títulos
        .replace(/## (.*?)$/gm, '<h2 class="ai-section-title">$1</h2>')
        .replace(/### (.*?)$/gm, '<h3 class="ai-section-subtitle">$1</h3>')
        
        // Converter separadores
        .replace(/---/g, '<hr class="ai-section-divider">')
        
        // Converter listas
        .replace(/- (.*?)$/gm, '<li>$1</li>')
        .replace(/(<li>.*?<\/li>)\n(?!<li>)/g, '$1</ul>')
        .replace(/(?<!<\/ul>)\n<li>/g, '<ul><li>')
        
        // Converter parágrafos (linhas que não são títulos, listas ou separadores)
        .replace(/^(?!<h[23]|<li|<ul|<\/ul|<hr)(.+)$/gm, '<p>$1</p>')
        
        // Tratar linhas em branco entre parágrafos
        .replace(/<\/p>\n<p>/g, '</p><p>')
        
        // Corrigir possíveis problemas com listas
        .replace(/<\/ul><ul>/g, '');
    
    // Adicionar classes para estilização
    html = '<div class="ai-analysis-formatted">' + html + '</div>';
    
    return html;
}



// Menu hambúrguer
document.addEventListener('DOMContentLoaded', function() {
    const hamburger = document.getElementById('hamburger');
    const nav = document.getElementById('nav');
    
    if (hamburger && nav) {
        hamburger.addEventListener('click', function() {
            hamburger.classList.toggle('active');
            nav.classList.toggle('active');
        });
        
        // Fechar menu ao clicar em um link
        const navLinks = nav.querySelectorAll('a');
        navLinks.forEach(link => {
            link.addEventListener('click', function() {
                hamburger.classList.remove('active');
                nav.classList.remove('active');
            });
        });
    }
    
      // Script para mostrar/esconder campos condicionais
    if (document.getElementById('isPremature')) {
        document.getElementById('isPremature').addEventListener('change', function () {
            document.getElementById('prematureWeeksField').style.display = this.checked ? 'block' : 'none';
        });
    }

    if (document.getElementById('isPCD')) {
        document.getElementById('isPCD').addEventListener('change', function () {
            document.getElementById('pcdDescriptionField').style.display = this.checked ? 'block' : 'none';
        });
    }
});

  