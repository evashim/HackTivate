<?php
require 'config.php';
require 'ai_advice.php';

$profile = require_profile($pdo, $session_key);

if (isset($_GET['new_check'])) {
    unset($_SESSION['new_commitment'], $_SESSION['checked'], $_SESSION['result_ready']);
    redirect('check.php');
}

$canShowResult = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['show_result']) && !empty($_SESSION['checked'])) {
    $_SESSION['result_ready'] = true;
    $canShowResult = true;
} elseif (!empty($_SESSION['result_ready'])) {
    $canShowResult = true;
}

/* Keep result visible after refresh. It is cleared only when user clicks Start New Check. */
$commitments = get_commitments($pdo, $session_key);
$goal = get_goal($pdo, $session_key);

$newCommitment = $_SESSION['new_commitment'] ?? [
    'name' => '',
    'amount' => 0,
    'duration' => 1,
    'unit' => 'months'
];

$result = calculate_result($profile, $commitments, $goal, $newCommitment);

/* Safe aliases */
$score = $result['score'] ?? 0;
$decision = $result['decision'] ?? 'Proceed Carefully';
$advice = $result['advice'] ?? 'Review your 50/30/20 allocation before committing.';
$status = $result['status'] ?? 'Needs Adjustment';
$tone = $result['class'] ?? $result['tone'] ?? 'warn';

$essPct = $result['essentials_pct'] ?? 0;
$comPct = $result['commitments_pct'] ?? 0;
$savPct = $result['savings_pct'] ?? 0;

$remaining = $result['remaining'] ?? 0;
$safeAmount = $result['safe_amount'] ?? 0;
$newPct = $result['new_commitment_pct'] ?? 0;
$durationMonths = $result['duration_months'] ?? 0;
$totalObligation = $result['total_obligation'] ?? 0;

$checkedName = $newCommitment['name'] ?? '';
$checkedAmount = (float)($newCommitment['amount'] ?? 0);
$checkedDuration = (int)($newCommitment['duration'] ?? 1);
$checkedUnit = $newCommitment['unit'] ?? 'months';

$income = (float)($profile['income'] ?? 0);
$needs = (float)($profile['needs'] ?? 0);
$existingTotal = commitment_total($commitments);
$newCommitmentAmount = (float)($newCommitment['amount'] ?? 0);
$totalCommitmentsForChart = $existingTotal + $newCommitmentAmount;

$goalMonthly = $result['goal_monthly'] ?? 0;
$savingsGoal = $result['savings_goal'] ?? ((float)($profile['savings'] ?? 0) + (float)$goalMonthly);

$remainingPct = $income > 0 ? ($remaining / $income) * 100 : 0;

/* Chart display values cannot use negative number, so negative remaining becomes 0 in chart only. */
$chartNeeds = max(0, $needs);
$chartCommitments = max(0, $totalCommitmentsForChart);
$chartSavings = max(0, $savingsGoal);
$chartRemaining = max(0, $remaining);

$aiData = [
    'decision' => $decision,
    'score' => $score,
    'income' => $income,
    'essentials_pct' => number_format($essPct, 1),
    'commitments_pct' => number_format($comPct, 1),
    'savings_pct' => number_format($savPct, 1),
    'remaining' => number_format($remaining, 2),
    'commitment_name' => $newCommitment['name'] ?? 'New commitment',
    'commitment_amount' => $newCommitmentAmount,
    'safe_amount' => number_format($safeAmount, 2),
    'duration_months' => $durationMonths
];

$aiAdvice = get_ai_advice($aiData);

require 'header.php';
?>

<?php if (!$canShowResult): ?>

    <section class="card">
        <h2><?php echo h($T['resultTitle'] ?? 'Result'); ?></h2>

        <div class="empty">
            <?php echo h($T['checkFirst'] ?? 'Please check a commitment first.'); ?>
        </div>

        <a class="btn primary full" href="check.php">
            <?php echo h($T['backToCheck'] ?? 'Back to Check'); ?> →
        </a>
    </section>

<?php else: ?>

