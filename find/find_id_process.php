<?php
// find_id_process.php
declare(strict_types=1);
session_start();

// 1. 중앙 DB 관리 파일 불러오기
require_once __DIR__ . '/../DB/database.php';

// 메시지와 함께 이전 페이지로 돌아가는 함수
function back_with(string $type, string $msg): void
{
    $_SESSION['flash'] = ['type' => $type, 'msg' => $msg];
    // 아이디/비밀번호 찾기 폼이 있는 파일 이름으로 설정
    header('Location: find.php');
    exit;
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
$student_id = trim((string)($_POST['student_id'] ?? ''));
if ($student_id === '') {
    back_with('err', '학번을 입력하세요.');
}

// 4. 데이터베이스 처리
try {
    // (수정) 중앙 DB 연결 함수 사용
    $pdo = get_db_connection();

    // --- (삭제) 기존의 DB 연결 및 테이블 생성 코드 전체를 삭제합니다. ---

    // 학번으로 사용자 조회
    $stmt = $pdo->prepare("SELECT username FROM Users WHERE student_id = ?");
    $stmt->execute([$student_id]);
    $user = $stmt->fetch();

    if (!$user) {
        back_with('err', '해당 학번으로 등록된 계정을 찾을 수 없습니다.');
    }

    // 보안을 위해 아이디 일부를 마스킹 처리
    $username = (string)$user['username'];
    $masked_username = mb_substr($username, 0, 3, 'UTF-8') . str_repeat('*', max(0, mb_strlen($username, 'UTF-8') - 3));

    back_with('ok', "조회된 아이디: {$masked_username}");
} catch (PDOException $e) {
    // 실제 운영 환경에서는 에러 로그를 기록하는 것이 중요합니다.
    error_log("Find ID Error: " . $e->getMessage());
    back_with('err', '데이터베이스 처리 중 오류가 발생했습니다. 잠시 후 다시 시도해주세요.');
}
