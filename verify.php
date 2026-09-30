<?php
require_once __DIR__ . '/config/config.php';
ensure_settings_table();
ensure_id_fields_table();
$id=trim((string)($_GET['id']??''));
$m=$id?member_by_id($id):null;
$theme=get_setting('site_theme','night')==='day'?'day':'night';
$verifyTitle=get_setting('verify_title','Verification Confirmed.');
$verifySubtitle=get_setting('verify_subtitle','Verified BAM Member');
$verifyPrompt=get_setting('verify_prompt','Scan the official BAM QR code or enter a Member ID to verify a membership record.');
$verifyInvalid=get_setting('verify_invalid','No valid BAM membership record was found for this verification.');
$verifyFooter=get_setting('verify_footer','BAM Digital ID Verification • Official QR verification');
$searchEnabled=get_setting('verify_public_search','0')==='1';
$badge=get_setting('verify_badge_style','green')==='blue'?'blue':'green';
$logo=$theme==='day'?'assets/bam-logo-day.png':'assets/bam-logo.png';
$custom=[];
if($m && !empty($m['custom_data'])){ $decoded=json_decode((string)$m['custom_data'],true); if(is_array($decoded))$custom=$decoded; }
$customFields=db()->query("SELECT * FROM id_fields WHERE active=1 AND public_verify=1 ORDER BY sort_order,id")->fetchAll();
$standard=[
 'member_no'=>['Member ID','verify_show_member_id'], 'designation'=>['Designation','verify_show_designation'], 'phone'=>['Phone','verify_show_phone'], 'email'=>['Email','verify_show_email'], 'address'=>['Address','verify_show_address'], 'events'=>['Events / Assignment','verify_show_events'], 'joined_date'=>['Joined Date','verify_show_joined_date'], 'valid_until'=>['Valid Until','verify_show_valid_until'], 'status'=>['Status','verify_show_status']
];
function verification_value(array $m,string $key): string { return trim((string)($m[$key]??'')); }
function verification_state(array $m): string {
    $status=strtolower(trim((string)($m['status']??'')));
    if($status==='active'){
        $valid=trim((string)($m['valid_until']??''));
        if($valid!==''){
            $ts=strtotime($valid.' 23:59:59');
            if($ts!==false && $ts<time()) return 'expired';
        }
        return 'active';
    }
    if(in_array($status,['suspended','inactive','expired'],true)) return $status;
    return $status?:'inactive';
}
$memberState=$m?verification_state($m):'';

