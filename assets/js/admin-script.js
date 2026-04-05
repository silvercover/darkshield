/**
 * DarkShield Admin Script
 *
 * @package DarkShield
 * @since 1.0.0
 */
(function($) {
    'use strict';

    window.DarkShield = {

        init: function() {
            this.bindEvents();
        },

        bindEvents: function() {
            setTimeout(function() {
                $('.darkshield-auto-dismiss').fadeOut(400, function() {
                    $(this).remove();
                });
            }, 5000);
        },

        showNotice: function(message, type, autoDismiss) {
            if (!message) return;
            type = type || 'info';
            autoDismiss = (typeof autoDismiss !== 'undefined') ? autoDismiss : true;

            var $notice = $(
                '<div class="notice notice-' + type + ' is-dismissible" style="margin:15px 0;">' +
                '<p>' + message + '</p>' +
                '<button type="button" class="notice-dismiss"><span class="screen-reader-text">Dismiss</span></button>' +
                '</div>'
            );

            var $target = $('.nav-tab-wrapper').first();
            if ($target.length) {
                $target.after($notice);
            } else {
                $('.wrap h1').first().after($notice);
            }

            $notice.find('.notice-dismiss').on('click', function() {
                $notice.fadeOut(200, function() { $(this).remove(); });
            });

            if (autoDismiss) {
                setTimeout(function() {
                    $notice.fadeOut(400, function() { $(this).remove(); });
                }, 8000);
            }
        },

        confirm: function(message) {
            return window.confirm(message);
        },

        formatNumber: function(num) {
            return num.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ',');
        },

        formatBytes: function(bytes) {
            if (bytes <= 0) return '0 B';
            var units = ['B', 'KB', 'MB', 'GB'];
            var i = Math.floor(Math.log(bytes) / Math.log(1024));
            if (i >= units.length) i = units.length - 1;
            return (bytes / Math.pow(1024, i)).toFixed(1) + ' ' + units[i];
        },

        escapeHtml: function(str) {
            if (!str) return '';
            var div = document.createElement('div');
            div.appendChild(document.createTextNode(str));
            return div.innerHTML;
        },

        truncate: function(str, max) {
            if (!str) return '';
            return str.length > max ? str.substring(0, max) + '...' : str;
        }
    };

    $(document).ready(function() {
        DarkShield.init();
    });

})(jQuery);
