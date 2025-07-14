document.addEventListener("DOMContentLoaded", function () {
  const ageSelect = document.getElementById("babyAge");
  const questionForm = document.getElementById("questionForm");
  const results = document.getElementById("results");
  const resultContent = document.getElementById("resultContent");
  const userQuestionContainer = document.getElementById("userQuestionContainer");

  // QUESTOES PARA CADA FORMULARIO BASEADO EM MESES
  const questionsDatabase = {
    0: [
      // 0 dias a 30 dias
      {
        id: "0-1",
        question: "Move os braços e pernas de forma espontânea e simétrica? (Indica atividade motora espontânea e integridade neurológica básica.)",
        options: ["Sim", "Não"],
      },
      {
        id: "0-2",
        question: "Mantém a cabeça de lado quando está deitado de barriga para cima? (Um sinal precoce de controle cervical e preferência postural.)",
        options: ["Sim", "Não"],
      },
      {
        id: "0-3",
        question: "Quando você coloca seu dedo na palma da mão do bebê, ele segura com força? (Reflexo primitivo esperado e importante para integração sensório-motora.)",
        options: ["Sim", "Não"],
      },
      {
        id: "0-4",
        question: "Reage com movimentos corporais (como um susto) ao som alto ou estímulo repentino (reflexo de Moro)?",
        options: ["Sim", "Não"],
      },
      {
        id: "0-5",
        question:
          "Consegue levantar brevemente a cabeça quando está de bruços (posição de barriga para baixo)? (Indica força cervical inicial e é uma das primeiras manifestações de controle antigravitacional).",
        options: ["Sim", "Não"],
      },
    ],
    1: [
      // 1 mês a 1 mes e 30d
      {
        id: "1-1",
        question: "Você percebe que seu bebê já tenta alinhar a cabeça quando é colocado sentado com apoio ou ao ser segurado no colo?",
        options: ["Sim", "Não"],
      },
      {
        id: "1-2",
        question: "Quando está deitado de barriga para cima, seu bebê mantém os braços e pernas afastados do corpo, sem ficar todo encolhido? (AIMS – posição supina)",
        options: ["Sim", "Não"],
      },
      {
        id: "1-3",
        question: "Seu bebê parece mais atento ao ambiente, ficando acordado por mais tempo e olhando ao redor?",
        options: ["Sim", "Não"],
      },
      {
        id: "1-4",
        question: "Seu bebê mantém a cabeça mais centralizada (reta) quando está deitado de costas?",
        options: ["Sim", "Não"],
      },
      {
        id: "1-5",
        question: "Quando está de bruços, seu bebê consegue levantar a cabeça por mais tempo do que no primeiro mês, mesmo que por poucos segundos? (Baseado na AIMS – posição prona)",
        options: ["Sim", "Não"],
      },
    ],
    2: [
      // 2 meses a 2 meses e 30d
      {
        id: "2-1",
        question: "Quando está de bruços, seu bebê levanta a cabeça e consegue deixá-la erguida por alguns segundos? (Controle cervical mais ativo na posição prona)",
        options: ["Sim", "Não"],
      },
      {
        id: "2-2",
        question: "Seu bebê consegue virar a cabeça para os dois lados quando está deitado de costas?",
        options: ["Sim", "Não"],
      },
      {
        id: "2-3",
        question: "Ao ser colocado sentado com apoio, seu bebê tenta manter a cabeça mais firme, mesmo que ainda oscile um pouco? (AIMS – progresso no controle postural na posição sentada)",
        options: ["Sim", "Não"],
      },
      {
        id: "2-4",
        question: "Quando você segura seu bebê de pé com apoio, ele dobra um pouco os joelhos e apoia os pezinhos no chão? (AIMS – início da reação de apoio plantar)",
        options: ["Sim", "Não"],
      },
      {
        id: "2-5",
        question: "Seu bebê já apoia o peso do corpo nos antebraços quando está de bruços? (Início do apoio em membros superiores – posição prona)",
        options: ["Sim", "Não"],
      },
      {
        id: "2-6",
        question: "Você percebe que seu bebê está começando a tentar juntar as mãozinhas na frente do corpo? (Coordenação olho-mão – início do uso das mãos na linha média)",
        options: ["Sim", "Não"],
      },
    ],
    3: [
      // 3 meses a 3 meses e 30d
      {
        id: "3-1",
        question: "Quando está de bruços, seu bebê consegue levantar bem a cabeça e parte do peito, apoiando-se nos antebraços? (Postura de esfinge – controle cervical e torácico)",
        options: ["Sim", "Não"],
      },
      {
        id: "3-2",
        question: "Quando está de barriga para cima, seu bebê brinca com as mãozinhas ou junta as mãos na frente do corpo? (Coordenação e atenção visual/manual em supino)",
        options: ["Sim", "Não"],
      },
      {
        id: "3-3",
        question: "Seu bebê vira a cabeça com facilidade para os dois lados quando está de bruços? (Controle lateral da cabeça em pronação)",
        options: ["Sim", "Não"],
      },
      {
        id: "3-4",
        question: "Quando você segura o bebê sentado com apoio, ele mantém a cabeça firme e erguida sem cair para frente ou para os lados? (Controle cervical quase completo na posição sentada)",
        options: ["Sim", "Não"],
      },
      {
        id: "3-5",
        question: "Ao ser segurado em pé com apoio, seu bebê empurra o chão com os dois pezinhos? (Ativação do reflexo de apoio em posição vertical)",
        options: ["Sim", "Não"],
      },
      {
        id: "3-6",
        question: "Seu bebê começa a rolar de lado (lateralmente), mesmo que ainda não complete o giro? (Início do rolamento lateral – movimento de transição)",
        options: ["Sim", "Não"],
      },
    ],
    4: [
      // 4 meses até 4 meses e 30d
      {
        id: "4-1",
        question: "Quando está de bruços, seu bebê levanta bem a cabeça e o peito, apoiando-se nos antebraços?",
        options: ["Sim", "Não"],
      },
      {
        id: "4-2",
        question: "Seu bebê consegue apoiar parte do peso do corpo nos cotovelos estendidos (braços retos) quando está de bruços? ",
        options: ["Sim", "Não"],
      },
      {
        id: "4-3",
        question: "Quando está de barriga para cima, seu bebê junta as mãos no centro do corpo? ",
        options: ["Sim", "Não"],
      },
      {
        id: "4-4",
        question: "Quando colocado sentado com apoio (como encostado em almofadas), ele mantém a cabeça estável sem cair para frente?",
        options: ["Sim", "Não"],
      },
      {
        id: "4-5",
        question: "Quando está deitado de barriga para cima, seu bebê tenta virar de lado (rolar lateralmente)? ",
        options: ["Sim", "Não"],
      },
      {
        id: "4-6",
        question: "Quando você segura o bebê em pé com apoio, ele estica as perninhas e empurra contra o chão com firmeza?",
        options: ["Sim", "Não"],
      },
      {
        id: "4-7",
        question: "Quando a criança é puxada de deitada para sentada pelos braços, o movimento pode ser ajudado pelos músculos abdominais e pela flexão dos braços?",
        options: ["Sim", "Não"],
      },
    ],
    5: [
      // 5 meses até 5 meses e 30d
      {
        id: "5-1",
        question: "Quando está de bruços, seu bebê consegue se apoiar nas mãos, com os braços estendidos e o peito bem elevado?",
        options: ["Sim", "Não"],
      },
      {
        id: "5-2",
        question: "Seu bebê rola sozinho de barriga para baixo para a posição de barriga para cima?",
        options: ["Sim", "Não"],
      },
      {
        id: "5-3",
        question: "Quando está deitado de barriga para cima, seu bebê pega os próprios pés com as mãos?",
        options: ["Sim", "Não"],
      },
      {
        id: "5-4",
        question: "Quando você o coloca sentado com apoio, ele consegue manter a cabeça e o tronco bem alinhados, sem tombar para os lados?",
        options: ["Sim", "Não"],
      },
      {
        id: "5-5",
        question: "Quando é segurado de pé com os pés no chão, seu bebê sustenta o peso do corpo com as pernas estendidas e firmes?",
        options: ["Sim", "Não"],
      },
    ],
    6: [
      // 6 meses até 6 meses e 30d
      {
        id: "6-1",
        question: "Seu bebê rola sozinho de barriga para cima para a posição de bruços?",
        options: ["Sim", "Não"],
      },
      {
        id: "6-2",
        question: "Quando está de bruços, seu bebê estica bem os braços, apoiando-se nas mãos com os cotovelos retos?",
        options: ["Sim", "Não"],
      },
      {
        id: "6-3",
        question: "Quando está sentado com apoio, seu bebê já usa as mãos para se equilibrar (coloca as mãos na frente do corpo)?",
        options: ["Sim", "Não"],
      },
      {
        id: "6-4",
        question: "Seu bebê pega brinquedos com uma das mãos com facilidade e leva até a boca?",
        options: ["Sim", "Não"],
      },
      {
        id: "6-5",
        question: "Seu bebê fica sentado com apoio e mantém o tronco e a cabeça firmes sem tombar para os lados?",
        options: ["Sim", "Não"],
      },
    ],
    7: [
      // 7 meses até 7 meses e 30d
      {
        id: "7-1",
        question: "Seu bebê consegue ficar sentado sem apoio, com as costas retas e as mãos livres por alguns segundos?",
        options: ["Sim", "Não"],
      },
      {
        id: "7-2",
        question: "Seu bebê apoia-se nas mãos e nos joelhos, como se estivesse começando a engatinhar? (postura de 4 apoios/gatas)",
        options: ["Sim", "Não"],
      },
      {
        id: "7-3",
        question: "Seu bebê rola sozinho de bruços para barriga para cima e de barriga para cima para bruços?",
        options: ["Sim", "Não"],
      },
      {
        id: "7-4",
        question: "Seu bebê transfere objetos de uma mão para a outra com facilidade?",
        options: ["Sim", "Não"],
      },
      {
        id: "7-5",
        question: "Seu bebê tenta se inclinar para frente ou para os lados enquanto está sentado, para alcançar algo?",
        options: ["Sim", "Não"],
      },
    ],
    8: [
      // 8 meses até 8 meses e 30d
      {
        id: "8-1",
        question: "Seu bebê senta sem apoio com estabilidade e mantém as mãos livres para brincar?",
        options: ["Sim", "Não"],
      },
      {
        id: "8-2",
        question: "Seu bebê muda de posição sozinho, por exemplo, de deitado para sentado ou de sentado para deitado?",
        options: ["Sim", "Não"],
      },
      {
        id: "8-3",
        question: "Seu bebê gira o corpo e se movimenta sobre a barriga para alcançar objetos (como se arrastasse)?",
        options: ["Sim", "Não"],
      },
      {
        id: "8-4",
        question: "Quando sentado, seu bebê gira o tronco e alcança objetos ao redor sem cair?",
        options: ["Sim", "Não"],
      },
      {
        id: "8-5",
        question: "Seu bebê consegue pegar objetos pequenos com os dedos (em pinça) e leva com facilidade até a boca?",
        options: ["Sim", "Não"],
      },
    ],
    9: [
      // 9 meses até 9 meses e 30d
      {
        id: "9-1",
        question: "Seu bebê engatinha ou se arrasta no chão para se locomover até brinquedos ou pessoas?",
        options: ["Sim", "Não"],
      },
      {
        id: "9-2",
        question: "Seu bebê consegue passar da posição de deitado para sentado sem ajuda?",
        options: ["Sim", "Não"],
      },
      {
        id: "9-3",
        question: "Seu bebê assume a posição de quatro apoios (mãos e joelhos) e balança o corpo para frente e para trás? ",
        options: ["Sim", "Não"],
      },
      {
        id: "9-4",
        question: "Seu bebê tenta se apoiar em móveis ou objetos baixos para levantar até ficar de pé?",
        options: ["Sim", "Não"],
      },
      {
        id: "9-5",
        question: "Quando está de pé com apoio, seu bebê sustenta bem o peso nas pernas e fica nessa posição por um tempinho?",
        options: ["Sim", "Não"],
      },
    ],
    10: [
      // 10 meses até 10 meses e 30d
      {
        id: "10-1",
        question: "Seu bebê engatinha com facilidade, usando as mãos e os joelhos para se locomover?",
        options: ["Sim", "Não"],
      },
      {
        id: "10-2",
        question: "Seu bebê se levanta sozinho, puxando-se em móveis ou em pessoas, até ficar de pé?",
        options: ["Sim", "Não"],
      },
      {
        id: "10-3",
        question: "Seu bebê consegue voltar da posição de pé para sentar, de forma controlada?",
        options: ["Sim", "Não"],
      },
      {
        id: "10-4",
        question: "Seu bebê se desloca de forma autônoma dentro de casa, mesmo que ainda engatinhando?",
        options: ["Sim", "Não"],
      },
      {
        id: "10-5",
        question: "Seu bebê manipula objetos pequenos com precisão, como um pedacinho de comida, usando o polegar e o indicador?",
        options: ["Sim", "Não"],
      },
    ],
    11: [
      // 11 meses até 11 meses e 30d
      {
        id: "11-1",
        question: "Seu bebê anda de lado com facilidade, segurando-se em móveis (anda lateralmente com apoio)?",
        options: ["Sim", "Não"],
      },
      {
        id: "11-2",
        question: "Seu bebê consegue ficar de pé sozinho por alguns segundos sem se segurar em nada?",
        options: ["Sim", "Não"],
      },
      {
        id: "11-3",
        question: "Seu bebê se agacha e volta a ficar de pé sem apoio, ou segurando em algo?",
        options: ["Sim", "Não"],
      },
      {
        id: "11-4",
        question: "Seu bebê tenta dar alguns passinhos quando alguém o segura pelas mãos?",
        options: ["Sim", "Não"],
      },
      {
        id: "11-5",
        question: "Seu bebê se desloca com agilidade engatinhando por toda a casa, mesmo em diferentes direções?",
        options: ["Sim", "Não"],
      },
    ],
    12: [
      // 12 meses até 12 meses e 30d
      {
        id: "12-1",
        question: "Seu bebê já consegue dar passos sozinho, sem segurar em nada?",
        options: ["Sim", "Não"],
      },
      {
        id: "12-2",
        question: "Seu bebê se levanta sozinho do chão até ficar de pé, sem ajuda de móveis ou pessoas?",
        options: ["Sim", "Não"],
      },
      {
        id: "12-3",
        question: "Seu bebê se agacha para pegar objetos no chão e volta a ficar de pé sozinho com o objeto na mão?",
        options: ["Sim", "Não"],
      },
      {
        id: "12-4",
        question: "Seu bebê consegue empurrar brinquedos de empurrar (como carrinhos ou andadores), andando atrás deles?",
        options: ["Sim", "Não"],
      },
      {
        id: "12-5",
        question: "Seu bebê caminha com os braços abaixados ou ao lado do corpo sem mantê-los erguidos o tempo todo?",
        options: ["Sim", "Não"],
      },
    ],
    13: [
      // 13 meses até 14 meses, fase de refinamento do andar em diante.
      {
        id: "13-1",
        question: "Anda mudando de direção (como fazer curvas)?",
        options: ["Sim", "Não"],
      },
      {
        id: "13-2",
        question: "Sobe em móveis baixos, como almofadas ou sofá?",
        options: ["Sim", "Não"],
      },
      {
        id: "13-3",
        question: "Anda com objetos nas mãos sem perder o equilíbrio?",
        options: ["Sim", "Não"],
      },
      {
        id: "13-4",
        question: "Tenta subir escadas engatinhando ou com ajuda?",
        options: ["Sim", "Não"],
      },
      {
        id: "13-5",
        question: "Começa a tentar correr, mesmo que tropece?",
        options: ["Sim", "Não"],
      },
    ],
    14: [
      // 13 meses até 14 meses, fase de refinamento do andar em diante.
      {
        id: "14-1",
        question: "Anda mudando de direção (como fazer curvas)?",
        options: ["Sim", "Não"],
      },
      {
        id: "14-2",
        question: "Sobe em móveis baixos, como almofadas ou sofá?",
        options: ["Sim", "Não"],
      },
      {
        id: "14-3",
        question: "Anda com objetos nas mãos sem perder o equilíbrio?",
        options: ["Sim", "Não"],
      },
      {
        id: "14-4",
        question: "Tenta subir escadas engatinhando ou com ajuda?",
        options: ["Sim", "Não"],
      },
      {
        id: "14-5",
        question: "Começa a tentar correr, mesmo que tropece?",
        options: ["Sim", "Não"],
      },
    ],
    15: [
      // 15 a 18 meses
      {
        id: "15-1",
        question: "Joga bola para frente com as mãos?",
        options: ["Sim", "Não"],
      },
      {
        id: "15-2",
        question: "Anda para trás ou tenta dar passinhos para trás?",
        options: ["Sim", "Não"],
      },
      {
        id: "15-3",
        question: "Sobe em sofás ou cadeiras baixas tentando se sentar?",
        options: ["Sim", "Não"],
      },
      {
        id: "15-4",
        question: "Anda puxando um brinquedo com corda ou barbante?",
        options: ["Sim", "Não"],
      },
      {
        id: "15-5",
        question: "Corre com mais controle, desviando de obstáculos?",
        options: ["Sim", "Não"],
      },
      {
        id: "15-6",
        question: "Agacha, brinca e levanta com facilidade?",
        options: ["Sim", "Não"],
      },
      {
        id: "15-7",
        question: "Chuta bola?",
        options: ["Sim", "Não"],
      },
    ],
    16: [
      // 15 a 18 meses
      {
        id: "16-1",
        question: "Joga bola para frente com as mãos?",
        options: ["Sim", "Não"],
      },
      {
        id: "16-2",
        question: "Anda para trás ou tenta dar passinhos para trás?",
        options: ["Sim", "Não"],
      },
      {
        id: "16-3",
        question: "Sobe em sofás ou cadeiras baixas tentando se sentar?",
        options: ["Sim", "Não"],
      },
      {
        id: "16-4",
        question: "Anda puxando um brinquedo com corda ou barbante?",
        options: ["Sim", "Não"],
      },
      {
        id: "16-5",
        question: "Corre com mais controle, desviando de obstáculos?",
        options: ["Sim", "Não"],
      },
      {
        id: "16-6",
        question: "Agacha, brinca e levanta com facilidade?",
        options: ["Sim", "Não"],
      },
      {
        id: "16-7",
        question: "Chuta bola?",
        options: ["Sim", "Não"],
      },
    ],
    17: [
      // 15 a 18 meses
      {
        id: "17-1",
        question: "Joga bola para frente com as mãos?",
        options: ["Sim", "Não"],
      },
      {
        id: "17-2",
        question: "Anda para trás ou tenta dar passinhos para trás?",
        options: ["Sim", "Não"],
      },
      {
        id: "17-3",
        question: "Sobe em sofás ou cadeiras baixas tentando se sentar?",
        options: ["Sim", "Não"],
      },
      {
        id: "17-4",
        question: "Anda puxando um brinquedo com corda ou barbante?",
        options: ["Sim", "Não"],
      },
      {
        id: "17-5",
        question: "Corre com mais controle, desviando de obstáculos?",
        options: ["Sim", "Não"],
      },
      {
        id: "17-6",
        question: "Agacha, brinca e levanta com facilidade?",
        options: ["Sim", "Não"],
      },
      {
        id: "17-7",
        question: "Chuta bola?",
        options: ["Sim", "Não"],
      },
    ],
    18: [
      // 15 a 18 meses
      {
        id: "18-1",
        question: "Joga bola para frente com as mãos?",
        options: ["Sim", "Não"],
      },
      {
        id: "18-2",
        question: "Anda para trás ou tenta dar passinhos para trás?",
        options: ["Sim", "Não"],
      },
      {
        id: "18-3",
        question: "Sobe em sofás ou cadeiras baixas tentando se sentar?",
        options: ["Sim", "Não"],
      },
      {
        id: "18-4",
        question: "Anda puxando um brinquedo com corda ou barbante?",
        options: ["Sim", "Não"],
      },
      {
        id: "18-5",
        question: "Corre com mais controle, desviando de obstáculos?",
        options: ["Sim", "Não"],
      },
      {
        id: "18-6",
        question: "Agacha, brinca e levanta com facilidade?",
        options: ["Sim", "Não"],
      },
      {
        id: "18-7",
        question: "Chuta bola?",
        options: ["Sim", "Não"],
      },
    ],
  };


  // Evento para o botão de calcular idade
if (document.getElementById("calculateAgeBtn")) {
  document.getElementById("calculateAgeBtn").addEventListener("click", function () {
    // Obter a data de nascimento do bebê
    const birthDateInput = document.getElementById("babyBirthdate");

    if (!birthDateInput.value) {
      alert("Por favor, informe a data de nascimento do bebê.");
      return;
    }

    // Validar outros campos obrigatórios
    const babyEmail = document.getElementById("babyEmail").value;
    const babyName = document.getElementById("babyName").value;
    const isPremature = document.getElementById("isPremature").checked;
    const prematureWeeks = isPremature ? document.getElementById("prematureWeeks").value : 40;

    if (!babyEmail || !babyName) {
      alert("Por favor, preencha o email e o nome do bebê.");
      return;
    }

    if (isPremature && !prematureWeeks) {
      alert("Por favor, informe as semanas de gestação do bebê prematuro.");
      return;
    }

    // Calcular a idade em meses
    const birthDate = new Date(birthDateInput.value);
    const today = new Date();

    // Verificar se a data de nascimento é válida e não está no futuro
    if (birthDate > today) {
      alert("A data de nascimento não pode ser no futuro.");
      return;
    }

    // Calcular a diferença em meses
    let months = (today.getFullYear() - birthDate.getFullYear()) * 12;
    months += today.getMonth() - birthDate.getMonth();

    // Ajustar para o dia do mês (se ainda não completou o mês inteiro)
    if (today.getDate() < birthDate.getDate()) {
      months--;
    }

    // Garantir que a idade não seja negativa
    const chronologicalAge = Math.max(0, months);

    // Armazenar a idade calculada no campo oculto
    document.getElementById("babyAge").value = chronologicalAge;

    // Aplicar correção de idade para bebês prematuros (nascidos antes de 37 semanas)
    if (isPremature && parseInt(prematureWeeks) < 37) {
      // Determinar a prematuridade (diferença entre 40 semanas e a idade gestacional)
      const weeksDifference = 40 - parseInt(prematureWeeks);

      // Converter a diferença de semanas para meses (4 semanas = 1 mês aproximadamente)
      const monthsToSubtract = Math.round(weeksDifference / 4);

      // Subtrair o tempo equivalente à prematuridade da idade cronológica
      // para obter a idade corrigida (garantindo que não seja negativa)
      const correctedAge = Math.max(0, chronologicalAge - monthsToSubtract);

      // Criar notificação explicativa sobre a idade corrigida
      const ageNotification = document.createElement("div");
      ageNotification.className = "age-notification";
      ageNotification.style.marginBottom = "1rem";
      ageNotification.style.padding = "0.5rem";
      ageNotification.style.backgroundColor = "#f0f7ff";
      ageNotification.style.borderRadius = "5px";
      ageNotification.style.borderLeft = "4px solid var(--primary-color)";

      ageNotification.innerHTML = `
          <p><strong>Atenção:</strong> Como o bebê nasceu com ${prematureWeeks} semanas de gestação, 
          estamos usando a idade corrigida de ${correctedAge} ${correctedAge == 1 ? "mês" : "meses"} 
          ao invés da idade cronológica de ${chronologicalAge} ${chronologicalAge == 1 ? "mês" : "meses"}.</p>
          <p><em>A idade corrigida é calculada subtraindo da idade cronológica o tempo equivalente à prematuridade 
          (diferença entre as 40 semanas de gestação ideal e as ${prematureWeeks} semanas de nascimento).</em></p>
          <p><em>Cálculo: ${chronologicalAge} ${chronologicalAge == 1 ? "mês" : "meses"} - ${monthsToSubtract} ${monthsToSubtract == 1 ? "mês" : "meses"} = ${correctedAge} ${
        correctedAge == 1 ? "mês" : "meses"
      }</em></p>
        `;

      // Clear any existing notification
      const existingNotification = questionForm.querySelector(".age-notification");
      if (existingNotification) {
        existingNotification.remove();
      }

      // Load questions for corrected age
      loadQuestions(correctedAge.toString());
      questionForm.classList.add("active");

      // Add notification at the top of the form
      questionForm.insertBefore(ageNotification, questionForm.firstChild);
    } else {
      // For non-premature babies or those born after 36 weeks, use chronological age
      loadQuestions(chronologicalAge.toString());
      questionForm.classList.add("active");
    }

    results.style.display = "none";
  });
}
  function loadQuestions(age) {
    const questions = questionsDatabase[age];
    if (!questions) {
      questionForm.innerHTML = "<p>Não há perguntas disponíveis para esta idade.</p>";
      return;
    }

    let html = "<h2>Questionário para bebê de " + parseInt(age) + " " + (age == 1 ? "mês" : "meses") + "</h2>";

    questions.forEach((q) => {
      // Adiciona área para cada questão
      const area = getAreaForQuestion(q.id);

      html += `
                <div class="question" data-id="${q.id}" data-area="${area}">
                    <h3>${q.question}</h3>
                    <div class="options">
            `;

      q.options.forEach((option) => {
        html += `
                    <label>
                        <input type="radio" name="${q.id}" value="${option}" required>
                        ${option}
                    </label>
                `;
      });

      html += "</div></div>";
    });
    
    // Show user question field alongside the form questions
    const userQuestionContainer = document.getElementById("userQuestionContainer");
    if (userQuestionContainer) {
      userQuestionContainer.style.display = "block";
    }
    
    html += '<button type="button" class="submit-btn" onclick="processAnswers()">Enviar Respostas</button>';
    questionForm.innerHTML = html;
  }

  // Função para determinar a área com base no ID da questão
  function getAreaForQuestion(questionId) {
    // Extrair o primeiro número do ID (ex: "0-1" -> "0")
    const ageGroup = questionId.split("-")[0];

    // Mapear áreas com base na idade
    const areaMap = {
      0: "Controle de Cabeça",
      1: "Controle de Cabeça",
      2: "Controle de Cabeça",
      3: "Controle de Tronco",
      4: "Controle de Tronco",
      5: "Controle de Tronco",
      6: "Sentar",
      7: "Engatinhar",
      8: "Ficar em Pé",
      9: "Engatinhar",
      10: "Ficar em Pé",
      11: "Andar",
      12: "Andar",
    };

    return areaMap[ageGroup] || "Desenvolvimento Geral";
  }
});

