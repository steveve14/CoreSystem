<?php
// find.php
declare(strict_types=1);
session_start();

// CSRF 토큰 생성
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
$csrf_token = $_SESSION['csrf_token'];

$flash = $_SESSION['flash'] ?? null;
unset($_SESSION['flash']);
?>
<!DOCTYPE html>
<html lang="ko">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>아이디/비밀번호 찾기 | CoreSystem</title>
    <link rel="stylesheet" href="find_style.css">
</head>

<body>
    <main class="wrap">
        <h2>아이디/비밀번호 찾기</h2>

        <?php if ($flash): ?>
            <div class="flash <?= htmlspecialchars($flash['type'], ENT_QUOTES, 'UTF-8') === 'ok' ? 'ok' : 'err' ?>">
                <?= nl2br(htmlspecialchars($flash['msg'], ENT_QUOTES, 'UTF-8')) ?>
            </div>
        <?php endif; ?>

        <div class="box">
            <h3>학번으로 아이디 찾기</h3>
            <form action="find_id_process.php" method="post">
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token, ENT_QUOTES, 'UTF-8') ?>">
                <label for="student_id">학번</label>
                <input id="student_id" name="student_id" type="text" required placeholder="예: 20250001">
                <button type="submit">아이디 찾기</button>
            </form>
        </div>

        <div class="box" style="margin-top:12px">
            <h3>아이디로 비밀번호 찾기 (임시 비밀번호 발급)</h3>
            <!-- (수정) 이메일 입력 필드 제거 -->
            <form action="find_pw_process.php" method="post">
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token, ENT_QUOTES, 'UTF-8') ?>">
                <label for="username">아이디</label>
                <input id="username" name="username" type="text" required placeholder="가입한 아이디">
                <button type="submit">임시 비밀번호 받기</button>
            </form>
            <p style="font-size:12px;color:#a6a8ad;margin:8px 0 0">
                임시 비밀번호로 로그인 후 마이페이지에서 꼭 변경하세요.
            </p>
        </div>

        <p style="text-align:center;margin-top:12px">
            <a href="../login/login.php">로그인으로 돌아가기</a>
        </p>
    </main>
</body>

</html>