<?php
// ============================================================
//  SIGA – API Galpones (CRUD)
//  Endpoint: api/galpones.php
//  GET    /api/galpones.php          → Listar todos
//  GET    /api/galpones.php?id=1     → Obtener uno
//  POST   /api/galpones.php          → Crear
//  PUT    /api/galpones.php?id=1     → Actualizar
//  DELETE /api/galpones.php?id=1     → Eliminar
// ============================================================

require_once __DIR__ . '/../includes/db.php';

$method = $_SERVER['REQUEST_METHOD'];
$id     = isset($_GET['id']) ? (int)$_GET['id'] : null;
$db     = getDB();

switch ($method) {

    // ── LISTAR / OBTENER ─────────────────────────────────
    case 'GET':
        if ($id) {
            $stmt = $db->prepare("
                SELECT g.*,
                       COUNT(DISTINCT l.id) AS total_lotes,
                       COUNT(DISTINCT s.id) AS total_sensores,
                       COALESCE(SUM(CASE WHEN l.estado='activo' THEN l.cantidad_actual ELSE 0 END),0) AS aves_activas
                FROM galpon g
                LEFT JOIN lote   l ON l.id_galpon = g.id
                LEFT JOIN sensor s ON s.id_galpon = g.id
                WHERE g.id = ?
                GROUP BY g.id
            ");
            $stmt->execute([$id]);
            $row = $stmt->fetch();
            if (!$row) jsonResponse(['error' => 'Galpón no encontrado'], 404);
            jsonResponse($row);
        } else {
            $stmt = $db->query("
                SELECT g.*,
                       COUNT(DISTINCT l.id) AS total_lotes,
                       COUNT(DISTINCT s.id) AS total_sensores,
                       COALESCE(SUM(CASE WHEN l.estado='activo' THEN l.cantidad_actual ELSE 0 END),0) AS aves_activas,
                       (SELECT ls.valor FROM lectura_sensor ls
                        JOIN sensor sx ON sx.id = ls.id_sensor
                        JOIN tipo_sensor ts ON ts.id = sx.id_tipo
                        WHERE sx.id_galpon = g.id AND ts.nombre = 'Temperatura'
                        ORDER BY ls.fecha DESC LIMIT 1) AS ultima_temp,
                       (SELECT ls.valor FROM lectura_sensor ls
                        JOIN sensor sx ON sx.id = ls.id_sensor
                        JOIN tipo_sensor ts ON ts.id = sx.id_tipo
                        WHERE sx.id_galpon = g.id AND ts.nombre = 'Amoníaco'
                        ORDER BY ls.fecha DESC LIMIT 1) AS ultimo_amoniaco
                FROM galpon g
                LEFT JOIN lote   l ON l.id_galpon = g.id
                LEFT JOIN sensor s ON s.id_galpon = g.id
                GROUP BY g.id
                ORDER BY g.nombre
            ");
            jsonResponse($stmt->fetchAll());
        }
        break;

    // ── CREAR ────────────────────────────────────────────
    case 'POST':
        $data = getJsonBody();
        $nombre    = trim($data['nombre']    ?? '');
        $ubicacion = trim($data['ubicacion'] ?? '');
        $capacidad = (int)($data['capacidad'] ?? 0);
        $desc      = trim($data['descripcion'] ?? '');

        if (!$nombre || $capacidad <= 0) {
            jsonResponse(['error' => 'Nombre y capacidad son obligatorios'], 400);
        }

        $stmt = $db->prepare("
            INSERT INTO galpon (nombre, ubicacion, capacidad, descripcion)
            VALUES (?, ?, ?, ?)
        ");
        $stmt->execute([$nombre, $ubicacion, $capacidad, $desc]);
        jsonResponse(['ok' => true, 'id' => (int)$db->lastInsertId(), 'mensaje' => 'Galpón creado'], 201);
        break;

    // ── ACTUALIZAR ───────────────────────────────────────
    case 'PUT':
        if (!$id) jsonResponse(['error' => 'ID requerido'], 400);
        $data = getJsonBody();
        $campos = [];
        $vals   = [];
        foreach (['nombre','ubicacion','capacidad','descripcion','estado'] as $f) {
            if (array_key_exists($f, $data)) {
                $campos[] = "$f = ?";
                $vals[]   = $data[$f];
            }
        }
        if (empty($campos)) jsonResponse(['error' => 'Sin datos para actualizar'], 400);
        $vals[] = $id;
        $db->prepare("UPDATE galpon SET " . implode(', ', $campos) . " WHERE id = ?")->execute($vals);
        jsonResponse(['ok' => true, 'mensaje' => 'Galpón actualizado']);
        break;

    // ── ELIMINAR ─────────────────────────────────────────
    case 'DELETE':
        if (!$id) jsonResponse(['error' => 'ID requerido'], 400);
        // Verificar que no tenga lotes activos
        $stmt = $db->prepare("SELECT COUNT(*) FROM lote WHERE id_galpon=? AND estado='activo'");
        $stmt->execute([$id]);
        if ($stmt->fetchColumn() > 0) {
            jsonResponse(['error' => 'No se puede eliminar: tiene lotes activos asociados'], 409);
        }
        $db->prepare("DELETE FROM galpon WHERE id=?")->execute([$id]);
        jsonResponse(['ok' => true, 'mensaje' => 'Galpón eliminado']);
        break;

    default:
        jsonResponse(['error' => 'Método no permitido'], 405);
}
