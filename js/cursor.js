
document.addEventListener('DOMContentLoaded',()=>{
const d=document.createElement('div');
d.className='cursor-dot';
document.body.appendChild(d);
document.addEventListener('mousemove',e=>{
d.style.left=e.clientX+'px';
d.style.top=e.clientY+'px';
});
});
