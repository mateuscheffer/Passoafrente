// Função para extrair dados do resultado da avaliação
function extractAssessmentData() {
    console.log("=== DADOS DA AVALIAÇÃO ===");
    
    const resultContent = document.getElementById("resultContent");
    if (!resultContent) {
        console.error("Elemento resultContent não encontrado");
        return null;
    }

    const data = {};

    // Função auxiliar para encontrar elemento por texto
    function findElementByText(parent, text) {
        const elements = parent.querySelectorAll('p, strong');
        for (let element of elements) {
            if (element.textContent.includes(text)) {
                return element;
            }
        }
        return null;
    }

    // 1. Email
    const emailElement = findElementByText(resultContent, 'Email:');
    data.email = emailElement ? emailElement.textContent.replace('Email:', '').trim() : 'Não encontrado';
    console.log(data.email);

    // 2. Nome do bebê
    const nameElement = findElementByText(resultContent, 'Nome do Bebê:');
    data.babyName = nameElement ? nameElement.textContent.replace('Nome do Bebê:', '').trim() : 'Não encontrado';
    console.log(data.babyName);

    // 3. Idade do bebê
    const ageElement = findElementByText(resultContent, 'Idade do bebê:') || 
                      findElementByText(resultContent, 'Idade cronológica:') ||
                      findElementByText(resultContent, 'Idade corrigida:');
    data.babyAge = ageElement ? ageElement.textContent.replace(/Idade (do bebê|cronológica|corrigida):/, '').trim() : 'Não encontrado';
    console.log(data.babyAge);

    // 4. PCD (Pessoa com Deficiência)
    const pcdElement = findElementByText(resultContent, 'PCD:');
    if (pcdElement) {
        data.isPCD = pcdElement.textContent.includes('Sim');
        data.pcdDescription = pcdElement.textContent.replace('PCD:', '').trim();
    } else {
        data.isPCD = false;
        data.pcdDescription = 'Não';
    }
    console.log(data.isPCD ? data.pcdDescription : 'Não é PCD');

    // 5. Prematuro
    const prematureElement = findElementByText(resultContent, 'Bebê Prematuro:');
    if (prematureElement) {
        data.isPremature = prematureElement.textContent.includes('Sim');
        data.prematureWeeks = prematureElement.textContent.replace('Bebê Prematuro:', '').trim();
    } else {
        data.isPremature = false;
        data.prematureWeeks = 'Não';
    }
    console.log(data.isPremature ? data.prematureWeeks : 'Não é prematuro');

    // 6. Dúvida do usuário
    const userQuestionSection = resultContent.querySelector('.user-question-section');
    if (userQuestionSection) {
        const questionElement = userQuestionSection.querySelector('p');
        data.userQuestion = questionElement ? questionElement.textContent.trim() : 'Nenhuma pergunta';
    } else {
        data.userQuestion = 'Nenhuma pergunta';
    }
    console.log(data.userQuestion);

    // 7. Perguntas do sistema e respostas do usuário
    const questionsSection = resultContent.querySelector('.questions-answers-section');
    data.systemQuestions = [];
    data.userAnswers = [];
    
    if (questionsSection) {
        const listItems = questionsSection.querySelectorAll('li');
        listItems.forEach(item => {
            const questionElement = item.querySelector('p:first-child');
            const answerElement = item.querySelector('p:last-child');
            
            if (questionElement && answerElement) {
                const question = questionElement.textContent.replace('Pergunta:', '').trim();
                const answer = answerElement.textContent.replace('Resposta:', '').trim();
                
                data.systemQuestions.push(question);
                data.userAnswers.push(answer);
            }
        });
    }
    
    // Imprimir perguntas e respostas
    data.systemQuestions.forEach((question, index) => {
        console.log(`Pergunta ${index + 1}: ${question}`);
    });
    
    data.userAnswers.forEach((answer, index) => {
        console.log(`Resposta ${index + 1}: ${answer}`);
    });

    // 8. Parecer da IA
    const aiAnalysisElement = document.getElementById('analysisContent');
    if (aiAnalysisElement) {
        data.aiAssessment = aiAnalysisElement.textContent.trim();
    } else {
        data.aiAssessment = 'Análise da IA não encontrada';
    }
    console.log(data.aiAssessment);

    console.log("=== FIM DOS DADOS DA AVALIAÇÃO ===");
    
    return data;
}

