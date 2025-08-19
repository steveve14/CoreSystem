<?php
// database.php

declare(strict_types=1);

const DB_FILE = __DIR__ . '/AccountBook.db';
const CATEGORIES = ['식비','교통','주거','통신','의료','교육','문화','여가','쇼핑','경조사','저축','기타'];

/**
 * PDO 데이터베이스 연결 객체를 반환합니다.
 * 파일이 없으면 초기화 과정을 수행합니다.
 * @return PDO
 */
function get_db_connection(): PDO
{
    static $pdo;
    if ($pdo) {
        return $pdo;
    }

    $dbExists = file_exists(DB_FILE);

    try {
        $pdo = new PDO('sqlite:' . DB_FILE, null, null, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]);

        // 외래 키 제약 조건 활성화
        $pdo->exec('PRAGMA foreign_keys = ON;');
        $pdo->exec('PRAGMA journal_mode = WAL;');

        // 데이터베이스 파일이 처음 생성되었을 때만 테이블을 생성합니다.
        if (!$dbExists) {
            initialize_database($pdo);
        }

    } catch (PDOException $e) {
        // 에러 발생 시 스크립트를 중지하고 에러 메시지를 출력합니다.
        die("❌ 데이터베이스 연결 실패: " . $e->getMessage());
    }

    return $pdo;
}

/**
 * 데이터베이스 테이블을 초기화하고 기본 데이터를 삽입합니다.
 * @param PDO $pdo
 */
function initialize_database(PDO $pdo): void
{
    // Users 테이블 생성
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS Users (
            user_id INTEGER PRIMARY KEY AUTOINCREMENT,
            username TEXT NOT NULL UNIQUE,
            password_hash TEXT NOT NULL,
            email TEXT NOT NULL UNIQUE,
            student_id TEXT UNIQUE,
            phone_number TEXT
        );
    ");
    // 예시 사용자 추가 (실제 운영 시에는 회원가입 기능으로 구현해야 함)
    $pdo->exec("INSERT OR IGNORE INTO Users (user_id, username, password_hash, email) VALUES (1, 'default_user', 'hashed_password', 'user@example.com');");


    // Categories 테이블 생성 및 기본값 삽입
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS Categories (
            category_id INTEGER PRIMARY KEY AUTOINCREMENT,
            category_name TEXT NOT NULL UNIQUE
        );
    ");

    $stmt = $pdo->prepare("INSERT OR IGNORE INTO Categories (category_name) VALUES (?)");
    foreach (CATEGORIES as $category) {
        $stmt->execute([$category]);
    }

    // AccountBook 테이블 생성 (사용자 스키마와 일치하도록 수정)
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS AccountBook (
            AccountBook_id INTEGER PRIMARY KEY AUTOINCREMENT,
            Date TEXT NOT NULL,
            category_id INTEGER,
            transaction_type TEXT NOT NULL CHECK(transaction_type IN ('지출', '수입')),
            amount REAL NOT NULL,
            memo TEXT,
            user_id INTEGER NOT NULL,
            FOREIGN KEY (user_id) REFERENCES Users(user_id) ON DELETE CASCADE,
            FOREIGN KEY (category_id) REFERENCES Categories(category_id) ON DELETE SET NULL
        );
    ");
    $pdo->exec('CREATE INDEX IF NOT EXISTS idx_accountbook_date ON AccountBook(Date);');
    $pdo->exec('CREATE INDEX IF NOT EXISTS idx_accountbook_cat ON AccountBook(category_id);');
}

/**
 * 모든 카테고리 목록을 가져옵니다.
 * @return array
 */
function get_all_categories(): array
{
    $stmt = get_db_connection()->query("SELECT category_id, category_name FROM Categories ORDER BY category_id");
    return $stmt->fetchAll(PDO::FETCH_KEY_PAIR); // [id => name] 형태의 배열로 반환
}