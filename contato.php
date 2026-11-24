<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>LARICA BURGER | Contato</title>
    <link rel="stylesheet" href="assets/css/style.css"/>
</head>
<body>
    
    <?php include 'includes/header.php'; ?>
    <main>
        <div class="form-container">
            <h2>Deixe sua mensagem</h2>
            <form action="#" method="POST">
                <label for="assunto">Assunto</label>
                <input type="text" id="assunto" name="assunto" placeholder="Ex: Elogio, Dúvida, Reclamação" required>
                
                <label for="texto">Mensagem</label>
                <textarea id="texto" name="texto" rows="5" placeholder="Digite sua mensagem aqui..." required></textarea>
                
                <div class="form-buttons">
                    <button class="btn btn-limpar" type="reset">Limpar</button>
                    <button class="btn btn-enviar" type="submit">Enviar</button>
                </div>
            </form>
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
