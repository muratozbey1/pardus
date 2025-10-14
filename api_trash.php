<?php
@ini_set('display_errors','0'); @error_reporting(0);
header('Content-Type: application/json; charset=utf-8');
if (session_status() === PHP_SESSION_NONE) { session_start(); }

$ROOT = __DIR__;
$DATA = $ROOT . '/data';
$USERDATA = $ROOT . '/userdata';

function resp($arr){ echo json_encode($arr, JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES); exit; }

function current_email(){
  if (isset($_SESSION['user']) && is_array($_SESSION['user']) && !empty($_SESSION['user']['email'])) return (string)$_SESSION['user']['email'];
  if (!empty($_SESSION['email'])) return (string)$_SESSION['email'];
  return null;
}
function slug_user($email){
  $u = strtolower($email);
  $u = preg_replace('~[^a-z0-9._-]+~','_', $u);
  return $u;
}
function desktop_path_user($email){
  global $USERDATA;
  $slug = slug_user($email);
  $dir = $USERDATA . '/' . $slug;
  if (!is_dir($dir)) @mkdir($dir, 0775, true);
  return $dir . '/desktop.json';
}
function desktop_path_global(){
  global $DATA;
  if (!is_dir($DATA)) @mkdir($DATA, 0775, true);
  return $DATA . '/desktop.json';
}
function load_file($path){
  if (!file_exists($path)){
    $o = ['folders'=>[],'links'=>[],'trash'=>[]];
    @file_put_contents($path, json_encode($o, JSON_UNESCAPED_UNICODE|JSON_PRETTY_PRINT));
    return $o;
  }
  $j = json_decode(@file_get_contents($path), true);
  if (!is_array($j)) $j = [];
  if (!isset($j['folders']) || !is_array($j['folders'])) $j['folders']=[];
  if (!isset($j['links'])   || !is_array($j['links']))   $j['links']=[];
  if (!isset($j['trash'])   || !is_array($j['trash']))   $j['trash']=[];
  return $j;
}
function save_file($path, $obj){
  return !!@file_put_contents($path, json_encode($obj, JSON_UNESCAPED_UNICODE|JSON_PRETTY_PRINT));
}
function norm_id($id){
  $id = trim((string)$id);
  $id = preg_replace('~^(fld|lnk)-~', '', $id);
  return $id;
}

function deep_find($id){
  global $USERDATA;
  $places = [];

  $gp = desktop_path_global();
  $gd = load_file($gp);
  foreach ($gd['folders'] as $k=>$f){ if (!empty($f['id']) && $f['id']===$id){ $places[] = ['kind'=>'global','path'=>$gp,'type'=>'folder','index'=>$k]; break; } }
  foreach ($gd['links'] as $k=>$f){ if (!empty($f['id']) && $f['id']===$id){ $places[] = ['kind'=>'global','path'=>$gp,'type'=>'link','index'=>$k]; break; } }

  if (is_dir($USERDATA)){
    $dh = opendir($USERDATA);
    if ($dh){
      while(false !== ($entry = readdir($dh))){
        if ($entry==='.' || $entry==='..') continue;
        $p = $USERDATA . '/' . $entry . '/desktop.json';
        if (!is_file($p)) continue;
        $d = load_file($p);
        foreach ($d['folders'] as $k=>$f){ if (!empty($f['id']) && $f['id']===$id){ $places[] = ['kind'=>'user','path'=>$p,'type'=>'folder','index'=>$k,'owner_slug'=>$entry]; break; } }
        foreach ($d['links'] as $k=>$f){ if (!empty($f['id']) && $f['id']===$id){ $places[] = ['kind'=>'user','path'=>$p,'type'=>'link','index'=>$k,'owner_slug'=>$entry]; break; } }
      }
      closedir($dh);
    }
  }
  return $places;
}

function append_to_trash($targetPath, $trashRec){
  $td = load_file($targetPath);
  if (!isset($td['trash']) || !is_array($td['trash'])) $td['trash'] = [];
  $td['trash'][] = $trashRec;
  save_file($targetPath, $td);
}

$action = isset($_REQUEST['action']) ? (string)$_REQUEST['action'] : '';

$me = current_email();
$owner = isset($_REQUEST['owner']) ? (string)$_REQUEST['owner'] : '';

