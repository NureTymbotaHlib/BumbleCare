
document.addEventListener("DOMContentLoaded", () => {
  const changeBtn = document.querySelector(".btn.blue[href='#']");
  const passwordModal = document.getElementById("change-password-modal");
  const closeBtn = passwordModal ? passwordModal.querySelector(".close-btn") : null;
  const form = document.getElementById("change-password-form");

  if (!form || !passwordModal) return;

  const defaultPlaceholders = {};
  form.querySelectorAll("input").forEach(i => {
    defaultPlaceholders[i.name] = i.placeholder;
  });

  // Открытие
  if (changeBtn && passwordModal) {
    changeBtn.addEventListener("click", e => {
      e.preventDefault();
      resetFormState();
      passwordModal.classList.remove("hidden");
    });
  }

  // Закрытие по крестику
  if (closeBtn) {
    closeBtn.addEventListener("click", () => {
      resetFormState();
      passwordModal.classList.add("hidden");
    });
  }

  // Закрытие по клику вне
  passwordModal.addEventListener("click", e => {
    if (e.target === passwordModal) {
      resetFormState();
      passwordModal.classList.add("hidden");
    }
  });

  //Отправка формы 
  form.addEventListener("submit", e => {
    e.preventDefault();

    if (!form.checkValidity()) {
      form.reportValidity();
      return;
    }

    const formData = new FormData(form);

    // Очистка прошлых ошибок и плейсхолдеров
    form.querySelectorAll(".password-input-wrapper").forEach(w => w.classList.remove("error"));
    form.querySelectorAll("input").forEach(i => {
      i.classList.remove("error");
      i.placeholder = defaultPlaceholders[i.name];
    });

    fetch("/BumbleCare/handlers/change_password.php", {
      method: "POST",
      body: formData
    })
      .then(res => res.json())
      .then(data => {
        if (data.success) {
          alert("Пароль успішно змінено!");
          resetFormState();
          passwordModal.classList.add("hidden");
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
          form.querySelector("[name='current_password']").value = "";
        }

        else if (err.includes("6 символів")) {
          const wrap = form.querySelector("[name='new_password']").closest(".password-input-wrapper");
          wrap.classList.add("error");
          const input = wrap.querySelector("input");
          input.value = "";
          input.placeholder = "Мінімум 6 символів";
          form.querySelector("[name='confirm_password']").value = "";
        }

        else if (err.includes("Неправильний поточний пароль")) {
          const wrap = form.querySelector("[name='current_password']").closest(".password-input-wrapper");
          wrap.classList.add("error");
          const input = wrap.querySelector("input");
          input.value = "";
          input.placeholder = "Невірний поточний пароль";
          form.querySelectorAll("[name='new_password'], [name='confirm_password']").forEach(i => (i.value = ""));
        }

        else if (err) {
          alert(err);
        }
      })
      .catch(() => alert("Помилка з'єднання з сервером"));
  });

  function resetFormState() {
    form.reset();
    form.querySelectorAll(".password-input-wrapper").forEach(w => w.classList.remove("error"));
    form.querySelectorAll("input").forEach(i => {
      i.placeholder = defaultPlaceholders[i.name];
      i.value = "";
      i.type = "password";
    });

    form.querySelectorAll(".toggle-password img").forEach(icon => {
      icon.src = "/BumbleCare/assets/icons/eye-closed.svg";
    });
  }

});
