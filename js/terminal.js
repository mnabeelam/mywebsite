
const commands={
help:'whoami, projects, experience, oracle, aws, vmware, skills, contact',
whoami:'Mirza Nabeel Ahmed - Deputy Director IT - GIFT University',
projects:'Oracle 19c Migration, Face Recognition, Moodle CBT, DSpace',
experience:'18+ years in IT Infrastructure and Digital Transformation',
oracle:'Oracle Database 19c Administration',
aws:'AWS Cloud Architecture',
vmware:'VMware ESXi, Hyper-V, Proxmox',
skills:'Networking, Security, Virtualization, Linux',
contact:'dd.it@gift.edu.pk'
};

document.addEventListener('DOMContentLoaded',()=>{
const inp=document.getElementById('terminalInput');
const out=document.getElementById('terminalOutput');
if(inp){
inp.addEventListener('keydown',(e)=>{
if(e.key==='Enter'){
let cmd=inp.value.toLowerCase();
out.innerHTML=commands[cmd] || 'Unknown command. Type help';
inp.value='';
}
});
}
});
