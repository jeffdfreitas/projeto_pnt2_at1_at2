<?php
session_start();
require 'includes/conexao.php';

// Proteção de rota: apenas funcionário (id_tipo == 2) pode acessar
if (!isset($_SESSION['usuario_id']) || $_SESSION['usuario_tipo'] != 2) {
    header("Location: login.php");
    exit;
}

$mensagem = "";

// Busca o id do funcionário vinculado ao usuário logado (para registrar quem alterou o pedido)
$stmtFunc = $pdo->prepare("SELECT id FROM funcionario WHERE id_usuario = ?");
$stmtFunc->execute([$_SESSION['usuario_id']]);
$func = $stmtFunc->fetch(PDO::FETCH_ASSOC);
$id_funcionario = $func ? $func['id'] : null;

// PROCESSAR ATUALIZAÇÃO DE STATUS DO PEDIDO
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['acao']) && $_POST['acao'] === 'mudar_status') {
    $id_pedido = (int) $_POST['id_pedido'];
    $novo_status = trim($_POST['novo_status']);

    $status_permitidos = [
        'aguardando pagamento',
        'solicitado',
        'em preparo',
        'pronto',
        'saiu para entrega',
        'entregue'
    ];

    if (in_array($novo_status, $status_permitidos)) {
        try {
            $stmtUpd = $pdo->prepare("UPDATE pedido SET status_pedido = ?, id_funcionario = ? WHERE id = ?");
            $stmtUpd->execute([$novo_status, $id_funcionario, $id_pedido]);
            $mensagem = "<div class='alerta-ok'>Status do Pedido #{$id_pedido} atualizado para <strong>" . htmlspecialchars($novo_status) . "</strong>!</div>";
        } catch (Exception $e) {
            $mensagem = "<div class='alerta-erro'>Erro ao atualizar status: " . $e->getMessage() . "</div>";
        }
    }
}

// PROCESSAR MARCAÇÃO DE MENSAGEM COMO LIDA
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['acao']) && $_POST['acao'] === 'marcar_lida') {
    $id_msg = (int) $_POST['id_mensagem'];
    try {
        $stmtMsg = $pdo->prepare("UPDATE mensagem_contato SET lida = 1 WHERE id = ?");
        $stmtMsg->execute([$id_msg]);
        $mensagem = "<div class='alerta-ok'>Mensagem #{$id_msg} marcada como lida!</div>";
    } catch (Exception $e) {
        $mensagem = "<div class='alerta-erro'>Erro ao atualizar mensagem: " . $e->getMessage() . "</div>";
    }
}

