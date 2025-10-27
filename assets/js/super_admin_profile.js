document.addEventListener("DOMContentLoaded", () => {
  const editBtn = document.getElementById("editSuperAdminInfo");
  const form = document.querySelector(".super-admin-info-form");

  if (!form || !editBtn) return;

  const inputs = form.querySelectorAll("input, textarea, select");
  const originalValues = {};
  inputs.forEach(i => (originalValues[i.name] = i.value));

  editBtn.addEventListener("click", () => {
    const isEditing = editBtn.textContent === "Зберегти";

    if (!isEditing) {
      inputs.forEach(i => {
        if (!["full_name", "email"].includes(i.name)) {
          i.disabled = false;
        }
      });
      editBtn.textContent = "Зберегти";
    } else {
      const formData = new FormData(form);

      fetch("/BumbleCare/handlers/update_super_admin_info.php", {
        method: "POST",
        body: formData
      })
        .then(res => res.json())
        .then(data => {
          if (data.success) {
            inputs.forEach(i => (originalValues[i.name] = i.value));
            resetEditState();
          } else {
            alert(data.error || "Помилка збереження");
          }
        })
        .catch(() => alert("Помилка запиту"));
    }
  });

  function resetEditState() {
    editBtn.textContent = "Редагувати";
    inputs.forEach(i => (i.disabled = true));
  }
});
