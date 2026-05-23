<?php
require 'config.php';

$profile = require_profile($pdo, $session_key);
$commitments = get_commitments($pdo, $session_key);
$goal = get_goal($pdo, $session_key);

if (isset($_GET['new_check'])) {
    unset($_SESSION['new_commitment'], $_SESSION['checked'], $_SESSION['result_ready']);
    redirect('check.php');
}

/* When user clicks Check Decision */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $amount = money_input('amount');
    $duration = int_input('duration', 1);
    $unit = valid_unit($_POST['unit'] ?? 'months');

    if ($name === '' || $amount <= 0) {
        redirect('check.php?error=invalid_amount');
    }

    $tempCommitment = [
        'name' => $name,
        'amount' => $amount,
        'duration' => max(1, $duration),
        'unit' => $unit
    ];

    $_SESSION['new_commitment'] = $tempCommitment;
    $_SESSION['checked'] = true;
    $_SESSION['result_ready'] = false;

    $tempResult = calculate_result($profile, $commitments, $goal, $tempCommitment);

    if ($name !== '' && $amount > 0) {
        $stmt = $pdo->prepare("
            INSERT INTO commitment_checks
            (session_key, name, amount, duration, unit, decision, score)
            VALUES (?, ?, ?, ?, ?, ?, ?)
        ");

        $stmt->execute([
            $session_key,
            $name,
            $amount,
            max(1, $duration),
            $unit,
            $tempResult['decision_key'] ?? $tempResult['decision'] ?? '',
            $tempResult['score'] ?? 0
        ]);
    }

    redirect('check.php?checked=1');
}

/* Latest checked commitment */
$newCommitment = $_SESSION['new_commitment'] ?? [
    'name' => '',
    'amount' => 0,
    'duration' => 1,
    'unit' => 'months'
];

$checked = isset($_GET['checked']) && !empty($_SESSION['checked']);
$result = calculate_result($profile, $commitments, $goal, $newCommitment);

require 'header.php';
?>

