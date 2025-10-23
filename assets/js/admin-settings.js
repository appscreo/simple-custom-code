// assets/js/admin-settings.js
jQuery(document).ready(function($) {
    'use strict';

    // Handle cache clear
    $('#scc-clear-cache-link, .scc-clear-cache-button').on('click', function(e) {
        e.preventDefault();
        
        var $button = $(this);
        var originalText = $button.text();
        
        $.ajax({
            url: ccmSettings.ajax_url,
            type: 'POST',
            data: {
                action: 'scc_clear_cache',
                _wpnonce: ccmSettings.nonce
            },
            beforeSend: function() {
                $button.text(ccmSettings.strings.clearing_cache).prop('disabled', true);
            },
            success: function(response) {
                if (response.success) {
                    $button.text(ccmSettings.strings.cache_cleared);
                    setTimeout(function() {
                        $button.text(originalText).prop('disabled', false);
                    }, 2000);
                } else {
                    alert(ccmSettings.strings.error);
                    $button.text(originalText).prop('disabled', false);
                }
            },
            error: function() {
                alert(ccmSettings.strings.error);
                $button.text(originalText).prop('disabled', false);
            }
        });
    });

    // Handle cache mode toggle
    $('#cache_mode').on('change', function() {
        var $cacheFields = $('#cache_type, #cache_method, #cache_optimized');
        
        if ($(this).is(':checked')) {
            $cacheFields.prop('disabled', false);
        } else {
            $cacheFields.prop('disabled', true);
        }
    }).trigger('change');
});