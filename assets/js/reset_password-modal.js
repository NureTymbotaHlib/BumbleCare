document.addEventListener("DOMContentLoaded", () => {
  const forgotLink = document.querySelector(".forgot-link");
  const resetModal = document.getElementById("resetModal");
  const closeX = resetModal?.querySelector(".close-btn");
  const closeBtn = document.getElementById("closeResetModal");
  const sendBtn = document.getElementById("sendResetLink");
  const emailInput = document.getElementById("resetEmail");

  if (!forgotLink || !resetModal) return;

  function openModal(modal) {
    if (!modal) return;
    modal.classList.remove("hidden");
  }

  function closeModal(modal) {
    if (!modal) return;
    modal.classList.add("hidden");
  }

  forgotLink.addEventListener("click", e => {
    e.preventDefault();
    openModal(resetModal);
    emailInput.value = "";
  });

  [closeX, closeBtn].forEach(btn => {
    if (!btn) return;
    btn.addEventListener("click", () => closeModal(resetModal));
  });

  resetModal.addEventListener("click", e => {
    if (e.target === resetModal) closeModal(resetModal);
  });

  sendBtn.addEventListener("click", async () => {
    const email = emailInput.value.trim();

    if (!email) {
      showPopupMessage("Введіть email.", "error");
      return;
    }

    try {
      const res = await fetch("/BumbleCare/handlers/send_reset_email.php", {
        method: "POST",
        headers: { "Content-Type": "application/x-www-form-urlencoded" },
        body: `email=${encodeURIComponent(email)}`
      });

      const data = await res.json();

      if (data.status === "success") {
        showPopupMessage("Лист з посиланням для відновлення паролю надіслано!", "success");
        emailInput.value = "";
        setTimeout(() => closeModal(resetModal), 700);
      } else if (data.status === "not_found") {
        showPopupMessage("Користувача з таким email не знайдено.", "error");
      } else {
        showPopupMessage("Сталася помилка при надсиланні листа.", "error");
      }
    } catch (err) {
      console.error(err);
      showPopupMessage("Не вдалося підключитися до сервера.", "error");
    }
  });
});
