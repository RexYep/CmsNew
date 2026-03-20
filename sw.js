// ============================================
// SERVICE WORKER - complaint-management-system
// sw.js (ilagay sa ROOT ng project)
// ============================================

const CACHE_NAME = "cms-cache-v1";

// Static assets na i-ca-cache (CDN + local)
const STATIC_ASSETS = [
  "https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css",
  "https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css",
  "https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js",
];

// ============================================
// INSTALL - i-cache ang static assets
// ============================================
self.addEventListener("install", (event) => {
  event.waitUntil(
    caches.open(CACHE_NAME).then((cache) => {
      return cache.addAll(STATIC_ASSETS);
    }),
  );
  self.skipWaiting();
});

// ============================================
// ACTIVATE - linisin ang lumang cache
// ============================================
self.addEventListener("activate", (event) => {
  event.waitUntil(
    caches.keys().then((cacheNames) => {
      return Promise.all(
        cacheNames
          .filter((name) => name !== CACHE_NAME)
          .map((name) => caches.delete(name)),
      );
    }),
  );
  self.clients.claim();
});

// ============================================
// FETCH - Cache Strategy:
//   CDN assets    → Cache First (basta may cache, gamitin agad)
//   Local CSS/JS  → Cache First (may network fallback)
//   PHP pages     → Network First (always fresh data)
// ============================================
self.addEventListener("fetch", (event) => {
  const url = new URL(event.request.url);

  // Huwag i-cache ang non-GET requests (POST, etc.)
  if (event.request.method !== "GET") return;

  // CDN assets → Cache First
  if (
    url.hostname.includes("cdn.jsdelivr.net") ||
    url.hostname.includes("cdnjs.cloudflare.com")
  ) {
    event.respondWith(
      caches.match(event.request).then((cached) => {
        return (
          cached ||
          fetch(event.request).then((response) => {
            const cloned = response.clone();
            caches
              .open(CACHE_NAME)
              .then((cache) => cache.put(event.request, cloned));
            return response;
          })
        );
      }),
    );
    return;
  }

  // Local CSS, JS, Images → Cache First + network update
  if (
    url.pathname.match(/\.(css|js|png|jpg|jpeg|gif|ico|svg|webp|woff2?|ttf)$/)
  ) {
    event.respondWith(
      caches.match(event.request).then((cached) => {
        const networkFetch = fetch(event.request).then((response) => {
          caches
            .open(CACHE_NAME)
            .then((cache) => cache.put(event.request, response.clone()));
          return response;
        });
        return cached || networkFetch;
      }),
    );
    return;
  }

  // PHP Pages → Network First (para laging fresh ang data)
  // Walang caching para sa PHP pages
});
