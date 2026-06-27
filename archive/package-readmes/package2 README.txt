PACKAGE 2 INSTALLATION

1. Copy css/animation.css into Package1 css folder.
2. Copy js/*.js into Package1 js folder.
3. Add before </body>:

<div id="progressBar"></div>
<button id="backTop">↑</button>

<script src="https://cdnjs.cloudflare.com/ajax/libs/three.js/r128/three.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/gsap/3.13.0/gsap.min.js"></script>

<script src="js/three-world.js"></script>
<script src="js/gsap.js"></script>
<script src="js/progress.js"></script>
<script src="js/counter.js"></script>
