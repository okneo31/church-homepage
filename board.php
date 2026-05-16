<?php
include 'db.php';

// 여러 줄 텍스트를 줄단위로 잘라 빈 값 제거
function splitLines($text) {
    if ($text === null || $text === '') return [];
    $lines = preg_split('/\r\n|\r|\n/', $text);
    $lines = array_map('trim', $lines);
    return array_values(array_filter($lines, fn($v) => $v !== ''));
}

// 글쓰기 처리
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = $_POST['title'];
    $content = $_POST['content'];

    // 유튜브 / 이미지링크: 여러 줄 입력을 그대로 저장 (줄바꿈 구분)
    $youtube_lines = splitLines($_POST['youtube_url'] ?? '');
    $imglink_lines = splitLines($_POST['image_link'] ?? '');
    $youtube = implode("\n", $youtube_lines);
    $img_link = implode("\n", $imglink_lines);

    // 복수 파일 업로드 처리
    $uploaded_files = [];
    if (!empty($_FILES['u_image']['name'][0])) {
        $target_dir = "uploads/";
        if (!is_dir($target_dir)) mkdir($target_dir, 0777, true);

        $count = count($_FILES['u_image']['name']);
        for ($i = 0; $i < $count; $i++) {
            if ($_FILES['u_image']['error'][$i] !== UPLOAD_ERR_OK) continue;
            if ($_FILES['u_image']['size'][$i] <= 0) continue;
            $safeName = preg_replace('/[^A-Za-z0-9._-]/', '_', basename($_FILES['u_image']['name'][$i]));
            $newName = time() . '_' . $i . '_' . $safeName;
            if (move_uploaded_file($_FILES['u_image']['tmp_name'][$i], $target_dir . $newName)) {
                $uploaded_files[] = $newName;
            }
        }
    }
    $image_file = implode("\n", $uploaded_files);

    $stmt = $pdo->prepare("INSERT INTO board (name, title, content, youtube_url, image_file, image_link) VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->execute(['성도', $title, $content, $youtube, $image_file, $img_link]);

    header("Location: board.php");
    exit;
}

