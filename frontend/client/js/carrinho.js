/**
 * =====================================================
 * ARQUIVO: carrinho.js
 * CAMINHO: /js/carrinho.js
 * DESCRIÇÃO: Gerencia todas as interações do carrinho de
 *            compras: sessão, token, chamadas à API,
 *            renderização, atualização de quantidades,
 *            remoção de itens e finalização via WhatsApp.
 * =====================================================
 */

// =====================================================
// CONSTANTES E CONFIGURAÇÕES
// =====================================================

// AJUSTAR URL AQUI - URL base das APIs
const API_BASE = './api/';

// AJUSTAR TELEFONE AQUI - Número do WhatsApp para onde os pedidos serão enviados
// Formato: 55DDDNumero - Sem espaços, sem traços, sem parênteses
const WHATSAPP_NUMERO = '5521999999999'; // Substitua pelo número real aqui 

// AJUSTAR CAMINHO AQUI - Caminho base para as imagens dos produtos
const IMAGENS_BASE = '../assets/images/';

// Nome da chave no localStorage para o token do carrinho
const TOKEN_KEY = 'oma_kasten_carrinho_token';

// =====================================================
// FUNÇÕES UTILITÁRIAS
// =====================================================

/**
 * Obtém ou cria o token do carrinho armazenado no localStorage.
 * Se não existir, gera um novo token e o armazena.
 * 
 * @returns {string} Token do carrinho
 */
function obterTokenCarrinho() {
    let token = localStorage.getItem(TOKEN_KEY);
    
    if (!token) {
        // Gera um token aleatório similar ao PHP bin2hex(random_bytes(16))
        const array = new Uint8Array(16);
        crypto.getRandomValues(array);
        token = Array.from(array, byte => byte.toString(16).padStart(2, '0')).join('');
        localStorage.setItem(TOKEN_KEY, token);
    }
    
    return token;
}

/**
 * Formata um valor numérico para o formato de moeda brasileira.
 * 
 * @param {number} valor - Valor a ser formatado
 * @returns {string} Valor formatado (ex: "R$ 18,00")
 */
function formatarMoeda(valor) {
    return 'R$ ' + valor.toFixed(2).replace('.', ',');
}

/**
 * Atualiza o contador do carrinho na navbar.
 * 
 * @param {number} total - Quantidade total de itens
 */
function atualizarContadorNavbar(total) {
    const contador = document.getElementById('contador-carrinho');
    if (contador) {
        const valorAnterior = contador.textContent;
        contador.textContent = total;
        
        // Adiciona classe de animação se o valor mudou
        if (valorAnterior !== String(total)) {
            contador.classList.add('atualizado');
            setTimeout(() => {
                contador.classList.remove('atualizado');
            }, 300);
        }
    }
}

/**
 * Exibe uma mensagem de erro para o usuário (não invasiva).
 * 
 * @param {string} mensagem - Mensagem de erro
 */
function exibirErro(mensagem) {
    console.error('Erro no carrinho:', mensagem);
    // Poderia ser expandido para mostrar um toast ou alerta visual
}

// =====================================================
// FUNÇÕES DE API (COMUNICAÇÃO COM BACKEND)
// =====================================================

/**
 * Busca os itens do carrinho na API.
 * 
 * @returns {Promise<Object>} Dados do carrinho com itens e resumo
 */
async function apiListarCarrinho() {
    const token = obterTokenCarrinho();
    
    try {
        // AJUSTAR URL AQUI se necessário
        const resposta = await fetch(`${API_BASE}carrinho_listar.php?token=${encodeURIComponent(token)}`);
        
        if (!resposta.ok) {
            throw new Error(`Erro HTTP: ${resposta.status}`);
        }
        
        const dados = await resposta.json();
        return dados;
    } catch (erro) {
        console.error('Erro ao listar carrinho:', erro);
        throw erro;
    }
}

/**
 * Adiciona um produto ao carrinho.
 * 
 * @param {number} produtoId - ID do produto
 * @param {number} quantidade - Quantidade a adicionar (padrão: 1)
 * @returns {Promise<Object>} Resposta da API
 */
