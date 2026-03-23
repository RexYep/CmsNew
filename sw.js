// ============================================
// SERVICE WORKER - complaint-management-system
// sw.js (ilagay sa ROOT ng project)
// FIX: "Response body is already used" error
// ============================================

const CACHE_NAME = "cms-cache-v2"; // bumago para ma-force refresh

const STATIC_ASSETS = [
  "https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css",
  "https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css",
  "https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js",
];

// ============================================
// INSTALL
// ============================================
self.addEventListener("install", (event) => {
  event.waitUntil(
    caches.open(CACHE_NAME).then((cache) => cache.addAll(STATIC_ASSETS)),
  );
  self.skipWaiting();
});

// ============================================
// ACTIVATE — linisin ang lumang cache
// ============================================
self.addEventListener("activate", (event) => {
  event.waitUntil(
    caches
      .keys()
      .then((cacheNames) =>
        Promise.all(
          cacheNames
            .filter((name) => name !== CACHE_NAME)
            .map((name) => caches.delete(name)),
        ),
      ),
  );
  self.clients.claim();
});

// ============================================
// FETCH
// FIX: I-clone ang response AGAD bago gamitin
// ============================================
self.addEventListener("fetch", (event) => {
  if (event.request.method !== "GET") return;

  const url = new URL(event.request.url);

  // CDN assets → Cache First
  if (
    url.hostname.includes("cdn.jsdelivr.net") ||
    url.hostname.includes("cdnjs.cloudflare.com")
  ) {
    event.respondWith(
      caches.match(event.request).then((cached) => {
        if (cached) return cached; // HIT — return agad

        // MISS — fetch tapos i-cache
        return fetch(event.request).then((response) => {
          // ✅ FIX: i-clone AGAD bago gamitin ang response
          const toCache = response.clone();
          caches
            .open(CACHE_NAME)
            .then((cache) => cache.put(event.request, toCache));
          return response;
        });
      }),
    );
    return;
  }

  // Local CSS, JS, Images → Cache First + background update
  if (
    url.pathname.match(/\.(css|js|png|jpg|jpeg|gif|ico|svg|webp|woff2?|ttf)$/)
  ) {
    event.respondWith(
      caches.match(event.request).then((cached) => {
        // ✅ FIX: fetch at i-clone AGAD — huwag hintayin na magamit ang response
        const networkFetch = fetch(event.request)
          .then((response) => {
            const toCache = response.clone(); // ← clone MUNA bago ibalik
            caches
              .open(CACHE_NAME)
              .then((cache) => cache.put(event.request, toCache));
            return response;
          })
          .catch(() => cached); // fallback sa cache kung walang network

        return cached || networkFetch;
      }),
    );
    return;
  }

  // PHP pages → Network only (hindi na-cache para laging fresh ang data)
});
