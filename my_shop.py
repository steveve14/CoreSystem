import sqlite3

def create_database(db_name="my_shop.db"):
    """
    SQLite 데이터베이스와 테이블을 생성합니다.
    """
    # 데이터베이스에 연결 (파일이 없으면 새로 생성됩니다)
    conn = sqlite3.connect(db_name)
    cursor = conn.cursor()

    print(f"'{db_name}' 데이터베이스에 연결되었습니다.")

    # 1. 사용자 (Users) 테이블 생성
    cursor.execute("""
        CREATE TABLE IF NOT EXISTS Users (
            user_id INTEGER PRIMARY KEY AUTOINCREMENT,
            username TEXT NOT NULL UNIQUE,
            password_hash TEXT NOT NULL,
            email TEXT NOT NULL UNIQUE,
            student_id TEXT UNIQUE,
            phone_number TEXT
        );
    """)
    print("'Users' 테이블을 성공적으로 생성했습니다.")

    # 2. 항목명 (Categories) 테이블 생성
    cursor.execute("""
        CREATE TABLE IF NOT EXISTS Categories (
            category_id INTEGER PRIMARY KEY AUTOINCREMENT,
            category_name TEXT NOT NULL UNIQUE
        );
    """)
    print("'Categories' 테이블을 성공적으로 생성했습니다.")

    # 3. 가계부 (AccountBook) 테이블 생성
    # 외래 키(Foreign Key) 제약 조건을 활성화합니다.
    cursor.execute("PRAGMA foreign_keys = ON;")
    
    cursor.execute("""
        CREATE TABLE IF NOT EXISTS AccountBook (
            AccountBook_id INTEGER PRIMARY KEY AUTOINCREMENT,
            Date TEXT NOT NULL,
            category_id INTEGER NOT NULL,
            transaction_type TEXT NOT NULL CHECK(transaction_type IN ('지출', '수입')),
            amount REAL NOT NULL,
            memo TEXT,
            user_id INTEGER NOT NULL,
            FOREIGN KEY (user_id) REFERENCES Users(user_id) ON DELETE CASCADE,
            FOREIGN KEY (category_id) REFERENCES Categories(category_id) ON DELETE SET NULL
        );
    """)
    print("'AccountBook' 테이블을 성공적으로 생성했습니다.")

    # 변경사항을 저장(commit)하고 연결을 닫습니다.
    conn.commit()
    conn.close()
    print(f"'{db_name}' 데이터베이스가 성공적으로 생성되고 연결이 닫혔습니다.")

if __name__ == "__main__":
    create_database()