async function apiAdicionarItem(produtoId, quantidade = 1) {
    const token = obterTokenCarrinho();
    
    try {
        // AJUSTAR URL AQUI se necessário
        const resposta = await fetch(`${API_BASE}carrinho_adicionar.php`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({
                token: token,
                produto_id: produtoId,
                quantidade: quantidade
            })
        });
        
        if (!resposta.ok) {
            throw new Error(`Erro HTTP: ${resposta.status}`);
        }
        
        const dados = await resposta.json();
        
        if (dados.sucesso) {
            atualizarContadorNavbar(dados.quantidade_total);
        }
        
        return dados;
    } catch (erro) {
        console.error('Erro ao adicionar item:', erro);
        throw erro;
    }
}

/**
 * Atualiza a quantidade de um item no carrinho.
 * 
 * @param {number} itemId - ID do item no carrinho
 * @param {number} quantidade - Nova quantidade
 * @returns {Promise<Object>} Resposta da API
 */
async function apiAtualizarItem(itemId, quantidade) {
    const token = obterTokenCarrinho();
    
    try {
        // AJUSTAR URL AQUI se necessário
        const resposta = await fetch(`${API_BASE}carrinho_atualizar.php`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({
                token: token,
                item_id: itemId,
                quantidade: quantidade
            })
        });
        
        if (!resposta.ok) {
            throw new Error(`Erro HTTP: ${resposta.status}`);
        }
        
        const dados = await resposta.json();
        
        if (dados.sucesso) {
            atualizarContadorNavbar(dados.quantidade_total);
        }
        
        return dados;
    } catch (erro) {
        console.error('Erro ao atualizar item:', erro);
        throw erro;
    }
}

/**
 * Remove um item do carrinho.
 * 
 * @param {number} itemId - ID do item no carrinho
 * @returns {Promise<Object>} Resposta da API
 */
async function apiRemoverItem(itemId) {
    const token = obterTokenCarrinho();
    
    try {
        // AJUSTAR URL AQUI se necessário
        const resposta = await fetch(`${API_BASE}carrinho_remover.php`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({
                token: token,
                item_id: itemId
            })
        });
        
        if (!resposta.ok) {
            throw new Error(`Erro HTTP: ${resposta.status}`);
        }
        
        const dados = await resposta.json();
        
        if (dados.sucesso) {
            atualizarContadorNavbar(dados.quantidade_total);
        }
        
        return dados;
    } catch (erro) {
        console.error('Erro ao remover item:', erro);
        throw erro;
    }
}

/**
 * Finaliza o carrinho e obtém a mensagem formatada para WhatsApp.
 * 
 * @returns {Promise<Object>} Resposta da API com texto formatado
 */
async function apiFinalizarCarrinho() {
    const token = obterTokenCarrinho();
    
    try {
        // AJUSTAR URL AQUI se necessário
        const resposta = await fetch(`${API_BASE}carrinho_finalizar.php`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({
                token: token
            })
        });
        
        if (!resposta.ok) {
            throw new Error(`Erro HTTP: ${resposta.status}`);
        }
        
        const dados = await resposta.json();
        return dados;
    } catch (erro) {
        console.error('Erro ao finalizar carrinho:', erro);
        throw erro;
    }
}

// =====================================================
// RENDERIZAÇÃO DA PÁGINA
// =====================================================

/**
 * Renderiza o estado de carrinho vazio.
 */
function renderizarCarrinhoVazio() {
    const grid = document.getElementById('carrinho-grid');
    const vazio = document.getElementById('carrinho-vazio');
    const totalItensTexto = document.getElementById('total-itens-texto');
    
    if (grid) grid.style.display = 'none';
    if (vazio) vazio.style.display = 'flex';
    if (totalItensTexto) totalItensTexto.textContent = '0 Itens';
    
    // Desabilita botão de finalizar
    const btnFinalizar = document.getElementById('btn-finalizar');
    if (btnFinalizar) btnFinalizar.disabled = true;
}

/**
 * Cria o HTML para um item do carrinho.
 * 
 * @param {Object} item - Dados do item
 * @returns {string} HTML do item
 */
