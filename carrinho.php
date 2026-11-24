<?php
session_start();
require 'includes/conexao.php';

// Inicializa o array do carrinho na sessão caso ainda não exista
if (!isset($_SESSION['carrinho'])) {
    $_SESSION['carrinho'] = [];
}

$mensagem = "";

// 1. ADICIONAR ITEM AO CARRINHO (vindo do cardapio.php)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['acao']) && $_POST['acao'] === 'adicionar') {
    $id_produto = (int) $_POST['id_produto'];
    $quantidade = max(1, (int) $_POST['quantidade']);

    // Busca os dados reais do produto no banco para garantir preço seguro
    $stmt = $pdo->prepare("SELECT id, nome, preco_venda, url_imagem FROM produto WHERE id = ? AND ativo = 1");
    $stmt->execute([$id_produto]);
    $produto = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($produto) {
        if (isset($_SESSION['carrinho'][$id_produto])) {
            $_SESSION['carrinho'][$id_produto]['quantidade'] += $quantidade;
        } else {
            $_SESSION['carrinho'][$id_produto] = [
                'id' => $produto['id'],
                'nome' => $produto['nome'],
                'preco' => (float) $produto['preco_venda'],
                'quantidade' => $quantidade
            ];
        }
    }
    header("Location: carrinho.php");
    exit;
}

// 2. ATUALIZAR QUANTIDADE OU REMOVER ITEM
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['acao']) && $_POST['acao'] === 'atualizar') {
    $id_produto = (int) $_POST['id_produto'];
    $nova_qtd = (int) $_POST['quantidade'];

    if ($nova_qtd <= 0) {
        unset($_SESSION['carrinho'][$id_produto]);
    } else {
        if (isset($_SESSION['carrinho'][$id_produto])) {
            $_SESSION['carrinho'][$id_produto]['quantidade'] = $nova_qtd;
        }
    }
    header("Location: carrinho.php");
    exit;
}

// 3. ESVAZIAR CARRINHO
if (isset($_GET['limpar'])) {
    $_SESSION['carrinho'] = [];
    header("Location: carrinho.php");
    exit;
}

// 4. FINALIZAR PEDIDO (Grava em pedido, item_pedido e pagamento)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['acao']) && $_POST['acao'] === 'finalizar') {
    // Exige login de Cliente
    if (!isset($_SESSION['usuario_id'])) {
        $mensagem = "<p style='color: #f87171; margin-bottom: 15px;'>Você precisa <a href='login.php' style='color: #ffb703;'>fazer login</a> como cliente para finalizar o pedido.</p>";
    } elseif ($_SESSION['usuario_tipo'] != 1) {
        $mensagem = "<p style='color: #f87171; margin-bottom: 15px;'>Apenas contas de Cliente podem realizar pedidos.</p>";
    } elseif (empty($_SESSION['carrinho'])) {
        $mensagem = "<p style='color: #f87171; margin-bottom: 15px;'>Seu carrinho está vazio.</p>";
    } else {
        $id_forma_pagamento = (int) $_POST['id_forma_pagamento'];

        // Busca o id_cliente vinculado ao usuario_id logado
        $stmtCli = $pdo->prepare("SELECT id FROM cliente WHERE id_usuario = ?");
        $stmtCli->execute([$_SESSION['usuario_id']]);
        $cliente = $stmtCli->fetch(PDO::FETCH_ASSOC);

        if (!$cliente) {
            $mensagem = "<p style='color: #f87171; margin-bottom: 15px;'>Perfil de cliente não encontrado no banco de dados.</p>";
        } else {
            $id_cliente = $cliente['id'];
            $valor_total = 0;

            foreach ($_SESSION['carrinho'] as $item) {
                $valor_total += $item['preco'] * $item['quantidade'];
            }

            try {
                $pdo->beginTransaction();

                // A) Insere o Pedido com status inicial 'solicitado'
                $stmtPedido = $pdo->prepare("INSERT INTO pedido (id_cliente, status_pedido, valor_total) VALUES (?, 'aguardando pagamento', ?)");
                $stmtPedido->execute([$id_cliente, $valor_total]);
                $id_pedido = $pdo->lastInsertId();

                // B) Insere cada item em item_pedido
                $stmtItem = $pdo->prepare("INSERT INTO item_pedido (id_pedido, id_produto, quantidade, preco_unitario) VALUES (?, ?, ?, ?)");
                foreach ($_SESSION['carrinho'] as $item) {
                    $stmtItem->execute([$id_pedido, $item['id'], $item['quantidade'], $item['preco']]);
                }

                // C) Registra o Pagamento
                $stmtPag = $pdo->prepare("INSERT INTO pagamento (id_pedido, id_forma_pagamento, valor_pago) VALUES (?, ?, ?)");
                $stmtPag->execute([$id_pedido, $id_forma_pagamento, $valor_total]);

                $pdo->commit();

                // Limpa o carrinho e redireciona para o painel do cliente
                $_SESSION['carrinho'] = [];
                header("Location: dashboard_cliente.php?pedido_sucesso=" . $id_pedido);
                exit;

            } catch (Exception $e) {
                $pdo->rollBack();
                $mensagem = "<p style='color: #f87171; margin-bottom: 15px;'>Erro ao registrar pedido: " . $e->getMessage() . "</p>";
            }
        }
    }
}

