<?php
// ============================================================
//  SIGA – API Alertas
//  GET  api/alertas.php           → Listar alertas (no leídas primero)
//  PUT  api/alertas.php?id=1      → Marcar como leída
//  PUT  api/alertas.php?all=1     → Marcar todas como leídas
//  DELETE api/alertas.php?id=1    → Eliminar alerta
// ============================================================

require_once __DIR__ . '/../includes/db.php';

$method = $_SERVER['REQUEST_METHOD'];
$id     = isset($_GET['id'])  ? (int)$_GET['id']  : null;
$all    = isset($_GET['all']);
$db     = getDB();

switch ($method) {

    case 'GET':
        $solo_no_leidas = isset($_GET['no_leidas']);
        $sql = "
            SELECT a.*, g.nombre AS galpon_nombre,
                   CONCAT(ts.nombre, ' (', ts.unidad, ')') AS sensor_tipo
            FROM alerta a
            LEFT JOIN galpon     g  ON g.id  = a.id_galpon
            LEFT JOIN sensor     s  ON s.id  = a.id_sensor
            LEFT JOIN tipo_sensor ts ON ts.id = s.id_tipo
        ";
        if ($solo_no_leidas) $sql .= " WHERE a.leida = 0";
        $sql .= " ORDER BY a.leida ASC, a.created_at DESC LIMIT 100";
        $alertas = $db->query($sql)->fetchAll();

        // Contar no leídas para el badge
        $no_leidas = (int)$db->query("SELECT COUNT(*) FROM alerta WHERE leida=0")->fetchColumn();
        jsonResponse(['alertas' => $alertas, 'no_leidas' => $no_leidas]);
        break;

    case 'PUT':
        if ($all) {
            $db->query("UPDATE alerta SET leida=1");
            jsonResponse(['ok' => true, 'mensaje' => 'Todas las alertas marcadas como leídas']);
        } elseif ($id) {
            $db->prepare("UPDATE alerta SET leida=1 WHERE id=?")->execute([$id]);
            jsonResponse(['ok' => true, 'mensaje' => 'Alerta marcada como leída']);
        } else {
            jsonResponse(['error' => 'ID requerido'], 400);
        }
        break;

    case 'DELETE':
        if (!$id) jsonResponse(['error' => 'ID requerido'], 400);
        $db->prepare("DELETE FROM alerta WHERE id=?")->execute([$id]);
        jsonResponse(['ok' => true, 'mensaje' => 'Alerta eliminada']);
        break;

    default:
        jsonResponse(['error' => 'Método no permitido'], 405);
}