<div class="grid two">

    <section class="card">

        <h2><?php echo h($T['resultTitle'] ?? 'Result'); ?></h2>
        <p><?php echo h($T['resultDesc'] ?? 'Full 50/30/20 breakdown for the checked commitment.'); ?></p>

        <div class="decision <?php echo h($tone); ?>">
            <span><?php echo h($T['finalDecision'] ?? 'Final Decision'); ?></span>
            <strong><?php echo h($decision); ?></strong>
            <b><?php echo h($score); ?><small>/100</small></b>
            <p><?php echo h($advice); ?></p>
        </div>

        <div class="checked-summary">
            <h3><?php echo h($T['checked_commitment_summary'] ?? 'Checked Commitment Summary'); ?></h3>

            <div class="stats">
                <div>
                    <span><?php echo h($T['name'] ?? 'Name'); ?></span>
                    <b><?php echo h($checkedName !== '' ? $checkedName : '-'); ?></b>
                </div>

                <div>
                    <span><?php echo h($T['monthly_amount'] ?? 'Monthly Amount'); ?></span>
                    <b><?php echo money($checkedAmount); ?></b>
                </div>

                <div>
                    <span><?php echo h($T['duration'] ?? 'Duration'); ?></span>
                    <b><?php echo h($checkedDuration . ' ' . $checkedUnit); ?></b>
                </div>
            </div>
        </div>

        <div class="rule-box">
            <div class="rule-head">
                <h3><?php echo h($T['statusTitle'] ?? '50/30/20 Status'); ?></h3>
                <span><?php echo h($status); ?></span>
            </div>

            <div class="rule">
                <div>
                    <b><?php echo h($T['essentials'] ?? 'Essentials'); ?></b>
                    <b><?php echo number_format($essPct, 1); ?>% / 50%</b>
                </div>
                <span>
                    <i class="blue" style="width: <?php echo min(100, max(0, $essPct)); ?>%;"></i>
                    <em style="left: 50%;"></em>
                </span>
            </div>

            <div class="rule">
                <div>
                    <b><?php echo h($T['commitments'] ?? 'Commitments'); ?></b>
                    <b><?php echo number_format($comPct, 1); ?>% / 30%</b>
                </div>
                <span>
                    <i class="orange" style="width: <?php echo min(100, max(0, $comPct)); ?>%;"></i>
                    <em style="left: 30%;"></em>
                </span>
            </div>

            <div class="rule">
                <div>
                    <b><?php echo h($T['savingsGoal'] ?? 'Savings + Goal'); ?></b>
                    <b><?php echo number_format($savPct, 1); ?>% / 20%</b>
                </div>
                <span>
                    <i class="green" style="width: <?php echo min(100, max(0, $savPct)); ?>%;"></i>
                    <em style="left: 20%;"></em>
                </span>
            </div>
        </div>

        <?php
        $logicPoints = [];

        if ($comPct <= 30) {
            $logicPoints[] = "Total commitments are " . number_format($comPct, 1) . "% of income, within the 30% guide.";
        } else {
            $logicPoints[] = "Total commitments are " . number_format($comPct, 1) . "% of income, above the 30% guide.";
        }

        if ($newCommitmentAmount <= $safeAmount) {
            $logicPoints[] = "The new monthly amount is below the safer limit of " . money($safeAmount) . ".";
        } else {
            $logicPoints[] = "The new monthly amount is above the safer limit of " . money($safeAmount) . ".";
        }

        if ($savPct >= 20) {
            $logicPoints[] = "Savings and goal saving meet the 20% guide.";
        } else {
            $logicPoints[] = "Savings and goal saving are below the 20% guide.";
        }

        if ($durationMonths > 36) {
            $logicPoints[] = "The commitment lasts " . h($durationMonths) . " months, so it is treated as long-term risk.";
        } else {
            $logicPoints[] = "The duration is " . h($durationMonths) . " months, which is not too long.";
        }
        ?>

        <div class="ai rule-explanation">
            <h3>Rule-Based Explanation</h3>
            <p>CashCue gives <b><?php echo h($decision); ?></b> because:</p>

            <ul class="logic-list">
                <?php foreach ($logicPoints as $point): ?>
                    <li><?php echo h($point); ?></li>
                <?php endforeach; ?>
            </ul>
        </div>

        <div class="actions stack-actions">
            <a class="btn secondary full" href="check.php">
                <?php echo h($T['backToCheck'] ?? 'Back to Check'); ?>
            </a>

            <a class="btn primary full" href="result.php?new_check=1">
                <?php echo h($T['start_new_check'] ?? 'Start New Check'); ?>
            </a>
        </div>

    </section>

    <section class="card summary-card">
        <h2><?php echo h($T['salaryAllocation'] ?? 'Salary Allocation'); ?></h2>

        <div class="chart-container">
            <canvas id="allocationChart"></canvas>
        </div>

        <p class="chart-hint">Hover on the pie chart to view amount and percentage.</p>

        <div class="stats">
            <div>
                <span><?php echo h($T['remainingBalance'] ?? 'Remaining balance'); ?></span>
                <b><?php echo money($remaining); ?></b>
            </div>

            <div>
                <span><?php echo h($T['saferLimit'] ?? 'Safer new commitment limit'); ?></span>
                <b><?php echo money($safeAmount); ?></b>
            </div>

            <div>
                <span><?php echo h($T['newShare'] ?? 'New commitment share'); ?></span>
                <b><?php echo number_format($newPct, 1); ?>%</b>
            </div>

            <div>
                <span><?php echo h($T['duration'] ?? 'Duration'); ?></span>
                <b><?php echo h($durationMonths); ?> <?php echo h($T['months'] ?? 'months'); ?></b>
            </div>

            <div>
                <span><?php echo h($T['totalValue'] ?? 'Total value'); ?></span>
                <b><?php echo money($totalObligation); ?></b>
            </div>
        </div>

        <div class="ai-advice-box">
            <div class="ai-badge">Generated by AI API</div>
            <h3>CashCue AI Advice</h3>
            <p><?php echo nl2br(h($aiAdvice)); ?></p>
        </div>

    </section>

