document.addEventListener("DOMContentLoaded", () => {
  const cancelModal = document.getElementById("cancelModal");
  const cancelInfo = document.getElementById("cancelInfo");
  const btnYes = cancelModal ? cancelModal.querySelector(".yes") : null;
  const btnNo = cancelModal ? cancelModal.querySelector(".no") : null;
  const closeBtn = cancelModal ? cancelModal.querySelector(".close-btn") : null;

  const cancelButtons = document.querySelectorAll(".btn-cancel");
  const reviewButtons = document.querySelectorAll(".btn-review");

  let selectedAppointmentId = null;

	cancelButtons.forEach((btn) => {
		btn.addEventListener("click", (e) => {
			const { id, doctor, clinic, date, time } = e.target.dataset;
			selectedAppointmentId = id;

			cancelInfo.innerHTML = `
				<strong>Лікар:</strong> ${doctor}<br>
				<strong>Клініка:</strong> ${clinic}<br>
				<strong>Обрана дата:</strong> ${date}<br>
				<strong>Час прийому:</strong> ${time}
			`;

			openModal(cancelModal);
		});
	});

  if (btnYes) {
    btnYes.addEventListener("click", () => {
      if (!selectedAppointmentId) return;

      fetch("/BumbleCare/handlers/appointment_actions.php", {
        method: "POST",
        headers: { "Content-Type": "application/x-www-form-urlencoded" },
        body: new URLSearchParams({
          action: "cancel",
          id: selectedAppointmentId,
        }),
      })
        .then((res) => res.json())
        .then((data) => {
          closeModal(cancelModal);
          if (data.success) {
            window.location.reload();
          } else {
            showPopupMessage(data.error || "Не вдалося скасувати бронювання", "error");
          }
        })
        .catch(() => {
          closeModal(cancelModal);
          showPopupMessage("Помилка з'єднання з сервером", "error");
        });
    });
  }

  if (btnNo) btnNo.addEventListener("click", () => closeModal(cancelModal));
  if (closeBtn) closeBtn.addEventListener("click", () => closeModal(cancelModal));

  cancelModal.addEventListener("click", (e) => {
    if (e.target === cancelModal) closeModal(cancelModal);
  });

  function openModal(modal) {
    modal.classList.remove("hidden");
  }

  function closeModal(modal) {
    modal.classList.add("hidden");
  }

  reviewButtons.forEach((btn) => {
    btn.addEventListener("click", () => {
      const apptId = btn.dataset.id;
      const doctorId = btn.dataset.doctor;
      window.location.href = `/BumbleCare/pages/leave_review.php?appointment_id=${apptId}&doctor_id=${doctorId}`;
    });
  });
  
});
