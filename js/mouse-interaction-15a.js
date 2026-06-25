
document.addEventListener('mousemove',(e)=>{
const x=(e.clientX/window.innerWidth-.5)*20;
const y=(e.clientY/window.innerHeight-.5)*20;
document.querySelectorAll('.parallax-earth').forEach(el=>{
el.style.transform=`translate(${x}px,${y}px)`;
});
});
