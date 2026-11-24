<?php
session_start(); // Inicia ou recupera a sessão atual
session_unset(); // Limpa todas as variáveis de sessão ($_SESSION)
session_destroy(); // Destrói a sessão no servidor

header("Location: index.php"); // Redireciona para a página inicial
exit;
?>
