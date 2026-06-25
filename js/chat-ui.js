
function sendMessage(){
const q=document.getElementById('chatInput').value;
document.getElementById('chatBox').innerHTML += '<div><b>You:</b> '+q+'</div>';
document.getElementById('chatInput').value='';
}
