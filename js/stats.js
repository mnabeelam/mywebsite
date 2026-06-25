
document.querySelectorAll('.counter').forEach(c=>{
let target=+c.dataset.target;
let n=0;
let i=setInterval(()=>{
n+=Math.ceil(target/100);
if(n>=target){n=target;clearInterval(i);}
c.innerText=n;
},20);
});
