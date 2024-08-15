<?php
include 'conexao.php';

// Função para prevenir XSS
function no_xss($data) {
    return htmlspecialchars($data, ENT_QUOTES, 'UTF-8');
}

// Função para limpar e validar parâmetros de ordenação
function get_order_by() {
    $allowed_columns = ['id_turma', 'vc_turma', 'vc_descricao', 'vc_tipo', 'boo_status'];
    $allowed_directions = ['asc', 'desc'];

    $column = isset($_GET['order_by']) && in_array($_GET['order_by'], $allowed_columns) ? $_GET['order_by'] : 'vc_turma';
    $direction = isset($_GET['order_direction']) && in_array($_GET['order_direction'], $allowed_directions) ? $_GET['order_direction'] : 'asc';

    return [$column, $direction];
}

// Obter parâmetros de ordenação
list($order_by, $order_direction) = get_order_by();

// Processar exclusão
if (isset($_GET['delete_id_turma'])) {
    $delete_id_turma = intval($_GET['delete_id_turma']);
    $sql = "DELETE FROM tb_turma WHERE id_turma = $delete_id_turma";
    $conn->query($sql);
    header('Location: cadastrar_turma.php');
    exit();
}

// Processar atualização
if (isset($_POST['update_id_turma'])) {
    $update_id_turma = intval($_POST['update_id_turma']);
    $vc_turma = $conn->real_escape_string($_POST['vc_turma']);
    $vc_descricao = $conn->real_escape_string($_POST['vc_descricao']);
    $it_tipo_turma = $conn->real_escape_string($_POST['id_tipo_turma']);
    $status = intval($_POST['status']);
    $sql = "UPDATE tb_turma SET vc_turma='$vc_turma', vc_descricao='$vc_descricao', vc_tipo='$id_tipo_turma', boo_status=$status WHERE id_turma=$update_id_turma";
    $conn->query($sql);
    header('Location: cadastrar_turma.php');
    exit();
}

// Processar cadastro
if ($_SERVER['REQUEST_METHOD'] == 'POST' && !isset($_POST['update_id_turma'])) {
    $vc_turma = $conn->real_escape_string($_POST['vc_turma']);
    $vc_descricao = $conn->real_escape_string($_POST['vc_descricao']);
    $id_tipo_turma = $conn->real_escape_string($_POST['id_tipo_turma']);
    $status = intval($_POST['status']);
    $sql = "INSERT INTO tb_turma (vc_turma, vc_descricao, id_tipo_turma, boo_status) VALUES ('$vc_turma', '$vc_descricao', '$id_tipo_turma', $status)";
    $conn->query($sql);
    header('Location: cadastrar_turma.php');
    exit();
}

// Consulta para obter tipos de turma
$sql = 'SELECT vc_tipo, id_tipo_turma FROM tb_tipo_turma'; // Certifique-se de que o nome da tabela e coluna estão corretos
$result = $conn->query($sql);

// Array para armazenar os tipos
$tipos = [];
if ($result->num_rows > 0) {
    $iContador = 0;
    while ($row = $result->fetch_assoc()) {
        $tipos[$iContador]['vc_tipo'] = $row['vc_tipo'];
	$tipos[$iContador]['id_tipo_turma'] = $row['id_tipo_turma'];
	$iContador++;
    }
}

// Consulta para obter turmas com ordenação
$sql = "SELECT t.id_turma, t.vc_turma, t.vc_descricao, t.id_tipo_turma, t.boo_status FROM tb_turma as t JOIN tb_tipo_turma as tt ON (tt.id_tipo_turma = t.id_tipo_turma) ORDER BY $order_by $order_direction";
$result = $conn->query($sql);
?>

