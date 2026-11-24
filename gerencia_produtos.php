<?php
session_start();
require 'includes/conexao.php';

// Proteção: somente usuário funcionário (id_tipo == 2) pode acessar
if (!isset($_SESSION['usuario_id']) || $_SESSION['usuario_tipo'] != 2) {
    header("Location: login.php");
    exit;
}

$mensagem = "";

// 1. PROCESSAR CADASTRO DE PRODUTO
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['acao']) && $_POST['acao'] === 'cadastrar') {
    $id_categoria = $_POST['id_categoria'];
    $nome = trim($_POST['nome']);
    $descricao = trim($_POST['descricao']);
    $preco_venda = str_replace(',', '.', $_POST['preco_venda']);
    $url_imagem = "img/sem_foto.jpg";

    // Upload da foto do produto
    if (isset($_FILES['imagem']) && $_FILES['imagem']['error'] === UPLOAD_ERR_OK) {
        $pasta_destino = "img/";
        if (!is_dir($pasta_destino)) {
            mkdir($pasta_destino, 0777, true);
        }

        $extensao = strtolower(pathinfo($_FILES['imagem']['name'], PATHINFO_EXTENSION));
        $permitidas = ['jpg', 'jpeg', 'png', 'webp'];

        if (in_array($extensao, $permitidas)) {
            $novo_nome = uniqid("prod_") . "." . $extensao;
            $caminho_completo = $pasta_destino . $novo_nome;

            if (move_uploaded_file($_FILES['imagem']['tmp_name'], $caminho_completo)) {
                $url_imagem = $caminho_completo;
            }
        } else {
            $mensagem = "<p style='color: red;'>Erro: Use apenas imagens JPG, PNG ou WEBP.</p>";
        }
    }

    if (empty($mensagem)) {
        try {
            $stmt = $pdo->prepare("INSERT INTO produto (id_categoria, nome, descricao, preco_venda, url_imagem, ativo) VALUES (?, ?, ?, ?, ?, 1)");
            $stmt->execute([$id_categoria, $nome, $descricao, $preco_venda, $url_imagem]);
            $mensagem = "<p style='color: green;'>Produto <strong>" . htmlspecialchars($nome) . "</strong> cadastrado com sucesso!</p>";
        } catch (Exception $e) {
            $mensagem = "<p style='color: red;'>Erro no banco de dados: " . $e->getMessage() . "</p>";
        }
    }
}

// 2. ATIVAR / DESATIVAR PRODUTO
if (isset($_GET['toggle_id'])) {
    $id_produto = (int) $_GET['toggle_id'];
    $stmtToggle = $pdo->prepare("UPDATE produto SET ativo = NOT ativo WHERE id = ?");
    $stmtToggle->execute([$id_produto]);
    header("Location: gerencia_produtos.php");
    exit;
}

// 3. CONSULTAR CATEGORIAS E PRODUTOS EXISTENTES
$categorias = $pdo->query("SELECT id, nome FROM categoria ORDER BY id")->fetchAll(PDO::FETCH_ASSOC);

$produtos = $pdo->query("
    SELECT p.*, c.nome AS categoria_nome 
    FROM produto p
    INNER JOIN categoria c ON p.id_categoria = c.id
    ORDER BY c.id, p.nome
")->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>LARICA BURGER | Gerenciar Produtos</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <style>
        .admin-wrapper {
            max-width: 960px;
            margin: 30px auto;
            padding: 25px;
            background: #fff;
            color: #1f2937; /* Cor escuras dos textos */
            border-radius: 10px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.08);
        }
        .admin-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            border-bottom: 2px solid #f3f4f6;
            padding-bottom: 15px;
        }
        .form-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 15px;
        }
        .form-grid .full-width {
            grid-column: span 2;
        }
        textarea, select {
            width: 100%;
            padding: 0.75rem;
            border: 1px solid #d1d5db;
            border-radius: 6px;
            box-sizing: border-box;
            font-family: inherit;
        }
        .capitalize {
            text-transform: capitalize;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 25px;
        }
        th, td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #e5e7eb;
        }
        th {
            background-color: #f9fafb;
            font-weight: 600;
        }
        .thumb-prod {
            width: 60px;
            height: 45px;
            object-fit: cover;
            border-radius: 6px;
        }
        .badge-ativo {
            background: #dcfce7;
            color: #166534;
            padding: 4px 8px;
            border-radius: 12px;
            font-size: 0.8rem;
            font-weight: bold;
        }
        .badge-inativo {
            background: #fee2e2;
            color: #991b1b;
            padding: 4px 8px;
            border-radius: 12px;
            font-size: 0.8rem;
            font-weight: bold;
        }
    </style>