<div class="grid two">

    <section class="card">
        <h2><?php echo h($T['checkTitle'] ?? 'Check New Commitment'); ?></h2>
        <p><?php echo h($T['checkDesc'] ?? 'Enter only the new commitment you are considering.'); ?></p>

        <?php if (isset($_GET['error']) && $_GET['error'] === 'invalid_amount'): ?>
            <div class="error-note"><?php echo h($T['invalid_amount']); ?></div>
        <?php endif; ?>

        <div class="current">
            <h3><?php echo h($T['currentData'] ?? 'Current data used'); ?></h3>

            <div class="stats">
                <div>
                    <span><?php echo h($T['income'] ?? 'Income'); ?></span>
                    <b><?php echo money($profile['income'] ?? 0); ?></b>
                </div>

                <div>
                    <span><?php echo h($T['basic_needs'] ?? 'Basic needs'); ?></span>
                    <b><?php echo money($profile['needs'] ?? 0); ?></b>
                </div>

                <div>
                    <span><?php echo h($T['existingCommitmentsShort'] ?? 'Existing commitments'); ?></span>
                    <b><?php echo money(commitment_total($commitments)); ?></b>
                </div>

                <div>
                    <span><?php echo h($T['monthly_savings'] ?? 'Monthly savings'); ?></span>
                    <b><?php echo money($profile['savings'] ?? 0); ?></b>
                </div>

                <div>
                    <span><?php echo h($T['goalSaving'] ?? 'Goal saving'); ?></span>
                    <b><?php echo money($result['goal_monthly'] ?? 0); ?></b>
                </div>

                <div>
                    <span><?php echo h($T['emergencySavingsShort'] ?? 'Emergency savings'); ?></span>
                    <b><?php echo money($profile['emergency_fund'] ?? 0); ?></b>
                </div>
            </div>

            <div class="actions">
                <a class="btn secondary" href="profile.php">
                    <?php echo h($T['improveProfile'] ?? 'Improve Profile'); ?>
                </a>

                <a class="btn secondary" href="goal.php">
                    <?php echo h($T['addGoal'] ?? 'Add Goal'); ?>
                </a>
            </div>
        </div>

        <form method="post" class="form-grid">

            <label>
                <?php echo h($T['newCommitmentName'] ?? 'New Commitment Name'); ?>
                <input
                    name="name"
                    placeholder="e.g. Motorcycle"
                    value="<?php echo h($newCommitment['name'] ?? ''); ?>"
                    required
                >
            </label>

            <label>
                <?php echo h($T['monthlyAmount'] ?? 'Monthly Amount'); ?>
                <input
                    name="amount"
                    type="number"
                    min="0"
                    step="0.01"
                    placeholder="e.g. 300"
                    value="<?php echo ((float)($newCommitment['amount'] ?? 0) > 0) ? h($newCommitment['amount']) : ''; ?>"
                    required
                >
            </label>

            <label>
                <?php echo h($T['duration'] ?? 'Duration'); ?>
                <span class="split">
                    <input
                        name="duration"
                        type="number"
                        min="1"
                        placeholder="e.g. 12"
                        value="<?php echo h($newCommitment['duration'] ?? 1); ?>"
                        required
                    >

                    <select name="unit">
                        <option value="weeks" <?php echo (($newCommitment['unit'] ?? '') === 'weeks') ? 'selected' : ''; ?>>
                            <?php echo h($T['weeks'] ?? 'Weeks'); ?>
                        </option>

                        <option value="months" <?php echo (($newCommitment['unit'] ?? 'months') === 'months') ? 'selected' : ''; ?>>
                            <?php echo h($T['months'] ?? 'Months'); ?>
                        </option>

                        <option value="years" <?php echo (($newCommitment['unit'] ?? '') === 'years') ? 'selected' : ''; ?>>
                            <?php echo h($T['years'] ?? 'Years'); ?>
                        </option>
                    </select>
                </span>
            </label>

            <button class="btn primary full" type="submit">
                <?php echo h($T['checkDecision'] ?? 'Check Decision'); ?>
            </button>

        </form>

        <?php if (!empty($_SESSION['checked'])): ?>
            <a class="btn secondary full" href="check.php?new_check=1"><?php echo h($T['start_new_check']); ?></a>
        <?php endif; ?>
    </section>

    <section class="card">
        <h2><?php echo h($T['quickAnswer'] ?? 'Quick Answer'); ?></h2>

        <?php if (!$checked): ?>
            <div class="empty">
                <?php echo h($T['decisionAppear'] ?? 'Your decision will appear here.'); ?>
            </div>
        <?php else: ?>

            <div class="decision <?php echo h($result['class'] ?? 'warn'); ?>">
                <span><?php echo h($T['decision'] ?? 'Decision'); ?></span>
                <strong><?php echo h($result['decision'] ?? 'Proceed Carefully'); ?></strong>
                <b><?php echo h($result['score'] ?? 0); ?><small>/100</small></b>
                <p><?php echo h($result['warning'] ?? 'Review this commitment carefully.'); ?></p>
            </div>

            <div class="pulse">
                <div>
                    <b><?php echo h($T['commitmentPulse'] ?? 'Commitment Pulse'); ?></b>
                    <b><?php echo h($result['mood'] ?? 'Caution'); ?></b>
                </div>

                <span>
                    <i style="width: <?php echo h($result['score'] ?? 0); ?>%;"></i>
                </span>
            </div>

            <div class="stats">
                <div>
                    <span><?php echo h($T['commitments'] ?? 'Commitments'); ?></span>
                    <b><?php echo number_format($result['commitments_pct'] ?? 0, 1); ?>% / 30%</b>
                </div>

                <div>
                    <span><?php echo h($T['saferLimit'] ?? 'Safer new commitment limit'); ?></span>
                    <b><?php echo money($result['safe_amount'] ?? 0); ?></b>
                </div>
            </div>

            <form method="post" action="result.php">
                <button class="btn primary full" name="show_result" value="1" type="submit">
                    <?php echo h($T['viewResult'] ?? 'View Result'); ?> →
                </button>
            </form>

        <?php endif; ?>
    </section>

</div>

<?php require 'footer.php'; ?>