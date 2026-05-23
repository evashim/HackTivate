<?php
session_start();

if (isset($_GET['new']) && $_GET['new'] == '1') {
    $_SESSION = [];
    session_regenerate_id(true);
}

$db_host = 'localhost';
$db_user = 'root';
$db_pass = '';
$db_name = 'cashcue_db';

try {
    $pdo_root = new PDO("mysql:host=$db_host;charset=utf8mb4", $db_user, $db_pass, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
    $pdo_root->exec("CREATE DATABASE IF NOT EXISTS `$db_name` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
    $pdo = new PDO("mysql:host=$db_host;dbname=$db_name;charset=utf8mb4", $db_user, $db_pass, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
} catch (PDOException $e) {
    die("Database connection failed. Please start MySQL in XAMPP. Error: " . htmlspecialchars($e->getMessage()));
}

$pdo->exec("CREATE TABLE IF NOT EXISTS user_profiles (
    session_key VARCHAR(128) PRIMARY KEY,
    income DECIMAL(10,2) DEFAULT 0,
    needs DECIMAL(10,2) DEFAULT 0,
    savings DECIMAL(10,2) DEFAULT 0,
    emergency_fund DECIMAL(10,2) DEFAULT 0,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
)");

$pdo->exec("CREATE TABLE IF NOT EXISTS user_profile_history (
    id INT AUTO_INCREMENT PRIMARY KEY,
    session_key VARCHAR(128) NOT NULL,
    income DECIMAL(10,2) DEFAULT 0,
    needs DECIMAL(10,2) DEFAULT 0,
    savings DECIMAL(10,2) DEFAULT 0,
    emergency_fund DECIMAL(10,2) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)");

$pdo->exec("CREATE TABLE IF NOT EXISTS commitments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    session_key VARCHAR(128) NOT NULL,
    name VARCHAR(100) NOT NULL,
    amount DECIMAL(10,2) NOT NULL,
    category VARCHAR(50) NOT NULL,
    is_active TINYINT(1) DEFAULT 1,
    deleted_at DATETIME NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)");

$pdo->exec("CREATE TABLE IF NOT EXISTS goals (
    session_key VARCHAR(128) PRIMARY KEY,
    name VARCHAR(100) DEFAULT '',
    target DECIMAL(10,2) DEFAULT 0,
    saved DECIMAL(10,2) DEFAULT 0,
    timeline INT DEFAULT 1,
    unit VARCHAR(20) DEFAULT 'months',
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
)");

$pdo->exec("CREATE TABLE IF NOT EXISTS goal_items (
    id INT AUTO_INCREMENT PRIMARY KEY,
    session_key VARCHAR(128) NOT NULL,
    name VARCHAR(100) NOT NULL,
    target DECIMAL(10,2) DEFAULT 0,
    saved DECIMAL(10,2) DEFAULT 0,
    timeline INT DEFAULT 1,
    unit VARCHAR(20) DEFAULT 'months',
    is_active TINYINT(1) DEFAULT 1,
    deleted_at DATETIME NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)");

$pdo->exec("CREATE TABLE IF NOT EXISTS new_commitments (
    session_key VARCHAR(128) PRIMARY KEY,
    name VARCHAR(100) DEFAULT '',
    amount DECIMAL(10,2) DEFAULT 0,
    duration INT DEFAULT 1,
    unit VARCHAR(20) DEFAULT 'months',
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
)");

$pdo->exec("CREATE TABLE IF NOT EXISTS commitment_checks (
    id INT AUTO_INCREMENT PRIMARY KEY,
    session_key VARCHAR(128) NOT NULL,
    name VARCHAR(100) NOT NULL,
    amount DECIMAL(10,2) DEFAULT 0,
    duration INT DEFAULT 1,
    unit VARCHAR(20) DEFAULT 'months',
    decision VARCHAR(50),
    score INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)");

function add_column_if_missing($pdo, $table, $column, $definition) {
    $stmt = $pdo->prepare("
        SELECT COUNT(*) 
        FROM INFORMATION_SCHEMA.COLUMNS 
        WHERE TABLE_SCHEMA = DATABASE() 
        AND TABLE_NAME = ? 
        AND COLUMN_NAME = ?
    ");
    $stmt->execute([$table, $column]);

    if ($stmt->fetchColumn() == 0) {
        $pdo->exec("ALTER TABLE `$table` ADD `$column` $definition");
    }
}

add_column_if_missing($pdo, 'commitments', 'is_active', "TINYINT(1) DEFAULT 1");
add_column_if_missing($pdo, 'commitments', 'deleted_at', "DATETIME NULL");

add_column_if_missing($pdo, 'goal_items', 'is_active', "TINYINT(1) DEFAULT 1");
add_column_if_missing($pdo, 'goal_items', 'deleted_at', "DATETIME NULL");

$session_key = session_id();

if (!isset($_SESSION['lang'])) {
    $_SESSION['lang'] = 'en';
}
if (isset($_GET['lang']) && in_array($_GET['lang'], ['en', 'bm'])) {
    $_SESSION['lang'] = $_GET['lang'];
}
$lang = $_SESSION['lang'];

$T = [
    'en' => [
        'home'=>'Home','profile'=>'Profile','goal'=>'Goal','check'=>'Check','result'=>'Result','tagline'=>'Check before you commit',
        'badge'=>'50/30/20 rule checker','hero'=>'Should I take this monthly commitment?','hero_desc'=>'CashCue gives a simple decision before users commit: Proceed, Proceed Carefully, Reduce, Delay, or Avoid.',
        'start'=>'Start Fast Check','improve'=>'Improve Accuracy','less'=>'Less typing','less_desc'=>'Start with a quick check, then add details only if needed.','rule'=>'Clear rule','rule_desc'=>'The result follows the 50/30/20 salary guide.','visual'=>'Visual answer','visual_desc'=>'Users see where their salary goes after the new commitment.',
        'monthly_income'=>'Monthly Income','basic_needs'=>'Basic Needs','monthly_savings'=>'Monthly Savings','emergency'=>'Current Emergency Savings','profile_desc'=>'Current Emergency Savings means backup money already saved, not monthly expenses.',
        'existing'=>'Existing Commitments','existing_desc'=>'Add current monthly payments only.','existing_total'=>'Existing commitment total','name'=>'Name','amount'=>'Amount','category'=>'Category','add'=>'Add','delete'=>'Delete',
        'next_goal'=>'Next: Set Goal','goal_desc'=>'Optional: add one goal. CashCue converts it into monthly goal saving.','goal_name'=>'Goal Name','target'=>'Target Amount','saved'=>'Already Saved','timeline'=>'Timeline to Achieve','weeks'=>'Weeks','months'=>'Months','years'=>'Years','goal_summary'=>'Goal Summary','remaining_target'=>'Remaining target','monthly_goal'=>'Monthly goal saving','progress'=>'Progress','next_check'=>'Next: Check New Commitment',
        'check_title'=>'Check New Commitment','check_desc'=>'Enter only the new commitment you are considering.','current_data'=>'Current data used','goal_saving'=>'Goal saving','improve_profile'=>'Improve Profile','add_goal'=>'Add Goal','new_commitment'=>'New Commitment Name','monthly_amount'=>'Monthly Amount','duration'=>'Duration','check_decision'=>'Check Decision','quick_answer'=>'Quick Answer','decision_wait'=>'Your decision will appear here.','decision'=>'Decision','pulse'=>'Commitment Pulse','commitments'=>'Commitments','safe_limit'=>'Safer new commitment limit','view_result'=>'View Result',
        'result_desc'=>'Full 50/30/20 breakdown for the checked commitment.','back_check'=>'Back to Check','check_first'=>'Please check a commitment first.','final_decision'=>'Final Decision','status'=>'50/30/20 Status','essentials'=>'Essentials','savings_goal'=>'Savings + Goal','ai_logic'=>'Explainable AI Logic','ai_desc'=>'CashCue combines 50/30/20 allocation, commitment ratio, emergency savings coverage, remaining balance, and duration risk.','salary_allocation'=>'Salary Allocation','remaining_balance'=>'Remaining balance','new_share'=>'New commitment share','total_value'=>'Total value',
        'Proceed'=>'Proceed','Proceed Carefully'=>'Proceed Carefully','Reduce'=>'Reduce','Delay'=>'Delay','Avoid'=>'Avoid','Balanced'=>'Balanced','High Risk'=>'High Risk','Too Heavy'=>'Too Heavy','Wait First'=>'Wait First','Caution'=>'Caution','On Track'=>'On Track','Needs Adjustment'=>'Needs Adjustment','Off Track'=>'Off Track'
    ],
    'bm' => [
        'home'=>'Utama','profile'=>'Profil','goal'=>'Matlamat','check'=>'Semak','result'=>'Keputusan','tagline'=>'Semak sebelum komited',
        'badge'=>'Penyemak peraturan 50/30/20','hero'=>'Patutkah saya ambil komitmen bulanan ini?','hero_desc'=>'CashCue memberi keputusan mudah sebelum pengguna membuat komitmen: Teruskan, Teruskan Dengan Berhati-hati, Kurangkan, Tangguh, atau Elakkan.',
        'start'=>'Mula Semakan Pantas','improve'=>'Tingkatkan Ketepatan','less'=>'Kurang input','less_desc'=>'Mulakan dengan semakan pantas, kemudian tambah butiran jika perlu.','rule'=>'Peraturan jelas','rule_desc'=>'Keputusan berpandukan panduan gaji 50/30/20.','visual'=>'Jawapan visual','visual_desc'=>'Pengguna boleh lihat ke mana gaji digunakan selepas komitmen baharu.',
        'monthly_income'=>'Pendapatan Bulanan','basic_needs'=>'Keperluan Asas','monthly_savings'=>'Simpanan Bulanan','emergency'=>'Simpanan Kecemasan Semasa','profile_desc'=>'Simpanan Kecemasan Semasa bermaksud wang sandaran yang sudah ada, bukan perbelanjaan bulanan.',
        'existing'=>'Komitmen Sedia Ada','existing_desc'=>'Tambah bayaran bulanan semasa sahaja.','existing_total'=>'Jumlah komitmen sedia ada','name'=>'Nama','amount'=>'Jumlah','category'=>'Kategori','add'=>'Tambah','delete'=>'Padam',
        'next_goal'=>'Seterusnya: Tetapkan Matlamat','goal_desc'=>'Pilihan: tambah satu matlamat. CashCue menukarkannya kepada simpanan matlamat bulanan.','goal_name'=>'Nama Matlamat','target'=>'Jumlah Sasaran','saved'=>'Sudah Disimpan','timeline'=>'Tempoh Untuk Capai','weeks'=>'Minggu','months'=>'Bulan','years'=>'Tahun','goal_summary'=>'Ringkasan Matlamat','remaining_target'=>'Baki sasaran','monthly_goal'=>'Simpanan matlamat bulanan','progress'=>'Kemajuan','next_check'=>'Seterusnya: Semak Komitmen Baharu',
        'check_title'=>'Semak Komitmen Baharu','check_desc'=>'Masukkan komitmen baharu yang sedang dipertimbangkan sahaja.','current_data'=>'Data semasa digunakan','goal_saving'=>'Simpanan matlamat','improve_profile'=>'Baiki Profil','add_goal'=>'Tambah Matlamat','new_commitment'=>'Nama Komitmen Baharu','monthly_amount'=>'Jumlah Bulanan','duration'=>'Tempoh','check_decision'=>'Semak Keputusan','quick_answer'=>'Jawapan Pantas','decision_wait'=>'Keputusan anda akan dipaparkan di sini.','decision'=>'Keputusan','pulse'=>'Nadi Komitmen','commitments'=>'Komitmen','safe_limit'=>'Had selamat komitmen baharu','view_result'=>'Lihat Keputusan',
        'result_desc'=>'Pecahan penuh 50/30/20 untuk komitmen yang disemak.','back_check'=>'Kembali ke Semak','check_first'=>'Sila semak komitmen dahulu.','final_decision'=>'Keputusan Akhir','status'=>'Status 50/30/20','essentials'=>'Keperluan','savings_goal'=>'Simpanan + Matlamat','ai_logic'=>'Logik AI Boleh Dijelaskan','ai_desc'=>'CashCue menggabungkan pembahagian 50/30/20, nisbah komitmen, liputan simpanan kecemasan, baki wang, dan risiko tempoh.','salary_allocation'=>'Pembahagian Gaji','remaining_balance'=>'Baki wang','new_share'=>'Bahagian komitmen baharu','total_value'=>'Jumlah nilai',
        'Proceed'=>'Teruskan','Proceed Carefully'=>'Teruskan Berhati-hati','Reduce'=>'Kurangkan','Delay'=>'Tangguh','Avoid'=>'Elakkan','Balanced'=>'Seimbang','High Risk'=>'Risiko Tinggi','Too Heavy'=>'Terlalu Berat','Wait First'=>'Tunggu Dahulu','Caution'=>'Berhati-hati','On Track'=>'Baik','Needs Adjustment'=>'Perlu Larasan','Off Track'=>'Tidak Seimbang'
    ]
][$lang];

/* Short homepage text */
if ($lang === 'bm') {
    $T['badge'] = 'Penyemak 50/30/20';
    $T['hero'] = 'Patutkah saya komited?';
    $T['hero_desc'] = 'Semak kemampuan sebelum ambil komitmen baharu. CashCue menilai pendapatan, komitmen, simpanan, matlamat, dan memberi keputusan ringkas.';
    $T['start'] = 'Mula Semakan →';

    $T['home_feature_1_title'] = '💰 Isi langkah demi langkah';
    $T['home_feature_1_desc'] = 'Profil, matlamat, kemudian semak komitmen baharu.';

    $T['home_feature_2_title'] = '📊 Panduan 50/30/20';
    $T['home_feature_2_desc'] = 'Bandingkan gaji dengan keperluan, komitmen, dan simpanan.';

    $T['home_feature_3_title'] = '✅ Keputusan jelas';
    $T['home_feature_3_desc'] = 'Dapatkan keputusan seperti Teruskan, Kurangkan, Tangguh, atau Elakkan.';
} else {
    $T['badge'] = '50/30/20 checker';
    $T['hero'] = 'Should I commit?';
    $T['hero_desc'] = 'Check affordability before taking a new commitment. CashCue reviews income, commitments, savings, goals, and gives a clear decision.';
    $T['start'] = 'Start Check →';

    $T['home_feature_1_title'] = '💰 Guided steps';
    $T['home_feature_1_desc'] = 'Fill in profile, add goals, then check a new commitment.';

    $T['home_feature_2_title'] = '📊 50/30/20 guide';
    $T['home_feature_2_desc'] = 'Compare salary with needs, commitments, and savings.';

    $T['home_feature_3_title'] = '✅ Clear decision';
    $T['home_feature_3_desc'] = 'See whether to proceed, reduce, delay, or avoid.';
}
// Compatibility aliases used by the separated page files.
// This prevents undefined array key warnings when a page uses camelCase labels.
$T['step'] = $T['step'] ?? ($lang === 'bm' ? 'Langkah' : 'Step');
$T['of'] = $T['of'] ?? ($lang === 'bm' ? 'daripada' : 'of');
$T['profileTitle'] = $T['profileTitle'] ?? $T['profile'];
$T['profileDesc'] = $T['profileDesc'] ?? ($T['profile_desc'] ?? '');
$T['goalTitle'] = $T['goalTitle'] ?? $T['goal'];
$T['resultTitle'] = $T['resultTitle'] ?? $T['result'];
$T['existingCommitmentsShort'] = $T['existingCommitmentsShort'] ?? ($lang === 'bm' ? 'Komitmen sedia ada' : 'Existing commitments');
$T['emergencySavingsShort'] = $T['emergencySavingsShort'] ?? ($lang === 'bm' ? 'Simpanan kecemasan' : 'Emergency savings');
$T['income'] = $T['income'] ?? ($lang === 'bm' ? 'Pendapatan' : 'Income');
$T['untitled'] = $T['untitled'] ?? ($lang === 'bm' ? 'Tiada nama' : 'Untitled');
$T['lifestyle'] = $T['lifestyle'] ?? ($lang === 'bm' ? 'Gaya hidup' : 'Lifestyle');
$T['education'] = $T['education'] ?? ($lang === 'bm' ? 'Pendidikan' : 'Education');
$T['vehicle'] = $T['vehicle'] ?? ($lang === 'bm' ? 'Kenderaan' : 'Vehicle');
$T['family'] = $T['family'] ?? ($lang === 'bm' ? 'Keluarga' : 'Family');
$T['protection'] = $T['protection'] ?? ($lang === 'bm' ? 'Perlindungan' : 'Protection');
$T['loan'] = $T['loan'] ?? ($lang === 'bm' ? 'Pinjaman' : 'Loan');
$T['save_profile'] = $T['save_profile'] ?? ($lang === 'bm' ? 'Simpan Profil' : 'Save Profile');
$T['profile_required'] = $T['profile_required'] ?? ($lang === 'bm' ? 'Sila simpan profil gaji dahulu sebelum membuka Matlamat, Semak, atau Keputusan.' : 'Please save your salary profile first before opening Goal, Check, or Result.');
$T['profile_saved'] = $T['profile_saved'] ?? ($lang === 'bm' ? 'Profil berjaya disimpan. Nilai terkini kekal di borang dan sejarah disimpan dalam database.' : 'Profile saved. The latest values stay in the form and the old submission is stored in database history.');
$T['goal_added'] = $T['goal_added'] ?? ($lang === 'bm' ? 'Matlamat berjaya ditambah.' : 'Goal added successfully.');
$T['goal_deleted'] = $T['goal_deleted'] ?? ($lang === 'bm' ? 'Matlamat disembunyikan daripada sistem, tetapi sejarah masih kekal dalam database.' : 'Goal removed from the screen, but the record still remains in the database.');
$T['commitment_added'] = $T['commitment_added'] ?? ($lang === 'bm' ? 'Komitmen berjaya ditambah.' : 'Commitment added successfully.');
$T['invalid_amount'] = $T['invalid_amount'] ?? ($lang === 'bm' ? 'Sila masukkan nilai yang sah. Nombor negatif tidak dibenarkan.' : 'Please enter valid values. Negative numbers are not allowed.');
$T['add_new_goal'] = $T['add_new_goal'] ?? ($lang === 'bm' ? 'Tambah Matlamat Baharu +' : 'Add New Goal +');
$T['total_monthly_goal_saving'] = $T['total_monthly_goal_saving'] ?? ($lang === 'bm' ? 'Jumlah simpanan matlamat bulanan' : 'Total monthly goal saving');
$T['current_goals'] = $T['current_goals'] ?? ($lang === 'bm' ? 'Matlamat Semasa' : 'Current Goals');
$T['goal_card_desc'] = $T['goal_card_desc'] ?? ($lang === 'bm' ? 'Setiap matlamat dipaparkan sebagai kad ringkasan kecil.' : 'Each goal is shown as a small summary card.');
$T['no_goal_added'] = $T['no_goal_added'] ?? ($lang === 'bm' ? 'Tiada matlamat lagi. Tambah matlamat pertama untuk melihat ringkasan.' : 'No goal added yet. Add your first goal to see the summary card.');
$T['saved_more_than_target'] = $T['saved_more_than_target'] ?? ($lang === 'bm' ? 'Jumlah sudah disimpan tidak boleh melebihi jumlah sasaran.' : 'Already saved should not be more than the target amount.');
$T['checked_commitment_summary'] = $T['checked_commitment_summary'] ?? ($lang === 'bm' ? 'Ringkasan Komitmen Disemak' : 'Checked Commitment Summary');
$T['start_new_check'] = $T['start_new_check'] ?? ($lang === 'bm' ? 'Mula Semakan Baharu' : 'Start New Check');
$T['no_commitment'] = $T['no_commitment'] ?? ($lang === 'bm' ? 'Tiada komitmen ditambah lagi.' : 'No commitment added yet.');
$T['form_reset'] = $T['form_reset'] ?? ($lang === 'bm' ? 'Kosongkan borang' : 'Reset form');
$T['confirm_reset'] = $T['confirm_reset'] ?? ($lang === 'bm' ? 'Kosongkan borang profil? Sejarah masih kekal dalam database.' : 'Clear the profile form? Your history will still remain in database.');
$T['commitment_removed'] = $T['commitment_removed'] ?? ($lang === 'bm' ? 'Komitmen disembunyikan daripada sistem, tetapi sejarah masih kekal dalam database.' : 'Commitment removed from the screen, but the record still remains in the database.');
$T['goalSaving'] = $T['goal_saving'];
$T['improveProfile'] = $T['improve_profile'];
$T['addGoal'] = $T['add_goal'];
$T['newCommitmentName'] = $T['new_commitment'];
$T['monthlyAmount'] = $T['monthly_amount'];
$T['checkDecision'] = $T['check_decision'];
$T['quickAnswer'] = $T['quick_answer'];
$T['decisionAppear'] = $T['decision_wait'];
$T['commitmentPulse'] = $T['pulse'];
$T['saferLimit'] = $T['safe_limit'];
$T['viewResult'] = $T['view_result'];
$T['resultDesc'] = $T['result_desc'];
$T['checkFirst'] = $T['check_first'];
$T['backToCheck'] = $T['back_check'];
$T['finalDecision'] = $T['final_decision'];
$T['statusTitle'] = $T['status'];
$T['savingsGoal'] = $T['savings_goal'];
$T['explainableAI'] = $T['ai_logic'];
$T['salaryAllocation'] = $T['salary_allocation'];
$T['remainingBalance'] = $T['remaining_balance'];
$T['newShare'] = $T['new_share'];
$T['totalValue'] = $T['total_value'];

function money($v) { return 'RM' . number_format((float)$v, 2); }
function h($v) { return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8'); }
function redirect($page) { header("Location: $page"); exit; }
function money_input($key) {
    if (!isset($_POST[$key]) || $_POST[$key] === '') return 0;
    return max(0, (float)$_POST[$key]);
}
function int_input($key, $default = 1) {
    if (!isset($_POST[$key]) || $_POST[$key] === '') return $default;
    return max(1, (int)$_POST[$key]);
}
function valid_unit($unit) {
    return in_array($unit, ['weeks', 'months', 'years'], true) ? $unit : 'months';
}
function has_valid_profile($profile) {
    return isset($profile['income']) && (float)$profile['income'] > 0;
}
function require_profile($pdo, $session_key) {
    $profile = get_profile($pdo, $session_key);
    if (!has_valid_profile($profile)) {
        redirect('profile.php?error=profile_required');
    }
    return $profile;
}

function get_profile($pdo, $session_key) {
    $stmt = $pdo->prepare("SELECT * FROM user_profiles WHERE session_key=?");
    $stmt->execute([$session_key]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    return $row ?: ['income'=>0,'needs'=>0,'savings'=>0,'emergency_fund'=>0];
}
function get_goals($pdo, $session_key) {
    $stmt = $pdo->prepare("
        SELECT * 
        FROM goal_items 
        WHERE session_key = ? 
        AND is_active = 1
        ORDER BY id DESC
    ");
    $stmt->execute([$session_key]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function goal_monthly_amount($goal) {
    $target = (float)($goal['target'] ?? 0);
    $saved = (float)($goal['saved'] ?? 0);
    $timeline = max(1, (float)($goal['timeline'] ?? 1));
    $unit = $goal['unit'] ?? 'months';

    $months = months_from($timeline, $unit);
    return max(0, ($target - $saved) / max(1, $months));
}

function get_goal($pdo, $session_key) {
    $goals = get_goals($pdo, $session_key);

    $totalTarget = 0;
    $totalSaved = 0;
    $totalMonthly = 0;

    foreach ($goals as $g) {
        $totalTarget += (float)$g['target'];
        $totalSaved += (float)$g['saved'];
        $totalMonthly += goal_monthly_amount($g);
    }

    return [
        'name' => count($goals) > 1 ? 'Multiple goals' : ($goals[0]['name'] ?? ''),
        'target' => $totalTarget,
        'saved' => $totalSaved,
        'timeline' => 1,
        'unit' => 'months',
        'monthly_total' => $totalMonthly
    ];
}
function get_new_commitment($pdo, $session_key) {
    $stmt = $pdo->prepare("SELECT * FROM new_commitments WHERE session_key=?");
    $stmt->execute([$session_key]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    return $row ?: ['name'=>'','amount'=>0,'duration'=>1,'unit'=>'months'];
}
function get_commitments($pdo, $session_key) {
    $stmt = $pdo->prepare("
        SELECT * 
        FROM commitments 
        WHERE session_key = ? 
        AND is_active = 1
        ORDER BY id DESC
    ");
    $stmt->execute([$session_key]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function commitment_total($commitments) {
    return array_sum(array_map(fn($c) => (float)$c['amount'], $commitments));
}
function months_from($number, $unit) {
    $number = max(1, (float)$number);
    if ($unit === 'weeks') return max(1, round($number / 4.345));
    if ($unit === 'years') return $number * 12;
    return $number;
}
function calculate_result($profile, $commitments, $goal, $newCommitment) {
    $income = (float)($profile['income'] ?? 0);
    $needs = (float)($profile['needs'] ?? 0);
    $savings = (float)($profile['savings'] ?? 0);
    $emergencyFund = (float)($profile['emergency_fund'] ?? 0);

    $existingCommitments = commitment_total($commitments);

    $newAmount = (float)($newCommitment['amount'] ?? 0);
    $duration = max(1, (float)($newCommitment['duration'] ?? 1));
    $unit = $newCommitment['unit'] ?? 'months';

    $durationMonths = months_from($duration, $unit);

    if (isset($goal['monthly_total'])) {
        $goalMonthly = (float)$goal['monthly_total'];
    } else {
        $goalMonths = months_from($goal['timeline'] ?? 1, $goal['unit'] ?? 'months');
        $goalMonthly = max(0, ((float)($goal['target'] ?? 0) - (float)($goal['saved'] ?? 0)) / max(1, $goalMonths));
    }

    $commitmentsAfter = $existingCommitments + $newAmount;
    $savingsAndGoal = $savings + $goalMonthly;
    $remaining = $income - $needs - $commitmentsAfter - $savingsAndGoal;

    $essentialsPct = $income > 0 ? ($needs / $income) * 100 : 0;
    $commitmentsPct = $income > 0 ? ($commitmentsAfter / $income) * 100 : 0;
    $savingsPct = $income > 0 ? ($savingsAndGoal / $income) * 100 : 0;
    $remainingPct = $income > 0 ? ($remaining / $income) * 100 : 0;

    $emergencyMonths = $needs > 0 ? $emergencyFund / $needs : 0;

    // This is the maximum monthly amount for the NEW commitment only.
    $safeNewCommitmentLimit = max(0, ($income * 0.30) - $existingCommitments);
    $overSafeLimit = max(0, $newAmount - $safeNewCommitmentLimit);

    // New commitment percentage only.
    $newCommitmentPct = $income > 0 ? ($newAmount / $income) * 100 : 0;

    /*
      Logical scoring:
      - Amount/commitment ratio is most important.
      - Duration is a caution factor, not automatic Delay.
      - Emergency savings affects safety, but should not override a safe amount too aggressively.
    */

    $score = 100;

    // Commitment ratio penalty
    if ($commitmentsPct > 45) {
        $score -= 35;
    } elseif ($commitmentsPct > 30) {
        $score -= min(30, round(($commitmentsPct - 30) * 2) + 10);
    } elseif ($commitmentsPct > 25) {
        $score -= round(($commitmentsPct - 25) * 1.2);
    }

    // Basic needs penalty
    if ($essentialsPct > 60) {
        $score -= 20;
    } elseif ($essentialsPct > 50) {
        $score -= 10;
    }

    // Savings penalty
    if ($savingsPct < 10) {
        $score -= 15;
    } elseif ($savingsPct < 20) {
        $score -= 8;
    }

    // Remaining balance penalty
    if ($remaining < 0) {
        $score -= 35;
    } elseif ($remainingPct < 5) {
        $score -= 12;
    } elseif ($remainingPct < 10) {
        $score -= 6;
    }

    // Emergency fund penalty
    if ($emergencyMonths < 0.5) {
        $score -= 10;
    } elseif ($emergencyMonths < 1) {
        $score -= 6;
    } elseif ($emergencyMonths < 3) {
        $score -= 3;
    }

    // Duration penalty: long duration is caution, not automatic delay
    if ($durationMonths > 84) {
        $score -= 12;
    } elseif ($durationMonths > 60) {
        $score -= 9;
    } elseif ($durationMonths > 36) {
        $score -= 6;
    } elseif ($durationMonths > 12) {
        $score -= 3;
    }

    // New amount above safe limit penalty
    if ($overSafeLimit > 0) {
        $overPct = $income > 0 ? ($overSafeLimit / $income) * 100 : 0;
        $score -= min(15, round($overPct * 2));
    }

    $score = max(0, min(100, round($score)));

    // Main warning priority
    $warning = "Your new commitment is within the 30% commitment guide.";

    if ($remaining < 0) {
        $warning = "Monthly allocation is over budget.";
    } elseif ($commitmentsPct > 30) {
        $warning = "Commitments exceed the 30% guide.";
    } elseif ($newAmount > $safeNewCommitmentLimit) {
        $warning = "The new commitment is higher than the safer monthly limit.";
    } elseif ($durationMonths > 36) {
        $warning = "The monthly amount is affordable, but the commitment is long-term.";
    } elseif ($emergencyMonths < 1) {
        $warning = "The amount is affordable, but emergency savings are still weak.";
    } elseif ($savingsPct < 20) {
        $warning = "The amount is affordable, but savings are below the 20% guide.";
    }

    /*
      Decision logic:
      1. Avoid = dangerous / over budget
      2. Reduce = amount exceeds 30% commitment guide
      3. Delay = only if weak emergency/savings AND long duration together
      4. Proceed Carefully = affordable but has caution factors
      5. Proceed = healthy
    */

    $decision = "Proceed";
    $mood = "Balanced";
    $tone = "safe";
    $advice = "This commitment fits your salary balance. Keep your savings consistent.";

    if ($remaining < 0 || $commitmentsPct > 45 || $score < 40) {
        $decision = "Avoid";
        $mood = "High Risk";
        $tone = "danger";
        $advice = "Avoid this commitment for now because it may create monthly financial stress.";
    } elseif ($commitmentsPct > 30 || $newAmount > $safeNewCommitmentLimit) {
        $decision = "Reduce";
        $mood = "Too Heavy";
        $tone = "warn";
        $advice = "Reduce the monthly amount so total commitments stay within the 30% guide.";
    } elseif (
        ($durationMonths > 60 && $emergencyMonths < 1) ||
        ($durationMonths > 60 && $savingsPct < 15) ||
        ($score < 60)
    ) {
        $decision = "Delay";
        $mood = "Wait First";
        $tone = "orange";
        $advice = "Delay this commitment because the long duration and weak savings/emergency buffer increase risk.";
    } elseif (
        $durationMonths > 36 ||
        $emergencyMonths < 1 ||
        $savingsPct < 20 ||
        $score < 80
    ) {
        $decision = "Proceed Carefully";
        $mood = "Caution";
        $tone = "warn";
        $advice = "The monthly amount is within the safer limit, but review the long duration, savings, or emergency buffer.";
    }

    $status = "On Track";

    if ($decision === "Reduce" || $decision === "Proceed Carefully" || $decision === "Delay") {
        $status = "Needs Adjustment";
    }

    if ($decision === "Avoid") {
        $status = "Off Track";
    }

    return [
        'score' => $score,
        'decision' => $decision,
        'mood' => $mood,
        'tone' => $tone,
        'class' => $tone,
        'warning' => $warning,
        'advice' => $advice,
        'status' => $status,

        'essentials_pct' => $essentialsPct,
        'commitments_pct' => $commitmentsPct,
        'savings_pct' => $savingsPct,
        'remaining_pct' => $remainingPct,

        'remaining' => $remaining,
        'duration_months' => $durationMonths,
        'safe_amount' => $safeNewCommitmentLimit,
        'new_commitment_pct' => $newCommitmentPct,
        'total_obligation' => $newAmount * $durationMonths,
        'commitments_after' => $commitmentsAfter,
        'savings_goal' => $savingsAndGoal,
        'goal_monthly' => $goalMonthly,
        'emergency_months' => $emergencyMonths,

        // Extra aliases in case your page uses camelCase
        'safeAmount' => $safeNewCommitmentLimit,
        'commitmentsPct' => $commitmentsPct,
        'newCommitmentPct' => $newCommitmentPct,
        'durationMonths' => $durationMonths,
        'totalObligation' => $newAmount * $durationMonths,
    ];
}
?>
