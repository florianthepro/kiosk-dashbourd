<?php($ht,"<IfModule mod_authz_core.c>\nRequire all denied\n</IfModule>\n<IfModule !mod_authz_core.c>\nDeny from all\n</IfModule>\n");@chmod($ht,0600);
$cfgFile=$data.'/config.json';$authFile=$data.'/auth.json';
session_set_cookie_params(['lifetime'=>0,'path'=>'/','secure'=>(!empty($_SERVER['HTTPS'])&&$_SERVER['HTTPS']!=='off'),'httponly'=>true,'samesite'=>'Strict']);
if(session_status()!==PHP_SESSION_ACTIVE)@session_start();
function h(string $s):string{return htmlspecialchars($s,ENT_QUOTES,'UTF-8');}
function jread(string $f,array $d):array{if(!file_exists($f))return $d;$s=@file_get_contents($f);if($s===false)return $d;$j=json_decode($s,true);return is_array($j)?$j:$d;}
function jwrite(string $f,array $a):bool{$t=$f.'.tmp';$j=json_encode($a,JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE|JSON_PRETTY_PRINT);if($j===false)return false;$ok=@file_put_contents($t,$j,LOCK_EX);if($ok===false)return false;@chmod($t,0600);return @rename($t,$f);}
function cleanText(string $s,int $m):string{$s=trim($s);$s=preg_replace('/[^\P{C}\t\n\r]+/u','',$s)??'';if(mb_strlen($s,'UTF-8')>$m)$s=mb_substr($s,0,$m,'UTF-8');return $s;}
function cleanTime(string $s):string{$s=trim($s);return preg_match('/^(?:[01]\d|2[0-3]):[0-5]\d$/',$s)?$s:'';}
function cleanColor(string $s):string{$s=trim($s);return preg_match('/^#[0-9a-fA-F]{6}$/',$s)?$s:'';}
function isHttpUrl(string $u):bool{$u=trim($u);if($u==='')return false;$p=@parse_url($u);if(!is_array($p)||empty($p['scheme']))return false;$sch=strtolower((string)$p['scheme']);return $sch==='http'||$sch==='https';}
function csrf():string{if(empty($_SESSION['csrf']))$_SESSION['csrf']=bin2hex(random_bytes(16));return (string)$_SESSION['csrf'];}
function csrfOk():bool{$t=(string)($_POST['csrf']??'');return $t!==''&&isset($_SESSION['csrf'])&&hash_equals((string)$_SESSION['csrf'],$t);}
$default=['timezone'=>'Europe/Berlin','global'=>['pollSeconds'=>30,'defaultRotationSeconds'=>12,'hideCursor'=>true,'defaultBackground'=>['type'=>'color','value'=>'#111111']],'cards'=>[['id'=>'karte1','title'=>'Beispiel Bild','type'=>'image','value'=>'https://picsum.photos/1600/900']],'scenes'=>[['name'=>'Standard','start'=>'00:00','end'=>'00:00','days'=>[1,2,3,4,5,6,7],'rotationSeconds'=>12,'background'=>['type'=>'color','value'=>'#111111'],'cardIds'=>['karte1']]]];
$cfg=jread($cfgFile,$default);
$auth=jread($authFile,['pwHash'=>'']);
$qs=(string)($_SERVER['QUERY_STRING']??'');
$dash=false;
if(isset($_GET['dash']))$dash=true;
if(!$dash&&isset($_GET[''])&&((string)$_GET['']==='dash'))$dash=true;
if(!$dash&&$qs==='dash')$dash=true;
if(!$dash&&$qs==='=dash')$dash=true;
if(!$dash&&$qs==='0dash')$dash=true;
if(!$dash&&preg_match('/(^|&)(dash|=dash|0dash)(&|$)/i',$qs))$dash=true;
if(!$dash&&stripos($qs,'dash')!==false)$dash=true;
$api=cleanText((string)($_GET['api']??''),24);
if(isset($_GET['logout'])){$_SESSION=[];@session_destroy();header('Location: '.$_SERVER['PHP_SELF']);exit;}
$hasHash=is_string($auth['pwHash']??'')&&($auth['pwHash']??'')!=='';
$logged=(!empty($_SESSION['logged'])&&$_SESSION['logged']===true&&$hasHash);
if($api==='config'){header('Content-Type: application/json; charset=UTF-8');echo json_encode($cfg,JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE);exit;}
if($dash){
$glob=is_array($cfg['global']??null)?$cfg['global']:$default['global'];
$poll=(int)($glob['pollSeconds']??30);if($poll<5)$poll=5;if($poll>300)$poll=300;
$hide=!empty($glob['hideCursor']);
echo '<!doctype html><html lang="de"><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Dashboard</title><style>html,body{width:100%;height:100%;margin:0;overflow:hidden;background:#000;font-family:system-ui,Segoe UI,Roboto,Arial,sans-serif}body'.($hide?'{cursor:none}':'{}').'.bgimg{background-size:cover;background-position:center;background-repeat:no-repeat}.wrap{position:fixed;inset:0;display:flex;align-items:center;justify-content:center}.panel{width:94vw;height:94vh;max-width:1700px;max-height:960px;border-radius:14px;overflow:hidden;box-shadow:0 12px 50px rgba(0,0,0,.6);background:rgba(0,0,0,.25);backdrop-filter:blur(6px);position:relative}iframe,img{width:100%;height:100%;border:0;display:block;background:#000}.html{width:100%;height:100%;display:flex;align-items:center;justify-content:center;text-align:center;color:#fff;font-size:40px;line-height:1.15;padding:22px;box-sizing:border-box}.bar{position:absolute;left:0;right:0;bottom:0;display:flex;gap:12px;align-items:center;justify-content:space-between;padding:12px 14px;color:#fff;background:linear-gradient(180deg,transparent,rgba(0,0,0,.78));font-size:16px}.title{font-weight:650;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:78%}.clock{opacity:.9;font-variant-numeric:tabular-nums}</style><div class="wrap"><div class="panel" id="panel"></div></div><script>';
echo 'const pollSeconds='.json_encode($poll).';let cfg=null,sceneSig="",cardIdx=0,rotTimer=null,clockTimer=null;';
echo 'function nowParts(tz){try{const fmt=new Intl.DateTimeFormat("de-DE",{timeZone:tz,weekday:"short",hour:"2-digit",minute:"2-digit"});const parts=fmt.formatToParts(new Date());const o={};for(const p of parts)o[p.type]=p.value;let w=(o.weekday||"Mo").replace(/[^A-Za-zÄÖÜäöü]/g,"");o.weekday=w;return o;}catch(e){const d=new Date();const wd=["So","Mo","Di","Mi","Do","Fr","Sa"][d.getDay()];return{weekday:wd,hour:String(d.getHours()).padStart(2,"0"),minute:String(d.getMinutes()).padStart(2,"0")};}}';
echo 'function dowNum(w){return({Mo:1,Di:2,Mi:3,Do:4,Fr:5,Sa:6,So:7,Mon:1,Tue:2,Wed:3,Thu:4,Fri:5,Sat:6,Sun:7}[w]||1);}';
echo 'function inRange(st,en,cur){if(!st||!en)return false;if(st===en)return true;return st<en?(cur>=st&&cur<en):(cur>=st||cur<en);}';
echo 'async function loadCfg(){try{const r=await fetch(location.pathname+"?api=config",{cache:"no-store"});if(!r.ok)throw 0;cfg=await r.json();}catch(e){}}';
echo 'function pickScene(){if(!cfg)return null;const tz=(cfg.timezone||"Europe/Berlin");const p=nowParts(tz);const cur=(p.hour||"00")+":"+(p.minute||"00");const dow=dowNum(p.weekday||"Mo");const scenes=Array.isArray(cfg.scenes)?cfg.scenes:[];for(const s of scenes){if(!s||typeof s!=="object")continue;const st=String(s.start||"");const en=String(s.end||"");let days=Array.isArray(s.days)&&s.days.length?s.days:[1,2,3,4,5,6,7];days=days.map(x=>parseInt(x,10)).filter(x=>x>=1&&x<=7);if(!days.includes(dow))continue;if(inRange(st,en,cur))return s;}return scenes.length?scenes[0]:null;}';
echo 'function sigScene(s){if(!s)return"";return JSON.stringify({start:s.start,end:s.end,days:s.days||[],rot:s.rotationSeconds||0,bg:s.background||null,ids:s.cardIds||[]});}';
echo 'function applyBg(s){const g=(cfg&&cfg.global)||{};const def=(g.defaultBackground)||{type:"color",value:"#111111"};const bg=(s&&s.background)||def;document.body.classList.remove("bgimg");document.body.style.background="#000";document.body.style.backgroundImage="";if(bg&&bg.type==="image"&&bg.value){document.body.classList.add("bgimg");document.body.style.backgroundImage="url("+bg.value+")";}else if(bg&&bg.type==="color"&&bg.value){document.body.style.background=bg.value;}}';
echo 'function cardsFor(s){const all=Array.isArray(cfg.cards)?cfg.cards:[];if(!s||!Array.isArray(s.cardIds)||!s.cardIds.length)return all;const map=new Map(all.map(c=>[String(c.id||""),c]));return s.cardIds.map(id=>map.get(String(id))).filter(Boolean);}';
echo 'function renderCard(c){const panel=document.getElementById("panel");panel.innerHTML="";let inner=null;const t=String(c.type||"");if(t==="url"){inner=document.createElement("iframe");inner.src=String(c.value||"");inner.referrerPolicy="no-referrer";inner.allow="autoplay; fullscreen";}else if(t==="image"){inner=document.createElement("img");inner.src=String(c.value||"");inner.alt="";inner.decoding="async";}else if(t==="html"){inner=document.createElement("div");inner.className="html";inner.innerHTML=String(c.value||"");}else{inner=document.createElement("div");inner.className="html";inner.textContent="Keine Karte";}panel.appendChild(inner);const bar=document.createElement("div");bar.className="bar";const title=document.createElement("div");title.className="title";title.textContent=String(c.title||"");const clock=document.createElement("div");clock.className="clock";bar.appendChild(title);bar.appendChild(clock);panel.appendChild(bar);return clock;}';
echo 'function tickClock(clockEl){const tz=(cfg&&cfg.timezone)||"Europe/Berlin";const p=nowParts(tz);clockEl.textContent=(p.weekday||"")+" "+(p.hour||"00")+":"+(p.minute||"00");}';
echo 'function startRotation(){if(rotTimer){clearInterval(rotTimer);rotTimer=null;}if(clockTimer){clearInterval(clockTimer);clockTimer=null;}if(!cfg){document.getElementById("panel").innerHTML="<div class=html>Keine Konfiguration</div>";return;}const s=pickScene();sceneSig=sigScene(s);applyBg(s);const g=cfg.global||{};let rot=parseInt((s&&s.rotationSeconds)||g.defaultRotationSeconds||12,10);if(!isFinite(rot)||rot<3)rot=3;if(rot>3600)rot=3600;let list=cardsFor(s);if(!list.length){document.getElementById("panel").innerHTML="<div class=html>Keine Karten konfiguriert</div>";return;}if(cardIdx>=list.length)cardIdx=0;let clockEl=renderCard(list[cardIdx]);tickClock(clockEl);clockTimer=setInterval(()=>tickClock(clockEl),1000);rotTimer=setInterval(()=>{const ns=pickScene();const nsig=sigScene(ns);if(nsig!==sceneSig){sceneSig=nsig;cardIdx=0;applyBg(ns);list=cardsFor(ns);}else{list=cardsFor(ns);}if(!list.length)return;cardIdx=(cardIdx+1)%list.length;clockEl=renderCard(list[cardIdx]);tickClock(clockEl);},rot*1000);}';
echo 'async function main(){await loadCfg();startRotation();setInterval(async()=>{const before=JSON.stringify(cfg||{});await loadCfg();const after=JSON.stringify(cfg||{});if(after!==before){cardIdx=0;startRotation();}},pollSeconds*1000);}main();</script></html>';
exit;
}
function requireAdmin(array $auth):void{
$has=is_string($auth['pwHash']??'')&&($auth['pwHash']??'')!=='';
$ok=(!empty($_SESSION['logged'])&&$_SESSION['logged']===true&&$has);
if($ok)return;
$err='';
if(!$has){
if($_SERVER['REQUEST_METHOD']==='POST'&&isset($_POST['setup'])){
if(!csrfOk()){http_response_code(400);exit('CSRF');}
$pw=(string)($_POST['pw']??'');$pw2=(string)($_POST['pw2']??'');
if($pw===''||$pw!==$pw2||mb_strlen($pw,'UTF-8')<10)$err='Passwort min. 10 Zeichen und identisch.';
else{$hash=password_hash($pw,PASSWORD_DEFAULT);if(!jwrite($GLOBALS['authFile'],['pwHash'=>$hash]))$err='Auth-Datei nicht schreibbar.';else{$_SESSION['logged']=true;header('Location: '.$_SERVER['PHP_SELF']);exit;}}
}
echo '<!doctype html><html lang="de"><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Setup</title><style>body{margin:0;background:#0f1115;color:#e7e7e7;font-family:system-ui,Segoe UI,Roboto,Arial,sans-serif}.box{max-width:560px;margin:60px auto;padding:18px;border-radius:12px;background:#151a22;border:1px solid #273044}input,button{width:100%;box-sizing:border-box;border-radius:10px;font-size:16px}input{padding:11px;border:1px solid #2a3240;background:#0e1219;color:#fff}button{padding:11px;border:0;background:#2b7cff;color:#fff;font-weight:700;cursor:pointer;margin-top:10px}.err{color:#ff6b6b;margin:10px 0}</style><div class="box"><h2>Admin-Setup</h2><div>'.h($err?:'Passwort setzen (min. 10 Zeichen).').'</div><form method="post"><input type="hidden" name="csrf" value="'.h(csrf()).'"><input type="password" name="pw" autocomplete="new-password" minlength="10" placeholder="Passwort" required><div style="height:10px"></div><input type="password" name="pw2" autocomplete="new-password" minlength="10" placeholder="Passwort wiederholen" required><button name="setup" value="1">Speichern</button></form></div></html>';
exit;
}
if($_SERVER['REQUEST_METHOD']==='POST'&&isset($_POST['login'])){
if(!csrfOk()){http_response_code(400);exit('CSRF');}
$pw=(string)($_POST['pw']??'');$hash=(string)($auth['pwHash']??'');
if($hash!==''&&password_verify($pw,$hash)){$_SESSION['logged']=true;header('Location: '.$_SERVER['PHP_SELF']);exit;}
$err='Login fehlgeschlagen.';
}
echo '<!doctype html><html lang="de"><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Login</title><style>body{margin:0;background:#0f1115;color:#e7e7e7;font-family:system-ui,Segoe UI,Roboto,Arial,sans-serif}.box{max-width:520px;margin:70px auto;padding:18px;border-radius:12px;background:#151a22;border:1px solid #273044}input,button{width:100%;box-sizing:border-box;border-radius:10px;font-size:16px}input{padding:11px;border:1px solid #2a3240;background:#0e1219;color:#fff}button{padding:11px;border:0;background:#2b7cff;color:#fff;font-weight:700;cursor:pointer;margin-top:10px}.err{color:#ff6b6b;margin:10px 0}</style><div class="box"><h2>Admin-Login</h2>'.($err?'<div class="err">'.h($err).'</div>':'').'<form method="post"><input type="hidden" name="csrf" value="'.h(csrf()).'"><input type="password" name="pw" autocomplete="current-password" placeholder="Passwort" required><button name="login" value="1">Anmelden</button></form><div style="margin-top:12px;opacity:.85">Dashboard: '.h($_SERVER['PHP_SELF']).'?=dash (auch ?0dash)</div></div></html>';
exit;
}
requireAdmin($auth);
$msg='';
if($_SERVER['REQUEST_METHOD']==='POST'&&isset($_POST['save'])){
if(!csrfOk()){http_response_code(400);exit('CSRF');}
$tz=cleanText((string)($_POST['timezone']??'Europe/Berlin'),64);
$poll=(int)($_POST['pollSeconds']??30);if($poll<5)$poll=5;if($poll>300)$poll=300;
$defRot=(int)($_POST['defaultRotationSeconds']??12);if($defRot<3)$defRot=3;if($defRot>3600)$defRot=3600;
$hide=((string)($_POST['hideCursor']??'1')==='1');
$bgType=cleanText((string)($_POST['bgType']??'color'),10);
$bgVal=cleanText((string)($_POST['bgValue']??'#111111'),4000);
if($bgType==='image'){if(!isHttpUrl($bgVal)){$bgType='color';$bgVal='#111111';}}else{$bgType='color';$c=cleanColor($bgVal);$bgVal=$c!==''?$c:'#111111';}
$cards=[];
$ids=$_POST['card_id']??[];$titles=$_POST['card_title']??[];$types=$_POST['card_type']??[];$vals=$_POST['card_value']??[];
if(is_array($ids)&&is_array($titles)&&is_array($types)&&is_array($vals)){
$n=max(count($ids),count($titles),count($types),count($vals));
for($i=0;$i<$n;$i++){
$id=cleanText((string)($ids[$i]??''),40);
if($id===''||!preg_match('/^[a-zA-Z0-9_-]{1,40}$/',$id))continue;
$type=cleanText((string)($types[$i]??''),10);
if(!in_array($type,['url','image','html'],true))continue;
$title=cleanText((string)($titles[$i]??''),160);
$val=(string)($vals[$i]??'');
if($type==='url'||$type==='image'){if(!isHttpUrl($val))continue;$val=cleanText($val,8000);}else{$val=trim($val);if(mb_strlen($val,'UTF-8')>80000)$val=mb_substr($val,0,80000,'UTF-8');}
$cards[]=['id'=>$id,'title'=>$title,'type'=>$type,'value'=>$val];
}
}
if(!$cards)$cards=$default['cards'];
$cardIdSet=array_flip(array_map(fn($c)=>(string)($c['id']??''),$cards));
$scenes=[];
$sn=$_POST['scene_name']??[];$ss=$_POST['scene_start']??[];$se=$_POST['scene_end']??[];$sd=$_POST['scene_days']??[];$sr=$_POST['scene_rot']??[];$sbt=$_POST['scene_bgType']??[];$sbv=$_POST['scene_bgValue']??[];$sci=$_POST['scene_cardIds']??[];
if(is_array($sn)&&is_array($ss)&&is_array($se)){
$m=max(count($sn),count($ss),count($se));
for($i=0;$i<$m;$i++){
$name=cleanText((string)($sn[$i]??''),80);
$start=cleanTime((string)($ss[$i]??''));$end=cleanTime((string)($se[$i]??''));
if($start===''||$end==='')continue;
$daysRaw=cleanText((string)($sd[$i]??'1,2,3,4,5,6,7'),120);
$days=array_values(array_filter(array_map(fn($x)=>(int)trim($x),explode(',',$daysRaw)),fn($x)=>$x>=1&&$x<=7));
if(!$days)$days=[1,2,3,4,5,6,7];
$rot=(int)($sr[$i]??0);if($rot<0)$rot=0;if($rot>3600)$rot=3600;
$bgT=cleanText((string)($sbt[$i]??''),10);
$bgV=cleanText((string)($sbv[$i]??''),8000);
$bg=null;
if($bgT==='image'&&isHttpUrl($bgV))$bg=['type'=>'image','value'=>$bgV];
if($bgT==='color'){$c=cleanColor($bgV);if($c!=='')$bg=['type'=>'color','value'=>$c];}
$idsRaw=cleanText((string)($sci[$i]??''),20000);
$sel=array_values(array_filter(array_map(fn($x)=>cleanText(trim($x),40),explode(',',$idsRaw)),fn($x)=>$x!==''&&preg_match('/^[a-zA-Z0-9_-]{1,40}$/',$x)&&isset($cardIdSet[$x])));
$s=['name'=>$name,'start'=>$start,'end'=>$end,'days'=>$days];
if($rot>0)$s['rotationSeconds']=$rot;
if($bg!==null)$s['background']=$bg;
if($sel)$s['cardIds']=$sel;
$scenes[]=$s;
}
}
if(!$scenes)$scenes=$default['scenes'];
$new=['timezone'=>$tz,'global'=>['pollSeconds'=>$poll,'defaultRotationSeconds'=>$defRot,'hideCursor'=>$hide,'defaultBackground'=>['type'=>$bgType,'value'=>$bgVal]],'cards'=>$cards,'scenes'=>$scenes];
if(jwrite($cfgFile,$new)){$cfg=$new;$msg='Gespeichert.';}else{$msg='Fehler: config.json nicht schreibbar.';}
}
$glob=is_array($cfg['global']??null)?$cfg['global']:$default['global'];
$bg=is_array($glob['defaultBackground']??null)?$glob['defaultBackground']:$default['global']['defaultBackground'];
$cards=is_array($cfg['cards']??null)?$cfg['cards']:$default['cards'];
$scenes=is_array($cfg['scenes']??null)?$cfg['scenes']:$default['scenes'];
echo '<!doctype html><html lang="de"><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Dashboard Konfiguration</title><style>body{margin:0;background:#0f1115;color:#e7e7e7;font-family:system-ui,Segoe UI,Roboto,Arial,sans-serif}.top{position:sticky;top:0;z-index:5;background:#121824;border-bottom:1px solid #273044;padding:12px 14px;display:flex;align-items:center;justify-content:space-between;gap:12px;flex-wrap:wrap}.wrap{max-width:1160px;margin:14px auto;padding:0 14px 40px}.card{background:#151a22;border:1px solid #273044;border-radius:12px;padding:14px;margin:12px 0}h2{margin:4px 0 10px;font-size:18px}input,select,textarea,button{box-sizing:border-box;border-radius:10px;font-size:14px}input,select,textarea{width:100%;padding:10px;border:1px solid #2a3240;background:#0e1219;color:#fff}textarea{min-height:58px;resize:vertical}.grid{display:grid;grid-template-columns:1fr 1fr;gap:10px}.row{display:grid;grid-template-columns:170px 1fr;gap:10px;align-items:center;margin:8px 0}.btn{display:inline-flex;align-items:center;justify-content:center;padding:10px 12px;border:0;border-radius:10px;background:#2b7cff;color:#fff;font-weight:700;cursor:pointer}.btn2{background:#273044}.btn3{background:#c23b3b}.small{font-size:12px;opacity:.82}.mono{font-family:ui-monospace,Menlo,Consolas,monospace}.ok{color:#7dff9a}.bad{color:#ff6b6b}table{width:100%;border-collapse:collapse}th,td{border-bottom:1px solid #243047;padding:8px 6px;vertical-align:top}th{text-align:left;opacity:.85;font-weight:650}@media(max-width:920px){.grid{grid-template-columns:1fr}.row{grid-template-columns:1fr}}</style>';
echo '<div class="top"><div><strong>Dashboard Konfiguration</strong> <span class="small">Dashboard: '.h($_SERVER['PHP_SELF']).'?=dash (auch ?0dash)</span></div><div><a style="color:#9bc3ff;text-decoration:none" href="'.h($_SERVER['PHP_SELF']).'?=dash">Test</a> <span class="small">|</span> <a style="color:#9bc3ff;text-decoration:none" href="'.h($_SERVER['PHP_SELF']).'?logout=1">Logout</a></div></div>';
echo '<div class="wrap">';
if($msg)echo '<div class="card"><div class="'.(str_starts_with($msg,'Fehler')?'bad':'ok').'">'.h($msg).'</div></div>';
echo '<form method="post"><input type="hidden" name="csrf" value="'.h(csrf()).'">';
echo '<div class="card"><h2>Global</h2><div class="grid">';
echo '<div class="row"><label>Zeitzone</label><input name="timezone" value="'.h((string)($cfg['timezone']??'Europe/Berlin')).'"></div>';
echo '<div class="row"><label>Config-Poll (s)</label><input type="number" min="5" max="300" name="pollSeconds" value="'.h((string)($glob['pollSeconds']??30)).'"></div>';
echo '<div class="row"><label>Rotation Default (s)</label><input type="number" min="3" max="3600" name="defaultRotationSeconds" value="'.h((string)($glob['defaultRotationSeconds']??12)).'"></div>';
echo '<div class="row"><label>Mauszeiger</label><select name="hideCursor"><option value="1"'.(!empty($glob['hideCursor'])?' selected':'').'>verstecken</option><option value="0"'.(empty($glob['hideCursor'])?' selected':'').'>anzeigen</option></select></div>';
echo '<div class="row"><label>Hintergrund Typ</label><select name="bgType"><option value="color"'.(((string)($bg['type']??''))==='color'?' selected':'').'>Farbe</option><option value="image"'.(((string)($bg['type']??''))==='image'?' selected':'').'>Bild-URL</option></select></div>';
echo '<div class="row"><label>Hintergrund Wert</label><input name="bgValue" value="'.h((string)($bg['value']??'#111111')).'" placeholder="#111111 oder https://..."></div>';
echo '</div><div class="small">Hinweis: url-Karten sind iframe; manche Seiten blockieren Einbettung.</div></div>';
echo '<div class="card"><h2>Karten (rotierend)</h2><table><thead><tr><th style="width:140px">ID</th><th style="width:220px">Titel</th><th style="width:110px">Typ</th><th>Wert</th><th style="width:110px"></th></tr></thead><tbody id="cardsBody">';
foreach($cards as $c){
echo '<tr><td><input class="mono" name="card_id[]" value="'.h((string)($c['id']??'')).'" placeholder="z.B. news"></td><td><input name="card_title[]" value="'.h((string)($c['title']??'')).'" placeholder="Titel"></td><td><select name="card_type[]"><option value="url"'.(((string)($c['type']??''))==='url'?' selected':'').'>url</option><option value="image"'.(((string)($c['type']??''))==='image'?' selected':'').'>image</option><option value="html"'.(((string)($c['type']??''))==='html'?' selected':'').'>html</option></select></td><td><textarea name="card_value[]" placeholder="https://... oder HTML">'.h((string)($c['value']??'')).'</textarea></td><td><button type="button" class="btn btn3" onclick="this.closest(\'tr\').remove()">Entfernen</button></td></tr>';
}
echo '</tbody></table><div style="margin-top:10px;display:flex;gap:10px;flex-wrap:wrap"><button type="button" class="btn btn2" onclick="addCard()">+ Karte</button><span class="small">ID: a-z A-Z 0-9 _ -</span></div></div>';
echo '<div class="card"><h2>Szenen (Zeitfenster)</h2><table><thead><tr><th style="width:160px">Name</th><th style="width:90px">Start</th><th style="width:90px">Ende</th><th style="width:170px">Wochentage</th><th style="width:120px">Rotation</th><th style="width:120px">BG Typ</th><th>BG Wert</th><th style="width:240px">Karten-IDs</th><th style="width:110px"></th></tr></thead><tbody id="scenesBody">';
foreach($scenes as $s){
$days=$s['days']??[1,2,3,4,5,6,7];$daysStr=is_array($days)?implode(',',array_map(fn($x)=>(string)(int)$x,$days)):'1,2,3,4,5,6,7';
$rot=(string)($s['rotationSeconds']??0);
$bgT=(string)(($s['background']['type']??'')?:'');
$bgV=(string)(($s['background']['value']??'')?:'');
$ids=$s['cardIds']??[];$idsStr=is_array($ids)?implode(',',array_map(fn($x)=>(string)$x,$ids)):'';
echo '<tr><td><input name="scene_name[]" value="'.h((string)($s['name']??'')).'" placeholder="z.B. Morgens"></td><td><input class="mono" name="scene_start[]" value="'.h((string)($s['start']??'')).'" placeholder="07:00"></td><td><input class="mono" name="scene_end[]" value="'.h((string)($s['end']??'')).'" placeholder="10:00"></td><td><input class="mono" name="scene_days[]" value="'.h($daysStr).'" placeholder="1,2,3,4,5"></td><td><input type="number" min="0" max="3600" name="scene_rot[]" value="'.h($rot).'" placeholder="0=Default"></td><td><select name="scene_bgType[]"><option value=""'.($bgT===''?' selected':'').'>Default</option><option value="color"'.($bgT==='color'?' selected':'').'>Farbe</option><option value="image"'.($bgT==='image'?' selected':'').'>Bild</option></select></td><td><input name="scene_bgValue[]" value="'.h($bgV).'" placeholder="#000000 oder https://..."></td><td><input class="mono" name="scene_cardIds[]" value="'.h($idsStr).'" placeholder="news,weather (leer=alle)"></td><td><button type="button" class="btn btn3" onclick="this.closest(\'tr\').remove()">Entfernen</button></td></tr>';
}
echo '</tbody></table><div style="margin-top:10px;display:flex;gap:10px;flex-wrap:wrap"><button type="button" class="btn btn2" onclick="addScene()">+ Szene</button><span class="small">Wochentage: 1=Mo … 7=So. 22:00–02:00 über Mitternacht. 00:00–00:00 ganztägig.</span></div></div>';
echo '<div class="card"><button class="btn" name="save" value="1">Speichern</button></div>';
echo '</form></div><script>';
echo 'function addCard(){const tr=document.createElement("tr");tr.innerHTML=\'<td><input class="mono" name="card_id[]" placeholder="z.B. news"></td><td><input name="card_title[]" placeholder="Titel"></td><td><select name="card_type[]"><option value="url">url</option><option value="image" selected>image</option><option value="html">html</option></select></td><td><textarea name="card_value[]" placeholder="https://... oder HTML"></textarea></td><td><button type="button" class="btn btn3" onclick="this.closest(\\\\\\\'tr\\\\\\\').remove()">Entfernen</button></td>\';document.getElementById("cardsBody").appendChild(tr);}';
echo 'function addScene(){const tr=document.createElement("tr");tr.innerHTML=\'<td><input name="scene_name[]" placeholder="z.B. Morgens"></td><td><input class="mono" name="scene_start[]" value="00:00"></td><td><input class="mono" name="scene_end[]" value="00:00"></td><td><input class="mono" name="scene_days[]" value="1,2,3,4,5,6,7"></td><td><input type="number" min="0" max="3600" name="scene_rot[]" value="0" placeholder="0=Default"></td><td><select name="scene_bgType[]"><option value="" selected>Default</option><option value="color">Farbe</option><option value="image">Bild</option></select></td><td><input name="scene_bgValue[]" value="" placeholder="#000000 oder https://..."></td><td><input class="mono" name="scene_cardIds[]" value="" placeholder="news,weather (leer=alle)"></td><td><button type="button" class="btn btn3" onclick="this.closest(\\\\\\\'tr\\\\\\\').remove()">Entfernen</button></td>\';document.getElementById("scenesBody").appendChild(tr);}';
echo '</script></html>';
``
declare(strict_types=1);
header('X-Content-Type-Options: nosniff');
header('Referrer-Policy: no-referrer');
header('X-Frame-Options: SAMEORIGIN');
$root=__DIR__;$data=$root.'/data';if(!is_dir($data))@mkdir($data,0700,true);
