document.addEventListener("DOMContentLoaded", () => {
  const btnBack = document.querySelector(".btn-back");
  const btnFinish = document.querySelector(".btn-finish");

  const exitModal = document.getElementById("exitModal");
  const finishModal = document.getElementById("finishModal");

  const closeButtons = document.querySelectorAll(".modal .close-btn");
  const cancelButtons = document.querySelectorAll(".modal .btn.no");

  const confirmExit = document.getElementById("confirmExit");
  const confirmFinish = document.getElementById("confirmFinish");

  function openModal(modal) {
    modal.classList.remove("hidden");
  }

  function closeModal(modal) {
    modal.classList.add("hidden");
  }

  btnBack.addEventListener("click", e => {
    e.preventDefault();
    openModal(exitModal);
  });

  btnFinish.addEventListener("click", e => {
    e.preventDefault();
    openModal(finishModal);
  });

  closeButtons.forEach(btn => {
    btn.addEventListener("click", () => {
      closeModal(exitModal);
      closeModal(finishModal);
    });
  });

  cancelButtons.forEach(btn => {
    btn.addEventListener("click", () => {
      closeModal(exitModal);
      closeModal(finishModal);
    });
  });

  [exitModal, finishModal].forEach(modal => {
    modal.addEventListener("click", e => {
      if (e.target === modal) closeModal(modal);
    });
  });

  confirmExit.addEventListener("click", () => {
    window.location.href = "/BumbleCare/pages/doctor_appointments.php";
  });

  confirmFinish.addEventListener("click", () => {
    const appointmentId = btnFinish.dataset.appt;

    const doctorComment = document.querySelector(
      "textarea[name='doctor_comment']"
    ).value.trim();

    const treatmentProgram = document.querySelector(
      "textarea[name='treatment_program']"
    ).value.trim();

    const followUp = document.querySelector(
      "input[name='follow_up_recommendation']"
    ).value.trim();

    if (!doctorComment || !treatmentProgram || !followUp) {
      closeModal(finishModal);
      showPopupMessage("Будь ласка, заповніть всі поля перед завершенням прийому.", "error");
      return;
    }

    const params = new URLSearchParams();
    params.append("appointment_id", appointmentId);
    params.append("doctor_comment", doctorComment);
    params.append("treatment_program", treatmentProgram);
    params.append("follow_up_recommendation", followUp);

    fetch("/BumbleCare/handlers/finish_appointment_handler.php", {
      method: "POST",
      headers: { "Content-Type": "application/x-www-form-urlencoded" },
      body: params.toString()
    })
      .then(res => res.json())
      .then(data => {

        if (data.error === "empty_fields") {
          closeModal(finishModal);
          showPopupMessage("Всі поля повинні бути заповнені.", "error");
          return;
        }

        if (data.error) {
          closeModal(finishModal);
          showPopupMessage("Помилка! Не вдалося завершити прийом.", "error");
          return;
        }

        closeModal(finishModal);
        showPopupMessage("Прийом завершено!", "success");

        setTimeout(() => {
          window.location.href = "/BumbleCare/pages/doctor_appointments.php";
        }, 1000);
      })
      .catch(() => {
        showPopupMessage("Помилка сервера", "error");
      });
  });
});
