
const data={
oracle:'Oracle Database 19c, Data Guard, HA',
vmware:'VMware ESXi, Hyper-V, Proxmox',
projects:'Face Recognition, Oracle Migration, Moodle CBT',
experience:'18+ years in IT Infrastructure'
};
function runSearch(){
let q=document.getElementById('searchBox').value.toLowerCase();
document.getElementById('searchResult').innerHTML=data[q]||'No result';
}
function startCommand(){
const SR=window.SpeechRecognition||window.webkitSpeechRecognition;
if(!SR){alert('Not supported');return;}
let r=new SR();
r.start();
r.onresult=e=>{
document.getElementById('searchBox').value=e.results[0][0].transcript;
runSearch();
}
}
