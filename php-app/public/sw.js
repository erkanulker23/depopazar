/* DepoPazar Web Push Service Worker */
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
    icon: data.icon || '/favicon.ico',
    badge: data.icon || '/favicon.ico',
    tag: 'depopazar-' + Date.now(),
    requireInteraction: false,
    data: { url: data.url || '/' }
  };
  event.waitUntil(self.registration.showNotification(title, options));
});

self.addEventListener('notificationclick', function (event) {
  event.notification.close();
  var url = event.notification.data && event.notification.data.url ? event.notification.data.url : '/';
  event.waitUntil(
    clients.matchAll({ type: 'window', includeUncontrolled: true }).then(function (clientList) {
      for (var i = 0; i < clientList.length; i++) {
        if (clientList[i].url && 'focus' in clientList[i]) {
          clientList[i].navigate(url);
          return clientList[i].focus();
        }
      }
      if (clients.openWindow) return clients.openWindow(url);
    })
  );
});