<!DOCTYPE html>
<html lang="pt-Br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cadastrar Turma</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
</head>
<body>
<div class="container">
    <h2><?php echo isset($_GET['edit_id_turma']) ? 'Editar Turma' : 'Cadastrar Turma'; ?></h2>
    <form method="POST" action="cadastrar_turma.php">
        <?php
        if (isset($_GET['edit_id_turma'])) {
            $edit_id_turma = intval($_GET['edit_id_turma']);
            $sql = "SELECT * FROM tb_turma WHERE id_turma = $edit_id_turma";
            $result = $conn->query($sql);
            $row = $result->fetch_assoc();
            echo '<input type="hidden" name="update_id_turma" value="' . no_xss($row['id_turma']) . '">';
            echo '<div class="mb-3"><label for="vc_turma" class="form-label">Nome da Turma:</label><input type="text" class="form-control" id="vc_turma" name="vc_turma" value="' . no_xss($row['vc_turma']) . '" required></div>';
            echo '<div class="mb-3">
                <label for="id_tipo_turma" class="form-label">Tipo:</label>
                <select class="form-control" id="id_tipo_turma" name="id_tipo_turma" required>';
	    foreach ($tipos as $tipo) {
                $selected = ($tipo === $row['vc_tipo']) ? ' selected' : '';
                echo '<option value="' . no_xss($tipo['id_tipo_turma']) . '"' . $selected . '>' . no_xss($tipo['vc_tipo']) . '</option>';
            }
            echo '</select></div>';
            echo '<div class="mb-3"><label for="vc_descricao" class="form-label">Descrição:</label><textarea class="form-control" id="vc_descricao" name="vc_descricao" required>' . no_xss($row['vc_descricao']) . '</textarea></div>';
            echo '<div class="mb-3">
                <label for="status" class="form-label">Status:</label>
                <select class="form-control" id="status" name="status" required>
                    <option value="1"' . ($row['boo_status'] == 1 ? ' selected' : '') . '>Ativo</option>
                    <option value="0"' . ($row['boo_status'] == 0 ? ' selected' : '') . '>Inativo</option>
                </select>
            </div>';
        } else {
            echo '<div class="mb-3"><label for="vc_turma" class="form-label">Nome da Turma:</label><input type="text" class="form-control" id="vc_turma" name="vc_turma" required></div>';
            echo '<div class="mb-3"><label for="vc_descricao" class="form-label">Descrição:</label><textarea class="form-control" id="vc_descricao" name="vc_descricao" required></textarea></div>';
            echo '<div class="mb-3">
                <label for="id_tipo_turma" class="form-label">Tipo:</label>
                <select class="form-select" id="id_tipo_turma" name="id_tipo_turma" required>';
	    foreach ($tipos as $tipo) {
                echo '<option value="' . no_xss($tipo['id_tipo_turma']) . '">' . no_xss($tipo['vc_tipo']) . '</option>';
            }
            echo '</select></div>';
            echo '<div class="mb-3">
                <label for="status" class="form-label">Status:</label>
                <select class="form-control" id="status" name="status" required>
                    <option value="1">Ativo</option>
                    <option value="0">Inativo</option>
                </select>
            </div>';
        }
        ?>
        <button type="submit" class="btn btn-primary"><?php echo isset($_GET['edit_id_turma']) ? 'Atualizar' : 'Cadastrar'; ?></button>
    </form>

    <hr>

    <h2>Lista de Turmas</h2>
    <table class="table table-striped">
        <thead>
            <tr>
                <th><a href="?order_by=id_turma&order_direction=<?php echo $order_direction === 'asc' ? 'desc' : 'asc'; ?>">ID</a></th>
                <th><a href="?order_by=vc_turma&order_direction=<?php echo $order_direction === 'asc' ? 'desc' : 'asc'; ?>">Nome da Turma</a></th>
                <th><a href="?order_by=vc_descricao&order_direction=<?php echo $order_direction === 'asc' ? 'desc' : 'asc'; ?>">Descrição</a></th>
                <th><a href="?order_by=vc_tipo&order_direction=<?php echo $order_direction === 'asc' ? 'desc' : 'asc'; ?>">Tipo</a></th>
                <th><a href="?order_by=boo_status&order_direction=<?php echo $order_direction === 'asc' ? 'desc' : 'asc'; ?>">Status</a></th>
                <th>Ações</th>
            </tr>
        </thead>
        <tbody>
            <?php
            if ($result->num_rows > 0) {
                while ($row = $result->fetch_assoc()) {
                    echo "<tr>";
                    echo "<td>" . no_xss($row['id_turma']) . "</td>";
                    echo "<td>" . no_xss($row['vc_turma']) . "</td>";
                    echo "<td>" . no_xss($row['vc_descricao']) . "</td>";
                    echo "<td>" . no_xss($row['vc_tipo']) . "</td>";
                    echo "<td>" . ($row['boo_status'] == 1 ? 'Ativo' : 'Inativo') . "</td>";
                    echo "<td>";
                    echo '<a href="cadastrar_turma.php?edit_id_turma=' . no_xss($row['id_turma']) . '" class="btn btn-warning btn-sm">Editar</a> ';
                    echo '<a href="cadastrar_turma.php?delete_id_turma=' . no_xss($row['id_turma']) . '" class="btn btn-danger btn-sm" onclick="return confirm(\'Tem certeza que deseja excluir esta turma?\')">Excluir</a>';
                    echo "</td>";
                    echo "</tr>";
                }
            } else {
                echo "<tr><td colspan='6'>Nenhuma turma cadastrada.</td></tr>";
            }

            $conn->close();
            ?>
        </tbody>
    </table>

    <div class="mt-4">
        <a href="dashboard.php" class="btn btn-secondary">Voltar</a>
    </div>
</div>
</body>
</html>
