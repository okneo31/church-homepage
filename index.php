<?php
session_start();
include 'db.php';

// [PHP] 1. 관리자 로그인/로그아웃
if (isset($_GET['logout'])) {
    session_destroy();
    header("Location: index.php");
    exit;
}

$login_error = '';
if (isset($_POST['login_id'])) {
    $username = $_POST['login_id'];
    $password = $_POST['login_pw'];

    $stmt = $pdo->prepare("SELECT * FROM admins WHERE username = ?");
    $stmt->execute([$username]);
    $admin = $stmt->fetch();

    if ($admin && password_verify($password, $admin['password'])) {
        $_SESSION['admin'] = true;
        header("Location: index.php");
        exit;
    } else {
        $login_error = '아이디 또는 비밀번호가 틀렸습니다.';
    }
}

// [PHP] 2. 설정값 불러오기
$config = getSettings($pdo);
$isAdmin = isset($_SESSION['admin']);
$previewMode = isset($_GET['preview']) && $_GET['preview'] == '1';

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
    
    <meta property="og:type" content="website">
    <meta property="og:title" content="<?= htmlspecialchars($config['og_title'] ?: '동서울소망교회') ?>">
    <meta property="og:description" content="<?= htmlspecialchars($config['og_desc']) ?>">
    <meta property="og:image" content="http://<?= $_SERVER['HTTP_HOST'] ?>/church/<?= $config['logo_img'] ?>">
    
    <title><?= htmlspecialchars($config['og_title']) ?></title>
    
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


    <?php if ($isAdmin && !$previewMode): ?>
    <button onclick="toggleSidebar()" class="fixed bottom-6 right-6 z-50 bg-black text-white w-16 h-16 rounded-full border-4 border-yellow-400 shadow-xl flex items-center justify-center hover:scale-110 transition group">
        <span class="text-3xl group-hover:rotate-90 transition duration-300">⚙️</span>
    </button>

    <div id="admin-sidebar" class="fixed inset-y-0 right-0 z-[60] w-full md:w-[450px] bg-white border-l-4 border-black transform translate-x-full transition-transform duration-300 shadow-2xl flex flex-col">
        <div class="bg-black text-white p-4 border-b-4 border-yellow-400 flex justify-between items-center">
            <h2 class="font-black text-lg">🔧 실시간 편집 모드</h2>
            <div>
                <a href="index.php?logout=1" class="text-xs text-yellow-400 underline mr-4">로그아웃</a>
                <button onclick="toggleSidebar()" class="text-2xl hover:text-yellow-400 font-bold">×</button>
            </div>
        </div>

        <div class="flex-1 overflow-y-auto p-6 space-y-8 bg-gray-50">
            
            <section class="bg-white p-4 border-4 border-black shadow-md">
                <h3 class="font-black text-lg mb-4 border-b-2 border-black pb-1">🖼 로고 & 공유 설정</h3>
                
                <div class="bg-gray-100 p-3 mb-4 border-2 border-dashed border-gray-400">
                    <label class="font-bold text-xs mb-1 block">교회 로고 (파일 업로드)</label>
                    <div class="flex items-center gap-2 mb-2">
                        <img id="admin-logo-preview" src="<?= $config['logo_img'] ?>" class="h-10 w-10 object-contain border border-black bg-white">
                        <span class="text-xs text-gray-500">현재 로고</span>
                    </div>
                    <input type="file" id="input-logo-img-file" class="swiss-file" accept="image/*">
                </div>

                <div class="space-y-2">
                    <div>
                        <label class="text-xs font-bold text-gray-500">교회 이름 (로고 옆 글자)</label>
                        <input type="text" id="input-logo" class="swiss-input" value="<?= $config['logo'] ?>" oninput="updateView()">
                    </div>
                    <div>
                        <label class="text-xs font-bold text-blue-600">공유 제목 (OG Title)</label>
                        <input type="text" id="input-og-title" class="swiss-input" value="<?= $config['og_title'] ?>">
                    </div>
                    <div>
                        <label class="text-xs font-bold text-blue-600">공유 설명 (OG Desc)</label>
                        <input type="text" id="input-og-desc" class="swiss-input" value="<?= $config['og_desc'] ?>">
                    </div>
                </div>
            </section>

            <section class="bg-white p-4 border-4 border-black shadow-md">
                <h3 class="font-black text-lg mb-4 border-b-2 border-black pb-1">🎨 디자인 & 컬러</h3>
                <div class="grid grid-cols-2 gap-2 mb-2">
                    <div><label class="text-xs font-bold text-gray-500">배경색</label><input type="color" id="input-hero-bg-color" value="<?= $config['hero_bg_color'] ?>" class="h-8 w-full border-2 border-black p-0" oninput="updateView()"></div>
                    <div><label class="text-xs font-bold text-gray-500">글자색</label><input type="color" id="input-hero-text-color" value="<?= $config['hero_text_color'] ?>" class="h-8 w-full border-2 border-black p-0" oninput="updateView()"></div>
                </div>
                <div><label class="text-xs font-bold text-gray-500">포인트 컬러</label><input type="color" id="input-accent-color" value="<?= $config['accent_color'] ?>" class="h-8 w-full border-2 border-black p-0" oninput="updateView()"></div>
            </section>

            <section class="bg-white p-4 border-4 border-black shadow-md">
                <h3 class="font-black text-lg mb-4 border-b-2 border-black pb-1">🏠 메인 배너</h3>
                <div class="space-y-3">
                    <input type="text" id="input-hero-tag" class="swiss-input" value="<?= $config['hero_tag'] ?>" oninput="updateView()">
                    <textarea id="input-hero-title" class="swiss-input h-20" oninput="updateView()"><?= $config['hero_title'] ?></textarea>
                    <textarea id="input-hero-desc" class="swiss-input h-20" oninput="updateView()"><?= $config['hero_desc'] ?></textarea>
                    <input type="text" id="input-hero-btn-text" class="swiss-input" value="<?= $config['hero_btn_text'] ?>" oninput="updateView()">
                    
                    <div class="bg-blue-50 p-2 border-2 border-dashed border-blue-200">
                        <label class="text-xs font-bold text-gray-500 mb-1 block">메인 이미지</label>
                        <input type="text" id="input-hero-img" class="swiss-input text-xs mb-2" value="<?= $config['hero_img'] ?>" oninput="updateView()" placeholder="이미지 URL">
                        <input type="file" id="input-hero-img-file" class="swiss-file" accept="image/*">
                    </div>
                </div>
            </section>

            <section class="bg-white p-4 border-4 border-black shadow-md">
                <h3 class="font-black text-lg mb-4 border-b-2 border-black pb-1">📺 설교 & 공지</h3>
                <input type="text" id="input-main-youtube-url" class="swiss-input text-xs text-blue-600 mb-2" value="<?= $config['main_youtube_url'] ?>" onchange="updateView()">
                <input type="text" id="input-sermon-title" class="swiss-input mb-2" value="<?= $config['sermon_title'] ?>" oninput="updateView()">
                <input type="text" id="input-sermon-info" class="swiss-input mb-4" value="<?= $config['sermon_info'] ?>" oninput="updateView()">
                
                <div class="bg-gray-100 p-2 mb-2 border-2 border-dashed border-gray-400">
                    <p class="font-bold text-xs mb-1">공지 1</p>
                    <input type="text" id="input-notice1-title" class="swiss-input mb-1 text-sm" value="<?= $config['notice1_title'] ?>" oninput="updateView()">
                    <input type="text" id="input-notice1-desc" class="swiss-input mb-1 text-sm" value="<?= $config['notice1_desc'] ?>" oninput="updateView()">
                    <input type="file" id="input-notice1-img-file" class="swiss-file mt-1" accept="image/*">
                    <input type="hidden" id="input-notice1-img" value="<?= $config['notice1_img'] ?>">
                </div>
                
                <input type="hidden" id="input-notice1-tag" value="<?= $config['notice1_tag'] ?>">
                <input type="hidden" id="input-notice2-title" value="<?= $config['notice2_title'] ?>">
                <input type="hidden" id="input-notice2-desc" value="<?= $config['notice2_desc'] ?>">
                <input type="hidden" id="input-show-notices" value="<?= $config['show_notices'] ?>">
            </section>

             <section class="bg-white p-4 border-4 border-black shadow-md">
                <h3 class="font-black text-lg mb-4 border-b-2 border-black pb-1">📍 정보</h3>
                <div class="space-y-3">
                    <input type="text" id="input-church-address" class="swiss-input" value="<?= $config['church_address'] ?>" oninput="updateView()">
                    <input type="text" id="input-church-phone" class="swiss-input" value="<?= $config['church_phone'] ?>" oninput="updateView()">
                    <input type="text" id="input-copyright" class="swiss-input text-xs" value="<?= $config['copyright'] ?>" oninput="updateView()">
                </div>
            </section>
            <div class="h-10"></div>
        </div>

        <div class="p-4 bg-white border-t-4 border-black">
            <button onclick="saveSettings()" id="save-btn" class="swiss-btn bg-yellow-400 w-full py-3 text-xl hover:bg-yellow-300">변경사항 저장 (SAVE)</button>
        </div>
    </div>

    <script>
        function toggleSidebar() {
            const sidebar = document.getElementById('admin-sidebar');
            sidebar.classList.toggle('translate-x-full');
            sidebar.classList.toggle('translate-x-0');
        }

        function updateView() {
            const getVal = (id) => document.getElementById(id).value;
            const setTxt = (id, val) => { const el = document.getElementById(id); if(el) el.innerText = val; };
            const setHTML = (id, val) => { const el = document.getElementById(id); if(el) el.innerHTML = val.replace(/\n/g, '<br>'); };

            // ✅ 수정됨: 로고 글자도 항상 업데이트
            setTxt('view-logo-text', getVal('input-logo'));
            setTxt('view-footer-text', getVal('input-logo'));

            setTxt('view-hero-tag', getVal('input-hero-tag'));
            setHTML('view-hero-title', getVal('input-hero-title'));
            setHTML('view-hero-desc', getVal('input-hero-desc'));
            setTxt('view-hero-btn', getVal('input-hero-btn-text'));
            setTxt('view-sermon-title', getVal('input-sermon-title'));
            setTxt('view-sermon-info', getVal('input-sermon-info'));
            setTxt('view-notice1-title', getVal('input-notice1-title'));
            setTxt('view-notice1-desc', getVal('input-notice1-desc'));
            setTxt('view-address', getVal('input-church-address')); 
            setTxt('view-phone', getVal('input-church-phone'));
            setTxt('view-copyright', getVal('input-copyright'));
            
            const heroBg = getVal('input-hero-bg-color');
            const heroText = getVal('input-hero-text-color');
            const accent = getVal('input-accent-color');
            document.getElementById('view-hero-bg').style.backgroundColor = heroBg;
            document.getElementById('view-hero-bg').style.color = heroText;
            document.getElementById('view-notices-section').style.backgroundColor = accent;
        }

        function getYoutubeID(url) {
            const regExp = /^.*(youtu.be\/|v\/|u\/\w\/|embed\/|watch\?v=|&v=)([^#&?]*).*/;
            const match = url.match(regExp);
            return (match && match[2].length === 11) ? match[2] : null;
        }

        function saveSettings() {
            const btn = document.getElementById('save-btn');
            const originalText = btn.innerText;
            btn.innerText = "저장 중...";
            btn.disabled = true;

            const formData = new FormData();
            const inputs = document.querySelectorAll('input[type="text"], input[type="color"], textarea, input[type="hidden"]');
            inputs.forEach(input => {
                const name = input.id.replace('input-', '').replace(/-/g, '_');
                formData.append(name, input.value);
            });

            // 파일 처리 (로고 포함)
            const files = ['logo-img-file', 'hero-img-file', 'notice1-img-file'];
            files.forEach(id => {
                const fileInput = document.getElementById('input-' + id);
                if (fileInput && fileInput.files[0]) {
                    formData.append(id.replace(/-/g, '_'), fileInput.files[0]);
                }
            });

            fetch('api.php', { method: 'POST', body: formData })
            .then(response => response.json())
            .then(result => {
                if (result.status === 'success') {
                    alert('저장되었습니다!');
                    // ✅ 수정됨: 로고 업로드 성공 시 이미지만 바꾸고 텍스트 숨기지 않음
                    if(result.new_logo) {
                        document.getElementById('view-logo-img').src = result.new_logo;
                        document.getElementById('view-footer-img').src = result.new_logo;
                        document.getElementById('admin-logo-preview').src = result.new_logo;
                        document.getElementById('view-logo-img').style.display = 'block';
                        document.getElementById('view-footer-img').style.display = 'block';
                    }
                    if(result.new_hero_img) document.getElementById('view-hero-img').src = result.new_hero_img;
                    if(result.new_notice1_img) document.getElementById('view-notice1-img').src = result.new_notice1_img;
                } else {
                    alert('오류: ' + result.message);
                }
            })
            .catch(error => alert('통신 오류: ' + error))
            .finally(() => {
                btn.innerText = originalText;
                btn.disabled = false;
            });
        }
    </script>

    <?php elseif (!$previewMode): ?>
    <button onclick="document.getElementById('login-modal').classList.remove('hidden')" class="fixed bottom-6 right-6 z-50 bg-gray-200 text-black w-10 h-10 rounded-full border-2 border-black flex items-center justify-center hover:bg-gray-300 opacity-50 hover:opacity-100">🔑</button>
    <div id="login-modal" class="hidden fixed inset-0 z-[100] bg-black/80 backdrop-blur-sm flex justify-center items-center p-4">
        <form method="POST" class="bg-white border-4 border-black w-full max-w-sm shadow-2xl p-8 text-center relative">
            <button type="button" onclick="document.getElementById('login-modal').classList.add('hidden')" class="absolute top-2 right-4 text-2xl font-bold">×</button>
            <h2 class="text-3xl font-black mb-6">ADMIN LOGIN</h2>
            <?php if($login_error): ?><p class="text-red-600 font-bold mb-4"><?= $login_error ?></p><?php endif; ?>
            <div class="space-y-4">
                <input type="text" name="login_id" placeholder="ID (admin)" class="swiss-input">
                <input type="password" name="login_pw" placeholder="PASSWORD (1234)" class="swiss-input">
                <button type="submit" class="swiss-btn bg-yellow-400 w-full py-3 text-lg hover:bg-yellow-300">LOGIN</button>
            </div>
        </form>
    </div>
    <?php endif; ?>
</body>
</html>