// ==== list (user if owner/me, else global)
if ($action === 'list'){
  $targetPath = null; $kind = 'global'; $who = '';
  if ($owner){ $targetPath = desktop_path_user($owner); $kind='user'; $who = $owner; }
  elseif($me){ $targetPath = desktop_path_user($me); $kind='user'; $who = $me; }
  else { $targetPath = desktop_path_global(); $kind='global'; $who = ''; }
  $d = load_file($targetPath);
  $items = [];
  foreach ($d['trash'] as $t){
    if (empty($t['deleted_at'])) $t['deleted_at'] = gmdate('c');
    $items[] = $t + ['owner' => ($kind==='user' ? $who : '') ];
  }
  resp(['ok'=>true,'items'=>$items,'meta'=>['path'=>$targetPath,'kind'=>$kind,'owner'=>$who,'count'=>count($items)]]);
}

// ==== list_all (debug)
if ($action === 'list_all'){
  $userPath = $me ? desktop_path_user($me) : null;
  $globalPath = desktop_path_global();
  $userItems = $userPath ? load_file($userPath)['trash'] : [];
  $globalItems = load_file($globalPath)['trash'];
  resp(['ok'=>true,'user'=>['path'=>$userPath,'count'=>count($userItems),'items'=>$userItems],
                'global'=>['path'=>$globalPath,'count'=>count($globalItems),'items'=>$globalItems]]);
}

// ==== soft_delete: remove from source but write trash into user's file if possible
if ($action === 'soft_delete'){
  $type = isset($_POST['type']) ? (string)$_POST['type'] : '';
  $id   = isset($_POST['id'])   ? norm_id($_POST['id']) : '';
  if (!$type || !$id) resp(['ok'=>false,'error'=>'Parametre eksik']);

  $candidates = [];
  if ($owner){ $candidates[] = ['kind'=>'user','path'=>desktop_path_user($owner)]; }
  if ($me && (!$owner || $owner!==$me)){ $candidates[] = ['kind'=>'user','path'=>desktop_path_user($me)]; }
  $candidates[] = ['kind'=>'global','path'=>desktop_path_global()];

  $moved=null; $fromKind=null; $fromPath=null; $fromData=null; $idx=-1; $foundType=null;
  foreach ($candidates as $sc){
    $d = load_file($sc['path']);
    $listKey = ($type==='folder') ? 'folders' : 'links';
    foreach ($d[$listKey] as $k=>$f){
      if (!empty($f['id']) && $f['id']===$id){
        $moved=$f; $fromKind=$sc['kind']; $fromPath=$sc['path']; $fromData=$d; $idx=$k; $foundType=$type; break 2;
      }
    }
  }

  if (!$moved){
    $found = deep_find($id);
    if (count($found)===1){
      $sc = $found[0];
      $d  = load_file($sc['path']);
      $listKey = ($sc['type']==='folder') ? 'folders' : 'links';
      $moved = $d[$listKey][$sc['index']];
      $fromKind = $sc['kind']; $fromPath = $sc['path']; $fromData = $d; $idx = $sc['index']; $foundType = $sc['type'];
    } elseif (count($found)>1){
      resp(['ok'=>false,'error'=>'Birden fazla konumda bulundu','matches'=>$found]);
    } else {
      resp(['ok'=>false,'error'=>'Klasör bulunamadı','id'=>$id]);
    }
  }

  // Kaynağından kaldır
  if ($foundType==='folder'){
    array_splice($fromData['folders'], $idx, 1);
  } else {
    array_splice($fromData['links'], $idx, 1);
  }
  save_file($fromPath, $fromData);

  // Hedef çöp dosyası: owner > me > source (global/user)
  $trashTargetPath = null;
  if (!empty($owner)) $trashTargetPath = desktop_path_user($owner);
  elseif (!empty($me)) $trashTargetPath = desktop_path_user($me);
  else $trashTargetPath = $fromPath;

  // Çöp kaydı
  if ($foundType==='folder'){
    $t = [
      'tid'=>'t_'.bin2hex(random_bytes(6)),
      'type'=>'folder',
      'id'=>$moved['id'],
      'name'=> isset($moved['name'])?$moved['name']:null,
      'icon'=> isset($moved['icon'])?$moved['icon']:null,
      'parent'=> isset($moved['parent'])?$moved['parent']:null,
      'deleted_at'=> gmdate('c')
    ];
  } else {
    $t = [
      'tid'=>'t_'.bin2hex(random_bytes(6)),
      'type'=>'link',
      'id'=>$moved['id'],
      'title'=> isset($moved['title'])?$moved['title']:null,
      'icon'=> isset($moved['icon'])?$moved['icon']:null,
      'url'=> isset($moved['url'])?$moved['url']:null,
      'parent'=> isset($moved['parent'])?$moved['parent']:null,
      'deleted_at'=> gmdate('c')
    ];
  }

  append_to_trash($trashTargetPath, $t);
  resp(['ok'=>true,'tid'=>$t['tid'],'from_kind'=>$fromKind,'from_path'=>$fromPath,'trash_path'=>$trashTargetPath]);
}

