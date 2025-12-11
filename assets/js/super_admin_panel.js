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

  if (!addForm || !editForm || !deactivateForm) return;

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
    const clinicName =
      editForm.querySelector("select[name='clinic_id']").selectedOptions[0].textContent;

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
});