function criarHtmlItem(item) {
    const subtotal = item.quantidade * item.valor_unitario;
    const imagemSrc = item.imagem 
        ? `${IMAGENS_BASE}${item.imagem}` // AJUSTAR CAMINHO AQUI se necessário
        : 'assets/images/placeholder.png'; // Imagem padrão caso não exista
    
    return `
        <div class="item-carrinho" data-item-id="${item.id}">
            <!-- Imagem do produto -->
            <div class="item-imagem">
                <img src="${imagemSrc}" alt="${item.nome}" loading="lazy">
            </div>
            
            <!-- Informações do produto -->
            <div class="item-info">
                <h3 class="item-nome">${item.nome}</h3>
                <p class="item-descricao">${item.descricao || ''}</p>
            </div>
            
            <!-- Controles de quantidade -->
            <div class="item-quantidade">
                <button class="btn-qtd btn-diminuir" data-item-id="${item.id}" data-quantidade="${item.quantidade}" aria-label="Diminuir quantidade">
                    −
                </button>
                <span class="item-qtd-valor">${String(item.quantidade).padStart(2, '0')}</span>
                <button class="btn-qtd btn-aumentar" data-item-id="${item.id}" data-quantidade="${item.quantidade}" aria-label="Aumentar quantidade">
                    +
                </button>
            </div>
            
            <!-- Preço do item -->
            <div class="item-preco">
                <span class="item-preco-valor">${formatarMoeda(subtotal)}</span>
                <span class="item-preco-subtotal">${item.quantidade}x ${formatarMoeda(item.valor_unitario)}</span>
            </div>
            
            <!-- Botão de excluir -->
            <button class="item-excluir" data-item-id="${item.id}" aria-label="Remover item" title="Remover item">
                ✕
            </button>
        </div>
    `;
}

/**
 * Renderiza o carrinho com os itens fornecidos.
 * 
 * @param {Array} itens - Lista de itens do carrinho
 * @param {Object} resumo - Dados do resumo (subtotal, quantidade_total)
 */
function renderizarCarrinho(itens, resumo) {
    const grid = document.getElementById('carrinho-grid');
    const vazio = document.getElementById('carrinho-vazio');
    const listaItens = document.getElementById('lista-itens');
    const totalItensTexto = document.getElementById('total-itens-texto');
    const resumoSubtotal = document.getElementById('resumo-subtotal');
    const resumoTotal = document.getElementById('resumo-total');
    const btnFinalizar = document.getElementById('btn-finalizar');
    
    // Mostrar grid, esconder estado vazio
    if (grid) grid.style.display = 'grid';
    if (vazio) vazio.style.display = 'none';
    
    // Atualizar contador de itens no título
    const qtdTotal = resumo?.quantidade_total || 0;
    const textoItens = qtdTotal === 1 ? '1 Item' : `${qtdTotal} Itens`;
    if (totalItensTexto) totalItensTexto.textContent = textoItens;
    
    // Renderizar lista de itens
    if (listaItens) {
        if (itens.length === 0) {
            listaItens.innerHTML = '';
            renderizarCarrinhoVazio();
            return;
        }
        
        listaItens.innerHTML = itens.map(item => criarHtmlItem(item)).join('');
        
        // Adicionar event listeners aos botões
        adicionarEventListenersItens();
    }
    
    // Atualizar resumo
    const subtotal = resumo?.subtotal || 0;
    if (resumoSubtotal) resumoSubtotal.textContent = formatarMoeda(subtotal);
    if (resumoTotal) resumoTotal.textContent = formatarMoeda(subtotal);
    
    // Habilitar/desabilitar botão de finalizar
    if (btnFinalizar) {
        btnFinalizar.disabled = itens.length === 0;
    }
    
    // Atualizar contador na navbar
    atualizarContadorNavbar(qtdTotal);
}

/**
 * Adiciona event listeners aos botões de quantidade e exclusão dos itens.
 */
