<?php
// ============================================================
//  SIGA – API Tipos de Sensor (catálogo)
//  GET api/tipos_sensor.php
// ============================================================

require_once __DIR__ . '/../includes/db.php';

$db = getDB();
$tipos = $db->query("SELECT * FROM tipo_sensor ORDER BY nombre")->fetchAll();
jsonResponse($tipos);
