// Cloudflare Worker — 페이스북/SNS 봇이 호스팅 차단당하는 문제 우회
// 봇은 Worker가 즉시 OG HTML로 응답. 일반 사용자는 origin으로 그대로 전달.
//
// 배포: Cloudflare 대시보드 → Workers & Pages → Create Worker → 이 코드 붙여넣기 → Deploy
// 라우트: somang.ttl1.top/* → 이 Worker

const OG_TITLE       = '승리하는 교회 - 동서울소망교회';
const OG_DESC        = '세상을 이기는 믿음! 영적 전쟁에서 승리하는 십자가의 능력을 체험하세요. (서울 중랑구 겸재로 154)';
const OG_IMAGE_URL   = 'https://somang.ttl1.top/og-image.png';
const OG_IMAGE_WIDTH = '1200';
const OG_IMAGE_HEIGHT= '630';
const OG_SITE_NAME   = '동서울소망교회';

// SNS / 메신저 OG 스크래퍼 봇 UA 패턴
const BOT_PATTERN = /facebookexternalhit|Facebot|Twitterbot|LinkedInBot|Slackbot|Discordbot|WhatsApp|TelegramBot|KakaoTalk|Daum|Pinterest|Bingbot|Googlebot/i;

export default {
    async fetch(request, env, ctx) {
        const url = new URL(request.url);
        const ua  = request.headers.get('user-agent') || '';
        const isBot = BOT_PATTERN.test(ua);

        // OG 이미지 자체에 대한 봇 요청 → origin으로 패스 (PNG 받아야 함)
        // 일반 사용자 → 그대로 origin
        if (!isBot || url.pathname.startsWith('/og-image') || url.pathname.startsWith('/uploads/')) {
            return fetch(request);
        }

        // 봇이 루트나 게시판 등 페이지 요청 → 정적 OG HTML 즉시 응답
        const canonical = `${url.origin}${url.pathname}`;
        const ogImageWithVer = `${OG_IMAGE_URL}?v=${Date.now()}`;

        const html = `<!DOCTYPE html>
<html lang="ko">
<head>
<meta charset="UTF-8">
<title>${escapeHtml(OG_TITLE)}</title>
<meta name="description" content="${escapeHtml(OG_DESC)}">
<link rel="canonical" href="${canonical}">

<meta property="og:type" content="website">
<meta property="og:locale" content="ko_KR">
<meta property="og:site_name" content="${escapeHtml(OG_SITE_NAME)}">
<meta property="og:url" content="${canonical}">
<meta property="og:title" content="${escapeHtml(OG_TITLE)}">
<meta property="og:description" content="${escapeHtml(OG_DESC)}">
<meta property="og:image" content="${ogImageWithVer}">
<meta property="og:image:secure_url" content="${ogImageWithVer}">
<meta property="og:image:type" content="image/png">
<meta property="og:image:width" content="${OG_IMAGE_WIDTH}">
<meta property="og:image:height" content="${OG_IMAGE_HEIGHT}">
<meta property="og:image:alt" content="${escapeHtml(OG_SITE_NAME)}">

<meta name="twitter:card" content="summary_large_image">
<meta name="twitter:title" content="${escapeHtml(OG_TITLE)}">
<meta name="twitter:description" content="${escapeHtml(OG_DESC)}">
<meta name="twitter:image" content="${ogImageWithVer}">
</head>
<body>
<h1>${escapeHtml(OG_TITLE)}</h1>
<p>${escapeHtml(OG_DESC)}</p>
<img src="${ogImageWithVer}" alt="${escapeHtml(OG_SITE_NAME)}" width="1200" height="630">
</body>
</html>`;

        return new Response(html, {
            status: 200,
            headers: {
                'Content-Type': 'text/html; charset=UTF-8',
                'Cache-Control': 'public, max-age=3600',
                'X-Robots-Tag': 'all',
                'X-OG-Source': 'cloudflare-worker'
            }
        });
    }
};

function escapeHtml(s) {
    return String(s)
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#039;');
}
