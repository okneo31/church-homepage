# 동서울소망교회 홈페이지

PHP + MySQL 기반 교회 홈페이지.

## 구성

- `index.php` — 메인 페이지 (실시간 편집 사이드바 포함)
- `board.php` — 커뮤니티 게시판 (복수 사진/유튜브 업로드, 슬라이더 + 라이트박스 뷰어)
- `admin.php` — 관리자 설정 페이지
- `api.php` — 설정 저장 / 파일 업로드 JSON 엔드포인트
- `db.php` — DB 연결 (환경변수 사용)
- `uploads/` — 이미지 저장 디렉토리 (git에는 포함되지 않음)

## 실행

1. `.env.example`을 `.env`로 복사하고 DB 접속 정보를 입력합니다.
2. MySQL에 `board`, `settings`, `admins` 테이블을 준비합니다.
3. 웹서버(Apache/Nginx + PHP 8+)로 디렉토리를 서빙합니다.

## 보안

- DB 비밀번호는 절대 커밋하지 마세요. `.env`는 `.gitignore`에 포함되어 있습니다.
- 관리자 기본 비밀번호(`admin/1234`)는 운영 시 변경하세요.
