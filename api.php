<?php
@ini_set('display_errors','1'); @error_reporting(E_ALL);
if (session_status() === PHP_SESSION_NONE) { @session_start(['cookie_httponly'=>true, 'cookie_samesite'=>'Lax']); }
$BASE = __DIR__;
define('USERDATA_DIR', $BASE.'/userdata');
define('DATA_FILE', $BASE.'/data/users.json');

function db_load(){
  if(!file_exists(DATA_FILE)) return ['next_id'=>1,'users'=>[]];
  $raw=@file_get_contents(DATA_FILE); $db=$raw?json_decode($raw,true):null; if(!is_array($db)) $db=['next_id'=>1,'users'=>[]]; return $db;
}
function user_by_id($db,$id){ foreach($db['users'] as $u){ if((int)($u['id']??0)===(int)$id) return $u; } return null; }
function is_admin($u){ return $u && ($u['role']??'user')==='admin'; }
function ensure_userdir($uname){
  if(!is_dir(USERDATA_DIR)) @mkdir(USERDATA_DIR,0775,true);
  $u = USERDATA_DIR.'/'.$uname; if(!is_dir($u)) @mkdir($u,0775,true);
  if(!is_dir($u.'/files')) @mkdir($u.'/files',0775,true);
}
function desk_file($uname){ return USERDATA_DIR.'/'.$uname.'/desktop.json'; }
function load_desk($uname){
  $f = desk_file($uname); if(!file_exists($f)) return ['folders'=>[],'links'=>[]];
  $raw=@file_get_contents($f); $j=$raw?json_decode($raw,true):null; if(!is_array($j)) $j=['folders'=>[],'links'=>[]]; if(!isset($j['folders'])) $j['folders']=[]; if(!isset($j['links'])) $j['links']=[]; return $j;
}
function save_desk($uname,$j){
  ensure_userdir($uname);
  $f=desk_file($uname); $tmp=$f.'.tmp'; $fp=@fopen($tmp,'wb'); if(!$fp){ http_response_code(500); exit('Yazılamadı'); }
  @flock($fp,LOCK_EX); @fwrite($fp,json_encode($j,JSON_UNESCAPED_UNICODE|JSON_PRETTY_PRINT)); @fflush($fp); @flock($fp,LOCK_UN); @fclose($fp); @rename($tmp,$f);
}
function json_send($arr){ header('Content-Type: application/json; charset=UTF-8'); echo json_encode($arr, JSON_UNESCAPED_UNICODE); exit; }

$action = $_GET['action'] ?? $_POST['action'] ?? '';

$db = db_load();
$me = !empty($_SESSION['uid']) ? user_by_id($db, (int)$_SESSION['uid']) : null;

/* ===== FOLDERS ===== */
if ($action === 'list_folders') {
  if ($me && is_admin($me)) {
    $res=['folders'=>[]];
    foreach($db['users'] as $u){
      $uname = $u['username'] ?? null; if(!$uname) continue;
      $j = load_desk($uname);
      foreach($j['folders'] as $f){ $f['owner']=$uname; $res['folders'][]=$f; }
    }
    json_send($res);
  } else if ($me) {
    $uname = $me['username'] ?? null; if(!$uname) json_send(['folders'=>[]]);
    $j = load_desk($uname); json_send(['folders'=>$j['folders']]);
  } else {
    json_send(['folders'=>[]]);
  }
}
elseif ($action === 'create_folder') {
  if(!$me){ json_send(['ok'=>false,'error'=>'Giriş gerekli']); }
  $uname = $me['username'] ?? null; if(!$uname){ json_send(['ok'=>false,'error'=>'Kullanıcı adı yok']); }
  $name = trim((string)($_POST['name']??'')); $icon = (string)($_POST['icon']??'');
  if($name===''){ json_send(['ok'=>false,'error'=>'İsim gerekli']); }
  $j = load_desk($uname);
  $id = 'f_' . bin2hex(random_bytes(6));
  $j['folders'][] = ['id'=>$id,'name'=>$name,'icon'=>$icon];
  save_desk($uname,$j);
  json_send(['ok'=>true,'id'=>$id]);
}
elseif ($action === 'delete_folder') {
  if(!$me){ json_send(['ok'=>false,'error'=>'Giriş gerekli']); }
  $id = (string)($_POST['id']??'');
  if($id===''){ json_send(['ok'=>false,'error'=>'ID gerekli']); }
  $targetOwner = $me['username'] ?? null;
  if (is_admin($me) && !empty($_POST['owner'])) $targetOwner = preg_replace('/[^a-z0-9_-]+/i','', $_POST['owner']);
  if(!$targetOwner){ json_send(['ok'=>false,'error'=>'Sahip yok']); }
  $j = load_desk($targetOwner);
  $before = count($j['folders']);
  $j['folders'] = array_values(array_filter($j['folders'], function($f) use ($id){ return ($f['id']??'') !== $id; }));
  $after = count($j['folders']);
  if ($after===$before){ json_send(['ok'=>false,'error'=>'Bulunamadı']); }
  save_desk($targetOwner, $j);
  json_send(['ok'=>true]);
}

