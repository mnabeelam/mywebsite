
function setTheme(color){
document.documentElement.style.setProperty('--accent',color);
localStorage.setItem('themeColor',color);
}
window.onload=()=>{
let c=localStorage.getItem('themeColor');
if(c) document.documentElement.style.setProperty('--accent',c);
}
