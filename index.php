<?php
// index.php — 단일 파일 가계부 (SQLite)
// 만든 목적: 빠른 설치, 기본 보안(CSRF/XSS), 월별 집계, CSV 내보내기

declare(strict_types=1);
session_start();

// ---- 설정 ----
const DB_FILE = __DIR__ . '/ledger.sqlite';
const CATEGORIES = ['식비','교통','주거','통신','의료','교육','문화','여가','쇼핑','경조사','저축','기타'];

// ---- 유틸 ----
function h(string $s): string { return htmlspecialchars($s, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); }
function csrf_token(): string {
    if (empty($_SESSION['csrf'])) $_SESSION['csrf'] = bin2hex(random_bytes(16));
    return $_SESSION['csrf'];
}
function verify_csrf(): void {
    if (($_POST['csrf'] ?? '') !== ($_SESSION['csrf'] ?? '')) {
        http_response_code(400);
        exit('Invalid CSRF token');
    }
}
function money_fmt($v): string {
    return number_format((int)$v);
}

// ---- DB 초기화 ----
function db(): PDO {
    static $pdo;
    if ($pdo) return $pdo;
    $pdo = new PDO('sqlite:' . DB_FILE, null, null, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);
    $pdo->exec('PRAGMA journal_mode = WAL;');
    $pdo->exec('PRAGMA foreign_keys = ON;');
    // 테이블 생성
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS entries (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            d DATE NOT NULL,                                  -- YYYY-MM-DD
            type TEXT NOT NULL CHECK (type IN ('income','expense')),
            category TEXT NOT NULL,
            memo TEXT DEFAULT '',
            amount INTEGER NOT NULL CHECK (amount >= 0),      -- 금액(원)
            created_at TEXT NOT NULL DEFAULT (datetime('now','localtime'))
        );
        CREATE INDEX IF NOT EXISTS idx_entries_d ON entries(d);
        CREATE INDEX IF NOT EXISTS idx_entries_cat ON entries(category);
    ");
    return $pdo;
}

// ---- 입력 처리 ----
$action = $_POST['action'] ?? $_GET['action'] ?? '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    if ($action === 'add') {
        $d = $_POST['d'] ?? '';
        $type = $_POST['type'] === 'income' ? 'income' : 'expense';
        $category = $_POST['category'] ?? '기타';
        $memo = trim((string)($_POST['memo'] ?? ''));
        $amount = (int)($_POST['amount'] ?? 0);

        // 기본 검증
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $d)) $d = date('Y-m-d');
        if (!in_array($category, CATEGORIES, true)) $category = '기타';
        if ($amount < 0) $amount = 0;

        $stmt = db()->prepare("INSERT INTO entries (d,type,category,memo,amount) VALUES (?,?,?,?,?)");
        $stmt->execute([$d,$type,$category,$memo,$amount]);

        header("Location: ?month=" . urlencode(substr($d,0,7)));
        exit;
    } elseif ($action === 'delete') {
        $id = (int)($_POST['id'] ?? 0);
        if ($id > 0) {
            $stmt = db()->prepare("DELETE FROM entries WHERE id=?");
            $stmt->execute([$id]);
        }
        $month = $_POST['month'] ?? date('Y-m');
        header("Location: ?month=" . urlencode($month));
        exit;
    }
} elseif ($_SERVER['REQUEST_METHOD'] === 'GET' && $action === 'export_csv') {
    $month = $_GET['month'] ?? date('Y-m');
    if (!preg_match('/^\d{4}-\d{2}$/', $month)) $month = date('Y-m');

    $stmt = db()->prepare("SELECT id,d,type,category,memo,amount FROM entries WHERE substr(d,1,7)=? ORDER BY d ASC, id ASC");
    $stmt->execute([$month]);
    $rows = $stmt->fetchAll();

    header('Content-Type: text/csv; charset=UTF-8');
    header('Content-Disposition: attachment; filename="ledger_'.$month.'.csv"');
    $out = fopen('php://output', 'w');
    // UTF-8 BOM for Excel
    fwrite($out, "\xEF\xBB\xBF");
    fputcsv($out, ['ID','날짜','구분','카테고리','메모','금액(원)']);
    foreach ($rows as $r) {
        fputcsv($out, [$r['id'],$r['d'],$r['type']==='income'?'수입':'지출',$r['category'],$r['memo'],$r['amount']]);
    }
    fclose($out);
    exit;
}

// ---- 조회 데이터 ----
$month = $_GET['month'] ?? date('Y-m');
if (!preg_match('/^\d{4}-\d{2}$/', $month)) $month = date('Y-m');

$stmt = db()->prepare("SELECT * FROM entries WHERE substr(d,1,7)=? ORDER BY d DESC, id DESC");
$stmt->execute([$month]);
$list = $stmt->fetchAll();

