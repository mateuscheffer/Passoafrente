// Variáveis globais para geolocalização
let userLocation = null;
let locationPermissionGranted = false;

// Solicitar permissão de localização quando a página carregar
document.addEventListener('DOMContentLoaded', function() {

        if(document.getElementById('aceitaLocation')){
             document.getElementById('aceitaLocation').addEventListener('change', function() {
            requestLocationPermission();
        }); 
        }
      

});
// Função para solicitar permissão de localização
function requestLocationPermission() {
    if (navigator.geolocation) {
        navigator.geolocation.getCurrentPosition(
            function(position) {
                // Permissão concedida, armazenar localização
                userLocation = {
                    lat: position.coords.latitude,
                    lng: position.coords.longitude
                };
                locationPermissionGranted = true;
                console.log("Permissão de localização concedida");
                console.log("Localização obtida:", userLocation);

                // Adicionar observador para resultados da IA
                addProfessionalsAfterResults();
            },
            function(error) {
                // Permissão negada ou erro
                locationPermissionGranted = false;
                console.log("Permissão de localização negada ou erro:", error.message);
            }
        );
    } else {
        // Navegador não suporta geolocalização
        locationPermissionGranted = false;
        console.log("Geolocalização não é suportada pelo navegador");
    }
}




// Função para adicionar a busca de profissionais após o resultado da IA
function addProfessionalsAfterResults() {
    // Verificar se o elemento de resultados existe
    const resultsElement = document.getElementById('results');
    if (!resultsElement) return;

    // Observar mudanças no elemento de resultados
    const observer = new MutationObserver(function(mutations) {
        mutations.forEach(function(mutation) {
            // Verificar se o elemento de análise da IA está visível
            const aiAnalysis = document.getElementById('aiAnalysis');
            if (aiAnalysis && aiAnalysis.style.display !== 'none') {
                // Verificar se já existe a seção de profissionais
                if (!document.getElementById('professionals-section') && locationPermissionGranted) {
                    // Adicionar a seção de profissionais
                    addProfessionalsSection();
                    // Buscar profissionais próximos
                    searchNearbyProfessionals();
                    // Parar de observar
                    observer.disconnect();
                }
            }
        });
    });

    // Configurar observação
    observer.observe(resultsElement, {
        childList: true,
        subtree: true,
        attributes: true,
        attributeFilter: ['style']
    });
}

// Função para adicionar a seção de profissionais próximos ao DOM
function addProfessionalsSection() {
    const resultsDiv = document.getElementById('results');

    // Verifica se a seção já existe
    if (document.getElementById('professionals-section')) {
        return;
    }

    // Cria a seção de profissionais
    const professionalsSection = document.createElement('div');
    professionalsSection.id = 'professionals-section';
    professionalsSection.className = 'professionals-section';
    professionalsSection.innerHTML = `
        <h2>Profissionais Próximos</h2>
        <p>Encontramos os seguintes profissionais de saúde infantil próximos à sua localização:</p>
        <div id="professionals-loading" class="loading">
            <p>Buscando profissionais próximos...</p>
            <div class="loader"></div>
        </div>
        <div id="professionals-list" class="professionals-list" style="display: none;"></div>
        <div id="professionals-error" class="error" style="display: none;">
            <p id="professionals-error-message"></p>
        </div>
    `;

    // Adiciona a seção após os resultados
    resultsDiv.appendChild(professionalsSection);
}

// Função para buscar profissionais próximos usando o proxy PHP
function searchNearbyProfessionals() {
    if (!locationPermissionGranted || !userLocation) {
        console.log("Localização não disponível para busca de profissionais");
        return;
    }

    const loadingElement = document.getElementById('professionals-loading');
    const listElement = document.getElementById('professionals-list');
    const errorElement = document.getElementById('professionals-error');
    const errorMessageElement = document.getElementById('professionals-error-message');

    // Mostra o carregamento
    loadingElement.style.display = 'block';
    listElement.style.display = 'none';
    errorElement.style.display = 'none';

    console.log("Iniciando busca de profissionais em:", userLocation);

    // Primeira tentativa: buscar pediatras com raio maior
    fetch('/proxy.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({
            action: 'nearbySearch',
            location: `${userLocation.lat},${userLocation.lng}`,
            radius: 40000, // 10km
            keyword: 'fisioterapia neurofuncional'
        })
    })
    .then(response => {
        console.log("Resposta recebida da primeira busca");
        return response.json();
    })
    .then(data => {
        console.log("Dados da primeira busca:", data);

        // Verificar se há resultados
        if (data.status === 'OK' && data.results && data.results.length > 0) {
            console.log("Profissionais encontrados na primeira busca:", data.results.length);
            // Processar resultados
            getPlacesDetails(data.results);
        } else {
            console.log("Nenhum resultado na primeira busca, tentando busca mais genérica");
            // Segunda tentativa: busca mais genérica
            return fetch('/proxy.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    action: 'nearbySearch',
                    location: `${userLocation.lat},${userLocation.lng}`,
                    radius: 15000, // 15km
                    keyword: 'médico'
                })
            })
            .then(response => response.json())
            .then(genericData => {
                console.log("Dados da segunda busca:", genericData);

                if (genericData.status === 'OK' && genericData.results && genericData.results.length > 0) {
                    console.log("Profissionais encontrados na segunda busca:", genericData.results.length);
                    getPlacesDetails(genericData.results);
                } else {
                    console.log("Nenhum resultado na segunda busca, tentando busca ainda mais genérica");
                    // Terceira tentativa: busca ainda mais genérica
                    return fetch('/proxy.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                        },
                        body: JSON.stringify({
                            action: 'nearbySearch',
                            location: `${userLocation.lat},${userLocation.lng}`,
                            radius: 50000, // 20km
                            keyword: 'hospital'
                        })
                    })
                    .then(response => response.json())
                    .then(lastData => {
                        console.log("Dados da terceira busca:", lastData);

                        if (lastData.status === 'OK' && lastData.results && lastData.results.length > 0) {
                            console.log("Profissionais encontrados na terceira busca:", lastData.results.length);
                            getPlacesDetails(lastData.results);
                        } else {
                            // Nenhum profissional encontrado mesmo com busca genérica
                            console.log("Nenhum profissional encontrado em todas as tentativas");
                            loadingElement.style.display = 'none';
                            errorElement.style.display = 'block';
                            errorMessageElement.textContent = 'Não encontramos profissionais próximos à sua localização.';
                        }
                    });
                }
            });
        }
    })
    .catch(error => {
        console.error('Erro ao buscar profissionais:', error);
        loadingElement.style.display = 'none';
        errorElement.style.display = 'block';
        errorMessageElement.textContent = 'Erro ao buscar profissionais. Por favor, tente novamente mais tarde.';
    });
}

