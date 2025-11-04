document.addEventListener("DOMContentLoaded", () => {
  const searchForm = document.getElementById("searchForm");
  const filterForm = document.getElementById("filterForm");
  const resultsContainer = document.getElementById("resultsContainer");
  const citySelect = document.getElementById("citySelect");
  const clinicSelect = document.getElementById("clinicSelect");
  const allClinicOptions = Array.from(clinicSelect.querySelectorAll("option[data-city]"));
  const sortSelect = document.getElementById("sortSelect");
  const resetBtn = document.querySelector(".btn-reset");
  const searchInput = document.getElementById("searchInput");
  const clearSearch = document.getElementById("clearSearch");

  let currentQuery = "";
  let currentFilters = {};
  let lastLoadedQuery = "";
  let lastLoadedFilters = {};

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

  async function loadResults(showAnimation = true) {
    const params = new URLSearchParams();
    if (currentQuery) params.append("query", currentQuery);

    for (const [key, value] of Object.entries(currentFilters)) {
      if (value) params.append(key, value);
    }

    if (showAnimation) resultsContainer.style.opacity = 0.4;

    const response = await fetch('/BumbleCare/handlers/search_handler.php?' + params.toString());
    const html = await response.text();

    if (showAnimation) {
      resultsContainer.style.transition = "opacity 0.3s ease";
      resultsContainer.style.opacity = 0;
      setTimeout(() => {
        resultsContainer.innerHTML = html;
        renderStars();
        resultsContainer.style.opacity = 1;
      }, 200);
    } else {
      resultsContainer.innerHTML = html;
      renderStars();
    }
  }

  searchForm.addEventListener("submit", e => {
    e.preventDefault();
    const newQuery = searchForm.querySelector("input[name='query']").value.trim();

    if (newQuery === lastLoadedQuery) {
      return;
    }
    currentQuery = newQuery;
    lastLoadedQuery = newQuery;
    loadResults();
  });

  filterForm.addEventListener("submit", e => {
    e.preventDefault();
    const formData = new FormData(filterForm);
    const newFilters = Object.fromEntries(formData.entries());

    const filtersChanged = Object.keys(newFilters).some(
      key => newFilters[key] !== (lastLoadedFilters[key] || "")
    );

    if (!filtersChanged) return;
    currentFilters = newFilters;
    lastLoadedFilters = { ...newFilters };
    loadResults();
  });

    resetBtn.addEventListener("click", e => {
    e.preventDefault();
    const currentSort = sortSelect.value;
    filterForm.reset();
    clinicSelect.innerHTML = '<option value="">Усі</option>';
    allClinicOptions.forEach(opt => clinicSelect.appendChild(opt.cloneNode(true)));

    sortSelect.value = currentSort;
    currentFilters = { sort: currentSort };
    lastLoadedFilters = { sort: currentSort };

    loadResults();
  });

  sortSelect.addEventListener("change", () => {
    currentFilters.sort = sortSelect.value;
    lastLoadedFilters.sort = sortSelect.value;
    loadResults();
  });

  searchInput.addEventListener("input", () => {
    clearSearch.style.display = searchInput.value.trim() ? "inline" : "none";
  });

  clearSearch.addEventListener("click", () => {
    if (!searchInput.value) return;

    searchInput.value = "";
    clearSearch.style.display = "none";

    if (currentQuery !== "") {
      currentQuery = "";
      lastLoadedQuery = "";
      loadResults();
    }
  });

  loadResults(false);
});
