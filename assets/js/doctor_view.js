document.addEventListener("DOMContentLoaded", () => {
  const sortSelect = document.getElementById("sort");
  const reviewsContainer = document.getElementById("reviews-container");

  if (sortSelect && reviewsContainer) {
    const reviewCards = Array.from(reviewsContainer.querySelectorAll(".review-card"));

    sortSelect.addEventListener("change", () => {
      const sortValue = sortSelect.value;
      const sorted = [...reviewCards];

      sorted.sort((a, b) => {
        const ratingA = parseFloat(a.querySelector(".rating-stars").dataset.rating || 0);
        const ratingB = parseFloat(b.querySelector(".rating-stars").dataset.rating || 0);

        const parseDate = (str) => {
          const [day, month, year, time] = str.split(/[\s.]+/);
          return new Date(`20${year}-${month}-${day}T${time}:00`);
        };
        const dateA = parseDate(a.querySelector(".review-date").textContent);
        const dateB = parseDate(b.querySelector(".review-date").textContent);

        switch (sortValue) {
          case "rating_asc": return ratingA - ratingB;
          case "rating_desc": return ratingB - ratingA;
          case "date_asc": return dateA - dateB;
          case "date_desc": return dateB - dateA;
          default: return 0;
        }
      });

      reviewsContainer.style.opacity = "0";
      setTimeout(() => {
        reviewsContainer.innerHTML = "";
        sorted.forEach(card => reviewsContainer.appendChild(card));
        reviewsContainer.style.opacity = "1";
      }, 200);
    });
  }
});
