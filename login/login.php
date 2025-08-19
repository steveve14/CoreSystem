<?php
// login.php
declare(strict_types=1);
session_start();

// 1. 데이터베이스 연결 파일을 불러옵니다.
// 이 코드를 실행하면 DB 연결 객체를 얻고, DB 파일이 없으면 자동으로 생성 및 초기화됩니다.
require_once __DIR__ . '/../DB/database.php';
$pdo = get_db_connection();

// 2. 이미 로그인된 사용자는 메인 페이지로 보냅니다.
if (isset($_SESSION['user_id'])) {
    header('Location: ../main/main.php'); // 메인 페이지 경로로 수정
    exit;
}

// 3. 플래시 메시지(로그인 실패, 안내 등) 처리
$flash = $_SESSION['flash'] ?? null;
unset($_SESSION['flash']);

// 4. 로그인 CSRF 토큰 발급
if (empty($_SESSION['csrf_login'])) {
    $_SESSION['csrf_login'] = bin2hex(random_bytes(32));
}
$csrf_login = $_SESSION['csrf_login'];

// 5. 로고 경로 설정
$LOGO_SRC = '../assets/images/logo.jpg'; // 프로젝트 구조에 맞게 경로 수정
?>
<!DOCTYPE html>
<html lang="ko">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>로그인 | CoreSystem</title>
    <!-- 분리된 CSS 파일 연결 -->
    <link rel="stylesheet" href="login_style.css">
</head>

<body>
    <main class="card" role="main" aria-labelledby="login-title">
        <div class="brand">
            <img src="<?= htmlspecialchars($LOGO_SRC, ENT_QUOTES, 'UTF-8') ?>" alt="CoreSystem 로고" />
            <h1 id="login-title">CoreSystem 로그인</h1>
        </div>

        <?php if ($flash): ?>
            <div class="flash <?= htmlspecialchars($flash['type'], ENT_QUOTES, 'UTF-8') === 'ok' ? 'ok' : 'err' ?>">
                <?= htmlspecialchars($flash['msg'], ENT_QUOTES, 'UTF-8') ?>
            </div>
        <?php endif; ?>

        <!-- 실제 로그인 처리를 담당할 'login_process.php' 파일로 폼 데이터 전송 -->
        <form action="login_process.php" method="post" autocomplete="on" novalidate>
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_login, ENT_QUOTES, 'UTF-8') ?>">

            <div class="field">
                <label for="userid">아이디</label>
                <input id="userid" name="userid" type="text" placeholder="아이디를 입력하세요" required />
            </div>
            <div class="field">
                <label for="password">비밀번호</label>
                <input id="password" name="password" type="password" placeholder="비밀번호를 입력하세요" required />
            </div>

            <div class="actions">
                <button class="btn" type="submit">로그인</button>
                <div style="display:flex; gap:12px;">
                    <!-- JS 분리를 위해 id 추가 -->
                    <button id="find-button" class="link" type="button">아이디/비밀번호 찾기</button>
                    <button id="register-button" class="link" type="button">회원가입</button>
                </div>
            </div>
        </form>

        <div class="divider">또는</div>

        <div class="helper">
            사내 계정 정책을 준수하여 비밀번호를 안전하게 관리하세요.
        </div>

        <p class="tiny">
            © <?= date('Y') ?> CoreSystem. All rights reserved.
        </p>
    </main>

    <!-- 분리된 JavaScript 파일 연결 -->
    <script src="login_script.js"></script>
</body>

</html>