// Função para enviar dados para o backend e gerar PDF
async function generatePDFBackend() {
    // Extrair e imprimir dados da avaliação
    const assessmentData = extractAssessmentData();
    
    // Mostrar mensagem de carregamento
    const loadingMessage = document.createElement("div");
    loadingMessage.className = "pdf-loading";
    loadingMessage.innerHTML = '<p style="text-align: center; font-size: 1.2em; color: #333;">Gerando PDF profissional...</p><div style="border: 4px solid #f3f3f3; border-top: 4px solid #A8434A; border-radius: 50%; width: 40px; height: 40px; animation: spin 1s linear infinite; margin: 10px auto;"></div><style>@keyframes spin { 0% { transform: rotate(0deg); } 100% { transform: rotate(360deg); } }</style>';
    document.body.appendChild(loadingMessage);

    console.log("--- Iniciando geração de PDF Backend ---");

    try {
        // --- Coleta de Conteúdo HTML apenas de #resultContent ---
        console.log("Coletando conteúdo HTML de #resultContent...");
        const resultContentElement = document.getElementById("resultContent");

        // Verificar se o elemento existe
        if (!resultContentElement) {
            console.error("Erro Crítico: Elemento #resultContent não encontrado no DOM.");
            throw new Error("Elemento #resultContent não encontrado na página. Verifique se o ID está correto.");
        }

        // Capturar o conteúdo HTML
        const resultContentHtml = resultContentElement.innerHTML;

        console.log("Conteúdo de #resultContent coletado (tamanho):", resultContentHtml.length);

        // Verificar se o conteúdo não está vazio
        if (!resultContentHtml.trim()) {
            console.warn("Aviso: O conteúdo de #resultContent parece estar vazio");
        }

        // Dados a serem enviados para o backend
        const currentDate = new Date().toLocaleDateString("pt-BR"); // Formato DD/MM/AAAA
        
        // Criar objeto de dados simplificado
        const postData = {
            resultContentHtml: resultContentHtml,
            currentDate: currentDate
        };

        console.log("Dados finais a serem enviados para o backend:", { 
            keys: Object.keys(postData),
            resultContentLength: postData.resultContentHtml.length,
            currentDate: postData.currentDate
        });

        // --- Envio para o Backend e Download ---
        console.log("Enviando dados para /api/generate_pdf.php...");
        
        // Converter para JSON
        const jsonData = JSON.stringify(postData);
        console.log("Tamanho do JSON a ser enviado:", jsonData.length, "bytes");
        
        const response = await fetch("/api/generate_pdf.php", {
            method: "POST",
            headers: {
                "Content-Type": "application/json",
                "Accept": "application/pdf, application/json"
            },
            body: jsonData
        });
        
        console.log(`Resposta do backend recebida. Status: ${response.status}`);

        if (!response.ok) {
            let errorMsg = `Erro HTTP: ${response.status}`;
            try {
                const errorData = await response.json();
                console.error("Erro do backend (JSON):", errorData);
                errorMsg = errorData.error || errorMsg;
            } catch (e) {
                const errorText = await response.text();
                console.error("Erro do backend (Texto):", errorText);
                errorMsg = errorText || errorMsg;
            }
            throw new Error(errorMsg);
        }

        const blob = await response.blob();
        console.log(`Blob recebido. Tamanho: ${blob.size}, Tipo: ${blob.type}`);

        if (blob.type !== "application/pdf") {
            console.warn("A resposta não foi um PDF.");
            const errorText = await blob.text();
            console.error("Conteúdo da resposta não-PDF:", errorText);
            throw new Error("O servidor não retornou um PDF. Verifique os logs do servidor PHP para mais detalhes.");
        }

        console.log("Criando URL do blob e iniciando download...");
        const url = window.URL.createObjectURL(blob);
        const a = document.createElement("a");
        a.style.display = "none";
        a.href = url;
        const safeDate = currentDate.replace(/\//g, "-");
        const fileName = `relatorio-resultados-${safeDate}.pdf`;
        a.download = fileName; // Define o nome do arquivo para download
        document.body.appendChild(a);
        a.click();
        window.URL.revokeObjectURL(url); // Libera o objeto URL da memória
        a.remove(); // Remove o elemento <a>
        
        // Imprimir nome do arquivo PDF gerado
        console.log(`Arquivo PDF gerado: ${fileName}`);

        console.log("PDF baixado com sucesso via backend!");

    } catch (error) {
        console.error("--- ERRO GERAL na geração/download do PDF Backend ---", error);
        alert("Ocorreu um erro ao gerar o PDF: " + error.message + "\n\nVerifique o console do navegador (F12) e os logs do servidor para mais detalhes.");
    } finally {
        // Remover a mensagem de carregamento
        if (document.body.contains(loadingMessage)) {
            document.body.removeChild(loadingMessage);
        }
        console.log("--- Finalizando geração de PDF Backend ---");
    }
}

// Adiciona o listener ao botão quando o DOM estiver pronto
document.addEventListener("DOMContentLoaded", function() {
    // Tenta encontrar o botão pelo ID fornecido no HTML original ou um ID genérico
    const pdfButton = document.getElementById("baixarPDF") || document.getElementById("generatePdfButton"); 
    if (pdfButton) {
        pdfButton.addEventListener("click", function(event) {
            event.preventDefault(); // Previne o comportamento padrão do botão, se houver
            console.log("Botão de PDF clicado - Chamando generatePDFBackend()");
            generatePDFBackend();
        });
    } 
});
