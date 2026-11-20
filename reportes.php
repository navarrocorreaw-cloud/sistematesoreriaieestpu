<?php
declare(strict_types=1);
require_once __DIR__.'/../includes/auth.php';
require_login();
require_role(['administrador']);
require_once __DIR__.'/../config/database.php';

$tablas = [
 'movimientos' => [
  'titulo'=>'Movimientos de tesorería', 'fecha'=>'m.fecha',
  'sql'=>"SELECT m.id AS 'ID',m.fecha AS 'Fecha',m.tipo AS 'Tipo',m.concepto AS 'Concepto',m.referencia AS 'Referencia',m.monto AS 'Monto (S/)',m.estado AS 'Estado',c.nombre AS 'Categoría',cb.banco AS 'Banco / cuenta',u.nombre AS 'Registrado por',uc.nombre AS 'Confirmado por' FROM movimientos m LEFT JOIN categorias c ON c.id=m.categoria_id LEFT JOIN cuentas_bancarias cb ON cb.id=m.cuenta_bancaria_id LEFT JOIN usuarios u ON u.id=m.usuario_id LEFT JOIN usuarios uc ON uc.id=m.confirmado_por ORDER BY m.fecha DESC,m.id DESC"
 ],
 'estudiantes' => [
  'titulo'=>'Registro de estudiantes', 'fecha'=>'e.creado_en',
  'sql'=>"SELECT e.id AS 'ID',e.dni AS 'DNI',e.nombres AS 'Nombres y apellidos',e.direccion AS 'Dirección',e.programa_estudio AS 'Programa de estudio',e.creado_en AS 'Fecha de registro' FROM estudiantes e ORDER BY e.nombres"
 ],
 'recibos' => [
  'titulo'=>'Recibos de ingreso a caja', 'fecha'=>'r.fecha_emision',
  'sql'=>"SELECT r.id AS 'ID',r.numero AS 'Número de recibo',r.fecha_emision AS 'Fecha de emisión',e.dni AS 'DNI',e.nombres AS 'Estudiante',e.direccion AS 'Dirección del estudiante',e.programa_estudio AS 'Programa de estudio',r.descripcion AS 'Descripción',r.cantidad AS 'Cantidad',r.precio_unitario AS 'Precio unitario (S/)',r.total AS 'Total (S/)',r.medio_pago AS 'Medio de pago',u.nombre AS 'Emitido por' FROM recibos r JOIN estudiantes e ON e.id=r.estudiante_id JOIN usuarios u ON u.id=r.emitido_por ORDER BY r.fecha_emision DESC,r.id DESC"
 ],
 'comprobantes' => [
  'titulo'=>'Comprobantes', 'fecha'=>'cp.creado_en',
  'sql'=>"SELECT cp.id AS 'ID',cp.movimiento_id AS 'Movimiento',cp.tipo_documento AS 'Tipo de documento',cp.numero_documento AS 'Número de documento',cp.fecha_documento AS 'Fecha del documento',cp.archivo AS 'Archivo',cp.observacion AS 'Observación',u.nombre AS 'Registrado por',cp.creado_en AS 'Fecha de registro' FROM comprobantes cp JOIN usuarios u ON u.id=cp.usuario_id ORDER BY cp.creado_en DESC"
 ],
 'categorias' => [
  'titulo'=>'Categorías de ingresos y egresos', 'fecha'=>null,
  'sql'=>"SELECT id AS 'ID',nombre AS 'Nombre',tipo AS 'Tipo',descripcion AS 'Descripción',CASE WHEN estado=1 THEN 'Activo' ELSE 'Inactivo' END AS 'Estado' FROM categorias ORDER BY tipo,nombre"
 ],
 'cuentas_bancarias' => [
  'titulo'=>'Cuentas bancarias', 'fecha'=>'cb.creado_en',
  'sql'=>"SELECT cb.id AS 'ID',cb.banco AS 'Banco',cb.tipo_cuenta AS 'Tipo de cuenta',cb.moneda AS 'Moneda',cb.numero_enmascarado AS 'Número de cuenta',cb.titular AS 'Titular',CASE WHEN cb.estado=1 THEN 'Activa' ELSE 'Inactiva' END AS 'Estado',cb.creado_en AS 'Fecha de registro' FROM cuentas_bancarias cb ORDER BY cb.banco"
 ],
 'cierres_caja' => [
  'titulo'=>'Cierres de caja', 'fecha'=>'cc.fecha',
  'sql'=>"SELECT cc.id AS 'ID',cc.fecha AS 'Fecha',cc.saldo_inicial AS 'Saldo inicial (S/)',cc.total_ingresos AS 'Ingresos (S/)',cc.total_egresos AS 'Egresos (S/)',cc.saldo_final AS 'Saldo final (S/)',cc.observaciones AS 'Observaciones',u.nombre AS 'Responsable',cc.creado_en AS 'Registrado el' FROM cierres_caja cc JOIN usuarios u ON u.id=cc.usuario_id ORDER BY cc.fecha DESC,cc.id DESC"
 ],
 'usuarios' => [
  'titulo'=>'Usuarios del sistema', 'fecha'=>'us.creado_en',
  'sql'=>"SELECT us.id AS 'ID',us.nombre AS 'Nombre',us.usuario AS 'Usuario',us.rol AS 'Rol',CASE WHEN us.estado=1 THEN 'Activo' ELSE 'Inactivo' END AS 'Estado',us.creado_en AS 'Fecha de creación' FROM usuarios us ORDER BY us.nombre"
 ],
 'auditoria' => [
  'titulo'=>'Auditoría de acciones', 'fecha'=>'au.creado_en',
  'sql'=>"SELECT au.id AS 'ID',au.creado_en AS 'Fecha y hora',u.nombre AS 'Usuario',au.accion AS 'Acción',au.tabla_afectada AS 'Tabla afectada',au.registro_id AS 'ID del registro',au.detalle AS 'Detalle',au.ip AS 'Dirección IP' FROM auditoria au LEFT JOIN usuarios u ON u.id=au.usuario_id ORDER BY au.creado_en DESC"
 ],
];

