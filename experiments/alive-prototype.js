/**
 * ALIVE PROTOTYPE 002 — throwaway
 *
 * Question: Can the monogram feel quietly alive before any interaction?
 * Concept: Saccadic pigment (static interference veil).
 *
 * Remove: this file, alive-prototype.css, index.html lines.
 */
(function () {
  const signature = document.querySelector("#scene-02 .portal-signature");
  const monogram = signature?.querySelector("img");

  if (!signature || !monogram || signature.querySelector(".alive-prototype-stack")) {
    return;
  }

  const stack = document.createElement("div");
  stack.className = "alive-prototype-stack";

  monogram.parentNode.insertBefore(stack, monogram);
  stack.appendChild(monogram);

  const veil = monogram.cloneNode(true);
  veil.className = "alive-prototype-veil";
  veil.removeAttribute("alt");
  veil.setAttribute("aria-hidden", "true");
  stack.appendChild(veil);

  document.body.classList.add("alive-prototype");
})();
