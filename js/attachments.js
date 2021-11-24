/*-------------------------------------------------------+
| SYSTOPIA MailBatch Extension                           |
| Copyright (C) 2021 SYSTOPIA                            |
| Author: J. Schuppe (schuppe@systopia.de)               |
| http://www.systopia.de/                                |
+--------------------------------------------------------+
| This program is released as free software under the    |
| Affero GPL license. You can redistribute it and/or     |
| modify it under the terms of this license which you    |
| can read by viewing the included agpl.txt or online    |
| at www.gnu.org/licenses/agpl.html. Removal of this     |
| copyright header is strictly prohibited without        |
| written permission from the original author(s).        |
+--------------------------------------------------------*/
(function($) {

  $.urlParam = function (name) {
    var results = new RegExp('[\?&]' + name + '=([^&#]*)')
      .exec(window.location.search);

    return (results !== null) ? results[1] || 0 : false;
  };

  var mailbatchBehavior = function() {
    if (this == document) {
      var $form = $(this).find('form.crm-mailbatch-attachments-form');
    }
    else {
      var $form = $(this).closest('form.crm-mailbatch-attachments-form');
    }
    var $attachmentsWrapper = $form.find('#crm-mailbatch-attachments-wrapper');
    $attachmentsWrapper
      .css('position', 'relative')
      .append(
        $('<div>')
          .hide()
          .addClass('loading-overlay')
          .css({
            backgroundColor: 'rgba(255, 255, 255, 0.5)',
            position: 'absolute',
            top: 0,
            right: 0,
            bottom: 0,
            left: 0
          })
          .append(
            $('<div>')
              .addClass('crm-loading-element')
              .css({
                position: 'absolute',
                left: '50%',
                top: '50%',
                marginLeft: '-15px',
                marginTop: '-15px'
              })
          )
      );

    $('#attachments_more', $attachmentsWrapper)
      .on('click', function() {
        var urlSearchparams = new URLSearchParams(window.location.search);
        urlSearchparams.append('ajax_action', 'add_attachment');
        var postValues = {
          qfKey: $form.find('[name="qfKey"]').val(),
          ajax_context: 'attachments',
          ajax_action: 'add_attachment',
          ajax_attachment_type: $attachmentsWrapper.find('[name="attachments_more_type"]').val(),
          snippet: 6
        };
        var $currentAttachments = $attachmentsWrapper.find('[name^="attachments--"]');
        $currentAttachments.each(function() {
          postValues[$(this).attr('name')] = $(this).val();
        });

        $attachmentsWrapper.find('.loading-overlay').show();

        // Retrieve the form with another attachment field.
        $.post(
          CRM.url(
            location.pathname.substr(1),
            location.search.substr(1)
          ),
          postValues,
          function(data) {
            $attachmentsWrapper
              .replaceWith($(data.content)
                .find('#crm-mailbatch-attachments-wrapper')
                .each(mailbatchBehavior)
              );
          }
        );
      });

    $('.crm-mailbatch-attachment-remove', $attachmentsWrapper)
      .on('click', function() {
        var urlSearchparams = new URLSearchParams(window.location.search);
        urlSearchparams.append('ajax_action', 'remove_attachment');
        var postValues = {
          qfKey: $form.find('[name="qfKey"]').val(),
          ajax_context: 'attachments',
          ajax_action: 'remove_attachment',
          ajax_attachment_id: $(this).data('attachment_id'),
          snippet: 6
        };
        var $currentAttachments = $attachmentsWrapper.find('[name^="attachments--"]');
        $currentAttachments.each(function() {
          postValues[$(this).attr('name')] = $(this).val();
        });

        $attachmentsWrapper.find('.loading-overlay').show();

        // Retrieve the form with another attachment field.
        $.post(
          CRM.url(
            location.pathname.substr(1),
            location.search.substr(1)
          ),
          postValues,
          function(data) {
            $attachmentsWrapper
              .replaceWith($(data.content)
                .find('#crm-mailbatch-attachments-wrapper')
                .each(mailbatchBehavior)
              );
          }
        );
      });
  };

  $(document).ready(mailbatchBehavior);

})(CRM.$ || cj);
