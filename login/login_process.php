<?php
// login_process.php
declare(strict_types=1);

session_start();

// 1. database.php 파일을 불러와 DB 연결 준비
require_once __DIR__ . '/../DB/database.php';

// 오류 발생 시 이전 페이지로 메시지와 함께 돌려보내는 함수
function back_err(string $msg): void
{
    $_SESSION['flash'] = ['type' => 'err', 'msg' => $msg];
    header('Location: login.php');
    exit;
}

// 2. 요청 방식 검증 (POST 요청만 허용)
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    back_err('잘못된 요청입니다.');
}

// 3. CSRF 토큰 검증
if (empty($_POST['csrf_token']) || empty($_SESSION['csrf_login']) || !hash_equals($_SESSION['csrf_login'], $_POST['csrf_token'])) {
    back_err('보안 토큰이 유효하지 않습니다.');
}

// 4. 입력값 받기 및 유효성 검사
$username = trim((string)($_POST['userid'] ?? ''));
$password = (string)($_POST['password'] ?? '');

if ($username === '' || $password === '') {
    back_err('아이디와 비밀번호를 입력하세요.');
}

// 5. (수정) get_db_connection() 함수로 DB 핸들 가져오기
$pdo = get_db_connection();

// 6. 사용자 조회 및 비밀번호 검증
try {
    $stmt = $pdo->prepare("SELECT user_id, username, password_hash FROM Users WHERE username = ?");
    $stmt->execute([$username]);
    $user = $stmt->fetch();

    if (!$user || !password_verify($password, $user['password_hash'])) {
        back_err('아이디 또는 비밀번호가 올바르지 않습니다.');
    }
} catch (PDOException $e) {
    // 실제 운영 환경에서는 로그를 남기는 것이 좋습니다.
    // error_log($e->getMessage());
    back_err('데이터베이스 오류가 발생했습니다. 관리자에게 문의하세요.');
}

// 7. 로그인 성공: 세션 설정
session_regenerate_id(true); // 세션 고정 공격 방지
$_SESSION['user_id']  = (int)$user['user_id'];
$_SESSION['username'] = $user['username'];

// 로그인 처리용 CSRF 토큰은 사용했으므로 삭제
unset($_SESSION['csrf_login']);

// 8. 성공 후 메인 페이지로 이동
header('Location: ../main/main.php');
exit;
