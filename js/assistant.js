
const replies={
oracle:'Oracle Database 19c Administration and Data Guard experience.',
vmware:'VMware ESXi, Hyper-V and Proxmox virtualization.',
aws:'AWS Cloud Architecture certified.',
projects:'Face Recognition, Oracle Migration, Moodle CBT, DSpace.',
contact:'dd.it@gift.edu.pk'
};

function askAI(){
let q=document.getElementById('ai-input').value.toLowerCase();
document.getElementById('ai-answer').innerHTML=replies[q]||'Try: oracle, vmware, aws, projects, contact';
}