// Função para obter detalhes de cada lugar
function getPlacesDetails(places) {
    const loadingElement = document.getElementById('professionals-loading');
    const listElement = document.getElementById('professionals-list');
    const errorElement = document.getElementById('professionals-error');

    console.log("Obtendo detalhes para", places.length, "lugares");

    const detailedPlaces = [];
    let processedCount = 0;

    places.forEach(function(place) {
        if (place.place_id) {
            fetch('/proxy.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    action: 'getDetails',
                    placeId: place.place_id,
                    fields: 'name,vicinity,formatted_phone_number,rating,photos,user_ratings_total'
                })
            })
            .then(response => response.json())
            .then(data => {
                processedCount++;
                console.log("Detalhes recebidos para lugar", processedCount, "de", places.length);

                if (data.status === 'OK' && data.result) {
                    detailedPlaces.push(data.result);
                }

                // Quando todos os lugares forem processados
                if (processedCount === places.length) {
                    console.log("Todos os detalhes recebidos, exibindo", detailedPlaces.length, "lugares");
                    displayProfessionalsFlexible(detailedPlaces, {
                        requirePhoto: true,
                        requirePhone: true,
                        requireRating: true,
                        minRating: 4.0,
                        sortByRating: true // Adicionamos essa nova opção
                    });
                }
            })
            .catch(error => {
                console.error('Erro ao obter detalhes do lugar:', error);
                processedCount++;

                // Quando todos os lugares forem processados
                if (processedCount === places.length) {
                    // Mesmo em caso de erro, tenta exibir o que foi coletado
                    displayProfessionalsFlexible(detailedPlaces, {
                        requirePhoto: true,
                        requirePhone: true,
                        requireRating: true,
                        minRating: 4.0,
                        sortByRating: true // Adicionamos essa nova opção
                    });
                }
            });
        } else {
            processedCount++;
            // Se não tem place_id, não podemos buscar detalhes, então consideramos processado
            if (processedCount === places.length) {
                 displayProfessionalsFlexible(detailedPlaces, {
                    requirePhoto: true,
                    requirePhone: true,
                    requireRating: true,
                    minRating: 4.0,
                    sortByRating: true // Adicionamos essa nova opção
                });
            }
        }
    });
}


