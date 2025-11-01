document.addEventListener("DOMContentLoaded", () => {
  const editBtn = document.getElementById("editDoctorInfo");
  const form = document.querySelector(".doctor-info-form");

  if (!form || !editBtn) return;

  const inputs = form.querySelectorAll("input, textarea, select");

  const originalValues = {};
  inputs.forEach(i => (originalValues[i.name] = i.value));

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
      editBtn.textContent = "Зберегти";
    } else {
      const formData = new FormData(form);

      fetch("/BumbleCare/handlers/update_doctor_info.php", {
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

  const birthInput = form.querySelector("input[type='date']");
  const ageInput = Array.from(inputs).find(i => i.name === "age");

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

  function resetEditState() {
    editBtn.textContent = "Редагувати";
    inputs.forEach(i => (i.disabled = true));
  }

  const tabs = document.querySelectorAll('.tabs .tab');
  const contents = document.querySelectorAll('.tab-content');

  tabs.forEach((tab, index) => {
      tab.addEventListener('click', () => {
      tabs.forEach(t => t.classList.remove('active'));
      contents.forEach(c => c.classList.add('hidden'));

      tab.classList.add('active');
      contents[index].classList.remove('hidden');
      });
  });


  // СОРТИРОВКА ОТЗЫВОВ
  const sortSelect = document.getElementById("sort");
  const reviewsContainer = document.getElementById("reviews-container");

  if (sortSelect && reviewsContainer) {
    const reviewCards = Array.from(reviewsContainer.querySelectorAll(".review-card"));

    sortSelect.addEventListener("change", () => {
      const sortValue = sortSelect.value;
      const sorted = [...reviewCards];

    sorted.sort((a, b) => {
      const ratingA = parseFloat(a.querySelector(".review-stars").dataset.rating || 0);
      const ratingB = parseFloat(b.querySelector(".review-stars").dataset.rating || 0);


        const parseDate = (str) => {
          const [day, month, year, time] = str.split(/[\s.]+/);
          return new Date(`20${year}-${month}-${day}T${time}:00`);
        };
        const dateA = parseDate(a.querySelector(".review-date").textContent);
        const dateB = parseDate(b.querySelector(".review-date").textContent);

        switch (sortValue) {
          case "rating_asc": return ratingA - ratingB;
          case "rating_desc": return ratingB - ratingA;
          case "date_asc": return dateA - dateB;
          case "date_desc": return dateB - dateA;
          default: return 0;
        }
      });

      reviewsContainer.style.opacity = "0";
      setTimeout(() => {
        reviewsContainer.innerHTML = "";
        sorted.forEach(card => reviewsContainer.appendChild(card));
        reviewsContainer.style.opacity = "1";
      }, 200);
    });
  }



  document.querySelectorAll(".stars-container, .review-stars").forEach(starBlock => {
    const rating = parseFloat(starBlock.dataset.rating || 0);
    const percent = Math.min(100, Math.max(0, (rating / 5) * 100));
    starBlock.style.setProperty("--fill-width", `${percent}%`);
  });

  
});
