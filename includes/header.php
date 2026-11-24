<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Calcula o total de itens no carrinho (apenas se não for funcionário)
$qtd_carrinho = 0;
if (isset($_SESSION['carrinho']) && is_array($_SESSION['carrinho'])) {
    foreach ($_SESSION['carrinho'] as $item) {
        $qtd_carrinho += $item['quantidade'];
    }
}
?>
<header class="navbar">
    <div class="logo">LARICA BURGER</div>
    <nav class="nav-links">
        
        <?php if (isset($_SESSION['usuario_id']) && $_SESSION['usuario_tipo'] == 2): ?>
            <!-- MENU EXCLUSIVO DO FUNCIONÁRIO / ADMIN -->
            <span style="font-weight: bold; color: #ffb703;">
                Olá, <?php echo htmlspecialchars($_SESSION['usuario_nome']); ?>!
            </span>
            <a href="logout.php" class="btn-login" style="background-color: #dc2626; color: #fff;">Sair</a>

        <?php else: ?>
            <!-- MENU PARA CLIENTES LOGADOS E VISITANTES -->
            <a href="index.php">Início</a>
            
            <?php if (isset($_SESSION['usuario_id'])): ?>
                <a href="dashboard_cliente.php">Dashboard</a>
            <?php endif; ?>

            <a href="cardapio.php">Cardápio</a>
            <a href="sobre.php">Sobre Nós</a>
            <a href="contato.php">Contatos</a>

            <a href="carrinho.php" style="font-weight: bold;">
                Carrinho (<?php echo $qtd_carrinho; ?>)
            </a>

            <?php if (isset($_SESSION['usuario_id'])): ?>
                <span style="font-weight: bold; margin-left: 15px;">
                    Olá, <?php echo htmlspecialchars($_SESSION['usuario_nome']); ?>!
                </span>
                <a href="logout.php" class="btn-login" style="background-color: #dc2626; color: #fff;">Sair</a>
            <?php else: ?>
                <a href="login.php" class="btn-login">Login</a>
            <?php endif; ?>
        <?php endif; ?>

    </nav>
</header>
