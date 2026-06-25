
window.addEventListener('scroll',()=>{
let s=document.documentElement.scrollTop;
let h=document.documentElement.scrollHeight-document.documentElement.clientHeight;
document.getElementById('progressBar').style.width=(s/h)*100+'%';
});
