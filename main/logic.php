<?php
// logic.php

declare(strict_types=1);
require_once __DIR__ . '/../DB/database.php';
session_start();

// 세션에 user_id가 없으면 로그인 페이지로 강제 이동시킵니다.
if (!isset($_SESSION['user_id'])) {
    header('Location: ../login/login.php');
    exit;
}

// ---- 유틸리티 함수 ----
function h(string $s): string
{
    return htmlspecialchars($s, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}
function csrf_token(): string
{
    if (empty($_SESSION['csrf'])) $_SESSION['csrf'] = bin2hex(random_bytes(16));
    return $_SESSION['csrf'];
}
function verify_csrf(): void
{
    if (($_POST['csrf'] ?? '') !== ($_SESSION['csrf'] ?? '')) {
        http_response_code(400);
        exit('Invalid CSRF token');
    }
}
function money_fmt($v): string
{
    return number_format((float)$v);
}

// ---- 상수 및 데이터 로드 ----
$db = get_db_connection();
$categories = get_all_categories();
// 이 예제에서는 사용자를 '1'로 고정합니다. 실제로는 로그인 시스템이 필요합니다.
$current_user_id = (int)$_SESSION['user_id'];

// ---- 입력 처리 ----
$action = $_POST['action'] ?? $_GET['action'] ?? '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    if ($action === 'add') {
        $date = $_POST['d'] ?? '';
        $type = $_POST['type'] === 'income' ? '수입' : '지출';
        $category_id = (int)($_POST['category_id'] ?? 0);
        $memo = trim((string)($_POST['memo'] ?? ''));
        $amount = (float)($_POST['amount'] ?? 0);

        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) $date = date('Y-m-d');
        if (!array_key_exists($category_id, $categories)) $category_id = null;
        if ($amount < 0) $amount = 0;

        $stmt = $db->prepare("INSERT INTO AccountBook (Date, transaction_type, category_id, memo, amount, user_id) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->execute([$date, $type, $category_id, $memo, $amount, $current_user_id]);

        header("Location: ?month=" . urlencode(substr($date, 0, 7)));
        exit;
    } elseif ($action === 'delete') {
        $id = (int)($_POST['id'] ?? 0);
        if ($id > 0) {
            $stmt = $db->prepare("DELETE FROM AccountBook WHERE AccountBook_id=? AND user_id=?");
            $stmt->execute([$id, $current_user_id]);
        }
        $month = $_POST['month'] ?? date('Y-m');
        header("Location: ?month=" . urlencode($month));
        exit;
    }
} elseif ($_SERVER['REQUEST_METHOD'] === 'GET' && $action === 'export_csv') {
    $month = $_GET['month'] ?? date('Y-m');
    if (!preg_match('/^\d{4}-\d{2}$/', $month)) $month = date('Y-m');

    $stmt = $db->prepare("SELECT a.AccountBook_id, a.Date, a.transaction_type, c.category_name, a.memo, a.amount FROM AccountBook a LEFT JOIN Categories c ON a.category_id = c.category_id WHERE substr(a.Date,1,7)=? AND a.user_id=? ORDER BY a.Date ASC, a.AccountBook_id ASC");
    $stmt->execute([$month, $current_user_id]);
    $rows = $stmt->fetchAll();

    header('Content-Type: text/csv; charset=UTF-8');
    header('Content-Disposition: attachment; filename="ledger_' . $month . '.csv"');
    $out = fopen('php://output', 'w');
    fwrite($out, "\xEF\xBB\xBF");
    fputcsv($out, ['ID', '날짜', '구분', '카테고리', '메모', '금액(원)']);
    foreach ($rows as $r) {
        fputcsv($out, [$r['AccountBook_id'], $r['Date'], $r['transaction_type'], $r['category_name'], $r['memo'], $r['amount']]);
    }
    fclose($out);
    exit;
}

