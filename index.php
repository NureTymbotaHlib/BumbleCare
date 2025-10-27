<?php
  $page_css = 'home.css';
  include __DIR__.'/includes/header.php';
?>

<main>
  <!-- HERO -->
<section class="home-hero">
  <div class="bc-container home-hero__inner">
    <div class="home-hero__left">
      <h1>Замість черги — <span class="c-primary">BumbleCare</span></h1>
      <p>Забудь про черги — запишись до лікаря онлайн за кілька секунд і отримай якісну медичну допомогу поруч із тобою.</p>
      <a href="#" class="home-cta">ЗНАЙТИ ЛІКАРЯ</a>
    </div>
    <div class="home-hero__right">
      <img src="/BumbleCare/assets/images/doctors-hero.png" alt="Doctors team">
    </div>
  </div>
</section>

<!-- INFO -->
<section class="home-info">
  <div class="bc-container home-info__inner">
    <div class="home-info__left">
      <img src="/BumbleCare/assets/images/team-info.png" alt="Doctors group">
    </div>
    <div class="home-info__right">
      <h2><span class="c-primary">Bumble</span>Care — твоє здоров’я в один клік</h2>
      <p>
        Потрібна консультація просто зараз?<br>
        Або хочеш знайти найкращого фахівця у своєму місті?<br><br>
        <strong>BumbleCare</strong> допоможе знайти перевірених лікарів, записатися онлайн і отримати підтримку без зайвого стресу.<br><br>
          Без дзвінків. Без очікування. Без турбот.
      </p>
    </div>
  </div>
</section>
</main>

<?php include __DIR__.'/includes/footer.php'; ?>
