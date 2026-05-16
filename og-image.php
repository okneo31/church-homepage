<?php
// og-image.php — PHP GD 기반 동적 OG 이미지 생성 (1200x630)
// 사용자가 admin에서 og_image를 따로 설정했다면 그 이미지가 우선 사용됩니다.

include 'db.php';
$config = getSettings($pdo);

if (!extension_loaded('gd')) {
    // GD가 없으면 SVG로 fallback
    header('Location: og-image.svg');
    exit;
}

header('Content-Type: image/png');
header('Cache-Control: public, max-age=3600');

function hex2rgb($hex) {
    $hex = ltrim($hex ?? '#facc15', '#');
    if (strlen($hex) === 3) $hex = $hex[0].$hex[0].$hex[1].$hex[1].$hex[2].$hex[2];
    if (strlen($hex) !== 6) return [250, 204, 21];
    return [hexdec(substr($hex, 0, 2)), hexdec(substr($hex, 2, 2)), hexdec(substr($hex, 4, 2))];
}

$W = 1200; $H = 630;
$im = imagecreatetruecolor($W, $H);
imagesavealpha($im, true);

$bg = hex2rgb($config['accent_color'] ?? '#facc15');
$cBg = imagecolorallocate($im, $bg[0], $bg[1], $bg[2]);
$cBlack = imagecolorallocate($im, 0, 0, 0);
$cYellow = imagecolorallocate($im, 250, 204, 21);
$cGrid = imagecolorallocatealpha($im, 0, 0, 0, 110);

imagefill($im, 0, 0, $cBg);

// 격자 패턴
for ($x = 0; $x < $W; $x += 60) imageline($im, $x, 0, $x, $H, $cGrid);
for ($y = 0; $y < $H; $y += 60) imageline($im, 0, $y, $W, $y, $cGrid);

// Swiss 보더
imagefilledrectangle($im, 0, 0, $W, 20, $cBlack);
imagefilledrectangle($im, 0, $H - 20, $W, $H, $cBlack);
imagefilledrectangle($im, 0, 0, 20, $H, $cBlack);
imagefilledrectangle($im, $W - 20, 0, $W, $H, $cBlack);

// 좌측 검정 블록
imagefilledrectangle($im, 60, 60, 380, 570, $cBlack);

// TTF 폰트 (선택)
$fontKo = __DIR__ . '/fonts/NanumGothic-Bold.ttf';
$fontEn = __DIR__ . '/fonts/Inter-Black.ttf';
$useTTF = is_readable($fontKo);
$useEnTTF = is_readable($fontEn);

// 좌측 라벨
if ($useEnTTF) {
    imagettftext($im, 18, 0, 80, 135, $cYellow, $fontEn, 'CHURCH');
} else {
    imagestring($im, 4, 80, 120, 'CHURCH', $cYellow);
}
imagefilledrectangle($im, 80, 145, 180, 149, $cYellow);

// 좌측 큰 영문 SOMANG / CHURCH
if ($useEnTTF) {
    imagettftext($im, 64, 0, 80, 280, $cYellow, $fontEn, 'SOMANG');
    imagettftext($im, 64, 0, 80, 360, $cYellow, $fontEn, 'CHURCH');
} else {
    imagestring($im, 5, 80, 240, 'SOMANG', $cYellow);
    imagestring($im, 5, 80, 280, 'CHURCH', $cYellow);
}

// 좌측 하단 사이트명
if ($useEnTTF) {
    imagettftext($im, 13, 0, 80, 500, $cYellow, $fontEn, 'EAST SEOUL');
    imagettftext($im, 13, 0, 80, 520, $cYellow, $fontEn, 'SOMANG CHURCH');
} else {
    imagestring($im, 2, 80, 490, 'EAST SEOUL', $cYellow);
    imagestring($im, 2, 80, 510, 'SOMANG CHURCH', $cYellow);
}
imagefilledrectangle($im, 80, 538, 120, 542, $cYellow);
if ($useEnTTF) {
    imagettftext($im, 10, 0, 80, 562, $cYellow, $fontEn, 'EST. SEOUL · KOREA');
}

// 우측 한글 영역 (TTF 필요)
if ($useTTF) {
    $title = $config['og_title'] ?: ($config['logo'] ?: '동서울소망교회');
    $desc = $config['og_desc'] ?: '소망의 노래를 함께 부르는 공동체';

    // 큰 제목 (여러 줄 자동)
    imagettftext($im, 72, 0, 420, 200, $cBlack, $fontKo, '동서울');
    imagettftext($im, 72, 0, 420, 305, $cBlack, $fontKo, '소망교회');
    imagefilledrectangle($im, 420, 345, 500, 353, $cBlack);
    imagettftext($im, 26, 0, 420, 395, $cBlack, $fontKo, '소망의 노래를 함께');
    imagettftext($im, 26, 0, 420, 435, $cBlack, $fontKo, '부르는 공동체');
    imagefilledrectangle($im, 420, 480, 1120, 560, $cBlack);
    imagettftext($im, 18, 0, 445, 528, $cYellow, $fontKo, '예배 · 설교 · 공동체 · 봉사 · 선교');
} else {
    // 한글 폰트 없음 — 영문 fallback
    imagestring($im, 5, 420, 180, 'DONGSEOUL', $cBlack);
    imagestring($im, 5, 420, 220, 'SOMANG CHURCH', $cBlack);
    imagefilledrectangle($im, 420, 260, 500, 264, $cBlack);
    imagestring($im, 4, 420, 290, 'Hope. Faith. Community.', $cBlack);
    imagestring($im, 3, 420, 330, 'Worship - Sermon - Fellowship', $cBlack);
    imagefilledrectangle($im, 420, 480, 1120, 560, $cBlack);
    imagestring($im, 3, 445, 510, 'Welcome to Dongseoul Somang Church', $cYellow);
}

imagepng($im);
imagedestroy($im);
