document.addEventListener("DOMContentLoaded", function () {
  const triggers = document.querySelectorAll("[data-open]");

  triggers.forEach((trigger) => {
    trigger.addEventListener("click", (e) => {
      e.preventDefault();

      const element = document.querySelector(trigger.getAttribute("data-open"));

      if (!element) return;

      const load = element.getAttribute("data-load");

      if (load && element.style.display === "none") {
        fetch(load)
          .then((res) => res.text())
          .then((html) => {
            element.innerHTML = html;
            element.style.display = "block";
          });
      } else {
        element.style.display =
          element.style.display === "block" ? "none" : "block";
      }
    });
  });

  const popups = document.querySelectorAll(".popup");

  popups.forEach((popup) => {
    popup.addEventListener("click", (e) => {
      if (e.target !== popup) return;

      let x = e.offsetX;
      let y = e.offsetY;

      // Click dans le coin supérieur droit
      if (popup.offsetWidth - e.offsetX <= 60 && e.offsetY <= 25) {
        popup.style.display = "none";
      }
      // console.log(x, y, e.target.offsetWidth, e.target.offsetHeight);
    });
  });
});

function close(el) {
  element.style.display = "none";
}
