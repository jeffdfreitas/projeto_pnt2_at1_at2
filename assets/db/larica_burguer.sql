-- 1. RECRIA O BANCO LIMPO MANTENDO O NOME ORIGINAL DO conexao.php
DROP DATABASE IF EXISTS larica_burguer;
CREATE DATABASE larica_burguer CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;
USE larica_burguer;

-- ==========================================================
-- ESTRUTURA DAS TABELAS (DDL)
-- ==========================================================

CREATE TABLE tipo_usuario (
    id INT AUTO_INCREMENT PRIMARY KEY,
    descricao VARCHAR(50) NOT NULL UNIQUE
);

CREATE TABLE usuario (
    id INT AUTO_INCREMENT PRIMARY KEY,
    email VARCHAR(150) NOT NULL UNIQUE,
    senha VARCHAR(255) NOT NULL,
    id_tipo INT NOT NULL,
    data_cadastro TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_usuario_tipo FOREIGN KEY (id_tipo) REFERENCES tipo_usuario(id)
);

CREATE TABLE cargo (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nome VARCHAR(50) NOT NULL UNIQUE
);

CREATE TABLE funcionario (
    id INT AUTO_INCREMENT PRIMARY KEY,
    id_usuario INT NOT NULL,
    id_cargo INT NOT NULL,
    nome VARCHAR(100) NOT NULL,
    cpf CHAR(11) NOT NULL UNIQUE,
    CONSTRAINT fk_func_usuario FOREIGN KEY (id_usuario) REFERENCES usuario(id),
    CONSTRAINT fk_func_cargo FOREIGN KEY (id_cargo) REFERENCES cargo(id)
);

CREATE TABLE cliente (
    id INT AUTO_INCREMENT PRIMARY KEY,
    id_usuario INT NOT NULL,
    nome VARCHAR(150) NOT NULL,
    cpf CHAR(11) NOT NULL UNIQUE,
    data_nascimento DATE NOT NULL,
    CONSTRAINT fk_cliente_usuario FOREIGN KEY (id_usuario) REFERENCES usuario(id)
);

CREATE TABLE endereco (
    id INT AUTO_INCREMENT PRIMARY KEY,
    logradouro VARCHAR(100) NOT NULL,
    nr VARCHAR(10) NOT NULL,
    complemento VARCHAR(50),
    bairro VARCHAR(50) NOT NULL,
    cidade VARCHAR(50) NOT NULL,
    estado CHAR(2) NOT NULL,
    id_cliente INT NULL,
    id_funcionario INT NULL,
    CONSTRAINT fk_end_cliente FOREIGN KEY (id_cliente) REFERENCES cliente(id),
    CONSTRAINT fk_end_func FOREIGN KEY (id_funcionario) REFERENCES funcionario(id)
);

CREATE TABLE telefone (
    id INT AUTO_INCREMENT PRIMARY KEY,
    numero VARCHAR(15) NOT NULL,
    id_cliente INT NULL,
    id_funcionario INT NULL,
    CONSTRAINT fk_tel_cliente FOREIGN KEY (id_cliente) REFERENCES cliente(id),
    CONSTRAINT fk_tel_func FOREIGN KEY (id_funcionario) REFERENCES funcionario(id)
);

CREATE TABLE categoria (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nome VARCHAR(50) NOT NULL UNIQUE
);

CREATE TABLE produto (
    id INT AUTO_INCREMENT PRIMARY KEY,
    id_categoria INT NOT NULL,
    nome VARCHAR(100) NOT NULL,
    descricao TEXT,
    preco_venda DECIMAL(10,2) NOT NULL,
    url_imagem VARCHAR(255) DEFAULT 'img/sem_foto.jpg',
    ativo BOOLEAN DEFAULT TRUE,
    CONSTRAINT fk_produto_categoria FOREIGN KEY (id_categoria) REFERENCES categoria(id)
);

CREATE TABLE forma_pagamento (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nome VARCHAR(50) NOT NULL UNIQUE
);

CREATE TABLE pedido (
    id INT AUTO_INCREMENT PRIMARY KEY,
    id_cliente INT NOT NULL,
    id_funcionario INT NULL,
    data_pedido TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    status_pedido VARCHAR(30) DEFAULT 'solicitado',
    valor_total DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    CONSTRAINT fk_pedido_cliente FOREIGN KEY (id_cliente) REFERENCES cliente(id),
    CONSTRAINT fk_pedido_func FOREIGN KEY (id_funcionario) REFERENCES funcionario(id)
);

CREATE TABLE item_pedido (
    id INT AUTO_INCREMENT PRIMARY KEY,
    id_pedido INT NOT NULL,
    id_produto INT NOT NULL,
    quantidade INT NOT NULL,
    preco_unitario DECIMAL(10,2) NOT NULL,
    CONSTRAINT fk_item_pedido FOREIGN KEY (id_pedido) REFERENCES pedido(id),
    CONSTRAINT fk_item_produto FOREIGN KEY (id_produto) REFERENCES produto(id)
);

CREATE TABLE pagamento (
    id INT AUTO_INCREMENT PRIMARY KEY,
    id_pedido INT NOT NULL,
    id_forma_pagamento INT NOT NULL,
    valor_pago DECIMAL(10,2) NOT NULL,
    data_pagamento TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_pag_pedido FOREIGN KEY (id_pedido) REFERENCES pedido(id),
    CONSTRAINT fk_pag_forma FOREIGN KEY (id_forma_pagamento) REFERENCES forma_pagamento(id)
);

