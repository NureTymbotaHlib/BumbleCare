document.addEventListener("DOMContentLoaded", () => {
  const form = document.querySelector(".reset-form");
  if (!form) return;

  const defaultPlaceholders = {};
  form.querySelectorAll("input").forEach(i => {
    defaultPlaceholders[i.name] = i.placeholder;
  });

  form.addEventListener("submit", async e => {
    e.preventDefault();

    const formData = new FormData(form);

    form.querySelectorAll(".password-input-wrapper").forEach(w => w.classList.remove("error"));
    form.querySelectorAll("input").forEach(i => {
      i.classList.remove("error");
      i.placeholder = defaultPlaceholders[i.name];
    });

    try {
      const response = await fetch("/BumbleCare/handlers/reset_password_handler.php", {
        method: "POST",
        body: formData
      });
      const data = await response.json();

      if (data.success) {
        showPopupMessage("Пароль успішно оновлено!", "success");
        setTimeout(() => {
          window.location.href = "/BumbleCare/pages/login.php";
        }, 1500);
        return;
      }

      const err = data.error || "";

      if (err.includes("співпадають")) {
        const newWrap = form.querySelector("[name='new_password']").closest(".password-input-wrapper");
        const confWrap = form.querySelector("[name='confirm_password']").closest(".password-input-wrapper");
        [newWrap, confWrap].forEach(w => {
          w.classList.add("error");
          const input = w.querySelector("input");
          input.value = "";
          input.placeholder = "Паролі не співпадають";
        });
      }

      else if (err.includes("6 символів")) {
        const wrap = form.querySelector("[name='new_password']").closest(".password-input-wrapper");
        wrap.classList.add("error");
        const input = wrap.querySelector("input");
        input.value = "";
        input.placeholder = "Довжина пароля мінімум 6 символів";
        form.querySelector("[name='confirm_password']").value = "";
      }

      else if (err.includes("Недійсний") || err.includes("Посилання")) {
        showPopupMessage(err, "error");
        setTimeout(() => {
          window.location.href = "/BumbleCare/index.php";
        }, 2000);
      }

      else if (err) {
        showPopupMessage(err, "error");
        setTimeout(() => {
          window.location.href = "/BumbleCare/index.php";
        }, 2000);
      }

    } catch {
      showPopupMessage("Помилка з'єднання з сервером", "error");
    }
  });
});
