<?php declare(strict_types=1);_reporting(E_ALL);
mb_internal_encoding('UTF-8');

$BASE=__DIR__;
$DATA=$BASE.'/data';
$YAML_PRIMARY=$DATA.'/dashboard.yaml';
$YAML_FALLBACK=$BASE.'/dashboard.yaml';

function ensureDataDir(string $dir):void{if(!is_dir($dir))@mkdir($dir,0700,true);$ht=$dir.'/.htaccess';if(!is_file($ht))@file_put_contents($ht,"Deny from all\n");}
function isDashRequest():bool{$qs=$_SERVER['QUERY_STRING']??'';if(isset($_GET['mode'])&&$_GET['mode']==='dash')return true;if(isset($_GET['dash']))return true;if(isset($_GET[''])&&$_GET['']==='dash')return true;if($qs==='dash'||$qs==='=dash')return true;if(stripos($qs,'dash')!==false&&preg_match('~(^|[&=?])dash($|[&=])~i',$qs))return true;if(stripos($qs,'0dash')!==false)return true;return false;}
function isConfigRequest():bool{$qs=$_SERVER['QUERY_STRING']??'';if(isset($_GET['mode'])&&$_GET['mode']==='config')return true;if(isset($_GET['config']))return true;if($qs==='config'||str_starts_with($qs,'config&')||str_ends_with($qs,'&config'))return true;return false;}
function jsonOut($data,int $code=200,array $headers=[]):never{http_response_code($code);header('Content-Type: application/json; charset=utf-8');header('X-Content-Type-Options: nosniff');foreach($headers as $k=>$v)header($k.': '.$v);echo json_encode($data,JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE);exit;}
function htmlOut(string $html,int $code=200,array $headers=[]):never{http_response_code($code);header('Content-Type: text/html; charset=utf-8');header('X-Content-Type-Options: nosniff');header('Referrer-Policy: no-referrer');header('X-Frame-Options: SAMEORIGIN');foreach($headers as $k=>$v)header($k.': '.$v);echo $html;exit;}
function safeStr($v,string $d=''):string{if(is_string($v))return $v; if(is_numeric($v))return (string)$v; if(is_bool($v))return $v?'true':'false'; return $d;}
function safeInt($v,int $d=0,int $min=0,int $max=86400):int{if(is_numeric($v)){$n=(int)$v; if($n<$min)$n=$min; if($n>$max)$n=$max; return $n;}return $d;}
function safeBool($v,bool $d=false):bool{if(is_bool($v))return $v; if(is_string($v)){ $s=strtolower(trim($v)); if(in_array($s,['1','true','yes','on'],true))return true; if(in_array($s,['0','false','no','off'],true))return false;} if(is_numeric($v))return (int)$v!==0; return $d;}
function safeUrl($v):string{$s=trim(safeStr($v,''));if($s==='')return '';if(preg_match('~^https?://~i',$s))return $s;if(preg_match('~^data:image/(png|jpe?g|gif|webp|svg\+xml);base64,~i',$s))return $s;return '';}
function nowIso():string{return (new DateTimeImmutable('now'))->format(DateTimeInterface::ATOM);}

