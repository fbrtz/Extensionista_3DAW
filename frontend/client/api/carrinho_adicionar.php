<?php
/**
 * =====================================================
 * ARQUIVO: carrinho_adicionar.php
 * CAMINHO: /api/carrinho_adicionar.php
 * DESCRIÇÃO: API REST para adicionar itens ao carrinho.
 *            Recebe JSON com token, produto_id e quantidade.
 *            Retorna JSON com status da operação.
 * =====================================================
 */

// Cabeçalhos obrigatórios
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");

// Se for uma requisição OPTIONS (preflight CORS), encerra aqui
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Incluir conexão com o banco
require_once __DIR__ . '/conexao.php'; // AJUSTAR CAMINHO AQUI se necessário

// Resposta padrão
$resposta = [
    'sucesso' => false,
    'mensagem' => '',
    'quantidade_total' => 0
];

try {
    // Verificar método HTTP
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Método não permitido. Utilize POST.');
    }

    // Obter e decodificar JSON do corpo da requisição
    $jsonEntrada = file_get_contents('php://input');
    $dados = json_decode($jsonEntrada, true);

    if (json_last_error() !== JSON_ERROR_NONE) {
        throw new Exception('JSON inválido: ' . json_last_error_msg());
    }

    // Validar campos obrigatórios
    if (empty($dados['token'])) {
        throw new Exception('Token do carrinho é obrigatório.');
    }

    if (empty($dados['produto_id']) || !is_numeric($dados['produto_id'])) {
        throw new Exception('ID do produto é obrigatório e deve ser numérico.');
    }

    $token = trim($dados['token']);
    $produtoId = (int)$dados['produto_id'];
    $quantidade = isset($dados['quantidade']) && is_numeric($dados['quantidade']) ? (int)$dados['quantidade'] : 1;

    if ($quantidade < 1) {
        $quantidade = 1;
    }

    // Obter conexão com o banco
    $conexao = obterConexao();

    if ($conexao === null) {
        throw new Exception('Falha na conexão com o banco de dados.');
    }

    // 1. Buscar ou criar o carrinho baseado no token
    $sqlCarrinho = "SELECT id, status FROM carrinhos WHERE token = ? LIMIT 1";
    $stmtCarrinho = $conexao->prepare($sqlCarrinho);
    $stmtCarrinho->bind_param("s", $token);
    $stmtCarrinho->execute();
    $resultadoCarrinho = $stmtCarrinho->get_result();
    $carrinho = $resultadoCarrinho->fetch_assoc();

    // Se não existir carrinho, cria um novo com o token fornecido
    if (!$carrinho) {
        $stmtCarrinho->close();
        $statusAberto = 'ABERTO';
        $sqlNovoCarrinho = "INSERT INTO carrinhos (token, status) VALUES (?, ?)";
        $stmtNovoCarrinho = $conexao->prepare($sqlNovoCarrinho);
        $stmtNovoCarrinho->bind_param("ss", $token, $statusAberto);
        
        if (!$stmtNovoCarrinho->execute()) {
            throw new Exception('Erro ao criar carrinho: ' . $stmtNovoCarrinho->error);
        }
        
        $carrinhoId = $stmtNovoCarrinho->insert_id;
        $stmtNovoCarrinho->close();
    } else {
        $carrinhoId = $carrinho['id'];
        
        // Se o carrinho estiver finalizado, não permite adicionar itens
        if ($carrinho['status'] === 'FINALIZADO') {
            // Cria um novo carrinho para o mesmo token
            $stmtCarrinho->close();
            $statusAberto = 'ABERTO';
            $sqlNovoCarrinho = "INSERT INTO carrinhos (token, status) VALUES (?, ?)";
            $stmtNovoCarrinho = $conexao->prepare($sqlNovoCarrinho);
            $stmtNovoCarrinho->bind_param("ss", $token, $statusAberto);
            
            if (!$stmtNovoCarrinho->execute()) {
                throw new Exception('Erro ao criar novo carrinho: ' . $stmtNovoCarrinho->error);
            }
            
            $carrinhoId = $stmtNovoCarrinho->insert_id;
            $stmtNovoCarrinho->close();
        } else {
            $stmtCarrinho->close();
        }
    }

    // 2. Verificar se o produto existe e obter seu valor atual
    $sqlProduto = "SELECT id, valor FROM produtos WHERE id = ? LIMIT 1";
    $stmtProduto = $conexao->prepare($sqlProduto);
    $stmtProduto->bind_param("i", $produtoId);
    $stmtProduto->execute();
    $resultadoProduto = $stmtProduto->get_result();
    $produto = $resultadoProduto->fetch_assoc();

    if (!$produto) {
        throw new Exception('Produto não encontrado.');
    }

    $valorUnitario = (float)$produto['valor'];
    $stmtProduto->close();

    // 3. Verificar se o produto já está no carrinho (atualizar quantidade) ou inserir novo
    $sqlItemExistente = "SELECT id, quantidade FROM carrinho_itens WHERE carrinho_id = ? AND produto_id = ? LIMIT 1";
    $stmtItemExistente = $conexao->prepare($sqlItemExistente);
    $stmtItemExistente->bind_param("ii", $carrinhoId, $produtoId);
    $stmtItemExistente->execute();
    $resultadoItemExistente = $stmtItemExistente->get_result();
    $itemExistente = $resultadoItemExistente->fetch_assoc();

    if ($itemExistente) {
        // Atualizar quantidade
        $novaQuantidade = $itemExistente['quantidade'] + $quantidade;
        $sqlAtualizar = "UPDATE carrinho_itens SET quantidade = ?, valor_unitario = ? WHERE id = ?";
        $stmtAtualizar = $conexao->prepare($sqlAtualizar);
        $stmtAtualizar->bind_param("idi", $novaQuantidade, $valorUnitario, $itemExistente['id']);
        
        if (!$stmtAtualizar->execute()) {
            throw new Exception('Erro ao atualizar item do carrinho: ' . $stmtAtualizar->error);
        }
        $stmtAtualizar->close();
    } else {
        // Inserir novo item
        $sqlInserir = "INSERT INTO carrinho_itens (carrinho_id, produto_id, quantidade, valor_unitario) VALUES (?, ?, ?, ?)";
        $stmtInserir = $conexao->prepare($sqlInserir);
        $stmtInserir->bind_param("iiid", $carrinhoId, $produtoId, $quantidade, $valorUnitario);
        
        if (!$stmtInserir->execute()) {
            throw new Exception('Erro ao inserir item no carrinho: ' . $stmtInserir->error);
        }
        $stmtInserir->close();
    }

    $stmtItemExistente->close();

    // 4. Obter a quantidade total de itens no carrinho
    $sqlTotalItens = "SELECT SUM(quantidade) as total FROM carrinho_itens WHERE carrinho_id = ?";
    $stmtTotal = $conexao->prepare($sqlTotalItens);
    $stmtTotal->bind_param("i", $carrinhoId);
    $stmtTotal->execute();
    $resultadoTotal = $stmtTotal->get_result();
    $totalItens = $resultadoTotal->fetch_assoc()['total'] ?? 0;
    $stmtTotal->close();

    // Fechar conexão
    $conexao->close();

    // Montar resposta de sucesso
    $resposta['sucesso'] = true;
    $resposta['mensagem'] = 'Produto adicionado ao carrinho com sucesso.';
    $resposta['quantidade_total'] = (int)$totalItens;

} catch (Exception $e) {
    http_response_code(400);
    $resposta['mensagem'] = $e->getMessage();
    // Log do erro real para debug (não expor em produção)
    error_log("Erro em carrinho_adicionar.php: " . $e->getMessage());
}

// Retornar resposta JSON
echo json_encode($resposta, JSON_UNESCAPED_UNICODE);
exit;