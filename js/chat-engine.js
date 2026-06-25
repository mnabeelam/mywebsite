
async function askKnowledgeAI(question){
 const fd = new FormData();
 fd.append('question',question);

 const r = await fetch('php/chat-api.php',{
   method:'POST',
   body:fd
 });
 return await r.json();
}
