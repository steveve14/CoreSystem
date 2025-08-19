<?php
// find.php (단순 버전)
session_start();
$flash = $_SESSION['flash'] ?? null;
unset($_SESSION['flash']);
?>
<!DOCTYPE html>
<html lang="ko">

<head>
    <meta charset="UTF-8">
    <title>아이디/비밀번호 찾기</title>
    <style>
        body {
            font-family: sans-serif;
            background: #0e0f13;
            color: #e8e8ea;
            display: grid;
            place-items: center;
            min-height: 100vh;
            margin: 0
        }

        .wrap {
            width: 100%;
            max-width: 520px;
            padding: 20px;
            border-radius: 14px;
            background: #171923;
            border: 1px solid rgba(255, 255, 255, .08)
        }

        h2 {
            margin: 8px 0 14px
        }

        form {
            display: grid;
            gap: 10px;
            margin: 8px 0 16px
        }

        label {
            font-size: 13px;
            color: #a6a8ad
        }

        input {
            padding: 10px 12px;
            border-radius: 10px;
            border: 1px solid rgba(255, 255, 255, .14);
            background: #10121a;
            color: #e8e8ea
        }

        button {
            padding: 10px 12px;
            border: 0;
            border-radius: 10px;
            background: #d71f26;
            color: #fff;
            font-weight: 700;
            cursor: pointer
        }

        .flash {
            margin: 8px 0;
            padding: 10px;
            border-radius: 10px
        }

        .ok {
            background: rgba(34, 197, 94, .12);
            border: 1px solid rgba(34, 197, 94, .35)
        }

        .err {
            background: rgba(244, 63, 94, .12);
            border: 1px solid rgba(244, 63, 94, .35)
        }

        .box {
            padding: 12px;
            border-radius: 10px;
            background: #10121a;
            border: 1px solid rgba(255, 255, 255, .08)
        }
    </style>
</head>

<body>
    <main class="wrap">
        <h2>아이디/비밀번호 찾기</h2>

        <?php if ($flash): ?>
            <div class="flash <?php echo $flash['type'] === 'ok' ? 'ok' : 'err'; ?>">
                <?php echo htmlspecialchars($flash['msg'], ENT_QUOTES, 'UTF-8'); ?>
            </div>
        <?php endif; ?>

        <div class="box">
            <h3 style="margin:0 0 6px">학번으로 아이디 찾기</h3>
            <form action="find_id_process.php" method="post">
                <label for="student_id">학번</label>
                <input id="student_id" name="student_id" type="text" required placeholder="예: 20250001">
                <button type="submit">아이디 찾기</button>
            </form>
        </div>

        <div class="box" style="margin-top:12px">
            <h3 style="margin:0 0 6px">아이디로 비밀번호 찾기(임시 비밀번호 발급)</h3>
            <form action="find_pw_process.php" method="post">
                <label for="username">아이디</label>
                <input id="username" name="username" type="text" required placeholder="가입한 아이디">
                <button type="submit">임시 비밀번호 받기</button>
            </form>
            <p style="font-size:12px;color:#a6a8ad;margin:8px 0 0">임시 비밀번호로 로그인 후 마이페이지에서 꼭 변경하세요.</p>
        </div>

        <p style="text-align:center;margin-top:12px">
            <a href="login.php" style="color:#fda4af">로그인으로 돌아가기</a>
        </p>
    </main>
</body>

</html>