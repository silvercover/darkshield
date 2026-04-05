/**
 * DarkShield Frontend Performance Collector
 * Loads only for admins on frontend pages.
 *
 * @package DarkShield
 * @since 1.0.0
 */
(function($) {
    'use strict';

    var FrontPerf = {

        init: function() {
            // Wait for page to fully load
            if (document.readyState === 'complete') {
                this.ready();
            } else {
                $(window).on('load', $.proxy(this.ready, this));
            }
        },

        ready: function() {
            this.bindAdminBar();
        },

        /**
         * Bind click on admin bar button.
         */
        bindAdminBar: function() {
            var self = this;

            $(document).on('click', '#wp-admin-bar-darkshield-analyze-page a', function(e) {
                e.preventDefault();
                self.collect();
            });
        },

        /**
         * Collect Resource Timing data and send to server.
         */
        collect: function() {
            var $btn = $('#wp-admin-bar-darkshield-analyze-page a');
            var originalText = $btn.text();

            // Check browser support
            if (!window.performance || !window.performance.getEntriesByType) {
                this.notify($btn, '❌ Browser not supported', originalText);
                return;
            }

            // Show collecting state
            $btn.text('⏳ Collecting...');
            $btn.css('opacity', '0.7');

            var entries = performance.getEntriesByType('resource');
            var navEntry = performance.getEntriesByType('navigation');
            var data = [];

            // Collect resource entries
            for (var i = 0; i < entries.length; i++) {
                var e = entries[i];
                data.push({
                    url:      e.name,
                    type:     e.initiatorType || 'other',
                    duration: Math.round(e.duration),
                    dns:      Math.round(e.domainLookupEnd - e.domainLookupStart),
                    connect:  Math.round(e.connectEnd - e.connectStart),
                    ttfb:     Math.round(e.responseStart - e.requestStart),
                    download: Math.round(e.responseEnd - e.responseStart),
                    size:     e.transferSize || 0,
                    decoded:  e.decodedBodySize || 0,
                    cached:   e.transferSize === 0 && e.decodedBodySize > 0
                });
            }

            // Sort by duration descending
            data.sort(function(a, b) { return b.duration - a.duration; });

            // Page-level timing
            var pageTiming = {};
            if (navEntry && navEntry.length > 0) {
                var nav = navEntry[0];
                pageTiming = {
                    dns:             Math.round(nav.domainLookupEnd - nav.domainLookupStart),
                    connect:         Math.round(nav.connectEnd - nav.connectStart),
                    ttfb:            Math.round(nav.responseStart - nav.requestStart),
                    download:        Math.round(nav.responseEnd - nav.responseStart),
                    dom_interactive: Math.round(nav.domInteractive - nav.startTime),
                    dom_complete:    Math.round(nav.domComplete - nav.startTime),
                    load_event:      Math.round(nav.loadEventEnd - nav.startTime),
                    total_duration:  Math.round(nav.duration)
                };
            }

            // Page metadata
            var meta = {
                url:        window.location.href,
                title:      document.title,
                timestamp:  new Date().toISOString(),
                user_agent: navigator.userAgent,
                resources:  data.length,
                page_timing: pageTiming
            };

            var self = this;

            // Send to server
            $.ajax({
                url: darkshield_front_perf.ajax_url,
                type: 'POST',
                dataType: 'json',
                data: {
                    action:   'darkshield_perf_save_frontend',
                    entries:  JSON.stringify(data),
                    meta:     JSON.stringify(meta),
                    _wpnonce: darkshield_front_perf.nonce
                },
                success: function(r) {
                    if (r.success) {
                        self.showResult($btn, r.data.count, r.data.view_url, originalText);
                    } else {
                        self.notify($btn, '❌ Error saving', originalText);
                    }
                },
                error: function() {
                    self.notify($btn, '❌ AJAX Error', originalText);
                }
            });
        },

        /**
         * Show success result with link.
         */
        showResult: function($btn, count, viewUrl, originalText) {
            $btn.css('opacity', '1');
            $btn.html('✅ ' + count + ' resources collected! <strong style="text-decoration:underline;">View Results →</strong>');
            $btn.attr('href', viewUrl);

            // Also show a floating notification
            this.showFloatingNotice(count, viewUrl);

            // Reset button after 15 seconds
            setTimeout(function() {
                $btn.text(originalText);
                $btn.removeAttr('href');
            }, 15000);
        },

        /**
         * Show floating notification at bottom of screen.
         */
        showFloatingNotice: function(count, viewUrl) {
            var $notice = $(
                '<div id="darkshield-front-notice" style="' +
                'position:fixed;bottom:20px;right:20px;z-index:999999;' +
                'background:#fff;border:1px solid #00a32a;border-radius:8px;' +
                'padding:15px 20px;box-shadow:0 4px 20px rgba(0,0,0,0.15);' +
                'max-width:400px;font-family:-apple-system,BlinkMacSystemFont,sans-serif;' +
                'animation:slideUp 0.3s ease;">' +
                '<div style="display:flex;align-items:center;gap:10px;">' +
                '<span style="font-size:24px;">🛡️</span>' +
                '<div>' +
                '<strong style="display:block;margin-bottom:3px;">DarkShield: Data Collected!</strong>' +
                '<span style="color:#666;font-size:13px;">' + count + ' resources analyzed on this page.</span>' +
                '</div>' +
                '</div>' +
                '<div style="margin-top:10px;display:flex;gap:8px;">' +
                '<a href="' + viewUrl + '" style="' +
                'display:inline-block;padding:6px 14px;background:#0073aa;color:#fff;' +
                'text-decoration:none;border-radius:4px;font-size:13px;font-weight:600;">' +
                'View Results →</a>' +
                '<button type="button" id="darkshield-dismiss-notice" style="' +
                'padding:6px 14px;background:#f0f0f0;border:1px solid #ccc;' +
                'border-radius:4px;font-size:13px;cursor:pointer;">Dismiss</button>' +
                '</div>' +
                '</div>'
            );

            // Add animation CSS
            if (!$('#darkshield-front-anim').length) {
                $('head').append(
                    '<style id="darkshield-front-anim">' +
                    '@keyframes slideUp{from{transform:translateY(100px);opacity:0}to{transform:translateY(0);opacity:1}}' +
                    '</style>'
                );
            }

            // Remove existing
            $('#darkshield-front-notice').remove();

            $('body').append($notice);

            // Dismiss
            $notice.on('click', '#darkshield-dismiss-notice', function() {
                $notice.fadeOut(300, function() { $(this).remove(); });
            });

            // Auto dismiss after 20s
            setTimeout(function() {
                $notice.fadeOut(500, function() { $(this).remove(); });
            }, 20000);
        },

        /**
         * Simple button notification.
         */
        notify: function($btn, text, originalText) {
            $btn.css('opacity', '1');
            $btn.text(text);
            setTimeout(function() {
                $btn.text(originalText);
            }, 3000);
        }
    };

    $(document).ready(function() {
        FrontPerf.init();
    });

})(jQuery);
