<?php
// find_pw_process.php
declare(strict_types=1);
session_start();

// 1. (추가) 중앙 DB 관리 파일 불러오기
// 이 파일의 위치에 따라 경로(../)를 확인해야 합니다.
require_once __DIR__ . '/../DB/database.php';

// 메시지와 함께 이전 페이지로 돌아가는 함수
function back_with(string $type, string $msg): void
{
    $_SESSION['flash'] = ['type' => $type, 'msg' => $msg];
    // 아이디/비밀번호 찾기 폼이 있는 파일 이름으로 설정
    header('Location: find.php');
    exit;
}

// 임시 비밀번호 생성 함수 (영문 대/소문자, 숫자 조합)
function generate_temporary_password(int $length = 10): string
{
    // 혼동하기 쉬운 문자(0, o, O, 1, l, I) 제외
    $chars = 'ABCDEFGHJKLMNPQRSTUVWXYZabcdefghijkmnpqrstuvwxyz23456789';
    $password = '';
    $char_length = strlen($chars) - 1;
    for ($i = 0; $i < $length; $i++) {
        $password .= $chars[random_int(0, $char_length)];
    }
    return $password;
}

// 2. 요청 방식 및 CSRF 토큰 검증 (추가 권장)
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: find.php');
    exit;
}
// find.php 폼에 CSRF 토큰이 포함되어 있다고 가정합니다.
if (empty($_POST['csrf_token']) || empty($_SESSION['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
    back_with('err', '보안 토큰이 유효하지 않습니다.');
}

// 3. 입력값 받기 및 유효성 검사
$username = trim((string)($_POST['username'] ?? ''));
if ($username === '') {
    back_with('err', '아이디를 입력하세요.');
}

// 4. 데이터베이스 처리
try {
    // (수정) 중앙 DB 연결 함수 사용
    $pdo = get_db_connection();

    // --- (삭제) 기존의 DB 연결 및 테이블 생성 코드 전체를 삭제합니다. ---

    // 아이디로 사용자 확인
    $stmt = $pdo->prepare("SELECT user_id FROM Users WHERE username = ?");
    $stmt->execute([$username]);
    $user = $stmt->fetch();

    if (!$user) {
        back_with('err', '해당 아이디의 계정을 찾을 수 없습니다.');
    }

    // 임시 비밀번호 생성 및 해시
    $temporary_password = generate_temporary_password(10);
    $password_hash = password_hash($temporary_password, PASSWORD_ARGON2ID); // 최신 해시 알고리즘 사용

    // 데이터베이스에 임시 비밀번호 해시 업데이트
    $stmt = $pdo->prepare("UPDATE Users SET password_hash = ? WHERE user_id = ?");
    $stmt->execute([$password_hash, (int)$user['user_id']]);

    // 화면에 임시 비밀번호 안내 (실제 서비스에서는 이메일/SMS로 발송해야 합니다)
    back_with('ok', "임시 비밀번호: [ {$temporary_password} ]\n로그인 후 즉시 변경해주세요.");
} catch (PDOException $e) {
    // 실제 운영 환경에서는 에러 로그를 기록하는 것이 중요합니다.
    error_log("Find PW Error: " . $e->getMessage());
    back_with('err', '데이터베이스 처리 중 오류가 발생했습니다.');
}
