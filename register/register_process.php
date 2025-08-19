<?php
// register_process.php
ini_set('display_errors', 1);
error_reporting(E_ALL);

// declare(strict_types=1);

session_start();

// 1. (추가) 중앙 DB 관리 파일 불러오기
require_once __DIR__ . '/../DB/database.php';

// 안전한 리다이렉트 + 플래시 메시지 함수
function back_with(string $type, string $msg): void
{
    $_SESSION['flash'] = ['type' => $type, 'msg' => $msg];
    header('Location: register.php');
    exit;
}

// 비밀번호 강도 검사 함수 (8자 이상, 대/소문자/숫자/특수문자 중 3가지 이상)
function is_password_strong(string $password): bool
{
    if (mb_strlen($password) < 8) {
        return false;
    }
    $score = 0;
    if (preg_match('/[A-Z]/', $password)) $score++; // 대문자
    if (preg_match('/[a-z]/', $password)) $score++; // 소문자
    if (preg_match('/[0-9]/', $password)) $score++; // 숫자
    if (preg_match('/[^A-Za-z0-9]/', $password)) $score++; // 특수문자

    return $score >= 3;
}

// 2. 요청 방식 및 CSRF 토큰 검증
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    // POST가 아니면 아무것도 안 하고 로그인 페이지로 보낼 수도 있습니다.
    header('Location: login.php');
    exit;
}
if (empty($_POST['csrf_token']) || empty($_SESSION['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
    back_with('err', '보안 토큰이 유효하지 않습니다.');
}

// 3. 입력값 수집 및 정리
$username      = trim((string)($_POST['username'] ?? ''));
$password      = (string)($_POST['password'] ?? '');
$password_conf = (string)($_POST['password_confirm'] ?? '');
$email         = trim((string)($_POST['email'] ?? ''));
$student_id    = trim((string)($_POST['student_id'] ?? '')) ?: null; // 빈 문자열이면 null로 처리
$phone_number  = trim((string)($_POST['phone_number'] ?? '')) ?: null; // 빈 문자열이면 null로 처리


// 4. 서버 측 유효성 검사
if (empty($username) || empty($password) || empty($password_conf) || empty($email)) {
    back_with('err', '필수 항목(아이디, 비밀번호, 이메일)을 모두 입력하세요.');
}
if ($password !== $password_conf) {
    back_with('err', '비밀번호가 일치하지 않습니다.');
}
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    back_with('err', '올바른 이메일 형식이 아닙니다.');
}
if (!is_password_strong($password)) {
    back_with('err', '비밀번호는 8자 이상이며, 대/소문자, 숫자, 특수문자 중 3종류 이상을 포함해야 합니다.');
}

// 5. 데이터베이스 처리
try {
    //중앙 DB 연결 함수 사용
    $pdo = get_db_connection();

    // 중복 체크 (아이디, 이메일, 학번)
    $stmt = $pdo->prepare("
        SELECT 1 FROM Users WHERE username = :username
        UNION ALL
        SELECT 1 FROM Users WHERE email = :email
        UNION ALL
        SELECT 1 FROM Users WHERE student_id = :student_id AND :student_id IS NOT NULL
    ");
    $stmt->execute([
        ':username' => $username,
        ':email' => $email,
        ':student_id' => $student_id,
    ]);

    if ($stmt->fetch()) {
        // 더 정확한 중복 메시지를 위해 개별 쿼리를 할 수도 있지만, 이 방식이 더 효율적입니다.
        back_with('err', '이미 사용 중인 아이디, 이메일 또는 학번입니다.');
    }

    // 비밀번호 해시
    $password_hash = password_hash($password, PASSWORD_ARGON2ID); // PASSWORD_DEFAULT 대신 최신 알고리즘 명시

    // 데이터베이스에 사용자 정보 저장
    $stmt = $pdo->prepare("
        INSERT INTO Users (username, password_hash, email, student_id, phone_number)
        VALUES (:username, :password_hash, :email, :student_id, :phone_number)
    ");
    $stmt->execute([
        ':username' => $username,
        ':password_hash' => $password_hash,
        ':email' => $email,
        ':student_id' => $student_id,
        ':phone_number' => $phone_number,
    ]);

    // CSRF 토큰 재발급 (1회 사용 원칙)
    unset($_SESSION['csrf_token']);

    // 성공 메시지를 로그인 페이지에서 보여주기 위해 세션에 저장합니다.
    $_SESSION['flash'] = ['type' => 'ok', 'msg' => '회원가입이 완료되었습니다. 바로 로그인하세요!'];

    // 로그인 페이지로 리다이렉트합니다.
    header('Location: ../login/login.php');
    exit;

    back_with('ok', '회원가입이 완료되었습니다. 로그인 페이지로 이동합니다.');
} catch (PDOException $e) {
    // 실제 운영 환경에서는 에러 로그를 기록하는 것이 중요합니다.
    error_log("Register Error: " . $e->getMessage());
    back_with('err', '데이터베이스 처리 중 오류가 발생했습니다. 잠시 후 다시 시도해주세요.');
}
