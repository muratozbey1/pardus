<?php
@ini_set('display_errors','0'); @error_reporting(E_ALL);
session_start();
$BASE = __DIR__;
$cfgFile = $BASE.'/config/upload_policy.json';
$passFile = $BASE.'/config/admin_password.php';
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
$is_admin = !empty($_SESSION['is_admin']) || (!empty($_SESSION['role']) && $_SESSION['role']==='admin') || (!empty($_SESSION['username']) && $_SESSION['username']==='admin');

function pass_ok($pw){
  $file = __DIR__.'/config/admin_password.php';
  if (!is_file($file)) return false;
  $arr = include $file;
  $algo = $arr['algo'] ?? 'sha256';
  $hash = $arr['hash'] ?? '';
  if ($algo === 'sha256'){
    $calc = hash('sha256', (string)$pw);
    return hash_equals($hash, $calc);
  }
  return false;
}

if (!$is_admin){
  if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST' && isset($_POST['login_pw'])){
    if (pass_ok($_POST['login_pw'])){
      $_SESSION['is_admin'] = 1;
      header('Location: admin_upload_settings.php'); exit;
    } else {
      $err = "Hatalı parola.";
    }
  }
  ?><!doctype html><meta charset="utf-8">
  <title>Upload Ayarları — Giriş</title>
  <style>body{font-family:system-ui,-apple-system,Segoe UI,Roboto;background:#0b1118;color:#e6e9ee;display:grid;place-items:center;height:100vh} .card{border:1px solid #263243;border-radius:12px;padding:22px;min-width:360px;background:#0e1622} input{width:100%;padding:10px;border:1px solid #263243;background:#0b141f;color:#e6e9ee;border-radius:8px} .btn{margin-top:10px;width:100%;padding:10px;border-radius:8px;border:1px solid #0e766e;background:linear-gradient(180deg,#22d3ee,#14b8a6);color:#001a17;font-weight:700;cursor:pointer}</style>
  <div class="card">
    <h3 style="margin:0 0 12px">Yönetici Girişi</h3>
    <?php if(!empty($err)) echo '<div style="color:#ff9b9b;margin:8px 0">'.$err.'</div>'; ?>
    <form method="post">
      <input type="password" name="login_pw" placeholder="Admin parolası">
      <button class="btn" type="submit">Giriş Yap</button>
    </form>
  </div>
  <?php
  exit;
}

// Save policy
if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST' && isset($_POST['save_policy'])){
  $cfg['image_only'] = !empty($_POST['image_only']);
  $cfg['max_size_mb'] = max(1, (int)($_POST['max_size_mb'] ?? 20));
  $cfg['allowed_ext'] = array_values(array_filter(array_map('trim', explode(',', $_POST['allowed_ext'] ?? ''))));
  $cfg['blocked_ext'] = array_values(array_filter(array_map('trim', explode(',', $_POST['blocked_ext'] ?? ''))));
  $cfg['allowed_mime_prefix'] = array_values(array_filter(array_map('trim', explode(',', $_POST['allowed_mime_prefix'] ?? ''))));
  @file_put_contents($cfgFile, json_encode($cfg, JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES));
  $saved = "Ayarlar kaydedildi.";
}

// Change admin password
if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST' && isset($_POST['change_pw'])){
  $p1 = (string)($_POST['p1'] ?? '');
  $p2 = (string)($_POST['p2'] ?? '');
  if ($p1 !== '' && $p1 === $p2){
    $hash = hash('sha256', $p1);
    $php = "<?php\nreturn ['algo'=>'sha256','hash'=>'".$hash."'];\n";
    @file_put_contents($passFile, $php);
    $saved_pw = "Parola güncellendi.";
  } else {
    $err_pw = "Parolalar uyuşmuyor.";
  }
}
?><!doctype html><meta charset="utf-8">
<title>Upload Ayarları</title>
<style>
body{font-family:system-ui,-apple-system,Segoe UI,Roboto; background:#0b1118; color:#e6e9ee; padding:24px}
input[type=text],input[type=number]{width:520px; padding:8px; border:1px solid #263243; background:#0e1622; color:#e6e9ee; border-radius:8px}
label{display:flex; align-items:center; gap:8px; margin:10px 0}
.card{border:1px solid #263243; border-radius:12px; padding:16px; max-width:820px; margin-bottom:22px}
.btn{padding:10px 16px; border-radius:10px; border:1px solid #0e766e; background:linear-gradient(180deg,#22d3ee,#14b8a6); color:#001a17; font-weight:700; cursor:pointer}
.note{opacity:.75; font-size:12px}
</style>
<h2>Upload Ayarları</h2>
<?php if(!empty($saved)) echo '<div style="background:#0a1;color:#fff;padding:10px;border-radius:8px">'.$saved.'</div>'; ?>
<div class="card">
  <form method="post">
    <input type="hidden" name="save_policy" value="1">
    <label><input type="checkbox" name="image_only" <?php echo !empty($cfg['image_only'])?'checked':''; ?>> Sadece Görseller (image/*)</label>
    <label>Maks. Boyut (MB): <input type="number" name="max_size_mb" min="1" value="<?php echo (int)$cfg['max_size_mb']; ?>"></label>
    <label>İzinli Uzantılar (virgülle): <input type="text" name="allowed_ext" value="<?php echo htmlspecialchars(implode(',', $cfg['allowed_ext'])); ?>"></label>
    <label>Engelli Uzantılar (virgülle): <input type="text" name="blocked_ext" value="<?php echo htmlspecialchars(implode(',', $cfg['blocked_ext'])); ?>"></label>
    <label>İzinli MIME Prefix (virgülle): <input type="text" name="allowed_mime_prefix" value="<?php echo htmlspecialchars(implode(',', $cfg['allowed_mime_prefix'])); ?>"></label>
    <div class="note">Kaydetmek için yazma izni gerekir: <code>config/</code> klasörü yazılabilir olmalı.</div>
    <div style="margin-top:16px"><button class="btn" type="submit">Ayarları Kaydet</button></div>
  </form>
</div>

<h3>Yönetici Parolası</h3>
<?php if(!empty($saved_pw)) echo '<div style="background:#0a1;color:#fff;padding:10px;border-radius:8px">'.$saved_pw.'</div>'; ?>
<?php if(!empty($err_pw)) echo '<div style="background:#a10;color:#fff;padding:10px;border-radius:8px">'.$err_pw.'</div>'; ?>
<form method="post" class="card">
  <input type="hidden" name="change_pw" value="1">
  <label>Yeni Parola: <input type="text" name="p1" placeholder="Yeni parola"></label>
  <label>Tekrar: <input type="text" name="p2" placeholder="Yeni parola (tekrar)"></label>
  <div class="note">Parola SHA‑256 ile saklanır.</div>
  <div style="margin-top:16px"><button class="btn" type="submit">Parolayı Güncelle</button></div>
</form>
