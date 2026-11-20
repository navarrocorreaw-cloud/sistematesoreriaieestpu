<?php
declare(strict_types=1);
if (session_status() !== PHP_SESSION_ACTIVE) { session_start(); }
function require_login(): void {
    if (empty($_SESSION['usuario'])) { header('Location: ' . (str_contains($_SERVER['PHP_SELF'],'/modules/') ? '../login.php' : 'login.php')); exit; }
}
function e(?string $value): string { return htmlspecialchars($value ?? '', ENT_QUOTES, 'UTF-8'); }
function csrf_token(): string { if(empty($_SESSION['csrf'])) $_SESSION['csrf']=bin2hex(random_bytes(32)); return $_SESSION['csrf']; }
function verify_csrf(): void { if($_SERVER['REQUEST_METHOD']==='POST' && !hash_equals($_SESSION['csrf'] ?? '', $_POST['csrf'] ?? '')) { http_response_code(419); exit('Solicitud no válida. Recarga la página e inténtalo nuevamente.'); } }
function audit(PDO $pdo,string $accion,string $tabla,?int $id=null,string $detalle=''): void {
  $s=$pdo->prepare("INSERT INTO auditoria(usuario_id,accion,tabla_afectada,registro_id,detalle,ip) VALUES(?,?,?,?,?,?)");
  $s->execute([$_SESSION['usuario']['id']??null,$accion,$tabla,$id,$detalle,$_SERVER['REMOTE_ADDR']??null]);
}
function require_role(array $roles): void { if(!in_array($_SESSION['usuario']['rol']??'', $roles,true)){http_response_code(403);exit('No tienes permisos para realizar esta operación.');} }
