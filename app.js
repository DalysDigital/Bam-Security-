const $=s=>document.querySelector(s);

/* Public home: show the skeleton only for the initial render.
   Never show it for same-page navigation/hash links. */
(function(){
  const sk=document.getElementById('bamGlobalSkeleton');
  if(!sk)return;
  const hide=()=>{sk.classList.remove('show');sk.setAttribute('aria-hidden','true');};
  if(document.readyState==='loading'){
    document.addEventListener('DOMContentLoaded',()=>setTimeout(hide,220),{once:true});
  }else{
    setTimeout(hide,120);
  }
  window.addEventListener('pageshow',hide,{once:true});
  setTimeout(hide,1200);
})();

const y=$('#year'); if(y)y.textContent=new Date().getFullYear();
const toggle=$('#menuToggle'),header=document.querySelector('.site-header');
toggle?.addEventListener('click',()=>{const open=header.classList.toggle('menu-open');toggle.setAttribute('aria-expanded',open)});
document.querySelectorAll('#mainNav a').forEach(a=>a.addEventListener('click',()=>{header.classList.remove('menu-open');toggle?.setAttribute('aria-expanded','false')}));
const main=$('#galleryMain'), indexEl=$('#galleryIndex'), statusEl=$('#galleryStatus'), counterEl=$('#galleryCounter');
let thumbs=[...document.querySelectorAll('.gallery-thumb')];
let galleryIndex=0;
const galleryFrame=$('#galleryFrame');
let galleryImages=thumbs.map(btn=>btn.dataset.img);
function setGallery(i){
  galleryIndex=(i+thumbs.length)%thumbs.length; const btn=thumbs[galleryIndex];
  thumbs.forEach(x=>x.classList.remove('active')); btn.classList.add('active');
  main.style.opacity=.15;
  setTimeout(()=>{main.src=btn.dataset.img;main.style.opacity=1;},120);
  if(indexEl) indexEl.textContent=`${String(galleryIndex+1).padStart(2,'0')} / ${String(thumbs.length).padStart(2,'0')}`;
  if(statusEl) statusEl.textContent=`VISUAL CHANNEL ${String(galleryIndex+1).padStart(2,'0')} // ONLINE`;
}
function bindGalleryThumbs(){ thumbs.forEach((btn,i)=>btn.addEventListener('click',()=>setGallery(i))); }
bindGalleryThumbs();
window.BAM_REFRESH_GALLERY=function(){
  if(window.__bamGalleryTimer) clearInterval(window.__bamGalleryTimer);
  thumbs=[...document.querySelectorAll('.gallery-thumb')];
  galleryImages=thumbs.map(btn=>btn.dataset.img);
  if(galleryImages[0]){const img=new Image();img.decoding='async';img.src=galleryImages[0];}
  galleryIndex=0;
  bindGalleryThumbs();
  if(thumbs.length) setGallery(0);
  window.__bamGalleryTimer=setInterval(()=>setGallery(galleryIndex+1),6500);
};
$('#galleryPrev')?.addEventListener('click',()=>setGallery(galleryIndex-1));
$('#galleryNext')?.addEventListener('click',()=>setGallery(galleryIndex+1));
window.__bamGalleryTimer=setInterval(()=>setGallery(galleryIndex+1),6500);
$('#galleryFrame')?.addEventListener('mouseenter',()=>clearInterval(window.__bamGalleryTimer));
$('#galleryFrame')?.addEventListener('mouseleave',()=>window.__bamGalleryTimer=setInterval(()=>setGallery(galleryIndex+1),6500));
const lightbox=$('#galleryLightbox'), zoom=$('#galleryZoom');
$('#galleryExpand')?.addEventListener('click',()=>{zoom.src=main.src;lightbox.classList.add('open');lightbox.setAttribute('aria-hidden','false');});
function closeGallery(){lightbox?.classList.remove('open');lightbox?.setAttribute('aria-hidden','true');}
$('#galleryClose')?.addEventListener('click',closeGallery); lightbox?.addEventListener('click',e=>{if(e.target===lightbox)closeGallery()});
document.addEventListener('keydown',e=>{if(e.key==='Escape')closeGallery();if(e.key==='ArrowLeft')setGallery(galleryIndex-1);if(e.key==='ArrowRight')setGallery(galleryIndex+1)});
let touchX=0; main?.addEventListener('touchstart',e=>touchX=e.changedTouches[0].screenX,{passive:true}); main?.addEventListener('touchend',e=>{const dx=e.changedTouches[0].screenX-touchX;if(Math.abs(dx)>45)setGallery(galleryIndex+(dx<0?1:-1));},{passive:true});
const toast=$('#toast');let toastTimer;
function showToast(msg){toast.textContent=msg;toast.classList.add('show');clearTimeout(toastTimer);toastTimer=setTimeout(()=>toast.classList.remove('show'),2600)}
function enquiryText(){
  const name=$('#name').value.trim(),phone=$('#phone').value.trim(),email=$('#email').value.trim(),service=$('#service').value,date=$('#date').value,location=$('#location').value.trim(),message=$('#message').value.trim();
  if(!name||!phone||!message){showToast('Please complete your name, phone and requirement.');return null}
  return `BAM SECURITY ENQUIRY\n\nName: ${name}\nPhone: ${phone}\nEmail: ${email||'Not provided'}\nService: ${service}\nDate: ${date||'Not specified'}\nLocation: ${location||'Not specified'}\n\nRequirement:\n${message}`;
}
document.querySelectorAll('[data-channel]').forEach(btn=>btn.addEventListener('click',()=>{
  const text=enquiryText();if(!text)return;
  const enc=encodeURIComponent(text),channel=btn.dataset.channel;
  const cfg=window.BAM_FRONTEND_CONFIG||{};
  const phone=(cfg.contact_phone||'+917005668453').replace(/\D/g,'');
  const email=cfg.contact_email||'e2kharkongor@gmail.com';
  const wa=(cfg.contact_whatsapp||phone).replace(/\D/g,'');
  if(channel==='whatsapp') window.open(`https://wa.me/${wa}?text=${enc}`,'_blank','noopener');
  if(channel==='email') window.location.href=`mailto:${email}?subject=${encodeURIComponent('BAM Security Enquiry — '+$('#name').value.trim())}&body=${enc}`;
  if(channel==='sms') window.location.href=`sms:+${phone}?body=${enc}`;
}));

