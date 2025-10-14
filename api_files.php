<?php
@ini_set('display_errors','0'); @error_reporting(E_ALL);
header('Content-Type: application/json; charset=utf-8');
if (session_status() === PHP_SESSION_NONE) { @session_start(['cookie_httponly'=>true, 'cookie_samesite'=>'Lax']); }
$BASE = __DIR__;
define('USERDATA_DIR', $BASE.'/userdata');

function json_send($d,$c=200){ http_response_code($c); echo json_encode($d,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES); exit; }
function current_user(){
  if(!empty($_SESSION['username'])) return $_SESSION['username'];
  foreach(glob(USERDATA_DIR.'/*') as $d){ if(is_dir($d)) return basename($d); }
  $g=USERDATA_DIR.'/guest'; if(!is_dir($g)) @mkdir($g,0775,true);
  return 'guest';
}
function safe_folder($s){ $s=trim((string)$s); if($s==='') $s='root'; $s=@iconv('UTF-8','ASCII//TRANSLIT//IGNORE',$s); $s=preg_replace('~[^A-Za-z0-9._-]+~','_',$s); $s=trim($s,'._-'); return $s?:'root'; }
function safe_name($n){ $n=trim((string)$n); if($n==='') $n='file'; $n=preg_replace('~[\\x00-\\x1F\\x7F]~','_',$n); $n=preg_replace('~[\\\\/<>:"|?*]+~','_',$n); $n=preg_replace('~\\s+~',' ',$n); $n=rtrim($n,'. '); if($n==='') $n='file'; if(strlen($n)>120) $n=substr($n,0,120); return $n; }

try{
  $method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
  $action = ($method==='POST') ? ($_POST['action'] ?? '') : ($_GET['action'] ?? 'list');
  $user   = current_user();

  if ($action === 'list'){
    $folder = safe_folder($_GET['folder'] ?? '');
    $dir = USERDATA_DIR.'/'.$user.'/files/'.$folder;
    if(!is_dir($dir)) json_send(['ok'=>true, 'user'=>$user, 'folder'=>$folder, 'files'=>[]]);
    $files = [];
    foreach(scandir($dir) as $f){
      if($f==='.'||$f==='..') continue;
      $p=$dir.'/'.$f; if(is_file($p)){ $files[]=['name'=>$f,'url'=>'userdata/'.$user.'/files/'.$folder.'/'.$f,'size'=>filesize($p),'mtime'=>filemtime($p)]; }
    }
    usort($files, function($a,$b){ return ($b['mtime']<=>$a['mtime']); });
    json_send(['ok'=>true,'user'=>$user,'folder'=>$folder,'files'=>$files]);
  }

  if ($action === 'delete'){
    $folder = safe_folder($_POST['folder'] ?? '');
    $name   = safe_name($_POST['name'] ?? '');
    $p = USERDATA_DIR.'/'.$user.'/files/'.$folder.'/'.$name;
    if (!is_file($p)) json_send(['ok'=>false,'error'=>'not_found']);
    $ok = @unlink($p);
    if ($ok) json_send(['ok'=>true,'name'=>$name]);
    json_send(['ok'=>false,'error'=>'unlink_failed']);
  }

  if ($action === 'rename'){
    $folder = safe_folder($_POST['folder'] ?? '');
    $name   = safe_name($_POST['name'] ?? '');
    $to     = safe_name($_POST['to'] ?? '');
    $dir    = USERDATA_DIR.'/'.$user.'/files/'.$folder;
    $src = $dir.'/'.$name; if(!is_file($src)) json_send(['ok'=>false,'error'=>'not_found']);
    $dst = $dir.'/'.$to;
    if ($src === $dst) json_send(['ok'=>true,'name'=>$to]);
    // auto-uniq if exists
    if (file_exists($dst)){
      $dot=strrpos($to,'.'); $stem=$dot!==false?substr($to,0,$dot):$to; $ext=$dot!==false?substr($to,$dot):''; $k=2;
      do { $dst = $dir.'/'.$stem.'-'.$k.$ext; $k++; } while (file_exists($dst) && $k<1000);
    }
    $ok = @rename($src,$dst);
    if ($ok) json_send(['ok'=>true,'name'=>basename($dst)]);
    json_send(['ok'=>false,'error'=>'rename_failed']);
  }

  // Optional: delete_folder (called by our desktop injector)
  if ($action === 'delete_folder'){
    $folder = safe_folder($_POST['folder'] ?? '');
    $dir = USERDATA_DIR.'/'.$user.'/files/'.$folder;
    if (!is_dir($dir)) json_send(['ok'=>true,'info'=>'not_exists']);
    // recursive remove
    $it = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($dir, RecursiveDirectoryIterator::SKIP_DOTS), RecursiveIteratorIterator::CHILD_FIRST);
    foreach($it as $f){ $f->isDir() ? @rmdir($f->getRealPath()) : @unlink($f->getRealPath()); }
    $ok = @rmdir($dir);
    json_send(['ok'=>$ok?true:false, 'folder'=>$folder]);
  }

  json_send(['ok'=>false,'error'=>'unknown_action']);
}catch(Throwable $e){ json_send(['ok'=>false,'error'=>'exception','detail'=>$e->getMessage()],500); }?>