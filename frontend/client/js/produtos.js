/**
 * =====================================================
 * ARQUIVO: produtos.js (MODIFICADO)
 * CAMINHO: /js/produtos.js
 * DESCRIÇÃO: Carrega a lista de produtos e renderiza
 *            na página. Agora inclui botão "Adicionar
 *            ao Carrinho" em cada produto.
 * =====================================================
 */

// =====================================================
// CONSTANTES E CONFIGURAÇÕES
// =====================================================

// AJUSTAR URL AQUI - URL base das APIs
const API_BASE_PRODUTOS = './api/';

// Nome da chave no localStorage para o token do carrinho
const TOKEN_KEY_PRODUTOS = 'oma_kasten_carrinho_token';

// =====================================================
// FUNÇÕES DO CARRINHO (COMPARTILHADAS)
// =====================================================

/**
 * Obtém ou cria o token do carrinho armazenado no localStorage.
 * 
 * @returns {string} Token do carrinho
 */
function obterTokenCarrinhoProdutos() {
    let token = localStorage.getItem(TOKEN_KEY_PRODUTOS);
    
    if (!token) {
        const array = new Uint8Array(16);
        crypto.getRandomValues(array);
        token = Array.from(array, byte => byte.toString(16).padStart(2, '0')).join('');
        localStorage.setItem(TOKEN_KEY_PRODUTOS, token);
    }
    
    return token;
}

/**
 * Atualiza o contador do carrinho na navbar.
 * 
 * @param {number} total - Quantidade total de itens
 */
function atualizarContadorNavbarProdutos(total) {
    const contador = document.getElementById('contador-carrinho');
    if (contador) {
        const valorAnterior = contador.textContent;
        contador.textContent = total;
        
        if (valorAnterior !== String(total)) {
            contador.classList.add('atualizado');
            setTimeout(() => {
                contador.classList.remove('atualizado');
            }, 300);
        }
    }
}

/**
 * Busca a quantidade atual de itens no carrinho para atualizar o contador.
 */
async function atualizarContadorInicial() {
    const token = obterTokenCarrinhoProdutos();
    
    try {
        // AJUSTAR URL AQUI se necessário
        const resposta = await fetch(`${API_BASE_PRODUTOS}carrinho_listar.php?token=${encodeURIComponent(token)}`);
        
        if (resposta.ok) {
            const dados = await resposta.json();
            if (dados.sucesso && dados.resumo) {
                atualizarContadorNavbarProdutos(dados.resumo.quantidade_total || 0);
            }
        }
    } catch (erro) {
        console.error('Erro ao atualizar contador inicial:', erro);
    }
}

/**
 * Adiciona um produto ao carrinho via API.
 * 
 * @param {number} produtoId - ID do produto
 * @param {number} quantidade - Quantidade (padrão: 1)
 * @returns {Promise<Object>} Resposta da API
 */
