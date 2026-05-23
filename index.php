<?php
require 'config.php';
require 'header.php';
?>

<section class="card hero">

  <div>
    <div class="soft-badge">
      ✨ <?php echo h($T['badge']); ?>
    </div>

    <h1>
      <?php echo h($T['hero']); ?>
    </h1>

    <p>
      <?php echo h($T['hero_desc']); ?>
    </p>

    <div class="actions">
      <a class="btn primary" href="profile.php?new=1">
        <?php echo h($T['start']); ?>
      </a>
    </div>
  </div>

  <div class="feature-panel">

    <div class="feature">
      <b><?php echo h($T['home_feature_1_title']); ?></b>
      <span><?php echo h($T['home_feature_1_desc']); ?></span>
    </div>

    <div class="feature">
      <b><?php echo h($T['home_feature_2_title']); ?></b>
      <span><?php echo h($T['home_feature_2_desc']); ?></span>
    </div>

    <div class="feature">
      <b><?php echo h($T['home_feature_3_title']); ?></b>
      <span><?php echo h($T['home_feature_3_desc']); ?></span>
    </div>

  </div>

</section>

<?php require 'footer.php'; ?>