<?php
session_start();

// Se o usuário já estiver logado, redireciona conforme o tipo de perfil
if (isset($_SESSION['usuario_id'])) {
    if ($_SESSION['usuario_tipo'] == 2) {
        header("Location: dashboard_funcionario.php");
    } else {
        header("Location: index.php");
    }
    exit;
}

require 'includes/conexao.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = $_POST['email'];
    $senha = $_POST['senha'];

    // Busca o usuário e os dados vinculados de cliente ou funcionário
    $stmt = $pdo->prepare("
        SELECT u.id, u.senha, u.id_tipo, c.nome AS nome_cliente, f.nome AS nome_func 
        FROM usuario u
        LEFT JOIN cliente c ON c.id_usuario = u.id
        LEFT JOIN funcionario f ON f.id_usuario = u.id
        WHERE u.email = ?
    ");
    $stmt->execute([$email]);
    $usuario = $stmt->fetch();

    // Verifica se o usuário existe e se a senha criptografada bate
    if ($usuario && password_verify($senha, $usuario['senha'])) {
        $_SESSION['usuario_id'] = $usuario['id'];
        $_SESSION['usuario_tipo'] = $usuario['id_tipo'];
        $_SESSION['usuario_nome'] = $usuario['nome_cliente'] ?? $usuario['nome_func'] ?? 'Usuário';

        // Redireciona conforme o tipo (1 = Cliente, 2 = Funcionário)
        if ($usuario['id_tipo'] == 2) {
            header("Location: dashboard_funcionario.php");
        } else {
            header("Location: index.php"); // Página principal do site
        }
        exit;
    } else {
        $mensagem = "<p style='color: red;'>E-mail ou senha incorretos.</p>";
    }
}
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>LARICA BURGER | Login</title>
    <link rel="stylesheet" href="assets/css/style.css"/>
</head>
<body>
        
    <header class="navbar">
        <div class="logo">LARICA BURGER</div>
        <nav class="nav-links">
            <a href="index.php">Início</a>
            <a href="dashboard.php">Dashboard</a>
            <div class="dropdown">
                <a href="#" class="dropbtn">Cardápio</a>
                <div class="dropdown-content">
                    <a href="produtos_clas.php">Burguer Clássico</a>
                    <a href="produtos_veg.php">Burguer Veggie</a>
                    <a href="produtos_acomp.php">Snacks</a>
                    <a href="#">Bebidas</a>
                </div>
            </div>
            <a href="sobre.php">Sobre Nós</a>
            <a href="contato.php">Contatos</a>
            <a href="login.php" class="btn-login">Login</a>
        </nav>
    </header>

    <main>
        <div class="form-container">
            <h2>Faça login para pedir</h2>

            <!-- Exibe mensagem de erro caso e-mail ou senha estejam incorretos -->
            <?php if (!empty($mensagem)) echo $mensagem; ?>

            <form action="login.php" method="POST"> 
                <label for="email">Usuário</label>
                <input type="email" id="email" name="email" placeholder="Digite o e-mail cadastrado" required>

                <label for="senha">Senha</label>
                <input type="password" id="senha" name="senha" placeholder="Digite sua senha" required>

                <button class="btn btn-enviar" type="submit">Entrar</button>
            </form>

            <div class="container-cadastro">
                <h3>Não possui login?</h3>
                <a href="cadastro.php" class="btn btn-cadastro">Criar conta</a>
            </div>
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
