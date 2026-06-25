
async function sendAIQuestion(){
 const q=document.getElementById('aiQuestion').value;
 if(!q) return;

 const chat=document.getElementById('aiChat');
 chat.innerHTML += '<div class="ai-message ai-user">'+q+'</div>';

 document.getElementById('aiTyping').style.display='block';

 const fd=new FormData();
 fd.append('question',q);

 const r=await fetch('php/chat-api.php',{method:'POST',body:fd});
 const data=await r.json();

 document.getElementById('aiTyping').style.display='none';

 chat.innerHTML += '<div class="ai-message ai-bot">'+(data.answer||'No response')+'</div>';
 chat.scrollTop=chat.scrollHeight;
}
