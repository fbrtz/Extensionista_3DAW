<?php
/**
 * =====================================================
 * ARQUIVO: carrinho_finalizar.php (CORRIGIDO)
 * CAMINHO: /api/carrinho_finalizar.php
 * DESCRIÇÃO: API REST para finalizar o carrinho e gerar
 *            a mensagem para envio via WhatsApp.
 *            Recebe JSON com token do carrinho.
 *            Retorna JSON com a mensagem formatada e dados
 *            do pedido.
 * =====================================================
 */

// Habilitar exibição de erros apenas para debug (remover em produção)
// ini_set('display_errors', 0);
// error_reporting(0);

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
    'texto_whatsapp' => '',
    'resumo' => [
        'subtotal' => 0,
        'quantidade_total' => 0
    ]
];

// Bloco try-catch para capturar qualquer erro
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

    $token = trim($dados['token']);

    // Obter conexão com o banco
    $conexao = obterConexao();

    if ($conexao === null) {
        throw new Exception('Falha na conexão com o banco de dados.');
    }

    // Buscar o carrinho ativo
    $sqlCarrinho = "SELECT id FROM carrinhos WHERE token = ? AND status = 'ABERTO' ORDER BY id DESC LIMIT 1";
    $stmtCarrinho = $conexao->prepare($sqlCarrinho);
    
    if (!$stmtCarrinho) {
        throw new Exception('Erro na preparação da consulta: ' . $conexao->error);
    }
    
    $stmtCarrinho->bind_param("s", $token);
    $stmtCarrinho->execute();
    $resultadoCarrinho = $stmtCarrinho->get_result();
    $carrinho = $resultadoCarrinho->fetch_assoc();
    $stmtCarrinho->close();

    if (!$carrinho) {
        throw new Exception('Carrinho não encontrado ou já finalizado.');
    }

    $carrinhoId = $carrinho['id'];

    // Buscar todos os itens do carrinho com dados do produto
    $sqlItens = "SELECT 
                    ci.id,
                    ci.quantidade,
                    ci.valor_unitario,
                    p.nome,
                    p.descricao
                 FROM carrinho_itens ci
                 INNER JOIN produtos p ON ci.produto_id = p.id
                 WHERE ci.carrinho_id = ?
                 ORDER BY ci.id ASC";
    
    $stmtItens = $conexao->prepare($sqlItens);
    
    if (!$stmtItens) {
        throw new Exception('Erro na preparação da consulta de itens: ' . $conexao->error);
    }
    
    $stmtItens->bind_param("i", $carrinhoId);
    $stmtItens->execute();
    $resultadoItens = $stmtItens->get_result();

    $itens = [];
    $subtotal = 0;
    $quantidadeTotal = 0;

    while ($item = $resultadoItens->fetch_assoc()) {
        $valorUnitario = (float)$item['valor_unitario'];
        $quantidade = (int)$item['quantidade'];
        $subtotalItem = $valorUnitario * $quantidade;
        
        $itens[] = [
            'nome' => $item['nome'],
            'quantidade' => $quantidade,
            'valor_unitario' => $valorUnitario,
            'subtotal' => $subtotalItem
        ];

        $subtotal += $subtotalItem;
        $quantidadeTotal += $quantidade;
    }

    $stmtItens->close();

    // Verificar se há itens no carrinho
    if (empty($itens)) {
        throw new Exception('O carrinho está vazio. Adicione itens antes de finalizar.');
    }

    // Montar a mensagem para WhatsApp
    $mensagem = "Olá!\n\n";
    $mensagem .= "Gostaria de realizar o seguinte pedido:\n\n";

    foreach ($itens as $item) {
        $valorFormatado = number_format($item['valor_unitario'], 2, ',', '.');
        $mensagem .= $item['nome'] . "\n";
        $mensagem .= $item['quantidade'] . " x R$" . $valorFormatado . "\n\n";
    }

    $mensagem .= "----------------------------\n\n";
    $mensagem .= "Subtotal:\n";
    $mensagem .= "R$" . number_format($subtotal, 2, ',', '.') . "\n\n";
    $mensagem .= "Total de itens:\n";
    $mensagem .= $quantidadeTotal . "\n\n";
    $mensagem .= "----------------------------\n\n";
    $mensagem .= "Aguardo confirmação.";

    // Atualizar o status do carrinho para FINALIZADO
    $sqlFinalizar = "UPDATE carrinhos SET status = 'FINALIZADO' WHERE id = ?";
    $stmtFinalizar = $conexao->prepare($sqlFinalizar);
    
    if ($stmtFinalizar) {
        $stmtFinalizar->bind_param("i", $carrinhoId);
        $stmtFinalizar->execute();
        $stmtFinalizar->close();
    }

    $conexao->close();

    // Montar resposta de sucesso
    $resposta['sucesso'] = true;
    $resposta['mensagem'] = 'Pedido finalizado com sucesso!';
    $resposta['texto_whatsapp'] = $mensagem;
    $resposta['resumo'] = [
        'subtotal' => $subtotal,
        'quantidade_total' => $quantidadeTotal
    ];

} catch (Exception $e) {
    http_response_code(400);
    $resposta['mensagem'] = $e->getMessage();
    // Log do erro real para debug
    error_log("Erro em carrinho_finalizar.php: " . $e->getMessage());
}

// Garantir que não há nenhuma saída antes do JSON
// Limpar qualquer buffer de saída
if (ob_get_length()) {
    ob_clean();
}

// Retornar resposta JSON
echo json_encode($resposta, JSON_UNESCAPED_UNICODE);
exit;