/* Service worker for desktop notifications + web push (Winning Heaven) */
self.addEventListener('install', (event) => {
  self.skipWaiting();
});
self.addEventListener('activate', (event) => {
  event.waitUntil(self.clients.claim());
});

self.addEventListener('push', (event) => {
  let data = {};
  try {
    data = event.data ? event.data.json() : {};
  } catch (_) {
    try { data = { body: event.data && event.data.text() }; } catch (__) {}
  }
  const title = data.title || 'Winning Heaven';
  const opts = {
    body: data.body || data.message || 'New activity',
    icon: data.icon || '/brand/logo.png',
    badge: data.badge || '/brand/logo.png',
    tag: data.tag || 'wh-push',
    renotify: true,
    data: { url: data.url || data.link || '/lobby' },
  };
  event.waitUntil(self.registration.showNotification(title, opts));
});

self.addEventListener('notificationclick', (event) => {
  event.notification.close();
  const target = (event.notification.data && event.notification.data.url) || '/lobby';
  event.waitUntil(
    self.clients.matchAll({ type: 'window', includeUncontrolled: true }).then((clientList) => {
      for (const client of clientList) {
        if ('focus' in client) {
          try {
            if (typeof client.navigate === 'function') client.navigate(target);
          } catch (_) {}
          return client.focus();
        }
      }
      if (self.clients.openWindow) return self.clients.openWindow(target);
    })
  );
});
