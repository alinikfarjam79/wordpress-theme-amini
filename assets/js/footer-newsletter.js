(function () {
  "use strict";

  function showMessage(element, message, type) {
    element.textContent = message;
    element.classList.remove("is-success", "is-error");
    element.classList.add("is-visible", type === "success" ? "is-success" : "is-error");
  }

  document.addEventListener("submit", async function (event) {
    var form = event.target.closest("[data-newsletter-form], .footer-newsletter-form");
    if (!form || typeof myThemeNewsletter === "undefined") {
      return;
    }

    event.preventDefault();

    var message = form.querySelector("[data-newsletter-message]");
    if (!message) {
      message = document.createElement("span");
      message.className = "footer-newsletter-form__message";
      message.setAttribute("data-newsletter-message", "");
      message.setAttribute("role", "status");
      message.setAttribute("aria-live", "polite");
      form.appendChild(message);
    }
    var button = form.querySelector('button[type="submit"]');
    var nameInput = form.querySelector('input[name="full_name"], input[type="text"]');
    var phoneInput = form.querySelector('input[name="phone"], input[type="tel"]');
    var data = new FormData(form);
    data.set("full_name", nameInput ? nameInput.value : "");
    data.set("phone", phoneInput ? phoneInput.value : "");
    data.append("action", "my_theme_newsletter_submit");
    data.append("nonce", myThemeNewsletter.nonce);

    button.disabled = true;

    try {
      var response = await fetch(myThemeNewsletter.ajaxUrl, {
        method: "POST",
        credentials: "same-origin",
        body: data,
      });
      var result = await response.json();

      if (!result.success) {
        throw new Error(result.data && result.data.message ? result.data.message : "ثبت اطلاعات انجام نشد.");
      }

      showMessage(message, result.data.message, "success");
      form.reset();
    } catch (error) {
      showMessage(message, error.message || "خطایی رخ داد. دوباره تلاش کنید.", "error");
    } finally {
      button.disabled = false;
    }
  });
})();
