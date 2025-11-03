<?php
$requireLogin = false;
$allowRoles   = ['patient'];
$allowGuest   = true;
require_once __DIR__ . '/../includes/check_auth.php';
require_once __DIR__ . '/../includes/db_connect.php';

$page_css = 'search.css';
include __DIR__ . '/../includes/header.php';

if ($isLoggedIn && !in_array($user_role, ['patient'])) {
    header("Location: /BumbleCare/pages/main.php");
    exit;
}

$clinics_stmt = $pdo->query("SELECT clinic_id, name, city FROM clinics ORDER BY name ASC");
$clinics = $clinics_stmt->fetchAll(PDO::FETCH_ASSOC);

$cities = [];
foreach ($clinics as $cl) {
  if (!empty($cl['city']) && !in_array($cl['city'], $cities)) {
    $cities[] = $cl['city'];
  }
}
sort($cities);

$specialties_stmt = $pdo->query("SELECT DISTINCT specialty FROM doctors WHERE specialty IS NOT NULL AND specialty <> '' ORDER BY specialty ASC");
$specialties = $specialties_stmt->fetchAll(PDO::FETCH_COLUMN);
?>

<main class="search-page">
  <div class="bc-container search-wrapper">

    <form method="GET" id="searchForm" class="search-form">
      <div class="search-top">
        <input 
          type="text" 
          name="query" 
          placeholder="Введіть спеціальність або ПІБ лікаря"
        >
        <button type="submit" class="btn-search">Знайти</button>
      </div>

      <div class="filter-grid">
        <div class="filter-col">
          <label>Спеціальність</label>
          <select name="specialty" id="specialtySelect">
            <option value="">Усі</option>
            <?php foreach ($specialties as $s): ?>
              <option value="<?= htmlspecialchars($s) ?>"><?= htmlspecialchars($s) ?></option>
            <?php endforeach; ?>
          </select>
        </div>

        <div class="filter-col">
          <label>Місто</label>
          <select name="city" id="citySelect">
            <option value="">Усі</option>
            <?php foreach ($cities as $ct): ?>
              <option value="<?= htmlspecialchars($ct) ?>"><?= htmlspecialchars($ct) ?></option>
            <?php endforeach; ?>
          </select>
        </div>

        <div class="filter-col">
          <label>Клініка</label>
          <select name="clinic_id" id="clinicSelect">
            <option value="">Усі</option>
            <?php foreach ($clinics as $cl): ?>
              <option 
                value="<?= $cl['clinic_id'] ?>" 
                data-city="<?= htmlspecialchars($cl['city']) ?>">
                <?= htmlspecialchars($cl['name']) ?>
              </option>
            <?php endforeach; ?>
          </select>
        </div>
      </div>

      <div class="controls-bar">
        <button type="submit" class="btn-apply">Застосувати фільтри</button>
        <div class="sort-bar">
          <label>Сортувати:</label>
          <select name="sort" id="sortSelect">
            <option value="default">За замовчанням (а-я)</option>
            <option value="rating">За рейтингом</option>
            <option value="reviews">За кількістю відгуків</option>
          </select>
        </div>
      </div>
    </form>

    <div class="results" id="resultsContainer"></div>
  </div>
</main>


<script src="/BumbleCare/assets/js/search.js"></script>
<?php include __DIR__ . '/../includes/footer.php'; ?>
