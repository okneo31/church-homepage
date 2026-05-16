<?php
session_start();
include 'db.php';

// 1. 로그아웃 처리
if (isset($_GET['logout'])) {
    session_destroy();
    header("Location: admin.php");
    exit;
}

// 2. 로그인 처리
$msg = '';
if (isset($_POST['login'])) {
    $username = $_POST['username'];
    $password = $_POST['password'];

    $stmt = $pdo->prepare("SELECT * FROM admins WHERE username = ?");
    $stmt->execute([$username]);
    $admin = $stmt->fetch();
    if ($admin && password_verify($password, $admin['password'])) {
        $_SESSION['admin'] = true;
    } else {
        $msg = '아이디 또는 비밀번호가 틀렸습니다.';
    }
}

// 로그인 안되어있으면 로그인 폼 보여줌
if (!isset($_SESSION['admin'])) {
?>
<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <title>Admin Login</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 h-screen flex justify-center items-center">
    <div class="bg-white p-8 border-4 border-black shadow-[8px_8px_0_0_black] w-96">
        <h2 class="text-2xl font-black mb-6">ADMIN LOGIN</h2>
        <?php if($msg) echo "<p class='text-red-600 font-bold mb-4'>$msg</p>"; ?>
        <form method="POST">
            <input type="text" name="username" placeholder="ID (admin)" class="w-full border-4 border-black p-2 mb-4 font-bold">
            <input type="password" name="password" placeholder="PW (1234)" class="w-full border-4 border-black p-2 mb-4 font-bold">
            <button type="submit" name="login" class="w-full bg-yellow-400 border-4 border-black p-2 font-black hover:bg-yellow-300">LOGIN</button>
        </form>
        <a href="index.php" class="block text-center mt-4 underline font-bold">홈으로 돌아가기</a>
    </div>
</body>
</html>
<?php
    exit;
}

// 3. 설정 저장 처리
if (isset($_POST['save_settings'])) {
    foreach ($_POST as $key => $value) {
        if ($key == 'save_settings') continue;
        $stmt = $pdo->prepare("INSERT INTO settings (site_key, site_value) VALUES (?, ?) ON DUPLICATE KEY UPDATE site_value = ?");
        $stmt->execute([$key, $value, $value]);
    }
    echo "<script>alert('설정이 저장되었습니다.');</script>";
}

// 4. 비밀번호 변경 처리
if (isset($_POST['change_pw'])) {
    $new_pw = password_hash($_POST['new_password'], PASSWORD_DEFAULT);
    $stmt = $pdo->prepare("UPDATE admins SET password = ? WHERE username = 'admin'");
    $stmt->execute([$new_pw]);
    echo "<script>alert('비밀번호가 변경되었습니다. 다시 로그인해주세요.'); location.href='admin.php?logout=1';</script>";
}

$config = getSettings($pdo);
?>

<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <title>Admin Panel</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>input, textarea { border: 2px solid black; padding: 5px; width: 100%; font-weight: bold; }</style>
</head>
<body class="bg-gray-100">

    <nav class="bg-black text-white p-4 flex justify-between items-center sticky top-0 z-50">
        <h1 class="text-xl font-black">⚙️ 관리자 설정 (Admin)</h1>
        <div>
            <a href="index.php" class="mr-4 hover:underline">🏠 홈페이지 확인</a>
            <a href="admin.php?logout=1" class="text-yellow-400 hover:underline">로그아웃</a>
        </div>
    </nav>

    <div class="max-w-4xl mx-auto p-8">
        
        <form method="POST" class="bg-white p-8 border-4 border-black shadow-lg mb-8">
            <h2 class="text-2xl font-black mb-6 border-b-4 border-black pb-2">1. 홈페이지 디자인 수정</h2>
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <label class="block font-bold mb-1">로고 텍스트</label>
                    <input type="text" name="logo" value="<?= $config['logo'] ?>">
                </div>
                <div>
                    <label class="block font-bold mb-1">배경 색상</label>
                    <div class="flex gap-2">
                        <input type="color" name="hero_bg_color" value="<?= $config['hero_bg_color'] ?>" class="h-10 w-20 p-0 border-2 border-black">
                    </div>
                </div>
                <div class="col-span-2">
                    <label class="block font-bold mb-1">메인 큰 제목</label>
                    <textarea name="hero_title" rows="2"><?= $config['hero_title'] ?></textarea>
                </div>
                <div class="col-span-2">
                    <label class="block font-bold mb-1">메인 설명</label>
                    <textarea name="hero_desc" rows="2"><?= $config['hero_desc'] ?></textarea>
                </div>
                <div class="col-span-2">
                    <label class="block font-bold mb-1">오른쪽 이미지 URL</label>
                    <input type="text" name="hero_img" value="<?= $config['hero_img'] ?>">
                </div>
            </div>

            <h3 class="text-xl font-black mt-8 mb-4">📺 설교 및 공지</h3>
            <div class="grid grid-cols-1 gap-4">
                <div>
                    <label class="block font-bold mb-1">유튜브 주소 (URL)</label>
                    <input type="text" name="main_youtube_url" value="<?= $config['main_youtube_url'] ?>">
                </div>
                <div>
                    <label class="block font-bold mb-1">설교 제목</label>
                    <input type="text" name="sermon_title" value="<?= $config['sermon_title'] ?>">
                </div>
                <div>
                    <label class="block font-bold mb-1">설교 본문/날짜</label>
                    <input type="text" name="sermon_info" value="<?= $config['sermon_info'] ?>">
                </div>
            </div>

            <button type="submit" name="save_settings" class="mt-8 bg-yellow-400 w-full py-4 border-4 border-black font-black text-xl hover:bg-yellow-300">
                모든 설정 저장하기 (SAVE)
            </button>
        </form>

        <form method="POST" class="bg-gray-800 text-white p-8 border-4 border-black">
            <h2 class="text-xl font-black mb-4 text-yellow-400">🔒 관리자 비밀번호 변경</h2>
            <div class="flex gap-4">
                <input type="password" name="new_password" placeholder="새로운 비밀번호 입력" class="text-black">
                <button type="submit" name="change_pw" class="bg-white text-black border-4 border-white font-bold px-6 hover:bg-gray-200">변경</button>
            </div>
        </form>

    </div>
</body>
</html>