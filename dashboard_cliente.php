<?php
session_start();
require 'includes/conexao.php';

// Proteção: apenas cliente logado (id_tipo == 1) pode acessar
if (!isset($_SESSION['usuario_id']) || $_SESSION['usuario_tipo'] != 1) {
    header("Location: login.php");
    exit;
}

// Busca o id do cliente vinculado ao usuário logado
$stmtCli = $pdo->prepare("SELECT id, nome FROM cliente WHERE id_usuario = ?");
$stmtCli->execute([$_SESSION['usuario_id']]);
$cliente = $stmtCli->fetch(PDO::FETCH_ASSOC);

$mensagem_checkout = "";

// PROCESSAR CONFIRMAÇÃO DE PAGAMENTO (CHECKOUT DO CLIENTE)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['acao']) && $_POST['acao'] === 'confirmar_pagamento') {
    $id_pedido_pagar = (int) $_POST['id_pedido'];

    if ($cliente) {
        try {
            $pdo->beginTransaction();

            // 1. Pagamento quando realizado, atualiza o status do pedido para 'solicitado'
            $stmtUpdPed = $pdo->prepare("
                UPDATE pedido 
                SET status_pedido = 'solicitado' 
                WHERE id = ? AND id_cliente = ? AND status_pedido = 'aguardando pagamento'
            ");
            $stmtUpdPed->execute([$id_pedido_pagar, $cliente['id']]);

            // 2. Atualiza a data/hora do pagamento efetivado na tabela pagamento
            $stmtUpdPag = $pdo->prepare("UPDATE pagamento SET data_pagamento = CURRENT_TIMESTAMP WHERE id_pedido = ?");
            $stmtUpdPag->execute([$id_pedido_pagar]);

            $pdo->commit();
            $mensagem_checkout = "Pagamento do Pedido #{$id_pedido_pagar} confirmado com sucesso! Já enviamos para a cozinha.";
        } catch (Exception $e) {
            $pdo->rollBack();
            $mensagem_checkout = "Erro ao confirmar pagamento: " . $e->getMessage();
        }
    }
}

