
function startVoiceInput(){
 const SpeechRecognition = window.SpeechRecognition || window.webkitSpeechRecognition;
 if(!SpeechRecognition){ alert('Speech Recognition not supported'); return; }
 const recognition = new SpeechRecognition();
 recognition.onresult = function(event){
   const text = event.results[0][0].transcript;
   const input = document.getElementById('chatInput');
   if(input) input.value = text;
 };
 recognition.start();
}

function speakText(text){
 const utterance = new SpeechSynthesisUtterance(text);
 speechSynthesis.speak(utterance);
}
