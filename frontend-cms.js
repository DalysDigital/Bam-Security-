(function(){
  const esc=(v)=>String(v??'');
  const setText=(sel,key,c)=>{const el=document.querySelector(sel);if(el&&c[key]!==undefined)el.textContent=esc(c[key]);};
  const setHTML=(sel,key,c)=>{const el=document.querySelector(sel);if(el&&c[key]!==undefined)el.innerHTML=esc(c[key]).replace(/\n/g,'<br>');};
  const setSrc=(sel,key,c)=>{const el=document.querySelector(sel);if(el&&c[key])el.src=c[key];};
  const setHref=(sel,key,c,fn)=>{const el=document.querySelector(sel);if(el&&c[key])el.href=fn?fn(c[key]):c[key];};
  function applyColors(c){
    const r=document.documentElement;
    const props={
      '--cms-day-bg':c.day_bg,'--cms-day-text':c.day_text,'--cms-day-muted':c.day_muted,'--cms-day-panel':c.day_panel,'--cms-day-line':c.day_line,'--cms-day-accent':c.day_accent,'--cms-day-accent2':c.day_accent2,
      '--cms-night-bg':c.night_bg,'--cms-night-text':c.night_text,'--cms-night-muted':c.night_muted,'--cms-night-panel':c.night_panel,'--cms-night-line':c.night_line,'--cms-night-accent':c.night_accent,'--cms-night-accent2':c.night_accent2,
      '--cms-heading-font':c.font_heading||'Space Grotesk','--cms-body-font':c.font_body||'Inter'
    };
    Object.entries(props).forEach(([k,v])=>{if(v)r.style.setProperty(k,v)});
    let style=document.getElementById('cmsThemeStyle');
    if(!style){style=document.createElement('style');style.id='cmsThemeStyle';document.head.appendChild(style)}
    style.textContent=`
      body{font-family:var(--cms-body-font),system-ui,sans-serif}
      h1,h2,h3,.hero h1,.section h2,.service-card h3,.cap-card h3,.timeline-item h3,.visual-h2{font-family:var(--cms-heading-font),system-ui,sans-serif}
      html[data-site-theme="night"] body{background:var(--cms-night-bg);color:var(--cms-night-text)}
      html[data-site-theme="night"] .glass{background:linear-gradient(135deg,var(--cms-night-panel),rgba(255,255,255,.02));border-color:var(--cms-night-line)}
      html[data-site-theme="night"] .hero-lead,html[data-site-theme="night"] .feature-copy p,html[data-site-theme="night"] .section-head>p,html[data-site-theme="night"] .story-copy p,html[data-site-theme="night"] .never-copy p,html[data-site-theme="night"] .contact p,html[data-site-theme="night"] .service-card p,html[data-site-theme="night"] .cap-card p{color:var(--cms-night-muted)}
      html[data-site-theme="night"] .site-header nav a,html[data-site-theme="night"] .header-call,html[data-site-theme="night"] .footer a{color:var(--cms-night-muted)}
      html[data-site-theme="night"] .btn-primary,.btn-primary{background:var(--cms-night-accent)}
      html[data-site-theme="night"] .hero h1 em,html[data-site-theme="night"] .section h2 em,.service-card .icon,.cap-card b,.timeline-item>span{color:var(--cms-night-accent)}
      html[data-site-theme="day"] body{background:var(--cms-day-bg);color:var(--cms-day-text)}
      html[data-site-theme="day"] .glass{background:var(--cms-day-panel);border-color:var(--cms-day-line)}
      html[data-site-theme="day"] .hero-lead,html[data-site-theme="day"] .feature-copy p,html[data-site-theme="day"] .section-head>p,html[data-site-theme="day"] .story-copy p,html[data-site-theme="day"] .never-copy p,html[data-site-theme="day"] .contact p,html[data-site-theme="day"] .service-card p,html[data-site-theme="day"] .cap-card p{color:var(--cms-day-muted)}
      html[data-site-theme="day"] .site-header nav a,html[data-site-theme="day"] .header-call,html[data-site-theme="day"] .footer a{color:var(--cms-day-muted)}
      html[data-site-theme="day"] .btn-primary{background:var(--cms-day-accent)}
      html[data-site-theme="day"] .hero h1 em,html[data-site-theme="day"] .section h2 em,.service-card .icon,.cap-card b,.timeline-item>span{color:var(--cms-day-accent)}
    `;
  }
  function applyGallery(items,c){
    const side=document.querySelector('.gallery-side'),main=document.getElementById('galleryMain'),zoom=document.getElementById('galleryZoom');
    if(!side||!main)return;
    side.innerHTML='';
    items.forEach((it,i)=>{
      const b=document.createElement('button');b.className='gallery-thumb'+(i===0?' active':'');b.dataset.img=it.image;b.type='button';
      const sp=document.createElement('span');sp.textContent=String(i+1).padStart(2,'0');
      const img=document.createElement('img');img.src=it.image;img.alt=it.alt_text||it.title||'BAM gallery image';b.append(sp,img);side.appendChild(b);
    });
    if(window.BAM_REFRESH_GALLERY) window.BAM_REFRESH_GALLERY();
    if(items[0]){main.src=items[0].image;main.alt=items[0].alt_text||items[0].title||'BAM gallery image';if(zoom)zoom.src=items[0].image;}
    const counter=document.getElementById('galleryCounter');if(counter)counter.textContent=`${items.length} ASSETS LOADED`;
  }
  function apply(c,gallery){
    setText('#mainNav a:nth-child(1)','nav_about',c);setText('#mainNav a:nth-child(2)','nav_services',c);setText('#mainNav a:nth-child(3)','nav_capabilities',c);setText('#mainNav a:nth-child(4)','nav_gallery',c);setText('#mainNav a:nth-child(5)','nav_contact',c);setText('.header-call','nav_call',c);
    setText('.hero h1','hero_title',c);setText('.hero-lead','hero_lead',c);setText('.hero-meta div:nth-child(1) strong','hero_established',c);setText('.hero-meta div:nth-child(1) span','hero_established_label',c);setText('.hero-meta div:nth-child(2) strong','hero_experience',c);setText('.hero-meta div:nth-child(2) span','hero_experience_label',c);setText('.hero-meta div:nth-child(3) strong','hero_professionals',c);setText('.hero-meta div:nth-child(3) span','hero_professionals_label',c);setText('.status-line','hero_status',c);
    const status=document.querySelector('.status-line');if(status&&c.hero_location)status.innerHTML='<i></i>'+esc(c.hero_status)+' <span>•</span> '+esc(c.hero_location);
    setText('.hero-actions .btn-primary','hero_button1',c);setText('.hero-actions .btn-glass','hero_button2',c);setText('.split>div:first-child h2','about_heading',c);setText('.split .text-card h3','about_title',c);setText('.split .text-card p:nth-of-type(1)','about_p1',c);setText('.split .text-card p:nth-of-type(2)','about_p2',c);setText('.pill-row span:nth-child(1)','about_pill1',c);setText('.pill-row span:nth-child(2)','about_pill2',c);setText('.pill-row span:nth-child(3)','about_pill3',c);
    setText('.feature-copy .visual-h2','why_title',c);setText('.feature-copy>p','why_text',c);setText('.stats div:nth-child(1) b','why_stat1',c);setText('.stats div:nth-child(1) span','why_stat1_label',c);setText('.stats div:nth-child(2) b','why_stat2',c);setText('.stats div:nth-child(2) span','why_stat2_label',c);setText('.stats div:nth-child(3) b','why_stat3',c);setText('.stats div:nth-child(3) span','why_stat3_label',c);
    setText('#services .section-head h2','services_heading',c);setText('#services .section-head>p','services_intro',c);for(let i=1;i<=4;i++){setText(`#services .service-card:nth-child(${i}) h3`,`service${i}_title`,c);setText(`#services .service-card:nth-child(${i}) p`,`service${i}_text`,c);setText(`#services .service-card:nth-child(${i})>span`,`service${i}_tag`,c)}setText('.service-keywords h3','services_additional_title',c);setText('.service-keywords p','services_additional',c);
    setText('#capabilities .section-head h2','cap_heading',c);setText('#capabilities .section-head>p','cap_intro',c);for(let i=1;i<=4;i++){setText(`#capabilities .cap-card:nth-child(${i}) h3`,`cap${i}_title`,c);setText(`#capabilities .cap-card:nth-child(${i}) p`,`cap${i}_text`,c)}setText('.events-keywords p','events_list',c);
    setText('.journey .section-head h2','journey_heading',c);setText('.journey .section-head>p','journey_intro',c);for(let i=1;i<=4;i++){setText(`.timeline-item:nth-of-type(${i+1})>span`,`timeline${i}_label`,c);setText(`.timeline-item:nth-of-type(${i+1}) h3`,`timeline${i}_title`,c);setText(`.timeline-item:nth-of-type(${i+1}) p`,`timeline${i}_text`,c)}
    setText('#gallery .section-head .visual-h2','gallery_heading',c);setText('#gallery .section-head>p','gallery_intro',c);setText('.gallery-console span:nth-child(1)','gallery_console1',c);setText('.gallery-console span:nth-child(2)','gallery_console2',c);
    setText('.story-copy h2','story_title',c);setText('.story-copy h3','story_heading',c);setText('.story-copy p:nth-of-type(1)','story_p1',c);setText('.story-copy p:nth-of-type(2)','story_p2',c);setText('.vision-card:nth-child(1)>h3:first-of-type','vision_title',c);setText('.vision-card:nth-child(1) .visual-h2','vision_big',c);setText('.vision-card:nth-child(1)>p','vision_text',c);setText('.vision-card:nth-child(2) .visual-h2','mission_big',c);setText('.vision-card:nth-child(2)>p','mission_text',c);
    setText('.never-copy h2','join_heading',c);setText('.never-copy p','join_text',c);setText('#contact .contact-layout>div:first-child h2','contact_heading',c);setText('#contact .contact-layout>div:first-child p','contact_text',c);setText('.form-top span','contact_form_label',c);setText('.form-top h3','contact_form_title',c);setText('.form-note','contact_note',c);setText('.footer span','footer_text',c);setText('.footer a','footer_back',c);
    const logo=document.getElementById('siteLogo');if(logo){logo.dataset.day=c.frontend_logo_day;logo.dataset.night=c.frontend_logo_night;logo.src=(document.documentElement.dataset.siteTheme==='day'?c.frontend_logo_day:c.frontend_logo_night)}const fl=document.getElementById('footerLogo');if(fl)fl.src=document.documentElement.dataset.siteTheme==='day'?c.frontend_logo_day:c.frontend_logo_night;
    setSrc('.hero-frame img','hero_image',c);setSrc('.feature-image img','feature_image',c);setSrc('.story-image img','story_image',c);
    setHref('.header-call','contact_phone',c,v=>/^https?:/i.test(v)?v:'tel:'+v.replace(/\s+/g,''));
    document.title=c.site_title||document.title;const desc=document.querySelector('meta[name="description"]');if(desc&&c.site_description)desc.content=c.site_description;
    const wa=document.querySelector('.floating-wa');if(wa&&c.contact_whatsapp)wa.href=`https://wa.me/${c.contact_whatsapp}?text=${encodeURIComponent('Hello BAM, I would like to enquire about your security services.')}`;
    window.BAM_FRONTEND_CONFIG=c; document.documentElement.style.setProperty('--cms-contact-email',JSON.stringify(c.contact_email||''));
    applyColors(c);applyGallery(gallery,c);
  }
  fetch('frontend_content.php',{cache:'no-store'}).then(r=>r.json()).then(d=>{if(d&&d.ok)apply(d.content||{},d.gallery||[])}).catch(()=>{});
})();
