<?php
include 'conexao.php';

// Função para prevenir XSS
function no_xss($data) {
    return htmlspecialchars($data, ENT_QUOTES, 'UTF-8');
}

// Função para limpar e validar parâmetros de ordenação
function get_order_by() {
    $allowed_columns = ['id_matricula', 'id_aluno', 'id_turma', 'dt_matricula', 'boo_status'];
    $allowed_directions = ['asc', 'desc'];

    $column = isset($_GET['order_by']) && in_array($_GET['order_by'], $allowed_columns) ? $_GET['order_by'] : 'id_matricula';
    $direction = isset($_GET['order_direction']) && in_array($_GET['order_direction'], $allowed_directions) ? $_GET['order_direction'] : 'asc';

    return [$column, $direction];
}

// Obter parâmetros de ordenação
list($order_by, $order_direction) = get_order_by();

// Padrão de matrículas por página
$default_per_page = 5;
$per_page_options = [5, 10, 15];

// Obter número de matrículas por página da query string ou usar padrão
$per_page = isset($_GET['per_page']) && in_array((int)$_GET['per_page'], $per_page_options) ? (int)$_GET['per_page'] : $default_per_page;

// Obter número da página atual
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$page = $page < 1 ? 1 : $page;

// Calcular o offset
$offset = ($page - 1) * $per_page;

// Processar exclusão
if (isset($_GET['delete_id_matricula'])) {
    $delete_id_matricula = intval($_GET['delete_id_matricula']);
    $sql = "DELETE FROM tb_matricula WHERE id_matricula = $delete_id_matricula";
    $conn->query($sql);
    header('Location: fazer_matricula.php');
    exit();
}

// Processar cadastro
if ($_SERVER['REQUEST_METHOD'] == 'POST' && !isset($_POST['update_id_matricula'])) {
    $id_aluno = intval($_POST['id_aluno']);
    $id_turma = intval($_POST['id_turma']);

    // Verificar se a matrícula já existe
    $check_sql = "SELECT COUNT(*) AS count FROM tb_matricula WHERE id_aluno = $id_aluno AND id_turma = $id_turma";
    $check_result = $conn->query($check_sql);
    $check_row = $check_result->fetch_assoc();
    
    if ($check_row['count'] > 0) {
        $error = "O aluno já está matriculado nesta turma.";
    } else {
        $boo_status = intval($_POST['boo_status']);
        $sql = "INSERT INTO tb_matricula (id_aluno, id_turma, boo_status) VALUES ('$id_aluno', '$id_turma', $boo_status)";
        $conn->query($sql);
        header('Location: fazer_matricula.php');
        exit();
    }
}

// Obter parâmetros de busca e filtro
$search_name = isset($_GET['search_name']) ? $_GET['search_name'] : '';
$status_filter = isset($_GET['status_filter']) ? $_GET['status_filter'] : '';

// Consulta para obter o total de matrículas com filtros de busca e status
$total_sql = "SELECT COUNT(*) AS total FROM tb_matricula m
              JOIN tb_aluno a ON m.id_aluno = a.id_aluno
              JOIN tb_turma t ON m.id_turma = t.id_turma
              WHERE a.vc_aluno LIKE '%$search_name%' AND (m.boo_status = '$status_filter' OR '$status_filter' = '')";
$total_result = $conn->query($total_sql);
$total_row = $total_result->fetch_assoc();
$total_rows = $total_row['total'];

// Consulta para obter matrículas com ordenação, filtros e paginação
$sql = "SELECT m.id_matricula, m.id_aluno, m.id_turma, m.dt_matricula, m.boo_status, a.vc_aluno, t.vc_turma 
        FROM tb_matricula m
        JOIN tb_aluno a ON m.id_aluno = a.id_aluno
        JOIN tb_turma t ON m.id_turma = t.id_turma
        WHERE a.vc_aluno LIKE '%$search_name%' AND (m.boo_status = '$status_filter' OR '$status_filter' = '')
        ORDER BY $order_by $order_direction 
        LIMIT $offset, $per_page";
$result = $conn->query($sql);

