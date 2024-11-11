<?php
include_once 'db.php';

$sql = "SELECT mesa_id, numero, capacidade, status FROM mesas";
$stmt = $pdo->query($sql);
$mesas = $stmt->fetchAll();

foreach ($mesas as $mesa) {
    if ($mesa['status'] == 'disponivel') {
        echo '<option value="' . $mesa['mesa_id'] . '">Mesa ' . $mesa['numero'] . ' - Capacidade: ' . $mesa['capacidade'] . ' pessoas</option>';
    } else {
        echo '<option value="' . $mesa['mesa_id'] . '" disabled style="color:gray;">Mesa ' . $mesa['numero'] . ' - Indisponível</option>';
    }
}