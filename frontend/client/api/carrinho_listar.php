<?php
/**
 * =====================================================
 * ARQUIVO: carrinho_listar.php (CORRIGIDO)
 * CAMINHO: /api/carrinho_listar.php
 * DESCRIÇÃO: API REST para listar itens do carrinho.
 *            Recebe o token via GET.
 *            Agora busca também carrinhos ABANDONADOS
 *            e os reativa se necessário.
 *            Retorna JSON com itens e resumo do carrinho.
 * =====================================================
 */

// Cabeçalhos obrigatórios
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, OPTIONS");
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
    'carrinho' => null,
    'itens' => [],
    'resumo' => [
        'subtotal' => 0,
        'quantidade_total' => 0
    ]
];

try {
    // Verificar método HTTP
    if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
        throw new Exception('Método não permitido. Utilize GET.');
    }

    // Validar token
    if (empty($_GET['token'])) {
        throw new Exception('Token do carrinho é obrigatório.');
    }

    $token = trim($_GET['token']);

    // Obter conexão com o banco
    $conexao = obterConexao();

    if ($conexao === null) {
        throw new Exception('Falha na conexão com o banco de dados.');
    }

    // Buscar o carrinho mais recente para este token (independente do status)
    // Primeiro tenta ABERTO, depois tenta ABANDONADO (para reativar)
    $sqlCarrinho = "SELECT id, token, status, data_criacao, ultima_atualizacao 
                    FROM carrinhos 
                    WHERE token = ? 
                    ORDER BY 
                        CASE status 
                            WHEN 'ABERTO' THEN 1 
                            WHEN 'ABANDONADO' THEN 2 
                            WHEN 'FINALIZADO' THEN 3 
                        END,
                        id DESC 
                    LIMIT 1";
    
    $stmtCarrinho = $conexao->prepare($sqlCarrinho);
    $stmtCarrinho->bind_param("s", $token);
    $stmtCarrinho->execute();
    $resultadoCarrinho = $stmtCarrinho->get_result();
    $carrinho = $resultadoCarrinho->fetch_assoc();

    if (!$carrinho) {
        // Retorna carrinho vazio sem erro
        $resposta['sucesso'] = true;
        $resposta['mensagem'] = 'Nenhum carrinho encontrado para este token.';
        echo json_encode($resposta, JSON_UNESCAPED_UNICODE);
        exit;
    }

    // Se o carrinho estiver FINALIZADO, retornar vazio (pedido já foi enviado)
    if ($carrinho['status'] === 'FINALIZADO') {
        $stmtCarrinho->close();
        $resposta['sucesso'] = true;
        $resposta['mensagem'] = 'Carrinho já foi finalizado.';
        echo json_encode($resposta, JSON_UNESCAPED_UNICODE);
        exit;
    }

    // Se o carrinho estiver ABANDONADO, reativá-lo
    if ($carrinho['status'] === 'ABANDONADO') {
        $sqlReativar = "UPDATE carrinhos SET status = 'ABERTO' WHERE id = ?";
        $stmtReativar = $conexao->prepare($sqlReativar);
        $stmtReativar->bind_param("i", $carrinho['id']);
        $stmtReativar->execute();
        $stmtReativar->close();
        $carrinho['status'] = 'ABERTO';
    }

    $stmtCarrinho->close();

    // Buscar itens do carrinho com dados do produto
    $sqlItens = "SELECT 
                    ci.id,
                    ci.quantidade,
                    ci.valor_unitario,
                    ci.produto_id,
                    p.nome,
                    p.descricao,
                    p.imagem,
                    u.nome AS unidade
                 FROM carrinho_itens ci
                 INNER JOIN produtos p ON ci.produto_id = p.id
                 LEFT JOIN unidades_medida u ON p.unidade_medida_id = u.id
                 WHERE ci.carrinho_id = ?
                 ORDER BY ci.id ASC";
    
    $stmtItens = $conexao->prepare($sqlItens);
    $stmtItens->bind_param("i", $carrinho['id']);
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
            'id' => (int)$item['id'],
            'produto_id' => (int)$item['produto_id'],
            'nome' => $item['nome'],
            'descricao' => $item['descricao'],
            'imagem' => $item['imagem'],
            'unidade' => $item['unidade'],
            'quantidade' => $quantidade,
            'valor_unitario' => $valorUnitario,
            'subtotal' => $subtotalItem
        ];

        $subtotal += $subtotalItem;
        $quantidadeTotal += $quantidade;
    }

    $stmtItens->close();
    $conexao->close();

    // Montar resposta de sucesso
    $resposta['sucesso'] = true;
    $resposta['mensagem'] = 'Carrinho listado com sucesso.';
    $resposta['carrinho'] = [
        'id' => (int)$carrinho['id'],
        'token' => $carrinho['token'],
        'status' => $carrinho['status']
    ];
    $resposta['itens'] = $itens;
    $resposta['resumo'] = [
        'subtotal' => $subtotal,
        'quantidade_total' => $quantidadeTotal
    ];

} catch (Exception $e) {
    http_response_code(400);
    $resposta['mensagem'] = $e->getMessage();
    error_log("Erro em carrinho_listar.php: " . $e->getMessage());
}

// Retornar resposta JSON
echo json_encode($resposta, JSON_UNESCAPED_UNICODE);
exit;