// Obter alunos e turmas para o formulário
$alunos_result = $conn->query("SELECT id_aluno, vc_aluno FROM tb_aluno");
$turmas_result = $conn->query("SELECT id_turma, vc_turma FROM tb_turma");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gerenciar Matrículas</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
</head>
<body>
<div class="container">
    <h2><?php echo isset($_GET['edit_id_matricula']) ? 'Editar Matrícula' : 'Cadastrar Matrícula'; ?></h2>
    <?php
    if (isset($error)) {
        echo '<div class="alert alert-danger">' . no_xss($error) . '</div>';
    }
    ?>
    <form method="POST" action="fazer_matricula.php">
        <div class="row">
            <div class="col-md-6 mb-3">
                <label for="id_aluno" class="form-label">Aluno:</label>
                <select class="form-select" id="id_aluno" name="id_aluno" required>
                    <option value="">Selecione um aluno</option>
                    <?php while ($row = $alunos_result->fetch_assoc()): ?>
                        <option value="<?php echo no_xss($row['id_aluno']); ?>"><?php echo no_xss($row['vc_aluno']); ?></option>
                    <?php endwhile; ?>
                </select>
            </div>
            <div class="col-md-6 mb-3">
                <label for="id_turma" class="form-label">Turma:</label>
                <select class="form-select" id="id_turma" name="id_turma" required>
                    <option value="">Selecione uma turma</option>
                    <?php while ($row = $turmas_result->fetch_assoc()): ?>
                        <option value="<?php echo no_xss($row['id_turma']); ?>"><?php echo no_xss($row['vc_turma']); ?></option>
                    <?php endwhile; ?>
                </select>
            </div>
        </div>
        <div class="row">
            <div class="col-md-6 mb-3">
                <label for="boo_status" class="form-label">Status:</label>
                <select class="form-select" id="boo_status" name="boo_status" required>
                    <option value="1">Ativo</option>
                    <option value="0">Inativo</option>
                </select>
            </div>
        </div>
        <button type="submit" class="btn btn-primary"><?php echo isset($_GET['edit_id_matricula']) ? 'Atualizar' : 'Cadastrar'; ?></button>
    </form>

    <hr>

    <h2>Lista de Matrículas</h2>

    <!-- Formulário de busca e filtro -->
    <form method="GET" action="fazer_matricula.php" class="mb-4">
        <div class="row">
            <div class="col-md-4 mb-3">
                <label for="search_name" class="form-label">Buscar por Aluno:</label>
                <input type="text" class="form-control" id="search_name" name="search_name" value="<?php echo isset($_GET['search_name']) ? no_xss($_GET['search_name']) : ''; ?>">
            </div>
            <div class="col-md-4 mb-3">
                <label for="status_filter" class="form-label">Status:</label>
                <select class="form-select" id="status_filter" name="status_filter">
                    <option value="">Todos</option>
                    <option value="1" <?php echo isset($_GET['status_filter']) && $_GET['status_filter'] == '1' ? 'selected' : ''; ?>>Ativo</option>
                    <option value="0" <?php echo isset($_GET['status_filter']) && $_GET['status_filter'] == '0' ? 'selected' : ''; ?>>Inativo</option>
                </select>
            </div>
            <div class="col-md-4 mb-3 d-flex align-items-end">
                <button type="submit" class="btn btn-primary">Buscar</button>
            </div>
        </div>

        <div class="d-flex justify-content-end mb-3">
            <div class="form-group">
                <label for="per_page" class="form-label me-2">Matrículas por Página:</label>
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

    <!-- Tabela de Matrículas -->
    <table class="table table-striped">
        <thead>
            <tr>
                <th><a href="?order_by=id_matricula&order_direction=<?php echo $order_direction == 'asc' ? 'desc' : 'asc'; ?>">ID Matrícula</a></th>
                <th>Aluno</th>
                <th>Turma</th>
                <th>Data da Matrícula</th>
                <th>Status</th>
                <th>Ações</th>
            </tr>
        </thead>
        <tbody>
            <?php if ($result->num_rows > 0): ?>
                <?php while ($row = $result->fetch_assoc()): ?>
                    <tr>
                        <td><?php echo no_xss($row['id_matricula']); ?></td>
                        <td><?php echo no_xss($row['vc_aluno']); ?></td>
                        <td><?php echo no_xss($row['vc_turma']); ?></td>
                        <td><?php echo date('d/m/Y H:i:s', strtotime($row['dt_matricula'])); ?></td>
                        <td><?php echo $row['boo_status'] == 1 ? 'Ativo' : 'Inativo'; ?></td>
                        <td>
                            <a href="fazer_matricula.php?delete_id_matricula=<?php echo no_xss($row['id_matricula']); ?>" class="btn btn-danger btn-sm" onclick="return confirm('Tem certeza que deseja excluir esta matrícula?')">Excluir</a>
                        </td>
                    </tr>
                <?php endwhile; ?>
            <?php else: ?>
                <tr>
                    <td colspan="6" class="text-center">Nenhuma matrícula encontrada.</td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>

    <!-- Paginação -->
    <nav aria-label="Page navigation">
        <ul class="pagination justify-content-center">
            <?php
            $total_pages = ceil($total_rows / $per_page);
            if ($total_pages > 1):
                $prev_page = $page > 1 ? $page - 1 : 1;
                $next_page = $page < $total_pages ? $page + 1 : $total_pages;
            ?>
                <li class="page-item <?php echo $page == 1 ? 'disabled' : ''; ?>">
                    <a class="page-link" href="?page=<?php echo $prev_page; ?>&order_by=<?php echo no_xss($order_by); ?>&order_direction=<?php echo no_xss($order_direction); ?>&per_page=<?php echo $per_page; ?>&search_name=<?php echo no_xss($search_name); ?>&status_filter=<?php echo no_xss($status_filter); ?>">Anterior</a>
                </li>
                <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                    <li class="page-item <?php echo $i == $page ? 'active' : ''; ?>">
                        <a class="page-link" href="?page=<?php echo $i; ?>&order_by=<?php echo no_xss($order_by); ?>&order_direction=<?php echo no_xss($order_direction); ?>&per_page=<?php echo $per_page; ?>&search_name=<?php echo no_xss($search_name); ?>&status_filter=<?php echo no_xss($status_filter); ?>"><?php echo $i; ?></a>
                    </li>
                <?php endfor; ?>
                <li class="page-item <?php echo $page == $total_pages ? 'disabled' : ''; ?>">
                    <a class="page-link" href="?page=<?php echo $next_page; ?>&order_by=<?php echo no_xss($order_by); ?>&order_direction=<?php echo no_xss($order_direction); ?>&per_page=<?php echo $per_page; ?>&search_name=<?php echo no_xss($search_name); ?>&status_filter=<?php echo no_xss($status_filter); ?>">Próximo</a>
                </li>
            <?php endif; ?>
        </ul>
    </nav>
    <div class="mt-4">
        <a href="dashboard.php" class="btn btn-secondary">Voltar</a>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
