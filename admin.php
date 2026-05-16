<?php
session_start();
include 'db.php';

// 로그아웃
if (isset($_GET['logout'])) {
    session_destroy();
    header("Location: admin.php");
    exit;
}

// 로그인 처리
$msg = '';
if (isset($_POST['login'])) {
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';
    $stmt = $pdo->prepare("SELECT * FROM admins WHERE username = ?");
    $stmt->execute([$username]);
    $admin = $stmt->fetch();
    if ($admin && password_verify($password, $admin['password'])) {
        $_SESSION['admin'] = true;
        header("Location: admin.php");
        exit;
    } else {
        $msg = '아이디 또는 비밀번호가 틀렸습니다.';
    }
}

// 로그인 폼
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
        <?php if ($msg) echo "<p class='text-red-600 font-bold mb-4'>" . htmlspecialchars($msg) . "</p>"; ?>
        <form method="POST">
            <input type="text" name="username" placeholder="ID" class="w-full border-4 border-black p-2 mb-4 font-bold">
            <input type="password" name="password" placeholder="PASSWORD" class="w-full border-4 border-black p-2 mb-4 font-bold">
            <button type="submit" name="login" class="w-full bg-yellow-400 border-4 border-black p-2 font-black hover:bg-yellow-300">LOGIN</button>
        </form>
        <a href="index.php" class="block text-center mt-4 underline font-bold">홈으로 돌아가기</a>
    </div>
</body>
</html>
<?php
    exit;
}

// 비밀번호 변경
if (isset($_POST['change_pw'])) {
    $new = $_POST['new_password'] ?? '';
    if (strlen($new) >= 4) {
        $hash = password_hash($new, PASSWORD_DEFAULT);
        $stmt = $pdo->prepare("INSERT INTO admins (username, password) VALUES ('admin', ?) ON DUPLICATE KEY UPDATE password = VALUES(password)");
        $stmt->execute([$hash]);
        echo "<script>alert('비밀번호가 변경되었습니다. 다시 로그인해주세요.'); location.href='admin.php?logout=1';</script>";
        exit;
    }
}

$config = getSettings($pdo);
$stmt = $pdo->query("SELECT id, title, name, created_at, image_file, image_link, youtube_url, LEFT(content, 100) AS preview FROM board ORDER BY id DESC LIMIT 50");
$posts = $stmt->fetchAll(PDO::FETCH_ASSOC);

