<?php
session_start();

// Usuário e senha fictícios para exemplo
$valid_username = 'teste';
$valid_password = 'teste';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = $_POST['username'];
    $password = $_POST['password'];

    if ($username === $valid_username && $password === $valid_password) {
        $_SESSION['loggedin'] = true;
        header('Location: dashboard.php'); // Redireciona para a página principal após login bem-sucedido
        exit();
    } else {
        echo "<script>
            document.getElementById('error-message').innerText = 'Invalid username or password';
            window.history.back(); // Volta para a página anterior
        </script>";
    }
}
?>
