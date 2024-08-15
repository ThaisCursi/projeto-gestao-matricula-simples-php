<?php
include 'conexao.php';

// Função para prevenir XSS
function no_xss($data) {
    return htmlspecialchars($data, ENT_QUOTES, 'UTF-8');
}

// Função para limpar e validar parâmetros de ordenação
function get_order_by() {
    $allowed_columns = ['id_aluno', 'vc_aluno', 'vc_usuario', 'dt_nascimento', 'dt_inclusao', 'dt_ult_alteracao', 'boo_status'];
    $allowed_directions = ['asc', 'desc'];

    $column = isset($_GET['order_by']) && in_array($_GET['order_by'], $allowed_columns) ? $_GET['order_by'] : 'vc_aluno';
    $direction = isset($_GET['order_direction']) && in_array($_GET['order_direction'], $allowed_directions) ? $_GET['order_direction'] : 'asc';

    return [$column, $direction];
}

// Obter parâmetros de ordenação
list($order_by, $order_direction) = get_order_by();

// Padrão de alunos por página
$default_per_page = 5;
$per_page_options = [5, 10, 15];

// Obter número de alunos por página da query string ou usar padrão
$per_page = isset($_GET['per_page']) && in_array((int)$_GET['per_page'], $per_page_options) ? (int)$_GET['per_page'] : $default_per_page;

// Obter número da página atual
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$page = $page < 1 ? 1 : $page;

// Calcular o offset
$offset = ($page - 1) * $per_page;

// Processar exclusão
if (isset($_GET['delete_id_aluno'])) {
    $delete_id_aluno = intval($_GET['delete_id_aluno']);
    $sql = "DELETE FROM tb_aluno WHERE id_aluno = $delete_id_aluno";
    $conn->query($sql);
    header('Location: cadastrar_aluno.php');
    exit();
}

// Processar atualização
if (isset($_POST['update_id_aluno'])) {
    $update_id_aluno = intval($_POST['update_id_aluno']);
    $vc_aluno = $conn->real_escape_string($_POST['vc_aluno']);
    $vc_usuario = $conn->real_escape_string($_POST['vc_usuario']);
    $dt_nascimento = $conn->real_escape_string($_POST['dt_nascimento']);
    $boo_status = intval($_POST['boo_status']);

    // Validar se a data de nascimento é no futuro
    if (strtotime($dt_nascimento) > time()) {
        $error = "Ops! A data de nascimento não pode ser anterior a data atual.";
    } else {
        $sql = "UPDATE tb_aluno SET vc_aluno='$vc_aluno', vc_usuario='$vc_usuario', dt_nascimento='$dt_nascimento', boo_status=$boo_status WHERE id_aluno=$update_id_aluno";
        $conn->query($sql);
        header('Location: cadastrar_aluno.php');
        exit();
    }
}

// Processar cadastro
if ($_SERVER['REQUEST_METHOD'] == 'POST' && !isset($_POST['update_id_aluno'])) {
    $vc_aluno = $conn->real_escape_string($_POST['vc_aluno']);
    $vc_usuario = $conn->real_escape_string($_POST['vc_usuario']);
    $dt_nascimento = $conn->real_escape_string($_POST['dt_nascimento']);
    $boo_status = intval($_POST['boo_status']);

    // Validar se a data de nascimento é no futuro
    if (strtotime($dt_nascimento) > time()) {
        $error = "A data de nascimento não pode ser no futuro.";
    } else {
        $sql = "INSERT INTO tb_aluno (vc_aluno, vc_usuario, dt_nascimento, boo_status) VALUES ('$vc_aluno', '$vc_usuario', '$dt_nascimento', $boo_status)";
        $conn->query($sql);
        header('Location: cadastrar_aluno.php');
        exit();
    }
}

// Obter parâmetros de filtro
$search_name = isset($_GET['search_name']) ? $_GET['search_name'] : '';
$status_filter = isset($_GET['status_filter']) ? $_GET['status_filter'] : '';

// Consultar o total de alunos após aplicar os filtros
$where_clauses = [];
if (!empty($search_name)) {
    $where_clauses[] = "vc_aluno LIKE '%$search_name%'";
}
if ($status_filter !== '') {
    $where_clauses[] = "boo_status = $status_filter";
}

$where_sql = '';
if (count($where_clauses) > 0) {
    $where_sql = 'WHERE ' . implode(' AND ', $where_clauses);
}

$total_sql = "SELECT COUNT(*) AS total FROM tb_aluno $where_sql";
$total_result = $conn->query($total_sql);
$total_row = $total_result->fetch_assoc();
$total_rows = $total_row['total'];

