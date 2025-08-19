<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);
// 1. 모든 로직을 처리하는 파일을 불러옵니다.
// 이 파일을 실행하면 데이터베이스 연결, 데이터 조회, 계산 등이 모두 완료됩니다.
require_once __DIR__ . '/logic.php';

// 2. 이제 준비된 변수들을 사용하여 HTML을 출력하기만 하면 됩니다.
?>
<!doctype html>
<html lang="ko">

<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>가계부 (<?= h($month) ?>)</title>
  <link rel="stylesheet" href="style.css">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://cdn.jsdelivr.net/npm/sanitize.css@13.0.0/sanitize.min.css" rel="stylesheet">
</head>

<body>
  <div class="container grid">
    <div class="card">
      <div class="row" style="justify-content: space-between; align-items: flex-start;">
        <!-- 왼쪽 영역: 타이틀 -->
        <h1>가계부 <span class="badge"><?= h($month) ?></span></h1>

        <!-- 오른쪽 영역: 사용자 정보 및 로그아웃 -->
        <div style="text-align: right;">
          <div style="margin-bottom: 8px; font-size: 14px;">
            <span class="muted">사용자:</span> <strong><?= h($_SESSION['username'] ?? 'Guest') ?></strong>
          </div>
          <a href="../logout/logout.php" class="btn btn-outline" style="padding: 6px 10px; font-size: 12px;">로그아웃</a>
        </div>
      </div>

      <div class="row" style="justify-content: space-between; margin-top: 12px;">
        <div class="row">
          <a class="btn btn-outline" href="?month=<?= $prevMonth ?>">← 이전달</a>
          <a class="btn btn-outline" href="?month=<?= h(date('Y-m')) ?>">이번달</a>
          <a class="btn btn-outline" href="?month=<?= $nextMonth ?>">다음달 →</a>
        </div>
        <form method="get" class="row" style="gap:6px">
          <input type="month" name="month" value="<?= h($month) ?>">
          <button class="btn btn-primary" type="submit">이동</button>
        </form>
      </div>

      <div class="kpi">
        <div class="box">
          <div class="small">총 수입</div>
          <div style="font-size:20px; font-weight:700;">₩ <?= money_fmt($sumIncome) ?></div>
        </div>
        <div class="box">
          <div class="small">총 지출</div>
          <div style="font-size:20px; font-weight:700;">₩ <?= money_fmt($sumExpense) ?></div>
        </div>
        <div class="box">
          <div class="small">잔액</div>
          <div style="font-size:20px; font-weight:700;"><?= $balance >= 0 ? '₩ ' . money_fmt($balance) : '<span style="color:#f87171">-₩ ' . money_fmt(abs($balance)) . '</span>' ?></div>
        </div>
      </div>
    </div>

    <div class="grid grid-2">
      <div class="card">
        <h1>내역 입력</h1>
        <form method="post" class="grid" style="gap:12px;">
          <input type="hidden" name="csrf" value="<?= csrf_token() ?>">
          <input type="hidden" name="action" value="add">
          <div class="row">
            <label class="small" style="width:70px;">날짜</label>
            <input type="date" name="d" value="<?= h($form_default_date) ?>" required>
          </div>
          <div class="row">
            <label class="small" style="width:70px;">구분</label>
            <label><input type="radio" name="type" value="expense" checked> 지출</label>
            <label><input type="radio" name="type" value="income"> 수입</label>
          </div>
          <div class="row" style="flex-wrap: nowrap;">
            <label class="small" style="width:70px;">카테고리</label>
            <select name="category_id" style="flex:1;">
              <?php foreach ($categories as $id => $name): ?>
                <option value="<?= h($id) ?>"><?= h($name) ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="row">
            <label class="small" style="width:70px;">금액</label>
            <input type="number" name="amount" min="0" step="0.01" placeholder="예: 12000" required>
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
            <tr>
              <th>카테고리</th>
              <th style="text-align:right;">수입</th>
              <th style="text-align:right;">지출</th>
              <th style="text-align:right;">순액</th>
            </tr>
          </thead>
          <tbody>
            <?php if (!$byCat): ?>
              <tr>
                <td colspan="4" class="muted">데이터 없음</td>
              </tr>
              <?php else: foreach ($byCat as $cat => $vals):
                $ci = $vals['수입'] ?? 0;
                $ce = $vals['지출'] ?? 0;
                $net = $ci - $ce; ?>
                <tr>
                  <td><?= h($cat) ?></td>
                  <td style="text-align:right;">₩ <?= money_fmt($ci) ?></td>
                  <td style="text-align:right;">₩ <?= money_fmt($ce) ?></td>
                  <td style="text-align:right; <?= $net < 0 ? 'color:#f87171;' : '' ?>"><?= ($net >= 0 ? '₩ ' : '-₩ ') . money_fmt(abs($net)) ?></td>
                </tr>
            <?php endforeach;
            endif; ?>
          </tbody>
        </table>
        <div class="row" style="justify-content:flex-end; margin-top:10px;">
          <a class="btn btn-outline" href="?action=export_csv&amp;month=<?= h($month) ?>">CSV 내보내기</a>
        </div>
      </div>
    </div>

    <div class="card">
      <h1>내역 (<?= h($month) ?>)</h1>
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
            <tr>
              <td colspan="7" class="muted">등록된 내역이 없습니다.</td>
            </tr>
            <?php else: foreach ($list as $r): ?>
              <tr>
                <td><?= (int)$r['AccountBook_id'] ?></td>
                <td><?= h($r['Date']) ?></td>
                <td>
                  <?php $badge_class = $r['transaction_type'] === '수입' ? 'income' : 'expense'; ?>
                  <span class="badge <?= $badge_class ?>"><?= h($r['transaction_type']) ?></span>
                </td>
                <td><?= h($r['category_name'] ?? '미분류') ?></td>
                <td><?= nl2br(h($r['memo'])) ?></td>
                <td style="text-align:right;">
                  <?= $r['transaction_type'] === '수입' ? '₩ ' . money_fmt($r['amount']) : '-₩ ' . money_fmt($r['amount']) ?>
                </td>
                <td style="text-align:right;">
                  <form method="post" onsubmit="return confirm('삭제하시겠습니까?');" style="display:inline;">
                    <input type="hidden" name="csrf" value="<?= csrf_token() ?>">
                    <input type="hidden" name="action" value="delete">
                    <input type="hidden" name="id" value="<?= $r['AccountBook_id'] ?>">
                    <input type="hidden" name="month" value="<?= h($month) ?>">
                    <button class="btn btn-danger" type="submit">삭제</button>
                  </form>
                </td>
              </tr>
          <?php endforeach;
          endif; ?>
        </tbody>
      </table>
    </div>

    <footer>
      단일 파일 · SQLite · CSRF/XSS 최소 보안 · 월별 집계 · CSV 내보내기 지원
    </footer>
  </div>
</body>

</html>