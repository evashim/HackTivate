<?php
require 'config.php';

$profile = require_profile($pdo, $session_key);

function goal_months_local($timeline, $unit) {
  $timeline = max(1, (float)$timeline);

  if ($unit === 'weeks') {
    return max(1, round($timeline / 4.345));
  }

  if ($unit === 'years') {
    return $timeline * 12;
  }

  return $timeline;
}

function goal_monthly_local($goal) {
  $target = (float)($goal['target'] ?? 0);
  $saved = (float)($goal['saved'] ?? 0);
  $timeline = (float)($goal['timeline'] ?? 1);
  $unit = $goal['unit'] ?? 'months';

  $months = goal_months_local($timeline, $unit);

  return max(0, ($target - $saved) / max(1, $months));
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  if (isset($_POST['delete_goal'])) {
    $stmt = $pdo->prepare("
      UPDATE goal_items
      SET is_active = 0, deleted_at = NOW()
      WHERE id = ? AND session_key = ?
    ");
    $stmt->execute([$_POST['delete_goal'], $session_key]);

    redirect('goal.php?goal_deleted=1');
  }

  if (isset($_POST['add_goal'])) {
    $name = trim($_POST['name'] ?? '');
    $target = money_input('target');
    $saved = money_input('saved');
    $timeline = int_input('timeline', 1);
    $unit = valid_unit($_POST['unit'] ?? 'months');

    if ($saved > $target) {
      redirect('goal.php?error=saved_more_than_target');
    }

    if ($name !== '' && $target > 0) {
      $stmt = $pdo->prepare("
        INSERT INTO goal_items
        (session_key, name, target, saved, timeline, unit)
        VALUES (?, ?, ?, ?, ?, ?)
      ");

      $stmt->execute([
        $session_key,
        $name,
        $target,
        $saved,
        max(1, $timeline),
        $unit
      ]);

      redirect('goal.php?goal_added=1');
    }

    redirect('goal.php?error=invalid_amount');
  }
}

$stmt = $pdo->prepare("SELECT * FROM goal_items WHERE session_key = ? AND is_active = 1 ORDER BY id DESC");
$stmt->execute([$session_key]);
$goals = $stmt->fetchAll(PDO::FETCH_ASSOC);

$totalMonthly = 0;

foreach ($goals as $g) {
  $totalMonthly += goal_monthly_local($g);
}

require 'header.php';
?>

<div class="goal-page-layout">

  <section class="card">
    <h2><?php echo h($T['goal']); ?></h2>
    <p><?php echo h($T['goal_desc']); ?></p>

    <?php if (isset($_GET['goal_added'])): ?>
      <div class="success-note"><?php echo h($T['goal_added']); ?></div>
    <?php endif; ?>

    <?php if (isset($_GET['goal_deleted'])): ?>
      <div class="success-note"><?php echo h($T['goal_deleted']); ?></div>
    <?php endif; ?>

    <?php if (isset($_GET['error']) && $_GET['error'] === 'saved_more_than_target'): ?>
      <div class="error-note"><?php echo h($T['saved_more_than_target']); ?></div>
    <?php endif; ?>

    <?php if (isset($_GET['error']) && $_GET['error'] === 'invalid_amount'): ?>
      <div class="error-note"><?php echo h($T['invalid_amount']); ?></div>
    <?php endif; ?>

    <form method="post" class="profile-form">

      <label>
        <?php echo h($T['goal_name']); ?>
        <input
          name="name"
          placeholder="e.g. iPhone 17"
          required
        >
      </label>

      <label>
        <?php echo h($T['target']); ?>
        <input
          name="target"
          type="number"
          min="0"
          step="0.01"
          placeholder="e.g. 5000"
          required
        >
      </label>

      <label>
        <?php echo h($T['saved']); ?>
        <input
          name="saved"
          type="number"
          min="0"
          step="0.01"
          placeholder="e.g. 600"
        >
      </label>

      <label>
        <?php echo h($T['timeline']); ?>
        <span class="split">
          <input
            name="timeline"
            type="number"
            min="1"
            placeholder="e.g. 12"
            required
          >

          <select name="unit">
            <option value="weeks"><?php echo h($T['weeks']); ?></option>
            <option value="months" selected><?php echo h($T['months']); ?></option>
            <option value="years"><?php echo h($T['years']); ?></option>
          </select>
        </span>
      </label>

      <button class="btn primary full" name="add_goal" type="submit">
        <?php echo h($T['add_new_goal']); ?>
      </button>

    </form>

    <div class="highlight">
      <span><?php echo h($T['total_monthly_goal_saving']); ?></span>
      <strong><?php echo money($totalMonthly); ?></strong>
    </div>

    <a class="btn primary full" href="check.php">
      <?php echo h($T['next_check']); ?> →
    </a>
  </section>

  <section class="card goal-list-card">
    <h2><?php echo h($T['current_goals']); ?></h2>
    <p><?php echo h($T['goal_card_desc']); ?></p>

    <?php if (empty($goals)): ?>
      <div class="empty-goal">
        <?php echo h($T['no_goal_added']); ?>
      </div>
    <?php endif; ?>

    <div class="goal-card-grid">
      <?php foreach ($goals as $g): ?>
        <?php
          $remaining = max(0, (float)$g['target'] - (float)$g['saved']);
          $monthly = goal_monthly_local($g);
          $progress = (float)$g['target'] > 0
            ? min(100, ((float)$g['saved'] / (float)$g['target']) * 100)
            : 0;
        ?>

        <div class="mini-goal-card">
          <div class="mini-goal-head">
            <h3><?php echo h($g['name']); ?></h3>
            <form method="post" class="inline-delete" onsubmit="return confirm('Remove this goal from the screen? It will remain in database history.');">
              <button class="delete-x" name="delete_goal" value="<?php echo h($g['id']); ?>" title="<?php echo h($T['delete']); ?>" type="submit">×</button>
            </form>
          </div>

          <div class="mini-goal-stats">
            <div>
              <span><?php echo h($T['remaining_target']); ?></span>
              <b><?php echo money($remaining); ?></b>
            </div>

            <div>
              <span><?php echo h($T['monthly_goal']); ?></span>
              <b><?php echo money($monthly); ?></b>
            </div>

            <div>
              <span><?php echo h($T['progress']); ?></span>
              <b><?php echo number_format($progress, 0); ?>%</b>
            </div>

            <div>
              <span><?php echo h($T['timeline']); ?></span>
              <b><?php echo h($g['timeline'] . ' ' . $g['unit']); ?></b>
            </div>
          </div>

          <div class="mini-progress">
            <span style="width: <?php echo $progress; ?>%;"></span>
          </div>
        </div>

      <?php endforeach; ?>
    </div>
  </section>

</div>

<?php require 'footer.php'; ?>