function yamlParseScalar(string $s){$s=trim($s);if($s==='')return '';if($s==='null'||$s==='~')return null;if($s==='true')return true;if($s==='false')return false;if(is_numeric($s)&&preg_match('~^-?\d+(\.\d+)?$~',$s))return strpos($s,'.')!==false?(float)$s:(int)$s;if((str_starts_with($s,'"')&&str_ends_with($s,'"'))||(str_starts_with($s,"'")&&str_ends_with($s,"'"))){$q=$s[0];$body=substr($s,1,-1);if($q==='"')$body=strtr($body,['\\"'=>'"','\\\\'=>'\\','\\n'=>"\n",'\\r'=>"\r",'\\t'=>"\t"]);return $body;}return $s;}
function yamlParse(string $text):array{
$lines=preg_split('~\R~u',$text);$root=[];$stack=[['indent'=>-1,'type'=>'map','ref'=>&$root]];
for($i=0,$c=count($lines);$i<$c;$i++){
$raw=$lines[$i];if($raw===null)continue;$line=preg_replace('~\s+#.*$~u','',rtrim($raw));if(trim($line)==='')continue;
preg_match('~^(\s*)~u',$line,$m);$indent=strlen($m[1]);$trim=ltrim($line);
while(count($stack)>1&&$indent<=$stack[count($stack)-1]['indent'])array_pop($stack);
$parent=&$stack[count($stack)-1]['ref'];$ptype=$stack[count($stack)-1]['type'];
if(str_starts_with($trim,'- ')){
$itemStr=substr($trim,2);
if($ptype!=='seq'){if(is_array($parent)&&array_is_list($parent)===false){$parent=[];}$stack[count($stack)-1]['type']='seq';$ptype='seq';}
if(!is_array($parent))$parent=[];
if($itemStr===''||preg_match('~^[^:]+:\s*.*$~u',$itemStr)){
$node=[];
$parent[]=&$node;$stack[]=['indent'=>$indent,'type'=>'map','ref'=>&$node];
if($itemStr!==''){
if(preg_match('~^([^:]+):\s*(.*)$~u',$itemStr,$mm)){
$k=trim($mm[1]);$v=trim($mm[2]);
if($v===''){ $node[$k]=[];$stack[]=['indent'=>$indent+2,'type'=>'map','ref'=>&$node[$k]]; }
else $node[$k]=yamlParseScalar($v);
}}
continue;
}else{
$parent[]=yamlParseScalar($itemStr);
continue;
}}
if(preg_match('~^([^:]+):\s*(.*)$~u',$trim,$mm)){
$k=trim($mm[1]);$v=trim($mm[2]);
if($v===''){ $parent[$k]=[];$stack[]=['indent'=>$indent,'type'=>'map','ref'=>&$parent[$k]]; }
else $parent[$k]=yamlParseScalar($v);
continue;
}}
return $root;
}
function yamlDumpScalar($v):string{
if($v===null)return 'null';
if(is_bool($v))return $v?'true':'false';
if(is_int($v)||is_float($v))return (string)$v;
$s=(string)$v;
if($s===''||preg_match('~[:\#\-\[\]\{\},&\*\!<>\|%@"\'\s]~u',$s))return '"'.strtr($s,["\\"=>"\\\\","\""=>"\\\"","\n"=>"\\n","\r"=>"\\r","\t"=>"\\t"]).'"';
return $s;
}
function yamlDump($data,int $indent=0):string{
$sp=str_repeat('  ',$indent);
if(is_array($data)){
$isList=array_is_list($data);
$out=[];
if($isList){
foreach($data as $item){
if(is_array($item)){
$out[]=$sp.'-';
$out[]=yamlDump($item,$indent+1);
}else $out[]=$sp.'- '.yamlDumpScalar($item);
}
}else{
foreach($data as $k=>$v){
if(is_array($v)){
$out[]=$sp.$k.':';
$out[]=yamlDump($v,$indent+1);
}else $out[]=$sp.$k.': '.yamlDumpScalar($v);
}
}
return implode("\n",$out);
}
return $sp.yamlDumpScalar($data);
}

function defaultConfig():array{
return [
'version'=>1,
'admin_token'=>'CHANGE_ME',
'timezone'=>'Europe/Berlin',
'title'=>'Kiosk Dashboard',
'background'=>[
'mode'=>'rotate',
'fade_ms'=>700,
'items'=>[
['type'=>'image','src'=>'https://picsum.photos/1920/1080?random=1','duration'=>25,'schedule'=>[['days'=>['Mon','Tue','Wed','Thu','Fri'],'from'=>'06:00','to'=>'18:00']]],
['type'=>'image','src'=>'https://picsum.photos/1920/1080?random=2','duration'=>25,'schedule'=>[['days'=>['Sat','Sun'],'from'=>'00:00','to'=>'23:59']]],
['type'=>'color','color'=>'#0b1220','duration'=>25,'schedule'=>[]]
]],
'cards'=>[
'columns'=>3,
'slots'=>6,
'gap_px'=>14,
'border_radius_px'=>14,
'rotate'=>true,
'default_duration'=>30,
'items'=>[
['type'=>'url','title'=>'Intranet','src'=>'https://example.com','duration'=>30,'slot'=>1,'sandbox'=>'on','schedule'=>[]],
['type'=>'url','title'=>'Monitoring','src'=>'https://example.org','duration'=>30,'slot'=>2,'sandbox'=>'on','schedule'=>[]],
['type'=>'image','title'=>'QR / Hinweis','src'=>'https://picsum.photos/800/600?random=3','duration'=>20,'slot'=>3,'schedule'=>[]],
['type'=>'url','title'=>'News','src'=>'https://example.net','duration'=>25,'slot'=>4,'sandbox'=>'on','schedule'=>[['days'=>['Mon','Tue','Wed','Thu','Fri'],'from'=>'07:00','to'=>'19:00']]],
['type'=>'image','title'=>'Wochenende','src'=>'https://picsum.photos/800/600?random=4','duration'=>25,'slot'=>4,'schedule'=>[['days'=>['Sat','Sun'],'from'=>'00:00','to'=>'23:59']]]
]
],
'display'=>[
'show_clock'=>true,
'clock_format'=>'24h',
'corner_badge'=>'',
'kiosk_hint'=>'F11 für Vollbild'
]
];
}

