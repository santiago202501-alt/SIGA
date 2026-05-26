<?php
// ============================================================
//  SIGA – API Dashboard (resumen general)
//  GET  api/dashboard.php  → KPIs, alertas recientes, consumos
// ============================================================

require_once __DIR__ . '/../includes/db.php';

$db = getDB();

// ── KPIs globales ─────────────────────────────────────────
$aves_activas = (int)$db->query("
    SELECT COALESCE(SUM(cantidad_actual),0) FROM lote WHERE estado='activo'
")->fetchColumn();

$temp_promedio = (float)$db->query("
    SELECT AVG(ls.valor)
    FROM lectura_sensor ls
    JOIN sensor s      ON s.id  = ls.id_sensor
    JOIN tipo_sensor ts ON ts.id = s.id_tipo
    WHERE ts.nombre = 'Temperatura'
    AND ls.fecha >= DATE_SUB(NOW(), INTERVAL 1 HOUR)
")->fetchColumn();

$amoniaco_promedio = (float)$db->query("
    SELECT AVG(ls.valor)
    FROM lectura_sensor ls
    JOIN sensor s      ON s.id  = ls.id_sensor
    JOIN tipo_sensor ts ON ts.id = s.id_tipo
    WHERE ts.nombre = 'Amoníaco'
    AND ls.fecha >= DATE_SUB(NOW(), INTERVAL 1 HOUR)
")->fetchColumn();

$consumo_alimento = (float)$db->query("
    SELECT COALESCE(SUM(cantidad),0) FROM consumo
    WHERE tipo='alimento' AND fecha = CURDATE()
")->fetchColumn();

$consumo_agua = (float)$db->query("
    SELECT COALESCE(SUM(cantidad),0) FROM consumo
    WHERE tipo='agua' AND fecha = CURDATE()
")->fetchColumn();

// ── Determinar estados ─────────────────────────────────────
function getEstado($valor, $warn, $crit): string {
    if ($valor === null) return 'ok';
    if ($valor >= $crit) return 'crit';
    if ($valor >= $warn) return 'warn';
    return 'ok';
}

$kpis = [
    [
        'label'  => 'Temperatura Promedio',
        'valor'  => $temp_promedio ? round($temp_promedio, 1) : '--',
        'unidad' => '°C',
        'icono'  => '🌡️',
        'estado' => getEstado($temp_promedio, 30, 35),
    ],
    [
        'label'  => 'Nivel de Amoníaco',
        'valor'  => $amoniaco_promedio ? round($amoniaco_promedio, 1) : '--',
        'unidad' => 'ppm',
        'icono'  => '💨',
        'estado' => getEstado($amoniaco_promedio, 25, 35),
    ],
    [
        'label'  => 'Aves Activas',
        'valor'  => number_format($aves_activas, 0, ',', '.'),
        'unidad' => 'pollos',
        'icono'  => '🐔',
        'estado' => 'ok',
    ],
    [
        'label'  => 'Consumo Alimento',
        'valor'  => number_format($consumo_alimento, 0),
        'unidad' => 'kg / día',
        'icono'  => '🌾',
        'estado' => 'ok',
    ],
    [
        'label'  => 'Consumo Agua',
        'valor'  => number_format($consumo_agua / 1000, 1),
        'unidad' => 'm³ / día',
        'icono'  => '💧',
        'estado' => 'ok',
    ],
];

// ── Consumo alimento últimos 7 días ───────────────────────
$stmt = $db->query("
    SELECT DATE_FORMAT(fecha,'%a') AS dia, SUM(cantidad) AS total
    FROM consumo WHERE tipo='alimento'
    AND fecha >= DATE_SUB(CURDATE(), INTERVAL 6 DAY)
    GROUP BY fecha ORDER BY fecha ASC
");
$consumo_semanal = $stmt->fetchAll();

// ── Alertas recientes ─────────────────────────────────────
$alertas_recientes = $db->query("
    SELECT a.tipo, a.titulo, a.descripcion, a.leida,
           DATE_FORMAT(a.created_at,'%H:%i') AS hora,
           g.nombre AS galpon_nombre
    FROM alerta a
    LEFT JOIN galpon g ON g.id = a.id_galpon
    ORDER BY a.leida ASC, a.created_at DESC
    LIMIT 5
")->fetchAll();

$no_leidas = (int)$db->query("SELECT COUNT(*) FROM alerta WHERE leida=0")->fetchColumn();

// ── Resumen galpones ──────────────────────────────────────
$galpones_resumen = $db->query("
    SELECT g.id, g.nombre, g.capacidad,
           COALESCE(SUM(CASE WHEN l.estado='activo' THEN l.cantidad_actual ELSE 0 END),0) AS aves_activas,
           (SELECT ls.valor FROM lectura_sensor ls
            JOIN sensor sx ON sx.id = ls.id_sensor
            JOIN tipo_sensor ts ON ts.id = sx.id_tipo
            WHERE sx.id_galpon = g.id AND ts.nombre = 'Temperatura'
            ORDER BY ls.fecha DESC LIMIT 1) AS temp
    FROM galpon g
    LEFT JOIN lote l ON l.id_galpon = g.id
    GROUP BY g.id
    ORDER BY g.nombre
")->fetchAll();

jsonResponse([
    'kpis'             => $kpis,
    'consumo_semanal'  => $consumo_semanal,
    'alertas_recientes'=> $alertas_recientes,
    'no_leidas'        => $no_leidas,
    'galpones_resumen' => $galpones_resumen,
]);
