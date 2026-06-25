
// Lighting + OrbitControls
if(typeof scene !== 'undefined' && typeof camera !== 'undefined' && typeof renderer !== 'undefined'){

const ambientLight = new THREE.AmbientLight(0xffffff,0.6);
scene.add(ambientLight);

const sunLight = new THREE.DirectionalLight(0xffffff,1);
sunLight.position.set(10,5,10);
scene.add(sunLight);

if(typeof THREE.OrbitControls !== 'undefined'){
 const controls = new THREE.OrbitControls(camera,renderer.domElement);
 controls.enableDamping = true;
 controls.enableZoom = true;
}

console.log('Lighting + OrbitControls Loaded');
}
