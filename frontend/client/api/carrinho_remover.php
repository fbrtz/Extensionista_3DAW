<?php
/**
 * =====================================================
 * ARQUIVO: carrinho_remover.php
 * CAMINHO: /api/carrinho_remover.php
 * DESCRIÇÃO: API REST para remover um item do carrinho.
 *            Recebe JSON com token e item_id.
 *            Retorna JSON com status e quantidade total atualizada.
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

    if (empty($dados['item_id']) || !is_numeric($dados['item_id'])) {
        throw new Exception('ID do item é obrigatório e deve ser numérico.');
    }

    $token = trim($dados['token']);
    $itemId = (int)$dados['item_id'];

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

    // Verificar se o item pertence ao carrinho
    $sqlVerificar = "SELECT id FROM carrinho_itens WHERE id = ? AND carrinho_id = ? LIMIT 1";
    $stmtVerificar = $conexao->prepare($sqlVerificar);
    $stmtVerificar->bind_param("ii", $itemId, $carrinhoId);
    $stmtVerificar->execute();
    $resultadoVerificar = $stmtVerificar->get_result();
    
    if ($resultadoVerificar->num_rows === 0) {
        throw new Exception('Item não encontrado no carrinho.');
    }
    $stmtVerificar->close();

    // Remover o item do carrinho
    $sqlRemover = "DELETE FROM carrinho_itens WHERE id = ? AND carrinho_id = ?";
    $stmtRemover = $conexao->prepare($sqlRemover);
    $stmtRemover->bind_param("ii", $itemId, $carrinhoId);
    
    if (!$stmtRemover->execute()) {
        throw new Exception('Erro ao remover item: ' . $stmtRemover->error);
    }
    $stmtRemover->close();

    // Verificar se ainda existem itens no carrinho
    $sqlTotalItens = "SELECT SUM(quantidade) as total FROM carrinho_itens WHERE carrinho_id = ?";
    $stmtTotal = $conexao->prepare($sqlTotalItens);
    $stmtTotal->bind_param("i", $carrinhoId);
    $stmtTotal->execute();
    $resultadoTotal = $stmtTotal->get_result();
    $totalItens = $resultadoTotal->fetch_assoc()['total'] ?? 0;
    $stmtTotal->close();

    // Se não houver mais itens, marca carrinho como abandonado
    if ($totalItens == 0) {
        $sqlAbandonar = "UPDATE carrinhos SET status = 'ABANDONADO' WHERE id = ?";
        $stmtAbandonar = $conexao->prepare($sqlAbandonar);
        $stmtAbandonar->bind_param("i", $carrinhoId);
        $stmtAbandonar->execute();
        $stmtAbandonar->close();
    }

    $conexao->close();

    // Montar resposta de sucesso
    $resposta['sucesso'] = true;
    $resposta['mensagem'] = 'Item removido do carrinho com sucesso.';
    $resposta['quantidade_total'] = (int)$totalItens;

} catch (Exception $e) {
    http_response_code(400);
    $resposta['mensagem'] = $e->getMessage();
    error_log("Erro em carrinho_remover.php: " . $e->getMessage());
}

// Retornar resposta JSON
echo json_encode($resposta, JSON_UNESCAPED_UNICODE);
exit;