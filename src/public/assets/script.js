function open(element) {
  const load = element.getAttribute("data-load");

  if (load && element.style.display === "none") {
    fetch(load)
      .then((res) => res.text())
      .then((html) => {
        element.innerHTML = html;
        element.style.display = "block";
        detect();
      });
  } else {
    element.style.display =
      element.style.display === "block" ? "none" : "block";
  }
}

function close(el) {
  element.style.display = "none";
}

let id = 0; /** Compteur d'élements détectés */
function forElements(selector, callback) {
  document.querySelectorAll(selector).forEach((element) => {
    try {
      if (element.hasAttribute("data-id")) return;
      element.setAttribute("data-id", ++id);

      callback(element);
    } catch (e) {
      console.error(e);
    }
  });
}

function detect() {
  // Détecter tous les triggers de popup
  forElements("[data-open]", (trigger) => {
    trigger.addEventListener("click", (e) => {
      e.preventDefault();

      const element = document.querySelector(trigger.getAttribute("data-open"));

      if (!element) return;

      open(element);
    });
  });

  // Détecter tous les popups
  forElements(".popup", (popup) => {
    popup.addEventListener("click", (e) => {
      if (e.target !== popup) return;

      // Click dans le coin supérieur droit
      if (popup.offsetWidth - e.offsetX <= 60 && e.offsetY <= 25) {
        popup.style.display = "none";
      }
    });
  });

  // Détecter tous les selecteur de données externes
  forElements("[data-ext-select]", (element) => {
    try {
      const input = document.createElement("input");
      input.hidden = true;
      input.name = element.getAttribute("data-name");
      input.value = null;

      input.setAttribute("data-for-id", element.getAttribute("data-id"));

      input.after(element);

      element.parentElement.addEventListener("click", (e) => {
        const callback = element.getAttribute("data-id");
        const url = element.getAttribute("data-ext-select");

        const select = document.createElement("div");
        select.classList.add("ext-select");
        select.setAttribute("data-for-id", element.getAttribute("data-id"));

        document.body.appendChild(select);

        fetch(url + "?callback_id=" + encodeURIComponent(callback))
          .then((res) => res.text())
          .then((res) => {
            select.innerHTML = res;
            detect();
          });
      });
    } catch (e) {
      console.error(e);
    }
    // element.addEventListener('click')
  });
}

document.addEventListener("DOMContentLoaded", function () {
  detect();
});
