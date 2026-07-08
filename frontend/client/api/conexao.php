<?php
/**
 * =====================================================
 * ARQUIVO: conexao.php
 * CAMINHO: /api/conexao.php
 * DESCRIÇÃO: Estabelece conexão com o banco de dados MySQL
 *            utilizando mysqli. Fornece função para obter
 *            a conexão em outras APIs.
 * =====================================================
 */

// Previne acesso direto ao arquivo (opcional, mas seguro)
// As credenciais abaixo devem ser mantidas em local seguro
// AJUSTAR CREDENCIAIS AQUI se necessário
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'oma_kasten');

/**
 * Obtém uma conexão ativa com o banco de dados.
 *
 * @return mysqli|null Retorna o objeto mysqli em caso de sucesso ou null em caso de falha.
 */
function obterConexao(): ?mysqli
{
    // Relatório de erros para mysqli
    mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

    try {
        $conexao = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
        $conexao->set_charset("utf8mb4");
        return $conexao;
    } catch (mysqli_sql_exception $e) {
        // Em produção, logar o erro em vez de exibir
        error_log("Erro de conexão com MySQL: " . $e->getMessage());
        return null;
    }
}