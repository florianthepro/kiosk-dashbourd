<?php):array{
$l=file($f,FILE_IGNORE_NEW_LINES);
$d=[];$s=[&$d];$i=[0];
foreach($l as $r){
if($r===''||$r[0]==='#')continue;
$sp=strlen($r)-strlen(ltrim($r,' '));
$r=trim($r);
while($sp<end($i)){array_pop($s);array_pop($i);}
if(substr($r,-1)===':'){
$k=substr($r,0,-1);
$s[array_key_last($s)][$k]=[];
$s[]=&$s[array_key_last($s)][$k];
$i[]=$sp+2;
}elseif(str_starts_with($r,'- ')){
$v=substr($r,2);
$s[array_key_last($s)][]=[];
$s[]=&$s[array_key_last($s)][array_key_last($s[array_key_last($s)])];
$i[]=$sp+2;
if(str_contains($v,':')){
[$k,$val]=array_map('trim',explode(':',$v,2));
$s[array_key_last($s)][$k]=$val;
}
}else{
[$k,$v]=array_map('trim',explode(':',$r,2));
$s[array_key_last($s)][$k]=$v;
}}
return $d;}
function active(array $c):bool{
$n=date('H:i');
if(isset($c['from'])&&$n<$c['from'])return false;
if(isset($c['to'])&&$n>$c['to'])return false;
if(isset($c['days'])&&!in_array(date('N'),array_map('trim',explode(',',$c['days']))))return false;
return true;}
$cfg=file_exists(__DIR__.'/dashboard.yaml')?yamlParse(__DIR__.'/dashboard.yaml'):[];
if(($_GET['']??'')!=='dash'){
header('Content-Type:text/plain;charset=utf-8');
print_r($cfg);
exit;}
$bg=$cfg['background']??[];
$cards=array_values(array_filter($cfg['cards']??[],fn($c)=>active($c)));
?><!doctype html><html><head><meta charset=utf-8><meta name=viewport content="width=device-width,height=device-height,initial-scale=1">
<style>
html,body{margin:0;width:100%;height:100%;overflow:hidden;background:#000}
#bg,#card{position:absolute;top:0;left:0;width:100%;height:100%;border:0}
</style></head><body>
<?php
if(($bg['type']??'')==='image')echo"<img id=bg src=\"{$bg['src']}\" style=\"object-fit:cover\">";
if(($bg['type']??'')==='web')echo"<iframe id=bg src=\"{$bg['src']}\"></iframe>";
?>
<iframe id=card></iframe>
<script>
const cards=<?php echo json_encode($cards,JSON_UNESCAPED_SLASHES); ?>;
let i=0;
function show(){
if(!cards.length)return;
const c=cards[i%cards.length];
const f=document.getElementById('card');
if(c.type==='image'){
f.src='data:text/html,<style>html,body{margin:0;background:black}img{width:100%;height:100%;object-fit:contain}</style><img src="'+c.src+'">';
}else f.src=c.src;
setTimeout(()=>{i++;show();},(parseInt(c.duration)||10)*1000);
}
show();
</script></body></html>
``
declare(strict_types=1);
date_default_timezone_set('Europe/Berlin');
