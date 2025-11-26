<?php
$requireLogin = true;
$allowRoles   = ['doctor'];
require_once __DIR__ . '/../includes/check_auth.php';
require_once __DIR__ . '/../includes/db_connect.php';

$page_css = 'doctor_appointments.css';
include __DIR__ . '/../includes/header.php';

$stmtDoc = $pdo->prepare("SELECT doctor_id FROM doctors WHERE user_id = ?");
$stmtDoc->execute([$user_id]);
$doctor_id = $stmtDoc->fetchColumn();

if (!$doctor_id) {
    echo "<p class='error'>Помилка: профіль лікаря не знайдено.</p>";
    include __DIR__ . '/../includes/footer.php';
    exit;
}
?>

<main class="doctor-appointments-page">
  <div class="bc-container doctor-appointments-wrapper">
    <div class="form-block">
			<form id="doctorSearchForm" class="search-form">
					<div class="search-input-wrapper">
							<input 
							type="text"
							id="searchInput"
							name="query"
							placeholder="Введіть ПІБ пацієнта"
							>
							<button type="button" class="btn-clear" id="clearSearch">Скинути</button>
					</div>
					<button type="submit" class="btn-search">Знайти</button>
					</form>

					<form id="doctorFilterForm" class="filter-form">
					<div class="filter-grid">
							<div class="filter-col">
							<label>Статус</label>
							<select name="status" id="statusSelect">
									<option value="planned">Заплановані (від сьогодні)</option>
									<option value="all">Усі прийоми</option>
									<option value="completed">Завершені</option>
									<option value="past">Всі минулі (до сьогодні)</option>
							</select>
							</div>

							<div class="filter-col">
							<label>День</label>
							<input type="date" name="day" id="dayInput">
							</div>
					</div>

					<div class="controls-bar">
							<div class="buttons-block">
							<button type="submit" class="btn-apply">Застосувати фільтри</button>
							<button type="button" class="btn-reset" id="resetFilters">Скинути фільтри</button>
							</div>
					</div>
					</form>
    </div>

    <div class="appointments-results" id="appointmentsContainer"></div>
  </div>
	<input type="hidden" id="doctorIdInput" value="<?= (int)$doctor_id ?>">
</main>

<script src="/BumbleCare/assets/js/doctor_appointments.js"></script>
<?php include __DIR__ . '/../includes/footer.php'; ?>
