document.addEventListener("DOMContentLoaded", () => {
  document.querySelectorAll(".toggle-password").forEach(toggleBtn => {
    const passwordInput = toggleBtn.parentElement.querySelector("input");
    const eyeIcon = toggleBtn.querySelector("img");

    if (!passwordInput || !eyeIcon) return;

    toggleBtn.addEventListener("click", () => {
      const isPassword = passwordInput.type === "password";
      passwordInput.type = isPassword ? "text" : "password";

      eyeIcon.src = isPassword
        ? "/BumbleCare/assets/icons/eye-open.svg"
        : "/BumbleCare/assets/icons/eye-closed.svg";
    });
  });
});