$sumIncome = 0; $sumExpense = 0;
$byCat = [];
foreach ($list as $r) {
    if ($r['type'] === 'income') $sumIncome += (int)$r['amount'];
    else $sumExpense += (int)$r['amount'];
    $byCat[$r['category']][$r['type']] = ($byCat[$r['category']][$r['type']] ?? 0) + (int)$r['amount'];
}
$balance = $sumIncome - $sumExpense;

// 이전/다음 달 계산
$monthDate = DateTime::createFromFormat('Y-m', $month) ?: new DateTime('first day of this month');
$prevMonth = $monthDate->modify('-1 month')->format('Y-m');
$nextMonth = $monthDate->modify('+2 month')->format('Y-m'); // 보정: -1 했다가 +2 = +1
$monthDate->modify('-1 month'); // 원복

?>
<!doctype html>
<html lang="ko">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>가계부 (<?=h($month)?>)</title>
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://cdn.jsdelivr.net/npm/sanitize.css@13.0.0/sanitize.min.css" rel="stylesheet">
<style>
:root {
  --bg:#0f172a; --panel:#111827; --muted:#94a3b8; --text:#e5e7eb; --accent:#4f46e5; --accent2:#22c55e; --danger:#ef4444;
  --ring: rgba(79,70,229,0.45);
}
body { background:linear-gradient(180deg,#0b1020, #0f172a 30%); color:var(--text); font-family: ui-sans-serif, system-ui, -apple-system, Segoe UI, Roboto; padding:20px; }
.container { max-width: 980px; margin: 0 auto; }
.card { background: rgba(17,24,39,0.85); border:1px solid rgba(148,163,184,0.15); border-radius:14px; padding:18px; box-shadow:0 10px 30px rgba(0,0,0,0.35); backdrop-filter: blur(6px); }
.grid { display:grid; gap:18px; }
.grid-2 { grid-template-columns: 1fr 1fr; }
h1 { font-size: 22px; margin: 0 0 12px; display:flex; align-items:center; gap:10px;}
.muted { color: var(--muted); }
input, select, button, textarea {
  background:#0b1220; color:var(--text); border:1px solid rgba(148,163,184,0.24); border-radius:10px; padding:10px 12px; outline:none;
}
input:focus, select:focus, textarea:focus { border-color: var(--accent); box-shadow: 0 0 0 4px var(--ring); }
.btn { cursor:pointer; padding:10px 14px; border-radius:10px; border:1px solid transparent; }
.btn-primary { background: linear-gradient(135deg, var(--accent), #7c3aed); }
.btn-green { background: linear-gradient(135deg, var(--accent2), #16a34a); }
.btn-outline { background:transparent; border-color: rgba(148,163,184,0.35); }
.btn-danger { background: linear-gradient(135deg, var(--danger), #b91c1c); }
.row { display:flex; gap:10px; align-items:center; flex-wrap: wrap;}
table { width:100%; border-collapse: collapse; }
th, td { padding:10px; border-bottom:1px solid rgba(148,163,184,0.15); }
th { text-align:left; color:var(--muted); font-weight:600; }
.badge { font-size:12px; padding:4px 8px; border-radius:999px; background:rgba(79,70,229,0.2); border:1px solid rgba(79,70,229,0.4); }
.badge.income { background:rgba(34,197,94,0.18); border-color:rgba(34,197,94,0.4); }
.badge.expense { background:rgba(239,68,68,0.18); border-color:rgba(239,68,68,0.4); }
.kpi { display:grid; grid-template-columns: repeat(3,1fr); gap:12px; margin-top:8px;}
.kpi .box { background:#0b1220; border:1px solid rgba(148,163,184,0.18); border-radius:12px; padding:14px; }
.small { font-size:12px; color:var(--muted); }
footer { margin-top:24px; color:var(--muted); font-size:12px; text-align:center;}
a { color:#a5b4fc; text-decoration: none; }
a:hover { text-decoration: underline; }
@media (max-width:720px){ .grid-2 { grid-template-columns:1fr; } .kpi{ grid-template-columns:1fr; } }
</style>
</head>
<body>
<div class="container grid">
  <div class="card">
    <h1>가계부 <span class="badge"><?=h($month)?></span></h1>

    <div class="row" style="justify-content: space-between;">
      <div class="row">
        <a class="btn btn-outline" href="?month=<?=$prevMonth?>">← 이전달</a>
        <a class="btn btn-outline" href="?month=<?=h(date('Y-m'))?>">이번달</a>
        <a class="btn btn-outline" href="?month=<?=$nextMonth?>">다음달 →</a>
      </div>
      <form method="get" class="row" style="gap:6px">
        <input type="month" name="month" value="<?=h($month)?>">
        <button class="btn btn-primary" type="submit">이동</button>
      </form>
    </div>

    <div class="kpi">
      <div class="box">
        <div class="small">총 수입</div>
        <div style="font-size:20px; font-weight:700;">₩ <?=money_fmt($sumIncome)?></div>
      </div>
      <div class="box">
        <div class="small">총 지출</div>
        <div style="font-size:20px; font-weight:700;">₩ <?=money_fmt($sumExpense)?></div>
      </div>
      <div class="box">
        <div class="small">잔액</div>
        <div style="font-size:20px; font-weight:700;"><?= $balance>=0 ? '₩ '.money_fmt($balance) : '<span style="color:#f87171">-₩ '.money_fmt(abs($balance)).'</span>'?></div>
      </div>
    </div>
  </div>

  <div class="grid grid-2">
    <div class="card">
      <h1>내역 입력</h1>
      <form method="post" class="grid" style="gap:12px;">
        <input type="hidden" name="csrf" value="<?=csrf_token()?>">
        <input type="hidden" name="action" value="add">
        <div class="row">
          <label class="small" style="width:70px;">날짜</label>
          <input type="date" name="d" value="<?=h(date('Y-m-d'))?>" required>
        </div>
        <div class="row">
          <label class="small" style="width:70px;">구분</label>
          <label><input type="radio" name="type" value="income" checked> 수입</label>
          <label><input type="radio" name="type" value="expense"> 지출</label>
        </div>
        <div class="row" style="flex-wrap: nowrap;">
          <label class="small" style="width:70px;">카테고리</label>
          <select name="category" style="flex:1;">
            <?php foreach (CATEGORIES as $c): ?>
              <option value="<?=h($c)?>"><?=h($c)?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="row">
          <label class="small" style="width:70px;">금액</label>
          <input type="number" name="amount" min="0" step="1" placeholder="예: 12000" required>
        </div>
        <div>
          <textarea name="memo" rows="2" placeholder="메모(선택)"></textarea>
        </div>
        <div class="row" style="justify-content:flex-end;">
          <button class="btn btn-green" type="submit">추가</button>
        </div>
      </form>
    </div>

    <div class="card">
      <h1>카테고리별 합계</h1>
      <table>
        <thead>
          <tr><th>카테고리</th><th style="text-align:right;">수입</th><th style="text-align:right;">지출</th><th style="text-align:right;">순액</th></tr>
        </thead>
        <tbody>
          <?php if (!$byCat): ?>
            <tr><td colspan="4" class="muted">데이터 없음</td></tr>
          <?php else: foreach ($byCat as $cat => $vals):
            $ci = $vals['income'] ?? 0; $ce = $vals['expense'] ?? 0; $net = $ci - $ce; ?>
            <tr>
              <td><?=h($cat)?></td>
              <td style="text-align:right;">₩ <?=money_fmt($ci)?></td>
              <td style="text-align:right;">₩ <?=money_fmt($ce)?></td>
              <td style="text-align:right; <?=$net<0?'color:#f87171;':''?>"><?= ($net>=0?'₩ ':'-₩ ') . money_fmt(abs($net)) ?></td>
            </tr>
          <?php endforeach; endif; ?>
        </tbody>
      </table>
      <div class="row" style="justify-content:flex-end; margin-top:10px;">
        <a class="btn btn-outline" href="?action=export_csv&amp;month=<?=h($month)?>">CSV 내보내기</a>
      </div>
    </div>
  </div>

  <div class="card">
    <h1>내역 (<?=h($month)?>)</h1>
    <table>
      <thead>
        <tr>
          <th>ID</th>
          <th>날짜</th>
          <th>구분</th>
          <th>카테고리</th>
          <th>메모</th>
          <th style="text-align:right;">금액</th>
          <th style="text-align:right;">삭제</th>
        </tr>
      </thead>
      <tbody>
        <?php if (!$list): ?>
          <tr><td colspan="7" class="muted">등록된 내역이 없습니다.</td></tr>
        <?php else: foreach ($list as $r): ?>
          <tr>
            <td><?= (int)$r['id'] ?></td>
            <td><?= h($r['d']) ?></td>
            <td><span class="badge <?=h($r['type'])?>"><?= $r['type']==='income'?'수입':'지출' ?></span></td>
            <td><?= h($r['category']) ?></td>
            <td><?= nl2br(h($r['memo'])) ?></td>
            <td style="text-align:right;"><?= $r['type']==='income' ? '₩ '.money_fmt($r['amount']) : '-₩ '.money_fmt($r['amount']) ?></td>
            <td style="text-align:right;">
              <form method="post" onsubmit="return confirm('삭제하시겠습니까?');" style="display:inline;">
                <input type="hidden" name="csrf" value="<?=csrf_token()?>">
                <input type="hidden" name="action" value="delete">
                <input type="hidden" name="id" value="<?=$r['id']?>">
                <input type="hidden" name="month" value="<?=h($month)?>">
                <button class="btn btn-danger" type="submit">삭제</button>
              </form>
            </td>
          </tr>
        <?php endforeach; endif; ?>
      </tbody>
    </table>
  </div>

  <footer>
    단일 파일 · SQLite · CSRF/XSS 최소 보안 · 월별 집계 · CSV 내보내기 지원
  </footer>
</div>
</body>
</html>