function loadYamlConfig(string $primary,string $fallback):array{
$path=is_file($primary)?$primary:(is_file($fallback)?$fallback:$primary);
if(!is_file($path))return ['__path'=>$path,'__raw'=>'','__data'=>defaultConfig(),'__missing'=>true];
$raw=(string)file_get_contents($path);
$data=[];
try{$data=yamlParse($raw);}catch(Throwable $e){$data=defaultConfig();$data['__parse_error']=$e->getMessage();}
return ['__path'=>$path,'__raw'=>$raw,'__data'=>$data,'__missing'=>false];
}

function normalizeConfig(array $cfg):array{
$out=defaultConfig();
foreach($cfg as $k=>$v){if($k==='__parse_error')continue;$out[$k]=$v;}
$out['version']=safeInt($out['version'],1,1,999);
$out['admin_token']=safeStr($out['admin_token'],'CHANGE_ME');
$out['timezone']=safeStr($out['timezone'],'Europe/Berlin');
$out['title']=safeStr($out['title'],'Kiosk Dashboard');
$out['background']=is_array($out['background'])?$out['background']:[];
$out['background']['mode']=in_array(safeStr($out['background']['mode']??'rotate'),['rotate','static'],true)?safeStr($out['background']['mode']??'rotate'):'rotate';
$out['background']['fade_ms']=safeInt($out['background']['fade_ms']??700,700,0,5000);
$out['background']['items']=is_array($out['background']['items']??null)?$out['background']['items']:[];
$out['cards']=is_array($out['cards'])?$out['cards']:[];
$out['cards']['columns']=safeInt($out['cards']['columns']??3,3,1,6);
$out['cards']['slots']=safeInt($out['cards']['slots']??6,6,1,24);
$out['cards']['gap_px']=safeInt($out['cards']['gap_px']??14,14,0,80);
$out['cards']['border_radius_px']=safeInt($out['cards']['border_radius_px']??14,14,0,40);
$out['cards']['rotate']=safeBool($out['cards']['rotate']??true,true);
$out['cards']['default_duration']=safeInt($out['cards']['default_duration']??30,30,3,600);
$out['cards']['items']=is_array($out['cards']['items']??null)?$out['cards']['items']:[];
$out['display']=is_array($out['display'])?$out['display']:[];
$out['display']['show_clock']=safeBool($out['display']['show_clock']??true,true);
$out['display']['clock_format']=in_array(safeStr($out['display']['clock_format']??'24h'),['24h','12h'],true)?safeStr($out['display']['clock_format']??'24h'):'24h';
$out['display']['corner_badge']=safeStr($out['display']['corner_badge']??'','');
$out['display']['kiosk_hint']=safeStr($out['display']['kiosk_hint']??'','');
return $out;
}

