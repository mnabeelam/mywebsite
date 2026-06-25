
async function askAI(msg){
 const r = await fetch('php/ai-chat.php',{
   method:'POST',
   headers:{'Content-Type':'application/json'},
   body:JSON.stringify({message:msg})
 });
 return await r.json();
}
