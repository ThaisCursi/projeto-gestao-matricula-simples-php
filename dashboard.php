<?php
session_start();

// Verifica se o usuário está logado
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    header('Location: index.php');
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Painel de Controle de Gestão Alunos</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <div class="container mt-5">
        <h1 class="text-center">Painel de Controle de Gestão de Aluno</h1>
        <div class="row mt-4">
            <div class="col-md-4">
                <a href="cadastrar_aluno.php" class="btn btn-primary w-100">Cadastrar Aluno</a>
            </div>
            <div class="col-md-4">
                <a href="cadastrar_turma.php" class="btn btn-secondary w-100">Cadastrar Turma</a>
            </div>
            <div class="col-md-4">
                <a href="fazer_matricula.php" class="btn btn-success w-100">Fazer Matrícula</a>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS and dependencies -->
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.11.7/dist/umd/popper.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.min.js"></script>
</body>
</html>
