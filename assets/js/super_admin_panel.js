document.addEventListener("DOMContentLoaded", () => {
  const tabs = document.querySelectorAll(".tabs .tab");
  const contents = document.querySelectorAll(".tab-content");
  let reviewsReady = false;
  let loadReviews = null;
  let statusSelect = null;
  let sortSelect = null;

  tabs.forEach((tab, index) => {
    tab.addEventListener("click", () => {
      tabs.forEach(t => t.classList.remove("active"));
      contents.forEach(c => c.classList.add("hidden"));
      tab.classList.add("active");
      contents[index].classList.remove("hidden");
      if (contents[index].id === "tab-reviews") {
        statusSelect.value = "pending";
        sortSelect.value = "date_desc";
        loadReviews();
      }
    });
  });

  const addForm = document.getElementById("addClinicAdminForm");
  const editForm = document.getElementById("editClinicAdminForm");
  const deactivateForm = document.getElementById("deactivateClinicAdminForm");

  // if (!addForm || !editForm || !deactivateForm) return;

  const editAdminClinicSelect = document.getElementById("editAdminClinicSelect");
  const deactivateAdminClinicSelect = document.getElementById("deactivateAdminClinicSelect");

  const editAdminSelect = document.getElementById("editAdminSelect");
  const deactivateAdminSelect = document.getElementById("deactivateAdminSelect");

  const adminStore = new Map();

  document
    .querySelectorAll("#editAdminSelect option[data-clinic]")
    .forEach(opt => {
      adminStore.set(opt.value, {
        fullName: opt.textContent,
        clinicId: opt.dataset.clinic
      });
    });
  
  function renderAdminSelect(selectEl, clinicId) {
    if (!selectEl) return;

    selectEl.innerHTML = '<option value="" disabled selected hidden>Оберіть адміністратора...</option>';

    for (const [adminId, admin] of adminStore.entries()) {
      if (clinicId && String(admin.clinicId) !== String(clinicId)) continue;

      const opt = document.createElement("option");
      opt.value = adminId;
      opt.textContent = admin.fullName;
      opt.dataset.clinic = admin.clinicId;

      selectEl.appendChild(opt);
    }
  }

  if (editAdminClinicSelect) {
    editAdminClinicSelect.addEventListener("change", () => {
      renderAdminSelect(editAdminSelect, editAdminClinicSelect.value);
    });
  }

  if (deactivateAdminClinicSelect) {
    deactivateAdminClinicSelect.addEventListener("change", () => {
      renderAdminSelect(deactivateAdminSelect, deactivateAdminClinicSelect.value);
    });
  }

  function addAdminToStore(adminId, fullName, clinicId) {
    adminStore.set(String(adminId), {
      fullName,
      clinicId
    });

    renderAdminSelect(editAdminSelect, editAdminClinicSelect?.value || "");
    renderAdminSelect(deactivateAdminSelect, deactivateAdminClinicSelect?.value || "");
  }

  function updateAdminInStore(adminId, fullName, clinicId) {
    const key = String(adminId);
    if (!adminStore.has(key)) return;

    adminStore.get(key).fullName = fullName;
    adminStore.get(key).clinicId = clinicId;

    renderAdminSelect(editAdminSelect, editAdminClinicSelect?.value || "");
    renderAdminSelect(deactivateAdminSelect, deactivateAdminClinicSelect?.value || "");
  }

  function removeAdminFromStore(adminId) {
    adminStore.delete(String(adminId));

    renderAdminSelect(editAdminSelect, editAdminClinicSelect?.value || "");
    renderAdminSelect(deactivateAdminSelect, deactivateAdminClinicSelect?.value || "");
  }

  editAdminSelect.addEventListener("change", async () => {
    const adminId = editAdminSelect.value;
    if (!adminId) return;

    const formData = new FormData();
    formData.append("action", "get_admin");
    formData.append("admin_id", adminId);

    try {
      const res = await fetch("/BumbleCare/handlers/super_admin_manage_admins.php", {
        method: "POST",
        body: formData
      });

      const data = await res.json();

      if (data.status !== "success") {
        showPopupMessage(data.message || "Не вдалося отримати дані адміністратора", "error");
        return;
      }

      editForm.querySelector("[name='full_name']").value = data.admin.full_name ?? "";
      editForm.querySelector("[name='email']").value = data.admin.email ?? "";
      editForm.querySelector("[name='phone']").value = data.admin.phone ?? "";
      editForm.querySelector("[name='clinic_id']").value = data.admin.clinic_id ?? "";
    } catch {
      showPopupMessage("Помилка звʼязку з сервером", "error");
    }
  });

  addForm.addEventListener("submit", async e => {
    e.preventDefault();

    const fullName = addForm.querySelector("[name='full_name']").value.trim();
    const email = addForm.querySelector("[name='email']").value.trim();
    const phone = addForm.querySelector("[name='phone']").value.trim();
    const pw = addForm.querySelector("[name='password']").value.trim();
    const conf = addForm.querySelector("[name='confirm_password']").value.trim();
    const clinicId = addForm.querySelector("[name='clinic_id']").value.trim();

    if (!fullName || !email || !phone || !pw || !conf || !clinicId) {
      showPopupMessage("Усі поля обов’язкові", "error");
      return;
    }

    if (pw !== conf) {
      showPopupMessage("Паролі не співпадають", "error");
      return;
    }

    if (pw.length < 6) {
      showPopupMessage("Пароль має бути не менше 6 символів", "error");
      return;
    }

    const formData = new FormData(addForm);
    formData.append("action", "add");

    try {
      const res = await fetch("/BumbleCare/handlers/super_admin_manage_admins.php", {
        method: "POST",
        body: formData
      });

      const data = await res.json();

      if (data.status === "success") {
        addAdminToStore(data.admin_id, fullName, clinicId);
        showPopupMessage("Адміністратора додано!", "success");
        addForm.reset();
      } else {
        showPopupMessage(data.message || "Помилка", "error");
      }
    } catch {
      showPopupMessage("Помилка з'єднання з сервером", "error");
    }
  });

  editForm.addEventListener("submit", async e => {
    e.preventDefault();

    const adminId = editForm.querySelector("[name='admin_id']").value;
    if (!adminId) {
      showPopupMessage("Оберіть адміністратора", "error");
      return;
    }

    const fullName = editForm.querySelector("[name='full_name']").value.trim();

    const clinicId = editForm.querySelector("[name='clinic_id']").value;
    const formData = new FormData(editForm);
    formData.append("action", "edit");

    try {
      const res = await fetch("/BumbleCare/handlers/super_admin_manage_admins.php", {
        method: "POST",
        body: formData
      });

      const data = await res.json();

      if (data.status === "success") {
        updateAdminInStore(adminId, fullName, clinicId);
        showPopupMessage("Дані адміністратора оновлено!", "success");
        editForm.reset();
      } else {
        showPopupMessage(data.message || "Помилка", "error");
      }
    } catch {
      showPopupMessage("Помилка з'єднання з сервером", "error");
    }
  });

  deactivateForm.addEventListener("submit", async e => {
    e.preventDefault();

    const adminId = deactivateForm.querySelector("[name='admin_id']").value;
    if (!adminId) {
      showPopupMessage("Оберіть адміністратора", "error");
      return;
    }

    const formData = new FormData(deactivateForm);
    formData.append("action", "deactivate");

    try {
      const res = await fetch("/BumbleCare/handlers/super_admin_manage_admins.php", {
        method: "POST",
        body: formData
      });

      const data = await res.json();

      if (data.status === "success") {
        removeAdminFromStore(adminId);
        showPopupMessage("Адміністратора деактивовано", "success");
        deactivateForm.reset();
      } else {
        showPopupMessage(data.message || "Помилка", "error");
      }
    } catch {
      showPopupMessage("Помилка з'єднання з сервером", "error");
    }
  });

  // manage doctors
  const editDoctorSelect = document.getElementById("editDoctorSelect");
  const deactDoctorSelect = document.getElementById("deactivateDoctorSelect");

  const doctorStore = new Map();

  document.querySelectorAll("#editDoctorSelect option[data-clinic]").forEach(opt => {
    doctorStore.set(opt.value, {
      fullName: opt.textContent,
      clinicId: opt.dataset.clinic,
      active: true
    });
  });

  function addDoctorToSelects(doctorId, fullName, clinicId) {
    doctorStore.set(String(doctorId), {
      fullName,
      clinicId,
      active: true
    });

    renderDoctorSelect(editDoctorSelect, editClinicSelect?.value || "", { activeOnly: true });
    renderDoctorSelect(deactDoctorSelect, deactivateClinicSelect?.value || "", { activeOnly: true });
  }

  function updateDoctorOption(doctorId, fullName) {
    const key = String(doctorId);
    if (!doctorStore.has(key)) return;

    doctorStore.get(key).fullName = fullName;

    renderDoctorSelect(editDoctorSelect, editClinicSelect?.value || "", { activeOnly: true });
    renderDoctorSelect(deactDoctorSelect, deactivateClinicSelect?.value || "", { activeOnly: true });
  }

  function deactivateDoctorInStore(doctorId) {
    const key = String(doctorId);
    if (!doctorStore.has(key)) return;

    doctorStore.get(key).active = false;

    renderDoctorSelect(editDoctorSelect, editClinicSelect?.value || "", { activeOnly: true });
    renderDoctorSelect(deactDoctorSelect, deactivateClinicSelect?.value || "", { activeOnly: true });
  }


  function renderDoctorSelect(selectEl, clinicId, { activeOnly = false } = {}) {
    if (!selectEl) return;

    selectEl.innerHTML = '<option value="" disabled selected hidden>Оберіть лікаря...</option>';

    for (const [doctorId, doc] of doctorStore.entries()) {
      if (activeOnly && !doc.active) continue;
      if (clinicId && String(doc.clinicId) !== String(clinicId)) continue;

      const opt = document.createElement("option");
      opt.value = doctorId;
      opt.textContent = doc.fullName;
      opt.dataset.clinic = doc.clinicId;

      selectEl.appendChild(opt);
    }
  }

  const editClinicSelect = document.getElementById("editClinicSelect");
  const deactivateClinicSelect = document.getElementById("deactivateClinicSelect");

  if (editClinicSelect) {
    editClinicSelect.addEventListener("change", () => {
      renderDoctorSelect(
        editDoctorSelect,
        editClinicSelect.value,
        { activeOnly: true }
      );
    });
  }

  if (deactivateClinicSelect) {
    deactivateClinicSelect.addEventListener("change", () => {
      renderDoctorSelect(
        deactDoctorSelect,
        deactivateClinicSelect.value,
        { activeOnly: true }
      );
    });
  }

  const addDoctorForm = document.getElementById("addDoctorForm");
  const editDoctorForm = document.getElementById("editDoctorForm");
  const deactivateDoctorForm = document.getElementById("deactivateDoctorForm");

  if (editDoctorSelect) {
    editDoctorSelect.addEventListener("change", async () => {
      const doctorId = editDoctorSelect.value;
      if (!doctorId) return;

      const formData = new FormData();
      formData.append("action", "get_doctor");
      formData.append("doctor_id", doctorId);

      try {
        const res = await fetch("/BumbleCare/handlers/admin_manage_doctors.php", {
          method: "POST",
          body: formData
        });

        const data = await res.json();

        if (data.status !== "success") {
          showPopupMessage(data.message || "Не вдалося отримати дані лікаря", "error");
          return;
        }

        editDoctorForm.querySelector("[name='full_name']").value = data.doctor.full_name ?? "";
        editDoctorForm.querySelector("[name='email']").value = data.doctor.email ?? "";
        editDoctorForm.querySelector("[name='phone']").value = data.doctor.phone ?? "";
        editDoctorForm.querySelector("[name='specialty']").value = data.doctor.specialty ?? "";
        editDoctorForm.querySelector("[name='education']").value = data.doctor.education ?? "";
        editDoctorForm.querySelector("[name='experience']").value = data.doctor.experience ?? "";
        editDoctorForm.querySelector("[name='license_number']").value = data.doctor.license_number ?? "";
        editDoctorForm.querySelector("[name='certification']").value = data.doctor.certification ?? "";
        editDoctorForm.querySelector("[name='gender']").value = data.doctor.gender ?? "";
        editDoctorForm.querySelector("[name='date_of_birth']").value = data.doctor.date_of_birth ?? "";
        editDoctorForm.querySelector("[name='id_code']").value = data.doctor.id_code ?? "";
        editDoctorForm.querySelector("[name='about']").value = data.doctor.about ?? "";
        editDoctorForm.querySelector("select[name='clinic_id']").value = data.doctor.clinic_id ?? "";
      } catch {
        showPopupMessage("Помилка звʼязку з сервером", "error");
      }
    });
  }

  if (addDoctorForm) {
    addDoctorForm.addEventListener("submit", async e => {
      e.preventDefault();

      const fullName = addDoctorForm.querySelector("[name='full_name']").value.trim();
      const email = addDoctorForm.querySelector("[name='email']").value.trim();
      const phone = addDoctorForm.querySelector("[name='phone']").value.trim();
      const pw = addDoctorForm.querySelector("[name='password']").value.trim();
      const conf = addDoctorForm.querySelector("[name='confirm_password']").value.trim();
      const clinicId = addDoctorForm.querySelector("[name='clinic_id']").value.trim();

      if (!fullName || !email || !phone || !pw || !conf || !clinicId) {
        showPopupMessage("Усі поля обовʼязкові", "error");
        return;
      }

      if (pw !== conf) {
        showPopupMessage("Паролі не співпадають", "error");
        return;
      }

      if (pw.length < 6) {
        showPopupMessage("Пароль має бути не менше 6 символів", "error");
        return;
      }

      const formData = new FormData(addDoctorForm);
      formData.append("action", "add");

      try {
        const res = await fetch("/BumbleCare/handlers/admin_manage_doctors.php", {
          method: "POST",
          body: formData
        });

        const data = await res.json();

        if (data.status === "success") {
          addDoctorToSelects(data.doctor_id, data.full_name, data.clinic_id);
          showPopupMessage("Лікаря додано успішно", "success");
          addDoctorForm.reset();
        } else {
          showPopupMessage(data.message || "Помилка додавання лікаря", "error");
        }
      } catch {
        showPopupMessage("Помилка зʼєднання з сервером", "error");
      }
    });
  }


  if (editDoctorForm) {
    editDoctorForm.addEventListener("submit", async e => {
      e.preventDefault();

      const doctorId = editDoctorForm.querySelector("[name='doctor_id']").value;

      if (!doctorId) {
        showPopupMessage("Оберіть лікаря", "error");
        return;
      }

      const fullName = editDoctorForm.querySelector("[name='full_name']").value.trim();

      const formData = new FormData(editDoctorForm);
      formData.append("action", "edit");

      try {
        const res = await fetch("/BumbleCare/handlers/admin_manage_doctors.php", {
          method: "POST",
          body: formData
        });

        const data = await res.json();

        if (data.status === "success") {
          updateDoctorOption(doctorId, fullName);
          showPopupMessage("Дані лікаря оновлено", "success");
          editDoctorForm.reset();
        } else {
          showPopupMessage(data.message || "Помилка редагування", "error");
        }
      } catch {
        showPopupMessage("Помилка зʼєднання з сервером", "error");
      }
    });
  }

  if (deactivateDoctorForm) {
    deactivateDoctorForm.addEventListener("submit", async e => {
      e.preventDefault();

      const doctorId = deactivateDoctorForm.querySelector("[name='doctor_id']").value;
      if (!doctorId) {
        showPopupMessage("Оберіть лікаря", "error");
        return;
      }

      const formData = new FormData(deactivateDoctorForm);
      formData.append("action", "deactivate");

      try {
        const res = await fetch("/BumbleCare/handlers/admin_manage_doctors.php", {
          method: "POST",
          body: formData
        });

        const data = await res.json();

        if (data.status === "success") {
          deactivateDoctorInStore(doctorId);

          renderDoctorSelect(
            editDoctorSelect,
            editClinicSelect?.value || "",
            { activeOnly: true }
          );

          deactivateClinicSelect.value = "";
          renderDoctorSelect(deactDoctorSelect, "", { activeOnly: true });

          showPopupMessage("Лікаря деактивовано", "success");
        }

      } catch {
        showPopupMessage("Помилка зʼєднання з сервером", "error");
      }
    });
  }

  // manage reviews
  const reviewsForm = document.getElementById("reviewsFilterForm");
  const reviewsContainer = document.getElementById("reviewsResultsContainer");
  const resetReviewsBtn = document.getElementById("resetReviewsFilters");

  if (reviewsForm && reviewsContainer) {

    statusSelect = reviewsForm.querySelector("select[name='status']");
    sortSelect = reviewsForm.querySelector("select[name='sort']");
    const doctorInput = reviewsForm.querySelector("input[name='doctor_query']");
    const clearDoctorBtn = document.getElementById("clearDoctorQuery");

    loadReviews = async () => {
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
        reviewsContainer.innerHTML = "<p class='no-reviews'>Помилка зʼєднання.</p>";
      }
    };

    doctorInput.addEventListener("input", () => {
      clearDoctorBtn.style.display = doctorInput.value.trim() ? "inline" : "none";
    });

    clearDoctorBtn.addEventListener("click", () => {
      doctorInput.value = "";
      clearDoctorBtn.style.display = "none";
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

    reviewsForm.addEventListener("submit", e => {
      e.preventDefault();
      loadReviews();
    });

    if (sortSelect) {
      sortSelect.addEventListener("change", loadReviews);
    }

    if (resetReviewsBtn) {
      resetReviewsBtn.addEventListener("click", () => {
        statusSelect.value = "pending";
        sortSelect.value = "date_desc";
        loadReviews();
      });
    }

    reviewsReady = true;
  }

  // manage patients
  const editPatientSelect = document.getElementById("editPatientSelect");
  const deactPatientSelect = document.getElementById("deactivatePatientSelect");

  const addPatientForm = document.getElementById("addPatientForm");
  const editPatientForm = document.getElementById("editPatientForm");
  const deactivatePatientForm = document.getElementById("deactivatePatientForm");

  const patientStore = new Map();

  document
    .querySelectorAll("#editPatientSelect option[value]")
    .forEach(opt => {
      if (!opt.value) return;

      patientStore.set(opt.value, {
        fullName: opt.textContent,
        active: true
      });
    });

  function renderPatientSelect(selectEl, { activeOnly = false } = {}) {
    if (!selectEl) return;

    selectEl.innerHTML = '<option value="" disabled selected hidden>Оберіть пацієнта...</option>';

    for (const [patientId, patient] of patientStore.entries()) {
      if (activeOnly && !patient.active) continue;

      const opt = document.createElement("option");
      opt.value = patientId;
      opt.textContent = patient.fullName;

      selectEl.appendChild(opt);
    }
  }

  function addPatientToSelects(patientId, fullName) {
    patientStore.set(String(patientId), {
      fullName,
      active: true
    });

    renderPatientSelect(editPatientSelect, { activeOnly: true });
    renderPatientSelect(deactPatientSelect, { activeOnly: true });
  }

  function updatePatientOption(patientId, fullName) {
    const key = String(patientId);
    if (!patientStore.has(key)) return;

    patientStore.get(key).fullName = fullName;

    renderPatientSelect(editPatientSelect, { activeOnly: true });
    renderPatientSelect(deactPatientSelect, { activeOnly: true });
  }

  function deactivatePatientInStore(patientId) {
    const key = String(patientId);
    if (!patientStore.has(key)) return;

    patientStore.get(key).active = false;

    renderPatientSelect(editPatientSelect, { activeOnly: true });
    renderPatientSelect(deactPatientSelect, { activeOnly: true });
  }

  if (editPatientSelect) {
    editPatientSelect.addEventListener("change", async () => {
      const patientId = editPatientSelect.value;
      if (!patientId) return;

      const formData = new FormData();
      formData.append("action", "get_patient");
      formData.append("patient_id", patientId);

      try {
        const res = await fetch("/BumbleCare/handlers/super_admin_manage_patients.php", {
          method: "POST",
          body: formData
        });

        const data = await res.json();

        if (data.status !== "success") {
          showPopupMessage(data.message || "Не вдалося отримати дані пацієнта", "error");
          return;
        }

        editPatientForm.querySelector("[name='full_name']").value = data.patient.full_name ?? "";
        editPatientForm.querySelector("[name='email']").value = data.patient.email ?? "";
        editPatientForm.querySelector("[name='phone']").value = data.patient.phone ?? "";

      } catch {
        showPopupMessage("Помилка звʼязку з сервером", "error");
      }
    });
  }

  if (addPatientForm) {
    addPatientForm.addEventListener("submit", async e => {
      e.preventDefault();

      const fullName = addPatientForm.querySelector("[name='full_name']").value.trim();
      const email = addPatientForm.querySelector("[name='email']").value.trim();
      const phone = addPatientForm.querySelector("[name='phone']").value.trim();
      const pw = addPatientForm.querySelector("[name='password']").value.trim();
      const conf = addPatientForm.querySelector("[name='confirm_password']").value.trim();

      if (!fullName || !email || !phone || !pw || !conf) {
        showPopupMessage("Усі поля обовʼязкові", "error");
        return;
      }

      if (pw !== conf) {
        showPopupMessage("Паролі не співпадають", "error");
        return;
      }

      if (pw.length < 6) {
        showPopupMessage("Пароль має бути не менше 6 символів", "error");
        return;
      }

      const formData = new FormData(addPatientForm);
      formData.append("action", "add");

      try {
        const res = await fetch("/BumbleCare/handlers/super_admin_manage_patients.php", {
          method: "POST",
          body: formData
        });

        const data = await res.json();

        if (data.status === "success") {
          addPatientToSelects(data.patient_id, data.full_name);
          showPopupMessage("Пацієнта додано", "success");
          addPatientForm.reset();
        } else {
          showPopupMessage(data.message || "Помилка", "error");
        }
      } catch {
        showPopupMessage("Помилка зʼєднання з сервером", "error");
      }
    });
  }

  if (editPatientForm) {
    editPatientForm.addEventListener("submit", async e => {
      e.preventDefault();

      const patientId = editPatientForm.querySelector("[name='patient_id']").value;
      if (!patientId) {
        showPopupMessage("Оберіть пацієнта", "error");
        return;
      }

      const fullName = editPatientForm.querySelector("[name='full_name']").value.trim();

      const formData = new FormData(editPatientForm);
      formData.append("action", "edit");

      try {
        const res = await fetch("/BumbleCare/handlers/super_admin_manage_patients.php", {
          method: "POST",
          body: formData
        });

        const data = await res.json();

        if (data.status === "success") {
          updatePatientOption(patientId, fullName);
          showPopupMessage("Дані пацієнта оновлено", "success");
          editPatientForm.reset();
        } else {
          showPopupMessage(data.message || "Помилка", "error");
        }
      } catch {
        showPopupMessage("Помилка зʼєднання з сервером", "error");
      }
    });
  }

  if (deactivatePatientForm) {
    deactivatePatientForm.addEventListener("submit", async e => {
      e.preventDefault();

      const patientId = deactivatePatientForm.querySelector("[name='patient_id']").value;
      if (!patientId) {
        showPopupMessage("Оберіть пацієнта", "error");
        return;
      }

      const formData = new FormData(deactivatePatientForm);
      formData.append("action", "deactivate");

      try {
        const res = await fetch("/BumbleCare/handlers/super_admin_manage_patients.php", {
          method: "POST",
          body: formData
        });

        const data = await res.json();

        if (data.status === "success") {
          deactivatePatientInStore(patientId);
          showPopupMessage("Пацієнта деактивовано", "success");
          deactivatePatientForm.reset();
        } else {
          showPopupMessage(data.message || "Помилка", "error");
        }
      } catch {
        showPopupMessage("Помилка зʼєднання з сервером", "error");
      }
    });
  }
});
