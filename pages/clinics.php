<?php
$requireLogin = false;
$allowRoles   = ['patient', 'clinic_admin', 'super_admin'];
$allowGuest   = true;

require_once __DIR__ . '/../includes/check_auth.php';
require_once __DIR__ . '/../includes/db_connect.php';

$page_css = 'clinics.css';
include __DIR__ . '/../includes/header.php';

if ($isLoggedIn && $user_role === 'doctor') {
  header("Location: /BumbleCare/pages/main.php");
  exit;
}

$cities_stmt = $pdo->query("SELECT DISTINCT city FROM clinics WHERE city IS NOT NULL ORDER BY city ASC");
$cities = $cities_stmt->fetchAll(PDO::FETCH_COLUMN);
?>

<main class="clinics-page">
  <div class="bc-container clinics-wrapper">

    <div class="form-block">
      <form id="clinicsSearchForm" class="search-form">
        <div class="search-input-wrapper">
          <input 
            type="text" 
            id="queryInput"
            name="query"
            placeholder="Введіть назву клініки"
          >
          <button type="button" class="btn-clear" id="clearQuery">Скинути</button>
        </div>
        <button type="submit" class="btn-search">Знайти</button>
      </form>

      <form id="clinicsFilterForm" class="filter-form">
        <div class="filter-grid">
          <div class="filter-col">
            <label>Місто</label>
            <select name="city" id="citySelect">
              <option value="">Усі</option>
              <?php foreach ($cities as $ct): ?>
                <option value="<?= htmlspecialchars($ct) ?>"><?= htmlspecialchars($ct) ?></option>
              <?php endforeach; ?>
            </select>
          </div>
        </div>

        <div class="controls-bar">
          <div class="buttons-block">
            <button type="submit" class="btn-apply">Застосувати фільтри</button>
            <button class="btn-reset">Скинути фільтри</button>
          </div>

          <div class="sort-bar">
            <label>Сортувати:</label>
            <select name="sort" id="sortSelect">
              <option value="default">За назвою (а-я)</option>
              <option value="rating">За рейтингом</option>
              <option value="reviews">За кількістю відгуків</option>
            </select>
          </div>
        </div>
      </form>
    </div>

    <div class="results" id="clinicsResults"></div>

  </div>
</main>

<script src="/BumbleCare/assets/js/rating_stars.js"></script>
<script src="/BumbleCare/assets/js/clinics.js"></script>
<?php include __DIR__ . '/../includes/footer.php'; ?>
