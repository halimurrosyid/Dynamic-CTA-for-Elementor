/**
 * Dynamic CTA Frontend Click Tracker
 */
(function() {
    'use strict';

    document.addEventListener('DOMContentLoaded', function() {
        if (typeof dynamic_cta_params === 'undefined') {
            return;
        }

        document.body.addEventListener('click', function(e) {
            var target = e.target.closest('a');
            if (!target) {
                return;
            }

            var href = target.getAttribute('href');
            if (!href || href === '#' || href.indexOf('javascript:') === 0) {
                return;
            }

            // Detect if link is dynamic CTA destination
            var defaultUrl = dynamic_cta_params.default_url || '';
            var isCtaLink = target.classList.contains('dynamic-cta-link') ||
                            target.hasAttribute('data-dynamic-cta') ||
                            (defaultUrl && href.indexOf(defaultUrl.replace(/\/$/, '')) !== -1);

            if (isCtaLink) {
                // Apply open link target mode if needed
                if (dynamic_cta_params.open_link === '_blank' && !target.hasAttribute('target')) {
                    target.setAttribute('target', '_blank');
                    target.setAttribute('rel', 'noopener noreferrer');
                }

                // Extract area from URL path if present
                var areaName = '';
                var basePath = dynamic_cta_params.base_path || '';
                var pathRegex;
                if (basePath) {
                    var escapedPath = basePath.replace(/[-\/\\^$*+?.()|[\]{}]/g, '\\$&');
                    pathRegex = new RegExp('\\/' + escapedPath + '\\/([^\\/]+)\\/?', 'i');
                } else {
                    pathRegex = /\/([^\/]+)\/?$/;
                }
                var matches = href.match(pathRegex);
                if (matches && matches[1]) {
                    areaName = matches[1];
                }

                // Send beacon or async fetch for statistics tracking
                var payload = new FormData();
                payload.append('action', 'dynamic_cta_record_click');
                payload.append('post_id', dynamic_cta_params.post_id || 0);
                payload.append('area_name', areaName);
                payload.append('destination_url', href);
                payload.append('referer', window.location.href);

                if (navigator.sendBeacon) {
                    navigator.sendBeacon(dynamic_cta_params.ajax_url, payload);
                } else {
                    fetch(dynamic_cta_params.ajax_url, {
                        method: 'POST',
                        body: payload,
                        credentials: 'same-origin'
                    }).catch(function() {});
                }
            }
        });
    });
})();
