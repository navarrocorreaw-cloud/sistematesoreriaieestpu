<?php
declare(strict_types=1);
require_once __DIR__.'/../includes/auth.php'; require_login();
require_once __DIR__.'/../config/database.php';
verify_csrf();
$mensaje=''; $error='';
$uploadDir=__DIR__.'/../uploads/comprobantes/';
if(!is_dir($uploadDir)) mkdir($uploadDir,0750,true);
if($_SERVER['REQUEST_METHOD']==='POST'){
  require_role(['administrador','tesorero']);
  $mov=(int)($_POST['movimiento_id']??0); $tipo=trim($_POST['tipo_documento']??''); $num=trim($_POST['numero_documento']??''); $fecha=$_POST['fecha_documento']??''; $obs=trim($_POST['observacion']??'');
  $q=$pdo->prepare("SELECT id FROM movimientos WHERE id=?"); $q->execute([$mov]);
  if(!$q->fetch() || $tipo==='' || $num==='' || !preg_match('/^\d{4}-\d{2}-\d{2}$/',$fecha)) $error='Completa los datos obligatorios y selecciona un movimiento válido.';
  elseif(isset($_FILES['archivo']) && $_FILES['archivo']['error']!==UPLOAD_ERR_NO_FILE){
    $f=$_FILES['archivo']; $allowed=['pdf'=>'application/pdf','jpg'=>'image/jpeg','jpeg'=>'image/jpeg','png'=>'image/png'];
    $ext=strtolower(pathinfo($f['name'],PATHINFO_EXTENSION)); $mime=(new finfo(FILEINFO_MIME_TYPE))->file($f['tmp_name']);
    if($f['error']!==UPLOAD_ERR_OK || $f['size']>8*1024*1024 || !isset($allowed[$ext]) || $allowed[$ext]!==$mime) $error='Archivo inválido. Usa PDF, JPG o PNG de máximo 8 MB.';
  }
  if(!$error){
    $du=$pdo->prepare("SELECT id FROM comprobantes WHERE tipo_documento=? AND numero_documento=? LIMIT 1"); $du->execute([$tipo,$num]);
    if($du->fetch()) $error='Ya existe un comprobante con ese tipo y número.';
  }
  if(!$error){
    $file=null;
    if(isset($_FILES['archivo']) && $_FILES['archivo']['error']===UPLOAD_ERR_OK){
      $file=bin2hex(random_bytes(18)).'.'.$ext;
      if(!move_uploaded_file($_FILES['archivo']['tmp_name'],$uploadDir.$file)) $error='No se pudo guardar el archivo.';
    }
    if(!$error){
      $s=$pdo->prepare("INSERT INTO comprobantes(movimiento_id,tipo_documento,numero_documento,fecha_documento,archivo,observacion,usuario_id) VALUES(?,?,?,?,?,?,?)");
      $s->execute([$mov,$tipo,$num,$fecha,$file,$obs,$_SESSION['usuario']['id']]);
      audit($pdo,'REGISTRAR','comprobantes',(int)$pdo->lastInsertId(),"Comprobante $tipo $num");
      $mensaje='Comprobante registrado correctamente.';
    }
  }
}
$busca=trim($_GET['q']??'');
$sql="SELECT c.*,m.tipo,m.fecha,m.concepto,m.monto,u.nombre responsable FROM comprobantes c JOIN movimientos m ON m.id=c.movimiento_id JOIN usuarios u ON u.id=c.usuario_id";
$args=[];
if($busca!==''){ $sql.=" WHERE c.numero_documento LIKE ? OR c.tipo_documento LIKE ? OR m.concepto LIKE ? OR m.referencia LIKE ?"; $like="%$busca%"; $args=[$like,$like,$like,$like];}
$sql.=" ORDER BY c.id DESC";
$s=$pdo->prepare($sql);$s->execute($args);$rows=$s->fetchAll();
$moves=$pdo->query("SELECT id,tipo,fecha,concepto,monto FROM movimientos ORDER BY fecha DESC,id DESC")->fetchAll();
?>
<!doctype html><html lang="es"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Comprobantes | Tesorería</title><link rel="stylesheet" href="../assets/css/style.css"></head><body><div class="layout"><aside class="sidebar"><div class="brand"><img src="../assets/img/logo_iestp.png"><div><strong>IESTP UTCUBAMBA</strong><small>Tesorería · 2026</small></div></div><nav><a href="../index.php">▦ Dashboard</a><a href="movimientos.php">⇄ Movimientos</a><a class="active" href="comprobantes.php">▤ Comprobantes</a><a href="usuarios.php">♙ Usuarios</a></nav><div class="sidebar-bottom"><a href="../logout.php">↪ Cerrar sesión</a></div></aside><main class="main"><header class="topbar"><div><span class="eyebrow">SUSTENTO DOCUMENTARIO</span><h1>Gestión de comprobantes</h1><p>Registra, adjunta y consulta documentos vinculados a movimientos.</p></div><a class="btn btn-light" href="../index.php">Volver</a></header>
<?php if($mensaje):?><p class="notice"><?=e($mensaje)?></p><?php endif;?><?php if($error):?><p class="error"><?=e($error)?></p><?php endif;?>
<?php if(in_array($_SESSION['usuario']['rol'],['administrador','tesorero'],true)):?><section class="panel"><h3>Registrar comprobante</h3><form method="post" enctype="multipart/form-data" class="form-grid"><input type="hidden" name="csrf" value="<?=e(csrf_token())?>"><div class="wide"><label>Movimiento relacionado</label><select name="movimiento_id" required><option value="">Selecciona un movimiento</option><?php foreach($moves as $m):?><option value="<?=$m['id']?>">#<?=$m['id']?> · <?=$m['tipo']?> · <?=e($m['fecha'])?> · <?=e($m['concepto'])?> · S/ <?=number_format((float)$m['monto'],2)?></option><?php endforeach;?></select></div><div><label>Tipo de documento</label><select name="tipo_documento" required><option value="">Selecciona</option><option>Boleta</option><option>Factura</option><option>Recibo</option><option>Voucher</option><option>Nota de crédito</option><option>Otro</option></select></div><div><label>Número de documento</label><input name="numero_documento" maxlength="100" required></div><div><label>Fecha del documento</label><input type="date" name="fecha_documento" value="<?=date('Y-m-d')?>" required></div><div><label>Archivo (PDF/JPG/PNG, máximo 8 MB)</label><input type="file" name="archivo" accept=".pdf,.jpg,.jpeg,.png,application/pdf,image/jpeg,image/png"></div><div class="wide"><label>Observación</label><input name="observacion" maxlength="500"></div><div class="wide"><button class="btn">Guardar comprobante</button></div></form></section><?php endif;?>
<section class="panel"><div class="panel-head"><h3>Comprobantes registrados</h3><form method="get" class="search-form"><input name="q" placeholder="Buscar número, tipo o concepto" value="<?=e($busca)?>"><button class="btn">⌕ Buscar</button></form></div><div class="table-wrap"><table><thead><tr><th>Fecha</th><th>Documento</th><th>Movimiento</th><th>Monto</th><th>Archivo</th><th>Registrado por</th><th>Observación</th></tr></thead><tbody><?php foreach($rows as $r):?><tr><td><?=e($r['fecha_documento'])?></td><td><?=e($r['tipo_documento'])?> · <?=e($r['numero_documento'])?></td><td>#<?=$r['movimiento_id']?> · <?=e($r['concepto'])?></td><td>S/ <?=number_format((float)$r['monto'],2)?></td><td><?php if($r['archivo']):?><a target="_blank" rel="noopener" href="../uploads/comprobantes/<?=rawurlencode($r['archivo'])?>">Ver archivo</a><?php else:?>Sin archivo<?php endif;?></td><td><?=e($r['responsable'])?></td><td><?=e($r['observacion'])?></td></tr><?php endforeach;?><?php if(!$rows):?><tr><td colspan="7">No hay comprobantes para mostrar.</td></tr><?php endif;?></tbody></table></div></section><footer>Los documentos se almacenan con nombre aleatorio y validación de tipo y tamaño.</footer></main></div></body></html>