// --- 연도와 월을 별도로 받아 처리 ---
$selected_year = (int)($_GET['year'] ?? date('Y'));
$selected_month = (int)($_GET['month'] ?? date('m'));

// 기존 코드와 호환성을 위해 'YYYY-MM' 형식의 $month 변수를 생성합니다.
$month = sprintf('%04d-%02d', $selected_year, $selected_month);

if (!preg_match('/^\d{4}-\d{2}$/', $month)) $month = date('Y-m');

// --- 필터 입력 값 받기 ---
$filter_type = $_GET['filter_type'] ?? ''; // '수입' 또는 '지출'
$filter_cat = (int)($_GET['filter_cat'] ?? 0); // category_id
$filter_memo = trim($_GET['filter_memo'] ?? ''); // 검색할 메모 내용


// --- 데이터베이스 쿼리를 동적으로 생성 ---
$base_sql = "SELECT a.*, c.category_name 
             FROM AccountBook a 
             LEFT JOIN Categories c ON a.category_id = c.category_id";

$where_clauses = [
    "substr(a.Date, 1, 7) = ?",
    "a.user_id = ?"
];
$params = [$month, $current_user_id];

if ($filter_type !== '') {
    $where_clauses[] = "a.transaction_type = ?";
    $params[] = $filter_type;
}
if ($filter_cat > 0) {
    $where_clauses[] = "a.category_id = ?";
    $params[] = $filter_cat;
}
if ($filter_memo !== '') {
    // LIKE 검색을 위해 값 양쪽에 %를 추가합니다.
    $where_clauses[] = "a.memo LIKE ?";
    $params[] = "%{$filter_memo}%";
}

$sql = $base_sql . " WHERE " . implode(" AND ", $where_clauses) . " ORDER BY a.Date DESC, a.AccountBook_id DESC";

$stmt = $db->prepare($sql);
$stmt->execute($params);
$list = $stmt->fetchAll();

$sumIncome = 0.0;
$sumExpense = 0.0;
$byCat = [];
foreach ($list as $r) {
    if ($r['transaction_type'] === '수입') {
        $sumIncome += (float)$r['amount'];
    } else {
        $sumExpense += (float)$r['amount'];
    }
    $catName = $r['category_name'] ?? '미분류';
    $byCat[$catName][$r['transaction_type']] = ($byCat[$catName][$r['transaction_type']] ?? 0) + (float)$r['amount'];
}
$balance = $sumIncome - $sumExpense;

// 이전/다음 달 계산
$monthDate = DateTime::createFromFormat('Y-m', $month) ?: new DateTime('first day of this month');
$prevMonth = (clone $monthDate)->modify('-1 month')->format('Y-m');
$nextMonth = (clone $monthDate)->modify('+1 month')->format('Y-m');

$monthDate = DateTime::createFromFormat('Y-m', $month) ?: new DateTime('first day of this month');
$prevMonthDate = (clone $monthDate)->modify('-1 month');
$nextMonthDate = (clone $monthDate)->modify('+1 month');

// ---- Chart.js를 위한 데이터 가공 ----
$chart_labels = [];
$chart_income_data = [];
$chart_expense_data = [];

// 데이터가 있는 카테고리만 추출하여 라벨로 사용 (순서 보장)
$chart_category_keys = array_keys($byCat);
sort($chart_category_keys); // 가나다순 정렬

foreach ($chart_category_keys as $category_name) {
    $chart_labels[] = $category_name;
    // 해당 카테고리에 수입/지출 데이터가 없으면 0을 사용
    $chart_income_data[] = $byCat[$category_name]['수입'] ?? 0;
    $chart_expense_data[] = $byCat[$category_name]['지출'] ?? 0;
}

// 이 파일은 HTML을 출력하지 않고 여기서 끝납니다.
// 여기에 선언된 모든 변수($month, $list, $sumIncome 등)는
// 이 파일을 require한 파일(index.php)에서 사용할 수 있습니다.