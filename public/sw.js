// Service Worker untuk Sistem CBT — minimal cache + offline fallback untuk halaman statis.
// Strategi: network-first untuk navigasi, cache-first untuk asset statis.

const CACHE_NAME = 'cbt-v1';
const STATIC_ASSETS = [
    '/',
    '/manifest.json',
    'https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css',
    'https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css',
    'https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js',
];

self.addEventListener('install', (event) => {
    event.waitUntil(
        caches.open(CACHE_NAME).then((cache) => cache.addAll(STATIC_ASSETS).catch(() => null))
    );
    self.skipWaiting();
});

self.addEventListener('activate', (event) => {
    event.waitUntil(
        caches.keys().then((keys) =>
            Promise.all(keys.filter((k) => k !== CACHE_NAME).map((k) => caches.delete(k)))
        )
    );
    self.clients.claim();
});

self.addEventListener('fetch', (event) => {
    const req = event.request;
    if (req.method !== 'GET') return;

    // Jangan cache endpoint CBT yang butuh session/state (ujian, koreksi, dll)
    const url = new URL(req.url);
    if (
        url.pathname.startsWith('/student/exam') ||
        url.pathname.startsWith('/admin/grading') ||
        url.pathname.startsWith('/admin/analysis') ||
        url.pathname.includes('/api/')
    ) {
        return; // biarkan network handle
    }

    // Network-first untuk HTML
    if (req.headers.get('accept')?.includes('text/html')) {
        event.respondWith(
            fetch(req)
                .then((res) => {
                    const clone = res.clone();
                    caches.open(CACHE_NAME).then((c) => c.put(req, clone));
                    return res;
                })
                .catch(() => caches.match(req).then((r) => r || caches.match('/')))
        );
        return;
    }

    // Cache-first untuk asset
    event.respondWith(
        caches.match(req).then((cached) => cached || fetch(req).then((res) => {
            if (res.status === 200) {
                const clone = res.clone();
                caches.open(CACHE_NAME).then((c) => c.put(req, clone));
            }
            return res;
        }))
    );
});
