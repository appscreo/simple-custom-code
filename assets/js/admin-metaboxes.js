// assets/js/admin-metaboxes.js
jQuery(document).ready(function ($) {
    // Handle code type button clicks
    $('.scc-code-type-btn').on('click', function (e) {
        e.preventDefault();

        var codeType = $(this).data('code-type');
        var $hiddenInput = $('#scc_code_type');

        // Remove active class from all buttons
        $('.scc-code-type-btn').removeClass('active');

        // Add active class to clicked button
        $(this).addClass('active');

        // Update hidden field value
        $hiddenInput.val(codeType).trigger('change');

        // Trigger any existing change handlers for code type
        if (window.ccmCodeTypeChange && typeof window.ccmCodeTypeChange === 'function') {
            window.ccmCodeTypeChange(codeType);
        }
    });
});

jQuery(document).ready(function ($) {
    'use strict';

    var $loadingMethod = $('#scc_loading_method');
    var $manualOptions = $('#scc_manual_options');
    var $actionFilterRow = $('#scc_action_filter_name_row');
    var $shortcodeRow = $('#scc_manual_shortcode');
    var $loadingLocationsRow = $('#scc_loading_locations_row');
    var $codePositionRow = $('#scc_code_position_row');
    var $loadingConditionsWrapper = $('#scc_loading_conditions_wrapper');
    var $codeType = $('#scc_code_type');
    var $linkingTypeRow = $('#scc_linking_type_row');
    var $cssPreprocessorRow = $('#scc_css_preprocessor_row');
    var $cssPreprocessor = $('#scc_css_preprocessor');
    var $manualType = $('#scc_manual_type');
    var $linkingType = $('#scc_linking_type');

    var $cssOptimizedLoadingRow = $('#scc_css_optimize_enabled_row');
    var $jsOptimizedLoadingRow = $('#scc_js_optimize_enabled_row');

    var codeLinting = $('#scc-code-editor-wrapper').data('lint') || '';
    if (codeLinting == 'true') codeLinting = '1';

    var codeAutocomplete = $('#scc-code-editor-wrapper').data('autocomplete') || '';
    if (codeAutocomplete == 'true') codeAutocomplete = '1';

    var codeTheme = $('#scc-code-editor-wrapper').data('theme') || '';

    window.ccmCodeMirror = null;

    // Handle loading method change
    $loadingMethod.on('change', function () {
        var method = $(this).val();

        if (method === 'manual') {
            $manualOptions.show();
            $loadingLocationsRow.hide();
            $codePositionRow.hide();
            $loadingConditionsWrapper.hide();
            updateManualTypeFields();
        } else {
            $manualOptions.hide();
            $actionFilterRow.hide();
            $loadingLocationsRow.show();
            $codePositionRow.show();
            $loadingConditionsWrapper.show();
        }
    });

    // Handle manual type change
    $manualType.on('change', updateManualTypeFields);

    function updateManualTypeFields() {
        var manualType = $manualType.val();

        if (manualType === 'action' || manualType === 'filter') {
            $actionFilterRow.show();
        } else {
            $actionFilterRow.hide();
        }

        if (manualType == 'shortcode') {
            $shortcodeRow.show();
        }
        else {
            $shortcodeRow.hide();
        }
    }

    $linkingType.on('change', function() {
        var type = $(this).val();

        $cssOptimizedLoadingRow.hide();
        $jsOptimizedLoadingRow.hide();

        if (type == 'external' && $codeType.val() == 'css') $cssOptimizedLoadingRow.show();
        if (type == 'external' && $codeType.val() == 'js') $jsOptimizedLoadingRow.show();
    });

    // Handle code type change
    $codeType.on('change', function () {
        var codeType = $(this).val();

        // Update metabox title
        var $metaboxTitle = $('.scc-code-content .hndle span');
        if ($metaboxTitle.length) {
            $metaboxTitle.text(codeType.toUpperCase() + ' Code Content');
        }

        // Show/hide linking type for HTML
        if (codeType === 'html') {
            $linkingTypeRow.hide();
        } else {
            $linkingTypeRow.show();
        }

        // Show/hide CSS preprocessor
        if (codeType === 'css') {
            $cssPreprocessorRow.show();
            $jsOptimizedLoadingRow.hide();

            if ($linkingType.val() == 'external')
                $cssOptimizedLoadingRow.show();
            else $cssOptimizedLoadingRow.hide();
        } else {
            $cssPreprocessorRow.hide();
            $cssOptimizedLoadingRow.hide();

            if (codeType == 'js') {
                $jsOptimizedLoadingRow.hide();
                if ($linkingType.val() == 'external') $jsOptimizedLoadingRow.show();
            }
            else {
                $jsOptimizedLoadingRow.hide();
            }
        }

        // Update file extension in permalink
        updateFileExtension(codeType);
    });

    function updateFileExtension(codeType) {
        var extension = '';
        switch (codeType) {
            case 'css':
                extension = '.css';
                break;
            case 'js':
                extension = '.js';
                break;
            case 'html':
                extension = '.html';
                break;
        }
        $('#scc-file-extension').text(extension);
    }

    // Permalink editing
    $('#scc-permalink-edit-button').on('click', function (e) {
        e.preventDefault();
        $('#scc-permalink-edit').css('display', 'flex');
        $(this).hide();
    });

    $('#scc-permalink-cancel').on('click', function (e) {
        e.preventDefault();
        $('#scc-permalink-edit').hide();
        $('#scc-permalink-edit-button').show();
    });

    $('#scc-permalink-save').on('click', function (e) {
        e.preventDefault();
        var newPermalink = $('#scc_custom_permalink').val();
        $('#scc-permalink-display').text(newPermalink);
        $('#scc-permalink-edit').hide();
        $('#scc-permalink-edit-button').show();
    });

    $('#scc-copy-shortcode').on('click', function (e) {
        e.preventDefault();

        let shortcode = $('#scc-shortcode-value').text();

        if ('clipboard' in navigator) {
            navigator.clipboard.writeText(shortcode)
                .then(() => {
                    console.log('Text copied');
                })
                .catch((err) => console.error(err.name, err.message));
        } else {
            const textArea = document.createElement('textarea');
            textArea.value = shortcode;
            textArea.style.opacity = 0;
            document.body.appendChild(textArea);
            textArea.focus();
            textArea.select();
            try {
                const success = document.execCommand('copy');
                console.log(`Text copy was ${success ? 'successful' : 'unsuccessful'}.`);
            } catch (err) {
                console.error(err.name, err.message);
            }
            document.body.removeChild(textArea);

        }
    });

    // Loading conditions management
    var conditionIndex = $('.scc-condition-row').length;

    $('#scc_add_condition').on('click', function (e) {
        e.preventDefault();

        var operator = $('#scc_new_condition_operator').val();
        var url = $('#scc_new_condition_url').val().trim();

        if (!url) {
            alert('Please enter a URL or pattern');
            return;
        }

        addConditionRow(operator, url, conditionIndex);
        conditionIndex++;

        // Clear inputs
        $('#scc_new_condition_url').val('');
    });

    function addConditionRow(operator, url, index) {
        var operatorOptions = '';
        $('#scc_new_condition_operator option').each(function () {
            var selected = $(this).val() === operator ? ' selected' : '';
            operatorOptions += '<option value="' + $(this).val() + '"' + selected + '>' + $(this).text() + '</option>';
        });

        var row = '<div class="scc-condition-row" data-index="' + index + '">' +
            '<select name="scc_conditions[' + index + '][operator]">' + operatorOptions + '</select> ' +
            '<input type="text" name="scc_conditions[' + index + '][url]" value="' + url + '" placeholder="URL or pattern" class="regular-text" /> ' +
            '<button type="button" class="button scc-remove-condition">' + ccmMetaboxes.strings.remove + '</button>' +
            '</div>';

        $('#scc_conditions_list').append(row);
    }

    // Remove condition
    $(document).on('click', '.scc-remove-condition', function (e) {
        e.preventDefault();

        if (confirm(ccmMetaboxes.strings.confirm_remove)) {
            $(this).closest('.scc-condition-row').remove();
        }
    });

    function scc_beautifyCode(cm) {
        let content = cm.getValue();
        let mode = cm.getOption("mode");
        let beautified = content;

        if (mode === "javascript") beautified = js_beautify(content, { indent_size: 4 });
        else if (mode === "css") beautified = css_beautify(content, { indent_size: 4 });
        else if (mode === "htmlmixed" || mode === "xml") beautified = html_beautify(content, { indent_size: 4 });

        cm.setValue(beautified);
    }

    // Initialize CodeMirror if available
    if (typeof CodeMirror !== 'undefined') {



        window.ccmCodeMirror = CodeMirror.fromTextArea(document.getElementById('scc-code-editor'), {
            lineNumbers: true,
            mode: 'css',
            theme: 'default',
            indentUnit: 4,
            lineWrapping: true,
            foldGutter: true,
            matchBrackets: true,
            autoCloseBrackets: true,
            matchTags: true,
            autoCloseTags: true,
            extraKeys: {
                'Ctrl-Space': 'autocomplete',
                'Cmd-Space': 'autocomplete',
                'Ctrl-J': 'toMatchingTag',
            },
            gutters: ["CodeMirror-linenumbers", "CodeMirror-foldgutter", "CodeMirror-lint-markers"]
        });

        if (codeLinting == '1') window.ccmCodeMirror.setOption('lint', true);

        if (codeTheme != '') window.ccmCodeMirror.setOption('theme', codeTheme);

        if (codeAutocomplete == '1') {
            window.ccmCodeMirror.on("keyup", function (cm, event) {
                if (!cm.state.completionActive && event.keyCode > 64 && event.keyCode < 91) {
                    CodeMirror.commands.autocomplete(cm, null, { completeSingle: false });
                }
            });
        }

        // Update CodeMirror mode based on code type
        $codeType.on('change', function () {
            var mode = 'css';
            switch ($(this).val()) {
                case 'js':
                    mode = 'javascript';
                    break;
                case 'html':
                    mode = 'htmlmixed';
                    break;
            }

            if (mode == 'css') {
                var preprocessor = $cssPreprocessor.val();
                if (preprocessor == 'scss') mode = 'text/x-scss';
                if (preprocessor == 'less') mode = 'text/x-less';
            }
            window.ccmCodeMirror.setOption('lint', false);
            window.ccmCodeMirror.setOption('mode', mode);

            if (codeLinting == '1')
                window.ccmCodeMirror.setOption('lint', mode == 'javascript' ? { options: { esversion: 2021 } } : true);
        });

        $cssPreprocessor.on('change', function () {
            var mode = 'css';

            var preprocessor = $(this).val();
            if (preprocessor == 'scss') mode = 'text/x-scss';
            if (preprocessor == 'less') mode = 'text/x-less';
            window.ccmCodeMirror.setOption('lint', false);
            window.ccmCodeMirror.setOption('mode', mode);

            if (codeLinting == '1') window.ccmCodeMirror.setOption('lint', true);
        });

        $('#formatCodeBtn').on('click', function (e) {
            e.preventDefault();
            scc_beautifyCode(window.ccmCodeMirror);
        });
    }

    // Trigger initial field updates
    $loadingMethod.trigger('change');
    $codeType.trigger('change');
});