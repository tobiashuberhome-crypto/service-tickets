// Minimaler Service Worker: Die App ist installierbar, aber es wird bewusst NICHTS gecacht.
// Jede Seite kommt immer frisch vom Server (Tickets und Rechnungen duerfen nie veraltet sein,
// und ein alter Cache nach einem Deploy waere genau der Fehler, den wir vermeiden wollen).
self.addEventListener('install', () => self.skipWaiting());
self.addEventListener('activate', (event) => event.waitUntil(
    caches.keys().then((keys) => Promise.all(keys.map((key) => caches.delete(key)))).then(() => self.clients.claim())
));
self.addEventListener('fetch', () => {
    // Kein respondWith: der Browser behandelt die Anfrage ganz normal.
});