$stmt = $pdo->query("SELECT * FROM board ORDER BY id DESC");
$posts = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Community Board</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;700;900&family=Noto+Sans+KR:wght@400;700;900&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Inter', 'Noto Sans KR', sans-serif; }
        .swiss-border { border: 4px solid black; }
        .swiss-input { border: 2px solid black; padding: 8px; width: 100%; font-weight: bold; }
        .swiss-btn { border: 3px solid black; font-weight: 900; box-shadow: 4px 4px 0 0 black; transition: 0.1s; cursor: pointer; }
        .swiss-btn:active { transform: translate(2px, 2px); box-shadow: 2px 2px 0 0 black; }
        .file-list { font-size: 0.75rem; color: #374151; margin-top: 4px; }
        .file-list li { margin-left: 1rem; list-style: disc; }
    </style>
</head>
<body class="bg-gray-100 p-4 min-h-screen">

    <div class="max-w-3xl mx-auto">
        <div class="flex justify-between items-center mb-8 border-b-4 border-black pb-4">
            <h1 class="text-4xl font-black tracking-tighter">COMMUNITY</h1>
            <a href="index.php" class="font-bold underline text-lg">🏠 홈으로</a>
        </div>

        <div class="bg-white swiss-border p-6 mb-12 shadow-[8px_8px_0_0_black]">
            <h2 class="font-black text-xl mb-4 bg-black text-white inline-block px-2 py-1">✍️ WRITE POST</h2>
            <form method="POST" enctype="multipart/form-data" class="space-y-4">
                <input type="text" name="title" placeholder="제목을 입력하세요" class="swiss-input text-lg" required>
                <textarea name="content" placeholder="내용을 자유롭게 나누세요..." class="swiss-input h-32" required></textarea>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 bg-gray-50 p-4 border-2 border-black border-dashed">
                    <div class="col-span-2">
                        <label class="block font-bold text-xs mb-1">📺 유튜브 영상 주소 (여러 개는 줄바꿈으로 입력)</label>
                        <textarea name="youtube_url" rows="3" placeholder="https://youtu.be/...&#10;https://youtu.be/..." class="swiss-input text-xs text-blue-600"></textarea>
                    </div>

                    <div>
                        <label class="block font-bold text-xs mb-1">📷 사진 파일 업로드 (여러 장 선택 가능)</label>
                        <input type="file" name="u_image[]" multiple accept="image/*" class="swiss-input text-xs bg-white" id="u_image_input">
                        <ul class="file-list" id="file-list"></ul>
                    </div>

                    <div>
                        <label class="block font-bold text-xs mb-1">🔗 또는 사진 이미지 주소 URL (여러 개는 줄바꿈으로 입력)</label>
                        <textarea name="image_link" rows="3" placeholder="https://example.com/a.jpg&#10;https://example.com/b.jpg" class="swiss-input text-xs"></textarea>
                    </div>
                </div>

                <button type="submit" class="swiss-btn bg-yellow-400 w-full py-3 text-xl hover:bg-yellow-300">
                    게시글 등록하기 (UPLOAD)
                </button>
            </form>
        </div>

        <div class="space-y-8 pb-20">
            <?php foreach ($posts as $post): ?>
            <article class="bg-white swiss-border p-0 shadow-sm hover:shadow-md transition">
                <div class="bg-gray-100 border-b-4 border-black p-3 flex justify-between font-mono font-bold text-xs md:text-sm">
                    <span>No. <?= $post['id'] ?> | 성도</span>
                    <span><?= $post['created_at'] ?></span>
                </div>
                <div class="p-6">
                    <h3 class="text-2xl font-black mb-4 leading-tight"><?= htmlspecialchars($post['title']) ?></h3>
                    <p class="whitespace-pre-wrap mb-6 text-gray-800 leading-relaxed"><?= htmlspecialchars($post['content']) ?></p>

                    <?php
                        $files = splitLines($post['image_file'] ?? '');
                        $links = splitLines($post['image_link'] ?? '');
                        $ytubes = splitLines($post['youtube_url'] ?? '');

                        // 업로드 파일 + 외부 URL을 하나의 이미지 배열로 합침
                        $images = [];
                        foreach ($files as $f) $images[] = 'uploads/' . $f;
                        foreach ($links as $l) $images[] = $l;
                        $imgCount = count($images);
                    ?>

                    <?php if ($imgCount === 1): ?>
                        <div class="swiss-border p-1 bg-white mb-4">
                            <img src="<?= htmlspecialchars($images[0]) ?>" class="w-full object-contain cursor-zoom-in"
                                 onclick="openLightbox(<?= $post['id'] ?>, 0)" onerror="this.style.display='none';">
                        </div>
                        <script type="application/json" id="imgs-<?= $post['id'] ?>"><?= json_encode($images, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) ?></script>
                    <?php elseif ($imgCount > 1): ?>
                        <?php $sid = 'slider-' . $post['id']; ?>
                        <div class="slider swiss-border bg-black mb-4 relative select-none" id="<?= $sid ?>"
                             data-index="0" data-count="<?= $imgCount ?>">
                            <div class="relative w-full bg-black overflow-hidden" style="aspect-ratio: 4/3;">
                                <?php foreach ($images as $i => $src): ?>
                                    <img src="<?= htmlspecialchars($src) ?>"
                                         class="slide absolute inset-0 w-full h-full object-contain cursor-zoom-in <?= $i === 0 ? '' : 'hidden' ?>"
                                         data-idx="<?= $i ?>"
                                         onclick="openLightbox(<?= $post['id'] ?>, <?= $i ?>)"
                                         onerror="this.style.display='none';">
                                <?php endforeach; ?>
                            </div>
                            <button type="button" onclick="slidePrev('<?= $sid ?>')"
                                    class="absolute left-2 top-1/2 -translate-y-1/2 bg-white/90 hover:bg-yellow-400 text-black border-2 border-black font-black w-10 h-10 flex items-center justify-center shadow-[3px_3px_0_0_black]">◀</button>
                            <button type="button" onclick="slideNext('<?= $sid ?>')"
                                    class="absolute right-2 top-1/2 -translate-y-1/2 bg-white/90 hover:bg-yellow-400 text-black border-2 border-black font-black w-10 h-10 flex items-center justify-center shadow-[3px_3px_0_0_black]">▶</button>
                            <div class="absolute bottom-2 left-1/2 -translate-x-1/2 bg-black/80 text-white font-mono text-xs px-2 py-1 border-2 border-white">
                                <span class="counter">1</span> / <?= $imgCount ?>
                            </div>
                        </div>
                        <script type="application/json" id="imgs-<?= $post['id'] ?>"><?= json_encode($images, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) ?></script>
                    <?php endif; ?>

                    <?php if (!empty($ytubes)): ?>
                        <div class="space-y-3">
                            <?php foreach ($ytubes as $yt): ?>
                                <?php $embed = getYoutubeEmbed($yt); if (!$embed) continue; ?>
                                <div class="aspect-video w-full swiss-border bg-black">
                                    <iframe class="w-full h-full" src="<?= htmlspecialchars($embed) ?>" frameborder="0" allowfullscreen></iframe>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </article>
            <?php endforeach; ?>
        </div>
    </div>

    <div id="lightbox" class="fixed inset-0 z-[200] hidden bg-black/95 select-none">
        <button type="button" onclick="closeLightbox()"
                class="absolute top-4 right-4 z-10 bg-white text-black border-4 border-black font-black w-12 h-12 flex items-center justify-center hover:bg-yellow-400 shadow-[4px_4px_0_0_#facc15]">×</button>
        <div class="absolute top-4 left-1/2 -translate-x-1/2 bg-yellow-400 text-black border-4 border-black font-black font-mono px-3 py-1">
            <span id="lb-counter">1 / 1</span>
        </div>
        <div class="w-full h-full flex items-center justify-center p-4 md:p-12">
            <img id="lb-img" src="" class="max-w-full max-h-full object-contain border-4 border-white shadow-[8px_8px_0_0_#facc15]">
        </div>
        <button type="button" onclick="lbPrev()"
                class="absolute left-4 top-1/2 -translate-y-1/2 bg-white text-black border-4 border-black font-black w-14 h-14 flex items-center justify-center hover:bg-yellow-400 shadow-[4px_4px_0_0_#facc15] text-xl">◀</button>
        <button type="button" onclick="lbNext()"
                class="absolute right-4 top-1/2 -translate-y-1/2 bg-white text-black border-4 border-black font-black w-14 h-14 flex items-center justify-center hover:bg-yellow-400 shadow-[4px_4px_0_0_#facc15] text-xl">▶</button>
    </div>

    <script>
        // 선택한 파일 목록 미리보기
        document.getElementById('u_image_input').addEventListener('change', function(e) {
            const list = document.getElementById('file-list');
            list.innerHTML = '';
            Array.from(e.target.files).forEach(f => {
                const li = document.createElement('li');
                li.textContent = f.name + ' (' + Math.round(f.size/1024) + ' KB)';
                list.appendChild(li);
            });
        });

        // 게시글 내 인라인 슬라이더
        function showSlide(sid, idx) {
            const slider = document.getElementById(sid);
            if (!slider) return;
            const slides = slider.querySelectorAll('.slide');
            const count = slides.length;
            if (count === 0) return;
            idx = ((idx % count) + count) % count;
            slides.forEach((s, i) => s.classList.toggle('hidden', i !== idx));
            slider.dataset.index = idx;
            const counter = slider.querySelector('.counter');
            if (counter) counter.textContent = (idx + 1);
        }
        function slideNext(sid) {
            const slider = document.getElementById(sid);
            showSlide(sid, parseInt(slider.dataset.index || '0') + 1);
        }
        function slidePrev(sid) {
            const slider = document.getElementById(sid);
            showSlide(sid, parseInt(slider.dataset.index || '0') - 1);
        }

        // Lightbox (만화 뷰어처럼 풀스크린)
        let lbImgs = [];
        let lbIdx = 0;
        function openLightbox(postId, idx) {
            const node = document.getElementById('imgs-' + postId);
            if (!node) return;
            try { lbImgs = JSON.parse(node.textContent); } catch(e) { lbImgs = []; }
            if (!lbImgs.length) return;
            lbIdx = idx || 0;
            renderLb();
            document.getElementById('lightbox').classList.remove('hidden');
            document.body.style.overflow = 'hidden';
        }
        function closeLightbox() {
            document.getElementById('lightbox').classList.add('hidden');
            document.body.style.overflow = '';
        }
        function renderLb() {
            if (!lbImgs.length) return;
            lbIdx = ((lbIdx % lbImgs.length) + lbImgs.length) % lbImgs.length;
            document.getElementById('lb-img').src = lbImgs[lbIdx];
            document.getElementById('lb-counter').textContent = (lbIdx + 1) + ' / ' + lbImgs.length;
        }
        function lbNext() { lbIdx++; renderLb(); }
        function lbPrev() { lbIdx--; renderLb(); }

        // 키보드 ← → ESC 지원 (lightbox 열려 있을 때)
        document.addEventListener('keydown', function(e) {
            const lb = document.getElementById('lightbox');
            if (lb.classList.contains('hidden')) return;
            if (e.key === 'ArrowRight') lbNext();
            else if (e.key === 'ArrowLeft') lbPrev();
            else if (e.key === 'Escape') closeLightbox();
        });

        // 배경 클릭 시 닫기
        document.getElementById('lightbox').addEventListener('click', function(e) {
            if (e.target.id === 'lightbox') closeLightbox();
        });

        // 모바일 스와이프 (lightbox & 슬라이더)
        function attachSwipe(el, onLeft, onRight) {
            let sx = 0, sy = 0;
            el.addEventListener('touchstart', e => { sx = e.touches[0].clientX; sy = e.touches[0].clientY; }, {passive: true});
            el.addEventListener('touchend', e => {
                const dx = e.changedTouches[0].clientX - sx;
                const dy = e.changedTouches[0].clientY - sy;
                if (Math.abs(dx) > 50 && Math.abs(dx) > Math.abs(dy)) {
                    if (dx < 0) onLeft(); else onRight();
                }
            }, {passive: true});
        }
        attachSwipe(document.getElementById('lightbox'), lbNext, lbPrev);
        document.querySelectorAll('.slider').forEach(sl => {
            attachSwipe(sl, () => slideNext(sl.id), () => slidePrev(sl.id));
        });
    </script>
</body>
</html>