// 필드 헬퍼
function val($config, $key, $default = '') {
    return htmlspecialchars($config[$key] ?? $default, ENT_QUOTES);
}
function rawval($config, $key, $default = '') {
    return $config[$key] ?? $default;
}
?>
<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Studio</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;700;900&family=Noto+Sans+KR:wght@400;700;900&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Inter', 'Noto Sans KR', sans-serif; }
        .swiss-input { border: 2px solid black; padding: 6px; width: 100%; font-weight: bold; font-size: 0.85rem; }
        .swiss-input:focus { outline: 2px solid #facc15; outline-offset: -2px; }
        .swiss-btn { border: 3px solid black; font-weight: 900; box-shadow: 4px 4px 0 0 black; transition: 0.1s; cursor: pointer; }
        .swiss-btn:active { transform: translate(2px, 2px); box-shadow: 2px 2px 0 0 black; }
        .swiss-file { border: 2px dashed black; padding: 4px; width: 100%; font-size: 0.75rem; background: #fafafa; cursor: pointer; }
        .tab-btn { border: 2px solid black; border-bottom: none; padding: 8px 16px; font-weight: 900; background: white; cursor: pointer; }
        .tab-btn.active { background: black; color: #facc15; }
        .field-label { display: block; font-size: 0.7rem; font-weight: 700; color: #4b5563; margin-bottom: 2px; text-transform: uppercase; letter-spacing: 0.03em; }
        .preview-frame { width: 100%; height: 100%; border: 0; background: white; }
    </style>
</head>
<body class="bg-gray-900 text-white h-screen flex flex-col overflow-hidden">

    <header class="bg-black border-b-4 border-yellow-400 px-4 py-2 flex justify-between items-center shrink-0">
        <h1 class="font-black text-lg">⚙️ ADMIN STUDIO</h1>
        <div class="text-sm">
            <a href="index.php" target="_blank" class="underline hover:text-yellow-400 mr-4">🏠 홈 보기</a>
            <a href="board.php" target="_blank" class="underline hover:text-yellow-400 mr-4">📋 게시판</a>
            <a href="admin.php?logout=1" class="text-yellow-400 underline">로그아웃</a>
        </div>
    </header>

    <div class="flex-1 flex flex-col md:flex-row overflow-hidden">

        <!-- 편집 패널 (상단 또는 좌측) -->
        <div class="md:w-1/2 md:h-full h-1/2 bg-gray-100 text-black flex flex-col border-b-4 md:border-b-0 md:border-r-4 border-black overflow-hidden">

            <div class="flex bg-gray-200 px-4 pt-2 shrink-0 overflow-x-auto">
                <button class="tab-btn active" data-tab="settings">🎨 사이트 설정</button>
                <button class="tab-btn" data-tab="board">📋 게시판 관리</button>
                <button class="tab-btn" data-tab="account">🔒 계정</button>
            </div>

            <div class="flex-1 overflow-y-auto bg-gray-100 p-4">

                <!-- 사이트 설정 탭 -->
                <div class="tab-content" data-tab="settings">

                    <section class="bg-white p-4 border-4 border-black mb-4">
                        <h3 class="font-black text-base mb-3 border-b-2 border-black pb-1">🖼 로고 & 공유</h3>
                        <label class="field-label">교회 로고 (파일 업로드)</label>
                        <div class="flex items-center gap-2 mb-2">
                            <img id="logo-preview" src="<?= val($config, 'logo_img') ?>" class="h-10 w-10 object-contain border border-black bg-white">
                            <input type="file" id="input-logo-img-file" class="swiss-file flex-1" accept="image/*">
                        </div>
                        <label class="field-label">교회 이름 (로고 옆 글자)</label>
                        <input type="text" id="input-logo" class="swiss-input mb-3" value="<?= val($config, 'logo') ?>">

                        <div class="border-t-2 border-dashed border-gray-400 pt-3 mt-3">
                            <p class="font-black text-xs mb-2">🔗 카카오톡/페이스북 공유 (Open Graph)</p>
                            <label class="field-label">공유 제목 (비우면 자동 생성)</label>
                            <input type="text" id="input-og-title" class="swiss-input mb-2" placeholder="동서울소망교회 | 소망의 노래를 함께 부르는 공동체" value="<?= val($config, 'og_title') ?>">
                            <label class="field-label">공유 설명 (비우면 자동 생성)</label>
                            <textarea id="input-og-desc" class="swiss-input mb-2 h-14" placeholder="예배 · 설교 · 공동체 · 봉사 · 선교 — 중랑구 면목동 동서울소망교회입니다."><?= htmlspecialchars(rawval($config, 'og_desc')) ?></textarea>
                            <label class="field-label">공유 이미지 (1200x630 권장)</label>
                            <div class="flex items-center gap-2 mb-1">
                                <img id="og-image-preview" src="<?= val($config, 'og_image') ?: 'og-image.svg' ?>" class="h-12 w-24 object-cover border border-black bg-white">
                                <input type="file" id="input-og-image-file" class="swiss-file flex-1" accept="image/*">
                            </div>
                            <input type="text" id="input-og-image" class="swiss-input text-xs" placeholder="비우면 og-image.svg 자동 사용" value="<?= val($config, 'og_image') ?>">
                            <p class="text-[10px] text-gray-500 mt-1">💡 파일 업로드 또는 URL 입력. 비워두면 기본 디자인(og-image.svg)이 자동 사용됩니다.</p>
                        </div>
                    </section>

                    <section class="bg-white p-4 border-4 border-black mb-4">
                        <h3 class="font-black text-base mb-3 border-b-2 border-black pb-1">🎨 컬러</h3>
                        <div class="grid grid-cols-3 gap-2">
                            <div>
                                <label class="field-label">배경색</label>
                                <input type="color" id="input-hero-bg-color" value="<?= val($config, 'hero_bg_color', '#facc15') ?>" class="h-8 w-full border-2 border-black p-0">
                            </div>
                            <div>
                                <label class="field-label">글자색</label>
                                <input type="color" id="input-hero-text-color" value="<?= val($config, 'hero_text_color', '#000000') ?>" class="h-8 w-full border-2 border-black p-0">
                            </div>
                            <div>
                                <label class="field-label">포인트 컬러</label>
                                <input type="color" id="input-accent-color" value="<?= val($config, 'accent_color', '#facc15') ?>" class="h-8 w-full border-2 border-black p-0">
                            </div>
                        </div>
                    </section>

                    <section class="bg-white p-4 border-4 border-black mb-4">
                        <h3 class="font-black text-base mb-3 border-b-2 border-black pb-1">🏠 메인 배너</h3>
                        <label class="field-label">태그</label>
                        <input type="text" id="input-hero-tag" class="swiss-input mb-2" value="<?= val($config, 'hero_tag') ?>">
                        <label class="field-label">큰 제목</label>
                        <textarea id="input-hero-title" class="swiss-input mb-2 h-16"><?= htmlspecialchars(rawval($config, 'hero_title')) ?></textarea>
                        <label class="field-label">설명</label>
                        <textarea id="input-hero-desc" class="swiss-input mb-2 h-16"><?= htmlspecialchars(rawval($config, 'hero_desc')) ?></textarea>
                        <label class="field-label">버튼 텍스트</label>
                        <input type="text" id="input-hero-btn-text" class="swiss-input mb-2" value="<?= val($config, 'hero_btn_text') ?>">
                        <label class="field-label">메인 이미지 URL</label>
                        <input type="text" id="input-hero-img" class="swiss-input mb-1" value="<?= val($config, 'hero_img') ?>">
                        <input type="file" id="input-hero-img-file" class="swiss-file" accept="image/*">
                    </section>

                    <section class="bg-white p-4 border-4 border-black mb-4">
                        <h3 class="font-black text-base mb-3 border-b-2 border-black pb-1">📺 설교</h3>
                        <label class="field-label">유튜브 URL</label>
                        <input type="text" id="input-main-youtube-url" class="swiss-input mb-2 text-blue-600" value="<?= val($config, 'main_youtube_url') ?>">
                        <label class="field-label">설교 제목</label>
                        <input type="text" id="input-sermon-title" class="swiss-input mb-2" value="<?= val($config, 'sermon_title') ?>">
                        <label class="field-label">설교 본문/날짜</label>
                        <input type="text" id="input-sermon-info" class="swiss-input" value="<?= val($config, 'sermon_info') ?>">
                    </section>

                    <section class="bg-white p-4 border-4 border-black mb-4">
                        <h3 class="font-black text-base mb-3 border-b-2 border-black pb-1">📢 공지</h3>
                        <label class="field-label flex items-center gap-2">
                            <input type="checkbox" id="input-show-notices" <?= rawval($config, 'show_notices') == '1' ? 'checked' : '' ?>>
                            공지 영역 표시
                        </label>
                        <div class="border-t pt-2 mt-2">
                            <p class="text-xs font-black mb-1">공지 1</p>
                            <input type="text" id="input-notice1-tag" class="swiss-input mb-1 text-xs" placeholder="태그" value="<?= val($config, 'notice1_tag') ?>">
                            <input type="text" id="input-notice1-title" class="swiss-input mb-1 text-sm" placeholder="제목" value="<?= val($config, 'notice1_title') ?>">
                            <input type="text" id="input-notice1-desc" class="swiss-input mb-1 text-sm" placeholder="설명" value="<?= val($config, 'notice1_desc') ?>">
                            <input type="file" id="input-notice1-img-file" class="swiss-file" accept="image/*">
                            <input type="hidden" id="input-notice1-img" value="<?= val($config, 'notice1_img') ?>">
                        </div>
                        <div class="border-t pt-2 mt-2">
                            <p class="text-xs font-black mb-1">공지 2</p>
                            <input type="text" id="input-notice2-title" class="swiss-input mb-1 text-sm" placeholder="제목" value="<?= val($config, 'notice2_title') ?>">
                            <textarea id="input-notice2-desc" class="swiss-input text-sm h-16" placeholder="설명"><?= htmlspecialchars(rawval($config, 'notice2_desc')) ?></textarea>
                        </div>
                    </section>

                    <section class="bg-white p-4 border-4 border-black mb-4">
                        <h3 class="font-black text-base mb-3 border-b-2 border-black pb-1">📍 정보 & 푸터</h3>
                        <label class="field-label">교회 주소</label>
                        <input type="text" id="input-church-address" class="swiss-input mb-2" value="<?= val($config, 'church_address') ?>">
                        <label class="field-label">전화번호</label>
                        <input type="text" id="input-church-phone" class="swiss-input mb-2" value="<?= val($config, 'church_phone') ?>">
                        <label class="field-label">저작권 (Footer)</label>
                        <input type="text" id="input-copyright" class="swiss-input text-xs" value="<?= val($config, 'copyright') ?>">
                    </section>

                    <div class="sticky bottom-0 bg-gray-100 pt-2 -mx-4 px-4 -mb-4 pb-4 border-t-2 border-black">
                        <button onclick="saveSettings()" id="save-btn" class="swiss-btn bg-yellow-400 w-full py-3 text-base hover:bg-yellow-300">💾 모든 변경사항 저장</button>
                    </div>
                </div>

                <!-- 게시판 관리 탭 -->
                <div class="tab-content hidden" data-tab="board">
                    <section class="bg-white p-4 border-4 border-black">
                        <h3 class="font-black text-base mb-3 border-b-2 border-black pb-1">📋 게시판 글 관리 (최근 50개)</h3>
                        <?php if (empty($posts)): ?>
                            <p class="text-gray-500 text-center py-8">게시글이 없습니다.</p>
                        <?php else: ?>
                            <div class="space-y-2">
                                <?php foreach ($posts as $post): ?>
                                    <div class="border-2 border-black p-3 flex gap-3 items-start" id="post-row-<?= $post['id'] ?>">
                                        <div class="flex-1 min-w-0">
                                            <div class="flex items-center gap-2 mb-1 text-xs font-mono text-gray-500">
                                                <span class="bg-black text-white px-2">No.<?= $post['id'] ?></span>
                                                <span><?= htmlspecialchars($post['created_at']) ?></span>
                                                <?php if (!empty($post['image_file']) || !empty($post['image_link'])): ?>
                                                    <span class="text-yellow-700">📷</span>
                                                <?php endif; ?>
                                                <?php if (!empty($post['youtube_url'])): ?>
                                                    <span class="text-red-600">📺</span>
                                                <?php endif; ?>
                                            </div>
                                            <p class="font-black text-sm leading-tight"><?= htmlspecialchars($post['title']) ?></p>
                                            <p class="text-xs text-gray-600 truncate"><?= htmlspecialchars($post['preview']) ?></p>
                                        </div>
                                        <button onclick="deletePost(<?= $post['id'] ?>)" class="swiss-btn bg-red-500 text-white px-3 py-1 text-xs shrink-0">삭제</button>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </section>
                </div>

                <!-- 계정 탭 -->
                <div class="tab-content hidden" data-tab="account">
                    <section class="bg-gray-800 text-white p-6 border-4 border-black">
                        <h3 class="font-black text-base mb-3 text-yellow-400">🔒 관리자 비밀번호 변경</h3>
                        <form method="POST" onsubmit="return confirm('비밀번호를 변경하면 다시 로그인해야 합니다. 계속하시겠습니까?');">
                            <input type="password" name="new_password" placeholder="새 비밀번호 (4자 이상)" minlength="4" required class="text-black w-full border-2 border-white p-2 font-bold mb-2">
                            <button type="submit" name="change_pw" class="swiss-btn bg-white text-black w-full py-2 font-bold">변경하기</button>
                        </form>
                    </section>
                </div>

            </div>
        </div>

        <!-- 미리보기 (하단 또는 우측) -->
        <div class="md:w-1/2 md:h-full h-1/2 bg-white flex flex-col overflow-hidden">
            <div class="bg-yellow-400 border-b-4 border-black px-3 py-1 flex justify-between items-center text-black shrink-0">
                <p class="font-black text-xs">📺 LIVE PREVIEW</p>
                <button onclick="reloadPreview()" class="text-xs font-bold underline hover:text-red-600">🔄 새로고침</button>
            </div>
            <iframe id="preview-frame" src="index.php?preview=1" class="preview-frame flex-1"></iframe>
        </div>

    </div>

    <script>
        // 탭 전환
        document.querySelectorAll('.tab-btn').forEach(btn => {
            btn.addEventListener('click', () => {
                const tab = btn.dataset.tab;
                document.querySelectorAll('.tab-btn').forEach(b => b.classList.toggle('active', b.dataset.tab === tab));
                document.querySelectorAll('.tab-content').forEach(c => c.classList.toggle('hidden', c.dataset.tab !== tab));
            });
        });

        function reloadPreview() {
            const f = document.getElementById('preview-frame');
            f.src = f.src.split('#')[0] + '&_=' + Date.now();
        }

        function saveSettings() {
            const btn = document.getElementById('save-btn');
            const original = btn.innerText;
            btn.innerText = '저장 중...';
            btn.disabled = true;

            const formData = new FormData();
            formData.append('action', 'save_settings');

            // 텍스트/색상/숨김/textarea 필드들
            document.querySelectorAll('input[type=text], input[type=color], textarea, input[type=hidden]').forEach(input => {
                if (!input.id.startsWith('input-')) return;
                const name = input.id.replace('input-', '').replace(/-/g, '_');
                formData.append(name, input.value);
            });

            // 체크박스 (show_notices)
            const showNotices = document.getElementById('input-show-notices');
            if (showNotices) formData.append('show_notices', showNotices.checked ? '1' : '0');

            // 파일 업로드
            ['logo-img-file', 'hero-img-file', 'notice1-img-file', 'og-image-file'].forEach(id => {
                const fi = document.getElementById('input-' + id);
                if (fi && fi.files[0]) {
                    formData.append(id.replace(/-/g, '_'), fi.files[0]);
                }
            });

            fetch('api.php', { method: 'POST', body: formData })
                .then(r => r.json())
                .then(result => {
                    if (result.status === 'success') {
                        if (result.new_logo) {
                            document.getElementById('logo-preview').src = result.new_logo;
                        }
                        if (result.new_hero_img) {
                            document.getElementById('input-hero-img').value = result.new_hero_img;
                        }
                        if (result.new_notice1_img) {
                            document.getElementById('input-notice1-img').value = result.new_notice1_img;
                        }
                        if (result.new_og_image) {
                            document.getElementById('input-og-image').value = result.new_og_image;
                            document.getElementById('og-image-preview').src = result.new_og_image;
                        }
                        reloadPreview();
                        // 파일 input 초기화
                        ['logo-img-file', 'hero-img-file', 'notice1-img-file', 'og-image-file'].forEach(id => {
                            const fi = document.getElementById('input-' + id);
                            if (fi) fi.value = '';
                        });
                        flash('저장 완료 ✓', '#22c55e');
                    } else {
                        flash('오류: ' + result.message, '#ef4444');
                    }
                })
                .catch(err => flash('통신 오류: ' + err, '#ef4444'))
                .finally(() => {
                    btn.innerText = original;
                    btn.disabled = false;
                });
        }

        function deletePost(id) {
            if (!confirm('No.' + id + ' 게시글을 삭제하시겠습니까? (첨부 사진도 함께 삭제됩니다)')) return;
            const fd = new FormData();
            fd.append('action', 'delete_board');
            fd.append('id', id);
            fetch('api.php', { method: 'POST', body: fd })
                .then(r => r.json())
                .then(result => {
                    if (result.status === 'success') {
                        const row = document.getElementById('post-row-' + id);
                        if (row) row.remove();
                        reloadPreview();
                        flash('삭제 완료 ✓', '#22c55e');
                    } else {
                        flash('오류: ' + result.message, '#ef4444');
                    }
                })
                .catch(err => flash('통신 오류: ' + err, '#ef4444'));
        }

        function flash(msg, color) {
            const t = document.createElement('div');
            t.style.cssText = 'position:fixed;top:60px;left:50%;transform:translateX(-50%);background:' + color + ';color:white;font-weight:900;padding:8px 16px;border:3px solid black;box-shadow:4px 4px 0 0 black;z-index:9999;font-size:14px;';
            t.textContent = msg;
            document.body.appendChild(t);
            setTimeout(() => t.remove(), 2000);
        }
    </script>
</body>
</html>
