document.addEventListener("DOMContentLoaded", () => {
  const slotsContainer = document.getElementById("slotsContainer");
  const btnAdd = document.getElementById("addIntervalBtn");

  const dateInput = document.getElementById("workDate");
  const startInput = document.getElementById("startTime");
  const endInput = document.getElementById("endTime");

	const freeModal = document.getElementById("freeSlotModal");
	const busyModal = document.getElementById("busySlotModal");

	const confirmFree = document.getElementById("confirmFree");
	const confirmBusy = document.getElementById("confirmBusy");

	const cancelFree = document.getElementById("cancelFree");
	const cancelBusy = document.getElementById("cancelBusy");

  let selectedDate = null;
  let selectedTime = null;
  let selectedBusy = 0;

  confirmFree.addEventListener("click", () => {
    closeModal(freeModal);
    deleteSlot(selectedDate, selectedTime, selectedBusy);
  });

  confirmBusy.addEventListener("click", () => {
    closeModal(busyModal);
    deleteSlot(selectedDate, selectedTime, selectedBusy);
  });

  [freeModal, busyModal].forEach(modal => {
    modal.addEventListener("click", e => {
      if (e.target === modal) closeModal(modal);
    });
  });

	function openModal(modal) {
		modal.classList.remove("hidden");
	}

	function closeModal(modal) {
		modal.classList.add("hidden");
	}

	document.querySelectorAll(".modal .close-btn").forEach(btn => {
		btn.addEventListener("click", () => {
			closeModal(freeModal);
			closeModal(busyModal);
		});
	});

	[cancelFree, cancelBusy].forEach(btn => {
		btn.addEventListener("click", () => {
			closeModal(freeModal);
			closeModal(busyModal);
		});
	});

  loadSchedule();

    if (dateInput) {
      dateInput.addEventListener("click", () => {
      	dateInput.showPicker();
      });
    }

    if (startInput) {
    	startInput.addEventListener("click", () => {
        startInput.showPicker();
      });
    }

    if (endInput) {
      endInput.addEventListener("click", () => {
        endInput.showPicker();
      });
    }

  function loadSchedule() {
    slotsContainer.innerHTML = "<p class='loading'>Завантаження...</p>";

    fetch(`/BumbleCare/handlers/update_doctor_schedule.php?action=get&doctor_id=${DOCTOR_ID}`)
      .then(res => res.json())
      .then(data => renderSlots(data.slots))
      .catch(() => slotsContainer.innerHTML = "<p class='error'>Помилка завантаження.</p>");
  }

  function renderSlots(data) {
    slotsContainer.innerHTML = "";
    if (!data || Object.keys(data).length === 0) {
      slotsContainer.innerHTML = "<p>Немає робочих інтервалів.</p>";
      return;
    }

    for (const [date, slots] of Object.entries(data)) {
      const block = document.createElement("div");
      block.className = "day-block";

      const title = document.createElement("p");
      title.className = "day-title";
      title.innerText = new Date(date).toLocaleDateString("uk-UA", {
        day: '2-digit', month: 'long', year: 'numeric'
      });

      const row = document.createElement("div");
      row.className = "slots-row";

      slots.forEach(slot => {
        const btn = document.createElement("button");
        btn.className = "slot-btn " + (slot.busy ? "busy" : "free");
        btn.innerText = slot.time;

      btn.addEventListener("click", () => {
        selectedDate = date;
        selectedTime = slot.time;
        selectedBusy = slot.busy ? 1 : 0;

        if (slot.busy) {
          openModal(busyModal);
        } else {
          openModal(freeModal);
        }
      });

        row.appendChild(btn);
      });

      block.append(title, row);
      slotsContainer.appendChild(block);
    }
  }

  function deleteSlot(date, time, busy) {
    const params = new URLSearchParams({
      action: "delete",
      doctor_id: DOCTOR_ID,
      date,
      time,
      busy
    });

    fetch("/BumbleCare/handlers/update_doctor_schedule.php", {
      method: "POST",
      headers: { "Content-Type": "application/x-www-form-urlencoded" },
      body: params.toString()
    })
      .then(r => r.json())
      .then(data => {
        if (data.success) {
          showPopupMessage("Слот видалено", "success");
          loadSchedule();
        } else {
          showPopupMessage("Помилка видалення слоту", "error");
        }
      });
  }

  btnAdd.addEventListener("click", () => {
    const d = dateInput.value;
    const s = startInput.value;
    const e = endInput.value;

    if (!d || !s || !e) {
      showPopupMessage("Заповніть усі поля", "error");
      return;
    }

    const params = new URLSearchParams({
      action: "add",
      doctor_id: DOCTOR_ID,
      date: d,
      start: s,
      end: e
    });

    fetch("/BumbleCare/handlers/update_doctor_schedule.php", {
      method: "POST",
      headers: { "Content-Type": "application/x-www-form-urlencoded" },
      body: params.toString()
    })
      .then(r => r.json())
      .then(data => {
        if (data.success) {
          showPopupMessage("Інтервал додано", "success");
          loadSchedule();
        } else {
          showPopupMessage(data.error || "Помилка", "error");
        }
      });
  });
});
