
(function () {
  'use strict';

  var loader = document.getElementById('loader');
  var loaderProgress = document.getElementById('loaderProgress');
  var loaderStatus = document.getElementById('loaderStatus');
  var backTop = document.getElementById('backTop');
  var canvas = document.getElementById('particle-canvas');
  var chapterLinks = document.querySelectorAll('.demo-chapter');
  var scrollDots = document.querySelectorAll('.demo-scroll-dot');
  var scrollRailFill = document.getElementById('scrollRailFill');
  var cursorGlow = document.getElementById('cursorGlow');
  var revealEls = document.querySelectorAll('.demo-reveal');
  var reducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
  var glowPos = { x: 0, y: 0 };
  var pageVisible = true;

  var mouse = { x: 0, y: 0, nx: 0, ny: 0 };
  var activeChapter = 'hero';
  var particleBoost = 0;

  var chapters = {
    hero: {
      cam: { x: 0, y: 0.15, z: 9.5 },
      earth: { x: 2.8, y: 0, scale: 1, opacity: 1 },
      racks: 0,
      starSpeed: 0.00014,
      particleBoost: 0.35
    },
    datacenter: {
      cam: { x: -1.2, y: -0.1, z: 5.8 },
      earth: { x: 3.2, y: -0.2, scale: 0.75, opacity: 0.55 },
      racks: 1,
      starSpeed: 0.00008,
      particleBoost: 0.5
    },
    particles: {
      cam: { x: 0.5, y: 0.1, z: 7.5 },
      earth: { x: -2.6, y: 0.15, scale: 0.65, opacity: 0.45 },
      racks: 0.15,
      starSpeed: 0.00038,
      particleBoost: 1
    },
    earth: {
      cam: { x: 0, y: 0, z: 4.2 },
      earth: { x: 0, y: 0, scale: 1.45, opacity: 1 },
      racks: 0,
      starSpeed: 0.00006,
      particleBoost: 0.65
    }
  };

  var state = {
    cam: { x: 0, y: 0.15, z: 9.5 },
    earth: { x: 2.8, y: 0, scale: 1, opacity: 1 },
    racks: 0,
    starSpeed: 0.00014
  };

  function lerp(a, b, t) {
    return a + (b - a) * t;
  }

  function lerpObj(current, target, t) {
    current.x = lerp(current.x, target.x, t);
    current.y = lerp(current.y, target.y, t);
    if (current.z !== undefined && target.z !== undefined) {
      current.z = lerp(current.z, target.z, t);
    }
    if (current.scale !== undefined && target.scale !== undefined) {
      current.scale = lerp(current.scale, target.scale, t);
    }
    if (current.opacity !== undefined && target.opacity !== undefined) {
      current.opacity = lerp(current.opacity, target.opacity, t);
    }
  }

  function setLoaderProgress(value, text) {
    if (loaderProgress) {
      loaderProgress.style.width = Math.min(100, Math.max(0, value)) + '%';
    }
    if (loaderStatus && text) {
      loaderStatus.textContent = text;
    }
  }

  function hideLoader() {
    if (!loader) return;
    loader.classList.add('is-hidden');
  }

  function updateActiveChapter() {
    var mid = window.innerHeight * 0.42;
    var best = 'hero';
    var bestDist = Infinity;

    document.querySelectorAll('.demo-chapter-section').forEach(function (section) {
      var name = section.getAttribute('data-chapter') || 'hero';
      var rect = section.getBoundingClientRect();
      var center = rect.top + rect.height * 0.35;
      var dist = Math.abs(center - mid);
      if (dist < bestDist) {
        bestDist = dist;
        best = name;
      }
    });

    if (best !== activeChapter) {
      activeChapter = best;
      document.body.setAttribute('data-demo-chapter', best);
      chapterLinks.forEach(function (link) {
        link.classList.toggle('is-active', link.getAttribute('data-chapter') === best);
      });
      scrollDots.forEach(function (dot) {
        dot.classList.toggle('is-active', dot.getAttribute('data-chapter') === best);
      });
    }

    var target = chapters[activeChapter] || chapters.hero;
    var t = reducedMotion ? 1 : 0.06;
    lerpObj(state.cam, target.cam, t);
    lerpObj(state.earth, target.earth, t);
    state.racks = lerp(state.racks, target.racks, t);
    state.starSpeed = lerp(state.starSpeed, target.starSpeed, t);
    particleBoost = lerp(particleBoost, target.particleBoost, t);
  }

  function updateScrollRail() {
    var doc = document.documentElement;
    var scrollTop = window.scrollY || doc.scrollTop;
    var maxScroll = Math.max(1, doc.scrollHeight - window.innerHeight);
    var pct = Math.min(100, (scrollTop / maxScroll) * 100);
    if (scrollRailFill) {
      scrollRailFill.style.height = pct + '%';
    }
  }

  function initScrollUi() {
    window.addEventListener('scroll', function () {
      updateActiveChapter();
      updateScrollRail();
      if (backTop) {
        backTop.style.display = window.scrollY > 400 ? 'block' : 'none';
      }
    }, { passive: true });

    scrollDots.forEach(function (dot) {
      dot.addEventListener('click', function () {
        var chapter = dot.getAttribute('data-chapter');
        var target = document.querySelector('.demo-chapter-section[data-chapter="' + chapter + '"]');
        if (target) {
          target.scrollIntoView({ behavior: reducedMotion ? 'auto' : 'smooth', block: 'start' });
        }
      });
    });

    if (backTop) {
      backTop.addEventListener('click', function () {
        window.scrollTo({ top: 0, behavior: reducedMotion ? 'auto' : 'smooth' });
      });
    }

    if ('IntersectionObserver' in window && !reducedMotion) {
      var observer = new IntersectionObserver(function (entries) {
        entries.forEach(function (entry) {
          if (entry.isIntersecting) {
            entry.target.classList.add('is-visible');
          }
        });
      }, { threshold: 0.15, rootMargin: '0px 0px -8% 0px' });

      revealEls.forEach(function (el) {
        observer.observe(el);
      });
    } else {
      revealEls.forEach(function (el) {
        el.classList.add('is-visible');
      });
    }

    updateActiveChapter();
    updateScrollRail();
  }

  function initMouse() {
    window.addEventListener('mousemove', function (event) {
      mouse.x = event.clientX;
      mouse.y = event.clientY;
      mouse.nx = (event.clientX / window.innerWidth - 0.5) * 2;
      mouse.ny = (event.clientY / window.innerHeight - 0.5) * 2;
      glowPos.x = event.clientX;
      glowPos.y = event.clientY;
    }, { passive: true });

    if (cursorGlow && !reducedMotion) {
      function moveGlow() {
        cursorGlow.style.transform = 'translate3d(' + glowPos.x + 'px, ' + glowPos.y + 'px, 0)';
        requestAnimationFrame(moveGlow);
      }
      moveGlow();
    }
  }

  function initVisibility() {
    document.addEventListener('visibilitychange', function () {
      pageVisible = !document.hidden;
    });
  }

  function createRack(color, x) {
    var group = new THREE.Group();
    var body = new THREE.Mesh(
      new THREE.BoxGeometry(0.85, 2.4, 0.65),
      new THREE.MeshStandardMaterial({
        color: 0x0f172a,
        metalness: 0.72,
        roughness: 0.35,
        emissive: 0x020617,
        emissiveIntensity: 0.2
      })
    );
    group.add(body);

    var trim = new THREE.Mesh(
      new THREE.BoxGeometry(0.88, 2.44, 0.68),
      new THREE.MeshBasicMaterial({ color: color, transparent: true, opacity: 0.12, wireframe: true })
    );
    group.add(trim);

    var leds = [];
    var i;
    for (i = 0; i < 10; i++) {
      var led = new THREE.Mesh(
        new THREE.BoxGeometry(0.55, 0.045, 0.02),
        new THREE.MeshBasicMaterial({ color: color, transparent: true, opacity: 0.85 })
      );
      led.position.set(0, -1 + i * 0.22, 0.34);
      led.userData.phase = Math.random() * Math.PI * 2;
      group.add(led);
      leds.push(led);
    }

    group.position.set(x, -1.8, -1.5);
    group.userData.leds = leds;
    return group;
  }

  function initThreeScene(onReady) {
    var container = document.getElementById('three-container');
    if (!container || typeof THREE === 'undefined') {
      setLoaderProgress(100, 'WebGL unavailable — showing content only.');
      if (onReady) onReady();
      return null;
    }

    var scene = new THREE.Scene();
    var camera = new THREE.PerspectiveCamera(60, window.innerWidth / window.innerHeight, 0.1, 2500);
    var renderer = new THREE.WebGLRenderer({ alpha: true, antialias: true, powerPreference: 'high-performance' });
    renderer.setPixelRatio(Math.min(window.devicePixelRatio || 1, 2));
    renderer.setSize(window.innerWidth, window.innerHeight);
    renderer.setClearColor(0x000000, 0);
    container.appendChild(renderer.domElement);

    camera.position.set(state.cam.x, state.cam.y, state.cam.z);

    scene.add(new THREE.AmbientLight(0x334155, 0.65));
    var keyLight = new THREE.DirectionalLight(0xffffff, 1.15);
    keyLight.position.set(6, 4, 8);
    scene.add(keyLight);
    var rimLight = new THREE.PointLight(0x38bdf8, 0.8, 40);
    rimLight.position.set(-6, 2, 4);
    scene.add(rimLight);

    var loadingManager = new THREE.LoadingManager(
      function () {
        setLoaderProgress(100, 'Ready — scroll to begin');
        if (onReady) onReady();
      },
      function (_url, loaded, total) {
        var pct = total ? Math.round((loaded / total) * 100) : 0;
        setLoaderProgress(pct, 'Loading textures ' + pct + '%');
      },
      function () {
        setLoaderProgress(100, 'Ready — scroll to begin');
        if (onReady) onReady();
      }
    );

    var starGeo = new THREE.BufferGeometry();
    var positions = [];
    var starColors = [];
    var i;
    for (i = 0; i < 5000; i++) {
      positions.push(
        (Math.random() - 0.5) * 2200,
        (Math.random() - 0.5) * 2200,
        (Math.random() - 0.5) * 2200
      );
      var tint = Math.random();
      starColors.push(0.7 + tint * 0.3, 0.85 + tint * 0.15, 1);
    }
    starGeo.setAttribute('position', new THREE.Float32BufferAttribute(positions, 3));
    starGeo.setAttribute('color', new THREE.Float32BufferAttribute(starColors, 3));
    var stars = new THREE.Points(
      starGeo,
      new THREE.PointsMaterial({ size: 0.85, vertexColors: true, transparent: true, opacity: 0.9, depthWrite: false })
    );
    scene.add(stars);

    var earthGroup = new THREE.Group();
    var textureLoader = new THREE.TextureLoader(loadingManager);
    var earthGeo = new THREE.SphereGeometry(1.45, 64, 64);

    var earthMat = new THREE.MeshPhongMaterial({
      color: 0x4488cc,
      specular: new THREE.Color(0x333333),
      shininess: 12,
      transparent: true,
      opacity: 1
    });

    textureLoader.load(
      'https://threejs.org/examples/textures/planets/earth_atmos_2048.jpg',
      function (map) {
        earthMat.map = map;
        earthMat.needsUpdate = true;
      }
    );
    textureLoader.load(
      'https://threejs.org/examples/textures/planets/earth_normal_2048.jpg',
      function (bump) {
        earthMat.bumpMap = bump;
        earthMat.bumpScale = 0.035;
        earthMat.needsUpdate = true;
      }
    );
    textureLoader.load(
      'https://threejs.org/examples/textures/planets/earth_specular_2048.jpg',
      function (spec) {
        earthMat.specularMap = spec;
        earthMat.needsUpdate = true;
      }
    );

    var earth = new THREE.Mesh(earthGeo, earthMat);
    earthGroup.add(earth);

    var atmosphere = new THREE.Mesh(
      new THREE.SphereGeometry(1.52, 64, 64),
      new THREE.MeshBasicMaterial({
        color: 0x38bdf8,
        transparent: true,
        opacity: 0.14,
        side: THREE.BackSide,
        depthWrite: false
      })
    );
    earthGroup.add(atmosphere);

    var glow = new THREE.Mesh(
      new THREE.SphereGeometry(1.62, 48, 48),
      new THREE.MeshBasicMaterial({
        color: 0x06b6d4,
        transparent: true,
        opacity: 0.06,
        side: THREE.BackSide,
        depthWrite: false
      })
    );
    earthGroup.add(glow);

    var ring = new THREE.Mesh(
      new THREE.TorusGeometry(2.15, 0.012, 8, 160),
      new THREE.MeshBasicMaterial({ color: 0xec4899, transparent: true, opacity: 0.45 })
    );
    ring.rotation.x = Math.PI / 2.35;
    earthGroup.add(ring);

    earthGroup.position.set(state.earth.x, state.earth.y, 0);
    scene.add(earthGroup);

    var rackGroup = new THREE.Group();
    rackGroup.add(createRack(0xf59e0b, -2.4));
    rackGroup.add(createRack(0x3b82f6, -0.8));
    rackGroup.add(createRack(0x22c55e, 0.8));
    rackGroup.add(createRack(0xa855f7, 2.4));
    scene.add(rackGroup);

    var floor = new THREE.Mesh(
      new THREE.PlaneGeometry(14, 8),
      new THREE.MeshBasicMaterial({
        color: 0x06b6d4,
        transparent: true,
        opacity: 0.06,
        side: THREE.DoubleSide
      })
    );
    floor.rotation.x = -Math.PI / 2;
    floor.position.y = -3.1;
    scene.add(floor);

    function onResize() {
      camera.aspect = window.innerWidth / window.innerHeight;
      camera.updateProjectionMatrix();
      renderer.setSize(window.innerWidth, window.innerHeight);
    }
    window.addEventListener('resize', onResize);

    var clock = 0;
    function animate() {
      requestAnimationFrame(animate);

      if (!pageVisible) {
        renderer.render(scene, camera);
        return;
      }

      if (reducedMotion) {
        camera.position.set(state.cam.x, state.cam.y, state.cam.z);
        camera.lookAt(state.earth.x * 0.35, 0, 0);
        earthGroup.position.set(state.earth.x, state.earth.y, 0);
        earthGroup.scale.setScalar(state.earth.scale);
        earthMat.opacity = state.earth.opacity;
        rackGroup.visible = state.racks > 0.02;
        renderer.render(scene, camera);
        return;
      }

      clock += 0.016;
      updateActiveChapter();

      camera.position.x = lerp(camera.position.x, state.cam.x + mouse.nx * 0.35, 0.04);
      camera.position.y = lerp(camera.position.y, state.cam.y - mouse.ny * 0.2, 0.04);
      camera.position.z = lerp(camera.position.z, state.cam.z, 0.04);
      camera.lookAt(state.earth.x * 0.35, 0, 0);

      earth.rotation.y += 0.0018 + mouse.nx * 0.0008;
      earth.rotation.x = lerp(earth.rotation.x, mouse.ny * 0.12, 0.05);
      ring.rotation.z += 0.0012;

      earthGroup.position.x = lerp(earthGroup.position.x, state.earth.x, 0.05);
      earthGroup.position.y = lerp(earthGroup.position.y, state.earth.y, 0.05);
      var scale = state.earth.scale;
      earthGroup.scale.set(scale, scale, scale);
      earthGroup.visible = state.earth.opacity > 0.05;
      earthMat.opacity = state.earth.opacity;

      stars.rotation.y += state.starSpeed;

      rackGroup.visible = state.racks > 0.02;
      rackGroup.children.forEach(function (rack, index) {
        rack.position.y = -1.8 + Math.sin(clock * 1.2 + index) * 0.04 * state.racks;
        rack.userData.leds.forEach(function (led) {
          led.material.opacity = 0.35 + ((Math.sin(clock * 3 + led.userData.phase) + 1) * 0.5) * 0.65 * state.racks;
        });
      });

      renderer.render(scene, camera);
    }

    animate();
    return { scene: scene, renderer: renderer };
  }

  function initParticleCanvas() {
    if (!canvas || reducedMotion) return null;

    var ctx = canvas.getContext('2d');
    if (!ctx) return null;

    var particles = [];
    var width = 0;
    var height = 0;
    var count = 100;
    var linkDistance = 130;
    var i;

    function resize() {
      width = window.innerWidth;
      height = window.innerHeight;
      canvas.width = width;
      canvas.height = height;
    }

    function seedParticles() {
      particles = [];
      for (i = 0; i < count; i++) {
        particles.push({
          x: Math.random() * width,
          y: Math.random() * height,
          vx: (Math.random() - 0.5) * 0.5,
          vy: (Math.random() - 0.5) * 0.5,
          r: Math.random() * 1.6 + 0.6
        });
      }
    }

    function draw() {
      if (!pageVisible) {
        requestAnimationFrame(draw);
        return;
      }

      ctx.clearRect(0, 0, width, height);
      var boost = 0.4 + particleBoost * 0.9;
      var mx = mouse.x;
      var my = mouse.y;

      for (i = 0; i < particles.length; i++) {
        var p = particles[i];
        p.x += (p.vx + (mx - width / 2) * 0.000015) * boost;
        p.y += (p.vy + (my - height / 2) * 0.000015) * boost;
        if (p.x < 0) p.x = width;
        if (p.x > width) p.x = 0;
        if (p.y < 0) p.y = height;
        if (p.y > height) p.y = 0;
      }

      for (i = 0; i < particles.length; i++) {
        for (var j = i + 1; j < particles.length; j++) {
          var a = particles[i];
          var b = particles[j];
          var dx = a.x - b.x;
          var dy = a.y - b.y;
          var dist = Math.sqrt(dx * dx + dy * dy);
          if (dist < linkDistance) {
            var alpha = (1 - dist / linkDistance) * 0.38 * boost;
            var lineHue = activeChapter === 'particles' ? '168, 85, 247' : activeChapter === 'earth' ? '74, 222, 128' : '56, 189, 248';
            ctx.strokeStyle = 'rgba(' + lineHue + ', ' + alpha + ')';
            ctx.lineWidth = 1;
            ctx.beginPath();
            ctx.moveTo(a.x, a.y);
            ctx.lineTo(b.x, b.y);
            ctx.stroke();
          }
        }
      }

      for (i = 0; i < particles.length; i++) {
        var pt = particles[i];
        var dm = Math.hypot(pt.x - mx, pt.y - my);
        var glow = dm < 140 ? 1 - dm / 140 : 0;
        ctx.beginPath();
        ctx.arc(pt.x, pt.y, pt.r + glow * 1.5, 0, Math.PI * 2);
        ctx.fillStyle = 'rgba(' + Math.round(56 + glow * 180) + ', ' + Math.round(189 - glow * 40) + ', 248, ' + (0.45 + glow * 0.5) + ')';
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

    return true;
  }

  initMouse();
  initScrollUi();
  initVisibility();
  initParticleCanvas();

  setLoaderProgress(8, 'Starting WebGL renderer…');
  initThreeScene(function () {
    document.body.classList.add('is-ready');
    window.setTimeout(hideLoader, reducedMotion ? 0 : 450);
  });

  if (typeof THREE === 'undefined') {
    window.setTimeout(function () {
      setLoaderProgress(100, 'Three.js failed to load.');
      hideLoader();
    }, 1200);
  }
})();
