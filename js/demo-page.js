
(function () {
  'use strict';

  var loader = document.getElementById('loader');
  var backTop = document.getElementById('backTop');
  var canvas = document.getElementById('particle-canvas');

  window.addEventListener('load', function () {
    if (loader) {
      loader.classList.add('is-hidden');
    }
  });

  window.addEventListener('scroll', function () {
    if (backTop) {
      backTop.style.display = window.scrollY > 400 ? 'block' : 'none';
    }
  });

  if (backTop) {
    backTop.addEventListener('click', function () {
      window.scrollTo({ top: 0, behavior: 'smooth' });
    });
  }

  function initThreeScene() {
    var container = document.getElementById('three-container');
    if (!container || typeof THREE === 'undefined') return;

    var scene = new THREE.Scene();
    var camera = new THREE.PerspectiveCamera(
      75,
      window.innerWidth / window.innerHeight,
      0.1,
      2000
    );
    var renderer = new THREE.WebGLRenderer({ alpha: true, antialias: true });
    renderer.setPixelRatio(Math.min(window.devicePixelRatio || 1, 2));
    renderer.setSize(window.innerWidth, window.innerHeight);
    container.appendChild(renderer.domElement);

    camera.position.z = 5;

    var starGeo = new THREE.BufferGeometry();
    var positions = [];
    var i;

    for (i = 0; i < 4000; i++) {
      positions.push(
        (Math.random() - 0.5) * 2000,
        (Math.random() - 0.5) * 2000,
        (Math.random() - 0.5) * 2000
      );
    }

    starGeo.setAttribute('position', new THREE.Float32BufferAttribute(positions, 3));
    var stars = new THREE.Points(
      starGeo,
      new THREE.PointsMaterial({ color: 0xffffff, size: 0.7 })
    );
    scene.add(stars);

    var earth = new THREE.Mesh(
      new THREE.SphereGeometry(1.5, 64, 64),
      new THREE.MeshBasicMaterial({ color: 0x00d4ff, wireframe: true })
    );
    scene.add(earth);

    var ring = new THREE.Mesh(
      new THREE.TorusGeometry(2.2, 0.015, 8, 120),
      new THREE.MeshBasicMaterial({ color: 0x38bdf8, transparent: true, opacity: 0.5 })
    );
    ring.rotation.x = Math.PI / 2.4;
    scene.add(ring);

    function onResize() {
      camera.aspect = window.innerWidth / window.innerHeight;
      camera.updateProjectionMatrix();
      renderer.setSize(window.innerWidth, window.innerHeight);
    }

    window.addEventListener('resize', onResize);

    function animate() {
      requestAnimationFrame(animate);
      earth.rotation.y += 0.003;
      ring.rotation.z += 0.001;
      stars.rotation.y += 0.00015;
      renderer.render(scene, camera);
    }

    animate();
  }

  function initParticleCanvas() {
    if (!canvas) return;

    var ctx = canvas.getContext('2d');
    if (!ctx) return;

    var particles = [];
    var width = 0;
    var height = 0;
    var mouseX = 0;
    var mouseY = 0;
    var i;

    function resize() {
      width = window.innerWidth;
      height = window.innerHeight;
      canvas.width = width;
      canvas.height = height;
    }

    function seedParticles() {
      particles = [];
      for (i = 0; i < 80; i++) {
        particles.push({
          x: Math.random() * width,
          y: Math.random() * height,
          vx: (Math.random() - 0.5) * 0.4,
          vy: (Math.random() - 0.5) * 0.4,
          r: Math.random() * 1.8 + 0.5
        });
      }
    }

    function draw() {
      ctx.clearRect(0, 0, width, height);

      for (i = 0; i < particles.length; i++) {
        var p = particles[i];
        p.x += p.vx + (mouseX - width / 2) * 0.00002;
        p.y += p.vy + (mouseY - height / 2) * 0.00002;

        if (p.x < 0) p.x = width;
        if (p.x > width) p.x = 0;
        if (p.y < 0) p.y = height;
        if (p.y > height) p.y = 0;

        ctx.beginPath();
        ctx.arc(p.x, p.y, p.r, 0, Math.PI * 2);
        ctx.fillStyle = 'rgba(56, 189, 248, 0.65)';
        ctx.fill();
      }

      requestAnimationFrame(draw);
    }

    resize();
    seedParticles();
    draw();

    window.addEventListener('resize', function () {
      resize();
      seedParticles();
    });

    window.addEventListener('mousemove', function (event) {
      mouseX = event.clientX;
      mouseY = event.clientY;
    });
  }

  initThreeScene();
  initParticleCanvas();
})();
