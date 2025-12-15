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
  const activateAdminForm = document.getElementById("activateClinicAdminForm");

  // if (!addForm || !editForm || !deactivateForm) return;

  const editAdminClinicSelect = document.getElementById("editAdminClinicSelect");
  const deactivateAdminClinicSelect = document.getElementById("deactivateAdminClinicSelect");
  const activateAdminClinicSelect = document.getElementById("activateAdminClinicSelect");

  const editAdminSelect = document.getElementById("editAdminSelect");
  const deactivateAdminSelect = document.getElementById("deactivateAdminSelect");
  const activateAdminSelect = document.getElementById("activateAdminSelect");

  const adminStore = new Map();

  document.querySelectorAll("#editAdminSelect option[data-clinic], #deactivateAdminSelect option[data-clinic], #activateAdminSelect option[data-clinic]")
    .forEach(opt => {
      adminStore.set(opt.value, {
        fullName: opt.textContent,
        clinicId: opt.dataset.clinic,
        active: opt.closest("#activateAdminSelect") ? false : true
      });
    });

  function renderAdminSelect(selectEl, clinicId, { activeOnly = false } = {}) {
    if (!selectEl) return;

    selectEl.innerHTML = '<option value="" disabled selected hidden>Оберіть адміністратора...</option>';

    for (const [adminId, admin] of adminStore.entries()) {
      if (activeOnly && !admin.active) continue;
      if (clinicId && String(admin.clinicId) !== String(clinicId)) continue;

      const opt = document.createElement("option");
      opt.value = adminId;
      opt.textContent = admin.fullName;
      opt.dataset.clinic = admin.clinicId;

      selectEl.appendChild(opt);
    }
  }
  
  function renderInactiveAdminSelect(selectEl, clinicId) {
    if (!selectEl) return;

    selectEl.innerHTML ='<option value="" disabled selected hidden>Оберіть адміністратора...</option>';

    for (const [adminId, admin] of adminStore.entries()) {
      if (admin.active) continue;
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
      renderAdminSelect(editAdminSelect, editAdminClinicSelect.value, { activeOnly: true });
    });
  }

  if (deactivateAdminClinicSelect) {
    deactivateAdminClinicSelect.addEventListener("change", () => {
      renderAdminSelect(deactivateAdminSelect, deactivateAdminClinicSelect.value, { activeOnly: true });
    });
  }

  if (activateAdminClinicSelect) {
    activateAdminClinicSelect.addEventListener("change", () => {
      renderInactiveAdminSelect(
        activateAdminSelect,
        activateAdminClinicSelect.value
      );
    });
  }

  function addAdminToStore(adminId, fullName, clinicId) {
    adminStore.set(String(adminId), {
      fullName,
      clinicId,
      active: true
    });

    renderAdminSelect(editAdminSelect, editAdminClinicSelect?.value || "", { activeOnly: true });
    renderAdminSelect(deactivateAdminSelect, deactivateAdminClinicSelect?.value || "", { activeOnly: true });
    renderInactiveAdminSelect(activateAdminSelect, activateAdminClinicSelect?.value || "");
  }

  function updateAdminInStore(adminId, fullName, clinicId) {
    const key = String(adminId);
    if (!adminStore.has(key)) return;

    adminStore.get(key).fullName = fullName;
    adminStore.get(key).clinicId = clinicId;

    renderAdminSelect(editAdminSelect, editAdminClinicSelect?.value || "", { activeOnly: true });
    renderAdminSelect(deactivateAdminSelect, deactivateAdminClinicSelect?.value || "", { activeOnly: true });
    renderInactiveAdminSelect(activateAdminSelect, activateAdminClinicSelect?.value || "");
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
        editAdminClinicSelect.value = "";
        deactivateAdminClinicSelect.value = "";
        activateAdminClinicSelect.value = "";

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
        editAdminClinicSelect.value = "";
        renderAdminSelect(editAdminSelect, "", { activeOnly: true });
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
        adminStore.get(String(adminId)).active = false;

        editAdminClinicSelect.value = "";
        deactivateAdminClinicSelect.value = "";
        activateAdminClinicSelect.value = "";

        renderAdminSelect(editAdminSelect, "", { activeOnly: true });
        renderAdminSelect(deactivateAdminSelect, "", { activeOnly: true });
        renderInactiveAdminSelect(activateAdminSelect, activateAdminClinicSelect?.value || "");

        showPopupMessage("Адміністратора деактивовано", "success");
        deactivateForm.reset();
      } else {
        showPopupMessage(data.message || "Помилка", "error");
      }
    } catch {
      showPopupMessage("Помилка з'єднання з сервером", "error");
    }
  });

  if (activateAdminForm) {
    activateAdminForm.addEventListener("submit", async e => {
      e.preventDefault();

      const adminId = activateAdminSelect.value;
      if (!adminId) {
        showPopupMessage("Оберіть адміністратора", "error");
        return;
      }

      const formData = new FormData();
      formData.append("action", "activate");
      formData.append("admin_id", adminId);

      try {
        const res = await fetch("/BumbleCare/handlers/super_admin_manage_admins.php", {
          method: "POST",
          body: formData
        });

        const data = await res.json();

        if (data.status === "success") {
          adminStore.get(String(adminId)).active = true;

          renderAdminSelect(editAdminSelect, editAdminClinicSelect?.value || "", { activeOnly: true });
          renderAdminSelect(deactivateAdminSelect, deactivateAdminClinicSelect?.value || "", { activeOnly: true });
          renderInactiveAdminSelect(
            activateAdminSelect,
            activateAdminClinicSelect?.value || ""
          );

          showPopupMessage("Адміністратора активовано", "success");
          activateAdminForm.reset();
        } else {
          showPopupMessage(data.message || "Помилка", "error");
        }
      } catch {
        showPopupMessage("Помилка зʼєднання з сервером", "error");
      }
    });
  }

  // manage doctors
  const editDoctorSelect = document.getElementById("editDoctorSelect");
  const deactDoctorSelect = document.getElementById("deactivateDoctorSelect");
  const activateDoctorSelect = document.getElementById("activateDoctorSelect");

  const doctorStore = new Map();

  document.querySelectorAll("#editDoctorSelect option[data-clinic], #activateDoctorSelect option[data-clinic]").forEach(opt => {
    doctorStore.set(opt.value, {
      fullName: opt.textContent,
      clinicId: opt.dataset.clinic,
      active: opt.closest("#activateDoctorSelect") ? false : true
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

  function renderInactiveDoctorSelect(selectEl, clinicId) {
    if (!selectEl) return;

    selectEl.innerHTML = '<option value="" disabled selected hidden>Оберіть лікаря...</option>';

    for (const [doctorId, doc] of doctorStore.entries()) {
      if (doc.active) continue;
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
  const activateClinicSelect = document.getElementById("activateClinicSelect");

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

  if (activateClinicSelect) {
    activateClinicSelect.addEventListener("change", () => {
      renderInactiveDoctorSelect(
        activateDoctorSelect,
        activateClinicSelect.value
      );
    });
  }

  const addDoctorForm = document.getElementById("addDoctorForm");
  const editDoctorForm = document.getElementById("editDoctorForm");
  const deactivateDoctorForm = document.getElementById("deactivateDoctorForm");
  const activateDoctorForm = document.getElementById("activateDoctorForm");

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
          editClinicSelect.value = "";
          deactivateClinicSelect.value = "";
          activateClinicSelect.value = "";

          renderDoctorSelect(editDoctorSelect, "", { activeOnly: true });
          renderDoctorSelect(deactDoctorSelect, "", { activeOnly: true });
          renderInactiveDoctorSelect(activateDoctorSelect, "");
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

          renderInactiveDoctorSelect(
            activateDoctorSelect,
            activateClinicSelect?.value || ""
          );

          showPopupMessage("Лікаря деактивовано", "success");
        }

      } catch {
        showPopupMessage("Помилка зʼєднання з сервером", "error");
      }
    });
  }

  if (activateDoctorForm) {
    activateDoctorForm.addEventListener("submit", async e => {
      e.preventDefault();

      const doctorId = activateDoctorForm.querySelector("[name='doctor_id']").value;
      if (!doctorId) {
        showPopupMessage("Оберіть лікаря", "error");
        return;
      }

      const formData = new FormData();
      formData.append("action", "activate");
      formData.append("doctor_id", doctorId);

      try {
        const res = await fetch("/BumbleCare/handlers/admin_manage_doctors.php", {
          method: "POST",
          body: formData
        });

        const data = await res.json();

        if (data.status === "success") {
          doctorStore.get(String(doctorId)).active = true;

          renderDoctorSelect(editDoctorSelect, editClinicSelect?.value || "", { activeOnly: true });
          renderDoctorSelect(deactDoctorSelect, deactivateClinicSelect?.value || "", { activeOnly: true });

          renderInactiveDoctorSelect(
            activateDoctorSelect,
            activateClinicSelect?.value || ""
          );

          showPopupMessage("Лікаря активовано", "success");
          activateDoctorForm.reset();
        } else {
          showPopupMessage(data.message || "Помилка", "error");
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
  const activatePatientSelect = document.getElementById("activatePatientSelect");

  const addPatientForm = document.getElementById("addPatientForm");
  const editPatientForm = document.getElementById("editPatientForm");
  const deactivatePatientForm = document.getElementById("deactivatePatientForm");
  const activatePatientForm = document.getElementById("activatePatientForm");

  const patientStore = new Map();

  document.querySelectorAll("#editPatientSelect option[value], #activatePatientSelect option[value]")
    .forEach(opt => {
      if (!opt.value) return;

      patientStore.set(opt.value, {
        fullName: opt.textContent,
        active: opt.closest("#activatePatientSelect") ? false : true
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

  function renderInactivePatientSelect(selectEl) {
    if (!selectEl) return;

    selectEl.innerHTML = '<option value="" disabled selected hidden>Оберіть пацієнта...</option>';

    for (const [patientId, patient] of patientStore.entries()) {
      if (patient.active) continue;

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
    renderInactivePatientSelect(activatePatientSelect);
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
    renderInactivePatientSelect(activatePatientSelect);
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
        editPatientForm.querySelector("[name='date_of_birth']").value = data.patient.date_of_birth ?? "";
        editPatientForm.querySelector("[name='gender']").value = data.patient.gender ?? "";
        editPatientForm.querySelector("[name='city']").value = data.patient.city ?? "";
        editPatientForm.querySelector("[name='address']").value = data.patient.address ?? "";
        editPatientForm.querySelector("[name='identification_code']").value = data.patient.identification_code ?? "";
        editPatientForm.querySelector("[name='social_status']").value = data.patient.social_status ?? "";
        editPatientForm.querySelector("[name='insurance_number']").value = data.patient.insurance_number ?? "";

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
          editPatientSelect.value = "";
          deactPatientSelect.value = "";
        } else {
          showPopupMessage(data.message || "Помилка", "error");
        }
      } catch {
        showPopupMessage("Помилка зʼєднання з сервером", "error");
      }
    });
  }

  if (activatePatientForm) {
    activatePatientForm.addEventListener("submit", async e => {
      e.preventDefault();

      const patientId = activatePatientSelect.value;
      if (!patientId) {
        showPopupMessage("Оберіть пацієнта", "error");
        return;
      }

      const formData = new FormData();
      formData.append("action", "activate");
      formData.append("patient_id", patientId);

      try {
        const res = await fetch("/BumbleCare/handlers/super_admin_manage_patients.php", {
          method: "POST",
          body: formData
        });

        const data = await res.json();

        if (data.status === "success") {
          patientStore.get(String(patientId)).active = true;

          renderPatientSelect(editPatientSelect, { activeOnly: true });
          renderPatientSelect(deactPatientSelect, { activeOnly: true });
          renderInactivePatientSelect(activatePatientSelect);

          showPopupMessage("Пацієнта активовано", "success");
          activatePatientForm.reset();
          activatePatientSelect.value = "";
        } else {
          showPopupMessage(data.message || "Помилка", "error");
        }
      } catch {
        showPopupMessage("Помилка зʼєднання", "error");
      }
    });
  }

  // manage clinics
  const clinicAddForm = document.getElementById("clinicAddForm");
  const clinicEditForm = document.getElementById("clinicEditForm");
  const clinicEditSelect = document.getElementById("clinicEditSelect");

  const addClinicPhotoInput = document.getElementById("addClinicPhotoInput");
  const addClinicPhotoPreview = document.getElementById("addClinicPhotoPreview");

  const editClinicPhotoInput = document.getElementById("editClinicPhotoInput");
  const editClinicPhotoPreview = document.getElementById("editClinicPhotoPreview");

  const DEFAULT_CLINIC_PHOTO = "/BumbleCare/assets/images/default_clinic.jpg";

  function bindImagePreview(input, img) {
    if (!input || !img) return;

    input.addEventListener("change", () => {
      const file = input.files[0];
      if (!file) return;

      const reader = new FileReader();
      reader.onload = e => img.src = e.target.result;
      reader.readAsDataURL(file);
    });
  }
  bindImagePreview(addClinicPhotoInput, addClinicPhotoPreview);
  bindImagePreview(editClinicPhotoInput, editClinicPhotoPreview);

  const clinicStore = new Map();

  document
    .querySelectorAll("#clinicEditSelect option[value]")
    .forEach(opt => {
      if (!opt.value) return;

      clinicStore.set(opt.value, {
        name: opt.textContent
      });
    });

  if (clinicEditSelect) {
    clinicEditSelect.addEventListener("change", async () => {
      const clinicId = clinicEditSelect.value;
      if (!clinicId) return;

      const formData = new FormData();
      formData.append("action", "get");
      formData.append("clinic_id", clinicId);

      try {
        const res = await fetch("/BumbleCare/handlers/super_admin_manage_clinics.php", {
          method: "POST",
          body: formData
        });

        const data = await res.json();

        if (data.status !== "success") {
          showPopupMessage(data.message || "Не вдалося отримати дані клініки", "error");
          return;
        }

        clinicEditForm.querySelector("[name='name']").value = data.clinic.name ?? "";
        clinicEditForm.querySelector("[name='description']").value = data.clinic.description ?? "";
        clinicEditForm.querySelector("[name='city']").value = data.clinic.city ?? "";
        clinicEditForm.querySelector("[name='address']").value = data.clinic.address ?? "";
        clinicEditForm.querySelector("[name='phone']").value = data.clinic.phone ?? "";
        clinicEditForm.querySelector("[name='email']").value = data.clinic.email ?? "";
        if (data.clinic.image_url) {
          editClinicPhotoPreview.src = data.clinic.image_url;
        } else {
          editClinicPhotoPreview.src = DEFAULT_CLINIC_PHOTO;
        }

        editClinicPhotoInput.value = ""; 
      } catch {
        showPopupMessage("Помилка зʼєднання з сервером", "error");
      }
    });
  }

  if (clinicAddForm) {
    clinicAddForm.addEventListener("submit", async e => {
      e.preventDefault();

      const formData = new FormData(clinicAddForm);
      formData.append("action", "add");

      try {
        const res = await fetch("/BumbleCare/handlers/super_admin_manage_clinics.php", {
          method: "POST",
          body: formData
        });

        const data = await res.json();

        if (data.status === "success") {
          const opt = document.createElement("option");
          opt.value = data.clinic_id;
          opt.textContent = data.name;

          clinicEditSelect.appendChild(opt);
          clinicStore.set(String(data.clinic_id), { name: data.name });

          showPopupMessage("Клініку додано", "success");
          addClinicPhotoPreview.src = DEFAULT_CLINIC_PHOTO;
          addClinicPhotoInput.value = "";
          clinicAddForm.reset();
        } else {
          showPopupMessage(data.message || "Помилка додавання", "error");
        }
      } catch {
        showPopupMessage("Помилка зʼєднання з сервером", "error");
      }
    });
  }

  if (clinicEditForm) {
    clinicEditForm.addEventListener("submit", async e => {
      e.preventDefault();

      const clinicId = clinicEditSelect.value;
      if (!clinicId) {
        showPopupMessage("Оберіть клініку", "error");
        return;
      }

      const formData = new FormData(clinicEditForm);
      formData.append("action", "edit");
      formData.append("clinic_id", clinicId);

      try {
        const res = await fetch("/BumbleCare/handlers/super_admin_manage_clinics.php", {
          method: "POST",
          body: formData
        });

        const data = await res.json();

        if (data.status === "success") {
          const newName = clinicEditForm.querySelector("[name='name']").value.trim();

          const opt = clinicEditSelect.querySelector(`option[value="${clinicId}"]`);
          if (opt) opt.textContent = newName;

          clinicStore.get(String(clinicId)).name = newName;

          showPopupMessage("Дані клініки оновлено", "success");
          editClinicPhotoPreview.src = DEFAULT_CLINIC_PHOTO;
          editClinicPhotoInput.value = "";

          clinicEditForm.reset();
          clinicEditSelect.value = "";

        } else {
          showPopupMessage(data.message || "Помилка оновлення", "error");
        }
      } catch {
        showPopupMessage("Помилка зʼєднання з сервером", "error");
      }
    });
  }
});
