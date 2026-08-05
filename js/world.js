gsap.from(".hero-content", {
  opacity: 0,
  y: 80,
  duration: 1.5,
  ease: "power3.out",
});

document.addEventListener("mousemove", (e) => {
  document.body.style.setProperty("--mouseX", e.clientX + "px");
  document.body.style.setProperty("--mouseY", e.clientY + "px");
});