// Busca formas de pagamento cadastradas (pix, credito, debito)
$formas_pagamento = $pdo->query("SELECT id, nome FROM forma_pagamento ORDER BY id")->fetchAll(PDO::FETCH_ASSOC);

// Calcula total geral para exibição
$total_geral = 0;
foreach ($_SESSION['carrinho'] as $item) {
    $total_geral += $item['preco'] * $item['quantidade'];
}
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>LARICA BURGER | Carrinho de Compras</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <style>
        .carrinho-wrapper {
            max-width: 900px;
            margin: 40px auto;
            padding: 30px;
            background: #1a1a1a;
            color: #f5f5f5;
            border: 1px solid #333;
            border-radius: 10px;
        }
        .carrinho-wrapper h2 {
            color: #ffb703;
            margin-bottom: 25px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }
        th, td {
            padding: 14px 12px;
            text-align: left;
            border-bottom: 1px solid #333;
        }
        th {
            background-color: #262626;
            color: #ffb703;
            font-weight: 600;
        }
        .qtd-form {
            display: flex;
            align-items: center;
            gap: 8px;
        }
        .qtd-input {
            width: 60px;
            padding: 6px;
            text-align: center;
            background-color: #262626;
            color: #fff;
            border: 1px solid #444;
            border-radius: 4px;
        }
        .btn-mini {
            padding: 6px 12px;
            font-size: 0.85rem;
            background-color: #4b5563;
            color: #fff;
            border: none;
            border-radius: 4px;
            cursor: pointer;
        }
        .btn-mini:hover {
            background-color: #6b7280;
        }
        .btn-remover {
            background-color: #dc2626;
        }
        .btn-remover:hover {
            background-color: #b91c1c;
        }
        .resumo-box {
            background-color: #262626;
            padding: 20px;
            border-radius: 8px;
            border: 1px solid #333;
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 20px;
        }
        .select-pagamento {
            padding: 10px;
            border-radius: 6px;
            border: 1px solid #444;
            background-color: #1a1a1a;
            color: #fff;
            text-transform: capitalize;
        }
        .capitalize {
            text-transform: capitalize;
        }
    </style>