function displayProfessionalsFlexible(places, options = {}) {
    const loadingElement = document.getElementById('professionals-loading');
    const listElement = document.getElementById('professionals-list');
    const errorElement = document.getElementById('professionals-error');

    console.log("Iniciando exibição flexível de profissionais com opções:", options);

    // Ocultar carregamento
    loadingElement.style.display = 'none';

    // Limpar lista anterior
    listElement.innerHTML = '';
    listElement.style.display = 'none'; // Esconde por padrão até ter resultados

    let filteredPlaces = places.filter(place => {
        // Filtrar por foto
        if (options.requirePhoto && (!place.photos || place.photos.length === 0 || !place.photos[0].photo_reference)) {
            return false;
        }
        // Filtrar por telefone
        if (options.requirePhone && !place.formatted_phone_number) {
            return false;
        }
        // Filtrar por avaliação mínima
        if (options.requireRating && (!place.rating || place.rating < options.minRating)) {
            return false;
        }
        return true;
    });

    // --- Lógica de Ordenação ADICIONADA AQUI ---
    if (options.sortByRating) {
        filteredPlaces.sort((a, b) => {
            // Critério principal: maior avaliação primeiro
            // Se 'a' tiver avaliação maior que 'b', 'a' vem antes (retorna negativo)
            // Se 'b' tiver avaliação maior que 'a', 'b' vem antes (retorna positivo)
            // Se forem iguais, vai para o segundo critério
            const ratingDiff = (b.rating || 0) - (a.rating || 0); // Use 0 se a avaliação não existir

            if (ratingDiff !== 0) {
                return ratingDiff;
            }

            // Critério de desempate: maior número de avaliações primeiro
            // Se 'a' tiver mais avaliações que 'b', 'a' vem antes
            // Se 'b' tiver mais avaliações que 'a', 'b' vem antes
            return (b.user_ratings_total || 0) - (a.user_ratings_total || 0); // Use 0 se o total de avaliações não existir
        });
        console.log("Profissionais ordenados por avaliação e número de avaliações.");
    }
    // --- Fim da Lógica de Ordenação ---

    console.log("Profissionais filtrados e ordenados para exibição:", filteredPlaces.length);

    if (filteredPlaces.length === 0) {
        console.log("Nenhum profissional para exibir após a filtragem e ordenação.");
        errorElement.style.display = 'block';
        document.getElementById('professionals-error-message').textContent = 'Não encontramos profissionais que correspondem aos critérios de busca (foto, telefone e avaliação acima de 4 estrelas).';
        return;
    }

    // Mostrar lista de lugares se houver resultados
    listElement.style.display = 'grid';
    errorElement.style.display = 'none'; // Esconde a mensagem de erro se houver resultados

    // Adicionar cada lugar filtrado e ordenado à lista
       filteredPlaces.forEach(function(place) {
        const placeCard = document.createElement('div');
        placeCard.className = 'professional-card';

        // Imagem do lugar
        let imageHtml = '';
        if (place.photos && place.photos.length > 0 && place.photos[0].photo_reference) {
            const photoUrl = `/proxy.php?action=getPhoto&photo_reference=${place.photos[0].photo_reference}&maxwidth=400`;
            imageHtml = `
                <div class="professional-image">
                    <img src="${photoUrl}" alt="${place.name}" loading='lazy' onerror="this.src='https://via.placeholder.com/150?text=Sem+Imagem'">
                </div>
            `;
        } else {
            imageHtml = `
                <div class="professional-image no-image">
                    <img src="https://via.placeholder.com/150?text=Sem+Imagem" alt="Sem imagem disponível">
                </div>
            `;
        }

        // --- LÓGICA PARA O BOTÃO WHATSAPP ---
        let whatsappButtonHtml = '';
        if (place.formatted_phone_number) {
            // Remove todos os caracteres não numéricos do telefone
            const phoneNumberClean = place.formatted_phone_number.replace(/\D/g, '');

            // Mensagem pré-preenchida para o WhatsApp (URL-encoded)
            const defaultMessage = encodeURIComponent(`Olá, vim através do site "Passo à Frente" e gostaria de mais informações. (Referente a ${place.name})`);

            // Constrói a URL do WhatsApp
            const whatsappUrl = `https://wa.me/+55${phoneNumberClean}?text=${defaultMessage}`;

            whatsappButtonHtml = `
                <a href="${whatsappUrl}" target="_blank" class="whatsapp-button">
                    <span>Entrar em contato via WhatsApp</span>
                </a>
            `;
        } else {
            whatsappButtonHtml = `
                <p class="phone-unavailable">Telefone não disponível</p>
            `;
        }
        // --- FIM DA LÓGICA PARA O BOTÃO WHATSAPP ---

        // Informações do lugar
        const rating = place.rating ? renderStarsForProfessionals(place.rating) : 'Sem avaliações';
        const totalRatings = place.user_ratings_total ? `(${place.user_ratings_total} avaliações)` : '';

        placeCard.innerHTML = `
            ${imageHtml}
            <div class="professional-info">
                <h3>${place.name}</h3>
                <p class="address"><strong>Endereço:</strong> ${place.vicinity || 'Não disponível'}</p>
                ${whatsappButtonHtml} <div class="rating">
                    <strong>Avaliação:</strong> <span class="stars">${rating}</span>
                    <span class="total-ratings">${totalRatings}</span>
                </div>
            </div>
        `;

        listElement.appendChild(placeCard);
    });
}
// Função para renderizar estrelas para avaliações
function renderStarsForProfessionals(rating) {
    if (!rating) return 'Sem avaliações';

    const fullStars = Math.floor(rating);
    const halfStar = rating % 1 >= 0.5;
    const emptyStars = 5 - fullStars - (halfStar ? 1 : 0);

    let starsHtml = '';

    // Estrelas cheias
    for (let i = 0; i < fullStars; i++) {
        starsHtml += '<span class="star">★</span>';
    }

    // Meia estrela
    if (halfStar) {
        starsHtml += '<span class="star half">★</span>';
    }

    // Estrelas vazias
    for (let i = 0; i < emptyStars; i++) {
        starsHtml += '<span class="star empty">☆</span>';
    }

    starsHtml += `<span class="rating-value"> (${rating})</span>`;

    return starsHtml;
}