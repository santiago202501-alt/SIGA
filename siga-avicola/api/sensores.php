<?php
// ============================================================
//  SIGA – API Sensores (CRUD) + Lecturas
//  GET  api/sensores.php              → Listar con última lectura
//  GET  api/sensores.php?id=1         → Detalle + historial
//  POST api/sensores.php              → Crear sensor
//  PUT  api/sensores.php?id=1         → Actualizar sensor
//  DELETE api/sensores.php?id=1       → Eliminar sensor
//  POST api/sensores.php?lectura=1    → Registrar lectura
// ============================================================

require_once __DIR__ . '/../includes/db.php';

$method  = $_SERVER['REQUEST_METHOD'];
$id      = isset($_GET['id'])       ? (int)$_GET['id']      : null;
$lectura = isset($_GET['lectura']);
$db      = getDB();

// ── Registrar lectura ──────────────────────────────────────
if ($method === 'POST' && $lectura) {
    $data  = getJsonBody();
    $sid   = (int)($data['id_sensor'] ?? 0);
    $valor = (float)($data['valor']   ?? 0);
    if (!$sid) jsonResponse(['error' => 'id_sensor requerido'], 400);

    $db->prepare("INSERT INTO lectura_sensor (id_sensor, valor) VALUES (?,?)")->execute([$sid, $valor]);

    // Verificar umbrales y generar alerta automática
    $stmt = $db->prepare("
        SELECT s.id_galpon, ts.nombre AS tipo, ts.umbral_warn, ts.umbral_crit, ts.unidad
        FROM sensor s
        JOIN tipo_sensor ts ON ts.id = s.id_tipo
        WHERE s.id = ?
    ");
    $stmt->execute([$sid]);
    $sensor = $stmt->fetch();

    if ($sensor) {
        $tipo_alerta = null;
        $titulo = $desc = '';
        if ($sensor['umbral_crit'] !== null && $valor >= $sensor['umbral_crit']) {
            $tipo_alerta = 'crit';
            $titulo = "🚨 {$sensor['tipo']} crítico";
            $desc   = "{$sensor['tipo']} en {$valor} {$sensor['unidad']} – Límite crítico: {$sensor['umbral_crit']}";
        } elseif ($sensor['umbral_warn'] !== null && $valor >= $sensor['umbral_warn']) {
            $tipo_alerta = 'warn';
            $titulo = "⚠️ {$sensor['tipo']} en advertencia";
            $desc   = "{$sensor['tipo']} en {$valor} {$sensor['unidad']} – Límite seguro: {$sensor['umbral_warn']}";
        }
        if ($tipo_alerta) {
            $db->prepare("
                INSERT INTO alerta (id_galpon, id_sensor, tipo, titulo, descripcion)
                VALUES (?,?,?,?,?)
            ")->execute([$sensor['id_galpon'], $sid, $tipo_alerta, $titulo, $desc]);
        }
    }

    jsonResponse(['ok' => true, 'mensaje' => 'Lectura registrada', 'alerta_generada' => $tipo_alerta !== null]);
}

switch ($method) {
    case 'GET':
        if ($id) {
            // Sensor con historial de lecturas
            $stmt = $db->prepare("
                SELECT s.*, g.nombre AS galpon_nombre, ts.nombre AS tipo_nombre,
                       ts.unidad, ts.umbral_warn, ts.umbral_crit, ts.icono
                FROM sensor s
                JOIN galpon g      ON g.id  = s.id_galpon
                JOIN tipo_sensor ts ON ts.id = s.id_tipo
                WHERE s.id = ?
            ");
            $stmt->execute([$id]);
            $sensor = $stmt->fetch();
            if (!$sensor) jsonResponse(['error' => 'Sensor no encontrado'], 404);

            $stmt2 = $db->prepare("
                SELECT valor, fecha FROM lectura_sensor
                WHERE id_sensor = ? ORDER BY fecha DESC LIMIT 50
            ");
            $stmt2->execute([$id]);
            $sensor['lecturas'] = $stmt2->fetchAll();
            jsonResponse($sensor);
        } else {
            $stmt = $db->query("
                SELECT s.*, g.nombre AS galpon_nombre, ts.nombre AS tipo_nombre,
                       ts.unidad, ts.umbral_warn, ts.umbral_crit, ts.icono,
                       (SELECT ls.valor FROM lectura_sensor ls
                        WHERE ls.id_sensor = s.id
                        ORDER BY ls.fecha DESC LIMIT 1) AS ultima_lectura,
                       (SELECT ls.fecha FROM lectura_sensor ls
                        WHERE ls.id_sensor = s.id
                        ORDER BY ls.fecha DESC LIMIT 1) AS ultima_fecha
                FROM sensor s
                JOIN galpon g       ON g.id  = s.id_galpon
                JOIN tipo_sensor ts  ON ts.id = s.id_tipo
                ORDER BY g.nombre, ts.nombre
            ");
            jsonResponse($stmt->fetchAll());
        }
        break;

    case 'POST':
        $data     = getJsonBody();
        $id_galpon = (int)($data['id_galpon'] ?? 0);
        $id_tipo   = (int)($data['id_tipo']   ?? 0);
        $modelo    = trim($data['modelo']     ?? '');
        if (!$id_galpon || !$id_tipo) jsonResponse(['error' => 'Galpón y tipo son obligatorios'], 400);
        $db->prepare("INSERT INTO sensor (id_galpon, id_tipo, modelo) VALUES (?,?,?)")
           ->execute([$id_galpon, $id_tipo, $modelo]);
        jsonResponse(['ok' => true, 'id' => (int)$db->lastInsertId(), 'mensaje' => 'Sensor creado'], 201);
        break;

    case 'PUT':
        if (!$id) jsonResponse(['error' => 'ID requerido'], 400);
        $data   = getJsonBody();
        $campos = []; $vals = [];
        foreach (['modelo','estado'] as $f) {
            if (array_key_exists($f, $data)) { $campos[] = "$f=?"; $vals[] = $data[$f]; }
        }
        if (empty($campos)) jsonResponse(['error' => 'Sin datos'], 400);
        $vals[] = $id;
        $db->prepare("UPDATE sensor SET " . implode(',', $campos) . " WHERE id=?")->execute($vals);
        jsonResponse(['ok' => true, 'mensaje' => 'Sensor actualizado']);
        break;

    case 'DELETE':
        if (!$id) jsonResponse(['error' => 'ID requerido'], 400);
        $db->prepare("UPDATE sensor SET estado='inactivo' WHERE id=?")->execute([$id]);
        jsonResponse(['ok' => true, 'mensaje' => 'Sensor desactivado']);
        break;

    default:
        jsonResponse(['error' => 'Método no permitido'], 405);
}
