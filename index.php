<?php];$indent=[0];
foreach($lines as $l){
if(trim($l)===''||str_starts_with(trim($l),'#'))continue;
$i=strlen($l)-strlen(ltrim($l,' '));
$l=trim($l);
while($i<end($indent)){array_pop($stack);array_pop($indent);}
if(str_ends_with($l,':')){
$key=rtrim($l,':');
$stack[array_key_last($stack)][$key]=[];
$stack[]=&$stack[array_key_last($stack)][$key];
$indent[]=$i+2;
}elseif(str_starts_with($l,'- ')){
$v=substr($l,2);
$stack[array_key_last($stack)][]=[];
$stack[]=&$stack[array_key_last($stack)][array_key_last($stack[array_key_last($stack)])];
$indent[]=$i+2;
if(str_contains($v,':')){
[$k,$val]=array_map('trim',explode(':',$v,2));
$stack[array_key_last($stack)][$k]=$val;
}
}else{
[$k,$v]=array_map('trim',explode(':',$l,2));
$stack[array_key_last($stack)][$k]=$v;
}}
return $data;}
function active(array $i):bool{
$now=date('H:i');
if(isset($i['from'])&&$now<$i['from'])return false;
if(isset($i['to'])&&$now>$i['to'])return false;
if(isset($i['days'])){
$days=array_map('trim',explode(',',$i['days']));
if(!in_array(date('N'),$days))return false;}
return true;}
$cfg=file_exists('dashboard.yaml')?yamlParse('dashboard.yaml'):[];
if(!isset($_GET[''])||$_GET['']!=='dash'){
header('Content-Type:text/plain;charset=utf-8');
echo "dashboard.yaml geladen:\n\n";
print_r($cfg);
exit;}
$bg=$cfg['background']??[];
$cards=array_values(array_filter($cfg['cards']??[],fn($c)=>active($c)));
?><!doctype html><html><head><meta charset=utf-8><meta name=viewport content="width=device-width,height=device-height,initial-scale=1">
<style>
html,body{margin:0;width:100%;height:100%;overflow:hidden;background:#000;font-family:sans-serif}
#bg,#card{position:absolute;top:0;left:0;width:100%;height:100%;border:0}
#card{display:none}
</style></head><body>
<?php
if(($bg['type']??'')==='image')echo"<img id=bg src='{$bg['src']}' style='object-fit:cover'>";
if(($bg['type']??'')==='web')echo"<iframe id=bg src='{$bg['src']}'></iframe>";
?>
<iframe id=card></iframe>
<script>
const cards=<?php echo json_encode($cards,JSON_UNESCAPED_SLASHES); ?>;
let i=0;
function show(){
if(cards.length===0)return;
const c=cards[i%cards.length];
const el=document.getElementById('card');
el.style.display='block';
el.src=c.type==='image'?'about:blank':c.src;
if(c.type==='image'){
el.onload=null;
el.src='data:text/html,<style>html,body{margin:0;background:black}img{width:100%;height:100%;object-fit:contain}</style><img src="'+c.src+'">';}
setTimeout(()=>{i++;show();},(parseInt(c.duration)||10)*1000);}
show();
</script></body></html>
``
declare(strict_types=1);
date_default_timezone_set('Europe/Berlin');
function yamlParse(string $file):array{
$lines=file($file,FILE_IGNORE_NEW_LINES);
