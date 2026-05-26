<?php
// ============================================================
//  SIGA – API Lotes (CRUD)
//  Endpoint: api/lotes.php
// ============================================================

require_once __DIR__ . '/../includes/db.php';

$method = $_SERVER['REQUEST_METHOD'];
$id     = isset($_GET['id'])       ? (int)$_GET['id']       : null;
$galpon = isset($_GET['galpon_id']) ? (int)$_GET['galpon_id'] : null;
$db     = getDB();

switch ($method) {

    case 'GET':
        if ($id) {
            $stmt = $db->prepare("
                SELECT l.*, g.nombre AS galpon_nombre
                FROM lote l
                JOIN galpon g ON g.id = l.id_galpon
                WHERE l.id = ?
            ");
            $stmt->execute([$id]);
            $row = $stmt->fetch();
            if (!$row) jsonResponse(['error' => 'Lote no encontrado'], 404);
            jsonResponse($row);
        } else {
            $sql = "
                SELECT l.*, g.nombre AS galpon_nombre,
                       (l.cantidad_inicial - l.cantidad_actual) AS mortalidad,
                       ROUND(((l.cantidad_inicial - l.cantidad_actual) / l.cantidad_inicial * 100), 2) AS pct_mortalidad
                FROM lote l
                JOIN galpon g ON g.id = l.id_galpon
            ";
            $params = [];
            if ($galpon) { $sql .= " WHERE l.id_galpon = ?"; $params[] = $galpon; }
            $sql .= " ORDER BY l.fecha_ingreso DESC";
            $stmt = $db->prepare($sql);
            $stmt->execute($params);
            jsonResponse($stmt->fetchAll());
        }
        break;

    case 'POST':
        $data = getJsonBody();
        $id_galpon = (int)($data['id_galpon'] ?? 0);
        $cantidad  = (int)($data['cantidad_inicial'] ?? 0);
        $fecha     = $data['fecha_ingreso'] ?? date('Y-m-d');
        $obs       = trim($data['observaciones'] ?? '');

        if (!$id_galpon || $cantidad <= 0) {
            jsonResponse(['error' => 'Galpón y cantidad inicial son obligatorios'], 400);
        }

        // Generar código único
        $stmt = $db->query("SELECT COUNT(*)+1 AS n FROM lote");
        $n    = $stmt->fetchColumn();
        $codigo = 'L' . str_pad($n, 3, '0', STR_PAD_LEFT);

        $stmt = $db->prepare("
            INSERT INTO lote (id_galpon, codigo, fecha_ingreso, cantidad_inicial, cantidad_actual, observaciones)
            VALUES (?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute([$id_galpon, $codigo, $fecha, $cantidad, $cantidad, $obs]);
        jsonResponse(['ok' => true, 'id' => (int)$db->lastInsertId(), 'codigo' => $codigo, 'mensaje' => 'Lote registrado'], 201);
        break;

    case 'PUT':
        if (!$id) jsonResponse(['error' => 'ID requerido'], 400);
        $data   = getJsonBody();
        $campos = [];
        $vals   = [];
        foreach (['cantidad_actual','estado','observaciones'] as $f) {
            if (array_key_exists($f, $data)) {
                $campos[] = "$f = ?";
                $vals[]   = $data[$f];
            }
        }
        if (empty($campos)) jsonResponse(['error' => 'Sin datos para actualizar'], 400);
        $vals[] = $id;
        $db->prepare("UPDATE lote SET " . implode(', ', $campos) . " WHERE id = ?")->execute($vals);
        jsonResponse(['ok' => true, 'mensaje' => 'Lote actualizado']);
        break;

    case 'DELETE':
        if (!$id) jsonResponse(['error' => 'ID requerido'], 400);
        // Solo cerrar, no borrar físicamente
        $db->prepare("UPDATE lote SET estado='cerrado' WHERE id=?")->execute([$id]);
        jsonResponse(['ok' => true, 'mensaje' => 'Lote cerrado']);
        break;

    default:
        jsonResponse(['error' => 'Método no permitido'], 405);
}
