
const kb={
oracle:'Oracle Database 19c, Data Guard and HA experience.',
vmware:'VMware ESXi, Hyper-V and Proxmox virtualization.',
projects:'Face Recognition, Oracle Migration, Moodle CBT, DSpace Repository.',
certifications:'Oracle, AWS Cloud Architecture and CCNA training.',
experience:'18+ years in IT Infrastructure and Digital Transformation.'
};

document.addEventListener('DOMContentLoaded',()=>{
const i=document.getElementById('copilotInput');
const o=document.getElementById('copilotOutput');
if(i){
i.addEventListener('change',()=>{
let q=i.value.toLowerCase();
o.innerHTML=kb[q]||'Try: oracle, vmware, projects, certifications, experience';
});
}
});