// ==== restore
if ($action === 'restore'){
  $tid = isset($_POST['tid']) ? (string)$_POST['tid'] : '';
  if (!$tid) resp(['ok'=>false,'error'=>'Parametre eksik']);

  $targets = [];
  if ($owner){ $targets[] = desktop_path_user($owner); }
  if ($me && (!$owner || $owner!==$me)){ $targets[] = desktop_path_user($me); }
  $targets[] = desktop_path_global();

  $data=null; $path=null; $idx=-1; $rec=null;
  foreach ($targets as $p){
    $d = load_file($p);
    foreach ($d['trash'] as $k=>$t){ if (!empty($t['tid']) && $t['tid']===$tid){ $data=$d; $path=$p; $idx=$k; $rec=$t; break 2; } }
  }
  if (!$rec) resp(['ok'=>false,'error'=>'Kayıt bulunamadı']);

  if ($rec['type']==='folder'){
    $data['folders'][] = ['id'=>$rec['id'],'name'=>($rec['name']??'Klasör'),'icon'=>($rec['icon']??'fa:fa-regular fa-folder|#8ab4f8'),'parent'=>($rec['parent']??null)];
  } else {
    $data['links'][] = ['id'=>$rec['id'],'title'=>($rec['title']??'Kısayol'),'icon'=>($rec['icon']??'fa:fa-solid fa-up-right-from-square|#8ab4f8'),'url'=>($rec['url']??'#'),'parent'=>($rec['parent']??null)];
  }
  array_splice($data['trash'], $idx, 1);
  save_file($path, $data);
  resp(['ok'=>true]);
}

// ==== delete_permanent
if ($action === 'delete_permanent'){
  $tid = isset($_POST['tid']) ? (string)$_POST['tid'] : '';
  if (!$tid) resp(['ok'=>false,'error'=>'Parametre eksik']);

  $paths = [ desktop_path_global() ];
  if ($me) $paths[] = desktop_path_user($me);
  if ($owner && (!$me || $owner!==$me)) $paths[] = desktop_path_user($owner);

  foreach ($paths as $p){
    $d = load_file($p);
    foreach ($d['trash'] as $k=>$t){ if (!empty($t['tid']) && $t['tid']===$tid){ array_splice($d['trash'],$k,1); save_file($p,$d); resp(['ok'=>true]); } }
  }
  // deep delete
  global $USERDATA;
  if (is_dir($USERDATA)){
    $dh = opendir($USERDATA);
    if ($dh){
      while(false !== ($entry = readdir($dh))){
        if ($entry==='.' || $entry==='..') continue;
        $p = $USERDATA . '/' . $entry . '/desktop.json';
        if (!is_file($p)) continue;
        $d = load_file($p);
        foreach ($d['trash'] as $k=>$t){ if (!empty($t['tid']) && $t['tid']===$tid){ array_splice($d['trash'],$k,1); save_file($p,$d); resp(['ok'=>true]); } }
      }
      closedir($dh);
    }
  }
  resp(['ok'=>false,'error'=>'Kayıt bulunamadı']);
}

// ==== empty
if ($action === 'empty'){
  $targetPath = null;
  if ($owner){ $targetPath = desktop_path_user($owner); }
  elseif($me){ $targetPath = desktop_path_user($me); }
  else { $targetPath = desktop_path_global(); }
  $d = load_file($targetPath); $d['trash'] = []; save_file($targetPath, $d);
  resp(['ok'=>true]);
}

// ==== probe
if ($action === 'probe'){
  $id = isset($_GET['id']) ? norm_id($_GET['id']) : '';
  if (!$id) resp(['ok'=>false,'error'=>'id yok']);
  $found = deep_find($id);
  resp(['ok'=>true,'found'=>$found]);
}

resp(['ok'=>false,'error'=>'Geçersiz istek']);
