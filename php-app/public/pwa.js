/* DepoPazar PWA: kurulum + Web Push abonelik */
(function () {
  'use strict';

  var body = document.body;
  if (!body || body.getAttribute('data-auth') !== '1') return;

  var SW_URL = '/sw.js';
  var SW_SCOPE = '/';

  function urlBase64ToUint8Array(base64String) {
    var padding = '='.repeat((4 - (base64String.length % 4)) % 4);
    var base64 = (base64String + padding).replace(/-/g, '+').replace(/_/g, '/');
    var raw = window.atob(base64);
    var arr = new Uint8Array(raw.length);
    for (var i = 0; i < raw.length; ++i) arr[i] = raw.charCodeAt(i);
    return arr;
  }

  function isStandalone() {
    return window.matchMedia('(display-mode: standalone)').matches || window.navigator.standalone === true;
  }

  function isIos() {
    return /iphone|ipad|ipod/i.test(navigator.userAgent);
  }

  function hide(el) {
    if (el) el.classList.add('hidden');
  }

  function registerServiceWorker() {
    if (!('serviceWorker' in navigator)) return Promise.resolve(null);
    return navigator.serviceWorker.register(SW_URL, { scope: SW_SCOPE }).catch(function (err) {
      console.warn('SW kayit hatasi:', err);
      return null;
    });
  }

  function subscribePush(registration) {
    if (!registration || !registration.pushManager) return Promise.resolve(false);
    return fetch('/api/push-vapid-public', { credentials: 'same-origin' })
      .then(function (r) { return r.json(); })
      .then(function (data) {
        if (!data || !data.publicKey) return false;
        return registration.pushManager.subscribe({
          userVisibleOnly: true,
          applicationServerKey: urlBase64ToUint8Array(data.publicKey),
        });
      })
      .then(function (subscription) {
        if (!subscription) return false;
        return fetch('/api/push-subscribe', {
          method: 'POST',
          credentials: 'same-origin',
          headers: { 'Content-Type': 'application/json' },
          body: JSON.stringify({ subscription: subscription.toJSON() }),
        }).then(function (r) { return r.json(); });
      })
      .then(function (res) {
        return !!(res && res.ok);
      })
      .catch(function () { return false; });
  }

  function ensurePushSubscription(registration) {
    if (!('Notification' in window) || !registration || !registration.pushManager) return;
    if (Notification.permission !== 'granted') return;
    registration.pushManager.getSubscription().then(function (existing) {
      if (existing) {
        return fetch('/api/push-subscribe', {
          method: 'POST',
          credentials: 'same-origin',
          headers: { 'Content-Type': 'application/json' },
          body: JSON.stringify({ subscription: existing.toJSON() }),
        });
      }
      return subscribePush(registration);
    }).catch(function () {});
  }

  function setupPushBanner(registration) {
    var banner = document.getElementById('pushBanner');
    var allowBtn = document.getElementById('pushBannerAllow');
    var laterBtn = document.getElementById('pushBannerLater');
    if (!banner || !('Notification' in window)) return;

    if (Notification.permission === 'granted') {
      ensurePushSubscription(registration);
      return;
    }
    if (Notification.permission === 'denied') return;

    var dismissedKey = isStandalone() ? 'pushBannerDismissedPwa' : 'pushBannerDismissed';
    if (localStorage.getItem(dismissedKey) === '1' && !isStandalone()) return;

    banner.classList.remove('hidden');

    if (allowBtn) {
      allowBtn.addEventListener('click', function () {
        Notification.requestPermission().then(function (perm) {
          if (perm === 'granted') {
            subscribePush(registration).then(function (ok) {
              hide(banner);
              localStorage.setItem(isStandalone() ? 'pushBannerDismissedPwa' : 'pushBannerDismissed', '1');
            });
          } else {
            hide(banner);
            localStorage.setItem(isStandalone() ? 'pushBannerDismissedPwa' : 'pushBannerDismissed', '1');
          }
        });
      });
    }
    if (laterBtn) {
      laterBtn.addEventListener('click', function () {
        hide(banner);
        localStorage.setItem(isStandalone() ? 'pushBannerDismissedPwa' : 'pushBannerDismissed', '1');
      });
    }
  }

  function setupInstallBanner() {
    var banner = document.getElementById('pwaInstallBanner');
    var installBtn = document.getElementById('pwaInstallBtn');
    var dismissBtn = document.getElementById('pwaInstallDismiss');
    if (!banner) return;

    if (isStandalone() || localStorage.getItem('pwaInstallDismissed') === '1') return;

    var deferredPrompt = null;

    window.addEventListener('beforeinstallprompt', function (e) {
      e.preventDefault();
      deferredPrompt = e;
      banner.classList.remove('hidden');
    });

    if (isIos() && !isStandalone()) {
      banner.classList.remove('hidden');
      if (installBtn) {
        installBtn.textContent = 'Nasıl eklenir?';
        installBtn.addEventListener('click', function () {
          alert('Safari\'de alttaki Paylaş düğmesine dokunun, ardından "Ana Ekrana Ekle" seçeneğini kullanın.');
        });
      }
    }

    if (installBtn && !isIos()) {
      installBtn.addEventListener('click', function () {
        if (!deferredPrompt) return;
        deferredPrompt.prompt();
        deferredPrompt.userChoice.then(function (choice) {
          deferredPrompt = null;
          hide(banner);
          localStorage.setItem('pwaInstallDismissed', '1');
        });
      });
    }

    if (dismissBtn) {
      dismissBtn.addEventListener('click', function () {
        hide(banner);
        localStorage.setItem('pwaInstallDismissed', '1');
      });
    }
  }

  registerServiceWorker().then(function (registration) {
    setupInstallBanner();
    setupPushBanner(registration);
    if (registration) {
      ensurePushSubscription(registration);
    }
  });
})();
