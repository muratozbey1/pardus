<?php
@ini_set('display_errors','0'); @error_reporting(E_ALL);
header('Content-Type: application/json; charset=utf-8');
$BASE = __DIR__;
$cfgFile = $BASE.'/config/upload_policy.json';
$def = [
  'image_only' => true,
  'max_size_mb' => 20,
  'allowed_ext' => ['jpg','jpeg','png','gif','webp','pdf'],
  'blocked_ext' => ['php','phtml','php3','php4','php5','phar','pht','shtml','cgi','pl','py','exe','js','sh','bat','cmd','htm','html','svg'],
  'allowed_mime_prefix' => ['image/','application/pdf'],
];
$cfg = $def;
if (is_file($cfgFile)){
  $j = json_decode(@file_get_contents($cfgFile), true);
  if (is_array($j)) $cfg = array_merge($def, $j);
}
echo json_encode(['ok'=>true,'policy'=>$cfg], JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES);