document.addEventListener("DOMContentLoaded", () => {
  const addForm = document.getElementById("addDoctorForm");
  const editForm = document.getElementById("editDoctorForm");
  const deactivateForm = document.getElementById("deactivateDoctorForm");

  const editSelect = editForm.querySelector("select[name='doctor_id']");
  const deactivateSelect = deactivateForm.querySelector("select[name='doctor_id']");

	const tabs = document.querySelectorAll(".tabs .tab");
	const contents = document.querySelectorAll(".tab-content");

  const clinicForm = document.getElementById("editClinicForm");
  const btn = document.getElementById("editClinicBtn");
  const inputs = clinicForm ? clinicForm.querySelectorAll("input, textarea") : null;
  const dayInput     = document.getElementById("dayInput");

  dayInput.addEventListener("click", () => {
    dayInput.showPicker(); 
  });

  const savedTab = sessionStorage.getItem('activeClinicTab');
  if (savedTab) {
    tabs.forEach(t => t.classList.remove('active'));
    contents.forEach(c => c.classList.add('hidden'));

    if (savedTab === 'clinic') {
      tabs[2].classList.add('active');
      document.getElementById('tab-clinic').classList.remove('hidden');
    }
  }

	tabs.forEach((tab, index) => {
		tab.addEventListener("click", () => {
			tabs.forEach(t => t.classList.remove("active"));
			contents.forEach(c => c.classList.add("hidden"));

			tab.classList.add("active");
			contents[index].classList.remove("hidden");

      if (index === 2) {
        sessionStorage.setItem('activeClinicTab', 'clinic');
      } else {
        sessionStorage.removeItem('activeClinicTab');
      }
		});
	});

  function addDoctorToSelects(doctorId, fullName, specialty = "") {
    const editOption = document.createElement("option");
    editOption.value = doctorId;
    editOption.textContent = specialty ? `${fullName} (${specialty})` : fullName;

    const deactOption = document.createElement("option");
    deactOption.value = doctorId;
    deactOption.textContent = fullName;

    editSelect.appendChild(editOption);
    deactivateSelect.appendChild(deactOption);
  }

  function updateDoctorOption(doctorId, fullName, specialty = "") {
    const textEdit = specialty ? `${fullName} (${specialty})` : fullName;
    const textDeact = fullName;

    const editOpt = editSelect.querySelector(`option[value="${doctorId}"]`);
    if (editOpt) editOpt.textContent = textEdit;

    const deactOpt = deactivateSelect.querySelector(`option[value="${doctorId}"]`);
    if (deactOpt) deactOpt.textContent = textDeact;
  }

  function removeDoctorFromSelects(doctorId) {
    const opt1 = editSelect.querySelector(`option[value="${doctorId}"]`);
    if (opt1) opt1.remove();

    const opt2 = deactivateSelect.querySelector(`option[value="${doctorId}"]`);
    if (opt2) opt2.remove();
  }

  editSelect.addEventListener("change", async () => {
    const doctor_id = editSelect.value;
    if (!doctor_id) return;

    const formData = new FormData();
    formData.append("action", "get_doctor");
    formData.append("doctor_id", doctor_id);

    try {
      const res = await fetch("/BumbleCare/handlers/admin_manage_doctors.php", { method: "POST", body: formData });
      const data = await res.json();

      if (data.status !== "success") {
        showPopupMessage("Не вдалося отримати дані лікаря", "error");
        return;
      }

      editForm.querySelector("[name='full_name']").value = data.doctor.full_name ?? "";
      editForm.querySelector("[name='email']").value = data.doctor.email ?? "";
      editForm.querySelector("[name='phone']").value = data.doctor.phone ?? "";
      editForm.querySelector("[name='specialty']").value = data.doctor.specialty ?? "";
      editForm.querySelector("[name='education']").value = data.doctor.education ?? "";
      editForm.querySelector("[name='experience']").value = data.doctor.experience ?? "";
      editForm.querySelector("[name='license_number']").value = data.doctor.license_number ?? "";
      editForm.querySelector("[name='certification']").value = data.doctor.certification ?? "";
      editForm.querySelector("[name='gender']").value = data.doctor.gender ?? "";
      editForm.querySelector("[name='date_of_birth']").value = data.doctor.date_of_birth ?? "";
      editForm.querySelector("[name='id_code']").value = data.doctor.id_code ?? "";
      editForm.querySelector("[name='about']").value = data.doctor.about ?? "";

    } catch {
      showPopupMessage("Помилка звʼязку з сервером", "error");
    }
  });

  addForm.addEventListener("submit", async (e) => {
    e.preventDefault();

    const full_name = addForm.querySelector("[name='full_name']").value.trim();
    const email = addForm.querySelector("[name='email']").value.trim();
    const password = addForm.querySelector("[name='password']").value.trim();
    const confirm = addForm.querySelector("[name='confirm_password']").value.trim();
    const phone = addForm.querySelector("[name='phone']").value.trim();

    if (!full_name || !email || !password || !confirm || !phone) {
      showPopupMessage("Усі поля обовʼязкові", "error");
      return;
    }

    if (password.length < 6) {
      showPopupMessage("Пароль має бути не менше 6 символів", "error");
      return;
    }

    if (password !== confirm) {
      showPopupMessage("Паролі не співпадають", "error");
      return;
    }

    const formData = new FormData(addForm);
    formData.append("action", "add");

    try {
      const res = await fetch("/BumbleCare/handlers/admin_manage_doctors.php", { method: "POST", body: formData });
      const data = await res.json();

      if (data.status === "success") {

        if (!data.doctor_id) {
          showPopupMessage("Помилка: не отримано ID лікаря", "error");
          return;
        }

        addDoctorToSelects(data.doctor_id, full_name);

        showPopupMessage(data.message, "success");
        addForm.reset();

      } else {
        showPopupMessage(data.message || "Помилка", "error");
      }

    } catch {
      showPopupMessage("Помилка зʼєднання з сервером", "error");
    }
  });

  editForm.addEventListener("submit", async (e) => {
    e.preventDefault();

    const doctor_id = editForm.querySelector("[name='doctor_id']").value;
    if (!doctor_id) {
      showPopupMessage("Оберіть лікаря", "error");
      return;
    }

    const full_name = editForm.querySelector("[name='full_name']").value.trim();
    const specialty = editForm.querySelector("[name='specialty']").value.trim();

    const formData = new FormData(editForm);
    formData.append("action", "edit");

    try {
      const res = await fetch("/BumbleCare/handlers/admin_manage_doctors.php", { method: "POST", body: formData });
      const data = await res.json();

      if (data.status === "success") {
        showPopupMessage(data.message, "success");

        updateDoctorOption(doctor_id, full_name, specialty);

        editForm.reset();
      } else {
        showPopupMessage(data.message || "Помилка", "error");
      }

    } catch {
      showPopupMessage("Помилка зʼєднання з сервером", "error");
    }
  });

  deactivateForm.addEventListener("submit", async (e) => {
    e.preventDefault();

    const doctor_id = deactivateForm.querySelector("[name='doctor_id']").value;
    if (!doctor_id) {
      showPopupMessage("Оберіть лікаря", "error");
      return;
    }

    const formData = new FormData(deactivateForm);
    formData.append("action", "deactivate");

    try {
      const res = await fetch("/BumbleCare/handlers/admin_manage_doctors.php", { method: "POST", body: formData });
      const data = await res.json();

      if (data.status === "success") {

        removeDoctorFromSelects(doctor_id);

        showPopupMessage(data.message, "success");
        deactivateForm.reset();

      } else {
        showPopupMessage(data.message || "Помилка", "error");
      }

    } catch {
      showPopupMessage("Помилка зʼєднання з сервером", "error");
    }
  });

  if (clinicForm && btn) {
    btn.addEventListener("click", async () => {
      const saving = btn.textContent === "Зберегти";

      if (!saving) {
        inputs.forEach(i => i.disabled = false);
        btn.textContent = "Зберегти";
        return;
      }

      const formData = new FormData(clinicForm);
      formData.append("action", "update");

      try {
        const res = await fetch("/BumbleCare/handlers/admin_manage_clinic.php", {
          method: "POST",
          body: formData
        });

        const data = await res.json();

        if (data.success) {
          showPopupMessage("Дані клініки оновлено!", "success");
          inputs.forEach(i => i.disabled = true);
          btn.textContent = "Редагувати";
        } else {
          showPopupMessage(data.error || "Помилка збереження", "error");
        }
      } catch {
        showPopupMessage("Помилка з'єднання з сервером", "error");
      }
    });
  }

  const reviewsForm = document.getElementById("reviewsFilterForm");
  const reviewsContainer = document.getElementById("reviewsResultsContainer");
  const resetReviewsBtn = document.getElementById("resetReviewsFilters");

  const doctorInput = reviewsForm.querySelector("input[name='doctor_query']");
  const statusSelect = reviewsForm.querySelector("select[name='status']");
  const sortSelect = reviewsForm.querySelector("select[name='sort']");
  const clearDoctorBtn = document.getElementById("clearDoctorQuery");

  async function loadReviews() {
    const formData = new FormData(reviewsForm);
    formData.append("action", "list");

    try {
      const res = await fetch("/BumbleCare/handlers/admin_manage_reviews.php", {
        method: "POST",
        body: formData
      });

      const data = await res.json();

      if (!data.success) {
        reviewsContainer.innerHTML = "<p class='no-reviews'>Помилка завантаження.</p>";
        return;
      }

      reviewsContainer.innerHTML = data.html;

      if (typeof renderStars === "function") renderStars();
      attachReviewButtons();

    } catch {
      reviewsContainer.innerHTML = "<p class='no-reviews'>Помилка з'єднання.</p>";
    }
  }

  reviewsForm.addEventListener("submit", e => {
    e.preventDefault();
    loadReviews();
  });

  doctorInput.addEventListener("input", () => {
    clearDoctorBtn.style.display = doctorInput.value.trim() ? "inline" : "none";
  });

  clearDoctorBtn.addEventListener("click", () => {
    doctorInput.value = "";
    clearDoctorBtn.style.display = "none";
    loadReviews();
  });

  sortSelect.addEventListener("change", loadReviews);

  resetReviewsBtn.addEventListener("click", () => {
    statusSelect.value = "pending";

    loadReviews();
  });

  function attachReviewButtons() {
    document.querySelectorAll(".btn-approve").forEach(btn => {
      btn.onclick = () => updateReviewStatus(btn.dataset.id, "approved");
    });
    document.querySelectorAll(".btn-reject").forEach(btn => {
      btn.onclick = () => updateReviewStatus(btn.dataset.id, "rejected");
    });
    document.querySelectorAll(".btn-hide").forEach(btn => {
      btn.onclick = () => updateReviewStatus(btn.dataset.id, "hidden");
    });
  }

  async function updateReviewStatus(id, status) {
    const formData = new FormData();
    formData.append("action", "update");
    formData.append("review_id", id);
    formData.append("status", status);

    const res = await fetch("/BumbleCare/handlers/admin_manage_reviews.php", {
      method: "POST",
      body: formData
    });

    const data = await res.json();

    if (data.success) {
      showPopupMessage("Статус відгуку оновлено!", "success");
      loadReviews();
    } else {
      showPopupMessage(data.error || "Помилка оновлення статусу", "error");
    }
  }

  tabs.forEach((tab, index) => {
    tab.addEventListener("click", () => {
      if (contents[index].id === "tab-reviews") {
        statusSelect.value = "pending";
        sortSelect.value = "date_desc";

        loadReviews();
      }
    });
  });

  const reviewsTab = document.getElementById("tab-reviews");
  if (!reviewsTab.classList.contains("hidden")) {
    statusSelect.value = "pending";
    sortSelect.value = "date_desc";
    loadReviews();
  }
});