</head>
<body>

    <?php include 'includes/header.php'; ?>

    <main class="admin-wrapper">
        <div class="admin-header">
            <h2>Cadastro e Gestão de Produtos</h2>
            <a href="dashboard_funcionario.php" class="btn" style="width: auto; padding: 8px 16px; text-decoration: none;">Painel de Pedidos</a>
        </div>

        <?php if (!empty($mensagem)) echo $mensagem; ?>

        <!-- FORMULÁRIO DE CADASTRO -->
        <form action="gerencia_produtos.php" method="POST" enctype="multipart/form-data">
            <input type="hidden" name="acao" value="cadastrar">
            
            <div class="form-grid">
                <div>
                    <label for="nome">Nome do Produto</label>
                    <input type="text" id="nome" name="nome" placeholder="Ex: Cheddar Bacon, Coca-Cola Lata..." required>
                </div>
    
                <div>
                    <label for="id_categoria">Categoria</label>
                    <select id="id_categoria" name="id_categoria" class="capitalize" required>
                        <option value="">Selecione...</option>
                        <?php foreach ($categorias as $cat): ?>
                            <option value="<?php echo $cat['id']; ?>" class="capitalize">
                                <?php echo htmlspecialchars($cat['nome']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div>
                    <label for="preco_venda">Preço de Venda (R$)</label>
                    <input type="number" id="preco_venda" name="preco_venda" step="0.01" min="0" placeholder="Ex: 34.90" required>
                </div>

                <div>
                    <label for="imagem">Foto do Produto (Opcional)</label>
                    <input type="file" id="imagem" name="imagem" accept=".jpg,.jpeg,.png,.webp">
                </div>

                <div class="full-width">
                    <label for="descricao">Descrição / Ingredientes</label>
                    <textarea id="descricao" name="descricao" rows="2" placeholder="Descreva os ingredientes ou tamanho..."></textarea>
                </div>
            </div>

            <button type="submit" class="btn btn-enviar" style="margin-top: 15px;">Salvar Produto</button>
        </form>

        <!-- TABELA DE PRODUTOS -->
        <h3 style="margin-top: 40px;">Cardápio Cadastrado (<?php echo count($produtos); ?> itens)</h3>
        <table>
            <thead>
                <tr>
                    <th>Foto</th>
                    <th>Produto</th>
                    <th>Categoria</th>
                    <th>Preço</th>
                    <th>Status</th>
                    <th>Ação</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($produtos)): ?>
                    <tr>
                        <td colspan="6" style="text-align: center; color: #6b7280;">Nenhum produto cadastrado no banco ainda.</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($produtos as $prod): ?>
                        <tr>
                            <td>
                                <img src="<?php echo htmlspecialchars($prod['url_imagem']); ?>" alt="Foto" class="thumb-prod">
                            </td>
                            <td>
                                <strong><?php echo htmlspecialchars($prod['nome']); ?></strong><br>
                                <small style="color: #6b7280;"><?php echo htmlspecialchars($prod['descricao']); ?></small>
                            </td>
                            <td class="capitalize"><?php echo htmlspecialchars($prod['categoria_nome']); ?></td>
                            <td>R$ <?php echo number_format($prod['preco_venda'], 2, ',', '.'); ?></td>
                            <td>
                                <?php if ($prod['ativo']): ?>
                                    <span class="badge-ativo">Disponível</span>
                                <?php else: ?>
                                    <span class="badge-inativo">Indisponível</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <a href="gerencia_produtos.php?toggle_id=<?php echo $prod['id']; ?>" style="font-size: 0.85rem; color: #2563eb; text-decoration: none; font-weight: 600;">
                                    <?php echo $prod['ativo'] ? 'Desativar' : 'Ativar'; ?>
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </main>

    <footer class="footer">
        <p><strong>Atendimento de Terça a Domingo, das 18h às 23h30.</strong></p>
        <p><strong>Endereço:</strong> Av. Rio Branco, 193 - Bairro do Recife (Recife Antigo)</p>