const style = document.createElement("style");
style.textContent = `
  .pdf-loading {
    margin-top: 0.5rem;
    padding: 0.5rem;
    background-color: #f8f9fa;
    border-radius: 5px;
    font-style: italic;
    color: var(--primary-color);
  }
`;
document.head.appendChild(style);

// Add CSS for the loading animation
const loadingStyle = document.createElement("style");
loadingStyle.textContent = `
  .loading-results {
    text-align: center;
    padding: 2rem;
  }
  .loader {
    border: 5px solid #f3f3f3;
    border-top: 5px solid var(--primary-color);
    border-radius: 50%;
    width: 50px;
    height: 50px;
    animation: spin 2s linear infinite;
    margin: 1rem auto;
  }
  @keyframes spin {
    0% { transform: rotate(0deg); }
    100% { transform: rotate(360deg); }
  }
  .ai-loading {
    margin-top: 1.5rem;
    padding: 1rem;
    background-color: #f8f9fa;
    border-radius: 8px;
    text-align: center;
  }
  .ai-error {
    margin-top: 1.5rem;
    padding: 1rem;
    background-color: #fff0f0;
    border-radius: 8px;
    border-left: 4px solid #dc3545;
  }
  /* Ensure headings in AI analysis are always bold */
  .analysis-content h1, 
  .analysis-content h2, 
  .analysis-content h3, 
  .analysis-content h4, 
  .analysis-content h5, 
  .analysis-content h6 {
    font-weight: bold;
    margin-top: 1rem;
    margin-bottom: 0.5rem;
    color: var(--text-color);
  }
`;
document.head.appendChild(loadingStyle);
