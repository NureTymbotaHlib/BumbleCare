document.addEventListener("DOMContentLoaded", () => {
  const modal = document.getElementById("patient-info-modal");
  const openBtn = document.getElementById("open-info");
  const closeBtn = document.querySelector(".close-btn");
  const editBtn = document.getElementById("edit-btn");
  const form = document.getElementById("patient-info-form");
  const medicalCardView = document.getElementById("medicalCardView");
  const medicalCardInput = document.getElementById("medicalCardInput");
  let medicalCardChanged = false;

  if (medicalCardInput) {
    medicalCardInput.addEventListener("change", () => {
      medicalCardChanged = medicalCardInput.files.length > 0;
    });
  }

  const inputs = form.querySelectorAll("input, select");

  const originalValues = {};
  inputs.forEach(i => {
    if (i.type !== "file") {
      originalValues[i.name] = i.value;
    }
  });


  openBtn.addEventListener("click", e => {
    e.preventDefault();
    modal.classList.remove("hidden");
  });

  closeBtn.addEventListener("click", () => {
    closeModal();
  });

  window.addEventListener("click", e => {
    if (e.target === modal) closeModal();
  });

  editBtn.addEventListener("click", () => {
    const isEditing = editBtn.textContent === "Зберегти";

    if (!isEditing) {
      inputs.forEach(i => {
        if (
          i.name !== "full_name" &&
          i.name !== "email" &&
          i.name !== "age"
        ) {
          i.disabled = false;
        }
      });

      if (medicalCardView) {
        medicalCardView.style.display = "none";
      }

      if (medicalCardInput) {
        medicalCardInput.disabled = false;
        medicalCardInput.value = "";
      }
      editBtn.textContent = "Зберегти";
    } else {
      const formData = new FormData(form);
      fetch("/BumbleCare/handlers/update_patient_info.php", {
        method: "POST",
        body: formData
      })
        .then(res => res.json())
        .then(data => {
          if (data.success) {
            if (data.medical_card) {
              const fileName = data.medical_card.split("/").pop();

              if (medicalCardView) {
                medicalCardView.href = data.medical_card;
                medicalCardView.textContent = fileName;
                medicalCardView.style.display = "";
              }
            }
            modal.classList.add("hidden");
            inputs.forEach(i => {
              if (i.type !== "file") {
                originalValues[i.name] = i.value;
              }
            });
            medicalCardChanged = false;
            resetEditState();
            restoreOriginalValues();
            showPopupMessage("Дані успішно збережено!", "success");
          } else {
            showPopupMessage(data.error || "Помилка збереження", "error");
          }
        })
        .catch(() => showPopupMessage("Помилка запиту", "error"));
    }
  });

  const birthInput = document.getElementById("date_of_birth");
  const ageInput = document.getElementById("age");

  function calculateAge(dateStr) {
    if (!dateStr) return "";
    const birthDate = new Date(dateStr);
    const today = new Date();
    let age = today.getFullYear() - birthDate.getFullYear();
    const m = today.getMonth() - birthDate.getMonth();
    if (m < 0 || (m === 0 && today.getDate() < birthDate.getDate())) age--;
    return age;
  }

  if (birthInput && ageInput) {
    if (birthInput.value) ageInput.value = calculateAge(birthInput.value);
    birthInput.addEventListener("change", () => {
      ageInput.value = calculateAge(birthInput.value);
    });
  }

  function closeModal() {
    modal.classList.add("hidden");
    resetEditState();
    restoreOriginalValues();
  }

  function resetEditState() {
    editBtn.textContent = "Редагувати";
    inputs.forEach(i => (i.disabled = true));
  }

  function restoreOriginalValues() {
    inputs.forEach(i => {
      if (i.type === "file") {
        i.value = "";
        return;
      }

      if (originalValues.hasOwnProperty(i.name)) {
        i.value = originalValues[i.name];
      }
    });

    if (medicalCardView && !medicalCardChanged) {
      medicalCardView.style.display = "";
    }

    if (medicalCardInput) {
      medicalCardInput.value = "";
      medicalCardInput.disabled = true;
    }

    if (birthInput && ageInput) {
      ageInput.value = calculateAge(birthInput.value);
    }
  }
});
