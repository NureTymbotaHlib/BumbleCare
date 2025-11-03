document.addEventListener("DOMContentLoaded", () => {
  const form = document.getElementById("searchForm");
  const resultsContainer = document.getElementById("resultsContainer");
  const citySelect = document.getElementById("citySelect");
  const clinicSelect = document.getElementById("clinicSelect");
  const allClinicOptions = Array.from(clinicSelect.querySelectorAll("option[data-city]"));
  const sortSelect = document.getElementById("sortSelect");

  citySelect.addEventListener("change", () => {
    const selectedCity = citySelect.value;
    const currentClinic = clinicSelect.value;
    const currentClinicOption = allClinicOptions.find(opt => opt.value === currentClinic);

    clinicSelect.innerHTML = '<option value="">Усі</option>';
    allClinicOptions.forEach(opt => {
      if (!selectedCity || opt.dataset.city === selectedCity) {
        clinicSelect.appendChild(opt.cloneNode(true));
      }
    });

    if (currentClinicOption && currentClinicOption.dataset.city === selectedCity) {
      clinicSelect.value = currentClinic;
    } else {
      clinicSelect.value = '';
    }
  });

  function renderStars() {
    document.querySelectorAll(".rating-stars").forEach(starBlock => {
      const rating = parseFloat(starBlock.dataset.rating || 0);
      const percent = Math.min(100, Math.max(0, (rating / 5) * 100));
      starBlock.style.setProperty("--fill-width", `${percent}%`);
    });
  }

  async function loadResults() {
    const formData = new FormData(form);
    const params = new URLSearchParams(formData);
    resultsContainer.style.opacity = 0.4;
    const response = await fetch('/BumbleCare/handlers/search_handler.php?' + params.toString());
    const html = await response.text();
    resultsContainer.innerHTML = html;
    renderStars();
    resultsContainer.style.opacity = 1;
  }

  function sortDoctors(sortType) {
    const cards = Array.from(resultsContainer.querySelectorAll(".doctor-card"));
    const sorted = [...cards];

    sorted.sort((a, b) => {
      const nameA = a.querySelector(".doctor-name").textContent.trim().toLowerCase();
      const nameB = b.querySelector(".doctor-name").textContent.trim().toLowerCase();
      const ratingA = parseFloat(a.querySelector(".rating-stars").dataset.rating || 0);
      const ratingB = parseFloat(b.querySelector(".rating-stars").dataset.rating || 0);
      const reviewsA = parseInt(a.querySelector(".rating-count").textContent.replace(/\D/g, "")) || 0;
      const reviewsB = parseInt(b.querySelector(".rating-count").textContent.replace(/\D/g, "")) || 0;

      switch (sortType) {
        case "rating": return ratingB - ratingA;
        case "reviews": return reviewsB - reviewsA;
        default: return nameA.localeCompare(nameB, 'uk');
      }
    });

    resultsContainer.style.opacity = "0";
    setTimeout(() => {
      resultsContainer.innerHTML = "";
      sorted.forEach(card => resultsContainer.appendChild(card));
      resultsContainer.style.opacity = "1";
    }, 200);
  }

  form.addEventListener("submit", e => {
    e.preventDefault();
    loadResults();
  });

  sortSelect.addEventListener("change", () => {
    sortDoctors(sortSelect.value);
  });

  loadResults();
});
