document.addEventListener("DOMContentLoaded", () => {
  const form = document.querySelector(".verify-form");

	if (!form) return;
	
  form.addEventListener("submit", async (e) => {
    e.preventDefault();

    const formData = new FormData(form);

    try {
      const res = await fetch("/BumbleCare/handlers/verify_2fa_handler.php", {
        method: "POST",
        body: formData
      });

      const data = await res.json();

      if (data.success) {
        showPopupMessage("Вхід підтверджено!", "success");
        setTimeout(() => {
          window.location.href = data.redirect;
        }, 1000);
        return;
      }

      showPopupMessage(data.error, "error");

    } catch {
      showPopupMessage("Помилка сервера", "error");
    }
  });
});
