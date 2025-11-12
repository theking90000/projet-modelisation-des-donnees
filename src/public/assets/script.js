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
  if (el.hasAttribute("data-popup-remove-on-close")) {
    el.remove();
    return;
  }
  el.style.display = "none";
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
  forElements("[data-popup]", (popup) => {
    popup.addEventListener("click", (e) => {
      if (e.target !== popup) return;

      // Click dans le coin supérieur droit
      if (popup.offsetWidth - e.offsetX <= 60 && e.offsetY <= 25) {
        close(e.target);
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
        select.setAttribute("data-popup", "1");
        select.setAttribute("data-popup-remove-on-close", "1");

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
  });
}

document.addEventListener("DOMContentLoaded", function () {
  detect();
});

function execute_callback(id, value, label = "") {
  const element = document.querySelector(`[data-id="${id}"]`);

  if (!element) return;

  if (element.hasAttribute("data-ext-select")) {
    element.innerHTML = label || value;
    const input = document.querySelector(`input[data-for-id="${id}"]`);
    if (input) input.value = value;

    const select = document.querySelector(`.ext-select[data-for-id="${id}"]`);
    if (select) close(select);
  }
}

function createDebouncer(callback, time = 500) {
  let timeout;

  return function () {
    clearTimeout(timeout);

    timeout = setTimeout(() => {
      callback.apply(this, arguments);
    }, time);
  };
}

let pageOverride;

function search_instrument_setpage(page) {
  pageOverride = page;
  return false;
}

function search_instrument(element, page, endpoint) {
  page = pageOverride || page;
  const value = element.value;

  fetch(`${endpoint}&ajax=1&page=${page}&recherche=${value}`)
    .then((res) => res.text())
    .then((html) => {
      document.querySelector("#search_instrument").innerHTML = html;
    });
}

const search_instrument_debounce = createDebouncer(function () {
  pageOverride = 0;
  search_instrument.apply(this, arguments);
});
