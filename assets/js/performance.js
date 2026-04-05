/**
 * DarkShield Performance Analyzer
 *
 * @package DarkShield
 * @since 1.0.0
 */
(function ($) {
    'use strict';

    var Perf = {
        resources: [],
        checked: 0,
        total: 0,
        running: false,

        init: function () {
            if ($('#darkshield-perf-start').length === 0 &&
                $('#darkshield-perf-client').length === 0 &&
                $('#darkshield-frontend-results').length === 0) {
                return;
            }
            this.bind();

            // Auto-load frontend data if spinner present
            if ($('#darkshield-frontend-results .spinner').length > 0) {
                this.loadFrontendData();
            }
        },

        bind: function () {
            var self = this;
            $('#darkshield-perf-start').on('click', function (e) { e.preventDefault(); self.startServer(); });
            $('#darkshield-perf-client').on('click', function (e) { e.preventDefault(); self.collectClient(); });
        },

        // ========================================
        // Server-Side Analysis
        // ========================================

        startServer: function () {
            if (this.running) return;
            this.running = true;
            this.resources = [];
            this.checked = 0;
            this.total = 0;

            var url = $('#darkshield-perf-url').val() || '';
            this.status('Extracting resources...', '#0073aa');
            this.disableButtons(true);
            $('#darkshield-perf-results').html('<p style="color:#666;"><span class="spinner is-active" style="float:none;margin:0 8px 0 0;"></span>Extracting...</p>');

            var self = this;
            $.ajax({
                url: darkshield_ajax.ajax_url, type: 'POST', dataType: 'json',
                data: { action: 'darkshield_perf_extract', page_url: url, _wpnonce: darkshield_ajax.nonce },
                success: function (r) {
                    if (r.success && r.data.resources) {
                        self.resources = r.data.resources;
                        self.total = self.resources.length;
                        if (self.total === 0) {
                            $('#darkshield-perf-results').html('<div class="card" style="padding:20px;"><p>No resources found.</p></div>');
                            self.done(); return;
                        }
                        self.renderTable(r.data);
                        self.status('Checking URLs...', '#0073aa');
                        self.checkNext();
                    } else {
                        self.done();
                        DarkShield.showNotice(r.data && r.data.message ? r.data.message : 'Error', 'error');
                    }
                },
                error: function (xhr, st) { self.done(); DarkShield.showNotice('AJAX error: ' + st, 'error'); }
            });
        },

        checkNext: function () {
            if (this.checked >= this.total) { this.serverComplete(); return; }
            var res = this.resources[this.checked];
            var $row = $('#perf-row-' + this.checked);
            $row.find('.p-status').html('<span style="color:#999;">⏳</span>');
            this.progress(Math.round((this.checked / this.total) * 100));

            var self = this;
            $.ajax({
                url: darkshield_ajax.ajax_url, type: 'POST', dataType: 'json',
                data: { action: 'darkshield_perf_check_url', url: res.url, _wpnonce: darkshield_ajax.nonce },
                success: function (r) {
                    if (r.success) self.renderRow(self.checked, r.data);
                    else $row.find('.p-status').html('<span style="color:red;">❌</span>');
                    self.checked++;
                    setTimeout(function () { self.checkNext(); }, 50);
                },
                error: function () {
                    $row.find('.p-status').html('<span style="color:red;">❌</span>');
                    self.checked++;
                    setTimeout(function () { self.checkNext(); }, 50);
                }
            });
        },

        renderTable: function (data) {
            var h = '<div id="perf-progress" style="margin-bottom:15px;"><div style="background:#f0f0f0;border-radius:3px;overflow:hidden;height:24px;">';
            h += '<div id="perf-bar" style="background:#0073aa;height:24px;width:0%;text-align:center;color:#fff;font-size:12px;line-height:24px;transition:width 0.3s;">0%</div>';
            h += '</div><p id="perf-text" style="margin:5px 0 0;font-size:12px;color:#666;"></p></div>';
            h += '<p><strong>' + data.total + ' resources</strong> | HTML: ' + DarkShield.formatBytes(data.html_size) + '</p>';
            h += '<table class="widefat striped"><thead><tr><th>#</th><th>URL</th><th>Type</th><th>Domain</th><th>Time</th><th>Status</th><th>Size</th><th>Cache</th><th>Rating</th></tr></thead><tbody>';
            for (var i = 0; i < data.resources.length; i++) {
                var r = data.resources[i], dom = this.domain(r.url);
                h += '<tr id="perf-row-' + i + '"><td>' + (i + 1) + '</td>';
                h += '<td><code style="font-size:11px;word-break:break-all;">' + DarkShield.truncate(r.url, 80) + '</code></td>';
                h += '<td><span class="darkshield-badge darkshield-badge-' + DarkShield.escapeHtml(r.type) + '">' + DarkShield.escapeHtml(r.type) + '</span></td>';
                h += '<td><code style="font-size:11px;">' + DarkShield.escapeHtml(dom) + '</code></td>';
                h += '<td class="p-time">—</td><td class="p-status"><span style="color:#999;">⏳</span></td>';
                h += '<td class="p-size">—</td><td class="p-cache">—</td><td class="p-rating">—</td></tr>';
            }
            h += '</tbody></table>';
            $('#darkshield-perf-results').html(h);
        },

        renderRow: function (i, d) {
            var $r = $('#perf-row-' + i);
            if (!$r.length) return;
            var tc = d.response_time > 1000 ? '#d63638' : (d.response_time > 500 ? '#dba617' : '#00a32a');
            $r.find('.p-time').html('<span style="color:' + tc + ';font-weight:bold;">' + d.response_time + 'ms</span>');
            if (d.status === 'error') {
                $r.find('.p-status').html('<span style="color:red;" title="' + DarkShield.escapeHtml(d.error) + '">❌ ' + (d.is_blocked ? 'Blocked' : 'Error') + '</span>');
            } else {
                var sc = (d.status_code >= 200 && d.status_code < 400) ? '#00a32a' : '#d63638';
                $r.find('.p-status').html('<span style="color:' + sc + ';">' + d.status_code + '</span>');
            }
            $r.find('.p-size').text(d.content_length > 0 ? DarkShield.formatBytes(d.content_length) : '—');
            if (d.cache_control) {
                var cached = d.cache_control.indexOf('max-age') !== -1 || d.cache_control.indexOf('public') !== -1;
                $r.find('.p-cache').html(cached ? '<span style="color:#00a32a;">✓</span>' : '<span style="color:#d63638;">✗</span>');
            }
            var rm = { fast: '🟢 Fast', medium: '🟡 Medium', slow: '🔴 Slow' };
            var rc = { fast: '#00a32a', medium: '#dba617', slow: '#d63638' };
            $r.find('.p-rating').html('<span style="color:' + (rc[d.rating] || '#999') + ';font-weight:bold;">' + (rm[d.rating] || '—') + '</span>');
            if (d.is_blocked || d.status === 'error') $r.css('background', '#fcf0f1');
            else if (d.is_external && d.response_time > 1000) $r.css('background', '#fff3cd');
            else if (d.is_external) $r.css('background', '#fff8e5');
        },

        serverComplete: function () {
            this.progress(100); this.done();
            var slow = 0, fast = 0, med = 0, err = 0;
            $('#darkshield-perf-results table tbody tr').each(function () {
                var t = parseInt($(this).find('.p-time span').text(), 10);
                if (!isNaN(t)) { if (t > 1000) slow++; else if (t > 500) med++; else fast++; }
                var st = $(this).find('.p-status').text();
                if (st.indexOf('Blocked') !== -1 || st.indexOf('Error') !== -1) err++;
            });
            var sh = '<div style="display:flex;gap:15px;flex-wrap:wrap;margin:15px 0;">';
            sh += this.card('Total', this.total, '') + this.card('🟢 Fast', fast, '#00a32a');
            sh += this.card('🟡 Medium', med, '#dba617') + this.card('🔴 Slow', slow, '#d63638');
            sh += this.card('❌ Error', err, err > 0 ? '#d63638' : '#00a32a') + '</div>';
            if (slow > 0 || err > 0) {
                sh += '<div class="darkshield-notice darkshield-notice-warning" style="margin-bottom:15px;"><p><strong>💡 Recommendations:</strong></p><ul style="margin:5px 0 0 20px;list-style:disc;">';
                if (slow > 0) sh += '<li>' + slow + ' slow resource(s).</li>';
                if (err > 0) sh += '<li>' + err + ' blocked/error resource(s).</li>';
                sh += '</ul></div>';
            } else {
                sh += '<div class="darkshield-notice darkshield-notice-success" style="margin-bottom:15px;"><p><strong>✅ All fast!</strong></p></div>';
            }
            $('#perf-progress').after(sh);
            DarkShield.showNotice('Complete: ' + fast + ' fast, ' + med + ' medium, ' + slow + ' slow, ' + err + ' errors.', (slow > 0 || err > 0) ? 'warning' : 'success');
        },

        // ========================================
        // Admin Page Client-Side
        // ========================================

        collectClient: function () {
            if (!window.performance || !window.performance.getEntriesByType) {
                DarkShield.showNotice('Resource Timing API not supported.', 'error'); return;
            }
            var entries = performance.getEntriesByType('resource'), data = [];
            for (var i = 0; i < entries.length; i++) {
                var e = entries[i];
                data.push({
                    url: e.name, type: e.initiatorType || 'other', duration: Math.round(e.duration),
                    dns: Math.round(e.domainLookupEnd - e.domainLookupStart),
                    connect: Math.round(e.connectEnd - e.connectStart),
                    ttfb: Math.round(e.responseStart - e.requestStart),
                    download: Math.round(e.responseEnd - e.responseStart),
                    size: e.transferSize || 0, cached: e.transferSize === 0 && e.decodedBodySize > 0
                });
            }
            data.sort(function (a, b) { return b.duration - a.duration; });
            if (data.length === 0) {
                $('#darkshield-perf-client-results').html('<div class="card" style="padding:20px;"><p>No data available.</p></div>'); return;
            }
            var $c = $('#darkshield-perf-client-results');
            $c.html('');
            this.renderClientInto(data, $c);
            $.ajax({
                url: darkshield_ajax.ajax_url, type: 'POST',
                data: { action: 'darkshield_perf_save_client', entries: JSON.stringify(data), _wpnonce: darkshield_ajax.nonce },
                success: function (r) { if (r.success) DarkShield.showNotice('Data saved (' + data.length + ' resources).', 'success'); }
            });
        },

        // ========================================
        // Frontend Data Loader
        // ========================================

        loadFrontendData: function () {
            var self = this;
            $.ajax({
                url: darkshield_ajax.ajax_url, type: 'POST', dataType: 'json',
                data: { action: 'darkshield_perf_get_frontend', _wpnonce: darkshield_ajax.nonce },
                success: function (r) {
                    if (r.success && r.data.entries) {
                        var meta = r.data.meta || {}, entries = r.data.entries;
                        var info = '<div class="card" style="padding:15px;margin-bottom:20px;">';
                        info += '<div style="display:flex;gap:20px;flex-wrap:wrap;font-size:13px;color:#666;">';
                        if (meta.url) info += '<span><strong>Page:</strong> <code>' + DarkShield.escapeHtml(meta.url) + '</code></span>';
                        if (meta.title) info += '<span><strong>Title:</strong> ' + DarkShield.escapeHtml(meta.title) + '</span>';
                        if (r.data.collected) info += '<span><strong>Collected:</strong> ' + DarkShield.escapeHtml(r.data.collected) + '</span>';
                        info += '<span><strong>Resources:</strong> ' + entries.length + '</span></div>';
                        if (meta.page_timing && meta.page_timing.load_event) {
                            var pt = meta.page_timing;
                            info += '<div style="display:flex;gap:15px;flex-wrap:wrap;margin-top:15px;">';
                            info += self.card('DNS', pt.dns + 'ms', pt.dns > 100 ? '#d63638' : '#00a32a');
                            info += self.card('TTFB', pt.ttfb + 'ms', pt.ttfb > 500 ? '#d63638' : '#00a32a');
                            info += self.card('DOM Interactive', pt.dom_interactive + 'ms', pt.dom_interactive > 3000 ? '#d63638' : '#dba617');
                            info += self.card('Page Load', pt.load_event + 'ms', pt.load_event > 5000 ? '#d63638' : '#00a32a');
                            info += '</div>';
                        }
                        info += '</div>';
                        var $c = $('#darkshield-frontend-results');
                        $c.html(info);
                        self.renderClientInto(entries, $c);
                    } else {
                        var msg = (r.data && r.data.message) ? r.data.message : 'No data found.';
                        $('#darkshield-frontend-results').html(
                            '<div class="card" style="padding:20px;"><p>' + DarkShield.escapeHtml(msg) + '</p>' +
                            '<p style="color:#666;">Visit a frontend page and click <strong>DarkShield → 🔍 Analyze This Page</strong> in the admin bar.</p></div>');
                    }
                },
                error: function () {
                    $('#darkshield-frontend-results').html('<div class="card" style="padding:20px;"><p>Error loading data.</p></div>');
                }
            });
        },

        // ========================================
        // Shared Client Renderer
        // ========================================

        renderClientInto: function (data, $container) {
            var site = window.location.hostname;
            var totalSize = 0, slowCount = 0, cachedCount = 0, extCount = 0;

            for (var s = 0; s < data.length; s++) {
                totalSize += data[s].size || 0;
                if (data[s].duration > 1000) slowCount++;
                if (data[s].cached) cachedCount++;
                try { if (new URL(data[s].url).hostname !== site) extCount++; } catch (e) { }
            }

            var h = '';

            // Summary cards
            h += '<div style="display:flex;gap:15px;flex-wrap:wrap;margin-bottom:20px;">';
            h += this.card('Resources', data.length, '');
            h += this.card('Total Size', DarkShield.formatBytes(totalSize), '#2271b1');
            h += this.card('Slow (>1s)', slowCount, slowCount > 0 ? '#d63638' : '#00a32a');
            h += this.card('Cached', cachedCount, '#2271b1');
            h += this.card('External', extCount, extCount > 0 ? '#dba617' : '#00a32a');
            h += '</div>';

            // Recommendations
            if (slowCount > 0 || extCount > 5) {
                h += '<div class="darkshield-notice darkshield-notice-warning" style="margin-bottom:15px;">';
                h += '<p><strong>💡 Recommendations:</strong></p><ul style="margin:5px 0 0 20px;list-style:disc;">';
                if (slowCount > 0) h += '<li>' + slowCount + ' resource(s) loading over 1 second. Consider blocking or localizing.</li>';
                if (extCount > 5) h += '<li>' + extCount + ' external resources detected. Switch to National/Offline mode to reduce.</li>';
                if (cachedCount < data.length * 0.3) h += '<li>Low cache hit rate. Consider enabling browser caching for static assets.</li>';
                h += '</ul></div>';
            } else {
                h += '<div class="darkshield-notice darkshield-notice-success" style="margin-bottom:15px;">';
                h += '<p><strong>✅ Good performance!</strong> No major issues detected.</p></div>';
            }

            // Resource table
            h += '<h3>📋 Resource Details (sorted by duration)</h3>';
            h += '<table class="widefat striped"><thead><tr>';
            h += '<th>#</th><th>URL</th><th>Type</th><th>DNS</th><th>Connect</th><th>TTFB</th><th>Download</th><th>Total</th><th>Size</th><th>Cached</th>';
            h += '</tr></thead><tbody>';

            var max = Math.min(data.length, 100);
            for (var i = 0; i < max; i++) {
                var e = data[i];
                var tc = e.duration > 1000 ? '#d63638' : (e.duration > 500 ? '#dba617' : '#00a32a');
                var bg = e.duration > 1000 ? ' style="background:#fcf0f1;"' : (e.duration > 500 ? ' style="background:#fff8e5;"' : '');
                var ext = false;
                try { ext = new URL(e.url).hostname !== site; } catch (ex) { }

                h += '<tr' + bg + '>';
                h += '<td>' + (i + 1) + '</td>';
                h += '<td><code style="font-size:10px;word-break:break-all;">' + DarkShield.truncate(e.url, 70) + '</code>';
                if (ext) h += ' <span style="color:#dba617;font-size:10px;">⬆ ext</span>';
                h += '</td>';
                h += '<td style="font-size:11px;">' + DarkShield.escapeHtml(e.type) + '</td>';
                h += '<td>' + this.colorMs(e.dns, 50) + '</td>';
                h += '<td>' + this.colorMs(e.connect, 100) + '</td>';
                h += '<td>' + this.colorMs(e.ttfb, 200) + '</td>';
                h += '<td>' + this.colorMs(e.download, 300) + '</td>';
                h += '<td><span style="color:' + tc + ';font-weight:bold;">' + e.duration + 'ms</span></td>';
                h += '<td>' + (e.size > 0 ? DarkShield.formatBytes(e.size) : '—') + '</td>';
                h += '<td>' + (e.cached ? '<span style="color:#2271b1;">✓</span>' : '—') + '</td>';
                h += '</tr>';
            }

            if (data.length > 100) {
                h += '<tr><td colspan="10" style="text-align:center;color:#666;">Showing top 100 of ' + data.length + '</td></tr>';
            }

            h += '</tbody></table>';

            // Waterfall chart
            h += this.waterfall(data.slice(0, 20));

            // External domains summary
            var domains = {};
            for (var d = 0; d < data.length; d++) {
                try {
                    var dom = new URL(data[d].url).hostname;
                    if (dom !== site) {
                        if (!domains[dom]) domains[dom] = { count: 0, totalTime: 0, totalSize: 0 };
                        domains[dom].count++;
                        domains[dom].totalTime += data[d].duration;
                        domains[dom].totalSize += data[d].size || 0;
                    }
                } catch (ex) { }
            }

            var domArr = [];
            for (var dk in domains) {
                if (domains.hasOwnProperty(dk)) {
                    domArr.push({ domain: dk, count: domains[dk].count, time: domains[dk].totalTime, size: domains[dk].totalSize });
                }
            }
            domArr.sort(function (a, b) { return b.time - a.time; });

            if (domArr.length > 0) {
                h += '<h3 style="margin-top:25px;">🌍 External Domains Summary</h3>';
                h += '<table class="widefat striped"><thead><tr>';
                h += '<th>Domain</th><th>Requests</th><th>Total Time</th><th>Total Size</th><th>Avg Time</th><th>Status</th>';
                h += '</tr></thead><tbody>';

                for (var di = 0; di < domArr.length; di++) {
                    var dd = domArr[di];
                    var avg = Math.round(dd.time / dd.count);
                    var avgColor = avg > 500 ? '#d63638' : (avg > 200 ? '#dba617' : '#00a32a');

                    h += '<tr>';
                    h += '<td><code>' + DarkShield.escapeHtml(dd.domain) + '</code></td>';
                    h += '<td>' + dd.count + '</td>';
                    h += '<td>' + dd.time + 'ms</td>';
                    h += '<td>' + DarkShield.formatBytes(dd.size) + '</td>';
                    h += '<td><span style="color:' + avgColor + ';font-weight:bold;">' + avg + 'ms</span></td>';
                    h += '<td>';
                    if (avg > 500) h += '<span style="color:#d63638;">🔴 Slow — consider blocking</span>';
                    else if (avg > 200) h += '<span style="color:#dba617;">🟡 Medium</span>';
                    else h += '<span style="color:#00a32a;">🟢 Fast</span>';
                    h += '</td></tr>';
                }

                h += '</tbody></table>';
            }

            $container.append(h);
        },

        // ========================================
        // Waterfall Chart
        // ========================================

        waterfall: function (data) {
            if (!data || data.length === 0) return '';

            var maxD = data[0].duration || 1;
            var h = '<h3 style="margin-top:25px;">📊 Waterfall — Top 20 Slowest</h3>';
            h += '<div class="darkshield-waterfall">';

            for (var i = 0; i < data.length; i++) {
                var e = data[i];
                var tw = Math.max(2, Math.round((e.duration / maxD) * 100));
                var dw = Math.max(0, Math.round((e.dns / maxD) * 100));
                var cw = Math.max(0, Math.round((e.connect / maxD) * 100));
                var bw = Math.max(0, Math.round((e.ttfb / maxD) * 100));
                var lw = Math.max(1, tw - dw - cw - bw);
                if (lw < 0) lw = 1;

                var tc = e.duration > 1000 ? '#d63638' : (e.duration > 500 ? '#dba617' : '#333');

                h += '<div class="darkshield-waterfall-row">';
                h += '<div class="darkshield-waterfall-label" title="' + DarkShield.escapeHtml(e.url) + '"><code style="font-size:10px;">' + DarkShield.escapeHtml(DarkShield.truncate(e.url, 40)) + '</code></div>';
                h += '<div class="darkshield-waterfall-bar">';
                if (dw > 0) h += '<div style="width:' + dw + '%;background:#9b59b6;min-width:1px;" title="DNS: ' + e.dns + 'ms"></div>';
                if (cw > 0) h += '<div style="width:' + cw + '%;background:#e67e22;min-width:1px;" title="Connect: ' + e.connect + 'ms"></div>';
                if (bw > 0) h += '<div style="width:' + bw + '%;background:#3498db;min-width:1px;" title="TTFB: ' + e.ttfb + 'ms"></div>';
                if (lw > 0) h += '<div style="width:' + lw + '%;background:#2ecc71;min-width:1px;" title="Download: ' + e.download + 'ms"></div>';
                h += '</div>';
                h += '<div class="darkshield-waterfall-time" style="color:' + tc + ';">' + e.duration + 'ms</div>';
                h += '</div>';
            }

            // Scale
            h += '<div style="display:flex;align-items:center;margin-top:8px;font-size:10px;color:#999;">';
            h += '<div style="width:250px;"></div>';
            h += '<div style="flex:1;display:flex;justify-content:space-between;">';
            h += '<span>0ms</span><span>' + Math.round(maxD * 0.5) + 'ms</span><span>' + maxD + 'ms</span>';
            h += '</div><div style="width:65px;"></div></div>';

            // Legend
            h += '<div style="display:flex;gap:15px;margin-top:12px;padding-top:10px;border-top:1px solid #e0e0e0;font-size:11px;color:#666;">';
            h += '<span><span style="display:inline-block;width:12px;height:12px;background:#9b59b6;border-radius:2px;vertical-align:middle;margin-right:3px;"></span> DNS</span>';
            h += '<span><span style="display:inline-block;width:12px;height:12px;background:#e67e22;border-radius:2px;vertical-align:middle;margin-right:3px;"></span> Connect</span>';
            h += '<span><span style="display:inline-block;width:12px;height:12px;background:#3498db;border-radius:2px;vertical-align:middle;margin-right:3px;"></span> TTFB</span>';
            h += '<span><span style="display:inline-block;width:12px;height:12px;background:#2ecc71;border-radius:2px;vertical-align:middle;margin-right:3px;"></span> Download</span>';
            h += '</div></div>';

            return h;
        },

        // ========================================
        // UI Helpers
        // ========================================

        card: function (label, value, color) {
            var cs = color ? 'color:' + color + ';' : '';
            return '<div class="card" style="flex:1;min-width:120px;padding:15px;">' +
                '<h3 style="margin:0 0 5px;font-size:12px;color:#666;">' + DarkShield.escapeHtml(String(label)) + '</h3>' +
                '<p style="margin:0;font-size:22px;font-weight:bold;' + cs + '">' + DarkShield.escapeHtml(String(value)) + '</p></div>';
        },

        colorMs: function (ms, threshold) {
            if (ms <= 0) return '<span style="color:#ccc;">0ms</span>';
            var c = ms > threshold ? '#d63638' : (ms > threshold / 2 ? '#dba617' : '#00a32a');
            return '<span style="color:' + c + ';">' + ms + 'ms</span>';
        },

        progress: function (pct) {
            pct = Math.min(100, Math.max(0, pct));
            $('#perf-bar').css('width', pct + '%').text(pct + '%');
            $('#perf-text').text('Checking ' + this.checked + ' / ' + this.total + '...');
        },

        status: function (text, color) {
            $('#darkshield-perf-status').text(text).css('color', color || '#666');
        },

        disableButtons: function (disabled) {
            $('#darkshield-perf-start, #darkshield-perf-client').prop('disabled', disabled);
        },

        done: function () {
            this.running = false;
            this.disableButtons(false);
            this.status('✓ Complete', '#00a32a');
        },

        domain: function (url) {
            try { var a = document.createElement('a'); a.href = url; return a.hostname; }
            catch (e) { return ''; }
        }
    };

    $(document).ready(function () {
        Perf.init();
    });

})(jQuery);

