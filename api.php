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

try {
    $pdo->beginTransaction();

    // 텍스트 설정 저장
    $stmt = $pdo->prepare("INSERT INTO settings (site_key, site_value) VALUES (?, ?) ON DUPLICATE KEY UPDATE site_value = ?");
    foreach ($_POST as $key => $value) {
        $stmt->execute([$key, $value, $value]);
    }

    // 파일 업로드 처리 함수
    function processUpload($fileKey, $dbKey, $uploadDir, $stmt) {
        if (isset($_FILES[$fileKey]) && $_FILES[$fileKey]['error'] === UPLOAD_ERR_OK) {
            $fileName = time() . '_' . basename($_FILES[$fileKey]['name']);
            $targetPath = $uploadDir . $fileName;
            if (move_uploaded_file($_FILES[$fileKey]['tmp_name'], $targetPath)) {
                $stmt->execute([$dbKey, $targetPath, $targetPath]);
                return $targetPath;
            }
        }
        return false;
    }

    // [추가됨] 로고 이미지 업로드 처리
    $newLogo = processUpload('logo_img_file', 'logo_img', $uploadDir, $stmt);
    
    // 기존 이미지 업로드 처리
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
    $pdo->rollBack();
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}
?>