</head>
<body>

    <?php include 'includes/header.php'; ?>

    <main>
        <div class="carrinho-wrapper">
            <h2>Seu Carrinho de Compras</h2>

            <?php if (!empty($mensagem)) echo $mensagem; ?>

            <?php if (empty($_SESSION['carrinho'])): ?>
                <div style="text-align: center; padding: 40px 0;">
                    <p style="color: #aaa; margin-bottom: 20px; font-size: 1.1rem;">Seu carrinho ainda está vazio.</p>
                    <a href="cardapio.php" class="btn btn-enviar">
                        Ver Cardápio
                    </a>
                </div>
            <?php else: ?>
                <table>
                    <thead>
                        <tr>
                            <th>Produto</th>
                            <th>Preço Unit.</th>
                            <th>Quantidade</th>
                            <th>Subtotal</th>
                            <th>Ações</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($_SESSION['carrinho'] as $item): 
                            $subtotal = $item['preco'] * $item['quantidade'];
                        ?>
                            <tr>
                                <td><strong><?php echo htmlspecialchars($item['nome']); ?></strong></td>
                                <td>R$ <?php echo number_format($item['preco'], 2, ',', '.'); ?></td>
                                <td>
                                    <form action="carrinho.php" method="POST" class="qtd-form">
                                        <input type="hidden" name="acao" value="atualizar">
                                        <input type="hidden" name="id_produto" value="<?php echo $item['id']; ?>">
                                        <input type="number" name="quantidade" value="<?php echo $item['quantidade']; ?>" min="1" max="20" class="qtd-input">
                                        <button type="submit" class="btn-mini">Atualizar</button>
                                    </form>
                                </td>
                                <td style="color: #25d366;"><strong>R$ <?php echo number_format($subtotal, 2, ',', '.'); ?></strong></td>
                                <td>
                                    <form action="carrinho.php" method="POST" style="margin: 0;">
                                        <input type="hidden" name="acao" value="atualizar">
                                        <input type="hidden" name="id_produto" value="<?php echo $item['id']; ?>">
                                        <input type="hidden" name="quantidade" value="0">
                                        <button type="submit" class="btn-mini btn-remover">Remover</button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>

                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 25px;">
                    <a href="cardapio.php" style="color: #ffb703; font-weight: 600; text-decoration: none;">
                        &larr; Continuar comprando
                    </a>
                    <a href="carrinho.php?limpar=1" style="color: #f87171; font-size: 0.9rem; text-decoration: none;">
                        Esvaziar carrinho
                    </a>
                </div>

                <div class="resumo-box">
                    <div>
                        <span style="color: #aaa; font-size: 0.95rem;">Total do Pedido:</span>
                        <h3 style="font-size: 1.8rem; color: #25d366; margin: 5px 0 0 0;">
                            R$ <?php echo number_format($total_geral, 2, ',', '.'); ?>
                        </h3>
                    </div>

                    <form action="carrinho.php" method="POST" style="display: flex; align-items: flex-end; gap: 15px; flex-wrap: wrap;">
                        <input type="hidden" name="acao" value="finalizar">
                        
                        <div>
                            <label for="id_forma_pagamento" style="display: block; font-size: 0.85rem; font-weight: bold; margin-bottom: 6px; color: #ccc;">
                                Forma de Pagamento:
                            </label>
                            <select name="id_forma_pagamento" id="id_forma_pagamento" class="select-pagamento" required>
                                <?php foreach ($formas_pagamento as $fp): ?>
                                    <option value="<?php echo $fp['id']; ?>" class="capitalize">
                                        <?php echo htmlspecialchars($fp['nome']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <button type="submit" class="btn btn-enviar" style="padding: 11px 24px; font-size: 1rem;">
                            Finalizar Pedido
                        </button>
                    </form>
                </div>
            <?php endif; ?>
        </div>
    </main>

    <footer class="footer">
        <p><strong>Atendimento de Terça a Domingo, das 18h às 23h30.</strong></p>
        <p><strong>Endereço:</strong> Av. Rio Branco, 193 - Bairro do Recife (Recife Antigo)</p>
        <p><strong>Cidade / Estado:</strong> Recife - PE - <strong>CEP:</strong> 50030-320</p>
        <p><strong>Telefone:</strong> (81) 99658-5753</p>  
    </footer>

</body>
</html>
