const CACHE_NAME = 'sanghasthan-cache-v1';
const DYNAMIC_CACHE = 'sanghasthan-dynamic-v1';

const STATIC_ASSETS = [
    '/manifest.json',
    '/offline.php',
    '/assets/css/home.css',
    '/assets/css/public-content.css',
    '/assets/images/favicon.png',
    '/assets/images/flag_icon.png'
];

// Install event: Cache essential static assets
self.addEventListener('install', event => {
    event.waitUntil(
        caches.open(CACHE_NAME)
            .then(cache => {
                console.log('[Service Worker] Caching static assets');
                return cache.addAll(STATIC_ASSETS);
            })
            .then(() => self.skipWaiting())
    );
});

// Activate event: Clean up old caches
self.addEventListener('activate', event => {
    event.waitUntil(
        caches.keys().then(cacheNames => {
            return Promise.all(
                cacheNames.map(cache => {
                    if (cache !== CACHE_NAME && cache !== DYNAMIC_CACHE) {
                        console.log('[Service Worker] Deleting old cache:', cache);
                        return caches.delete(cache);
                    }
                })
            );
        }).then(() => self.clients.claim())
    );
});

// Fetch event: Network-first for pages, Cache-first for assets
self.addEventListener('fetch', event => {
    // Only intercept GET requests
    if (event.request.method !== 'GET') return;

    const url = new URL(event.request.url);

    // Skip cross-origin requests
    if (url.origin !== location.origin) return;

    // For HTML Navigation Requests (Network First, fallback to Cache, fallback to Offline page)
    if (event.request.mode === 'navigate') {
        event.respondWith(
            fetch(event.request)
                .then(response => {
                    return caches.open(DYNAMIC_CACHE).then(cache => {
                        cache.put(event.request.url, response.clone());
                        return response;
                    });
                })
                .catch(() => {
                    return caches.match(event.request).then(cachedResponse => {
                        return cachedResponse || caches.match('/offline.php');
                    });
                })
        );
        return;
    }

    // For Static Assets (Cache First, fallback to Network)
    event.respondWith(
        caches.match(event.request).then(cachedResponse => {
            if (cachedResponse) {
                return cachedResponse;
            }
            return fetch(event.request).then(networkResponse => {
                if (!networkResponse || networkResponse.status !== 200 || networkResponse.type !== 'basic') {
                    return networkResponse;
                }
                const responseToCache = networkResponse.clone();
                caches.open(DYNAMIC_CACHE).then(cache => {
                    cache.put(event.request, responseToCache);
                });
                return networkResponse;
            }).catch(() => {
                return new Response('', { status: 408, statusText: 'Request timeout' });
            });
        })
    );
});
