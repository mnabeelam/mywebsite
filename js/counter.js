
document.querySelectorAll('.counter').forEach(c=>{
let t=+c.dataset.target;
let v=0;
let i=setInterval(()=>{
v+=Math.ceil(t/100);
if(v>=t){v=t;clearInterval(i);}
c.innerHTML=v;
},20);
});
