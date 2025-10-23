// assets/js/admin-list.js
jQuery(document).ready(function ($) {
    'use strict';

    // Handle status toggle
    $(document).on('click', '.scc-status', function (e) {
        e.preventDefault();

        var $this = $(this);
        var $toggle = $this.closest('.scc-status-toggle');
        var postId = $toggle.data('post-id');
        var nonce = $toggle.data('nonce');

        //if (confirm(ccmAdminList.strings.confirm_toggle)) {
            $.ajax({
                url: ccmAdminList.ajax_url,
                type: 'POST',
                data: {
                    action: 'scc_toggle_active',
                    post_id: postId,
                    _nonce: nonce
                },
                beforeSend: function () {
                    $this.css('opacity', '0.5');
                },
                success: function (response) {
                    let code = response.code || '';
                    if (response.success) {
                        $toggle.html(code);
                        sscUpdateRowStatusOpacity();
                    } else {
                        alert(ccmAdminList.strings.error);
                    }
                },
                error: function (xhr, status, error) {
                    alert(ccmAdminList.strings.error);
                },
                complete: function () {
                    $this.css('opacity', '1');
                }
            });
        //}
    });

    function sscUpdateRowStatusOpacity() {
        document.querySelectorAll('.wp-list-table tbody tr').forEach(row => {
            let statusElement = row.querySelector('.scc-status');
            if (statusElement && !statusElement.classList.contains('scc-status-active')) {
                row.style.opacity = '0.5';
            }
            else {
                row.style.opacity = '1';
            }
        });
    }

    sscUpdateRowStatusOpacity();
});
