<?php
declare(strict_types=1);
require_once __DIR__.'/../includes/auth.php'; require_login();
require_once __DIR__.'/../config/database.php'; verify_csrf();
$mensaje=''; $error='';
$programas=['Arquitectura de Plataformas y Servicios de TI','Enfermería Técnica','Contabilidad','Producción Agropecuaria'];
if($_SERVER['REQUEST_METHOD']==='POST'){
  require_role(['administrador','tesorero']);
  $dni=trim($_POST['dni']??''); $nombre=trim($_POST['nombres']??'');
  $direccion=trim($_POST['direccion']??''); $programa=$_POST['programa_estudio']??'';
  if(!preg_match('/^\d{8}$/',$dni)||$nombre===''||!in_array($programa,$programas,true)) $error='Verifica DNI (8 dígitos), nombres y programa de estudio.';
  else try{$q=$pdo->prepare('INSERT INTO estudiantes(dni,nombres,direccion,programa_estudio) VALUES(?,?,?,?)');$q->execute([$dni,$nombre,$direccion,$programa]);audit($pdo,'REGISTRAR','estudiantes',(int)$pdo->lastInsertId(),'Alta de estudiante');$mensaje='Estudiante registrado correctamente.';}catch(PDOException $e){$error='Ese DNI ya está registrado. Busca al estudiante en la lista.';}
}
$q=trim($_GET['q']??'');
$sql='SELECT * FROM estudiantes'; $args=[];
if($q!==''){$sql.=' WHERE dni LIKE ? OR nombres LIKE ?';$args=['%'.$q.'%','%'.$q.'%'];}
$sql.=' ORDER BY nombres LIMIT 500';$st=$pdo->prepare($sql);$st->execute($args);$rows=$st->fetchAll();
?>
<!doctype html><html lang="es"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Estudiantes | Tesorería</title><link rel="stylesheet" href="../assets/css/style.css"></head><body><div class="layout"><aside class="sidebar"><div class="brand"><img src="../assets/img/logo_iestp.png" alt=""><div><strong>IESTP UTCUBAMBA</strong><small>Tesorería</small></div></div><nav><a href="../index.php">▦ Dashboard</a><a class="active" href="estudiantes.php">♙ Estudiantes</a><a href="recibos.php">▧ Emitir recibo</a><a href="movimientos.php">⇄ Movimientos</a><?php if($_SESSION['usuario']['rol']==='administrador'):?><a href="reportes.php">▤ Reportes</a><?php endif;?></nav><div class="sidebar-bottom"><a href="../logout.php">↪ Cerrar sesión</a></div></aside><main class="main"><header class="topbar"><div><span class="eyebrow">REGISTRO ACADÉMICO</span><h1>Estudiantes</h1><p>Consulta y administra los datos necesarios para emitir recibos.</p></div><a class="btn btn-light" href="../index.php">Dashboard</a></header>
<?php if($mensaje):?><p class="notice"><?=e($mensaje)?></p><?php endif;?><?php if($error):?><p class="error"><?=e($error)?></p><?php endif;?>
<?php if(in_array($_SESSION['usuario']['rol'],['administrador','tesorero'],true)):?><section class="panel"><h3>Registrar estudiante</h3><form method="post" class="form-grid"><input type="hidden" name="csrf" value="<?=e(csrf_token())?>"><div><label>DNI (8 dígitos)</label><input name="dni" inputmode="numeric" pattern="[0-9]{8}" maxlength="8" required></div><div><label>Nombres y apellidos</label><input name="nombres" maxlength="150" required></div><div><label>Dirección del estudiante</label><input name="direccion" maxlength="220"></div><div><label>Programa de estudio</label><select name="programa_estudio" required><option value="">Seleccionar</option><?php foreach($programas as $p):?><option><?=e($p)?></option><?php endforeach;?></select></div><div class="wide"><button class="btn">Guardar estudiante</button></div></form></section><?php endif;?>
<section class="panel"><div class="panel-head"><h3>Directorio de estudiantes</h3><form method="get" class="search-form"><input name="q" placeholder="Buscar por DNI o nombre" value="<?=e($q)?>"><button class="btn">⌕ Buscar</button></form></div><div class="table-wrap"><table><thead><tr><th>DNI</th><th>Nombre completo</th><th>Programa de estudio</th><th>Dirección</th></tr></thead><tbody><?php foreach($rows as $r):?><tr><td><?=e($r['dni'])?></td><td><?=e($r['nombres'])?></td><td><?=e($r['programa_estudio'])?></td><td><?=e($r['direccion'])?></td></tr><?php endforeach;?><?php if(!$rows):?><tr><td colspan="4">No hay estudiantes registrados.</td></tr><?php endif;?></tbody></table></div></section></main></div></body></html>