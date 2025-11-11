document.addEventListener("DOMContentLoaded", function () {
  const triggers = document.querySelectorAll("[data-open]");

  triggers.forEach((trigger) => {
    trigger.addEventListener("click", (e) => {
      e.preventDefault();

      const element = document.querySelector(trigger.getAttribute("data-open"));

      if (!element) return;

      element.style.display =
        element.style.display === "block" ? "none" : "block";
    });
  });
});

function close(el) {
  element.style.display = "none";
}