?>
<!doctype html><html lang="en" data-admin-theme="<?=e($theme)?>"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><meta name="theme-color" content="<?= $theme==='day'?'#ffffff':'#050505' ?>"><title><?=e($verifyTitle)?></title><link rel="stylesheet" href="styles.css"><style>
.verify-page{min-height:100vh;background:<?= $theme==='day'?'#f5f7fa':'#050505' ?>;color:<?= $theme==='day'?'#151a20':'#fff' ?>}.verify-card{max-width:820px;margin:auto;padding:34px;border-radius:24px;border:1px solid <?= $theme==='day'?'#d8dde5':'#2b2b2b' ?>;background:<?= $theme==='day'?'#fff':'rgba(12,12,12,.88)' ?>;box-shadow:0 20px 70px rgba(0,0,0,.18)}.verify-header{display:flex;justify-content:space-between;align-items:center;gap:20px}.verify-header img{width:170px;max-height:85px;object-fit:contain;border-radius:10px}.verify-search{display:flex;gap:10px;margin-top:20px}.verify-search input{flex:1;min-width:0;padding:14px 16px;border-radius:12px;border:1px solid <?= $theme==='day'?'#cfd5dd':'#333' ?>;background:<?= $theme==='day'?'#fff':'#0e0e0e' ?>;color:inherit}.verify-search button{border:0;border-radius:12px;padding:14px 18px;background:#e30613;color:#fff;font-weight:800}.verified-badge{display:inline-flex;align-items:center;justify-content:center;width:46px;height:46px;border-radius:50%;margin-bottom:10px}.verified-badge.green{background:#18a957}.verified-badge.blue{background:#1677ff}.verify-member.state-active .verify-state,.verify-member.state-active .verify-status-value{color:#18a957}.verify-member.state-inactive .verify-state,.verify-member.state-inactive .verify-status-value,.verify-member.state-suspended .verify-state,.verify-member.state-suspended .verify-status-value,.verify-member.state-expired .verify-state,.verify-member.state-expired .verify-status-value{color:#e30613}.verify-state{font-weight:900;letter-spacing:.06em}.verified-badge svg{width:27px;height:27px}.verified-badge path{stroke:#fff;stroke-width:4;fill:none;stroke-linecap:round;stroke-linejoin:round}.verify-member{display:grid;grid-template-columns:150px 1fr;gap:26px;align-items:start;margin-top:24px}.verify-member img{width:150px;height:180px;object-fit:cover;border-radius:14px;border:1px solid <?= $theme==='day'?'#d7dce3':'#333' ?>;background:#111}.verify-detail{padding:11px 13px;border:1px solid <?= $theme==='day'?'#e0e4e9':'#2b2b2b' ?>;border-radius:10px;background:<?= $theme==='day'?'#f8f9fa':'rgba(255,255,255,.025)' ?>}.verify-footer{margin-top:24px;padding-top:18px;border-top:1px solid <?= $theme==='day'?'#e0e4e9':'#292929' ?>;color:#7b838c;font-size:12px}@media(max-width:650px){.verify-card{padding:20px}.verify-header{align-items:flex-start}.verify-header img{width:125px}.verify-search{flex-direction:column}.verify-member{grid-template-columns:1fr}.verify-member img{width:120px;height:145px}}
</style></head><body class="verify-page">
<header class="site-header glass"><a class="brand" href="./"><img src="<?=$logo?>" alt="Bouncer Association of Meghalaya"></a><nav><a href="./">Home</a><a href="verify.php">Verify ID</a></nav></header>
<main class="section" style="min-height:78vh;padding-top:160px"><div class="verify-card">
<div class="section-label">BAM // DIGITAL ID VERIFICATION</div>
<div class="verify-header"><div><h1><?=e($verifyTitle)?></h1><p class="hero-lead"><?=e($verifyPrompt)?></p></div></div>
<?php if(!$id && $searchEnabled): ?>
<form class="verify-search" method="get"><input name="id" placeholder="Enter BAM Member ID" autocomplete="off" required><button type="submit">VERIFY MEMBER</button></form>
<?php endif; ?>
<?php if($id && !$m): ?>
<div class="alert" style="margin-top:20px;padding:15px;border:1px solid rgba(227,6,19,.4);border-radius:12px;background:rgba(227,6,19,.08);color:#ff5962"><?=e($verifyInvalid)?></div>
<?php elseif($m): ?>
<div class="verify-member state-<?=e($memberState)?>">
<div><img src="<?=e(member_photo($m['photo']))?>" alt="BAM member photo"></div>
<div>
<div class="verified-badge <?=$badge?>" aria-label="Verified"><svg viewBox="0 0 32 32" aria-hidden="true"><?php if($badge==='blue'): ?><path d="M9 9l7 7 7-7"/><?php else: ?><path d="M7 17l6 6L26 9"/><?php endif; ?></svg></div>
<div class="verify-state" style="font-size:10px;letter-spacing:.15em;text-transform:uppercase"><?=e($verifySubtitle)?></div>
<h2 style="margin:6px 0 18px;font-size:30px"><?=e($m['name'])?></h2>
<div style="display:grid;gap:9px">
<?php foreach($standard as $key=>$cfg): $show=get_setting($cfg[1],'0')==='1'; $value=verification_value($m,$key); if(!$show || $value==='') continue; ?><div class="verify-detail"><b><?=e($cfg[0])?>:</b> <span class="<?= $key==='status'?'verify-status-value':'' ?>"><?=e($key==='status'?strtoupper($memberState):$value)?></span></div><?php endforeach; ?>
<?php foreach($customFields as $f): $key=$f['field_key']; $value=trim((string)($custom[$key]??'')); if($value==='') continue; ?><div class="verify-detail"><b><?=e($f['label'])?>:</b> <?=e($value)?></div><?php endforeach; ?>
</div>
<?php if(get_setting('verify_show_status','1')==='1' && $memberState!==''): ?><div style="margin-top:18px"><span class="verify-status-value" style="font-weight:900"><?=e(strtoupper($memberState))?></span></div><?php endif; ?>
</div></div>
<?php elseif(!$id && !$searchEnabled): ?>
<div class="alert" style="margin-top:20px;padding:15px;border:1px solid rgba(227,6,19,.3);border-radius:12px;background:rgba(227,6,19,.06)"><?=e($verifyPrompt)?></div>
<?php endif; ?>
<div class="verify-footer"><?=e($verifyFooter)?></div>
</div></main>
</body></html>
