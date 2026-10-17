(function(){
  var $$=function(s,r){return [].slice.call((r||document).querySelectorAll(s))};
  // عدّادات متحركة
  $$('.cnt').forEach(function(el){var n=+el.dataset.n,sfx=el.dataset.s||'',t0=performance.now(),d=Math.min(1400,500+n*8);
    (function f(t){var k=Math.min(1,(t-t0)/d),v=Math.round(n*(1-Math.pow(1-k,3)));el.textContent=v+sfx;if(k<1)requestAnimationFrame(f)})(t0)});
  // تبويبات (بتفتكر التبويب من الـ hash)
  function tab(id){var ok=$$('.pane').some(function(p){return p.id===id});if(!ok)id='replies';
    $$('.pane').forEach(function(p){p.classList.toggle('on',p.id===id)});$$('.tab').forEach(function(b){b.classList.toggle('on',b.dataset.t===id)});}
  $$('.tab').forEach(function(b){b.onclick=function(){tab(b.dataset.t);history.replaceState(null,'','#'+b.dataset.t)}});
  tab(location.hash.slice(1));
  // نسخ
  function copy(txt,btn){var done=function(){var o=btn.textContent;btn.textContent='تم النسخ ✓';setTimeout(function(){btn.textContent=o},1500)};
    if(navigator.clipboard)navigator.clipboard.writeText(txt).then(done,function(){fb()});else fb();
    function fb(){var i=document.createElement('input');i.value=txt;document.body.appendChild(i);i.select();document.execCommand('copy');i.remove();done()}}
  var c=document.getElementById('copy');if(c)c.onclick=function(){copy(document.getElementById('lnk').value,c)};
  $$('.copy').forEach(function(b){b.onclick=function(){copy(b.dataset.l,b)}});
  // بحث وفلترة الردود
  var q=document.getElementById('q'),f='all';
  function filt(){var s=(q.value||'').trim().toLowerCase(),shown=0;
    $$('#replies .row').forEach(function(r){var ok=(f==='all'||r.dataset.s===f)&&(!s||r.dataset.n.indexOf(s)>-1);r.hidden=!ok;if(ok)shown++});
    var n=document.getElementById('none');if(n)n.hidden=!($$('#replies .row').length&&!shown)}
  if(q){q.oninput=filt;$$('.chip').forEach(function(ch){ch.onclick=function(){$$('.chip').forEach(function(x){x.classList.remove('on')});ch.classList.add('on');f=ch.dataset.f;filt()}})}
})();
