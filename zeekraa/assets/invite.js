(function(){
  window.__inviteReady=true;
  var $=function(i){return document.getElementById(i)},body=document.body,
      reduce=matchMedia('(prefers-reduced-motion:reduce)').matches;
  var a=$('bg'),b=$('mus'),intro=$('intro'),yt=$('yt');
  // مشغّل الموسيقى: ملف/رابط صوت (audio) أو يوتيوب (iframe مخفي)
  var ytp=null,ytReady=false,ytWant=false,M={on:false,
    set:function(v){M.on=v;if(b)b.classList.toggle('on',v)},
    play:function(){
      if(a){var p=a.play();if(p&&p.then)p.then(function(){M.set(true)},function(){M.set(false)});else M.set(true)}
      else if(yt){ytWant=true;if(ytp&&ytReady){ytp.playVideo();M.set(true)}else if(!ytp)M.load()}},
    pause:function(){ytWant=false;if(a)a.pause();else if(ytp&&ytReady)ytp.pauseVideo();M.set(false)},
    load:function(){
      window.onYouTubeIframeAPIReady=function(){var id=yt.dataset.id;
        ytp=new YT.Player('ytp',{videoId:id,playerVars:{autoplay:1,loop:1,playlist:id,controls:0,playsinline:1,rel:0,modestbranding:1},
          events:{onReady:function(e){ytReady=true;if(ytWant){e.target.playVideo();M.set(true)}},
            onStateChange:function(e){if(e.data===0)e.target.playVideo();if(e.data===1)M.set(true);if(e.data===2)M.set(false)},
            onError:function(){M.set(false)}}})};
      var s=document.createElement('script');s.src='https://www.youtube.com/iframe_api';document.head.appendChild(s)}};

  // جزيئات متساقطة (بتلات/قلوب/نجوم حسب التصميم)
  var fx=$('fx');
  if(fx&&!reduce){
    var sym=body.classList.contains('t-minimal')?['·','•','○']:['❀','✿','♡','✦'];
    for(var i=0;i<16;i++){var s=document.createElement('span');s.textContent=sym[i%sym.length];
      s.style.cssText='left:'+Math.random()*100+'%;font-size:'+(10+Math.random()*16)+'px;animation-duration:'+(9+Math.random()*10)+'s;animation-delay:'+(-Math.random()*14)+'s;--dx:'+(Math.random()*160-80)+'px;--r:'+(Math.random()*720-360)+'deg';
      fx.appendChild(s);}
  }

  // فتح الظرف: بيبدأ الحركات والموسيقى (التشغيل لازم يكون بضغطة مستخدم)
  function start(){body.classList.remove('locked');body.classList.add('go');}
  function openIntro(){
    if(!intro||intro.classList.contains('open'))return;
    intro.classList.add('open');setTimeout(start,reduce?0:450);
    M.play();
    try{sessionStorage.setItem('opened'+location.pathname,'1')}catch(e){}
  }
  var seen=false;try{seen=sessionStorage.getItem('opened'+location.pathname)==='1'}catch(e){}
  if(!intro||reduce||location.hash==='#rsvp'||seen){if(intro)intro.style.display='none';start();}
  else{intro.onclick=openIntro;intro.onkeydown=function(e){if(e.key==='Enter'||e.key===' ')openIntro()};}

  // العد التنازلي مع حركة عند تغيّر الرقم
  var cd=$('cd');
  if(cd){var t=+cd.dataset.ts*1000,ids=['d','h','m','s'],last={};
    (function tick(){var d=Math.max(0,t-Date.now())/1000|0,v=[d/86400|0,d%86400/3600|0,d%3600/60|0,d%60];
      ids.forEach(function(k,i){var el=$('cd-'+k);if(last[k]!==v[i]){el.textContent=v[i];if(last[k]!==undefined){el.classList.remove('tick');void el.offsetWidth;el.classList.add('tick')}last[k]=v[i]}});
      setTimeout(tick,1000)})();}

  // زرار الموسيقى
  if(b)b.onclick=function(){M.on?M.pause():M.play()};

  // الكشف عند التمرير + تتابع الصور
  var io=new IntersectionObserver(function(es){es.forEach(function(e){if(e.isIntersecting){e.target.classList.add('in');io.unobserve(e.target)}})},{threshold:.15});
  document.querySelectorAll('.ev,.gal,.rsvp,.count,.events h2,.notes').forEach(function(el,i){el.classList.add('rv');io.observe(el)});
  document.querySelectorAll('.ev').forEach(function(el,i){el.style.setProperty('--d',(i%4)*.12+'s')});
  document.querySelectorAll('.gal-g img').forEach(function(el,i){el.style.setProperty('--d',(i*.07)+'s')});

  // شريط التقدم + بارالاكس خفيف للهيرو
  var prog=$('prog'),hero=document.querySelector('.photo')?null:document.querySelector('.hero-in'),tk=false;
  var onScroll=function(){tk=false;var h=document.documentElement,sc=h.scrollTop||body.scrollTop,max=h.scrollHeight-h.clientHeight;
    if(prog)prog.style.transform='scaleX('+(max>0?sc/max:0)+')';
    if(hero&&!reduce&&sc<innerHeight){hero.style.transform='translateY('+sc*.25+'px)';hero.style.opacity=Math.max(0,1-sc/(innerHeight*.9))}}
  var ph=$('photoImg');if(ph&&!ph.classList.contains('photo-img'))ph=null;
  var _on=onScroll;onScroll=function(){_on();var sc=document.documentElement.scrollTop;if(ph&&!reduce&&sc<innerHeight*1.2)ph.style.translate='0 '+Math.min(40,sc*.08)+'px'};
  addEventListener('scroll',function(){if(!tk){tk=true;requestAnimationFrame(onScroll)}},{passive:true});

  // كونفيتي لما الضيف يأكد حضوره
  var fl=document.querySelector('.rsvp .flash');
  if(fl&&fl.dataset.ok&&!reduce){
    var cols=['#d4b26a','#b08a3e','#f1e6c9','#e8a0a0','#fff'];
    for(var i=0;i<70;i++){var c=document.createElement('i');c.className='cf';
      c.style.cssText='left:'+Math.random()*100+'%;background:'+cols[i%cols.length]+';--x:'+(Math.random()*240-120)+'px;--r:'+(Math.random()*900)+'deg;animation-delay:'+Math.random()*.8+'s;animation-duration:'+(2+Math.random()*1.5)+'s';
      document.body.appendChild(c);setTimeout(function(n){n.remove()}.bind(null,c),5000);}
  }

  // ميلان 3D خفيف لكروت المناسبات (للماوس بس)
  if(!reduce&&matchMedia('(hover:hover)').matches){
    document.querySelectorAll('.ev').forEach(function(c){
      c.addEventListener('pointermove',function(e){var r=c.getBoundingClientRect(),x=(e.clientX-r.left)/r.width-.5,y=(e.clientY-r.top)/r.height-.5;
        c.classList.add('tilt');c.style.transform='perspective(700px) rotateY('+x*8+'deg) rotateX('+-y*8+'deg) translateY(-4px)'});
      c.addEventListener('pointerleave',function(){c.classList.remove('tilt');c.style.transform=''});});
  }

  // عرض الصور بحجم كبير مع تنقل بالأسهم/السحب
  var lb=$('lb'),lbi=$('lbImg'),imgs=[].slice.call(document.querySelectorAll('.gal-g img')),cur=0;
  function show(i){cur=(i+imgs.length)%imgs.length;lbi.src=imgs[cur].src;lb.hidden=false;body.style.overflow='hidden'}
  function hide(){lb.hidden=true;body.style.overflow=''}
  if(lb&&imgs.length){
    imgs.forEach(function(im,i){im.onclick=function(){show(i)};im.onkeydown=function(e){if(e.key==='Enter')show(i)}});
    $('lbX').onclick=hide;$('lbN').onclick=function(){show(cur+1)};$('lbP').onclick=function(){show(cur-1)};
    lb.onclick=function(e){if(e.target===lb)hide()};
    addEventListener('keydown',function(e){if(lb.hidden)return;if(e.key==='Escape')hide();if(e.key==='ArrowLeft')show(cur+1);if(e.key==='ArrowRight')show(cur-1)});
    var sx=0;lb.addEventListener('touchstart',function(e){sx=e.touches[0].clientX},{passive:true});
    lb.addEventListener('touchend',function(e){var d=e.changedTouches[0].clientX-sx;if(Math.abs(d)>50)show(cur+(d<0?1:-1))});
  }
})();
