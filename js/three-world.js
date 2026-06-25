
// Package 4B - Three.js Universe
const container = document.getElementById("three-container");
if(container){
const scene=new THREE.Scene();
const camera=new THREE.PerspectiveCamera(75,window.innerWidth/window.innerHeight,0.1,1000);
const renderer=new THREE.WebGLRenderer({alpha:true,antialias:true});
renderer.setSize(window.innerWidth,window.innerHeight);
container.appendChild(renderer.domElement);

camera.position.z=5;

// Stars
const starGeo=new THREE.BufferGeometry();
const starCount=5000;
const positions=[];
for(let i=0;i<starCount;i++){
positions.push((Math.random()-0.5)*2000,(Math.random()-0.5)*2000,(Math.random()-0.5)*2000);
}
starGeo.setAttribute('position',new THREE.Float32BufferAttribute(positions,3));
const starMat=new THREE.PointsMaterial({color:0xffffff,size:0.7});
const stars=new THREE.Points(starGeo,starMat);
scene.add(stars);

// Earth wireframe
const earth=new THREE.Mesh(
new THREE.SphereGeometry(1.5,64,64),
new THREE.MeshBasicMaterial({color:0x00d4ff,wireframe:true})
);
scene.add(earth);

function animate(){
requestAnimationFrame(animate);
earth.rotation.y+=0.003;
stars.rotation.y+=0.0002;
renderer.render(scene,camera);
}
animate();
}
