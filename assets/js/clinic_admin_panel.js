document.addEventListener("DOMContentLoaded", () => {
  const addForm = document.getElementById("addDoctorForm");
  const editForm = document.getElementById("editDoctorForm");
  const deactivateForm = document.getElementById("deactivateDoctorForm");

  const HANDLER_URL = "/BumbleCare/handlers/admin_manage_doctors.php";

  const editSelect = editForm.querySelector("select[name='doctor_id']");
  const deactivateSelect = deactivateForm.querySelector("select[name='doctor_id']");

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
      const res = await fetch(HANDLER_URL, { method: "POST", body: formData });
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
      const res = await fetch(HANDLER_URL, { method: "POST", body: formData });
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
      const res = await fetch(HANDLER_URL, { method: "POST", body: formData });
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
      const res = await fetch(HANDLER_URL, { method: "POST", body: formData });
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
});
