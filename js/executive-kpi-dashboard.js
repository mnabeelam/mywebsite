
document.addEventListener('DOMContentLoaded',()=>{
 document.querySelectorAll('.kpi-number').forEach(el=>{
   const target=parseInt(el.dataset.target||0);
   let n=0;
   const t=setInterval(()=>{
      n+=Math.ceil(target/50);
      if(n>=target){n=target;clearInterval(t);}
      el.textContent=n;
   },30);
 });
});