$pedidos = [];
if ($cliente) {
    $stmtPed = $pdo->prepare("
        SELECT p.id, p.data_pedido, p.status_pedido, p.valor_total, fp.nome AS forma_pagamento
        FROM pedido p
        LEFT JOIN pagamento pag ON pag.id_pedido = p.id
        LEFT JOIN forma_pagamento fp ON fp.id = pag.id_forma_pagamento
        WHERE p.id_cliente = ?
        ORDER BY p.data_pedido DESC
    ");
    $stmtPed->execute([$cliente['id']]);
    $pedidos = $stmtPed->fetchAll(PDO::FETCH_ASSOC);

    $stmtItens = $pdo->prepare("
        SELECT ip.quantidade, ip.preco_unitario, prod.nome
        FROM item_pedido ip
        INNER JOIN produto prod ON prod.id = ip.id_produto
        WHERE ip.id_pedido = ?
    ");

    foreach ($pedidos as $indice => $ped) {
        $stmtItens->execute([$ped['id']]);
        $pedidos[$indice]['itens'] = $stmtItens->fetchAll(PDO::FETCH_ASSOC);
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>LARICA BURGER | Meus Pedidos</title>
    <link rel="stylesheet" href="assets/css/style.css"/>
    <style>
        .alerta-sucesso {
            background-color: #14532d;
            border: 1px solid #22c55e;
            color: #dcfce7;
            padding: 15px 20px;
            border-radius: 8px;
            max-width: 800px;
            margin: 0 auto 25px auto;
            font-weight: bold;
        }
        .checkout-box {
            background-color: #1e293b;
            border: 2px solid #ffb703;
            padding: 20px;
            border-radius: 8px;
            margin-top: 20px;
            text-align: center;
        }
        .btn-confirmar-pag {
            background-color: #16a34a;
            color: #ffffff;
            padding: 14px 28px;
            font-size: 1.05rem;
            font-weight: bold;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            margin-top: 12px;
        }
        .btn-confirmar-pag:hover {
            background-color: #15803d;
        }
        .status-tag {
            display: inline-block;
            padding: 5px 14px;
            border-radius: 12px;
            font-size: 0.85rem;
            font-weight: bold;
            background-color: #ffb703;
            color: #0a0a0a;
            text-transform: capitalize;
        }
        .status-pendente {
            background-color: #dc2626;
            color: #ffffff;
        }
    </style>
</head>

<body>

    <?php include 'includes/header.php'; ?>

    <main class="menu-section">
        <h2>Olá, <strong><?php echo htmlspecialchars($_SESSION['usuario_nome']); ?></strong>!</h2>

        <?php if (isset($_GET['pedido_sucesso']) && empty($mensagem_checkout)): ?>
            <div class="alerta-sucesso">
                Pedido #<?php echo (int) $_GET['pedido_sucesso']; ?> registrado! Confirme o pagamento abaixo para liberar para a cozinha.
            </div>
        <?php endif; ?>

        <?php if (!empty($mensagem_checkout)): ?>
            <div class="alerta-sucesso">
                <?php echo htmlspecialchars($mensagem_checkout); ?>
            </div>
        <?php endif; ?>

        <?php if (empty($pedidos)): ?>
            <div class="tracking-panel">
                <h3>Você ainda não fez nenhum pedido</h3>
                <p style="margin-bottom: 20px;">Que tal escolher um hambúrguer artesanal agora?</p>
                <a href="cardapio.php" class="btn btn-enviar">Ver Cardápio</a>
            </div>
        <?php else: ?>
            <?php $ultimo = $pedidos[0]; ?>
            <div class="tracking-panel">
                <h3>Último Pedido (#<?php echo $ultimo['id']; ?>)</h3>
                <p><strong>Data:</strong> <?php echo date('d/m/Y H:i', strtotime($ultimo['data_pedido'])); ?></p>
                <p><strong>Forma de Pagamento:</strong> <span style="text-transform: capitalize;"><?php echo htmlspecialchars($ultimo['forma_pagamento'] ?? 'Não informado'); ?></span></p>
                
                <p style="margin: 12px 0;">
                    <strong>Status Atual:</strong> 
                    <span class="status-tag <?php echo ($ultimo['status_pedido'] === 'aguardando pagamento') ? 'status-pendente' : ''; ?>">
                        <?php echo htmlspecialchars($ultimo['status_pedido']); ?>
                    </span>
                </p>
                
                <div style="margin-top: 15px; border-top: 1px solid #444; padding-top: 10px; text-align: left;">
                    <strong>Itens do pedido:</strong>
                    <ul style="margin: 8px 0 0 20px; color: #ddd;">
                        <?php foreach ($ultimo['itens'] as $it): ?>
                            <li><?php echo $it['quantidade']; ?>x <?php echo htmlspecialchars($it['nome']); ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>

                <p style="margin-top: 15px; font-size: 1.3rem; color: #25d366;">
                    <strong>Total a Pagar: R$ <?php echo number_format($ultimo['valor_total'], 2, ',', '.'); ?></strong>
                </p>

                <?php if ($ultimo['status_pedido'] === 'aguardando pagamento'): ?>
                    <div class="checkout-box">
                        <h4 style="color: #ffb703; margin-bottom: 8px;">Checkout - Finalização de Pagamento</h4>
                        <p style="font-size: 0.95rem; color: #cbd5e1; margin-bottom: 10px;">
                            Modalidade selecionada: <strong style="text-transform: uppercase;"><?php echo htmlspecialchars($ultimo['forma_pagamento']); ?></strong>
                        </p>
                        <form action="dashboard_cliente.php" method="POST">
                            <input type="hidden" name="acao" value="confirmar_pagamento">
                            <input type="hidden" name="id_pedido" value="<?php echo $ultimo['id']; ?>">
                            <button type="submit" class="btn-confirmar-pag">
                                Confirmar Pagamento
                            </button>
                        </form>
                    </div>
                <?php endif; ?>
            </div>

            <?php if (count($pedidos) > 1): ?>
                <h3 style="margin-top: 40px; margin-bottom: 20px;">Histórico de Pedidos Anteriores</h3>
                <div class="cards-container">
                    <?php for ($i = 1; $i < count($pedidos); $i++): 
                        $ant = $pedidos[$i];
                    ?>
                        <div class="card">
                            <div>
                                <h3>Pedido #<?php echo $ant['id']; ?></h3>
                                <p>
                                    <strong>Data:</strong> <?php echo date('d/m/Y H:i', strtotime($ant['data_pedido'])); ?><br>
                                    <strong>Status:</strong> <span style="text-transform: capitalize;"><?php echo htmlspecialchars($ant['status_pedido']); ?></span>
                                </p>
                                <p>
                                    <?php foreach ($ant['itens'] as $it): ?>
                                        <?php echo $it['quantidade']; ?>x <?php echo htmlspecialchars($it['nome']); ?><br>
                                    <?php endforeach; ?>
                                </p>
                            </div>
                            <div class="card-footer">
                                <span class="price">R$ <?php echo number_format($ant['valor_total'], 2, ',', '.'); ?></span>
                            </div>
                        </div>
                    <?php endfor; ?>
                </div>
            <?php endif; ?>
        <?php endif; ?>
    </main>

    <footer class="footer">
        <p><strong>Atendimento de Terça a Domingo, das 18h às 23h30.</strong></p>
        <p><strong>Endereço:</strong> Av. Rio Branco, 193 - Bairro do Recife (Recife Antigo)</p>
        <p><strong>Cidade / Estado:</strong> Recife - PE - <strong>CEP:</strong> 50030-320</p>
        <p><strong>Telefone:</strong> (81) 99658-5753</p>  
    </footer> 
</body>
</html>
