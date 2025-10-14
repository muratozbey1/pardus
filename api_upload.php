<?php
@ini_set('display_errors','0'); @error_reporting(E_ALL);
header('Content-Type: application/json; charset=utf-8');
if (function_exists('ob_start')) { @ob_start(); }
if (session_status() === PHP_SESSION_NONE) { @session_start(['cookie_httponly'=>true, 'cookie_samesite'=>'Lax']); }

$BASE = __DIR__;
define('USERDATA_DIR', $BASE.'/userdata');

function json_send($d,$c=200){
  if (function_exists('ob_get_clean')) { $noise = @ob_get_clean(); if (!empty($noise)) { $d['debug_noise'] = $noise; } }
  http_response_code($c);
  echo json_encode($d, JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES);
  exit;
}
function current_user(){
  if (!empty($_SESSION['username'])) return $_SESSION['username'];
  foreach(glob(USERDATA_DIR.'/*') as $d){ if(is_dir($d)) return basename($d); }
  $g = USERDATA_DIR.'/guest'; if(!is_dir($g)) @mkdir($g,0775,true);
  return 'guest';
}
function safe_folder($s){
  $s = trim((string)$s);
  if ($s === '' ) $s = 'root';
  $s = @iconv('UTF-8','ASCII//TRANSLIT//IGNORE',$s);
  $s = preg_replace('~[^A-Za-z0-9._-]+~','_', $s);
  $s = trim($s, '._-'); if ($s==='') $s='root';
  return $s;
}
function safe_filename($name){
  $name = trim((string)$name); if($name==='') $name='file';
  $name = preg_replace('~[\x00-\x1F\x7F]~','_', $name);
  $name = preg_replace('~[\\/<>:"|?*]+~','_', $name);
  $name = preg_replace('~\s+~',' ', $name);
  $name = rtrim($name, ". "); if($name==='') $name='file';
  if(strlen($name)>120) $name = substr($name,0,120);
  return $name;
}
function file_ext($n){ $p=strrpos($n,'.'); return $p!==false ? strtolower(substr($n,$p+1)) : ''; }
function mime_of($tmp){ $finfo = function_exists('finfo_open') ? finfo_open(FILEINFO_MIME_TYPE) : false; $m = $finfo ? finfo_file($finfo,$tmp) : ''; if ($finfo) finfo_close($finfo); return $m ?: 'application/octet-stream'; }

// Load policy
$cfgFile = $BASE.'/config/upload_policy.json';
$def = [
  'image_only' => true,
  'max_size_mb' => 20,
  'allowed_ext' => ['jpg','jpeg','png','gif','webp','pdf'],
  'blocked_ext' => ['php','phtml','php3','php4','php5','phar','pht','shtml','cgi','pl','py','exe','js','sh','bat','cmd','htm','html','svg'],
  'allowed_mime_prefix' => ['image/','application/pdf'],
];
$policy = $def;
if (is_file($cfgFile)){
  $j = json_decode(@file_get_contents($cfgFile), true);
  if (is_array($j)) $policy = array_merge($def, $j);
}
$maxBytes = max(1, (int)($policy['max_size_mb'] ?? 20)) * 1024 * 1024;

try{
  if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') json_send(['ok'=>false,'error'=>'POST required'], 405);
  if (!isset($_FILES['files'])) json_send(['ok'=>false,'error'=>'files[] not provided'], 400);

  $folder = safe_folder($_POST['folder'] ?? '');
  $user = current_user();

  $u = USERDATA_DIR.'/'.$user;         if (!is_dir($u))       @mkdir($u,0775,true);
  $filesDir = $u.'/files';             if (!is_dir($filesDir))@mkdir($filesDir,0775,true);
  $destDir = $filesDir.'/'.$folder;    if (!is_dir($destDir)) @mkdir($destDir,0775,true);

  $F = $_FILES['files'];
  $n = is_array($F['name']) ? count($F['name']) : 0;
  $saved = []; $errors = [];

  for($i=0; $i<$n; $i++){
    $name = $F['name'][$i] ?? 'file';
    $tmp  = $F['tmp_name'][$i] ?? '';
    $err  = (int)($F['error'][$i] ?? UPLOAD_ERR_OK);
    $size = (int)($F['size'][$i] ?? 0);

    $ext = file_ext($name);
    $mime = is_file($tmp) ? mime_of($tmp) : '';

    if($err !== UPLOAD_ERR_OK){ $errors[] = ['name'=>$name,'error'=>'upload_error_'.$err]; continue; }
    if(!is_file($tmp)){ $errors[] = ['name'=>$name,'error'=>'tmp_missing']; continue; }
    if($size <= 0){ $errors[] = ['name'=>$name,'error'=>'empty_file']; continue; }
    if($size > $maxBytes){ $errors[] = ['name'=>$name,'error'=>'too_large','max_mb'=>$policy['max_size_mb']]; @unlink($tmp); continue; }

    // Policy checks
    if (!empty($policy['image_only']) && strpos($mime, 'image/') !== 0){ $errors[]=['name'=>$name,'error'=>'not_image']; @unlink($tmp); continue; }
    if (!empty($policy['blocked_ext']) && in_array($ext, $policy['blocked_ext'])){ $errors[]=['name'=>$name,'error'=>'blocked_ext']; @unlink($tmp); continue; }
    if (!empty($policy['allowed_ext']) && !in_array($ext, $policy['allowed_ext'])){ $errors[]=['name'=>$name,'error'=>'ext_not_allowed']; @unlink($tmp); continue; }
    if (!empty($policy['allowed_mime_prefix'])){
      $okMime=false; foreach($policy['allowed_mime_prefix'] as $p){ if (strpos($mime, $p)===0){ $okMime=true; break; } }
      if (!$okMime){ $errors[]=['name'=>$name,'error'=>'mime_not_allowed','mime'=>$mime]; @unlink($tmp); continue; }
    }

    // Normalize name + avoid collisions
    $base = safe_filename($name);
    $out = $base; $k=1; $dot = strrpos($base,'.'); $stem = $dot!==false ? substr($base,0,$dot) : $base; $ext2  = $dot!==false ? substr($base,$dot) : '';
    while(file_exists($destDir.'/'.$out)){ $k++; $out = $stem.'-'.$k.$ext2; }

    $target = $destDir.'/'.$out;
    $ok = @move_uploaded_file($tmp, $target);
    if (!$ok) { $ok = @rename($tmp, $target); }
    if (!$ok) { $ok = @copy($tmp, $target); if($ok) @unlink($tmp); }
    if ($ok) { @chmod($target, 0664); $saved[] = ['name'=>$out, 'path'=>'userdata/'.$user.'/files/'.$folder.'/'.$out]; }
    else { $errors[] = ['name'=>$name,'error'=>'move_failed']; }
  }

  json_send(['ok'=>true, 'user'=>$user, 'folder'=>$folder, 'saved'=>$saved, 'errors'=>$errors, 'policy'=>$policy]);
}
catch(Throwable $e){
  json_send(['ok'=>false,'error'=>'exception','detail'=>$e->getMessage()], 500);
}
