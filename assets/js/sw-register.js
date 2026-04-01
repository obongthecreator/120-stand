/**
 * 120 Stand Inventory - Service Worker Registration
 */

if ('serviceWorker' in navigator) {
    window.addEventListener('load', function() {
        navigator.serviceWorker.register(stand120_ajax.plugin_url + 'assets/js/sw.js')
            .then(function(registration) {
                console.log('120 Stand SW registered:', registration.scope);
                
                // Check for updates
                registration.onupdatefound = function() {
                    const installingWorker = registration.installing;
                    installingWorker.onstatechange = function() {
                        if (installingWorker.state === 'installed') {
                            if (navigator.serviceWorker.controller) {
                                // New content is available
                                console.log('New content is available; please refresh.');
                            }
                        }
                    };
                };
            })
            .catch(function(error) {
                console.log('120 Stand SW registration failed:', error);
            });
    });
}