$tabla = (string)($_GET['tabla'] ?? 'movimientos');
if (!isset($tablas[$tabla])) { http_response_code(400); exit('Tabla de reporte no válida.'); }
$desde = (string)($_GET['desde'] ?? date('Y-m-01'));
$hasta = (string)($_GET['hasta'] ?? date('Y-m-d'));
$fechaValida = static fn(string $v): bool => (bool)preg_match('/^\d{4}-\d{2}-\d{2}$/',$v);
if (!$fechaValida($desde) || !$fechaValida($hasta) || $desde > $hasta) { http_response_code(400); exit('Rango de fechas no válido.'); }
$config = $tablas[$tabla];
$sql = $config['sql'];
$params = [];
if ($config['fecha'] !== null) {
 $sql = preg_replace('/ ORDER BY /i', ' WHERE DATE('.$config['fecha'].') BETWEEN ? AND ? ORDER BY ', $sql, 1);
 $params = [$desde,$hasta];
}
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$headers = [];
for ($i=0; $i<$stmt->columnCount(); $i++) { $meta=$stmt->getColumnMeta($i); if (!empty($meta['name'])) $headers[]=$meta['name']; }
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

if (($_GET['export'] ?? '') === 'excel') {
 $filename = 'reporte_'.$tabla.'_'.date('Ymd_His').'.csv';
 header('Content-Type: text/csv; charset=UTF-8');
 header('Content-Disposition: attachment; filename="'.$filename.'"');
 $out = fopen('php://output','w');
 fwrite($out,"\xEF\xBB\xBF");
 if ($headers) fputcsv($out,$headers,';');
 foreach ($rows as $row) fputcsv($out,array_values($row),';');
 fclose($out);
 exit;
}
function report_cell($value): string {
 if ($value === null || $value === '') return '—';
 return e((string)$value);
}
$queryBase = 'tabla='.rawurlencode($tabla).'&desde='.rawurlencode($desde).'&hasta='.rawurlencode($hasta);
?>
<!doctype html><html lang="es"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Reportes | Tesorería IESTP Utcubamba</title><link rel="stylesheet" href="../assets/css/style.css"><style>
.report-intro{color:#64748b}.report-actions{display:flex;gap:9px;flex-wrap:wrap}.report-table{width:100%;border-collapse:collapse}.report-table th,.report-table td{padding:9px 10px;border:1px solid #dbe3ef;text-align:left;font-size:13px;vertical-align:top}.report-table th{background:#eef3fa;color:#234f94;white-space:nowrap}.report-table-wrap{overflow:auto}.report-count{padding:12px 15px;background:#f2f6fc;border-radius:9px;margin:12px 0}.field-note{font-size:12px;color:#64748b;margin-top:6px}@media print{.sidebar,.topbar,.filters,.report-actions,.no-print{display:none!important}.layout{display:block}.main{padding:0!important}.panel{box-shadow:none!important;border:0!important;padding:0!important}.report-table{font-size:9px}.report-table th,.report-table td{font-size:9px;padding:5px;overflow-wrap:anywhere}h2{font-size:17px}@page{size:A4 landscape;margin:10mm}}
</style></head><body><div class="layout"><aside class="sidebar"><div class="brand"><img src="../assets/img/logo_iestp.png" alt="Logo"><div><strong>IESTP UTCUBAMBA</strong><small>Tesorería</small></div></div><nav><a href="../index.php">▦ Dashboard</a><a href="movimientos.php">⇄ Movimientos</a><a href="recibos.php">▧ Recibos</a><a class="active" href="reportes.php">▤ Reportes</a><a href="usuarios.php">⚙ Usuarios</a></nav><div class="sidebar-bottom"><a href="../logout.php">↪ Cerrar sesión</a></div></aside><main class="main">
<header class="topbar"><div><span class="eyebrow">ACCESO EXCLUSIVO DEL ADMINISTRADOR</span><h1>Reportes de todas las tablas</h1><p>Selecciona la información que necesitas y expórtala a Excel o PDF.</p></div><a class="btn btn-light" href="../index.php">Dashboard</a></header>
<section class="panel filters"><h3>Configurar reporte</h3><form method="get" class="form-grid"><div class="wide"><label>Tabla o módulo</label><select name="tabla" onchange="this.form.submit()"><?php foreach($tablas as $key=>$item):?><option value="<?=e($key)?>" <?=$tabla===$key?'selected':''?>><?=e($item['titulo'])?></option><?php endforeach;?></select></div><?php if($config['fecha']!==null):?><div><label>Fecha desde</label><input type="date" name="desde" value="<?=e($desde)?>" required></div><div><label>Fecha hasta</label><input type="date" name="hasta" value="<?=e($hasta)?>" required></div><?php else:?><input type="hidden" name="desde" value="<?=e($desde)?>"><input type="hidden" name="hasta" value="<?=e($hasta)?>"><p class="field-note wide">Esta tabla no contiene un filtro de fecha aplicable; se mostrarán todos sus registros.</p><?php endif;?><div class="wide"><button class="btn">Consultar tabla</button></div></form></section>
<section class="panel"><div class="panel-head"><div><h2><?=e($config['titulo'])?></h2><p class="report-intro">Instituto de Educación Superior Tecnológico Público “Utcubamba” · RUC 20437454419</p><?php if($config['fecha']!==null):?><p class="report-intro">Periodo: <?=e($desde)?> al <?=e($hasta)?></p><?php endif;?></div><div class="report-actions"><a class="btn" href="?<?=$queryBase?>&export=excel">Descargar Excel</a><button class="btn btn-light" type="button" onclick="window.print()">Guardar PDF</button></div></div>
<div class="report-count"><strong><?=count($rows)?></strong> registro(s) encontrados</div><div class="report-table-wrap"><table class="report-table"><thead><tr><?php foreach($headers as $head):?><th><?=e((string)$head)?></th><?php endforeach;?></tr></thead><tbody><?php foreach($rows as $row):?><tr><?php foreach($row as $value):?><td><?=report_cell($value)?></td><?php endforeach;?></tr><?php endforeach;?><?php if(!$rows):?><tr><td colspan="<?=max(1,count($headers))?>">No hay registros para mostrar con los filtros seleccionados.</td></tr><?php endif;?></tbody></table></div><p class="field-note no-print">Excel se descarga en CSV UTF-8 compatible con Excel. Para PDF, selecciona “Guardar como PDF” en la ventana de impresión.</p></section>
</main></div></body></html>
