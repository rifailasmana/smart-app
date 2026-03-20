const CACHE_NAME = 'terminal-v4';

const STATIC_ASSETS = [
    '/manifest.json'
];

self.addEventListener('install', (e) => {
    self.skipWaiting();
    e.waitUntil(
        caches.open(CACHE_NAME).then((cache) => cache.addAll(STATIC_ASSETS)).catch(() => undefined)
    );
});

self.addEventListener('activate', (e) => {
    e.waitUntil(
        (async () => {
            const keys = await caches.keys();
            await Promise.all(keys.filter((k) => k !== CACHE_NAME).map((k) => caches.delete(k)));
            await self.clients.claim();
        })()
    );
});

self.addEventListener('fetch', (e) => {
    const req = e.request;
    if (req.method !== 'GET') return;

    const url = new URL(req.url);

    if (req.mode === 'navigate' && url.origin === self.location.origin && url.pathname.startsWith('/terminal')) {
        e.respondWith(
            (async () => {
                try {
                    const res = await fetch(req);
                    const cache = await caches.open(CACHE_NAME);
                    cache.put(req, res.clone());
                    return res;
                } catch {
                    const cached = await caches.match(req);
                    if (cached) return cached;
                    return new Response(
                        '<!doctype html><html lang="id"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Offline</title></head><body style="font-family:system-ui,Segoe UI,Arial,sans-serif;margin:0;display:flex;min-height:100vh;align-items:center;justify-content:center;background:#F8FAFC;color:#1E293B;"><div style="max-width:520px;padding:24px;text-align:center;"><div style="font-weight:800;font-size:20px;">Terminal sedang offline</div><div style="margin-top:8px;opacity:.75;font-size:14px;line-height:1.4;">Koneksi internet dibutuhkan untuk memuat asset terminal. Coba nyalakan internet lalu muat ulang.</div><div style="margin-top:16px;"><button onclick="location.reload()" style="padding:10px 14px;border-radius:12px;border:1px solid rgba(255,140,0,.35);background:#fff;font-weight:700;">Muat Ulang</button></div></div></body></html>',
                        { headers: { 'Content-Type': 'text/html; charset=utf-8' } }
                    );
                }
            })()
        );
        return;
    }

    const cdnHosts = new Set([
        'cdn.tailwindcss.com',
        'unpkg.com',
        'cdn.jsdelivr.net',
        'fonts.googleapis.com',
        'fonts.gstatic.com',
        'cdn-icons-png.flaticon.com'
    ]);

    if (cdnHosts.has(url.hostname)) {
        e.respondWith(
            (async () => {
                const cache = await caches.open(CACHE_NAME);
                const cached = await cache.match(req);
                const fetchPromise = fetch(req)
                    .then((res) => {
                        cache.put(req, res.clone());
                        return res;
                    })
                    .catch(() => undefined);
                return cached || (await fetchPromise) || fetch(req);
            })()
        );
        return;
    }

    if (url.origin === self.location.origin) {
        e.respondWith(
            (async () => {
                const cache = await caches.open(CACHE_NAME);
                const cached = await cache.match(req);
                if (cached) return cached;
                const res = await fetch(req);
                if (url.pathname.startsWith('/css/') || url.pathname.startsWith('/js/') || url.pathname.startsWith('/build/')) {
                    cache.put(req, res.clone());
                }
                return res;
            })()
        );
    }
});
