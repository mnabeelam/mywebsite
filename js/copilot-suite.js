
const knowledge={
oracle:'Oracle Database 19c, Data Guard, HA',
vmware:'ESXi, Hyper-V, Proxmox',
ai:'Face Recognition, Smart Campus',
linux:'Apache, Squid, Monitoring'
};
function askCopilot(){
let q=document.getElementById('copilotQuestion').value.toLowerCase();
document.getElementById('copilotAnswer').innerHTML=knowledge[q]||'Knowledge not found';
}
