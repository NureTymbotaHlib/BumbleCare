document.addEventListener("DOMContentLoaded", () => {
  const results = document.getElementById("clinicsResults");
  const searchForm = document.getElementById("clinicsSearchForm");
  const filterForm = document.getElementById("clinicsFilterForm");

  const queryInput = document.getElementById("queryInput");
  const clearQuery = document.getElementById("clearQuery");
	
  const sortSelect = document.getElementById("sortSelect");

  const resetBtn = document.querySelector(".btn-reset");

  let currentQuery = "";
  let currentFilters = {};
  let lastQuery = "";
  let lastFilters = {};

  async function loadClinics(showAnimation = true) {
    const params = new URLSearchParams();

    if (currentQuery) params.append("query", currentQuery);

    for (const [k, v] of Object.entries(currentFilters)) {
      if (v) params.append(k, v);
    }

    if (showAnimation) results.style.opacity = 0.4;

    const response = await fetch('/BumbleCare/handlers/clinics_handler.php?' + params.toString());
    const html = await response.text();

    if (showAnimation) {
      results.style.transition = "opacity 0.3s ease";
      results.style.opacity = 0;
      setTimeout(() => {
        results.innerHTML = html;
        renderStars();
        results.style.opacity = 1;
      }, 200);
    } else {
      results.innerHTML = html;
      renderStars();
    }
  }

  searchForm.addEventListener("submit", e => {
    e.preventDefault();
    const q = queryInput.value.trim();
    if (q === lastQuery) return;

    currentQuery = q;
    lastQuery = q;
    loadClinics();
  });

  filterForm.addEventListener("submit", e => {
    e.preventDefault();
    const fd = new FormData(filterForm);
    const newFilters = Object.fromEntries(fd.entries());

    const changed = Object.keys(newFilters).some(
      k => newFilters[k] !== (lastFilters[k] || "")
    );

    if (!changed) return;

    currentFilters = newFilters;
    lastFilters = { ...newFilters };

    loadClinics();
  });

	resetBtn.addEventListener("click", e => {
		e.preventDefault();
		const currentSort = sortSelect.value;
		filterForm.reset();

		sortSelect.value = currentSort;

		currentFilters = { sort: currentSort };
		lastFilters = { sort: currentSort };

		loadClinics();
	});

  sortSelect.addEventListener("change", () => {
    currentFilters.sort = sortSelect.value;
    lastFilters.sort = sortSelect.value;
    loadClinics();
  });

  queryInput.addEventListener("input", () => {
    clearQuery.style.display = queryInput.value.trim() ? "inline" : "none";
  });

  clearQuery.addEventListener("click", () => {
    queryInput.value = "";
    clearQuery.style.display = "none";

    if (currentQuery !== "") {
      currentQuery = "";
      lastQuery = "";
      loadClinics();
    }
  });

  loadClinics(false);
});
