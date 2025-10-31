self.addEventListener('install', (event) => {
  console.log('Service worker installed');
  self.skipWaiting();
});

self.addEventListener('activate', (event) => {
  console.log('Service worker activated');
});

self.addEventListener('fetch', (event) => {
  // For now, just pass through the request
  event.respondWith(fetch(event.request));
});