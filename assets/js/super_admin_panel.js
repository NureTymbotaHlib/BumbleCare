document.addEventListener("DOMContentLoaded", () => {
  const tabs = document.querySelectorAll(".tabs .tab");
  const contents = document.querySelectorAll(".tab-content");

  tabs.forEach((tab, index) => {
    tab.addEventListener("click", () => {
      tabs.forEach(t => t.classList.remove("active"));
      contents.forEach(c => c.classList.add("hidden"));
      tab.classList.add("active");
      contents[index].classList.remove("hidden");
    });
  });

  const addForm = document.getElementById("addClinicAdminForm");
  const editForm = document.getElementById("editClinicAdminForm");
  const deactivateForm = document.getElementById("deactivateClinicAdminForm");

  // if (!addForm || !editForm || !deactivateForm) return;

  const editSelect = editForm.querySelector("select[name='admin_id']");
  const deactSelect = deactivateForm.querySelector("select[name='admin_id']");

  function addAdminToSelects(adminId, fullName, clinicName = "") {
    const label = clinicName ? `${fullName} — ${clinicName}` : fullName;

    const optEdit = document.createElement("option");
    optEdit.value = adminId;
    optEdit.textContent = label;

    const optDeact = document.createElement("option");
    optDeact.value = adminId;
    optDeact.textContent = fullName;

    editSelect.appendChild(optEdit);
    deactSelect.appendChild(optDeact);
  }

  function updateAdminOption(adminId, fullName, clinicName = "") {
    const editLabel = clinicName ? `${fullName} — ${clinicName}` : fullName;

    const e1 = editSelect.querySelector(`option[value="${adminId}"]`);
    if (e1) e1.textContent = editLabel;

    const e2 = deactSelect.querySelector(`option[value="${adminId}"]`);
    if (e2) e2.textContent = fullName;
  }

  function removeAdminFromSelects(adminId) {
    const e1 = editSelect.querySelector(`option[value="${adminId}"]`);
    if (e1) e1.remove();

    const e2 = deactSelect.querySelector(`option[value="${adminId}"]`);
    if (e2) e2.remove();
  }

  editSelect.addEventListener("change", async () => {
    const adminId = editSelect.value;
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
        addAdminToSelects(data.admin_id, fullName, data.clinic_name);
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
    const clinicName = editForm.querySelector("select[name='clinic_id']").selectedOptions[0].textContent;

    const formData = new FormData(editForm);
    formData.append("action", "edit");

    try {
      const res = await fetch("/BumbleCare/handlers/super_admin_manage_admins.php", {
        method: "POST",
        body: formData
      });

      const data = await res.json();

      if (data.status === "success") {
        updateAdminOption(adminId, fullName, clinicName);
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
        removeAdminFromSelects(adminId);
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

    selectEl.innerHTML = '<option value="">Оберіть лікаря...</option>';

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
});
