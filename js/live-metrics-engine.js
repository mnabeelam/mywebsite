
document.addEventListener('DOMContentLoaded',()=>{
 document.querySelectorAll('.metric-value').forEach(el=>{
   const target=parseInt(el.dataset.target||0);
   let n=0;
   const t=setInterval(()=>{
      n += Math.max(1, Math.ceil(target/80));
      if(n>=target){n=target;clearInterval(t);}
      el.textContent=n;
   },25);
 });
});
