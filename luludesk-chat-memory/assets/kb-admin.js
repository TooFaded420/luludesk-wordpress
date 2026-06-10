/**
 * LuluDesk Knowledge Base admin JS.
 * Handles: save-credentials button, REST API check.
 *
 * Loaded only on Settings → LuluDesk page via LuluDesk_KB_Settings::enqueue_scripts().
 * Requires: ludeskKbAdmin global (set via wp_localize_script).
 *
 * Note: credentials are generated in the LuluDesk dashboard (Clerk-gated) and
 * pasted here — the plugin does NOT call /connect or /full-sync server-to-server,
 * because both require an authenticated dashboard session.
 */
/* global ludeskKbAdmin, jQuery */
(function ($) {
  'use strict';

  $(document).ready(function () {
    var cfg = window.ludeskKbAdmin || {};
    var ajaxurl = cfg.ajaxurl || '';
    var nonce = cfg.nonce || '';
    var i18n = cfg.i18n || {};

    // ── REST API accessibility check ────────────────────────────────────────
    // Run silently on page load; show warning banner if REST API appears off.
    $.post(ajaxurl, {
      action: 'luludesk_kb_check_rest',
      nonce: nonce,
    }, function (response) {
      if (response && response.success && response.data && !response.data.accessible) {
        $('#luludesk-rest-api-warning').show();
      }
    });

    // ── Save credentials button ─────────────────────────────────────────────
    $('#luludesk-save-credentials-btn').on('click', function () {
      var $btn = $(this);
      var $result = $('#luludesk-connect-result');
      var apiKey = $.trim($('#luludesk-kb-api-key').val() || '');
      var sourceId = $.trim($('#luludesk-kb-source-id').val() || '');

      if (!apiKey || !sourceId) {
        $result.css('color', 'red').text(i18n.invalid || 'Both fields are required.');
        return;
      }

      $btn.prop('disabled', true).text(i18n.saving || 'Saving…');
      $result.text('').css('color', '');

      $.post(ajaxurl, {
        action: 'luludesk_kb_save_credentials',
        nonce: nonce,
        wp_api_key: apiKey,
        kb_source_id: sourceId,
      }, function (response) {
        $btn.prop('disabled', false).text('Save connection');

        if (response && response.success) {
          $result.css('color', 'green').text(i18n.save_ok || 'Credentials saved.');
          // Reload so the connected state renders correctly.
          setTimeout(function () { window.location.reload(); }, 1200);
        } else {
          var msg = (response && response.data && response.data.message)
            ? response.data.message
            : (i18n.save_fail || 'Could not save credentials.');
          $result.css('color', 'red').text(msg);
        }
      }).fail(function () {
        $btn.prop('disabled', false).text('Save connection');
        $result.css('color', 'red').text(i18n.save_fail || 'Could not save credentials.');
      });
    });
  });
}(jQuery));
