
function showDCInfo(title){
 const p=document.getElementById('dc-popup');
 p.innerHTML='<h3>'+title+'</h3><p>Infrastructure details panel.</p>';
 p.style.display='block';
}
function closeDCInfo(){
 document.getElementById('dc-popup').style.display='none';
}