/* ===== LINKS ===== */
elseif ($action === 'list_links') {
  if ($me && is_admin($me)) {
    $res=['links'=>[]];
    foreach($db['users'] as $u){
      $uname = $u['username'] ?? null; if(!$uname) continue;
      $j = load_desk($uname);
      foreach($j['links'] as $l){ $l['owner']=$uname; $res['links'][]=$l; }
    }
    json_send($res);
  } else if ($me) {
    $uname = $me['username'] ?? null; if(!$uname) json_send(['links'=>[]]);
    $j = load_desk($uname); json_send(['links'=>$j['links']]);
  } else {
    json_send(['links'=>[]]);
  }
}
elseif ($action === 'create_link') {
  if(!$me){ json_send(['ok'=>false,'error'=>'Giriş gerekli']); }
  $uname = $me['username'] ?? null; if(!$uname){ json_send(['ok'=>false,'error'=>'Kullanıcı adı yok']); }
  $title = trim((string)($_POST['title']??'')); $url = trim((string)($_POST['url']??'')); $icon = (string)($_POST['icon']??'');
  if($title==='' || $url===''){ json_send(['ok'=>false,'error'=>'Başlık ve URL gerekli']); }
  if(!preg_match('#^https?://#i',$url)){ $url='https://'.$url; }
  $j = load_desk($uname);
  $id = 'l_' . bin2hex(random_bytes(6));
  $j['links'][] = ['id'=>$id,'title'=>$title,'url'=>$url,'icon'=>$icon,'parent'=>null];
  save_desk($uname,$j);
  json_send(['ok'=>true,'id'=>$id]);
}
elseif ($action === 'move_link') {
  if(!$me){ json_send(['ok'=>false,'error'=>'Giriş gerekli']); }
  $targetOwner = $me['username'] ?? null;
  if (is_admin($me) && !empty($_POST['owner'])) $targetOwner = preg_replace('/[^a-z0-9_-]+/i','', $_POST['owner']);
  $id=(string)($_POST['id']??''); $parent=(string)($_POST['parent']??'');
  if($id===''){ json_send(['ok'=>false,'error'=>'ID gerekli']); }
  $j = load_desk($targetOwner);
  $ok=false;
  foreach($j['links'] as &$l){ if(($l['id']??'')===$id){ $l['parent'] = $parent?:null; $ok=true; break; } }
  if(!$ok) json_send(['ok'=>false,'error'=>'Bulunamadı']);
  save_desk($targetOwner,$j);
  json_send(['ok'=>true]);
}
elseif ($action === 'delete_link') {
  if(!$me){ json_send(['ok'=>false,'error'=>'Giriş gerekli']); }
  $targetOwner = $me['username'] ?? null;
  if (is_admin($me) && !empty($_POST['owner'])) $targetOwner = preg_replace('/[^a-z0-9_-]+/i','', $_POST['owner']);
  $id=(string)($_POST['id']??'');
  if($id===''){ json_send(['ok'=>false,'error'=>'ID gerekli']); }
  $j = load_desk($targetOwner);
  $before = count($j['links']);
  $j['links'] = array_values(array_filter($j['links'], function($l) use ($id){ return ($l['id']??'') !== $id; }));
  $after = count($j['links']);
  save_desk($targetOwner,$j);
  json_send(['ok'=>($after<$before)]);
}

/* ===== FILE UPLOAD ===== */
elseif ($action === 'upload_files') {
  if(!$me){ json_send(['ok'=>false,'error'=>'Giriş gerekli']); }
  $uname = $me['username'] ?? null; if(!$uname){ json_send(['ok'=>false,'error'=>'Kullanıcı adı yok']); }
  $target = (string)($_POST['folder'] ?? '');
  if($target===''){ json_send(['ok'=>false,'error'=>'Hedef klasör gerekli']); }

  ensure_userdir($uname);
  $base = USERDATA_DIR.'/'.$uname.'/files';
  if(!is_dir($base)) @mkdir($base, 0775, true);

  $targetSafe = preg_replace('~[^a-z0-9_-]+~i', '_', $target);
  $destDir = $base.'/'.$targetSafe;
  if(!is_dir($destDir)) @mkdir($destDir, 0775, true);

  $saved = []; $errors = [];
  if(!isset($_FILES['files'])){ json_send(['ok'=>false,'error'=>'Dosya yok']); }
  $files = $_FILES['files'];
  $n = is_array($files['name']) ? count($files['name']) : 0;

  $blocked = ['php','phtml','phar','php5','cgi','pl','exe','sh','bat','cmd','com','dll'];
  for($i=0;$i<$n;$i++){
    $name = $files['name'][$i] ?? '';
    $tmp  = $files['tmp_name'][$i] ?? '';
    $err  = (int)($files['error'][$i] ?? 0);
    if($err!==UPLOAD_ERR_OK || !is_uploaded_file($tmp)){ $errors[]=['name'=>$name,'error'=>$err]; continue; }
    $ext = strtolower(pathinfo($name, PATHINFO_EXTENSION));
    if(in_array($ext,$blocked,true)){ $errors[]=['name'=>$name,'error'=>'Engelli uzantı']; continue; }

    $baseName = preg_replace('~[^\w.\-]+~u','_', $name);
    $outName = $baseName;
    $k=1;
    while(file_exists($destDir.'/'.$outName)){
      $outName = pathinfo($baseName, PATHINFO_FILENAME)."($k)".($ext?'.'.$ext:'');
      $k++;
    }
    if(@move_uploaded_file($tmp, $destDir.'/'.$outName)){
      $saved[] = ['name'=>$outName, 'path'=>'userdata/'.$uname.'/files/'.$targetSafe.'/'.$outName];
    } else {
      $errors[] = ['name'=>$name,'error'=>'Taşıma başarısız'];
    }
  }
  json_send(['ok'=>true, 'saved'=>$saved, 'errors'=>$errors]);
}
/* ===== UNKNOWN ===== */
else {
  json_send(['ok'=>false,'error'=>'Unknown action']);
}
