document.addEventListener("DOMContentLoaded", () => {
  const doctorId = parseInt(document.getElementById("doctorIdInput").value, 10);
  const container    = document.getElementById("appointmentsContainer");

  const searchForm   = document.getElementById("doctorSearchForm");
  const filterForm   = document.getElementById("doctorFilterForm");

  const searchInput  = document.getElementById("searchInput");
  const clearSearch  = document.getElementById("clearSearch");

  const statusSelect = document.getElementById("statusSelect");
  const dayInput     = document.getElementById("dayInput");
  const resetFilters = document.getElementById("resetFilters");

  dayInput.addEventListener("click", () => {
    dayInput.showPicker(); 
  });

  let currentQuery = "";
  let lastLoadedQuery = "";

  let currentFilters = {
    status: "planned",
    day: ""
  };

  let lastLoadedFilters = {
    status: "planned",
    day: ""
  };

  async function loadAppointments(showAnimation = true) {
    const params = new URLSearchParams();
    params.append("doctor_id", doctorId);

    if (currentQuery) params.append("query", currentQuery);
    if (currentFilters.status) params.append("status", currentFilters.status);
    if (currentFilters.day) params.append("day", currentFilters.day);

    if (showAnimation) container.style.opacity = 0.4;

    const res = await fetch("/BumbleCare/handlers/doctor_appointments_handler.php?" + params.toString());
    const html = await res.text();

    if (showAnimation) {
      container.style.transition = "opacity 0.25s ease";
      container.style.opacity = 0;

      setTimeout(() => {
        container.innerHTML = html;
        container.style.opacity = 1;
        bindButtons();
      }, 200);
    } else {
      container.innerHTML = html;
      bindButtons();
    }
  }

  function bindButtons() {
		document.querySelectorAll(".btn-start").forEach(btn => {
				btn.addEventListener("click", () => {
				const id = btn.dataset.id;
				window.location.href = `/BumbleCare/pages/doctor_start_appointment.php?appointment_id=${id}`;
				});
		});

		document.querySelectorAll(".btn-result").forEach(btn => {
				btn.addEventListener("click", () => {
				const id = btn.dataset.id;
				window.location.href = `/BumbleCare/pages/appointment_view_result.php?appointment_id=${id}`;
				});
		});
  }

  searchForm.addEventListener("submit", e => {
    e.preventDefault();

    const newQuery = searchInput.value.trim();

    if (newQuery === lastLoadedQuery) return;

    currentQuery = newQuery;
    lastLoadedQuery = newQuery;

    loadAppointments();
  });

  searchInput.addEventListener("input", () => {
    clearSearch.style.display = searchInput.value.trim() ? "inline" : "none";
  });

  clearSearch.addEventListener("click", () => {
    if (!searchInput.value.trim()) return;

    searchInput.value = "";
    clearSearch.style.display = "none";

    if (currentQuery !== "") {
      currentQuery = "";
      lastLoadedQuery = "";
      loadAppointments();
    }
  });

  filterForm.addEventListener("submit", e => {
    e.preventDefault();

    const newFilters = {
      status: statusSelect.value,
      day: dayInput.value
    };

    const filtersChanged =
      newFilters.status !== lastLoadedFilters.status ||
      newFilters.day !== lastLoadedFilters.day;

    if (!filtersChanged) return;

    currentFilters = newFilters;
    lastLoadedFilters = { ...newFilters };

    loadAppointments();
  });

  resetFilters.addEventListener("click", e => {
    e.preventDefault();

    filterForm.reset();

    currentFilters = {
      status: "planned",
      day: ""
    };

    lastLoadedFilters = {
      status: "planned",
      day: ""
    };

    loadAppointments();
  });

  loadAppointments(false);
});
