<?php
header('Content-Type: application/json');
include_once 'db.php';

class ReservaController
{
    private $pdo;

    public function __construct($pdo)
    {
        $this->pdo = $pdo;
    }

    public function listarReservas()
    {
        try {
            $sql = "SELECT r.reserva_id, c.nome AS cliente_nome, c.telefone AS cliente_telefone,
                           m.numero AS mesa_numero, r.data, r.horario, r.num_pessoas, r.status
                    FROM reservas r
                    JOIN clientes c ON r.clientes_id = c.clientes_id
                    JOIN mesas m ON r.mesa_id = m.mesa_id
                    WHERE r.status = 'confirmada'
                    ORDER BY r.data, r.horario";

            $stmt = $this->pdo->query($sql);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            throw new Exception("Erro ao listar reservas: " . $e->getMessage());
        }
    }

    public function atualizarReserva($reserva_id, $cliente_nome, $cliente_telefone, $num_pessoas, $mesa_id, $data_reserva, $horario_reserva, $clientes_id)
    {
        try {

            $sql = "UPDATE reservas SET mesa_id = :mesa_id, data = :data_reserva, horario = :horario_reserva, num_pessoas = :num_pessoas 
                    WHERE reserva_id = :reserva_id";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([
                'reserva_id' => $reserva_id,
                'mesa_id' => $mesa_id,
                'data_reserva' => $data_reserva,
                'horario_reserva' => $horario_reserva,
                'num_pessoas' => $num_pessoas
            ]);

            $sql_cliente = "UPDATE clientes SET nome = :cliente_nome, telefone = :cliente_telefone WHERE clientes_id = :clientes_id";
            $stmt_cliente = $this->pdo->prepare($sql_cliente);
            $stmt_cliente->execute([
                'clientes_id' => $clientes_id,
                'cliente_nome' => $cliente_nome,
                'cliente_telefone' => $cliente_telefone
            ]);

            return true;
        } catch (Exception $e) {
            throw new Exception("Erro ao atualizar reserva: " . $e->getMessage());
        }
    }

    public function cancelarReserva($reserva_id)
    {
        try {
            $this->pdo->beginTransaction();

            $sql = "UPDATE reservas SET status = 'cancelada' WHERE reserva_id = :reserva_id";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute(['reserva_id' => $reserva_id]);

            $this->pdo->commit();
            return true;
        } catch (Exception $e) {
            $this->pdo->rollBack();
            throw new Exception("Erro ao cancelar reserva: " . $e->getMessage());
        }
    }
}

$controller = new ReservaController($pdo);

try {
    $acao = $_GET['acao'] ?? '';

    switch ($acao) {
        case 'listar':
            $reservas = $controller->listarReservas();
            echo json_encode($reservas);
            break;

        case 'atualizar':
            if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                $reserva_id = $_POST['reserva_id'];
                $cliente_nome = $_POST['nome_cliente'];
                $cliente_telefone = $_POST['telefone_cliente'];
                $num_pessoas = $_POST['num_pessoas'];
                $mesa_id = $_POST['mesa_id'];
                $data_reserva = $_POST['data_reserva'];
                $horario_reserva = $_POST['horario_reserva'];

                $sql_atual = "SELECT mesa_id, clientes_id, data FROM reservas WHERE reserva_id = :reserva_id";
                $stmt_atual = $pdo->prepare($sql_atual);
                $stmt_atual->execute(['reserva_id' => $reserva_id]);
                $reserva_atual = $stmt_atual->fetch(PDO::FETCH_ASSOC);

                if ($reserva_atual['mesa_id'] != $mesa_id || $reserva_atual['data'] != $data_reserva) {
                    $sql_check_reserva = "SELECT * FROM reservas 
                                         WHERE mesa_id = :mesa_id 
                                         AND data = :data_reserva 
                                         AND status = 'confirmada' 
                                         AND reserva_id != :reserva_id";

                    $stmt_check_reserva = $pdo->prepare($sql_check_reserva);
                    $stmt_check_reserva->execute([
                        'mesa_id' => $mesa_id,
                        'data_reserva' => $data_reserva,
                        'reserva_id' => $reserva_id
                    ]);

                    if ($stmt_check_reserva->rowCount() > 0) {
                        echo json_encode(['success' => false, 'error' => 'Esta mesa já está reservada para essa data!']);
                        exit;
                    }
                }

                $controller->atualizarReserva($reserva_id, $cliente_nome, $cliente_telefone, $num_pessoas, $mesa_id, $data_reserva, $horario_reserva, $reserva_atual['clientes_id']);
                echo json_encode(['success' => true, 'message' => 'Reserva atualizada com sucesso!']);
            } else {
                throw new Exception('Requisição inválida');
            }
            break;

        case 'cancelar':
            if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['reserva_id'])) {
                $reserva_id = $_POST['reserva_id'];
                $controller->cancelarReserva($reserva_id);
                echo json_encode(['success' => true, 'message' => 'Reserva excluida']);
            } else {
                throw new Exception('Requisição inválida');
            }
            break;

        default:
            throw new Exception('Ação não especificada');
    }
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
?>