/**
 * 120 Stand Inventory - Service Worker
 * Enables offline functionality
 * Version: 1.0.0
 */

// Cache version - update this when deploying new versions
const CACHE_VERSION = '1.0.0';
const CACHE_NAME = 'stand120-v' + CACHE_VERSION;
const OFFLINE_URL = '/120-stand/offline/';

// Files to cache
const CACHE_FILES = [
    '/120-stand/',
    '/120-stand/login/',
    '/wp-content/plugins/120-stand-inventory/assets/css/style.css',
    '/wp-content/plugins/120-stand-inventory/assets/js/main.js',
    '/wp-content/plugins/120-stand-inventory/assets/images/logo.png'
];

// Install event
self.addEventListener('install', (event) => {
    event.waitUntil(
        caches.open(CACHE_NAME).then((cache) => {
            return cache.addAll(CACHE_FILES).catch((error) => {
                console.log('Cache addAll error:', error);
            });
        })
    );
    self.skipWaiting();
});

// Activate event
self.addEventListener('activate', (event) => {
    event.waitUntil(
        caches.keys().then((cacheNames) => {
            return Promise.all(
                cacheNames.map((cacheName) => {
                    if (cacheName !== CACHE_NAME) {
                        return caches.delete(cacheName);
                    }
                })
            );
        })
    );
    self.clients.claim();
});

// Fetch event
self.addEventListener('fetch', (event) => {
    const { request } = event;
    const url = new URL(request.url);
    
    // Skip non-GET requests
    if (request.method !== 'GET') {
        return;
    }
    
    // Skip admin-ajax.php for offline data handling
    if (url.pathname.includes('admin-ajax.php')) {
        event.respondWith(
            fetch(request).catch(() => {
                // Return a JSON response indicating offline
                return new Response(
                    JSON.stringify({
                        success: false,
                        offline: true,
                        message: 'You are offline. Data will sync when you reconnect.'
                    }),
                    {
                        headers: { 'Content-Type': 'application/json' }
                    }
                );
            })
        );
        return;
    }
    
    // Network-first strategy for HTML pages
    if (request.headers.get('accept').includes('text/html')) {
        event.respondWith(
            fetch(request)
                .then((response) => {
                    const responseClone = response.clone();
                    caches.open(CACHE_NAME).then((cache) => {
                        cache.put(request, responseClone);
                    });
                    return response;
                })
                .catch(() => {
                    return caches.match(request).then((cachedResponse) => {
                        if (cachedResponse) {
                            return cachedResponse;
                        }
                        // Return offline page if available
                        return caches.match(OFFLINE_URL);
                    });
                })
        );
        return;
    }
    
    // Cache-first strategy for assets
    event.respondWith(
        caches.match(request).then((cachedResponse) => {
            if (cachedResponse) {
                // Return cached version and update cache in background
                fetch(request).then((response) => {
                    caches.open(CACHE_NAME).then((cache) => {
                        cache.put(request, response);
                    });
                }).catch(() => {});
                return cachedResponse;
            }
            
            return fetch(request).then((response) => {
                // Cache the new resource
                const responseClone = response.clone();
                caches.open(CACHE_NAME).then((cache) => {
                    cache.put(request, responseClone);
                });
                return response;
            });
        })
    );
});

// Background sync for offline data
self.addEventListener('sync', (event) => {
    if (event.tag === 'sync-offline-data') {
        event.waitUntil(syncOfflineData());
    }
});

// Sync offline data function
async function syncOfflineData() {
    // Get offline data from IndexedDB or localStorage
    // This would be called when connection is restored
    console.log('Syncing offline data...');
}

// Push notification handling (for future use)
self.addEventListener('push', (event) => {
    if (event.data) {
        const data = event.data.json();
        self.registration.showNotification(data.title, {
            body: data.body,
            icon: '/wp-content/plugins/120-stand-inventory/assets/images/logo.png',
            badge: '/wp-content/plugins/120-stand-inventory/assets/images/logo.png'
        });
    }
});
