<?php
session_start();
require 'includes/conexao.php';

// 1. Busca todas as categorias cadastradas para montar os botões de filtro
$categorias = $pdo->query("SELECT id, nome FROM categoria ORDER BY id")->fetchAll(PDO::FETCH_ASSOC);

// 2. Verifica se o usuário clicou em alguma categoria específica na URL (?categoria=1)
$categoria_filtro = isset($_GET['categoria']) && is_numeric($_GET['categoria']) ? (int) $_GET['categoria'] : 0;

// 3. Busca os produtos ativos no banco (filtrados ou todos)
if ($categoria_filtro > 0) {
    $stmt = $pdo->prepare("
        SELECT p.*, c.nome AS categoria_nome 
        FROM produto p
        INNER JOIN categoria c ON p.id_categoria = c.id
        WHERE p.ativo = 1 AND p.id_categoria = ?
        ORDER BY p.nome
    ");
    $stmt->execute([$categoria_filtro]);
} else {
    $stmt = $pdo->query("
        SELECT p.*, c.nome AS categoria_nome 
        FROM produto p
        INNER JOIN categoria c ON p.id_categoria = c.id
        WHERE p.ativo = 1
        ORDER BY c.id, p.nome
    ");
}
$produtos = $stmt->fetchAll(PDO::FETCH_ASSOC);

// 4. Agrupa os produtos por categoria para exibir em seções organizadas
$produtos_por_categoria = [];
foreach ($produtos as $prod) {
    $produtos_por_categoria[$prod['categoria_nome']][] = $prod;
}
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>LARICA BURGER | Cardápio</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <style>
        .filtros-categoria {
            display: flex;
            justify-content: center;
            flex-wrap: wrap;
            gap: 10px;
            margin: 20px 0 35px 0;
        }
        .btn-filtro {
            padding: 8px 18px;
            border-radius: 20px;
            text-decoration: none;
            font-weight: 600;
            font-size: 0.9rem;
            background-color: #e5e7eb;
            color: #1f2937;
            text-transform: capitalize;
            transition: all 0.2s;
        }
        .btn-filtro:hover, .btn-filtro.ativo {
            background-color: #d97706;
            color: #ffffff;
        }
        .categoria-titulo {
            text-transform: uppercase;
            margin-top: 35px;
            margin-bottom: 15px;
            border-bottom: 2px solid #d97706;
            display: inline-block;
            padding-bottom: 5px;
        }
        .form-add-carrinho {
            display: flex;
            align-items: center;
            gap: 8px;
            margin-top: 10px;
        }
        .input-qtd {
            width: 55px !important;
            padding: 6px !important;
            text-align: center;
        }
    </style>
</head>
<body>

    <?php include 'includes/header.php'; ?>

    <section class="menu-section">
        <h2>CARDÁPIO LARICA</h2>

        <!-- Barra de Filtros Dinâmicos por Categoria -->
        <div class="filtros-categoria">
            <a href="cardapio.php" class="btn-filtro <?php echo ($categoria_filtro === 0) ? 'ativo' : ''; ?>">
                Todos
            </a>
            <?php foreach ($categorias as $cat): ?>
                <a href="cardapio.php?categoria=<?php echo $cat['id']; ?>" 
                   class="btn-filtro <?php echo ($categoria_filtro === (int)$cat['id']) ? 'ativo' : ''; ?>">
                    <?php echo htmlspecialchars($cat['nome']); ?>
                </a>
            <?php endforeach; ?>
        </div>

        <?php if (empty($produtos_por_categoria)): ?>
            <p style="text-align: center; color: #6b7280; margin: 40px 0;">
                Nenhum produto disponível nesta categoria no momento.
            </p>
        <?php else: ?>
            <?php foreach ($produtos_por_categoria as $nome_categoria => $itens): ?>
                <div style="text-align: center;">
                    <h3 class="categoria-titulo"><?php echo htmlspecialchars($nome_categoria); ?></h3>
                </div>

                <div class="cards-container">
                    <?php foreach ($itens as $item): ?>
                        <div class="card">
                            <img src="<?php echo htmlspecialchars($item['url_imagem']); ?>" 
                                 alt="<?php echo htmlspecialchars($item['nome']); ?>" 
                                 width="260" height="180"
                                 onerror="this.src='img/sem_foto.jpg';">
    
                            <h3><?php echo htmlspecialchars($item['nome']); ?></h3>
                            <p><?php echo htmlspecialchars($item['descricao']); ?></p>
                            
                            <div class="card-footer">
                                <span class="price">R$ <?php echo number_format($item['preco_venda'], 2, ',', '.'); ?></span>
                                
                                <!-- Formulário preparado para enviar o item ao carrinho -->
                                <form action="carrinho.php" method="POST" class="form-add-carrinho">
                                    <input type="hidden" name="acao" value="adicionar">
                                    <input type="hidden" name="id_produto" value="<?php echo $item['id']; ?>">
                                    <input type="number" name="quantidade" value="1" min="1" max="20" class="input-qtd" title="Quantidade">
                                    <button type="submit" class="btn-buy" style="width: auto; padding: 8px 14px;">Adicionar</button>
                                </form>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </section>

    <footer class="footer">
        <p><strong>Atendimento de Terça a Domingo, das 18h às 23h30.</strong></p>
        <p><strong>Endereço:</strong> Av. Rio Branco, 193 - Bairro do Recife (Recife Antigo)</p>
        <p><strong>Cidade / Estado:</strong> Recife - PE - <strong>CEP:</strong> 50030-320</p>
        <p><strong>Telefone:</strong> (81) 99658-5753</p>  
    </footer>

</body>
</html>
