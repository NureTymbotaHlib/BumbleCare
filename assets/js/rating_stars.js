function renderStars() {
  document.querySelectorAll(".rating-stars").forEach(starBlock => {
    const rating = parseFloat(starBlock.dataset.rating || 0);
    const percent = Math.min(100, Math.max(0, (rating / 5) * 100));
    starBlock.style.setProperty("--fill-width", `${percent}%`);
  });
}

document.addEventListener("DOMContentLoaded", renderStars);
window.renderStars = renderStars;