// Função para obter alunos com base em filtros e paginação
function get_filtered_students($conn, $search_name, $status_filter, $order_by, $order_direction, $offset, $per_page) {
    $search_name = $conn->real_escape_string($search_name);
    $status_filter = $conn->real_escape_string($status_filter);

    // Montar a cláusula WHERE com base nos filtros
    $where_clauses = [];
    if (!empty($search_name)) {
        $where_clauses[] = "vc_aluno LIKE '%$search_name%'";
    }
    if ($status_filter !== '') {
        $where_clauses[] = "boo_status = $status_filter";
    }
    
    // Montar a consulta SQL
    $where_sql = '';
    if (count($where_clauses) > 0) {
        $where_sql = 'WHERE ' . implode(' AND ', $where_clauses);
    }

    $sql = "SELECT id_aluno, vc_aluno, vc_usuario, dt_nascimento, dt_inclusao, dt_ult_alteracao, boo_status 
            FROM tb_aluno 
            $where_sql 
            ORDER BY $order_by $order_direction 
            LIMIT $offset, $per_page";

    return $conn->query($sql);
}

// Obter alunos filtrados
$result = get_filtered_students($conn, $search_name, $status_filter, $order_by, $order_direction, $offset, $per_page);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gerenciar Alunos</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
</head>
<body>
<div class="container">
    <h2><?php echo isset($_GET['edit_id_aluno']) ? 'Editar Aluno' : 'Cadastrar Aluno'; ?></h2>
    <?php
    if (isset($error)) {
        echo '<div class="alert alert-danger">' . no_xss($error) . '</div>';
    }
    ?>
    <form method="POST" action="cadastrar_aluno.php">
        <?php
        if (isset($_GET['edit_id_aluno'])) {
            $edit_id_aluno = intval($_GET['edit_id_aluno']);
            $sql = "SELECT * FROM tb_aluno WHERE id_aluno = $edit_id_aluno";
            $result = $conn->query($sql);
            $row = $result->fetch_assoc();
            echo '<input type="hidden" name="update_id_aluno" value="' . no_xss($row['id_aluno']) . '">';
        }
        ?>
        <div class="row">
            <div class="col-md-6 mb-3">
                <label for="vc_aluno" class="form-label">Nome:</label>
                <input type="text" class="form-control" id="vc_aluno" name="vc_aluno" value="<?php echo isset($row['vc_aluno']) ? no_xss($row['vc_aluno']) : ''; ?>" required>
            </div>
            <div class="col-md-6 mb-3">
                <label for="vc_usuario" class="form-label">Usuário:</label>
                <input type="text" class="form-control" id="vc_usuario" name="vc_usuario" value="<?php echo isset($row['vc_usuario']) ? no_xss($row['vc_usuario']) : ''; ?>" required>
            </div>
        </div>
        <div class="row">
            <div class="col-md-6 mb-3">
                <label for="dt_nascimento" class="form-label">Data de Nascimento:</label>
                <input type="date" class="form-control" id="dt_nascimento" name="dt_nascimento" value="<?php echo isset($row['dt_nascimento']) ? no_xss($row['dt_nascimento']) : ''; ?>" required>
            </div>
            <div class="col-md-6 mb-3">
                <label for="boo_status" class="form-label">Status:</label>
                <select class="form-select" id="boo_status" name="boo_status" required>
                    <option value="1" <?php echo isset($row['boo_status']) && $row['boo_status'] == '1' ? 'selected' : ''; ?>>Ativo</option>
                    <option value="0" <?php echo isset($row['boo_status']) && $row['boo_status'] == '0' ? 'selected' : ''; ?>>Inativo</option>
                </select>
            </div>
        </div>
        <button type="submit" class="btn btn-primary"><?php echo isset($_GET['edit_id_aluno']) ? 'Atualizar' : 'Cadastrar'; ?></button>
    </form>

    <h2 class="mt-5">Lista de Alunos</h2>
    <!-- Formulário de busca e filtro -->
    <form method="GET" action="cadastrar_aluno.php" class="mb-4">
        <div class="row">
            <div class="col-md-4 mb-3">
                <label for="search_name" class="form-label">Buscar por Nome:</label>
                <input type="text" class="form-control" id="search_name" name="search_name" value="<?php echo no_xss($search_name); ?>">
            </div>
            <div class="col-md-4 mb-3">
                <label for="status_filter" class="form-label">Status:</label>
                <select class="form-select" id="status_filter" name="status_filter">
                    <option value="">Todos</option>
                    <option value="1" <?php echo $status_filter == '1' ? 'selected' : ''; ?>>Ativo</option>
                    <option value="0" <?php echo $status_filter == '0' ? 'selected' : ''; ?>>Inativo</option>
                </select>
            </div>
            <div class="col-md-4 mb-3 d-flex align-items-end">
                <button type="submit" class="btn btn-primary">Buscar</button>
            </div>
        </div>

        <div class="d-flex justify-content-end mb-3">
            <div class="form-group">
                <label for="per_page" class="form-label me-2">Alunos por Página:</label>
                <select class="form-select" id="per_page" name="per_page" onchange="this.form.submit()">
                    <?php foreach ($per_page_options as $option): ?>
                        <option value="<?php echo $option; ?>" <?php echo $option == $per_page ? 'selected' : ''; ?>>
                            <?php echo $option; ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>
        <input type="hidden" name="order_by" value="<?php echo no_xss($order_by); ?>">
        <input type="hidden" name="order_direction" value="<?php echo no_xss($order_direction); ?>">
        <input type="hidden" name="page" value="<?php echo $page; ?>">
    </form>

    <!-- Tabela de alunos -->
    <table class="table">
        <thead>
            <tr>
                <th><a href="?order_by=id_aluno&order_direction=<?php echo $order_direction == 'asc' ? 'desc' : 'asc'; ?>">ID</a></th>
                <th><a href="?order_by=vc_aluno&order_direction=<?php echo $order_direction == 'asc' ? 'desc' : 'asc'; ?>">Nome</a></th>
                <th><a href="?order_by=vc_usuario&order_direction=<?php echo $order_direction == 'asc' ? 'desc' : 'asc'; ?>">Usuário</a></th>
                <th><a href="?order_by=dt_nascimento&order_direction=<?php echo $order_direction == 'asc' ? 'desc' : 'asc'; ?>">Data de Nascimento</a></th>
                <th><a href="?order_by=dt_inclusao&order_direction=<?php echo $order_direction == 'asc' ? 'desc' : 'asc'; ?>">Data de Inclusão</a></th>
                <th><a href="?order_by=dt_ult_alteracao&order_direction=<?php echo $order_direction == 'asc' ? 'desc' : 'asc'; ?>">Última Alteração</a></th>
                <th><a href="?order_by=boo_status&order_direction=<?php echo $order_direction == 'asc' ? 'desc' : 'asc'; ?>">Status</a></th>
                <th>Ações</th>
            </tr>
        </thead>
        <tbody>
            <?php while ($row = $result->fetch_assoc()): ?>
                <tr>
                    <td><?php echo no_xss($row['id_aluno']); ?></td>
                    <td><?php echo no_xss($row['vc_aluno']); ?></td>
                    <td><?php echo no_xss($row['vc_usuario']); ?></td>
                    <td><?php echo no_xss($row['dt_nascimento']); ?></td>
                    <td><?php echo no_xss($row['dt_inclusao']); ?></td>
                    <td><?php echo no_xss($row['dt_ult_alteracao']); ?></td>
                    <td><?php echo $row['boo_status'] == 1 ? 'Ativo' : 'Inativo'; ?></td>
                    <td>
                        <a href="cadastrar_aluno.php?edit_id_aluno=<?php echo no_xss($row['id_aluno']); ?>" class="btn btn-warning btn-sm">Editar</a>
                        <a href="cadastrar_aluno.php?delete_id_aluno=<?php echo no_xss($row['id_aluno']); ?>" class="btn btn-danger btn-sm" onclick="return confirm('Tem certeza que deseja excluir?');">Excluir</a>
                    </td>
                </tr>
            <?php endwhile; ?>
        </tbody>
    </table>

    <!-- Paginação -->
    <nav>
        <ul class="pagination">
            <?php
            $total_pages = ceil($total_rows / $per_page);
            for ($i = 1; $i <= $total_pages; $i++): ?>
                <li class="page-item <?php echo $i == $page ? 'active' : ''; ?>">
                    <a class="page-link" href="?page=<?php echo $i; ?>&search_name=<?php echo no_xss($search_name); ?>&status_filter=<?php echo no_xss($status_filter); ?>&per_page=<?php echo $per_page; ?>&order_by=<?php echo no_xss($order_by); ?>&order_direction=<?php echo no_xss($order_direction); ?>">
                        <?php echo $i; ?>
                    </a>
                </li>
            <?php endfor; ?>
        </ul>
    </nav>
    <div class="mt-4">
        <a href="dashboard.php" class="btn btn-secondary">Voltar</a>
    </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
