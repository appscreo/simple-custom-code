jQuery(document).ready(function ($) {
    const overlayHTML = `
        <div id="scc-ai-overlay" class="scc-ai-modal">
            <div class="scc-ai-field-container">
            <div class="scc-ai-field">
                <input type="hidden" id="scc-ai-mode" value="default">
                <textarea id="scc-ai-prompt" placeholder="Enter prompt..."></textarea>
            </div>
            <div class="scc-ai-field ai-toggle">
                <label><input type="checkbox" id="scc-ai-advanced-toggle"> Advanced Requirements</label>
            </div>
            <div class="scc-ai-field" id="scc-ai-advanced-field" style="display:none;">
                <textarea id="scc-ai-advanced" placeholder="Enter advanced requirements..."></textarea>
            </div>
            <div class="scc-ai-field scc-ai-actions">
                <button id="scc-ai-generate-start" class="button button-primary">Generate</button>
                <button id="scc-ai-close-overlay" class="button">Close</button>
                <span class="scc-loading-spinner" id="scc-ai-loading" style="display:none;"></span>
            </div>
            </div>
        </div>
    `;

    const resultHTML = `
        <div id="scc-ai-result" class="scc-ai-modal">
            <div class="scc-ai-field-container">
            <div class="scc-ai-field">
                <textarea id="scc-ai-generated-code" readonly></textarea>
            </div>
            <div class="scc-ai-field">
                <button id="scc-ai-insert-code" class="button button-primary">Insert Code</button>
                <button id="scc-ai-close-result" class="button">Close</button>
            </div>
            </div>
        </div>
    `;

    $('#scc-ai-overlay-container').html(overlayHTML);
    $('#scc-ai-result-overlay').html(resultHTML);

    $('#scc-ai-generate-button').on('click', function (e) {
        e.preventDefault();
        $('#scc-ai-mode').val($('#scc_code_type').val());
        $('#scc-ai-overlay-container').show();
    });

    $('#scc-ai-close-overlay').on('click', function (e) {
        e.preventDefault();
        $('#scc-ai-overlay-container').hide();
    });

    $('#scc-ai-advanced-toggle').on('change', function () {
        $('#scc-ai-advanced-field').toggle(this.checked);
    });

    $('#scc-ai-generate-start').on('click', function (e) {
        e.preventDefault();
        const $btn = $(this);
        const $spinner = $('#scc-ai-loading');

        if ($('#scc-ai-prompt').val() == '') {
            alert('Enter message');
            return;
        }

        $spinner.show();
        $btn.prop('disabled', true);

        const data = {
            action: 'scc_generate_ai_code',
            nonce: sccaiGenerator.nonce,
            prompt: $('#scc-ai-prompt').val(),
            advanced: $('#scc-ai-advanced-toggle').is(':checked') ? $('#scc-ai-advanced').val() : '',
            mode: $('#scc-ai-mode').val()
        };

        $.post(sccaiGenerator.ajax_url, data, function (response) {
            $spinner.hide();
            $btn.prop('disabled', false);

            if (response.code === 1) {
                $('#scc-ai-overlay-container').hide();
                $('#scc-ai-generated-code').val(response.generated);
                $('#scc-ai-result-overlay').show();
            } else {
                alert(response.code + ': ' + (response.generated || 'An error occurred.'));
            }
        });
    });

    $('#scc-ai-close-result').on('click', function (e) {
        e.preventDefault();
        $('#scc-ai-result-overlay').hide();
    });

    $('#scc-ai-insert-code').on('click', function (e) {
        e.preventDefault();
        // Placeholder function – user can define this
        const doc = window.ccmCodeMirror.getDoc();
        const endPos = doc.posFromIndex(doc.getValue().length);
        doc.replaceRange("\n" + $('#scc-ai-generated-code').val(), endPos);
        $('#scc-ai-result-overlay').hide();
    });
});
