DROP DATABASE IF EXISTS oma_kasten;

CREATE DATABASE oma_kasten;


USE oma_kasten;



-- TABELA: usuarios (admins do painel)

CREATE TABLE usuarios (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nome VARCHAR(100) NOT NULL,
    senha VARCHAR(255) NOT NULL,
    criado_em DATETIME DEFAULT CURRENT_TIMESTAMP,
    atualizado_em DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);



-- TABELA: unidades_medida (fatia, unidade, kg, litro...)

CREATE TABLE unidades_medida (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nome VARCHAR(100) NOT NULL,
    abreviacao VARCHAR(20),
    criado_em DATETIME DEFAULT CURRENT_TIMESTAMP
);



-- TABELA: produtos (itens do cardápio)

CREATE TABLE produtos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nome VARCHAR(150) NOT NULL,
    descricao TEXT,
    valor DECIMAL(10,2) NOT NULL,
    imagem VARCHAR(500),
    unidade_medida_id INT,
    criado_em DATETIME DEFAULT CURRENT_TIMESTAMP,
    atualizado_em DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    CONSTRAINT fk_produto_unidade_medida
        FOREIGN KEY (unidade_medida_id)
        REFERENCES unidades_medida(id)
        ON UPDATE CASCADE
        ON DELETE SET NULL
);



-- TABELA: contatos (formulário de contato enviado)

CREATE TABLE contatos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nome VARCHAR(150) NOT NULL,
    email VARCHAR(150),
    telefone VARCHAR(20),
    mensagem TEXT,
    enviado_em DATETIME DEFAULT CURRENT_TIMESTAMP
);



-- =====================================================
-- MÓDULO: CARRINHO DE COMPRAS - OMA KASTEN
-- BANCO: MySQL
-- DESCRIÇÃO: Criação das tabelas para gerenciamento
--            do carrinho de compras.
-- =====================================================

-- Utilizar o banco de dados existente
-- USE oma_kasten;

-- =====================================================
-- Tabela: carrinhos
-- Armazena os carrinhos criados para cada visitante
-- =====================================================
CREATE TABLE IF NOT EXISTS carrinhos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    token VARCHAR(64) NOT NULL UNIQUE,
    status ENUM('ABERTO', 'FINALIZADO', 'ABANDONADO') NOT NULL DEFAULT 'ABERTO',
    data_criacao DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    ultima_atualizacao DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_token (token),
    INDEX idx_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- Tabela: carrinho_itens
-- Armazena os itens adicionados em cada carrinho
-- =====================================================
CREATE TABLE IF NOT EXISTS carrinho_itens (
    id INT AUTO_INCREMENT PRIMARY KEY,
    carrinho_id INT NOT NULL,
    produto_id INT NOT NULL,
    quantidade INT NOT NULL DEFAULT 1,
    valor_unitario DECIMAL(10,2) NOT NULL,
    FOREIGN KEY (carrinho_id) REFERENCES carrinhos(id) ON DELETE CASCADE,
    FOREIGN KEY (produto_id) REFERENCES produtos(id) ON DELETE RESTRICT,
    INDEX idx_carrinho (carrinho_id),
    INDEX idx_produto (produto_id),
    UNIQUE KEY uk_carrinho_produto (carrinho_id, produto_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;



-- INSERTS DE TESTE

INSERT INTO usuarios (nome, senha)
VALUES ('Admin Loja', '123456');

INSERT INTO unidades_medida (nome, abreviacao) VALUES
('Unidade', 'un'),
('Fatia', 'fatia'),
('Porção', 'por'),
('Litro', 'L'),
('Quilo', 'kg');

INSERT INTO contatos (nome, email, telefone, mensagem)
VALUES (
    'João da Silva',
    'joao@gmail.com',
    '(11) 99999-8888',
    'Gostaria de saber mais sobre combos para festa.'
);


INSERT INTO produtos (nome, descricao, valor, imagem, unidade_medida_id) VALUES
('Bolo de Chocolate', 'Fatia de bolo caseiro com cobertura cremosa de chocolate.', 12.00, 'doce1.jpg', 2),

('Bolo Red Velvet', 'Fatia de Red Velvet com creme especial de cream cheese.', 14.00, 'doce2.jpg', 2),

('Brownie Tradicional', 'Brownie macio com sabor intenso de chocolate.', 8.00, 'doce3.jpg', 1),

('Cookies de Baunilha', 'Cookies caseiros crocantes com gotas de chocolate.', 5.00, 'doce1.jpg', 1),

('Cupcake de Morango', 'Cupcake macio de baunilha com cobertura de morango.', 7.50, 'doce2.jpg', 1),

('Cupcake de Oreo', 'Cupcake com massa de chocolate e cobertura cremosa com Oreo.', 8.00, 'doce3.jpg', 1),

('Torta de Limão', 'Fatia de torta artesanal de limão com merengue suave.', 13.00, 'doce1.jpg', 2),

('Pudim Tradicional', 'Porção de pudim de leite condensado caseiro.', 9.50, 'doce2.jpg', 3),

('Brigadeiro Gourmet', 'Brigadeiro artesanal feito com chocolate belga.', 3.50, 'doce3.jpg', 1),

('Copo da Felicidade', 'Camadas de mousse, brigadeiro e bolo no pote.', 15.00, 'doce1.jpg', 3),
('Cheesecake de Frutas Vermelhas', 'Fatia de cheesecake com calda artesanal de frutas vermelhas.', 14.50, 'doce2.jpg', 2),

('Trufa de Chocolate Branco', 'Trufa recheada com creme especial de chocolate branco.', 4.50, 'doce3.jpg', 1);
