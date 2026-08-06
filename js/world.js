gsap.registerPlugin(ScrollTrigger);

console.log("NoelClark.com initialized");

const heroTitle = document.querySelector(".hero-title");

gsap.set(heroTitle, {
  opacity: 1,
  y: 0,
});