function configToClient(array $cfg):array{
$cfg=normalizeConfig($cfg);
$bg=[];
foreach($cfg['background']['items'] as $it){
if(!is_array($it))continue;
$t=safeStr($it['type']??'image','image');
if(!in_array($t,['image','color'],true))continue;
$entry=['type'=>$t,'duration'=>safeInt($it['duration']??25,25,3,3600),'schedule'=>is_array($it['schedule']??null)?$it['schedule']:[]];
if($t==='image'){$u=safeUrl($it['src']??'');if($u==='')continue;$entry['src']=$u;}
if($t==='color'){$c=safeStr($it['color']??'#000','#000');$entry['color']=$c;}
$bg[]=$entry;
}
$cards=[];
foreach($cfg['cards']['items'] as $it){
if(!is_array($it))continue;
$t=safeStr($it['type']??'url','url');
if(!in_array($t,['url','image'],true))continue;
$entry=['type'=>$t,'title'=>safeStr($it['title']??'',''),'duration'=>safeInt($it['duration']??$cfg['cards']['default_duration'],$cfg['cards']['default_duration'],3,3600),'slot'=>safeInt($it['slot']??0,0,0,999),'schedule'=>is_array($it['schedule']??null)?$it['schedule']:[]];
if($t==='url'){$u=safeUrl($it['src']??'');if($u==='')continue;$entry['src']=$u;$entry['sandbox']=in_array(strtolower(safeStr($it['sandbox']??'on','on')),['on','off'],true)?strtolower(safeStr($it['sandbox']??'on','on')):'on';$entry['zoom']=is_numeric($it['zoom']??null)?max(0.25,min(2.0,(float)$it['zoom'])):1.0;}
if($t==='image'){$u=safeUrl($it['src']??'');if($u==='')continue;$entry['src']=$u;}
$cards[]=$entry;
}
return [
'meta'=>['generated_at'=>nowIso()],
'title'=>$cfg['title'],
'timezone'=>$cfg['timezone'],
'background'=>['mode'=>$cfg['background']['mode'],'fade_ms'=>$cfg['background']['fade_ms'],'items'=>$bg],
'cards'=>['columns'=>$cfg['cards']['columns'],'slots'=>$cfg['cards']['slots'],'gap_px'=>$cfg['cards']['gap_px'],'border_radius_px'=>$cfg['cards']['border_radius_px'],'rotate'=>$cfg['cards']['rotate'],'default_duration'=>$cfg['cards']['default_duration'],'items'=>$cards],
'display'=>$cfg['display']
];
}

ensureDataDir($DATA);
$loaded=loadYamlConfig($YAML_PRIMARY,$YAML_FALLBACK);
$yamlPath=$loaded['__path'];
if($loaded['__missing']===true){
$cfg=defaultConfig();
@file_put_contents($yamlPath,yamlDump($cfg)."\n");
$loaded=loadYamlConfig($YAML_PRIMARY,$YAML_FALLBACK);
$yamlPath=$loaded['__path'];
}
$cfgRaw=$loaded['__data'];
$cfg=normalizeConfig($cfgRaw);

$api=$_GET['api']??'';
if($api==='config'){
$client=configToClient($cfg);
$etag='"'.sha1(($yamlPath?@filemtime($yamlPath):0).':'.(@filesize($yamlPath)?:0)).'"';
if(($_SERVER['HTTP_IF_NONE_MATCH']??'')===$etag){http_response_code(304);exit;}
jsonOut($client,200,['ETag'=>$etag,'Cache-Control'=>'no-store, max-age=0']);
}
if($api==='save'){
if(($_SERVER['REQUEST_METHOD']??'GET')!=='POST')jsonOut(['ok'=>false,'error'=>'POST required'],405);
$token=safeStr($_GET['token']??'','');
if($token===''||!hash_equals($cfg['admin_token'],$token))jsonOut(['ok'=>false,'error'=>'invalid token'],403);
$ct=$_SERVER['CONTENT_TYPE']??'';if(stripos($ct,'application/x-www-form-urlencoded')===false&&stripos($ct,'multipart/form-data')===false){} 
$yamlText=(string)($_POST['yaml']??'');
if(trim($yamlText)==='')jsonOut(['ok'=>false,'error'=>'empty yaml'],400);
try{$parsed=yamlParse($yamlText);$norm=normalizeConfig($parsed);}catch(Throwable $e){jsonOut(['ok'=>false,'error'=>'YAML parse failed','detail'=>$e->getMessage()],400);}
$dump=yamlDump($norm)."\n";
$tmp=$yamlPath.'.tmp.'.bin2hex(random_bytes(4));
if(@file_put_contents($tmp,$dump,LOCK_EX)===false)jsonOut(['ok'=>false,'error'=>'write failed'],500);
@chmod($tmp,0600);
if(!@rename($tmp,$yamlPath)){@unlink($tmp);jsonOut(['ok'=>false,'error'=>'rename failed'],500);}
jsonOut(['ok'=>true,'path'=>basename($yamlPath)],200);
}

