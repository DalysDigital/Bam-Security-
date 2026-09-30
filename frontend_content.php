<?php
require_once __DIR__.'/config/config.php';
ensure_frontend_content_table();
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
$s=frontend_settings();
$g=frontend_gallery(true);
$base=rtrim(APP_URL,'/').'/';
$normalize=function($path)use($base){$path=(string)$path;if(preg_match('~^https?://~i',$path))return $path;return $base.ltrim($path,'/');};
foreach(['hero_image','feature_image','story_image','frontend_logo_day','frontend_logo_night'] as $k) if(isset($s[$k])) $s[$k]=$normalize($s[$k]);
foreach($g as &$row){$row['image']=$normalize($row['image']);}
unset($row);
$s['day_logo']=$base.'assets/bam-logo-day.png';
$s['night_logo']=$base.'assets/bam-logo-night-glossy.png';
$s['footer_logo_day']=$base.'assets/bam-logo-day.png';
$s['footer_logo_night']=$base.'assets/bam-logo-night.png';
echo json_encode(['ok'=>true,'content'=>$s,'gallery'=>$g],JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE);