async function adicionarAoCarrinho(produtoId, quantidade = 1) {
    const token = obterTokenCarrinhoProdutos();
    
    try {
        // AJUSTAR URL AQUI se necessário
        const resposta = await fetch(`${API_BASE_PRODUTOS}carrinho_adicionar.php`, {
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
            // Atualizar contador na navbar
            atualizarContadorNavbarProdutos(dados.quantidade_total);
            
            // Feedback visual no botão
            return { sucesso: true, dados: dados };
        } else {
            console.error('Erro da API:', dados.mensagem);
            return { sucesso: false, mensagem: dados.mensagem };
        }
    } catch (erro) {
        console.error('Erro ao adicionar ao carrinho:', erro);
        return { sucesso: false, mensagem: 'Erro de conexão.' };
    }
}

// =====================================================
// CARREGAMENTO DOS PRODUTOS
// =====================================================

/**
 * Carrega os produtos da API e renderiza na página.
 * Agora inclui botão "Adicionar ao Carrinho" em cada produto.
 */
async function carregarProdutos() {
    try {
        // AJUSTAR URL AQUI se necessário
        const resposta = await fetch(`${API_BASE_PRODUTOS}listar_produtos.php`);

        if (!resposta.ok) {
            console.error("Erro HTTP:", resposta.status);
            return;
        }

        const produtos = await resposta.json();

        console.log("Produtos carregados:", produtos); // DEBUG

        const container = document.getElementById("lista-produtos");

        if (!Array.isArray(produtos)) {
            container.innerHTML = "<p>Erro ao carregar produtos.</p>";
            console.error("Resposta não é uma lista:", produtos);
            return;
        }

        let html = `<table><tr>`;

        produtos.forEach((p, index) => {
            // Quebra de linha a cada 3 produtos
            if (index % 3 === 0 && index !== 0) {
                html += `</tr><tr>`;
            }

            const preco = Number(p.valor)
                .toFixed(2)
                .replace(".", ",");

            html += `
                <td>
                    <img class="produtos" src="../assets/images/${p.imagem}" alt="${p.nome}"> <!-- CAMINHO AJUSTADO -->
                    <p><b>${p.nome}</b></p>
                    <p class="descricao">${p.descricao}</p>
                    <p class="preco">R$${preco}/${p.unidade}</p>
                    <button class="btn-adicionar-carrinho" 
                            data-produto-id="${p.id}" 
                            data-produto-nome="${p.nome}"
                            title="Adicionar ${p.nome} ao carrinho">
                        &#128722; Adicionar ao Carrinho
                    </button>
                </td>
            `;
        });

        html += `</tr></table>`;
        container.innerHTML = html;

        // Adicionar event listeners aos botões de adicionar ao carrinho
        adicionarEventListenersBotoes();

    } catch (e) {
        console.error("Erro no JS:", e);
    }
}

/**
 * Adiciona event listeners a todos os botões "Adicionar ao Carrinho".
 */
function adicionarEventListenersBotoes() {
    const botoes = document.querySelectorAll('.btn-adicionar-carrinho');
    
    botoes.forEach(botao => {
        botao.addEventListener('click', async function() {
            const produtoId = parseInt(this.dataset.produtoId);
            const produtoNome = this.dataset.produtoNome;
            
            // Desabilitar botão temporariamente e mostrar feedback
            this.disabled = true;
            const textoOriginal = this.innerHTML;
            this.innerHTML = 'Adicionando...';
            
            const resultado = await adicionarAoCarrinho(produtoId, 1);
            
            if (resultado.sucesso) {
                // Feedback de sucesso
                this.innerHTML = '&#10003; Adicionado!';
                this.style.backgroundColor = '#6c4a4a';
                this.style.borderColor = '#6c4a4a';
                
                // Restaurar botão após 2 segundos
                setTimeout(() => {
                    this.innerHTML = textoOriginal;
                    this.disabled = false;
                    this.style.backgroundColor = '';
                    this.style.borderColor = '';
                }, 2000);
            } else {
                // Feedback de erro
                this.innerHTML = 'Erro! Tente novamente';
                this.style.backgroundColor = '#c0392b';
                this.style.borderColor = '#c0392b';
                
                // Restaurar botão após 2 segundos
                setTimeout(() => {
                    this.innerHTML = textoOriginal;
                    this.disabled = false;
                    this.style.backgroundColor = '';
                    this.style.borderColor = '';
                }, 2000);
            }
        });
    });
}

// =====================================================
// INICIALIZAÇÃO
// =====================================================

/**
 * Inicializa a página de produtos.
 */
function inicializarPaginaProdutos() {
    // Garantir token do carrinho
    obterTokenCarrinhoProdutos();
    
    // Atualizar contador inicial do carrinho
    atualizarContadorInicial();
    
    // Carregar produtos
    carregarProdutos();
}

// Iniciar quando o DOM estiver pronto
document.addEventListener('DOMContentLoaded', inicializarPaginaProdutos);