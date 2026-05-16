<?php
// DB 설정 — .env 파일 또는 환경변수에서 로드 (커밋되지 않음)
// 로컬 개발/서버 배포 시 같은 디렉토리에 .env 파일을 두거나
// 웹서버에서 환경변수(DB_HOST, DB_NAME, DB_USER, DB_PASS)를 설정해주세요.

(function () {
    $envFile = __DIR__ . '/.env';
    if (!is_readable($envFile)) return;
    foreach (file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
        $line = trim($line);
        if ($line === '' || str_starts_with($line, '#')) continue;
        if (!str_contains($line, '=')) continue;
        [$k, $v] = explode('=', $line, 2);
        $k = trim($k);
        $v = trim($v);
        if ((str_starts_with($v, '"') && str_ends_with($v, '"')) ||
            (str_starts_with($v, "'") && str_ends_with($v, "'"))) {
            $v = substr($v, 1, -1);
        }
        if (getenv($k) === false) {
            putenv("$k=$v");
            $_ENV[$k] = $v;
        }
    }
})();

$host = getenv('DB_HOST') ?: 'localhost';
$db   = getenv('DB_NAME') ?: '';
$user = getenv('DB_USER') ?: '';
$pass = getenv('DB_PASS') ?: '';
$charset = 'utf8mb4';

if ($db === '' || $user === '') {
    die("DB 설정이 없습니다. .env 파일을 생성하거나 환경변수를 설정해주세요. (.env.example 참고)");
}

try {
    $pdo = new PDO("mysql:host=$host;dbname=$db;charset=$charset", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (\PDOException $e) {
    die("DB 연결 실패: " . $e->getMessage());
}

// 설정값 불러오기 함수
function getSettings($pdo) {
    $stmt = $pdo->query("SELECT * FROM settings");
    $settings = [];
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $settings[$row['site_key']] = $row['site_value'];
    }
    return $settings;
}

// 유튜브 변환 함수
function getYoutubeEmbed($url) {
    preg_match('/(?:youtube\.com\/(?:[^\/]+\/.+\/|(?:v|e(?:mbed)?)\/|.*[?&]v=)|youtu\.be\/)([^"&?\/\s]{11})/', $url, $matches);
    return isset($matches[1]) ? "https://www.youtube.com/embed/" . $matches[1] : "";
}
