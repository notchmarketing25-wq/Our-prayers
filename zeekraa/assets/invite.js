(function(){
  var cd=document.getElementById('cd');
  if(cd){var t=+cd.dataset.ts*1000;(function tick(){var d=Math.max(0,t-Date.now())/1000|0;
    document.getElementById('cd-d').textContent=d/86400|0;document.getElementById('cd-h').textContent=d%86400/3600|0;
    document.getElementById('cd-m').textContent=d%3600/60|0;document.getElementById('cd-s').textContent=d%60;setTimeout(tick,1000)})();}
  var a=document.getElementById('bg'),b=document.getElementById('mus');
  if(a&&b)b.onclick=function(){if(a.paused){a.play();b.classList.add('on')}else{a.pause();b.classList.remove('on')}};
  var io=new IntersectionObserver(function(es){es.forEach(function(e){if(e.isIntersecting){e.target.classList.add('in');io.unobserve(e.target)}})},{threshold:.15});
  document.querySelectorAll('.ev,.gal,.rsvp,.count').forEach(function(el){el.classList.add('rv');io.observe(el)});
})();
