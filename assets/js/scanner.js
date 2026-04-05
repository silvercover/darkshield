/**
 * DarkShield Scanner
 *
 * @package DarkShield
 * @since 1.0.0
 */
(function($) {
    'use strict';

    var Scanner = {
        batchId: '',
        totalBatches: 0,
        currentBatch: 0,
        isPaused: false,
        isStopped: false,
        isRunning: false,

        init: function() {
            if ($('#darkshield-start-scan').length === 0) return;
            this.bind();
        },

        bind: function() {
            var self = this;
            $('#darkshield-start-scan').on('click', function() { self.start('files'); });
            $('#darkshield-scan-db').on('click', function() { self.start('database'); });
            $('#darkshield-clear-scan').on('click', function() { self.clear(); });
            $('#darkshield-pause-scan').on('click', function() { self.togglePause(); });
            $('#darkshield-stop-scan').on('click', function() { self.stop(); });
        },

        start: function(type) {
            if (this.isRunning) return;
            this.isRunning = true;
            this.isPaused = false;
            this.isStopped = false;
            this.currentBatch = 0;

            var self = this;
            var s = darkshield_ajax.strings;

            this.setStatus(type === 'files' ? s.scanning_files : s.scanning_db, '#0073aa');
            this.showControls(true);
            this.showProgress(true);
            this.updateProgress(0);

            $.ajax({
                url: darkshield_ajax.ajax_url,
                type: 'POST',
                dataType: 'json',
                data: {
                    action: 'darkshield_start_scan',
                    scan_type: type,
                    _wpnonce: darkshield_ajax.nonce
                },
                success: function(r) {
                    if (r.success) {
                        self.batchId = r.data.batch_id;
                        self.totalBatches = r.data.total_batches;
                        self.currentBatch = 0;
                        self.processNext();
                    } else {
                        self.finish(false, r.data.message || s.error);
                    }
                },
                error: function() {
                    self.finish(false, s.error);
                }
            });
        },

        processNext: function() {
            if (this.isStopped) return;
            if (this.isPaused) return;
            if (this.currentBatch >= this.totalBatches) {
                this.finish(true);
                return;
            }

            var self = this;
            var pct = Math.round((this.currentBatch / this.totalBatches) * 100);
            this.updateProgress(pct);
            this.setStatus(darkshield_ajax.strings.scanning + ' (' + this.currentBatch + '/' + this.totalBatches + ')', '#0073aa');

            $.ajax({
                url: darkshield_ajax.ajax_url,
                type: 'POST',
                dataType: 'json',
                data: {
                    action: 'darkshield_process_batch',
                    batch_id: this.batchId,
                    batch_index: this.currentBatch,
                    _wpnonce: darkshield_ajax.nonce
                },
                success: function(r) {
                    if (r.success) {
                        self.currentBatch++;
                        setTimeout(function() { self.processNext(); }, 50);
                    } else {
                        self.finish(false, r.data.message || darkshield_ajax.strings.error);
                    }
                },
                error: function() {
                    self.finish(false, darkshield_ajax.strings.error);
                }
            });
        },

        togglePause: function() {
            this.isPaused = !this.isPaused;
            var $btn = $('#darkshield-pause-scan');
            var s = darkshield_ajax.strings;

            if (this.isPaused) {
                $btn.text(s.resume);
                this.setStatus(s.paused_status, '#dba617');
            } else {
                $btn.text(s.pause);
                this.processNext();
            }
        },

        stop: function() {
            if (!confirm(darkshield_ajax.strings.confirm_stop)) return;
            this.isStopped = true;

            var self = this;
            $.ajax({
                url: darkshield_ajax.ajax_url,
                type: 'POST',
                dataType: 'json',
                data: {
                    action: 'darkshield_stop_scan',
                    batch_id: this.batchId,
                    _wpnonce: darkshield_ajax.nonce
                },
                success: function() {
                    var msg = darkshield_ajax.strings.scan_stopped_detail
                        .replace('%completed%', self.currentBatch)
                        .replace('%total%', self.totalBatches);
                    self.finish(true, msg);
                }
            });
        },

        clear: function() {
            if (!confirm(darkshield_ajax.strings.confirm_clear)) return;
            $.ajax({
                url: darkshield_ajax.ajax_url,
                type: 'POST',
                dataType: 'json',
                data: {
                    action: 'darkshield_clear_scan',
                    _wpnonce: darkshield_ajax.nonce
                },
                success: function(r) {
                    if (r.success) {
                        $('#darkshield-scan-results').html('<p>' + darkshield_ajax.strings.no_results + '</p>');
                        DarkShield.showNotice(r.data.message, 'success');
                    }
                }
            });
        },

        finish: function(success, message) {
            this.isRunning = false;
            this.showControls(false);
            this.updateProgress(100);

            var s = darkshield_ajax.strings;
            var msg = message || s.scan_complete;
            var type = success ? 'success' : 'error';

            this.setStatus(msg, success ? '#00a32a' : '#d63638');
            DarkShield.showNotice(msg, type);

            // Reload results
            this.loadResults();
        },

        loadResults: function() {
            $.ajax({
                url: darkshield_ajax.ajax_url,
                type: 'POST',
                dataType: 'json',
                data: {
                    action: 'darkshield_get_scan_results',
                    _wpnonce: darkshield_ajax.nonce
                },
                success: function(r) {
                    if (r.success && r.data.html) {
                        $('#darkshield-scan-results').html(r.data.html);
                    }
                }
            });
        },

        setStatus: function(text, color) {
            $('#darkshield-scan-status').text(text).css('color', color || '#666');
        },

        showControls: function(show) {
            $('#darkshield-scan-controls').toggle(show);
            $('#darkshield-start-scan, #darkshield-scan-db, #darkshield-clear-scan').prop('disabled', show);
            if (show) {
                $('#darkshield-pause-scan').text(darkshield_ajax.strings.pause);
            }
        },

        showProgress: function(show) {
            $('#darkshield-scan-progress').toggle(show);
        },

        updateProgress: function(pct) {
            pct = Math.min(100, Math.max(0, pct));
            $('#darkshield-progress-bar').css('width', pct + '%').text(pct + '%');
            $('#darkshield-progress-text').text(this.currentBatch + ' / ' + this.totalBatches);
        }
    };

    $(document).ready(function() {
        Scanner.init();
    });

})(jQuery);