</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const canvas = document.getElementById('allocationChart');

    if (!canvas) return;

    const allocationLabels = [
        <?php echo json_encode($T['basic_needs'] ?? 'Basic Needs'); ?>,
        <?php echo json_encode($T['commitments'] ?? 'Commitments'); ?>,
        <?php echo json_encode($T['savingsGoal'] ?? 'Savings + Goal'); ?>,
        <?php echo json_encode($T['remainingBalance'] ?? 'Remaining balance'); ?>
    ];

    const chartValues = [
        <?php echo json_encode((float)$chartNeeds); ?>,
        <?php echo json_encode((float)$chartCommitments); ?>,
        <?php echo json_encode((float)$chartSavings); ?>,
        <?php echo json_encode((float)$chartRemaining); ?>
    ];

    const actualValues = [
        <?php echo json_encode((float)$needs); ?>,
        <?php echo json_encode((float)$totalCommitmentsForChart); ?>,
        <?php echo json_encode((float)$savingsGoal); ?>,
        <?php echo json_encode((float)$remaining); ?>
    ];

    const income = <?php echo json_encode((float)$income); ?>;

    function formatMoney(value) {
        const sign = value < 0 ? '-' : '';
        const absValue = Math.abs(value);

        return sign + 'RM' + absValue.toLocaleString('en-MY', {
            minimumFractionDigits: 2,
            maximumFractionDigits: 2
        });
    }

    function getIncomePercentage(value) {
        if (income <= 0) return '0.0';
        return ((value / income) * 100).toFixed(1);
    }

    new Chart(canvas, {
        type: 'pie',
        data: {
            labels: allocationLabels,
            datasets: [{
                data: chartValues,
                backgroundColor: [
                    '#60a5fa',
                    '#fb923c',
                    '#34d399',
                    '#cbd5e1'
                ],
                borderColor: '#ffffff',
                borderWidth: 5,
                hoverOffset: 24,
                hoverBorderWidth: 7,
                hoverBorderColor: '#ffffff'
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,

            animation: {
                animateRotate: true,
                animateScale: true,
                duration: 1100,
                easing: 'easeOutQuart'
            },

            layout: {
                padding: 12
            },

            plugins: {
                legend: {
                    position: 'bottom',
                    labels: {
                        usePointStyle: true,
                        pointStyle: 'rectRounded',
                        padding: 18,
                        boxWidth: 14,
                        boxHeight: 14,
                        color: '#475569',
                        font: {
                            size: 13,
                            weight: '700'
                        }
                    }
                },

                tooltip: {
                    enabled: true,
                    backgroundColor: '#0f172a',
                    titleColor: '#ffffff',
                    bodyColor: '#ffffff',
                    borderColor: 'rgba(255, 255, 255, 0.18)',
                    borderWidth: 1,
                    padding: 14,
                    cornerRadius: 14,
                    displayColors: true,
                    boxPadding: 6,

                    callbacks: {
                        title: function (items) {
                            return items[0].label;
                        },

                        label: function (context) {
                            const index = context.dataIndex;
                            const value = actualValues[index] || 0;
                            const percentage = getIncomePercentage(value);

                            return ' ' + formatMoney(value) + ' • ' + percentage + '% of income';
                        },

                        afterLabel: function (context) {
                            if (context.label === allocationLabels[3] && actualValues[3] < 0) {
                                return ' Negative balance means overspending risk.';
                            }

                            return '';
                        }
                    }
                }
            },

            interaction: {
                mode: 'nearest',
                intersect: true
            },

            onHover: function (event, activeElements) {
                event.native.target.style.cursor = activeElements.length ? 'pointer' : 'default';
            }
        }
    });
});
</script>

<?php endif; ?>

<?php require 'footer.php'; ?>