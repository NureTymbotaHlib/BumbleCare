document.addEventListener("DOMContentLoaded", () => {
  const slotsContainer = document.getElementById("slotsContainer");
  const doctorId = new URLSearchParams(window.location.search).get("doctor_id");

  const confirmModal = document.getElementById("confirmModal");
  const loginModal = document.getElementById("loginModal");
  const confirmInfo = document.getElementById("confirmInfo");

  const btnYes = confirmModal ? confirmModal.querySelector(".yes") : null;
  const btnNo = confirmModal ? confirmModal.querySelector(".no") : null;
  const closeConfirmBtn = confirmModal ? confirmModal.querySelector(".close-btn") : null;
  const closeLoginBtn = loginModal ? loginModal.querySelector(".close-btn") : null;

  let isLoggedIn = false;
  let userRole = null;

  if (!slotsContainer || !doctorId) return;

  fetch("/BumbleCare/handlers/make_appointment_handler.php?action=check_auth")
    .then(res => res.json())
    .then(data => {
      isLoggedIn = data.isLoggedIn;
      userRole = data.role;
    })
    .catch(() => console.warn("Не вдалося перевірити авторизацію"));

  // Завантаження слотів
  fetch(`/BumbleCare/handlers/make_appointment_handler.php?action=get_slots&doctor_id=${doctorId}`)
    .then(res => res.json())
    .then(data => renderSlots(data.slots))
    .catch(() => {
      slotsContainer.innerHTML = "<p class='error'>Помилка завантаження слотів.</p>";
    });

  function renderSlots(slotsData) {
    slotsContainer.innerHTML = "";
    if (!slotsData || Object.keys(slotsData).length === 0) {
      slotsContainer.innerHTML = "<p>Немає доступних дат.</p>";
      return;
    }

    for (const [date, slots] of Object.entries(slotsData)) {
      const dayBlock = document.createElement("div");
      dayBlock.className = "day-block";

      const title = document.createElement("p");
      title.className = "day-title";
      title.textContent = new Date(date).toLocaleDateString("uk-UA", {
        day: "2-digit",
        month: "long",
        year: "numeric"
      });

      const row = document.createElement("div");
      row.className = "slots-row";

      slots.forEach(slot => {
        const btn = document.createElement("button");
        btn.className = `slot-btn ${slot.busy ? "busy" : "free"}`;
        btn.textContent = slot.time;
        btn.dataset.date = date;
        btn.dataset.time = slot.time;

        if (!slot.busy) {
          btn.addEventListener("click", () => handleSlotClick(btn));
        } else {
          btn.disabled = true;
        }

        row.appendChild(btn);
      });

      dayBlock.append(title, row);
      slotsContainer.appendChild(dayBlock);
    }
  }

  function handleSlotClick(btn) {
    const date = btn.dataset.date;
    const time = btn.dataset.time;

    if (!isLoggedIn) {
      openModal(loginModal);
      return;
    }

    if (userRole !== "patient") {
      showPopupMessage("Запис дозволено лише пацієнтам", "error");
      return;
    }

    confirmInfo.innerHTML = `
    Обрана дата: <strong>${new Date(date).toLocaleDateString("uk-UA")}</strong><br>
    Обраний час: <strong>${time} - ${add20Minutes(time)}</strong>
    `;
    openModal(confirmModal);

    btnYes.onclick = () => createAppointment(date, time, btn);
    btnNo.onclick = () => closeModal(confirmModal);
  }

  function add20Minutes(time) {
    const [h, m] = time.split(":").map(Number);
    const date = new Date();
    date.setHours(h, m + 20);
    return date.toTimeString().slice(0, 5);
  }

  // Створення запису
  function createAppointment(date, time, btn) {
    const params = new URLSearchParams({
      action: "create_appointment",
      doctor_id: doctorId,
      date: date,
      time: time
    });

    fetch("/BumbleCare/handlers/make_appointment_handler.php", {
      method: "POST",
      headers: { "Content-Type": "application/x-www-form-urlencoded" },
      body: params.toString()
    })
      .then(res => res.json())
      .then(data => {
        closeModal(confirmModal);
        if (data.success) {
          btn.classList.remove("free");
          btn.classList.add("busy");
          btn.disabled = true;
          showPopupMessage("Запис успішно створено!", "success");
        } else if (data.error === "already_booked") {
          showPopupMessage("У вас уже є активний запис до цього лікаря.", "error");
        } else if (data.error === "slot_busy") {
          showPopupMessage("Цей час вже зайнятий.", "error");
        } else if (data.error === "past_slot") {
          showPopupMessage("Неможливо записатись у минуле.", "error");
        } else {
          showPopupMessage("Щось пішло не так. Спробуйте ще раз.", "error");
        }
      })
      .catch(() => {
        closeModal(confirmModal);
        showPopupMessage("Помилка з'єднання з сервером", "error");
      });
  }

  // Модалки
  function openModal(modal) {
    if (!modal) return;
    modal.classList.remove("hidden");
  }

  function closeModal(modal) {
    if (!modal) return;
    modal.classList.add("hidden");
  }

  if (closeConfirmBtn) {
    closeConfirmBtn.addEventListener("click", () => closeModal(confirmModal));
  }
  if (closeLoginBtn) {
    closeLoginBtn.addEventListener("click", () => closeModal(loginModal));
  }

  [confirmModal, loginModal].forEach(modal => {
    if (!modal) return;
    modal.addEventListener("click", e => {
      if (e.target === modal) closeModal(modal);
    });
  });

});
