
// Clouds layer
const cloudTexture = new THREE.TextureLoader().load('assets/textures/clouds.png');

if(typeof earth !== 'undefined'){
 const cloudGeometry = new THREE.SphereGeometry(5.05,64,64);
 const cloudMaterial = new THREE.MeshStandardMaterial({
   map: cloudTexture,
   transparent: true,
   opacity: 0.5
 });
 const clouds = new THREE.Mesh(cloudGeometry, cloudMaterial);
 scene.add(clouds);

 function animateClouds(){
   clouds.rotation.y += 0.001;
 }
}
