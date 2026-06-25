
// Earth texture loader
const textureLoader = new THREE.TextureLoader();
const earthTexture = textureLoader.load('assets/textures/earth_day.jpg');
if(typeof earth!=='undefined'){
 earth.material = new THREE.MeshStandardMaterial({map:earthTexture});
}
