<?php
// api.php
session_start();
include 'db.php';

header('Content-Type: application/json');

if (!isset($_SESSION['admin'])) {
    echo json_encode(['status' => 'error', 'message' => '권한이 없습니다.']);
    exit;
}

$uploadDir = 'uploads/';
if (!is_dir($uploadDir)) {
    mkdir($uploadDir, 0777, true);
}

$action = $_POST['action'] ?? $_GET['action'] ?? 'save_settings';

try {
    if ($action === 'delete_board') {
        $id = (int)($_POST['id'] ?? 0);
        if ($id <= 0) throw new Exception('잘못된 게시글 ID');

        // 첨부 파일 먼저 삭제
        $stmt = $pdo->prepare("SELECT image_file FROM board WHERE id = ?");
        $stmt->execute([$id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($row && !empty($row['image_file'])) {
            $files = preg_split('/\r\n|\r|\n/', $row['image_file']);
            foreach ($files as $f) {
                $f = trim($f);
                if ($f === '') continue;
                $path = $uploadDir . basename($f);
                if (is_file($path)) @unlink($path);
            }
        }

        $stmt = $pdo->prepare("DELETE FROM board WHERE id = ?");
        $stmt->execute([$id]);

        echo json_encode(['status' => 'success', 'deleted_id' => $id]);
        exit;
    }

    // 기본: settings 저장 + 파일 업로드
    $pdo->beginTransaction();

    $stmt = $pdo->prepare("INSERT INTO settings (site_key, site_value) VALUES (?, ?) ON DUPLICATE KEY UPDATE site_value = ?");
    foreach ($_POST as $key => $value) {
        if ($key === 'action') continue;
        $stmt->execute([$key, $value, $value]);
    }

    function processUpload($fileKey, $dbKey, $uploadDir, $stmt) {
        if (isset($_FILES[$fileKey]) && $_FILES[$fileKey]['error'] === UPLOAD_ERR_OK) {
            $safe = preg_replace('/[^A-Za-z0-9._-]/', '_', basename($_FILES[$fileKey]['name']));
            $fileName = time() . '_' . $safe;
            $targetPath = $uploadDir . $fileName;
            if (move_uploaded_file($_FILES[$fileKey]['tmp_name'], $targetPath)) {
                $stmt->execute([$dbKey, $targetPath, $targetPath]);
                return $targetPath;
            }
        }
        return false;
    }

    $newLogo = processUpload('logo_img_file', 'logo_img', $uploadDir, $stmt);
    $newHeroImg = processUpload('hero_img_file', 'hero_img', $uploadDir, $stmt);
    $newNoticeImg = processUpload('notice1_img_file', 'notice1_img', $uploadDir, $stmt);

    $pdo->commit();

    echo json_encode([
        'status' => 'success',
        'new_logo' => $newLogo,
        'new_hero_img' => $newHeroImg,
        'new_notice1_img' => $newNoticeImg
    ]);

} catch (Exception $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}
