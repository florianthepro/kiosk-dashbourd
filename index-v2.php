<?php
declare(strict_types=1);
header("Content-Type:text/html; charset=utf-8");
header("X-Content-Type-Options: nosniff");
header("Referrer-Policy: no-referrer");
header("Permissions-Policy: geolocation=(), microphone=(), camera=()");
$allow_private_fetch=false;
function deep_defaults($in,$def){if(!is_array($in))return $def;foreach($def as $k=>$v){if(!array_key_exists($k,$in)){$in[$k]=$v;continue;}if(is_array($v))$in[$k]=deep_defaults($in[$k],$v);}return $in;}
$default=["meta"=>["timezone"=>"Europe/Berlin","autosync_hours"=>6,"globalRotationSpeed"=>1],"pages"=>[["id"=>"p1","name"=>"Page 1","thumb"=>"","settings"=>["w"=>3840,"h"=>2160],"background"=>["fade"=>1,"items"=>[]],"widgets"=>[]]],"schedules"=>[]];
function sanitize_data($d,$default){
$d=deep_defaults($d,$default);
if(!is_array($d["pages"]))$d["pages"]=$default["pages"];
if(!is_array($d["schedules"]))$d["schedules"]=[];
if(!isset($d["meta"])||!is_array($d["meta"]))$d["meta"]=$default["meta"];
$d["pages"]=array_values(array_filter($d["pages"],fn($p)=>is_array($p)&&isset($p["id"])&&isset($p["name"])));
if(!count($d["pages"]))$d["pages"]=$default["pages"];
foreach($d["pages"] as &$p){
$p=deep_defaults($p,$default["pages"][0]);
if(!is_array($p["settings"]))$p["settings"]=["w"=>3840,"h"=>2160];
if(!is_array($p["background"]))$p["background"]=["fade"=>1,"items"=>[]];
if(!is_array($p["background"]["items"]??null))$p["background"]["items"]=[];
if(!is_array($p["widgets"]))$p["widgets"]=[];
$p["widgets"]=array_values(array_filter($p["widgets"],fn($w)=>is_array($w)&&isset($w["id"])&&isset($w["type"])));
foreach($p["widgets"] as &$w){
$w=deep_defaults($w,["id"=>$w["id"]??("w".bin2hex(random_bytes(4))),"type"=>$w["type"]??"text","x"=>40,"y"=>40,"w"=>320,"h"=>180,"z"=>1,"locked"=>false,"hidden"=>false,"contentW"=>3840,"contentH"=>2160,"autoContent"=>false,"lockAspect"=>false,"style"=>["radius"=>0,"shadow"=>false,"bg"=>"","color"=>"","font"=>16],"src"=>"","mode"=>"iframe","text"=>"","format"=>"HH:mm:ss","playlist"=>[],"duration"=>5,"title"=>""]);
if(!isset($w["contentW"])||!is_numeric($w["contentW"]))$w["contentW"]=3840;
if(!isset($w["contentH"])||!is_numeric($w["contentH"]))$w["contentH"]=2160;
if(!is_array($w["style"]))$w["style"]=["radius"=>0,"shadow"=>false,"bg"=>"","color"=>"","font"=>16];
if(!is_array($w["playlist"]))$w["playlist"]=[];
}
}
foreach($d["schedules"] as &$r){
$r=deep_defaults($r,["id"=>$r["id"]??("r".bin2hex(random_bytes(4))),"name"=>$r["name"]??"Rule","enabled"=>$r["enabled"]??true,"from"=>$r["from"]??"08:00","to"=>$r["to"]??"18:00","weekdays"=>$r["weekdays"]??[1,2,3,4,5],"page"=>$r["page"]??"","override"=>$r["override"]??["rotationSpeed"=>null,"background"=>null,"widgets"=>[]]]);
if(!is_array($r["weekdays"]))$r["weekdays"]=[1,2,3,4,5];
if(!is_array($r["override"]))$r["override"]=["rotationSpeed"=>null,"background"=>null,"widgets"=>[]];
if(!isset($r["override"]["widgets"])||!(is_array($r["override"]["widgets"])||is_object($r["override"]["widgets"])))$r["override"]["widgets"]=[];
}
return $d;
}
function json_path(){
$p1=__DIR__."/data/data.json";
$p2=__DIR__."/data.json";
if(file_exists($p1))return $p1;
return $p2;
}
function checkHostSafe($host,$allow_private_fetch){
$ips=@gethostbynamel($host);
if(!$ips)return false;
if($allow_private_fetch)return true;
foreach($ips as $ip){
if(!filter_var($ip,FILTER_VALIDATE_IP,FILTER_FLAG_NO_PRIV_RANGE|FILTER_FLAG_NO_RES_RANGE))return false;
}
return true;
}
if(($_GET["action"]??"")==="fetchHtml"){
$url=trim($_GET["url"]??"");
if($url===""||!filter_var($url,FILTER_VALIDATE_URL)){http_response_code(400);exit;}
$u=parse_url($url);$scheme=strtolower($u["scheme"]??"");$host=$u["host"]??"";
if(($scheme!=="http"&&$scheme!=="https")||$host===""){http_response_code(400);exit;}
if(!checkHostSafe($host,$allow_private_fetch)){http_response_code(403);exit;}
$max=2*1024*1024;$buf="";
$ch=curl_init($url);
curl_setopt_array($ch,[CURLOPT_RETURNTRANSFER=>false,CURLOPT_FOLLOWLOCATION=>true,CURLOPT_MAXREDIRS=>3,CURLOPT_CONNECTTIMEOUT=>4,CURLOPT_TIMEOUT=>12,CURLOPT_USERAGENT=>"DashboardPlayer/1.0",CURLOPT_HTTPHEADER=>["Accept: text/html,application/xhtml+xml"],CURLOPT_WRITEFUNCTION=>function($ch,$data)use(&$buf,$max){$buf.=$data;return strlen($buf)>$max?0:strlen($data);}]);
$ok=curl_exec($ch);
$eff=curl_getinfo($ch,CURLINFO_EFFECTIVE_URL);
$code=(int)curl_getinfo($ch,CURLINFO_RESPONSE_CODE);
curl_close($ch);
if(!$ok||$code>=400){http_response_code(502);echo"fetch failed";exit;}
if($eff){
$uu=parse_url($eff);$h=$uu["host"]??"";
if($h!==""&&!checkHostSafe($h,$allow_private_fetch)){http_response_code(403);exit;}
}
$path2=$u["path"]??"/";$port=isset($u["port"])?":".$u["port"]:"";
$base=$scheme."://".$host.$port.rtrim(str_replace("\\","/",dirname($path2)),"/")."/";
if(stripos($buf,"<base")===false){
$baseTag='<base href="'.htmlspecialchars($base,ENT_QUOTES|ENT_SUBSTITUTE,"UTF-8").'">';
if(preg_match('/<head[^>]*>/i',$buf,$m,PREG_OFFSET_CAPTURE)){
$pos=$m[0][1]+strlen($m[0][0]);
$buf=substr($buf,0,$pos).$baseTag.substr($buf,$pos);
}else{$buf='<!doctype html><html><head><meta charset="utf-8">'.$baseTag.'</head><body>'.$buf.'</body></html>';}
}
header("Content-Type:text/html; charset=utf-8");
echo $buf;
exit;
}
$path=json_path();
$data=$default;
if(file_exists($path)){
$j=json_decode((string)file_get_contents($path),true);
if($j)$data=sanitize_data($j,$default);
}
$etag='"'.sha1(json_encode($data)).'"';
header("ETag: ".$etag);
?>
<!doctype html><html><head><meta charset=utf-8><meta name=viewport content="width=device-width,initial-scale=1"><?php $t=(string)($data["pages"][0]["name"]??""); if($t!==""){ ?><title><?php echo htmlspecialchars($t,ENT_QUOTES|ENT_SUBSTITUTE,"UTF-8"); ?></title><?php } ?><style>
html,body{margin:0;height:100%;background:#000;overflow:hidden;font-family:system-ui,Segoe UI,Arial}
#wrap{position:fixed;inset:0;display:flex;align-items:center;justify-content:center;background:#000}
#stageScale{position:relative}
#stage{position:relative;transform-origin:0 0;background:#111827;border:0}
#bg{position:absolute;inset:0;overflow:hidden}
.bgLayer{position:absolute;inset:0;background:#000 center/cover no-repeat;opacity:0;transition:opacity 1s}
.bgLayer.on{opacity:1}
#w{position:absolute;inset:0}
.widget{position:absolute;box-sizing:border-box;overflow:hidden}
.widget.hidden{display:none}
.widget .inner{position:absolute;inset:0}
.widget.text .inner{white-space:pre-wrap;padding:10px}
.widget.clock .inner{display:flex;align-items:center;justify-content:center}
.widget.image img{width:100%;height:100%;object-fit:cover;display:block}
.widget.url .frameWrap{position:absolute;left:0;top:0;transform-origin:0 0}
.widget.url iframe{border:0;display:block;background:#fff}
.widget.carousel .inner{position:absolute;inset:0}
.widget.carousel iframe,.widget.carousel img{width:100%;height:100%;border:0;display:block}
</style></head><body><div id=wrap><div id=stageScale><div id=stage><div id=bg><div class="bgLayer" id=bgA></div><div class="bgLayer" id=bgB></div></div><div id=w></div></div></div></div><script>
const dataInitial=<?php echo json_encode($data,JSON_UNESCAPED_UNICODE); ?>;
let data=dataInitial;
let viewScale=1;
let stageW=3840,stageH=2160;
let pageIdx=0;
let pageList=[];
let activeRuleByPage=new Map();
let bgState={sig:"",i:0,nextAt:0,onA:true,pageId:""};
let carState={};
const elWrap=document.getElementById("wrap");
const elStage=document.getElementById("stage");
const elStageScale=document.getElementById("stageScale");
const elW=document.getElementById("w");
const elBgA=document.getElementById("bgA");
const elBgB=document.getElementById("bgB");
const clamp=(v,a,b)=>Math.max(a,Math.min(b,v));
const deepClone=o=>JSON.parse(JSON.stringify(o));
function parseHM(s){s=String(s||"").trim();let m=s.match(/^(\d{1,2}):(\d{2})$/);if(!m)return null;let h=+m[1],mi=+m[2];if(h<0||h>23||mi<0||mi>59)return null;return h*60+mi;}
function nowInRange(from,to,mins){if(from==null||to==null)return true;if(from===to)return true;return from<to?(mins>=from&&mins<to):(mins>=from||mins<to);}
function matchRule(r,now){if(!r||!r.enabled)return false;let wd=now.getDay();wd=wd===0?7:wd;let wds=Array.isArray(r.weekdays)?r.weekdays:[];if(wds.length&&wds.indexOf(wd)===-1)return false;let mins=now.getHours()*60+now.getMinutes();let f=parseHM(r.from),t=parseHM(r.to);return nowInRange(f,t,mins);}
function computeSchedule(){activeRuleByPage=new Map();let now=new Date();let rules=Array.isArray(data.schedules)?data.schedules:[];let activePages=[];for(let r of rules){if(!r||!r.page)continue;if(matchRule(r,now)){if(!activeRuleByPage.has(r.page))activeRuleByPage.set(r.page,r);if(activePages.indexOf(r.page)===-1)activePages.push(r.page);}}if(activePages.length){pageList=activePages.map(pid=>data.pages.find(p=>p.id===pid)).filter(Boolean);}else{pageList=(Array.isArray(data.pages)?data.pages:[]).slice();}if(!pageList.length)pageList=(Array.isArray(data.pages)?data.pages:[]).slice(0,1);if(pageIdx>=pageList.length)pageIdx=0;}
function applyView(){let cw=stageW,ch=stageH;let maxW=Math.max(100,window.innerWidth),maxH=Math.max(100,window.innerHeight);let s=Math.min(maxW/cw,maxH/ch);s=clamp(s,0.05,10);viewScale=s;elStage.style.width=cw+"px";elStage.style.height=ch+"px";elStage.style.transform="scale("+s+")";elStageScale.style.width=Math.round(cw*s)+"px";elStageScale.style.height=Math.round(ch*s)+"px";}
window.addEventListener("resize",()=>applyView());
function styleWidgetDiv(d,w){let s=w.style||{};d.style.left=(w.x||0)+"px";d.style.top=(w.y||0)+"px";d.style.width=(w.w||100)+"px";d.style.height=(w.h||80)+"px";d.style.zIndex=(w.z||1);d.style.borderRadius=((+s.radius||0))+"px";d.style.boxShadow=s.shadow?"0 6px 18px rgba(0,0,0,.35)":"none";d.style.background=(s.bg||"transparent");d.style.color=(s.color||"#fff");d.style.fontSize=((+s.font||16))+"px";d.classList.toggle("hidden",!!w.hidden);}
function mergeWidgetOverrides(w,ov){if(!ov)return w;let out=deepClone(w);for(let k in ov){if(k==="style"&&typeof ov.style==="object"&&ov.style){out.style=Object.assign({},out.style||{},ov.style);}else if(k!=="id"){out[k]=ov[k];}}return out;}
function mapOverrides(ov){let m=new Map();if(!ov)return m;if(Array.isArray(ov)){for(let it of ov){if(it&&it.id)m.set(it.id,it);}return m;}if(typeof ov==="object"){for(let k in ov){let it=ov[k];if(it&&typeof it==="object"){if(!it.id)it.id=k;m.set(it.id,it);}}}return m;}
function effectivePage(p){let out=deepClone(p);let r=activeRuleByPage.get(p.id)||null;if(r&&r.override){let o=r.override||{};if(o.background&&typeof o.background==="object")out.background=deepClone(o.background);let wm=mapOverrides(o.widgets);out.widgets=(out.widgets||[]).map(w=>wm.has(w.id)?mergeWidgetOverrides(w,wm.get(w.id)):w);}return out;}
function proxyFetchUrl(u){return location.pathname+"?action=fetchHtml&url="+encodeURIComponent(u)+"&ts="+Date.now();}
function renderUrlInto(container,w){let url=(w.src||"").trim();let mode=(w.mode||"iframe")==="fetch"?"fetch":"iframe";let cw=+w.contentW||stageW;let ch=+w.contentH||stageH;let frameWrap=document.createElement("div");frameWrap.className="frameWrap";frameWrap.style.width=cw+"px";frameWrap.style.height=ch+"px";let sc=Math.min((w.w||1)/cw,(w.h||1)/ch);sc=clamp(sc,0.01,10);frameWrap.style.transform="scale("+sc+")";let fr=document.createElement("iframe");fr.width=cw;fr.height=ch;fr.style.width=cw+"px";fr.style.height=ch+"px";fr.style.border="0";fr.allow="fullscreen";fr.loading="eager";if(mode==="fetch")fr.setAttribute("sandbox","allow-scripts allow-forms allow-popups allow-same-origin allow-popups-to-escape-sandbox allow-top-navigation-by-user-activation");fr.src=(mode==="fetch")?proxyFetchUrl(url):url;frameWrap.appendChild(fr);container.appendChild(frameWrap);}
function renderCarouselItem(box,it){box.innerHTML="";let t=(it&&it.type)||"image";if(t==="image"){let img=document.createElement("img");img.src=(it&&it.src)||"";box.appendChild(img);}else{let fr=document.createElement("iframe");fr.src=(it&&it.src)||"";fr.style.border="0";box.appendChild(fr);}}
function fmtTime(d,fmt,tz){fmt=String(fmt||"HH:mm:ss");let parts=new Intl.DateTimeFormat("en-GB",{timeZone:tz,hour:"2-digit",minute:"2-digit",second:"2-digit",year:"numeric",month:"2-digit",day:"2-digit"}).formatToParts(d).reduce((a,p)=>(a[p.type]=p.value,a),{});let HH=parts.hour||"00",mm=parts.minute||"00",ss=parts.second||"00",YYYY=parts.year||"0000",MM=parts.month||"00",DD=parts.day||"00";return fmt.replace(/YYYY/g,YYYY).replace(/MM/g,MM).replace(/DD/g,DD).replace(/HH/g,HH).replace(/mm/g,mm).replace(/ss/g,ss);}
function renderPageNow(){computeSchedule();let p=pageList[pageIdx]||pageList[0]||data.pages[0];if(!p)return;let ep=effectivePage(p);stageW=+((ep.settings&&ep.settings.w)||3840)||3840;stageH=+((ep.settings&&ep.settings.h)||2160)||2160;applyView();document.title=String(ep.name||"");renderBackground(ep);renderWidgets(ep);}
function renderBackground(p){let items=(p.background&&Array.isArray(p.background.items))?p.background.items:[];let fade=clamp(+((p.background&&p.background.fade)||0),0,20);elBgA.style.transitionDuration=fade+"s";elBgB.style.transitionDuration=fade+"s";let sig=items.map(it=>(it.src||"")+"|"+(+it.duration||0)).join("||")+"|f"+fade+"|p"+p.id;if(bgState.sig!==sig)bgState={sig:sig,i:0,nextAt:0,onA:true,pageId:p.id};tickBg(p);}
function tickBg(p){let items=(p.background&&Array.isArray(p.background.items))?p.background.items:[];if(!items.length){elBgA.classList.remove("on");elBgB.classList.remove("on");return;}let now=Date.now();let fade=clamp(+((p.background&&p.background.fade)||0),0,20);elBgA.style.transitionDuration=fade+"s";elBgB.style.transitionDuration=fade+"s";if(bgState.nextAt===0){let it=items[0]||{};let src=(it.src||"").trim();let show=bgState.onA?elBgA:elBgB;let hide=bgState.onA?elBgB:elBgA;if(src)show.style.backgroundImage="url('"+src.replace(/'/g,"%27")+"')";show.classList.add("on");hide.classList.remove("on");bgState.nextAt=now+clamp(+it.duration||10,1,3600)*1000;return;}if(now<bgState.nextAt)return;bgState.i=(bgState.i+1)%items.length;bgState.onA=!bgState.onA;let it=items[bgState.i]||{};let src=(it.src||"").trim();let show=bgState.onA?elBgA:elBgB;let hide=bgState.onA?elBgB:elBgA;if(src)show.style.backgroundImage="url('"+src.replace(/'/g,"%27")+"')";show.classList.add("on");hide.classList.remove("on");bgState.nextAt=now+clamp(+it.duration||10,1,3600)*1000;}
function renderWidgets(p){elW.innerHTML="";carState={};let tz=(data.meta&&data.meta.timezone)||"Europe/Berlin";for(let w of (p.widgets||[])){let d=document.createElement("div");d.className="widget "+String(w.type||"");styleWidgetDiv(d,w);let inner=document.createElement("div");inner.className="inner";d.appendChild(inner);if(w.type==="text"){d.classList.add("text");inner.textContent=String(w.text||"");}else if(w.type==="image"){d.classList.add("image");let img=document.createElement("img");img.src=String(w.src||"");inner.appendChild(img);}else if(w.type==="clock"){d.classList.add("clock");inner.textContent=fmtTime(new Date(),w.format||"HH:mm:ss",tz);inner.dataset.fmt=String(w.format||"HH:mm:ss");}else if(w.type==="url"){d.classList.add("url");let url=(w.src||"").trim();if(!url){inner.textContent="no url";}else{renderUrlInto(inner,w);}}else if(w.type==="carousel"){d.classList.add("carousel");let box=document.createElement("div");box.className="inner";d.innerHTML="";d.appendChild(box);let list=Array.isArray(w.playlist)?w.playlist:[];if(!list.length){box.textContent="empty";}else{carState[w.id]={i:0,nextAt:0,list:list};renderCarouselItem(box,list[0]);}}else{inner.textContent=String(w.type||"widget");}elW.appendChild(d);}}
function tickCarousels(){let now=Date.now();for(let wid in carState){let st=carState[wid];if(!st||!st.list||!st.list.length)continue;let el=document.querySelector('.widget.carousel');let nodes=document.querySelectorAll('.widget.carousel');for(let n of nodes){if(n&&n.querySelector&&n.querySelector(".inner")){let box=n.querySelector(".inner");if(!box)continue;if(st.nextAt===0){st.nextAt=now+clamp(+((st.list[st.i]||{}).duration||5),1,3600)*1000;}if(now<st.nextAt)continue;st.i=(st.i+1)%st.list.length;renderCarouselItem(box,st.list[st.i]);st.nextAt=now+clamp(+((st.list[st.i]||{}).duration||5),1,3600)*1000;}}}}
function tickClocks(){let tz=(data.meta&&data.meta.timezone)||"Europe/Berlin";let now=new Date();document.querySelectorAll('.widget.clock .inner').forEach(n=>{let fmt=n.dataset.fmt||"HH:mm:ss";n.textContent=fmtTime(now,fmt,tz);});}
function rotationSeconds(){let base=10;let sp=+(data.meta&&data.meta.globalRotationSpeed||1)||1;sp=clamp(sp,0.1,100);return clamp(base/sp,1,3600);}
let nextPageAt=0;
function tickRotation(){let now=Date.now();if(nextPageAt===0)nextPageAt=now+rotationSeconds()*1000;if(now<nextPageAt)return;computeSchedule();pageIdx=(pageIdx+1)%Math.max(1,pageList.length);renderPageNow();nextPageAt=now+rotationSeconds()*1000;}
async function maybeReload(){try{let r=await fetch("<?php echo basename($path); ?>?ts="+Date.now(),{cache:"no-store"});if(!r.ok)return;let j=await r.json();let s=JSON.stringify(j);let s2=JSON.stringify(data);if(s!==s2){data=j;renderPageNow();}}catch(e){}}
function start(){computeSchedule();renderPageNow();setInterval(()=>{let p=pageList[pageIdx]||pageList[0]||data.pages[0];if(p)tickBg(effectivePage(p));tickClocks();tickCarousels();tickRotation();},250);let ah=+((data.meta&&data.meta.autosync_hours)||6)||6;let iv=clamp(ah*3600*1000,30000,24*3600*1000);setInterval(()=>maybeReload(),iv);}
start();
</script>
</body>
</html>
