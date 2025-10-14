<?php 
function find_stem_file($stem){
  $cands = ["$stem.png","$stem.svg","$stem.webp","$stem.jpg","$stem.jpeg"];
  foreach($cands as $f){ if(is_file(__DIR__."/$f")) return $f; }
  return null;
}
$pardusIcon = find_stem_file('pardus');
?><!doctype html>
<html lang="tr">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Pardus XFCE — Web Masaüstü</title>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" crossorigin="anonymous" referrerpolicy="no-referrer"/>
<style>
:root{
  --panel-h: 36px;
  --bg: #0f1115; --bg2:#1b1f28; --bg3:#161b25;
  --fg:#e6e9ee; --mut:#9aa3b2; --line:#2a2f3b; --hover:#232a36;
  --shadow:0 14px 34px rgba(0,0,0,.45);
}
*{box-sizing:border-box}
html,body{height:100%;margin:0}
body{
  font-family: Inter,system-ui,-apple-system,"Segoe UI",Roboto,Arial,sans-serif;
  color:var(--fg);
  background:url("100.jpg") center/cover fixed no-repeat, var(--bg);
  overflow:hidden;
}
.panel{position:fixed;left:0;right:0;bottom:0;height:var(--panel-h);
  display:flex;align-items:center;gap:8px;padding:0 8px;
  background:linear-gradient(#1e2430,#171c25);border-top:1px solid var(--line);
  box-shadow:0 -4px 12px rgba(0,0,0,.25);z-index:1000}
.start-btn{display:flex;align-items:center;gap:8px;height:28px;padding:0 10px;
  background:linear-gradient(#26303e,#1c2330);border:1px solid var(--line);border-radius:8px;
  cursor:pointer;user-select:none}
.start-btn img{width:16px;height:16px;object-fit:contain}
.tasks{flex:1;display:flex;gap:6px;overflow:auto}
.task{min-width:140px;height:26px;display:flex;align-items:center;padding:0 10px;
  background:linear-gradient(#151a24,#121620);border:1px solid var(--line);border-radius:6px;
  white-space:nowrap;overflow:hidden;text-overflow:ellipsis;cursor:pointer;user-select:none}
.tray{display:flex;align-items:center;gap:10px;color:var(--mut);font-size:12px}
.clock{min-width:120px;text-align:center}.clock .t{font-weight:700}

.desktop{position:absolute;inset:0 0 var(--panel-h) 0;padding:10px}
#grid{position:relative;width:100%;height:100%}
.item{position:absolute;width:96px;text-align:center;user-select:none;cursor:default}
.item .ficon{width:48px;height:48px;margin:0 auto;display:grid;place-items:center}
.item .ficon i{font-size:30px;color:#8ab4f8}
.item .flabel{margin-top:4px;font-size:12px;color:#e6e9ee;text-shadow:0 1px 2px rgba(0,0,0,.35)}

.menu-wrap{position:fixed;left:8px;bottom:calc(var(--panel-h) + 6px);z-index:12000;display:none}
.menu-wrap.open{display:block}
.startmenu{width:340px;background:var(--bg2);border:1px solid var(--line);border-radius:8px;
  box-shadow:var(--shadow);color:var(--fg);font-size:13px;overflow:hidden}
.startmenu.start-rail{display:flex}
.startmenu .rail{width:40px;background:var(--bg3);border-right:1px solid var(--line);
  display:flex;flex-direction:column;align-items:center;justify-content:space-between;padding:8px 4px}
.startmenu .rail img{width:18px;height:18px;object-fit:contain}
.startmenu .rail .rname{
  writing-mode:vertical-rl; transform:rotate(180deg);
  font-size:12.5px;color:#dde6f4;letter-spacing:.5px;font-weight:600;white-space:nowrap
}
.startmenu .body{flex:1;max-height:540px;overflow:auto;background:linear-gradient(#1a202b,#171c25)}
.startmenu .sec{padding:6px 10px;color:#9aa6b2;font-weight:700;font-size:12px;letter-spacing:.2px}
.startmenu .list{list-style:none;margin:0;padding:4px 0}
.startmenu .it{display:flex;align-items:center;gap:8px;padding:7px 12px;cursor:pointer;white-space:nowrap}
.startmenu .it:hover{background:var(--hover)}
.startmenu .it i{width:16px;text-align:center}

.ctx{position:fixed;display:none;z-index:13000}
.ctxmenu{min-width:220px;background:var(--bg2);border:1px solid var(--line);border-radius:8px;
  box-shadow:var(--shadow);padding:4px 0;font-size:13px}
.ctxmenu .citem{display:flex;align-items:center;gap:10px;padding:6px 12px;cursor:pointer}
.ctxmenu .citem:hover{background:var(--hover)}
.ctxmenu .csep{height:1px;background:#232732;margin:4px 6px}

.window{position:fixed;background:var(--bg2);border:1px solid var(--line);border-radius:10px;
  box-shadow:var(--shadow);color:#e6e9ee;overflow:hidden}
.titlebar{height:30px;display:flex;align-items:center;gap:8px;padding:0 8px;cursor:move;
  background:linear-gradient(#202532,#181d26);border-bottom:1px solid rgba(255,255,255,.08)}
.titlebar .tt{font-weight:600}
.titlebar .btn{background:transparent;border:1px solid rgba(255,255,255,.2);border-radius:6px;color:#fff;
  padding:0 8px;height:22px;line-height:20px;cursor:pointer}
.window .content{width:100%;height:calc(100% - 30px);background:#0f1115;overflow:auto}

.modal{position:fixed;inset:0;background:rgba(0,0,0,.35);display:none;align-items:center;justify-content:center;z-index:14000}
.modal .box{width:360px;background:var(--bg2);border:1px solid var(--line);border-radius:10px;box-shadow:var(--shadow);padding:12px}
.modal .row{display:flex;gap:8px;margin:8px 0}
.modal input{flex:1;padding:8px;background:#0f1115;color:#e6e9ee;border:1px solid var(--line);border-radius:6px}
.modal .actions{display:flex;gap:8px;justify-content:flex-end}
.modal .btn{padding:8px 12px;background:#22293a;border:1px solid var(--line);border-radius:8px;color:#fff;cursor:pointer}
.ico-grid{display:grid;grid-template-columns:repeat(6,1fr);gap:8px;width:100%}
.ico-grid .ico{display:grid;place-items:center;height:34px;border:1px solid var(--line);border-radius:8px;background:#0f1115;cursor:pointer}
.ico-grid .ico i{font-size:18px}
.ico-grid .ico.sel{outline:2px solid #3b82f6}
</style>
<link rel="stylesheet" href="assets/hide_legacy_console.css">
</head>
<body>

<div class="desktop"><div id="grid"></div></div>

<div class="panel">
  <div class="start-btn" id="startBtn"><?php if($pardusIcon): ?><img src="<?=htmlspecialchars($pardusIcon,ENT_QUOTES,'UTF-8')?>" alt="P"><?php endif; ?><span>Pardus</span></div>
  <div class="tasks" id="tasks"></div>
  <div class="tray">
    <span class="net" title="Ağ"><i class="fa-solid fa-wifi"></i></span>
    <span class="bat" title="Pil"><i class="fa-solid fa-battery-three-quarters"></i></span>
    <span class="clock"><span class="t" id="clkT">--:--:--</span> <span id="clkD">-- -- ----</span></span>
  </div>
</div>

<div class="menu-wrap" id="menu">
  <div class="startmenu start-rail">
    <div class="rail">
      <?php if($pardusIcon): ?><img src="<?=htmlspecialchars($pardusIcon,ENT_QUOTES,'UTF-8')?>" alt="P"><?php endif; ?>
      <span class="rname">Özgürlük için Pardus...</span>
    </div>
    <div class="body">
      <div class="sec">Genel</div>
      <ul class="list" id="menuStatic">
        <li class="it" id="mAbout"><i class="fa-regular fa-circle-question" style="color:#8b5cf6"></i><span>Hakkında</span></li>
        <li class="it" id="mExit"><i class="fa-solid fa-power-off" style="color:#ef4444"></i><span>Çıkış</span></li>
      </ul>
      <div class="sec">Sayfalar</div>
      <ul class="list" id="menuPages"></ul>
    </div>
  </div>
</div>

<div class="ctx" id="ctx">
  <div class="ctxmenu" id="ctxMenu">
    <div class="citem" id="ctxNew"><i class="fa-solid fa-folder-plus" style="color:#22c55e"></i><span>Yeni Klasör</span></div>
    <div class="citem" id="ctxSettings"><i class="fa-solid fa-gear" style="color:#f59e0b"></i><span>Ayarlar</span></div>
    <div class="citem" id="ctxAbout"><i class="fa-regular fa-circle-question" style="color:#8b5cf6"></i><span>Hakkında</span></div>
    <div class="citem" id="ctxReload"><i class="fa-solid fa-rotate-right" style="color:#3b82f6"></i><span>Yenile</span></div>
  </div>
</div>

<div class="modal" id="modal">
  <div class="box">
    <div style="font-weight:700;margin-bottom:6px">Yeni Klasör</div>
    <div class="row"><input id="fldName" placeholder="Klasör adı"></div>
    <div class="row">
      <input type="hidden" id="fldIcon" value="fa:fa-regular fa-folder|#8ab4f8">
      <div class="ico-grid" id="icoGrid"></div>
    </div>
    <div class="actions">
      <button class="btn" id="btnCancel">İptal</button>
      <button class="btn" id="btnCreate">Oluştur</button>
    </div>
  </div>
</div>

<script>
"use strict";
const $=(s,c=document)=>c.querySelector(s), $$=(s,c=document)=>Array.from(c.querySelectorAll(s));
function eh(s){return String(s).replace(/[&<>"']/g,m=>({"&":"&amp;","<":"&lt;",">":"&gt;","\"":"&quot;","'":"&#39;"}[m]));}

function tick(){
  const d=new Date(), M=["Oca","Şub","Mar","Nis","May","Haz","Tem","Ağu","Eyl","Eki","Kas","Ara"];
  const hh=String(d.getHours()).padStart(2,"0"), mm=String(d.getMinutes()).padStart(2,"0"), ss=String(d.getSeconds()).padStart(2,"0");
  $("#clkT").textContent=hh+":"+mm+":"+ss;
  $("#clkD").textContent=String(d.getDate()).padStart(2,"0")+" "+M[d.getMonth()]+" "+d.getFullYear();
}
setInterval(tick,1000); tick();

const menu=$("#menu"), startBtn=$("#startBtn");
startBtn.addEventListener("click",(e)=>{ e.stopPropagation(); menu.classList.toggle("open"); });
menu.addEventListener("click",(e)=>e.stopPropagation());
document.addEventListener("click",(e)=>{ if(!menu.contains(e.target) && !startBtn.contains(e.target)) menu.classList.remove("open"); });
$("#mAbout").addEventListener("click",()=>{ openWindow("Hakkında","<div style='padding:8px'>Pardus XFCE web masaüstü maketi.</div>",420,260); menu.classList.remove("open"); });
$("#mExit").addEventListener("click",()=>{ menu.classList.remove("open"); fetch('auth/logout.php?inapp=1',{cache:'no-store',credentials:'same-origin'}).finally(()=>location.reload()); });

const ctx=$("#ctx"), ctxMenu=$("#ctxMenu");
function showCtx(x,y){
  ctx.style.display="block";
  const w=ctxMenu.offsetWidth||220, h=ctxMenu.offsetHeight||160,
        vw=Math.max(document.documentElement.clientWidth,window.innerWidth||0),
        vh=Math.max(document.documentElement.clientHeight,window.innerHeight||0);
  ctx.style.left=Math.min(x,vw-w-4)+"px"; ctx.style.top=Math.min(y,vh-h-4)+"px";
}
const openCtx=(e)=>{ e.preventDefault(); showCtx(e.pageX,e.pageY); };
document.addEventListener("contextmenu",openCtx,{capture:true});
document.getElementById("grid").addEventListener("contextmenu",openCtx);
document.addEventListener("click",(e)=>{ if(!ctxMenu.contains(e.target)) ctx.style.display="none"; });
$("#ctxReload").addEventListener("click",()=>location.reload());
$("#ctxAbout").addEventListener("click",()=>openWindow("Hakkında","<div style='padding:8px'>Pardus XFCE web masaüstü maketi.</div>",420,260));
$("#ctxSettings").addEventListener("click",()=>openWindow("Ayarlar","<div style='padding:8px'>Ayarlar yakında.</div>",420,260));
$("#ctxNew").addEventListener("click",()=>{ openNewFolderDialog(); });

const WM={ z:3000, map:new Map(),
  add(w){this.z+=2;w.style.zIndex=this.z;}, bring(w){this.z+=2;w.style.zIndex=this.z;},
  get(id){return id?this.map.get(id):null;}, reg(id,meta){if(id)this.map.set(id,meta);},
  del(id){if(id)this.map.delete(id);}
};
function openWindowEx(o){
  const id=o.id||null, ex=id?WM.get(id):null;
  if(ex){ if(ex.win.style.display==="none") ex.win.style.display="block"; WM.bring(ex.win); return ex.win; }
  const ph=parseInt(getComputedStyle(document.querySelector(".panel")).height)||36;
  const W=Math.min(Math.max(o.w||640,360), window.innerWidth-20),
        H=Math.min(Math.max(o.h||420,260), window.innerHeight-ph-20),
        L=Math.max(10,Math.round((window.innerWidth-W)/2)),
        T=Math.max(10,Math.round((window.innerHeight-ph-H)/2));
  const win=document.createElement("div");
  win.className="window"; win.style.left=L+"px"; win.style.top=T+"px"; win.style.width=W+"px"; win.style.height=H+"px";
  win.innerHTML="<div class='titlebar'>"
   +"<div class='tt'>"+eh(o.title||"Pencere")+"</div>"
   +"<div style='display:flex;gap:6px;margin-left:auto'>"
   +"<button class='btn' data-min title='Küçült'>_</button>"
   +"<button class='btn' data-max title='Büyüt'>▢</button>"
   +"<button class='btn' data-x title='Kapat'>×</button>"
   +"</div></div><div class='content'>"+(o.html||"")+"</div>";
  document.body.appendChild(win); WM.add(win);

  const tb=win.querySelector(".titlebar");
  tb.addEventListener("mousedown",(e)=>{
    WM.bring(win);
    const r=win.getBoundingClientRect(), sx=e.clientX, sy=e.clientY, sl=r.left, st=r.top;
    function mm(ev){ win.style.left=(sl+(ev.clientX-sx))+"px"; win.style.top=(st+(ev.clientY-sy))+"px"; }
    function mu(){ document.removeEventListener("mousemove",mm); document.removeEventListener("mouseup",mu); }
    document.addEventListener("mousemove",mm); document.addEventListener("mouseup",mu,{once:true}); e.preventDefault();
  });

  const bMin=win.querySelector("[data-min]"), bMax=win.querySelector("[data-max]"), bX=win.querySelector("[data-x]");
  let max=false, prev=null;
  bMin.addEventListener("click",()=>{ win.style.display="none"; });
  bX.addEventListener("click",()=>{ win.remove(); if(id){ const m=WM.get(id); if(m){ if(m.task) m.task.remove(); WM.del(id);} }});
  bMax.addEventListener("click",()=>{
    const ph2=parseInt(getComputedStyle(document.querySelector(".panel")).height)||36;
    if(!max){ const r=win.getBoundingClientRect(); prev={l:r.left,t:r.top,w:r.width,h:r.height};
      win.style.left="0px"; win.style.top="0px"; win.style.width=window.innerWidth+"px"; win.style.height=(window.innerHeight-ph2)+"px"; bMax.textContent="▭"; max=true;
    }else{ if(prev){ win.style.left=prev.l+"px"; win.style.top=prev.t+"px"; win.style.width=prev.w+"px"; win.style.height=prev.h+"px"; } bMax.textContent="▢"; max=false; }
  });

  let task=null;
  if(o.task!==false){
    task=document.createElement("div"); task.className="task"; task.textContent=o.title||"Pencere";
    task.addEventListener("click",()=>{ if(win.style.display==="none"){ win.style.display="block"; WM.bring(win); } else { win.style.display="none"; } });
    $("#tasks").appendChild(task);
  }
  if(id) WM.reg(id,{win,task});
  return win;
}
function openWindow(title,html,w,h){ return openWindowEx({title,html,w,h}); }

/* === Klasör penceresi + uploader entegrasyonu (Toolbar'a buton) === */
function idSafe(s){ return 'fwin-'+String(s).replace(/[^a-z0-9_-]+/gi,'_'); }

function openFolder(folderIdOrPath, title){
  const id = idSafe(folderIdOrPath);
  const btnId = 'uplBtn_'+id;
  const inpId = 'uplInp_'+id;
  const gridId = 'uplGrid_'+id;

  const html =
    "<div style='display:flex;flex-direction:column;height:100%'>"
  +   "<div style='display:flex;align-items:center;gap:8px;padding:8px;border-bottom:1px solid var(--line)'>"
  +     "<span style='color:#9aa3b2;font-weight:600'>"+eh(title||folderIdOrPath)+"</span>"
  +     "<button id='"+btnId+"' class='btn' style='margin-left:auto;height:26px;padding:0 10px;'>Dosya Seç</button>"
  +     "<input id='"+inpId+"' type='file' multiple style='display:none'>"
  +   "</div>"
  +   "<div id='"+gridId+"' style='flex:1;padding:10px;position:relative;outline:none'>"
  +     "<div style='opacity:.7'>İçerik listesi burada yer alacak…<br>Bu alana dosya sürükleyip bırakabilirsiniz.</div>"
  +   "</div>"
  + "</div>";

  const w = openWindowEx({ id, title: (title||folderIdOrPath), html, w: 720, h: 480 });

  const btn = document.getElementById(btnId);
  const inp = document.getElementById(inpId);
  const grid = document.getElementById(gridId);

  btn.addEventListener('click', ()=> inp.click());
  inp.addEventListener('change', ()=>{ if (inp.files?.length) uploadFiles(inp.files, folderIdOrPath); inp.value=''; });

  // Drag & drop
  grid.addEventListener('dragover', e=>{ e.preventDefault(); grid.style.boxShadow='inset 0 0 0 2px #3b82f6'; });
  grid.addEventListener('dragleave', ()=>{ grid.style.boxShadow='none'; });
  grid.addEventListener('drop', (e)=>{
    e.preventDefault(); grid.style.boxShadow='none';
    const files = e.dataTransfer?.files; if (files?.length) uploadFiles(files, folderIdOrPath);
  });

  function toast(msg, ms=1600){
    const t=document.createElement('div');
    t.textContent=msg;
    Object.assign(t.style,{position:'fixed',bottom:'36px',left:'50%',transform:'translateX(-50%)',
      background:'#1b1f28',color:'#e6e9ee',padding:'10px 14px',borderRadius:'10px',
      border:'1px solid #2a2f38',boxShadow:'0 2px 8px rgba(0,0,0,.25)',zIndex:99999,font:'500 13px system-ui'});
    document.body.appendChild(t); setTimeout(()=>t.remove(),ms);
  }
  function uploadFiles(fileList, target){
    const files = Array.from(fileList||[]); if(!files.length) return;
    const prog=document.createElement('div'), bar=document.createElement('div');
    Object.assign(prog.style,{position:'fixed',left:'16px',right:'16px',bottom:'16px',height:'12px',
      background:'#1b1f28',border:'1px solid #2a2f38',borderRadius:'6px',overflow:'hidden',zIndex:99998});
    Object.assign(bar.style,{height:'100%',width:'0%',background:'#198754',transition:'width .2s'});
    prog.appendChild(bar); document.body.appendChild(prog);

    const fd = new FormData();
    fd.append('action','upload_files');
    fd.append('folder', target||'');
    files.forEach(f=>fd.append('files[]',f,f.name));

    const xhr = new XMLHttpRequest();
    xhr.open('POST','api.php');
    xhr.withCredentials = true;
    xhr.setRequestHeader('X-Requested-With','XMLHttpRequest');
    xhr.upload.onprogress = (e)=>{ if(e.lengthComputable) bar.style.width = Math.round(e.loaded/e.total*100)+'%'; };
    xhr.onreadystatechange = ()=>{
      if(xhr.readyState===4){
        setTimeout(()=>prog.remove(),600);
        if(xhr.status>=200 && xhr.status<300){
          try{
            const res = JSON.parse(xhr.responseText||'{}');
            if(res?.saved?.length) toast('Yüklendi: '+res.saved.length+' dosya');
            else toast('Yükleme hatası');
          }catch(_){ toast('Sunucu yanıtı okunamadı'); }
        }else{
          toast('Yükleme başarısız (HTTP '+xhr.status+')');
        }
      }
    };
    xhr.send(fd);
  }
}


// Masaüstü placeholder
const grid=document.getElementById("grid");
function place(el,key,col,row){ const x=10+col*96, y=10+row*96; el.style.left=x+"px"; el.style.top=y+"px"; }
function dragify(el,key){
  let down=false,sx=0,sy=0,sl=0,st=0;
  el.addEventListener("pointerdown",(e)=>{down=true;sx=e.clientX;sy=e.clientY;const r=el.getBoundingClientRect(), R=grid.getBoundingClientRect();sl=r.left-R.left;st=r.top-R.top; el.setPointerCapture(e.pointerId);});
  el.addEventListener("pointermove",(e)=>{ if(!down) return; const dx=e.clientX-sx, dy=e.clientY-sy; el.style.left=(sl+dx)+"px"; el.style.top=(st+dy)+"px";});
  el.addEventListener("pointerup",()=>{ down=false; });
}
(function(){
  grid.innerHTML="";
  const home=document.createElement("div"); home.className="item"; home.dataset.id="home";
  home.innerHTML="<div class='ficon'><i class='fa-solid fa-house'></i></div><div class='flabel'>Ev</div>";
  home.addEventListener("click",()=>openFolder('Ev','Ev'));
  grid.appendChild(home); dragify(home,"home"); place(home,"home",0,0);
})();
</script>

<!-- Uploader config ve script (sonda, diğerlerine sonra) -->
<script src="partials/state.js.php"></script>
<script src="assets/login_mini.js"></script>
<script src="assets/profile.js"></script>
<script src="assets/start_menu_user_admin_inject.js"></script>
<script src="assets/create_folder_fix.js"></script>
<script src="assets/links_runtime.js"></script>
<script src="assets/ctx_extras.js"></script>
<script src="assets/tray_enhance.js"></script>
<script src="assets/desktop_rules.js"></script>
<script src="assets/trash_icon_inject.js"></script>
<script src="assets/wallpaper_cycle.js"></script>
<script src="assets/toast.js"></script>
<script src="assets/folders_patch.js"></script>
<script src="assets/new_folder_dialog.js"></script>
<script src="assets/folder_icon_picker.js"></script>
<script src="assets/trash_bin_v2.js"></script>
<script src="assets/trash_ui.js"></script>
<script src="assets/register_mini.js"></script>
<script>
  window.PardusUploaderConfig = {
    api: 'api_upload.php',
    mountButton: true,
    afterUploadRefresh: true,
    globalDrop: 'target-only'
  };
</script>
<script src="assets/uploader.js"></script>
<script src="assets/uploader.after.js"></script>
<script src="assets/folder_upload_inject.js"></script>
<script src="assets/desktop_delete_fix.js"></script>
<script src="assets/upload_policy_client.js"></script>



</body></html>