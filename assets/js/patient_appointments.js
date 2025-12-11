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

/* Залишити відгук  */
  const reviewModal = document.getElementById("reviewModal");
  const reviewForm = document.getElementById("reviewForm");
  const closeReviewBtn = reviewModal ? reviewModal.querySelector(".close-btn") : null;
  const cancelReviewBtn = document.getElementById("cancelReview");
  const stars = reviewModal ? reviewModal.querySelectorAll("#starContainer .star") : [];

  let selectedRating = 0;

  reviewButtons.forEach((btn) => {
    btn.addEventListener("click", () => {
      const apptId = btn.dataset.id;
      const doctorId = btn.dataset.doctor;

      document.getElementById("review_appointment_id").value = apptId;
      document.getElementById("review_doctor_id").value = doctorId;
      document.getElementById("review_comment").value = "";
      document.getElementById("review_rating").value = 0;

      selectedRating = 0;
      stars.forEach((s) => (s.style.color = "#aaa"));
      openModal(reviewModal);
    });
  });

  stars.forEach((star) => {
    star.addEventListener("click", () => {
      selectedRating = parseInt(star.dataset.value);
      document.getElementById("review_rating").value = selectedRating;
      stars.forEach((s) => (s.style.color = s.dataset.value <= selectedRating ? "#FFD700" : "#aaa"));
    });
  });

  if (closeReviewBtn) closeReviewBtn.addEventListener("click", () => closeModal(reviewModal));
  if (cancelReviewBtn) cancelReviewBtn.addEventListener("click", () => closeModal(reviewModal));
  if (reviewModal) {
    reviewModal.addEventListener("click", (e) => {
      if (e.target === reviewModal) closeModal(reviewModal);
    });
  }

  if (reviewForm) {
    reviewForm.addEventListener("submit", (e) => {
      e.preventDefault();

      const formData = new FormData(reviewForm);
      if (formData.get("rating") === "0") {
        showPopupMessage("Будь ласка, оберіть оцінку!", "error");
        return;
      }

      fetch("/BumbleCare/handlers/add_review.php", {
        method: "POST",
        body: formData,
      })
        .then((res) => res.json())
        .then((data) => {
          if (data.success) {
            showPopupMessage("Ваш відгук успішно додано!", "success");
            closeModal(reviewModal);
            setTimeout(() => window.location.reload(), 1000);
          } else {
            showPopupMessage(data.message || "Помилка при додаванні відгуку", "error");
          }
        })
        .catch(() => {
          showPopupMessage("Помилка з'єднання з сервером", "error");
        });
    });
  }
  
  /* Переглянути відгук  */
  const viewReviewModal = document.getElementById("viewReviewModal");
  const closeViewBtn = viewReviewModal ? viewReviewModal.querySelector(".close-btn") : null;
  const viewReviewButtons = document.querySelectorAll(".btn-view-review");

  viewReviewButtons.forEach((btn) => {
    btn.addEventListener("click", () => {
      const rating = parseFloat(btn.dataset.rating) || 0;
      const comment = btn.dataset.comment || "";
      const status = btn.dataset.status || "";

      document.getElementById("viewRatingNumber").textContent = rating.toFixed(1);
      const starsBlock = document.getElementById("viewRatingStars");
      starsBlock.dataset.rating = rating;

      document.getElementById("viewReviewComment").textContent = comment;

      const statusField = document.getElementById("viewReviewStatus");
      let statusText = "";

      switch (status) {
        case "pending":
          statusText = "Статус: очікує перевірки";
          break;
        case "approved":
          statusText = "Статус: опубліковано";
          break;
        case "rejected":
          statusText = "Статус: відхилено адміністрацією";
          break;
        case "hidden":
          statusText = "Статус: приховано адміністрацією";
          break;
        default:
          statusText = "";
      }

      statusField.textContent = statusText;

      if (typeof renderStars === "function") renderStars();

      openModal(viewReviewModal);
    });
  });

  if (closeViewBtn) closeViewBtn.addEventListener("click", () => closeModal(viewReviewModal));
  if (viewReviewModal) {
    viewReviewModal.addEventListener("click", (e) => {
      if (e.target === viewReviewModal) closeModal(viewReviewModal);
    });
  }
  
});