-- ==========================================================
-- CARGA INICIAL DE DADOS PADRONIZADOS (DML)
-- ==========================================================

INSERT INTO tipo_usuario (id, descricao) VALUES 
(1, 'cliente'),
(2, 'funcionario');

INSERT INTO cargo (id, nome) VALUES 
(1, 'chapeiro'),
(2, 'gerente');

INSERT INTO categoria (id, nome) VALUES 
(1, 'burguer clássico'),
(2, 'burguer veggie'),
(3, 'snacks'),
(4, 'bebidas');

INSERT INTO forma_pagamento (id, nome) VALUES 
(1, 'pix'),
(2, 'credito'),
(3, 'debito');

-- Usuário Admin Único (Login: admin@laricaburger.com | Senha: 123456)
INSERT INTO usuario (id, email, senha, id_tipo) VALUES 
(1, 'admin@laricaburger.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 2);

INSERT INTO funcionario (id, id_usuario, id_cargo, nome, cpf) VALUES 
(1, 1, 2, 'Administrador Larica', '11122233344');

-- Produtos Iniciais do Cardápio (ajustados com extensões de imagem padrão)
INSERT INTO produto (id_categoria, nome, descricao, preco_venda, url_imagem, ativo) VALUES
(1, 'Cheddar Bacon', 'Blend 150g, muito cheddar cremoso, bacon crocante e maionese da casa no pão brioche.', 29.90, 'img/img_clas_cheddar.jpg', 1),
(1, 'Double Smash', 'Dois blends smash de 80g prensados na chapa, queijo prato, alface, tomate e molho especial.', 34.90, 'img/img_clas_smash.jpg', 1),
(1, 'Chicken Salad', 'Classic Burger com três fatias de pão, Frango Empanado Crocante e salada de alface, tomate e cebola.', 29.90, 'img/img_clas_chicken.jpg', 1),
(2, 'Grão-de-bico e Tomate Seco', 'Hamburguer de Grão-de-bico com tomate seco e manjericão, creme de abacate e queijo tofu derretido no pão brioche.', 34.90, 'img/img_veg_grao_bico.jpg', 1),
(2, 'Cogumelos Shitake', 'Hamburguer de cogumelos com textura rica e terrosa, combinada com cebola caramelizada, rúcula fresca, tomate e pão brioche.', 39.90, 'img/img_veg_shitake.jpeg', 1),
(2, 'Beterraba com Arroz Integral', 'Hambúrguer de Beterraba com Arroz Integral, acompanhado de picles de pepino e brotos frescos.', 29.90, 'img/img_veg_beterraba.jpg', 1),
(3, 'Batatas rústicas com alecrim e alho assado', 'Batatas cortadas em gomos com casca, assadas com azeite, alecrim fresco e dentes de alho inteiros.', 19.90, 'img/img_acomp_batata_rustica.jpg', 1),
(3, 'Chips de batata-doce com flor de sal', 'Fatias finas e crocantes de batata-doce finalizadas com cristais de sal grosso.', 26.90, 'img/img_acomp_batata_doce.jpg', 1),
(3, 'Palitos de polenta frita com parmesão', 'Polenta cremosa cortada em bastões, frita até dourar e salpicada com queijo parmesão ralado na hora.', 32.90, 'img/img_acomp_batata_polenta.jpg', 1),
(4, 'Água com Gás', 'Água Mineral com Gás 500ml', 4.80, 'img/img_agua_com_gas.jpg', 1),
(4, 'Água sem Gás', 'Água Mineral sem Gás 500ml', 3.80, 'img/img_agua_sem_gas.jpg', 1),
(4, 'Suco de Laranja', 'Suco de Laranja Del Valle Lata', 7.80, 'img/img_suco_laranja.jpg', 1),
(4, 'Suco de Pêssego', 'Suco de Pêssego Del Valle Lata', 7.80, 'img/img_suco_pessego.jpg', 1),
(4, 'Suco de Uva', 'Suco de Uva Del Valle Lata', 7.80, 'img/img_suco_uva.jpg', 1),
(4, 'Coca-Cola', 'Refrigerante Coca-Cola sabor original Lata 350ml', 6.80, 'img/img_coca_cola_orig.jpg', 1),
(4, 'Coca-Cola Zero', 'Refrigerante Coca-Cola Zero Açúcar Lata 350ml', 6.80, 'img/img_coca_cola_zero.jpg', 1),
(4, 'Guaraná Antarctica', 'Refrigerante Guaraná Antarctica Lata 350ml', 6.80, 'img/img_guarana_orig.jpg', 1),
(4, 'Soda Zero', 'Refrigerante Soda Limonada Zero Açúcar Lata 350ml', 6.80, 'img/img_soda_zero.jpg', 1);

UPDATE usuario 
SET senha = '$2y$10$wT.4jZ9V0fX5U1qJ8h2n8e9Z7x6c5v4b3n2m1a0s9d8f7g6h5j4k2' 
WHERE email = 'admin@laricaburger.com';

CREATE TABLE mensagem_contato (
    id INT AUTO_INCREMENT PRIMARY KEY,
    assunto VARCHAR(50) NOT NULL,
    mensagem TEXT NOT NULL,
    lida INT DEFAULT 0,
    data_envio TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);