$mode=isDashRequest()?'dash':(isConfigRequest()?'config':'landing');
$csp="default-src 'self'; img-src * data:; style-src 'self' 'unsafe-inline'; script-src 'self' 'unsafe-inline'; frame-src *; connect-src 'self'; base-uri 'self'; form-action 'self'";
header("Content-Security-Policy: ".$csp);

if($mode==='landing'){
$dashUrl='?dash';
$configUrl='?config';
$info='Konfig-Datei: '.htmlspecialchars($yamlPath,ENT_QUOTES,'UTF-8');
$html='<!doctype html><html lang="de"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Dashboard</title><style>body{font-family:system-ui,-apple-system,Segoe UI,Roboto,Arial,sans-serif;margin:24px;background:#0b1220;color:#e7eefc}a{color:#7ab7ff}code{background:#121c33;padding:2px 6px;border-radius:6px}div.card{background:#0f1a31;border:1px solid #1d2a4b;border-radius:12px;padding:16px;max-width:820px}p{line-height:1.45}</style></head><body><div class="card"><h1>PHP Kiosk Dashboard</h1><p>'.$info.'</p><p><a href="'.$dashUrl.'">Dashboard öffnen</a> (für Kiosk: <code>index.php?dash</code> oder <code>index.php?=dash</code>)</p><p><a href="'.$configUrl.'">Konfig-Editor öffnen</a> (benötigt Token)</p><p>Hinweis: Setze <code>admin_token</code> in der YAML und öffne dann <code>?config&amp;token=DEIN_TOKEN</code>.</p></div></body></html>';
htmlOut($html);
}

