<?php
include 'db.php';

// 설정값 불러오기
$config = getSettings($pdo);

// OG 메타 헬퍼
$scheme = ((!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || ($_SERVER['SERVER_PORT'] ?? '') == 443) ? 'https' : 'http';
$siteHost = $_SERVER['HTTP_HOST'] ?? 'localhost';
$siteOrigin = $scheme . '://' . $siteHost;
$currentUrl = $siteOrigin . ($_SERVER['REQUEST_URI'] ?? '/');

$siteName = $config['logo'] ?? '동서울소망교회';
$ogTitle = $config['og_title'] ?? '';
if ($ogTitle === '') $ogTitle = $siteName . ' | 소망의 노래를 함께 부르는 공동체';
$ogDesc = $config['og_desc'] ?? '';
if ($ogDesc === '') $ogDesc = '예배 · 설교 · 공동체 · 봉사 · 선교 — 중랑구 면목동 ' . $siteName . '입니다. 매주 주일 함께 예배드립니다.';

// OG 이미지: 사용자가 admin에서 og_image 설정했으면 그것 우선
// 카카오톡 등은 SVG 미지원이라 PNG가 기본 (og-image.png)
$ogImageRaw = $config['og_image'] ?? '';
if ($ogImageRaw === '') $ogImageRaw = 'og-image.png';
$ogImage = (preg_match('#^https?://#', $ogImageRaw)) ? $ogImageRaw : $siteOrigin . '/' . ltrim($ogImageRaw, '/');
// 캐시 버스터 — 카카오 / 페이스북 / 텔레그램이 이미지 캐시함. 갱신 시 mtime을 쿼리에 붙임
if (!preg_match('#^https?://#', $ogImageRaw)) {
    $localPath = __DIR__ . '/' . ltrim($ogImageRaw, '/');
    if (file_exists($localPath)) {
        $ogImage .= '?v=' . filemtime($localPath);
    }
}

// 지도 좌표 (동서울소망교회)
$mapLat = '37.5847861';
$mapLng = '127.0820967';

// 최근 게시글 5개 (홈에서 미리보기)
function pickFirstLine($text) {
    if ($text === null || $text === '') return '';
    $lines = preg_split('/\r\n|\r|\n/', $text);
    foreach ($lines as $l) {
        $l = trim($l);
        if ($l !== '') return $l;
    }
    return '';
}
try {
    $stmt = $pdo->query("SELECT id, title, content, image_file, image_link, created_at FROM board ORDER BY id DESC LIMIT 5");
    $recent_posts = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    $recent_posts = [];
}
?>

<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    
    <meta name="description" content="<?= htmlspecialchars($ogDesc) ?>">
    <link rel="canonical" href="<?= htmlspecialchars($currentUrl) ?>">

    <!-- Open Graph -->
    <meta property="og:type" content="website">
    <meta property="og:locale" content="ko_KR">
    <meta property="og:site_name" content="<?= htmlspecialchars($siteName) ?>">
    <meta property="og:url" content="<?= htmlspecialchars($currentUrl) ?>">
    <meta property="og:title" content="<?= htmlspecialchars($ogTitle) ?>">
    <meta property="og:description" content="<?= htmlspecialchars($ogDesc) ?>">
    <meta property="og:image" content="<?= htmlspecialchars($ogImage) ?>">
    <meta property="og:image:width" content="1200">
    <meta property="og:image:height" content="630">
    <meta property="og:image:alt" content="<?= htmlspecialchars($siteName) ?>">

    <!-- Twitter Card -->
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="<?= htmlspecialchars($ogTitle) ?>">
    <meta name="twitter:description" content="<?= htmlspecialchars($ogDesc) ?>">
    <meta name="twitter:image" content="<?= htmlspecialchars($ogImage) ?>">

    <title><?= htmlspecialchars($ogTitle) ?></title>
    
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;700;900&family=Noto+Sans+KR:wght@400;700;900&display=swap" rel="stylesheet">
    
    <style>
        body { font-family: 'Inter', 'Noto Sans KR', sans-serif; }
        .swiss-border { border: 4px solid black; }
        .swiss-border-b { border-bottom: 4px solid black; }
        .swiss-border-r { border-right: 4px solid black; }
        .swiss-border-l { border-left: 4px solid black; }
        
        .swiss-input { border: 2px solid black; padding: 6px; width: 100%; font-weight: bold; font-size: 0.9rem; }
        .swiss-file { border: 2px dashed black; padding: 6px; width: 100%; font-size: 0.8rem; background: #f9fafb; cursor: pointer; }
        .swiss-btn { border: 3px solid black; font-weight: 900; box-shadow: 4px 4px 0 0 black; transition: 0.1s; cursor: pointer; }
        .swiss-btn:active { transform: translate(2px, 2px); box-shadow: 2px 2px 0 0 black; }

        .root_daum_roughmap { width: 100% !important; height: 100% !important; }
        .root_daum_roughmap .wrap_map { height: 100% !important; }
    </style>
</head>
<body class="bg-gray-100 text-black min-h-screen">

    <div id="site-wrapper" class="max-w-[1600px] mx-auto bg-white shadow-2xl min-h-screen swiss-border-l swiss-border-r relative">
        
        <nav class="sticky top-0 z-40 bg-white grid grid-cols-4 swiss-border-b h-20">
            <a href="index.php" class="col-span-1 px-5 hover:bg-yellow-400 transition flex items-center border-r-4 border-black truncate gap-3">
                <img id="view-logo-img" src="<?= $config['logo_img'] ?>" alt="Logo" class="h-10 w-auto object-contain" style="display: <?= $config['logo_img'] ? 'block' : 'none' ?>;">
                
                <span id="view-logo-text" class="font-black text-xl md:text-2xl tracking-tighter leading-none truncate">
                    <?= htmlspecialchars($config['logo']) ?>
                </span>
            </a>
            <div class="col-span-3 flex justify-end">
                <a href="board.php" class="hidden md:flex items-center px-8 font-bold hover:bg-gray-100 border-l-4 border-black">COMMUNITY</a>
                <a href="#" class="flex items-center px-8 font-bold bg-black text-white hover:bg-gray-800 border-l-4 border-black">VISIT</a>
            </div>
        </nav>

        <header class="grid grid-cols-1 md:grid-cols-2 h-auto md:h-[650px] swiss-border-b relative">
            <div id="view-hero-bg" class="p-12 flex flex-col justify-center relative overflow-hidden transition-colors duration-300" 
                 style="background-color: <?= $config['hero_bg_color'] ?>; color: <?= $config['hero_text_color'] ?>;">
                <div class="z-10 relative">
                    <span id="view-hero-tag" class="inline-block px-3 py-1 border-2 border-current font-bold text-xs mb-6"><?= htmlspecialchars($config['hero_tag']) ?></span>
                    <h1 id="view-hero-title" class="text-6xl md:text-8xl font-black leading-none tracking-tighter mb-6 whitespace-pre-wrap"><?= $config['hero_title'] ?></h1>
                    <p id="view-hero-desc" class="text-xl font-bold border-l-4 border-current pl-6 mb-10 opacity-90 whitespace-pre-wrap"><?= $config['hero_desc'] ?></p>
                    <a href="#" id="view-hero-btn" class="inline-block px-8 py-4 font-black border-4 border-black hover:-translate-y-1 transition text-black" style="background-color: white;"><?= htmlspecialchars($config['hero_btn_text']) ?></a>
                </div>
            </div>
            <div class="relative md:border-l-4 border-black h-[400px] md:h-full overflow-hidden">
                <img id="view-hero-img" src="<?= $config['hero_img'] ?>" class="w-full h-full object-cover transition duration-700 hover:scale-105">
            </div>
        </header>

        <main class="grid grid-cols-1 md:grid-cols-3 swiss-border-b">
            <div class="col-span-2 md:border-r-4 border-black border-b-4 md:border-b-0">
                <div class="p-6 border-b-4 border-black bg-gray-100 flex justify-between items-center">
                    <h2 class="font-black text-2xl">LATEST SERMON</h2>
                    <span class="font-mono text-xs font-bold bg-red-600 text-white px-2 py-1 border-2 border-black">LIVE</span>
                </div>
                <div class="aspect-video w-full bg-black relative">
                    <iframe id="view-youtube" class="w-full h-full" src="<?= getYoutubeEmbed($config['main_youtube_url']) ?>" frameborder="0" allowfullscreen></iframe>
                </div>
                <div class="p-8 bg-white">
                    <h3 id="view-sermon-title" class="text-3xl font-black mb-2"><?= htmlspecialchars($config['sermon_title']) ?></h3>
                    <p id="view-sermon-info" class="font-mono text-gray-500 font-bold"><?= htmlspecialchars($config['sermon_info']) ?></p>
                </div>
            </div>

            <div id="view-notices-section" class="col-span-1 flex flex-col min-h-[500px]" style="background-color: <?= $config['accent_color'] ?>; display: <?= $config['show_notices'] == '1' ? 'flex' : 'none' ?>;">
                <div class="p-6 border-b-4 border-black flex justify-between items-center bg-white">
                    <h2 class="font-black text-2xl">NOTICES</h2>
                </div>
                <div class="border-b-4 border-black group cursor-pointer hover:bg-white transition flex-1 relative bg-white">
                    <div class="h-48 overflow-hidden border-b-4 border-black relative">
                        <img id="view-notice1-img" src="<?= $config['notice1_img'] ?>" class="w-full h-full object-cover">
                        <div id="view-notice1-tag" class="absolute top-2 right-2 bg-black text-white text-xs font-bold px-2 py-1 border-2 border-white"><?= $config['notice1_tag'] ?></div>
                    </div>
                    <div class="p-6">
                        <h4 id="view-notice1-title" class="font-bold text-xl mb-2"><?= $config['notice1_title'] ?></h4>
                        <p id="view-notice1-desc" class="text-sm font-mono opacity-70 font-bold"><?= $config['notice1_desc'] ?></p>
                    </div>
                </div>
                <div class="p-6 group cursor-pointer hover:bg-white transition flex-1 bg-white/50">
                    <h4 id="view-notice2-title" class="font-bold text-xl mb-3"><?= $config['notice2_title'] ?></h4>
                    <p id="view-notice2-desc" class="text-sm leading-relaxed border-l-4 border-black pl-4 font-bold opacity-80 whitespace-pre-wrap"><?= $config['notice2_desc'] ?></p>
                </div>
            </div>
        </main>

        <?php if (!empty($recent_posts)): ?>
        <section class="swiss-border-b">
            <div class="p-6 border-b-4 border-black bg-gray-100 flex justify-between items-center">
                <h2 class="font-black text-2xl">RECENT POSTS</h2>
                <a href="board.php" class="font-bold text-sm underline hover:bg-yellow-400 px-2 py-1">전체 보기 →</a>
            </div>
            <div class="grid grid-cols-2 md:grid-cols-5">
                <?php foreach ($recent_posts as $rp):
                    $rp_files = preg_split('/\r\n|\r|\n/', $rp['image_file'] ?? '');
                    $rp_links = preg_split('/\r\n|\r|\n/', $rp['image_link'] ?? '');
                    $thumb = '';
                    foreach ($rp_files as $f) { $f = trim($f); if ($f !== '') { $thumb = 'uploads/' . $f; break; } }
                    if ($thumb === '') {
                        foreach ($rp_links as $l) { $l = trim($l); if ($l !== '') { $thumb = $l; break; } }
                    }
                    $preview = pickFirstLine($rp['content']);
                    if (mb_strlen($preview) > 40) $preview = mb_substr($preview, 0, 40) . '…';
                ?>
                <a href="board.php" class="block border-r-4 border-b-4 md:border-b-0 border-black last:border-r-0 hover:bg-yellow-400 transition group">
                    <div class="aspect-square bg-gray-200 overflow-hidden border-b-4 border-black relative">
                        <?php if ($thumb): ?>
                            <img src="<?= htmlspecialchars($thumb) ?>" class="w-full h-full object-cover group-hover:scale-105 transition duration-300" onerror="this.style.display='none';this.parentNode.classList.add('bg-gray-300');">
                        <?php else: ?>
                            <div class="w-full h-full flex items-center justify-center text-4xl font-black text-gray-400">📝</div>
                        <?php endif; ?>
                        <span class="absolute top-2 left-2 bg-black text-white text-[10px] font-mono font-bold px-2 py-1">No.<?= $rp['id'] ?></span>
                    </div>
                    <div class="p-3">
                        <h4 class="font-black text-sm leading-tight mb-1 line-clamp-2"><?= htmlspecialchars($rp['title']) ?></h4>
                        <p class="text-xs text-gray-600 font-bold leading-tight line-clamp-1"><?= htmlspecialchars($preview) ?></p>
                        <p class="font-mono text-[10px] text-gray-400 mt-2"><?= htmlspecialchars(substr($rp['created_at'] ?? '', 0, 10)) ?></p>
                    </div>
                </a>
                <?php endforeach; ?>
            </div>
        </section>
        <?php endif; ?>

        <section class="swiss-border-b grid grid-cols-1 md:grid-cols-3 min-h-[400px]">
            <div class="col-span-1 p-8 md:p-12 bg-yellow-400 border-b-4 md:border-b-0 md:border-r-4 border-black flex flex-col justify-center">
                <h2 class="text-3xl font-black mb-6 tracking-tight">LOCATION</h2>
                <div class="bg-white p-6 border-4 border-black shadow-[6px_6px_0_0_black]">
                    <p class="text-xs font-bold text-gray-500 mb-1">CHURCH ADDRESS</p>
                    <p id="view-address" class="font-bold text-lg mb-4 leading-snug">
                        <?= $config['church_address'] ?: '서울 중랑구 겸재로 154 (면목동)' ?>
                    </p>
                    <p class="text-xs font-bold text-gray-500 mb-1">TEL</p>
                    <p id="view-phone" class="font-bold text-lg"><?= $config['church_phone'] ?></p>
                </div>
            </div>
            <div class="col-span-2 relative bg-gray-100 h-[400px] md:h-auto overflow-hidden" id="map-wrapper">
                <div id="daumRoughmapContainer1771769326195" class="root_daum_roughmap root_daum_roughmap_landing" style="width:100%; height:100%;"></div>
                <script charset="UTF-8" class="daum_roughmap_loader_script" src="https://ssl.daumcdn.net/dmaps/map_js_init/roughmapLoader.js"></script>
                <script charset="UTF-8">
                    var mapWrapper = document.getElementById('map-wrapper');
                    new daum.roughmap.Lander({
                        "timestamp" : "1771769326195",
                        "key" : "i82ymihdkfh",
                        "mapWidth" : mapWrapper.offsetWidth.toString(),
                        "mapHeight" : mapWrapper.offsetHeight.toString()
                    }).render();
                </script>
            </div>
        </section>

        <footer class="p-12 bg-black text-white">
            <div class="flex items-center gap-4 mb-4">
                <img id="view-footer-img" src="<?= $config['logo_img'] ?>" class="h-12 w-auto bg-white border-2 border-white rounded-sm" style="display: <?= $config['logo_img'] ? 'block' : 'none' ?>;">
                <h3 id="view-footer-text" class="text-3xl font-black">
                    <?= nl2br(htmlspecialchars($config['logo'])) ?>
                </h3>
            </div>
            <p id="view-copyright" class="text-xs opacity-50 font-mono"><?= $config['copyright'] ?></p>
        </footer>
    </div>

</body>
</html>
