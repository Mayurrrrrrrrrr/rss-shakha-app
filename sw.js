const CACHE_NAME = 'sanghasthan-static-v1';
const API_CACHE_NAME = 'sanghasthan-api-v1';

const STATIC_ASSETS = [
  '/',
  '/login.php',
  '/manifest.json',
  '/assets/images/favicon.png',
  '/assets/images/logo.svg',
  '/assets/images/flag_icon.png'
];

// Install Event
self.addEventListener('install', event => {
  event.waitUntil(
    caches.open(CACHE_NAME).then(cache => {
      return cache.addAll(STATIC_ASSETS);
    }).then(() => self.skipWaiting())
  );
});

// Activate Event
self.addEventListener('activate', event => {
  event.waitUntil(
    caches.keys().then(cacheNames => {
      return Promise.all(
        cacheNames.map(cache => {
          if (cache !== CACHE_NAME && cache !== API_CACHE_NAME) {
            return caches.delete(cache);
          }
        })
      );
    }).then(() => self.clients.claim())
  );
});

// Fetch Event
self.addEventListener('fetch', event => {
  const url = new URL(event.request.url);

  // 1. Static Assets - Stale-While-Revalidate
  if (url.pathname.includes('/assets/') || url.pathname.match(/\.(css|js|woff2?|png|jpe?g|gif|svg|ico)$/)) {
    event.respondWith(
      caches.open(CACHE_NAME).then(cache => {
        return cache.match(event.request).then(cachedResponse => {
          const fetchPromise = fetch(event.request).then(networkResponse => {
            if (networkResponse.status === 200) {
              cache.put(event.request, networkResponse.clone());
            }
            return networkResponse;
          }).catch(() => {
            // Ignore network failures for Stale-While-Revalidate background fetch
          });
          return cachedResponse || fetchPromise;
        });
      })
    );
    return;
  }

  // 2. API Requests - Network-First, fallback to Cache
  if (url.pathname.includes('/api/')) {
    event.respondWith(
      fetch(event.request)
        .then(networkResponse => {
          // Cache successful GET requests
          if (event.request.method === 'GET' && networkResponse.status === 200) {
            const responseClone = networkResponse.clone();
            caches.open(API_CACHE_NAME).then(cache => {
              cache.put(event.request, responseClone);
            });
          }
          return networkResponse;
        })
        .catch(() => {
          // Network failed, try cache
          if (event.request.method === 'GET') {
            return caches.match(event.request).then(cachedResponse => {
              if (cachedResponse) {
                return cachedResponse;
              }
              // Cache also failed/empty, return custom offline JSON
              return new Response(JSON.stringify({
                success: false,
                offline: true,
                message: "You are currently offline."
              }), {
                status: 200,
                headers: { 'Content-Type': 'application/json; charset=UTF-8' }
              });
            });
          } else {
            // For POST/PUT etc. where cache is not possible, return offline JSON directly
            return new Response(JSON.stringify({
              success: false,
              offline: true,
              message: "You are currently offline."
            }), {
              status: 200,
              headers: { 'Content-Type': 'application/json; charset=UTF-8' }
            });
          }
        })
    );
    return;
  }

  // 3. Default Strategy: Network First
  event.respondWith(
    fetch(event.request).catch(() => caches.match(event.request))
  );
});
