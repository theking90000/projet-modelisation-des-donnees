function open(element) {
  const load = element.getAttribute("data-load");

  if (load /*&& element.style.display === "none"*/) {
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
  const redirectOnClose = el.getAttribute("data-redirect-on-close");

  const remove = el.hasAttribute("data-popup-remove-on-close");

  const id = el.getAttribute("data-id");
  if (id) {
    document.querySelectorAll(`[data-parent-id="${id}"]`).forEach((el) => {
      if (remove) el.remove();
      else el.style.display = "none";
    });
  }

  if (redirectOnClose) {
    window.location.href = redirectOnClose;
    return;
  }

  if (remove) {
    el.remove();
    return;
  }

  el.style.display = "none";
}
const closePopup = close;

let id = 0; /** Compteur d'élements détectés */
function forElements(selector, callback, name = "data-id") {
  document.querySelectorAll(selector).forEach((element) => {
    try {
      if (element.hasAttribute(name)) return;
      element.setAttribute(name, ++id);

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
      const value = element.getAttribute("data-value");

      const input = document.createElement("input");
      input.hidden = true;
      input.name = element.getAttribute("data-name");
      input.value = value;

      input.setAttribute("data-for-id", element.getAttribute("data-id"));

      element.parentElement.appendChild(input);

      element.addEventListener("click", (e) => {
        const existing = document.querySelector(
          `.ext-select[data-for-id="${element.getAttribute("data-id")}"]`
        );
        if (existing) close(existing);

        const callback = element.getAttribute("data-id");
        const url = element.getAttribute("data-ext-select");

        const select = document.createElement("div");
        select.classList.add("ext-select");
        select.setAttribute("data-for-id", element.getAttribute("data-id"));
        select.setAttribute("data-popup", "1");
        select.setAttribute("data-popup-remove-on-close", "1");
        const parent = element.parentElement.closest("[data-id]");
        if (parent != null) {
          select.setAttribute("data-parent-id", parent.getAttribute("data-id"));
        }

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

  forElements(
    "[data-portal]",
    (element) => {
      const portal = document.querySelector(
        element.getAttribute("data-portal")
      );

      const parent = element.parentElement.closest("[data-id]");
      if (parent != null) {
        element.setAttribute("data-parent-id", parent.getAttribute("data-id"));
        /*element.setAttribute(
          "data-popup-remove-on-close",
          parent.getAttribute("data-popup-remove-on-close")
        );*/
      }

      portal.appendChild(element);
    },
    "data-portal-id"
  );

  forElements("[data-if]", (element) => {
    const target = document.querySelector(element.getAttribute("data-if"));
    if (!target) return;

    function check() {
      let value = element.getAttribute("data-if-value");
      if (!value) value = [];
      else value = value.split("|");

      if (value.includes(target.value)) {
        element.style.display = "block";
      } else {
        element.style.display = "none";
      }
    }

    check();

    target.addEventListener("input", (e) => {
      console.log(e.target.value);
      check();
    });
  });

  forElements("[data-lazy]", (element) => {
    const url = element.getAttribute("data-lazy");

    fetch(url)
      .then((res) => res.text())
      .then((html) => {
        element.innerHTML = html;
        detect();
      });
  });
}

document.addEventListener("DOMContentLoaded", function () {
  detect();
});

var callbackTable = {};

function add_callback(id, fn) {
  callbackTable[id] = fn;
}

function execute_callback(id, value, label = "") {
  console.log("execute_callback", id);
  if (callbackTable[id]) {
    callbackTable[id](value, label);
    return;
  }

  const element = document.querySelector(`[data-id="${id}"]`);

  if (!element) {
    const closeEl = document.querySelector(`[data-close-on-callback="${id}"]`);
    if (closeEl) close(closeEl);

    if (document.querySelector(`[data-reload-on-callback="${id}"]`))
      window.location.reload();

    return;
  }

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

function search_ajax(input, element, page, endpoint) {
  input = typeof input === "string" ? document.querySelector(input) : input;
  element =
    typeof element === "string" ? document.querySelector(element) : element;
  const value = input.value;

  fetch(`${endpoint}&ajax=1&page=${page}&recherche=${value}`)
    .then((res) => res.text())
    .then((html) => {
      element.innerHTML = html;
    });
}

const search_ajax_debounce = createDebouncer(function (
  input,
  element,
  _page,
  endpoint
) {
  search_ajax(input, element, 0, endpoint);
});

function submit_form(form, callback) {
  fetch(form.action, {
    method: "POST",
    body: new FormData(form),
  })
    .then((res) => res.text())
    .then((html) => callback(html));
}
