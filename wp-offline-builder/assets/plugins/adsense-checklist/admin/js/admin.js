(function ($) {
    'use strict';

    // Run Scan
    $(document).on('click', '#adsc-scan', function () {
        var $btn = $(this);
        var $status = $('#adsc-scan-status');
        $btn.prop('disabled', true);
        $status.text(' ' + (AdSenseChecklist.strings.scanning || 'Scanning…'));

        $.post(AdSenseChecklist.ajaxUrl, {
            action: 'adsense_checklist_scan',
            nonce: AdSenseChecklist.nonce
        })
            .done(function () { window.location.reload(); })
            .fail(function () {
                $btn.prop('disabled', false);
                $status.text(' ' + (AdSenseChecklist.strings.failed || 'Scan failed. Check the WP error log.'));
            });
    });

    // Resolve dropdown - optimistic update, rollback on failure
    $(document).on('change', '.adsc-status', function () {
        var $select = $(this);
        var $finding = $select.closest('[data-finding-id]');
        var id = $finding.data('finding-id');
        var previous = $select.data('previous') || $select.find('option:not(:selected)').first().val() || 'pending';
        var next = $select.val();
        $finding.addClass('adsc-resolving');

        $.post(AdSenseChecklist.ajaxUrl, {
            action: 'adsense_checklist_resolve',
            nonce: AdSenseChecklist.nonce,
            finding_id: id,
            status: next
        })
            .done(function () {
                // Reload so the finding moves into the correct group server-side
                // (bucketing happens in report.php at render time).
                // Class toggles below stay as brief visual feedback during the reload flash.
                $select.data('previous', next);
                $finding.toggleClass('adsc-finding--resolved', next === 'resolved');
                $finding.toggleClass('adsc-finding--ignored',  next === 'ignored');
                window.location.reload();
            })
            .fail(function () {
                $select.val(previous);
                $finding.removeClass('adsc-resolving');
            });
    });
    // Record initial state per select so rollback knows what to revert to
    $('.adsc-status').each(function () { $(this).data('previous', $(this).val()); });

    // Passing checks toggle
    $(document).on('click', '#adsc-passing-toggle', function (e) {
        e.preventDefault();
        var $list = $('#adsc-passing-list');
        var hidden = $list.prop('hidden');
        $list.prop('hidden', !hidden);
        $(this).text(hidden ? 'Hide' : 'View all');
    });
})(jQuery);
