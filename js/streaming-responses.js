
function streamResponse(text,targetId='chatBox'){
 const target=document.getElementById(targetId);
 let i=0;
 const timer=setInterval(()=>{
   if(i<text.length){
     target.innerHTML += text.charAt(i);
     i++;
   }else{
     clearInterval(timer);
   }
 },20);
}
