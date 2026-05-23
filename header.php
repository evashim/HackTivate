<?php
$current = basename($_SERVER['PHP_SELF']);

$steps = [
  'profile.php' => $T['profile'],
  'goal.php' => $T['goal'],
  'check.php' => $T['check'],
  'result.php' => $T['result']
];

$stepIndex = 0;
$stepLabel = '';

$i = 1;
foreach ($steps as $file => $label) {
  if ($current === $file) {
    $stepIndex = $i;
    $stepLabel = $label;
  }
  $i++;
}

$progress = $stepIndex > 0 ? ($stepIndex / 4) * 100 : 0;
?>
<!DOCTYPE html>
<html lang="<?php echo h($lang); ?>">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>CashCue</title>
  <link rel="stylesheet" href="style.css">
  <script src="script.js"></script>
</head>

<body>

<header class="site-header">
  <a class="brand" href="index.php">
    <span class="logo">
      <img src="logo.png" alt="CashCue logo">
    </span>

    <span>
      <strong>CashCue</strong>
      <small><?php echo h($T['tagline']); ?></small>
    </span>
  </a>

  <nav class="main-nav">
    <a class="<?php echo $current === 'index.php' ? 'active' : ''; ?>" href="index.php">
      <?php echo h($T['home']); ?>
    </a>

    <a class="<?php echo $current === 'profile.php' ? 'active' : ''; ?>" href="profile.php">
      <?php echo h($T['profile']); ?>
    </a>

    <a class="<?php echo $current === 'goal.php' ? 'active' : ''; ?>" href="goal.php">
      <?php echo h($T['goal']); ?>
    </a>

    <a class="<?php echo $current === 'check.php' ? 'active' : ''; ?>" href="check.php">
      <?php echo h($T['check']); ?>
    </a>

    <a class="<?php echo $current === 'result.php' ? 'active' : ''; ?>" href="result.php">
      <?php echo h($T['result']); ?>
    </a>
  </nav>

  <div class="right-tools">
    <span class="pill">50/30/20</span>

    <span class="lang">
      <a class="<?php echo $lang === 'en' ? 'active' : ''; ?>" href="?lang=en">ENG</a>
      <a class="<?php echo $lang === 'bm' ? 'active' : ''; ?>" href="?lang=bm">BM</a>
    </span>
  </div>
</header>

<main class="container">

<?php if ($current !== 'index.php' && $stepIndex > 0): ?>
  <section class="steps-card simple-step">
    <div class="step-top">
      <span class="step-badge">
        <?php echo h($T['step'] . ' ' . $stepIndex . ' ' . $T['of'] . ' 4: ' . $stepLabel); ?>
      </span>
    </div>

    <div class="bar">
      <span style="width: <?php echo $progress; ?>%;"></span>
    </div>
  </section>
<?php endif; ?>