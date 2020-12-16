{*-------------------------------------------------------+
| SYSTOPIA Event Messages                                |
| Copyright (C) 2020 SYSTOPIA                            |
| Author: B. Endres (endres@systopia.de)                 |
+--------------------------------------------------------+
| This program is released as free software under the    |
| Affero GPL license. You can redistribute it and/or     |
| modify it under the terms of this license which you    |
| can read by viewing the included agpl.txt or online    |
| at www.gnu.org/licenses/agpl.html. Removal of this     |
| copyright header is strictly prohibited without        |
| written permission from the original author(s).        |
+-------------------------------------------------------*}

{crmScope extensionKey='de.systopia.mailbatch'}
{if $no_email_count}
  <div id="help">{ts 1=$no_email_count}<b>Warning:</b> %1 contact(s) have no viable email address, an email will not be sent to them{/ts}</div>
{/if}

  <h3>{ts}Mailing Properties{/ts}</h3><br/>

  <div class="crm-section">
    <div class="label">{$form.sender_email.label}</div>
    <div class="content">{$form.sender_email.html}</div>
    <div class="clear"></div>
  </div>

  <div class="crm-section">
    <div class="label">{$form.sender_cc.label}</div>
    <div class="content">{$form.sender_cc.html}</div>
    <div class="clear"></div>
  </div>

  <div class="crm-section">
    <div class="label">{$form.sender_bcc.label}</div>
    <div class="content">{$form.sender_bcc.html}</div>
    <div class="clear"></div>
  </div>

  <div class="crm-section">
    <div class="label">{$form.sender_reply_to.label}</div>
    <div class="content">{$form.sender_reply_to.html}</div>
    <div class="clear"></div>
  </div>

  <div class="crm-section">
    <div class="label">{$form.batch_size.label}</div>
    <div class="content">{$form.batch_size.html}</div>
    <div class="clear"></div>
  </div>


  <h3>{ts}Content{/ts}</h3><br/>

  <div class="crm-section">
    <div class="label">{$form.template_id.label}</div>
    <div class="content">{$form.template_id.html}</div>
    <div class="clear"></div>
  </div>


  <div class="crm-section">
    <div class="label">{$form.send_wo_attachment.label}&nbsp;{help id="id-no-attachment" title=$form.send_wo_attachment.label}</div>
    <div class="content">{$form.send_wo_attachment.html}</div>
    <div class="clear"></div>
  </div>

  <div class="crm-section">
    <div class="label">{$form.attachment1_path.label}&nbsp;{help id="id-attachment-path" title=$form.attachment1_path.label}</div>
    <div class="content">{$form.attachment1_path.html}</div>
    <div class="clear"></div>
  </div>

  <div class="crm-section">
    <div class="label">{$form.attachment1_name.label}&nbsp;{help id="id-attachment-name" title=$form.attachment1_name.label}</div>
    <div class="content">{$form.attachment1_name.html}</div>
    <div class="clear"></div>
  </div>


  <h3>{ts}Activities{/ts}</h3><br/>

  <div class="crm-section">
    <div class="label">{$form.sent_activity_type_id.label}</div>
    <div class="content">{$form.sent_activity_type_id.html}</div>
    <div class="clear"></div>
  </div>

  <div class="crm-section mailbatch-sent-section">
    <div class="label">{$form.sent_activity_subject.label}</div>
    <div class="content">{$form.sent_activity_subject.html}</div>
    <div class="clear"></div>
  </div>

  <div class="crm-section">
    <div class="label">{$form.failed_activity_type_id.label}</div>
    <div class="content">{$form.failed_activity_type_id.html}</div>
    <div class="clear"></div>
  </div>

  <div class="crm-section mailbatch-failed-section">
    <div class="label">{$form.failed_activity_subject.label}</div>
    <div class="content">{$form.failed_activity_subject.html}</div>
    <div class="clear"></div>
  </div>

  <br>
  <div class="crm-submit-buttons">
      {include file="CRM/common/formButtons.tpl" location="bottom"}
  </div>
{/crmScope}

{literal}
<script>
  cj(document).ready(function() {
    // add logic to sent_activity
    cj("[name=sent_activity_type_id]").change(function() {
      let active = cj("[name=sent_activity_type_id]").val();
      if (active) {
        cj("div.mailbatch-sent-section").show();
      } else {
        cj("div.mailbatch-sent-section").hide();
      }
    });
    cj("[name=sent_activity_type_id]").change();

    // add logic to failed_activity
    cj("[name=failed_activity_type_id]").change(function() {
      let active = cj("[name=failed_activity_type_id]").val();
      if (active) {
        cj("div.mailbatch-failed-section").show();
      } else {
        cj("div.mailbatch-failed-section").hide();
      }
    });
    cj("[name=failed_activity_type_id]").change();
  });
</script>
{/literal}