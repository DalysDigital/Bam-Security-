<?php
require_once __DIR__.'/config/config.php';
ensure_settings_table();
$id=trim((string)($_GET['id']??''));
if($id===''){$id=trim((string)($_GET['member']??''));}
$m=$id?member_by_id($id):null;
if(!$m){http_response_code(404);exit('Member not found.');}

$verifyUrl=APP_URL.'/verify.php?id='.rawurlencode($m['member_no']);
$qr='https://api.qrserver.com/v1/create-qr-code/?size=500x500&data='.rawurlencode($verifyUrl);
$idLogo=get_setting('id_logo','assets/bam-logo.png');
$idLogoSrc=(strpos($idLogo,'uploads/')===0?APP_URL.'/'.$idLogo:APP_URL.'/'.$idLogo);
$frontBg=get_setting('id_front_bg','#090909');
$backBg=get_setting('id_back_bg','#090909');
$accent=get_setting('id_accent','#e30613');
$idSize=get_setting('id_size','80x100');

$sizes=[
 '55x85'=>['w'=>'55mm','h'=>'85mm','label'=>'ATM / Standard — 5.5 × 8.5 cm'],
 '85x55'=>['w'=>'85mm','h'=>'55mm','label'=>'Landscape — 8.5 × 5.5 cm'],
 '80x100'=>['w'=>'80mm','h'=>'100mm','label'=>'Large — 8 × 10 cm']
];
if(!isset($sizes[$idSize]))$idSize='80x100';
$dim=$sizes[$idSize];

$frontFooter=get_setting('id_front_footer','BAM • PROFESSIONAL SECURITY • MEGHALAYA');
$backTitle=get_setting('id_back_title','MEMBERSHIP VERIFICATION');
$backNote=get_setting('id_back_note',"Scan the QR code to verify this member's BAM registration and current membership status.");
$backFooter=get_setting('id_back_footer','ESTD. 2004');
?>
<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<meta name="theme-color" content="#080808">
<title>BAM Member ID - <?=e($m['name'])?></title>
<link rel="stylesheet" href="assets/id.css">
</head>
<body>
<div class="print-actions">
  <button onclick="window.print()">Print / Save PDF</button>
  <a href="verify.php?id=<?=urlencode($m['member_no'])?>">Verify</a>
  <span class="size-badge"><?=e($dim['label'])?></span>
</div>

<div class="print-sheet id-size-<?=e($idSize)?>" style="--id-w:<?=$dim['w']?>;--id-h:<?=$dim['h']?>">
  <div class="cut-card">
    <div class="id-wrap front" style="--id-bg:<?=e($frontBg)?>;--id-accent:<?=e($accent)?>">
      <div class="id-bg"></div>
      <div class="id-content">
        <div class="brand-zone">
          <img class="id-logo" src="<?=e($idLogoSrc)?>" alt="BAM">
          <span class="brand-line"></span>
        </div>

        <div class="photo-frame">
          <span class="corner tl"></span><span class="corner tr"></span>
          <span class="corner bl"></span><span class="corner br"></span>
          <img class="photo" src="<?=e(member_photo($m['photo']))?>" alt="<?=e($m['name'])?>">
        </div>

        <div class="name"><?=e($m['name'])?></div>
        <div class="designation"><?=e($m['designation'])?></div>

        <div class="validity">
          <div><span>VALID FROM</span><strong><?=e(!empty($m['joined_date']) ? date('d/m/Y', strtotime($m['joined_date'])) : '—')?></strong></div>
          <div><span>VALID TILL</span><strong><?=e(!empty($m['valid_until']) ? date('d/m/Y', strtotime($m['valid_until'])) : '—')?></strong></div>
        </div>

        <div class="front-footer">BAM • PROFESSIONAL SECURITY • MEGHALAYA</div>
      </div>
    </div>
  </div>

  <div class="cut-card">
    <div class="id-wrap back" style="--id-bg:<?=e($backBg)?>;--id-accent:<?=e($accent)?>">
      <div class="id-bg"></div>
      <div class="id-content">
        <div class="brand-zone">
          <img class="id-logo" src="<?=e($idLogoSrc)?>" alt="BAM">
          <span class="brand-line"></span>
        </div>

        <h2>MEMBERSHIP VERIFICATION</h2>
        <p class="verify-note">Scan the QR code to verify this member's BAM registration and current membership status.</p>

        <div class="qr-frame">
          <span class="corner tl"></span><span class="corner tr"></span>
          <span class="corner bl"></span><span class="corner br"></span>
          <img class="qr" src="<?=e($qr)?>" alt="Verification QR Code">
        </div>

        <div class="back-member-id"><span>Member ID:</span> <?=e($m['member_no'])?></div>

        <p class="property-note">This card is the property of the Bouncers Association of Meghalaya and is non-transferable.<br>
        If found, please return to Malki Point,<br>Shillong - 793001 | +91 70056 68453</p>

        <div class="back-footer">ESTD. 2004</div>
      </div>
    </div>
  </div>
</div>
</body>
</html>
