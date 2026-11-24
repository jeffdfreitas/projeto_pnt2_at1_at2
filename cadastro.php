<?php
session_start();

// Se o usuário já estiver logado, redireciona para a página inicial
if (isset($_SESSION['usuario_id'])) {
    header("Location: index.php");
    exit;
}

require 'includes/conexao.php';
$mensagem = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nome = $_POST['nome'];
    $email = $_POST['email'];
    $senha_raw = $_POST['senha'];
    $confirmar_senha = $_POST['confirmar_senha'];
    $cpf = preg_replace('/[^0-9]/', '', $_POST['cpf']); // Remove pontos e traço
    $telefone = preg_replace('/[^0-9]/', '', $_POST['telefone']); // Remove parênteses, espaço e traço
    $data_nascimento = $_POST['data_nascimento'];
    $id_tipo = 1; // 1 = Cliente

    // Dupla validação no servidor (boa prática além do JS)
    if ($senha_raw !== $confirmar_senha) {
        $mensagem = "<p style='color: red;'>Erro: As senhas não coincidem.</p>";
    } else {
        $senha = password_hash($senha_raw, PASSWORD_DEFAULT);

        try {
            $pdo->beginTransaction();

            // 1. Insere o usuário
            $stmtUser = $pdo->prepare("INSERT INTO usuario (email, senha, id_tipo) VALUES (?, ?, ?)");
            $stmtUser->execute([$email, $senha, $id_tipo]);
            $id_usuario = $pdo->lastInsertId();

            // 2. Insere o cliente usando o ID gerado acima
            $stmtCliente = $pdo->prepare("INSERT INTO cliente (id_usuario, nome, cpf, data_nascimento) VALUES (?, ?, ?, ?)");
            $stmtCliente->execute([$id_usuario, $nome, $cpf, $data_nascimento]);
            $id_cliente = $pdo->lastInsertId();

            // 3. Insere o telefone na tabela telefone (se preenchido)
            if (!empty($telefone)) {
                $stmtTel = $pdo->prepare("INSERT INTO telefone (numero, id_cliente) VALUES (?, ?)");
                $stmtTel->execute([$telefone, $id_cliente]);
            }

            $pdo->commit();
            $mensagem = "<p style='color: green;'>Cadastro realizado com sucesso! <a href='login.php'>Faça login</a></p>";
        } catch (Exception $e) {
            $pdo->rollBack();
            if ($e->getCode() == 23000) {
                $mensagem = "<p style='color: red;'>Erro: E-mail ou CPF já cadastrados.</p>";
            } else {
                $mensagem = "<p style='color: red;'>Erro ao cadastrar: " . $e->getMessage() . "</p>";
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>LARICA BURGER | Cadastro de Cliente</title>
    <link rel="stylesheet" href="assets/css/style.css">
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
            <h2>Cadastro de Cliente</h2>

            <!-- Exibe mensagem de sucesso ou erro -->
            <?php if (!empty($mensagem)) echo $mensagem; ?>

            <form id="form-cadastro" action="cadastro.php" method="POST">
                <label for="nome">Nome Completo</label>
                <input type="text" id="nome" name="nome" maxlength="150" required>

                <label for="cpf">CPF</label>
                <input type="text" id="cpf" name="cpf" placeholder="111.111.111-11" maxlength="14" required>

                <label for="data_nascimento">Data de Nascimento</label>
                <input type="date" id="data_nascimento" name="data_nascimento" required>

                <label for="telefone">Telefone</label>
                <input type="tel" id="telefone" name="telefone" placeholder="(99) 99999-9999" maxlength="15" required>

                <label for="email">E-mail</label>
                <input type="email" id="email" name="email" maxlength="150" required>

                <label for="senha">Senha</label>
                <input type="password" id="senha" name="senha" required>

                <label for="confirmar_senha">Confirmar Senha</label>
                <input type="password" id="confirmar_senha" name="confirmar_senha" required>
                <small id="erro-senha" style="color: red; display: none; margin-top: -10px; margin-bottom: 10px;">
                    As senhas digitadas não são iguais!
                </small>

                <button class="btn btn-enviar" type="submit">Cadastrar</button>
            </form>
        </div>
    </main>

    <footer class="footer">
        <p><strong>Atendimento de Terça a Domingo, das 18h às 23h30.</strong></p>
        <p><strong>Endereço:</strong> Av. Rio Branco, 193 - Bairro do Recife (Recife Antigo)</p>
        <p><strong>Cidade / Estado:</strong> Recife - PE - <strong>CEP:</strong> 50030-320</p>
        <p><strong>Telefone:</strong> (81) 99658-5753</p>  
    </footer>

    <script src="assets/js/script.js"></script>
</body>
</html>
