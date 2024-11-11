<?php
include_once 'db.php';

header("Access-Control-Allow-Origin: " . $_SERVER['HTTP_ORIGIN']);
header("Access-Control-Allow-Credentials: true");
header("Access-Control-Allow-Methods: POST, GET, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");
header("Content-Type: application/json; charset=UTF-8");

if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    http_response_code(200);
    exit();
}

try {
    $pdo->beginTransaction();

    $required_fields = ['cliente_nome', 'cliente_telefone', 'num_pessoas', 'mesa_id', 'data_reserva', 'horario_reserva'];
    foreach ($required_fields as $field) {
        if (!isset($_POST[$field]) || empty($_POST[$field])) {
            throw new Exception("Campo obrigatório não preenchido: " . $field);
        }
    }

    $cliente_nome = $_POST['cliente_nome'];
    $cliente_telefone = $_POST['cliente_telefone'];
    $num_pessoas = $_POST['num_pessoas'];
    $mesa_id = $_POST['mesa_id'];
    $data_reserva = $_POST['data_reserva'];
    $horario_reserva = $_POST['horario_reserva'];

    $sql_check_reserva = "SELECT * FROM reservas 
                         WHERE mesa_id = :mesa_id 
                         AND data = :data_reserva 
                         AND horario = :horario_reserva 
                         AND status = 'confirmada'";

    $stmt_check_reserva = $pdo->prepare($sql_check_reserva);
    $stmt_check_reserva->execute([
        'mesa_id' => $mesa_id,
        'data_reserva' => $data_reserva,
        'horario_reserva' => $horario_reserva
    ]);

    if ($stmt_check_reserva->rowCount() > 0) {
        throw new Exception("Esta mesa já está reservada para este horário!");
    }

    $sql_cliente = "INSERT INTO clientes (nome, telefone) VALUES (:nome, :telefone)";
    $stmt_cliente = $pdo->prepare($sql_cliente);
    $stmt_cliente->execute([
        'nome' => $cliente_nome,
        'telefone' => $cliente_telefone
    ]);
    $clientes_id = $pdo->lastInsertId();

    $sql_reserva = "INSERT INTO reservas (clientes_id, mesa_id, data, horario, num_pessoas, status) 
                    VALUES (:clientes_id, :mesa_id, :data_reserva, :horario_reserva, :num_pessoas, 'confirmada')";

    $stmt_reserva = $pdo->prepare($sql_reserva);
    $stmt_reserva->execute([
        'clientes_id' => $clientes_id,
        'mesa_id' => $mesa_id,
        'data_reserva' => $data_reserva,
        'horario_reserva' => $horario_reserva,
        'num_pessoas' => $num_pessoas
    ]);

    $pdo->commit();
    echo json_encode(['success' => true, 'message' => 'Reserva realizada com sucesso!']);
} catch (Exception $e) {
    $pdo->rollBack();
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?>