<?php
require 'config.php';

if (isset($_GET['reset_profile'])) {
  // Clear only the current form values for this browser session.
  // History remains saved in user_profile_history.
  $stmt = $pdo->prepare("DELETE FROM user_profiles WHERE session_key = ?");
  $stmt->execute([$session_key]);

  redirect('profile.php');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

  if (isset($_POST['save_profile'])) {
    $income = money_input('income');
    $needs = money_input('needs');
    $savings = money_input('savings');
    $emergency_fund = money_input('emergency_fund');

    // 1) Save every submission as history first.
    $history = $pdo->prepare("
      INSERT INTO user_profile_history
      (session_key, income, needs, savings, emergency_fund)
      VALUES (?, ?, ?, ?, ?)
    ");

    $history->execute([
      $session_key,
      $income,
      $needs,
      $savings,
      $emergency_fund
    ]);

    // 2) Save latest value as the active/current profile used by the checker.
    $stmt = $pdo->prepare("
      INSERT INTO user_profiles
      (session_key, income, needs, savings, emergency_fund)
      VALUES (?, ?, ?, ?, ?)
      ON DUPLICATE KEY UPDATE
        income = VALUES(income),
        needs = VALUES(needs),
        savings = VALUES(savings),
        emergency_fund = VALUES(emergency_fund),
        updated_at = CURRENT_TIMESTAMP
    ");

    $stmt->execute([
      $session_key,
      $income,
      $needs,
      $savings,
      $emergency_fund
    ]);

    redirect('profile.php?saved=1');
  }

  if (isset($_POST['delete_commitment'])) {
    $stmt = $pdo->prepare("
      UPDATE commitments
      SET is_active = 0, deleted_at = NOW()
      WHERE id = ? AND session_key = ?
    ");
    $stmt->execute([$_POST['delete_commitment'], $session_key]);

    redirect('profile.php?commitment_removed=1');
  }

  if (isset($_POST['add_commitment'])) {
    $name = trim($_POST['c_name'] ?? '');
    $amount = money_input('c_amount');

    $allowedCategories = [
      'Lifestyle',
      'Education',
      'Vehicle',
      'Family',
      'Protection',
      'Loan',
      'Others'
    ];

    $category = $_POST['c_category'] ?? 'Lifestyle';

    if (!in_array($category, $allowedCategories, true)) {
      $category = 'Others';
    }

    if ($name !== '' && $amount > 0) {
      $stmt = $pdo->prepare("
        INSERT INTO commitments
        (session_key, name, amount, category)
        VALUES (?, ?, ?, ?)
      ");

      $stmt->execute([
        $session_key,
        $name,
        $amount,
        $category
      ]);

      redirect('profile.php?commitment_added=1');
    }

    redirect('profile.php?error=invalid_amount');
  }
}

$profile = get_profile($pdo, $session_key);
$commitments = get_commitments($pdo, $session_key);
$total = commitment_total($commitments);

function old_value($profile, $key) {
  if (!isset($profile[$key])) return '';
  $value = (float) $profile[$key];
  return $value > 0 ? h($profile[$key]) : '';
}

require 'header.php';
?>

<div class="grid two profile-layout">

  <section class="card">
    <div class="section-title-row">
      <h2><?php echo h($T['profileTitle'] ?? 'Profile'); ?></h2>
      <a
        class="icon-reset"
        href="profile.php?reset_profile=1"
        title="<?php echo h($T['form_reset'] ?? 'Reset form'); ?>"
        onclick="return confirm('<?php echo h($T['confirm_reset'] ?? 'Reset the current profile form? History will remain saved.'); ?>');"
      >↻</a>
    </div>

    <p><?php echo h($T['profileDesc'] ?? 'Enter your monthly financial information.'); ?></p>

    <?php if (isset($_GET['error']) && $_GET['error'] === 'profile_required'): ?>
      <div class="error-note"><?php echo h($T['profile_required'] ?? 'Please save your profile first.'); ?></div>
    <?php endif; ?>

    <?php if (isset($_GET['error']) && $_GET['error'] === 'invalid_amount'): ?>
      <div class="error-note"><?php echo h($T['invalid_amount'] ?? 'Please enter a valid name and amount.'); ?></div>
    <?php endif; ?>

    <?php if (isset($_GET['saved'])): ?>
      <div class="success-note"><?php echo h($T['profile_saved'] ?? 'Profile saved successfully.'); ?></div>
    <?php endif; ?>

    <?php if (isset($_GET['commitment_added'])): ?>
      <div class="success-note"><?php echo h($T['commitment_added'] ?? 'Commitment added successfully.'); ?></div>
    <?php endif; ?>

    <?php if (isset($_GET['commitment_removed'])): ?>
      <div class="success-note"><?php echo h($T['commitment_removed'] ?? 'Commitment removed from current view.'); ?></div>
    <?php endif; ?>

    <form method="post" class="profile-form">

      <label>
        <?php echo h($T['monthly_income'] ?? 'Monthly Income'); ?>
        <input
          name="income"
          type="number"
          min="0"
          step="0.01"
          placeholder="e.g. 4200"
          value="<?php echo old_value($profile, 'income'); ?>"
        >
      </label>

      <label>
        <?php echo h($T['basic_needs'] ?? 'Basic Needs'); ?>
        <input
          name="needs"
          type="number"
          min="0"
          step="0.01"
          placeholder="e.g. 1800"
          value="<?php echo old_value($profile, 'needs'); ?>"
        >
      </label>

      <label>
        <?php echo h($T['monthly_savings'] ?? 'Monthly Savings'); ?>
        <input
          name="savings"
          type="number"
          min="0"
          step="0.01"
          placeholder="e.g. 600"
          value="<?php echo old_value($profile, 'savings'); ?>"
        >
      </label>

      <label>
        <?php echo h($T['emergency'] ?? 'Emergency Fund'); ?>
        <input
          name="emergency_fund"
          type="number"
          min="0"
          step="0.01"
          placeholder="e.g. 3500"
          value="<?php echo old_value($profile, 'emergency_fund'); ?>"
        >
      </label>

      <button class="btn primary full" name="save_profile" type="submit">
        <?php echo h($T['save_profile'] ?? 'Save Profile'); ?>
      </button>

    </form>

    <div class="highlight">
      <span><?php echo h($T['existing_total'] ?? 'Existing Total'); ?></span>
      <strong><?php echo money($total); ?></strong>
    </div>

    <a class="btn primary full" href="goal.php">
      <?php echo h($T['next_goal'] ?? 'Next: Goal'); ?> →
    </a>
  </section>

  <section class="card">
    <h2><?php echo h($T['existing'] ?? 'Existing Commitments'); ?></h2>
    <p><?php echo h($T['existing_desc'] ?? 'Add current monthly payments only.'); ?></p>

    <form method="post" class="commit-form">

      <input
        name="c_name"
        placeholder="e.g. Car"
      >

      <input
        name="c_amount"
        type="number"
        min="0"
        step="0.01"
        placeholder="RM"
      >

      <select name="c_category">
        <option value="Lifestyle"><?php echo h($T['lifestyle'] ?? 'Lifestyle'); ?></option>
        <option value="Education"><?php echo h($T['education'] ?? 'Education'); ?></option>
        <option value="Vehicle"><?php echo h($T['vehicle'] ?? 'Vehicle'); ?></option>
        <option value="Family"><?php echo h($T['family'] ?? 'Family'); ?></option>
        <option value="Protection"><?php echo h($T['protection'] ?? 'Protection'); ?></option>
        <option value="Loan"><?php echo h($T['loan'] ?? 'Loan'); ?></option>
        <option value="Others"><?php echo h($T['others'] ?? 'Others'); ?></option>
      </select>

      <button class="round" name="add_commitment" type="submit">+</button>

    </form>

    <div class="list">
      <?php if (empty($commitments)): ?>
        <p class="muted"><?php echo h($T['no_commitment'] ?? 'No commitment added yet.'); ?></p>
      <?php endif; ?>

      <?php foreach ($commitments as $c): ?>
        <div class="list-item">
          <span>
            <b><?php echo h($c['name']); ?></b>
            <small><?php echo h($c['category']); ?></small>
          </span>

          <span class="list-right">
            <b><?php echo money($c['amount']); ?></b>
            <form
              method="post"
              class="inline-delete"
              onsubmit="return confirm('Remove this commitment from the screen? It will remain in database history.');"
            >
              <button
                class="delete-x"
                name="delete_commitment"
                value="<?php echo h($c['id']); ?>"
                title="<?php echo h($T['delete'] ?? 'Delete'); ?>"
                type="submit"
              >×</button>
            </form>
          </span>
        </div>
      <?php endforeach; ?>
    </div>
  </section>

</div>

<?php require 'footer.php'; ?>