// Consulta todas as mensagens de contato (não lidas primeiro, depois mais recentes)
$mensagens_contato = $pdo->query("
    SELECT id, assunto, mensagem, lida, data_envio
    FROM mensagem_contato
    ORDER BY lida ASC, data_envio DESC
")->fetchAll(PDO::FETCH_ASSOC);


// Consulta todos os pedidos do banco (mais recentes primeiro)
$pedidos = $pdo->query("
    SELECT p.id, p.data_pedido, p.status_pedido, p.valor_total, c.nome AS cliente_nome, fp.nome AS forma_pagamento
    FROM pedido p
    INNER JOIN cliente c ON p.id_cliente = c.id
    LEFT JOIN pagamento pag ON pag.id_pedido = p.id
    LEFT JOIN forma_pagamento fp ON fp.id = pag.id_forma_pagamento
    ORDER BY p.data_pedido DESC
")->fetchAll(PDO::FETCH_ASSOC);

// Busca os itens de cada pedido para exibir na comanda da cozinha
$stmtItens = $pdo->prepare("
    SELECT ip.quantidade, prod.nome
    FROM item_pedido ip
    INNER JOIN produto prod ON prod.id = ip.id_produto
    WHERE ip.id_pedido = ?
");

foreach ($pedidos as $i => $ped) {
    $stmtItens->execute([$ped['id']]);
    $pedidos[$i]['itens'] = $stmtItens->fetchAll(PDO::FETCH_ASSOC);
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>LARICA BURGER | Painel do Funcionário</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <style>
        .painel-wrapper {
            max-width: 1100px;
            margin: 30px auto;
            padding: 25px;
            background: #fff;
            color: #1f2937;
            border-radius: 10px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.08);
        }
        .painel-topo {
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-bottom: 2px solid #f3f4f6;
            padding-bottom: 15px;
            margin-bottom: 25px;
        }
        .btn-atalho {
            display: inline-block;
            padding: 12px 20px;
            background-color: #d97706;
            color: #fff;
            text-decoration: none;
            border-radius: 6px;
            font-weight: 600;
        }
        .btn-atalho:hover {
            background-color: #b45309;
        }
        .alerta-ok {
            background-color: #dcfce7;
            color: #166534;
            border: 1px solid #86efac;
            padding: 12px 16px;
            border-radius: 6px;
            margin-bottom: 20px;
        }
        .alerta-erro {
            background-color: #fee2e2;
            color: #991b1b;
            padding: 12px 16px;
            border-radius: 6px;
            margin-bottom: 20px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
        }
        th, td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #e5e7eb;
            vertical-align: top;
        }
        th {
            background-color: #f9fafb;
            font-weight: 600;
        }
        .status-badge {
            display: inline-block;
            padding: 4px 10px;
            border-radius: 12px;
            font-size: 0.85rem;
            font-weight: 600;
            background-color: #fef3c7;
            color: #92400e;
            text-transform: capitalize;
        }
        .status-entregue {
            background-color: #dcfce7;
            color: #166534;
        }
        .status-pendente {
            background-color: #fee2e2;
            color: #991b1b;
        }
        .form-status {
            display: flex;
            gap: 6px;
            align-items: center;
        }
        .select-status {
            padding: 6px 8px;
            border: 1px solid #d1d5db;
            border-radius: 4px;
            font-size: 0.85rem;
            text-transform: capitalize;
        }
        .btn-salvar-status {
            padding: 6px 12px;
            background-color: #2563eb;
            color: #fff;
            border: none;
            border-radius: 4px;
            font-size: 0.85rem;
            font-weight: 600;
            cursor: pointer;
        }
        .btn-salvar-status:hover {
            background-color: #1d4ed8;
        }
    </style>
</head>

<body>

    <?php include 'includes/header.php'; ?>

    <main class="painel-wrapper">
        <div class="painel-topo">
            <div>
                <h2 style="text-align: left; margin-bottom: 5px;">Painel Operacional (Cozinha / Admin)</h2>
                <p style="color: #6b7280; margin: 0;">Bem-vindo(a), <strong><?php echo htmlspecialchars($_SESSION['usuario_nome']); ?></strong></p>
            </div>
            
            <a href="gerencia_produtos.php" class="btn-atalho">+ Gerenciar Produtos</a>
        </div>

        <?php if (!empty($mensagem)) echo $mensagem; ?>

        <h3 style="margin-bottom: 15px;">Mapa de Pedidos em Andamento</h3>
        <table>
            <thead>
                <tr>
                    <th>Pedido</th>
                    <th>Data / Hora</th>
                    <th>Cliente e Itens (Comanda)</th>
                    <th>Total / Pagto</th>
                    <th>Status Atual</th>
                    <th>Atualizar Andamento</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($pedidos)): ?>
                    <tr>
                        <td colspan="6" style="text-align: center; color: #6b7280; padding: 25px;">
                            Nenhum pedido realizado ainda. Assim que os clientes fecharem o carrinho, eles aparecerão aqui!
                        </td>
                    </tr>
                <?php else: ?>
                    <?php 
                    $lista_status = [
                        'aguardando pagamento',
                        'solicitado',
                        'em preparo',
                        'pronto',
                        'saiu para entrega',
                        'entregue'
                    ];
                    foreach ($pedidos as $ped): 
                        $classe_badge = 'status-badge';
                        if ($ped['status_pedido'] === 'entregue') $classe_badge .= ' status-entregue';
                        if ($ped['status_pedido'] === 'aguardando pagamento') $classe_badge .= ' status-pendente';
                    ?>
                        <tr>
                            <td><strong>#<?php echo $ped['id']; ?></strong></td>
                            <td><?php echo date('d/m/Y H:i', strtotime($ped['data_pedido'])); ?></td>
                            <td>
                                <strong><?php echo htmlspecialchars($ped['cliente_nome']); ?></strong>
                                <ul style="margin: 6px 0 0 18px; font-size: 0.9rem; color: #374151;">
                                    <?php foreach ($ped['itens'] as $it): ?>
                                        <li><?php echo $it['quantidade']; ?>x <?php echo htmlspecialchars($it['nome']); ?></li>
                                    <?php endforeach; ?>
                                </ul>
                            </td>
                            <td>
                                <strong>R$ <?php echo number_format($ped['valor_total'], 2, ',', '.'); ?></strong><br>
                                <small style="color: #6b7280; text-transform: uppercase;">
                                    <?php echo htmlspecialchars($ped['forma_pagamento'] ?? '-'); ?>
                                </small>
                            </td>
                            <td>
                                <span class="<?php echo $classe_badge; ?>">
                                    <?php echo htmlspecialchars($ped['status_pedido']); ?>
                                </span>
                            </td>
                            <td>
                                <form action="dashboard_funcionario.php" method="POST" class="form-status">
                                    <input type="hidden" name="acao" value="mudar_status">
                                    <input type="hidden" name="id_pedido" value="<?php echo $ped['id']; ?>">
                                    
                                    <select name="novo_status" class="select-status">
                                        <?php foreach ($lista_status as $st): ?>
                                            <option value="<?php echo $st; ?>" <?php echo ($ped['status_pedido'] === $st) ? 'selected' : ''; ?>>
                                                <?php echo ucfirst($st); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                    
                                    <button type="submit" class="btn-salvar-status">Salvar</button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>


    <hr style="border: 0; border-top: 2px solid #f3f4f6; margin: 40px 0 25px 0;">
    <h3 style="margin-bottom: 15px;">Mensagens Recebidas (Elogios, Sugestões e Reclamações)</h3>
        <table>
            <thead>
                <tr>
                    <th>Data / Hora</th>
                    <th>Assunto</th>
                    <th>Mensagem</th>
                    <th>Status</th>
                    <th>Ação</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($mensagens_contato)): ?>
                    <tr>
                        <td colspan="5" style="text-align: center; color: #6b7280; padding: 20px;">
                            Nenhuma mensagem enviada pelos visitantes até o momento.
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($mensagens_contato as $msg): ?>
                        <tr style="<?php echo ($msg['lida'] == 0) ? 'background-color: #fffbeb;' : ''; ?>">
                            <td><?php echo date('d/m/Y H:i', strtotime($msg['data_envio'])); ?></td>
                            <td><strong><?php echo htmlspecialchars($msg['assunto']); ?></strong></td>
                            <td style="max-width: 400px;"><?php echo nl2br(htmlspecialchars($msg['mensagem'])); ?></td>
                            <td>
                                <?php if ($msg['lida'] == 0): ?>
                                    <span class="status-badge status-pendente">Nova</span>
                                <?php else: ?>
                                    <span class="status-badge status-entregue">Lida</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if ($msg['lida'] == 0): ?>
                                    <form action="dashboard_funcionario.php" method="POST" style="margin: 0;">
                                        <input type="hidden" name="acao" value="marcar_lida">
                                        <input type="hidden" name="id_mensagem" value="<?php echo $msg['id']; ?>">
                                        <button type="submit" class="btn-salvar-status">Marcar como lida</button>
                                    </form>
                                <?php else: ?>
                                    <span style="color: #6b7280; font-size: 0.85rem;">Concluída</span>
                                <?php endif; ?>
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
        <p><strong>Cidade / Estado:</strong> Recife - PE - <strong>CEP:</strong> 50030-320</p>
        <p><strong>Telefone:</strong> (81) 99658-5753</p>  
    </footer>

</body>
</html>
