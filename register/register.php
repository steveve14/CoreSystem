<?php
// register.php
declare(strict_types=1);
session_start();

// CSRF 토큰 생성 (페이지에 처음 접근할 때만 생성)
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
$csrf_token = $_SESSION['csrf_token'];

// 플래시 메시지가 있다면 변수에 담고 세션에서 삭제
$flash = $_SESSION['flash'] ?? null;
unset($_SESSION['flash']);
?>
<!DOCTYPE html>
<html lang="ko">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>회원가입 | CoreSystem</title>
    <!-- 분리된 CSS 파일 연결 -->
    <link rel="stylesheet" href="register_style.css">
</head>

<body>
    <main class="card" role="main" aria-labelledby="title">
        <div class="brand">
            <img src="../assets/images/logo.jpg" alt="CoreSystem 로고" />
            <h1 id="title">CoreSystem 회원가입</h1>
        </div>

        <?php if ($flash): ?>
            <div class="flash <?= htmlspecialchars($flash['type'], ENT_QUOTES, 'UTF-8') === 'ok' ? 'ok' : 'err' ?>">
                <?= htmlspecialchars($flash['msg'], ENT_QUOTES, 'UTF-8') ?>
            </div>
        <?php endif; ?>

        <form id="regForm" action="register_process.php" method="post" autocomplete="on" novalidate>
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token, ENT_QUOTES, 'UTF-8') ?>">

            <div class="field">
                <label for="username">아이디 (Username)</label>
                <input id="username" name="username" type="text" minlength="3" maxlength="32" required placeholder="예: coresys01">
            </div>

            <div class="field">
                <label for="password">비밀번호</label>
                <input id="password" name="password" type="password" minlength="8" required placeholder="8자 이상, 영문/숫자/특수문자 조합">
                <div class="hint" id="pwHint">대/소문자, 숫자, 특수문자를 포함하면 안전합니다.</div>
            </div>

            <div class="field">
                <label for="password_confirm">비밀번호 확인</label>
                <input id="password_confirm" name="password_confirm" type="password" minlength="8" required placeholder="비밀번호를 다시 한번 입력해주세요">
            </div>

            <div class="field">
                <label for="email">이메일</label>
                <input id="email" name="email" type="email" required placeholder="name@example.com">
            </div>

            <div class="field">
                <label for="student_id">학번 (선택)</label>
                <input id="student_id" name="student_id" type="text" placeholder="예: 20250001">
            </div>

            <div class="field">
                <label for="phone_number">전화번호 (선택)</label>
                <input id="phone_number" name="phone_number" type="tel" placeholder="예: 010-1234-5678">
            </div>

            <button class="btn" type="submit">가입하기</button>
        </form>

        <div class="links">
            <a class="link" href="../login/login.php">이미 계정이 있으신가요? 로그인</a>
        </div>
    </main>

    <!-- 분리된 JavaScript 파일 연결 -->
    <script src="register_script.js"></script>
</body>

</html>