function adicionarEventListenersItens() {
    // Botões de aumentar quantidade
    document.querySelectorAll('.btn-aumentar').forEach(btn => {
        btn.addEventListener('click', async function() {
            const itemId = parseInt(this.dataset.itemId);
            const quantidadeAtual = parseInt(this.dataset.quantidade);
            const novaQuantidade = quantidadeAtual + 1;
            
            // Feedback visual imediato (otimista)
            this.dataset.quantidade = novaQuantidade;
            atualizarQuantidadeVisual(itemId, novaQuantidade);
            
            try {
                const resposta = await apiAtualizarItem(itemId, novaQuantidade);
                if (!resposta.sucesso) {
                    // Reverter em caso de erro
                    this.dataset.quantidade = quantidadeAtual;
                    atualizarQuantidadeVisual(itemId, quantidadeAtual);
                    exibirErro(resposta.mensagem);
                } else {
                    // Recarregar para garantir consistência
                    await carregarCarrinho();
                }
            } catch (erro) {
                // Reverter em caso de erro de rede
                this.dataset.quantidade = quantidadeAtual;
                atualizarQuantidadeVisual(itemId, quantidadeAtual);
                exibirErro('Erro de conexão ao atualizar item.');
            }
        });
    });
    
    // Botões de diminuir quantidade
    document.querySelectorAll('.btn-diminuir').forEach(btn => {
        btn.addEventListener('click', async function() {
            const itemId = parseInt(this.dataset.itemId);
            const quantidadeAtual = parseInt(this.dataset.quantidade);
            
            // Se quantidade é 1, confirmar se deseja remover
            if (quantidadeAtual <= 1) {
                const confirmar = confirm('Deseja remover este item do carrinho?');
                if (confirmar) {
                    await removerItemHandler(itemId);
                }
                return;
            }
            
            const novaQuantidade = quantidadeAtual - 1;
            
            // Feedback visual imediato (otimista)
            this.dataset.quantidade = novaQuantidade;
            atualizarQuantidadeVisual(itemId, novaQuantidade);
            
            try {
                const resposta = await apiAtualizarItem(itemId, novaQuantidade);
                if (!resposta.sucesso) {
                    this.dataset.quantidade = quantidadeAtual;
                    atualizarQuantidadeVisual(itemId, quantidadeAtual);
                    exibirErro(resposta.mensagem);
                } else {
                    await carregarCarrinho();
                }
            } catch (erro) {
                this.dataset.quantidade = quantidadeAtual;
                atualizarQuantidadeVisual(itemId, quantidadeAtual);
                exibirErro('Erro de conexão ao atualizar item.');
            }
        });
    });
    
    // Botões de excluir item
    document.querySelectorAll('.item-excluir').forEach(btn => {
        btn.addEventListener('click', async function() {
            const itemId = parseInt(this.dataset.itemId);
            const confirmar = confirm('Deseja remover este item do carrinho?');
            if (confirmar) {
                await removerItemHandler(itemId);
            }
        });
    });
}

/**
 * Atualiza visualmente a quantidade de um item (feedback otimista).
 * 
 * @param {number} itemId - ID do item
 * @param {number} novaQuantidade - Nova quantidade
 */
function atualizarQuantidadeVisual(itemId, novaQuantidade) {
    const itemElement = document.querySelector(`.item-carrinho[data-item-id="${itemId}"]`);
    if (!itemElement) return;
    
    const qtdValor = itemElement.querySelector('.item-qtd-valor');
    if (qtdValor) {
        qtdValor.textContent = String(novaQuantidade).padStart(2, '0');
    }
}

/**
 * Manipula a remoção de um item do carrinho.
 * 
 * @param {number} itemId - ID do item a ser removido
 */
async function removerItemHandler(itemId) {
    // Adiciona classe de loading ao item
    const itemElement = document.querySelector(`.item-carrinho[data-item-id="${itemId}"]`);
    if (itemElement) {
        itemElement.style.opacity = '0.5';
        itemElement.style.pointerEvents = 'none';
    }
    
    try {
        const resposta = await apiRemoverItem(itemId);
        if (resposta.sucesso) {
            // Recarregar carrinho para refletir mudanças
            await carregarCarrinho();
        } else {
            exibirErro(resposta.mensagem || 'Erro ao remover item.');
            // Restaurar opacidade
            if (itemElement) {
                itemElement.style.opacity = '1';
                itemElement.style.pointerEvents = 'auto';
            }
        }
    } catch (erro) {
        exibirErro('Erro de conexão ao remover item.');
        if (itemElement) {
            itemElement.style.opacity = '1';
            itemElement.style.pointerEvents = 'auto';
        }
    }
}

