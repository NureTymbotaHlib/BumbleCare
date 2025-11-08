function showPopupMessage(text, type = "info") {
  const msg = document.createElement("div");
  msg.className = `popup-msg ${type}`;
  msg.textContent = text;
  document.body.appendChild(msg);

  setTimeout(() => msg.classList.add("visible"), 10);

  setTimeout(() => {
    msg.classList.remove("visible");
    setTimeout(() => msg.remove(), 400);
  }, 2000);
}
