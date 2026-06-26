/* DepoPazar Service Worker – PWA + Web Push */
var CACHE_NAME = 'depopazar-v3';
var SHELL_URLS = [
  '/manifest.webmanifest',
  '/icons/icon-192.png',
  '/icons/icon-512.png',
];

self.addEventListener('install', function (event) {
  event.waitUntil(
    caches.open(CACHE_NAME).then(function (cache) {
      return cache.addAll(SHELL_URLS).catch(function () {});
    }).then(function () {
      return self.skipWaiting();
    })
  );
});

self.addEventListener('activate', function (event) {
  event.waitUntil(
    caches.keys().then(function (keys) {
      return Promise.all(
        keys.filter(function (k) { return k !== CACHE_NAME; }).map(function (k) { return caches.delete(k); })
      );
    }).then(function () {
      return self.clients.claim();
    })
  );
});

function isHtmlNavigation(request) {
  if (request.mode === 'navigate') return true;
  var accept = request.headers.get('accept') || '';
  return accept.indexOf('text/html') !== -1;
}

self.addEventListener('fetch', function (event) {
  if (event.request.method !== 'GET') return;
  var url = new URL(event.request.url);
  if (url.origin !== self.location.origin) return;
  if (url.pathname.indexOf('/api/') === 0) return;

  /* Dinamik sayfalar (liste, sayfalama) önbelleğe alınmaz — eski sayfa gösterilmesin */
  if (isHtmlNavigation(event.request)) {
    event.respondWith(fetch(event.request));
    return;
  }

  event.respondWith(
    fetch(event.request).then(function (response) {
      if (response && response.status === 200 && response.type === 'basic') {
        var clone = response.clone();
        caches.open(CACHE_NAME).then(function (cache) {
          cache.put(event.request, clone);
        });
      }
      return response;
    }).catch(function () {
      return caches.match(event.request);
    })
  );
});

self.addEventListener('push', function (event) {
  if (!event.data) return;
  var data = {};
  try {
    data = event.data.json();
  } catch (e) {
    data = { title: 'Bildirim', body: event.data.text() || 'Yeni bildirim' };
  }
  var title = data.title || 'DepoPazar';
  var options = {
    body: data.body || '',
    icon: data.icon || '/icons/icon-192.png',
    badge: data.badge || '/icons/icon-192.png',
    tag: data.tag || ('depopazar-' + Date.now()),
    renotify: true,
    requireInteraction: false,
    data: { url: data.url || '/bildirimler' },
  };
  event.waitUntil(self.registration.showNotification(title, options));
});

self.addEventListener('notificationclick', function (event) {
  event.notification.close();
  var url = (event.notification.data && event.notification.data.url) ? event.notification.data.url : '/bildirimler';
  event.waitUntil(
    clients.matchAll({ type: 'window', includeUncontrolled: true }).then(function (clientList) {
      for (var i = 0; i < clientList.length; i++) {
        var client = clientList[i];
        if ('focus' in client) {
          if ('navigate' in client) {
            return client.navigate(url).then(function () { return client.focus(); });
          }
          client.focus();
          return;
        }
      }
      if (clients.openWindow) return clients.openWindow(url);
    })
  );
});
