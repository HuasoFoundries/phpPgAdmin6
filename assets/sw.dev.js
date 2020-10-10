self.addEventListener('install', function (/*event*/) {
    // The promise that skipWaiting() returns can be safely ignored.
    self.skipWaiting();
  
    // Perform any other actions required for your
    // service worker to install, potentially inside
    // of event.waitUntil();
  });
  self.addEventListener('activate', (event) => {
    event.waitUntil(clients.claim());
  });
  if (typeof workbox === 'undefined') {
    importScripts(
        'https://storage.googleapis.com/workbox-cdn/releases/5.1.2/workbox-sw.js'
    );
    workbox.loadModule('workbox-strategies');
    workbox.loadModule('workbox-cacheable-response');
    workbox.loadModule('workbox-expiration');
  }
  self.__precacheManifest = [].concat(self.__precacheManifest || []);
  if (typeof workbox !== 'undefined' && workbox) {
  
  workbox.core.skipWaiting();
  
  workbox.core.clientsClaim();
  
  workbox.precaching.precacheAndRoute(self.__WB_MANIFEST);
  
    console.log(`Yay! Workbox is loaded 🎉`);
  
    workbox.routing.registerRoute(
      /\/assets\/css/,
      new workbox.strategies.CacheFirst({
        cacheName: "vendor-local-css",
        plugins: [ new workbox.cacheableResponse.CacheableResponse({ statuses: [0, 200] })],
      })
    );
    workbox.routing.registerRoute(
      /\/assets\/js/,
      new workbox.strategies.CacheFirst({
        cacheName: "vendor-local-js",
        plugins: [ new workbox.cacheableResponse.CacheableResponse({ statuses: [0, 200] })],
      })
    );
    workbox.routing.registerRoute(
      /\/img/,
      new workbox.strategies.CacheFirst({
        cacheName: "image-files",
        plugins: [ new workbox.cacheableResponse.CacheableResponse({ statuses: [0, 200] })],
  
      })
    );
  
    // Cache the Google Fonts stylesheets with a stale-while-revalidate strategy.
    workbox.routing.registerRoute(
      /^https:\/\/fonts\.googleapis\.com/,
     new workbox.strategies.StaleWhileRevalidate({
        cacheName: "google-fonts-stylesheets"
      })
    );
  
    // Cache the underlying font files with a cache-first strategy for 1 year.
    workbox.routing.registerRoute(
      /^https:\/\/fonts\.gstatic\.com/,
      new workbox.strategies.CacheFirst({
        cacheName: "google-fonts-webfonts",
        plugins: [ new workbox.cacheableResponse.CacheableResponse({ statuses: [0, 200] })],
      })
    );
  } else {
    console.log(`Boo! Workbox didn't load 😬`);
  }
  