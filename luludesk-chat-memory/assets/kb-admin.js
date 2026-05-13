/**
 * LuluDesk Knowledge Base admin JS.
 * Handles: connect button, sync button, REST API check.
 *
 * Loaded only on Settings → LuluDesk page via LuluDesk_KB_Settings::enqueue_scripts().
 * Requires: ludeskKbAdmin global (set via wp_localize_script).
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

    // ── Connect button ──────────────────────────────────────────────────────
    $('#luludesk-connect-btn').on('click', function () {
      var $btn = $(this);
      var $result = $('#luludesk-connect-result');

      $btn.prop('disabled', true).text(i18n.connecting || 'Connecting…');
      $result.text('').css('color', '');

      $.post(ajaxurl, {
        action: 'luludesk_kb_connect',
        nonce: nonce,
      }, function (response) {
        $btn.prop('disabled', false).text('Connect to LuluDesk Knowledge Base');

        if (response && response.success) {
          $result.css('color', 'green').text(i18n.connect_ok || 'Connected!');
          // Reload the page so the connected state renders correctly.
          setTimeout(function () { window.location.reload(); }, 1500);
        } else {
          var msg = (response && response.data && response.data.message)
            ? response.data.message
            : (i18n.connect_fail || 'Connection failed.');
          $result.css('color', 'red').text(msg);
        }
      }).fail(function () {
        $btn.prop('disabled', false);
        $result.css('color', 'red').text(i18n.connect_fail || 'Connection failed.');
      });
    });

    // ── Sync now button ─────────────────────────────────────────────────────
    $('#luludesk-sync-btn').on('click', function () {
      var $btn = $(this);
      var $result = $('#luludesk-sync-result');

      $btn.prop('disabled', true).text(i18n.syncing || 'Syncing…');
      $result.text('').css('color', '');

      $.post(ajaxurl, {
        action: 'luludesk_kb_sync',
        nonce: nonce,
      }, function (response) {
        $btn.prop('disabled', false).text('Sync now');

        if (response && response.success) {
          var d = response.data || {};
          var msg = (i18n.sync_ok || 'Sync complete.')
            + ' Pages: ' + (d.pages_synced || 0)
            + ', Chunks: ' + (d.chunks_upserted || 0);
          $result.css('color', 'green').text(msg);
          $('#luludesk-last-sync-label').text('just now');
        } else {
          var errMsg = (response && response.data && response.data.message)
            ? response.data.message
            : (i18n.sync_fail || 'Sync failed.');
          $result.css('color', 'red').text(errMsg);
        }
      }).fail(function () {
        $btn.prop('disabled', false);
        $result.css('color', 'red').text(i18n.sync_fail || 'Sync failed.');
      });
    });
  });
}(jQuery));