if($mode==='config'){
$token=safeStr($_GET['token']??'','');
$authed=($token!==''&&hash_equals($cfg['admin_token'],$token));
$yamlText=is_file($yamlPath)?(string)file_get_contents($yamlPath):yamlDump($cfg)."\n";
$yamlEsc=htmlspecialchars($yamlText,ENT_QUOTES,'UTF-8');
$pathEsc=htmlspecialchars($yamlPath,ENT_QUOTES,'UTF-8');
$saveBtn=$authed?'<button id="save">Speichern</button>':'<button id="save" disabled>Speichern (Token fehlt/ungültig)</button>';
$tokenHint=$authed?'<span class="ok">Token ok</span>':'<span class="bad">Token fehlt/ungültig</span> — öffne: <code>?config&amp;token=DEIN_TOKEN</code>';
$html='<!doctype html><html lang="de"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Dashboard Konfiguration</title><style>body{font-family:system-ui,-apple-system,Segoe UI,Roboto,Arial,sans-serif;margin:16px;background:#0b1220;color:#e7eefc}a{color:#7ab7ff}textarea{width:100%;height:62vh;background:#0f1a31;color:#e7eefc;border:1px solid #23355f;border-radius:12px;padding:12px;font-family:ui-monospace,SFMono-Regular,Menlo,Consolas,monospace;font-size:13px;line-height:1.35;box-sizing:border-box}button{background:#2b78ff;color:#fff;border:0;border-radius:10px;padding:10px 14px;font-weight:600;cursor:pointer}button[disabled]{background:#27406f;color:#9bb0d6;cursor:not-allowed}div.top{display:flex;gap:12px;align-items:center;flex-wrap:wrap;margin-bottom:10px}div.box{background:#0f1a31;border:1px solid #1d2a4b;border-radius:12px;padding:12px}code{background:#121c33;padding:2px 6px;border-radius:6px}span.ok{color:#7dffb2}span.bad{color:#ff8b8b}pre{white-space:pre-wrap}#msg{margin-left:auto}</style></head><body><div class="top"><div class="box">Datei: <code>'.$pathEsc.'</code></div><div class="box">'.$tokenHint.'</div><div class="box"><a href="?dash">Dashboard</a></div><div id="msg"></div></div><textarea id="yaml">'.$yamlEsc.'</textarea><div class="top" style="margin-top:10px">'.$saveBtn.'<div class="box">Tipp: Ändere zuerst <code>admin_token</code> auf einen langen Wert.</div></div><script>
const authed='.($authed?'true':'false').';
const token='.json_encode($token,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES).';
const msg=document.getElementById("msg");
function setMsg(t,ok){msg.innerHTML="<div class=\\"box\\" style=\\"border-color:"+(ok?"#2d8a5a":"#8a2d2d")+"\\">"+t+"</div>";}
document.getElementById("save").addEventListener("click",async()=>{
if(!authed){setMsg("Kein gültiger Token.",false);return;}
const yaml=document.getElementById("yaml").value;
setMsg("Speichere…",true);
const fd=new FormData();fd.append("yaml",yaml);
const r=await fetch("?api=save&token="+encodeURIComponent(token),{method:"POST",body:fd,credentials:"same-origin"});
const j=await r.json().catch(()=>null);
if(!j||!j.ok){setMsg("Fehler: "+(j&&j.error?j.error:"unknown"),false);return;}
setMsg("Gespeichert: "+(j.path||""),true);
});
</script></body></html>';
htmlOut($html);
}

$dashHtml='<!doctype html><html lang="de"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><meta http-equiv="refresh" content="0"><title>'.htmlspecialchars($cfg['title'],ENT_QUOTES,'UTF-8').'</title><style>
html,body{height:100%;margin:0;background:#000;overflow:hidden;font-family:system-ui,-apple-system,Segoe UI,Roboto,Arial,sans-serif}
#bgA,#bgB{position:fixed;inset:0;background-position:center;background-size:cover;opacity:0;transition:opacity 700ms linear}
#bgA.show,#bgB.show{opacity:1}
#overlay{position:fixed;inset:0;display:flex;flex-direction:column;gap:10px;padding:14px;box-sizing:border-box}
#topbar{display:flex;align-items:center;justify-content:space-between;gap:12px;color:#e7eefc;text-shadow:0 2px 12px rgba(0,0,0,.65)}
#title{font-size:20px;font-weight:700;letter-spacing:.2px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:65vw}
#right{display:flex;gap:12px;align-items:center}
#clock{font-size:18px;font-weight:700}
#badge{font-size:13px;padding:6px 10px;border-radius:999px;background:rgba(0,0,0,.35);border:1px solid rgba(255,255,255,.15)}
#hint{font-size:12px;opacity:.85}
#grid{flex:1;display:grid;gap:14px}
.card{position:relative;border-radius:14px;overflow:hidden;background:rgba(10,16,28,.55);border:1px solid rgba(255,255,255,.14);backdrop-filter:blur(6px)}
.cardHeader{position:absolute;left:0;right:0;top:0;padding:8px 10px;font-size:13px;font-weight:700;color:#f0f6ff;background:linear-gradient(to bottom,rgba(0,0,0,.62),rgba(0,0,0,0));text-shadow:0 2px 12px rgba(0,0,0,.9);pointer-events:none}
.cardBody{position:absolute;inset:0}
.cardBody iframe{width:100%;height:100%;border:0;background:#000}
.cardBody img{width:100%;height:100%;object-fit:cover;display:block}
.cardEmpty{display:flex;align-items:center;justify-content:center;height:100%;color:rgba(231,238,252,.7);font-size:13px}
@media (max-width:1100px){#title{max-width:55vw}}
</style></head><body>
<div id="bgA"></div><div id="bgB"></div>
<div id="overlay">
<div id="topbar">
<div id="title"></div>
<div id="right">
<div id="hint"></div>
<div id="badge" style="display:none"></div>
<div id="clock" style="display:none"></div>
</div>
</div>
<div id="grid"></div>
</div>
<script>
const state={cfg:null,tz:"Europe/Berlin",bgIndex:0,bgUseA:true,slots:[],slotTimers:[],bgTimer:null,clockTimer:null};
const el={bgA:document.getElementById("bgA"),bgB:document.getElementById("bgB"),grid:document.getElementById("grid"),title:document.getElementById("title"),clock:document.getElementById("clock"),badge:document.getElementById("badge"),hint:document.getElementById("hint")};

function partsInTz(tz){
const d=new Date();
const fmt=new Intl.DateTimeFormat("en-GB",{timeZone:tz,weekday:"short",hour:"2-digit",minute:"2-digit",second:"2-digit",hour12:false});
const parts=fmt.formatToParts(d);
const o={};for(const p of parts)o[p.type]=p.value;
return {weekday:o.weekday,hh:parseInt(o.hour,10),mm:parseInt(o.minute,10),ss:parseInt(o.second,10)};
}
function weekdayToKey(w){
const m={Mon:"Mon",Tue:"Tue",Wed:"Wed",Thu:"Thu",Fri:"Fri",Sat:"Sat",Sun:"Sun"};
return m[w]||w;
}
function parseHm(s){
if(!s||typeof s!=="string")return null;
const m=s.match(/^(\d{1,2}):(\d{2})$/);if(!m)return null;
const h=Math.max(0,Math.min(23,parseInt(m[1],10))),mi=Math.max(0,Math.min(59,parseInt(m[2],10)));
return h*60+mi;
}
function inSchedule(item){
const sched=item.schedule||[];
if(!Array.isArray(sched)||sched.length===0)return true;
const p=partsInTz(state.tz);
const wd=weekdayToKey(p.weekday);
const now=p.hh*60+p.mm;
for(const r of sched){
if(!r||typeof r!=="object")continue;
const days=Array.isArray(r.days)?r.days:null;
if(days&&days.length&&days.indexOf(wd)===-1)continue;
const f=parseHm(r.from||"00:00");const t=parseHm(r.to||"23:59");
if(f===null||t===null)continue;
if(f<=t){if(now>=f&&now<=t)return true;}
else{if(now>=f||now<=t)return true;}
}
return false;
}
function pickActive(list,startIndex){
if(!Array.isArray(list)||list.length===0)return null;
for(let i=0;i<list.length;i++){
const idx=(startIndex+i)%list.length;
const it=list[idx];
if(inSchedule(it))return {it,idx};
}
return null;
}
function setBg(item,showA){
const elShow=showA?el.bgA:el.bgB;
const elHide=showA?el.bgB:el.bgA;
elShow.style.transitionDuration=(state.cfg.background.fade_ms||700)+"ms";
elHide.style.transitionDuration=(state.cfg.background.fade_ms||700)+"ms";
if(item.type==="image"){elShow.style.backgroundImage="url('"+item.src.replace(/'/g,"%27")+"')";elShow.style.backgroundColor="#000";}
else{elShow.style.backgroundImage="none";elShow.style.backgroundColor=item.color||"#000";}
elShow.classList.add("show");
elHide.classList.remove("show");
}
function scheduleBgNext(){
const items=state.cfg.background.items||[];
const found=pickActive(items,state.bgIndex);
const item=found?found.it:null;
if(!item){setBg({type:"color",color:"#000"},state.bgUseA);state.bgUseA=!state.bgUseA;state.bgTimer=setTimeout(scheduleBgNext,10000);return;}
state.bgIndex=(found.idx+1)%items.length;
setBg(item,state.bgUseA);
state.bgUseA=!state.bgUseA;
state.bgTimer=setTimeout(scheduleBgNext,Math.max(3000,(item.duration||25)*1000));
}
function buildGrid(){
const c=state.cfg.cards;
const cols=Math.max(1,Math.min(6,c.columns||3));
const slots=Math.max(1,Math.min(24,c.slots||6));
el.grid.style.gap=(c.gap_px||14)+"px";
el.grid.style.gridTemplateColumns="repeat("+cols+", minmax(0, 1fr))";
const radius=(c.border_radius_px||14)+"px";
el.grid.innerHTML="";
state.slots=[];
for(let s=1;s<=slots;s++){
const wrap=document.createElement("div");
wrap.className="card";
wrap.style.borderRadius=radius;
wrap.dataset.slot=String(s);
const head=document.createElement("div");
head.className="cardHeader";
head.textContent="";
const body=document.createElement("div");
body.className="cardBody";
wrap.appendChild(head);wrap.appendChild(body);
el.grid.appendChild(wrap);
state.slots.push({slot:s,wrap,head,body,index:0,playlist:[]});
}
const items=Array.isArray(c.items)?c.items:[];
const bySlot=new Map();
for(const it of items){
const sl=(it.slot|0);
if(sl>=1&&sl<=slots){if(!bySlot.has(sl))bySlot.set(sl,[]);bySlot.get(sl).push(it);}
}
let rr=1;
for(const it of items){
const sl=(it.slot|0);
if(sl>=1&&sl<=slots)continue;
while(rr<=slots&&bySlot.has(rr)&&bySlot.get(rr).length>=50)rr++;
if(rr>slots)rr=1;
if(!bySlot.has(rr))bySlot.set(rr,[]);
bySlot.get(rr).push(it);
rr++;if(rr>slots)rr=1;
}
for(const s of state.slots)s.playlist=bySlot.get(s.slot)||[];
}
function renderSlot(slotObj,item){
slotObj.head.textContent=item.title||"";
slotObj.body.innerHTML="";
if(item.type==="image"){
const img=document.createElement("img");
img.alt=item.title||"";
img.src=item.src;
slotObj.body.appendChild(img);
return;
}
if(item.type==="url"){
const iframe=document.createElement("iframe");
iframe.src=item.src;
iframe.loading="lazy";
iframe.referrerPolicy="no-referrer";
if((item.sandbox||"on")==="on")iframe.setAttribute("sandbox","allow-same-origin allow-scripts allow-forms allow-popups");
slotObj.body.appendChild(iframe);
const z=(typeof item.zoom==="number")?item.zoom:1.0;
if(z!==1.0){
iframe.style.transformOrigin="0 0";
iframe.style.transform="scale("+z+")";
iframe.style.width=(100/z)+"%";
iframe.style.height=(100/z)+"%";
}
return;
}
slotObj.body.innerHTML='<div class="cardEmpty">Nicht unterstützt</div>';
}
function slotTick(slotObj){
const list=slotObj.playlist||[];
const found=pickActive(list,slotObj.index);
if(!found){
slotObj.head.textContent="";
slotObj.body.innerHTML='<div class="cardEmpty">Keine aktive Karte (Schedule)</div>';
slotObj.index=0;
state.slotTimers[slotObj.slot]=setTimeout(()=>slotTick(slotObj),10000);
return;
}
slotObj.index=(found.idx+1)%list.length;
renderSlot(slotObj,found.it);
const dur=Math.max(3,(found.it.duration||state.cfg.cards.default_duration||30))*1000;
state.slotTimers[slotObj.slot]=setTimeout(()=>slotTick(slotObj),dur);
}
function updateClock(){
const p=partsInTz(state.tz);
const fmt=state.cfg.display.clock_format==="12h"?"en-US":"de-DE";
const d=new Date();
const t=new Intl.DateTimeFormat(fmt,{timeZone:state.tz,hour:"2-digit",minute:"2-digit",second:"2-digit",hour12:state.cfg.display.clock_format==="12h"}).format(d);
el.clock.textContent=t;
}
async function loadCfg(){
const r=await fetch("?api=config",{cache:"no-store",credentials:"same-origin"});
const cfg=await r.json();
state.cfg=cfg;
state.tz=cfg.timezone||"Europe/Berlin";
el.title.textContent=cfg.title||"Dashboard";
if(cfg.display&&cfg.display.corner_badge){el.badge.style.display="inline-block";el.badge.textContent=cfg.display.corner_badge;}else el.badge.style.display="none";
if(cfg.display&&cfg.display.kiosk_hint){el.hint.textContent=cfg.display.kiosk_hint;}else el.hint.textContent="";
if(cfg.display&&cfg.display.show_clock){el.clock.style.display="block";updateClock();if(state.clockTimer)clearInterval(state.clockTimer);state.clockTimer=setInterval(updateClock,1000);}else el.clock.style.display="none";
buildGrid();
for(const s of state.slots){if(state.slotTimers[s.slot])clearTimeout(state.slotTimers[s.slot]);slotTick(s);}
if(state.bgTimer)clearTimeout(state.bgTimer);
scheduleBgNext();
}
loadCfg().catch(()=>{document.body.style.background="#000";});
setInterval(()=>{loadCfg().catch(()=>{});},60000);
</script></body></html>';
htmlOut($dashHtml);
