<?php
/**
 * =====================================================
 * ARQUIVO: carrinho_atualizar.php
 * CAMINHO: /api/carrinho_atualizar.php
 * DESCRIÇÃO: API REST para atualizar a quantidade de um
 *            item no carrinho. Recebe JSON com token,
 *            item_id e quantidade.
 *            Retorna JSON com status e dados atualizados.
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
    'item' => null,
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

    if (empty($dados['item_id']) || !is_numeric($dados['item_id'])) {
        throw new Exception('ID do item é obrigatório e deve ser numérico.');
    }

    if (!isset($dados['quantidade']) || !is_numeric($dados['quantidade'])) {
        throw new Exception('Quantidade é obrigatória e deve ser numérica.');
    }

    $token = trim($dados['token']);
    $itemId = (int)$dados['item_id'];
    $quantidade = (int)$dados['quantidade'];

    // Quantidade não pode ser menor que 1
    if ($quantidade < 1) {
        throw new Exception('A quantidade mínima é 1. Para remover o item, utilize a API de remoção.');
    }

    // Obter conexão com o banco
    $conexao = obterConexao();

    if ($conexao === null) {
        throw new Exception('Falha na conexão com o banco de dados.');
    }

    // Buscar o carrinho ativo para o token
    $sqlCarrinho = "SELECT id FROM carrinhos WHERE token = ? AND status = 'ABERTO' ORDER BY id DESC LIMIT 1";
    $stmtCarrinho = $conexao->prepare($sqlCarrinho);
    $stmtCarrinho->bind_param("s", $token);
    $stmtCarrinho->execute();
    $resultadoCarrinho = $stmtCarrinho->get_result();
    $carrinho = $resultadoCarrinho->fetch_assoc();

    if (!$carrinho) {
        throw new Exception('Carrinho não encontrado ou já finalizado.');
    }

    $carrinhoId = $carrinho['id'];
    $stmtCarrinho->close();

    // Verificar se o item pertence ao carrinho e obter dados atuais
    $sqlVerificar = "SELECT ci.id, ci.quantidade, ci.valor_unitario, ci.produto_id, p.nome, p.descricao 
                     FROM carrinho_itens ci
                     INNER JOIN produtos p ON ci.produto_id = p.id
                     WHERE ci.id = ? AND ci.carrinho_id = ? LIMIT 1";
    $stmtVerificar = $conexao->prepare($sqlVerificar);
    $stmtVerificar->bind_param("ii", $itemId, $carrinhoId);
    $stmtVerificar->execute();
    $resultadoVerificar = $stmtVerificar->get_result();
    $item = $resultadoVerificar->fetch_assoc();

    if (!$item) {
        throw new Exception('Item não encontrado no carrinho.');
    }
    $stmtVerificar->close();

    // Atualizar a quantidade
    $sqlAtualizar = "UPDATE carrinho_itens SET quantidade = ? WHERE id = ? AND carrinho_id = ?";
    $stmtAtualizar = $conexao->prepare($sqlAtualizar);
    $stmtAtualizar->bind_param("iii", $quantidade, $itemId, $carrinhoId);
    
    if (!$stmtAtualizar->execute()) {
        throw new Exception('Erro ao atualizar quantidade: ' . $stmtAtualizar->error);
    }
    $stmtAtualizar->close();

    // Calcular novo subtotal do item
    $valorUnitario = (float)$item['valor_unitario'];
    $subtotalItem = $valorUnitario * $quantidade;

    // Obter a quantidade total de itens no carrinho
    $sqlTotalItens = "SELECT SUM(quantidade) as total FROM carrinho_itens WHERE carrinho_id = ?";
    $stmtTotal = $conexao->prepare($sqlTotalItens);
    $stmtTotal->bind_param("i", $carrinhoId);
    $stmtTotal->execute();
    $resultadoTotal = $stmtTotal->get_result();
    $totalItens = $resultadoTotal->fetch_assoc()['total'] ?? 0;
    $stmtTotal->close();

    $conexao->close();

    // Montar resposta de sucesso
    $resposta['sucesso'] = true;
    $resposta['mensagem'] = 'Quantidade atualizada com sucesso.';
    $resposta['item'] = [
        'id' => (int)$item['id'],
        'produto_id' => (int)$item['produto_id'],
        'nome' => $item['nome'],
        'quantidade' => $quantidade,
        'valor_unitario' => $valorUnitario,
        'subtotal' => $subtotalItem
    ];
    $resposta['quantidade_total'] = (int)$totalItens;

} catch (Exception $e) {
    http_response_code(400);
    $resposta['mensagem'] = $e->getMessage();
    error_log("Erro em carrinho_atualizar.php: " . $e->getMessage());
}

// Retornar resposta JSON
echo json_encode($resposta, JSON_UNESCAPED_UNICODE);
exit;