// =====================================================
// FINALIZAÇÃO DO PEDIDO VIA WHATSAPP
// =====================================================

/**
 * Manipula o clique no botão de finalizar pedido.
 * Chama a API de finalização, obtém a mensagem formatada
 * e abre o WhatsApp com a mensagem.
 */
async function finalizarPedidoHandler() {
    const btnFinalizar = document.getElementById('btn-finalizar');
    
    // Desabilitar botão durante o processamento
    if (btnFinalizar) {
        btnFinalizar.disabled = true;
        btnFinalizar.textContent = 'Processando...';
    }
    
    try {
        const resposta = await apiFinalizarCarrinho();
        
        if (resposta.sucesso && resposta.texto_whatsapp) {
            // Codificar a mensagem para URL
            const mensagemCodificada = encodeURIComponent(resposta.texto_whatsapp);
            
            // AJUSTAR TELEFONE AQUI se necessário (o número também está na constante WHATSAPP_NUMERO)
            const urlWhatsApp = `https://wa.me/${WHATSAPP_NUMERO}?text=${mensagemCodificada}`;
            
            // Limpar o token do localStorage para iniciar um novo carrinho
            localStorage.removeItem(TOKEN_KEY);
            
            // Atualizar contador para zero
            atualizarContadorNavbar(0);
            
            // Redirecionar para o WhatsApp em uma nova aba
            window.open(urlWhatsApp, '_blank');
            
            // Recarregar a página para mostrar carrinho vazio
            setTimeout(() => {
                window.location.reload();
            }, 500);
        } else {
            exibirErro(resposta.mensagem || 'Erro ao finalizar pedido.');
            if (btnFinalizar) {
                btnFinalizar.disabled = false;
                btnFinalizar.innerHTML = 'Finalizar Pedido <span class="seta">&#8594;</span>';
            }
        }
    } catch (erro) {
        exibirErro('Erro de conexão ao finalizar pedido.');
        if (btnFinalizar) {
            btnFinalizar.disabled = false;
            btnFinalizar.innerHTML = 'Finalizar Pedido <span class="seta">&#8594;</span>';
        }
    }
}

// =====================================================
// INICIALIZAÇÃO
// =====================================================

/**
 * Carrega o carrinho da API e renderiza na página.
 */
async function carregarCarrinho() {
    const listaItens = document.getElementById('lista-itens');
    const grid = document.getElementById('carrinho-grid');
    
    // Mostrar estado de carregamento
    if (listaItens) {
        listaItens.innerHTML = '<div class="carregando">Carregando seu carrinho</div>';
    }
    
    try {
        const dados = await apiListarCarrinho();
        
        if (dados.sucesso) {
            if (dados.itens && dados.itens.length > 0) {
                renderizarCarrinho(dados.itens, dados.resumo);
            } else {
                renderizarCarrinhoVazio();
            }
        } else {
            console.warn('Resposta da API sem sucesso:', dados.mensagem);
            renderizarCarrinhoVazio();
        }
    } catch (erro) {
        console.error('Erro ao carregar carrinho:', erro);
        if (listaItens) {
            listaItens.innerHTML = '<p style="text-align:center;color:#c0392b;">Erro ao carregar o carrinho. Tente novamente.</p>';
        }
    }
}

/**
 * Inicializa a página do carrinho.
 * Configura os event listeners globais e carrega os dados.
 */
function inicializarPaginaCarrinho() {
    // Garantir que o token existe no localStorage
    obterTokenCarrinho();
    
    // Configurar event listener do botão de finalizar
    const btnFinalizar = document.getElementById('btn-finalizar');
    if (btnFinalizar) {
        btnFinalizar.addEventListener('click', finalizarPedidoHandler);
    }
    
    // Atualizar contador inicial (será sobrescrito ao carregar)
    atualizarContadorNavbar(0);
    
    // Carregar itens do carrinho
    carregarCarrinho();
}

// =====================================================
// INICIAR QUANDO O DOM ESTIVER PRONTO
// =====================================================
document.addEventListener('DOMContentLoaded', inicializarPaginaCarrinho);