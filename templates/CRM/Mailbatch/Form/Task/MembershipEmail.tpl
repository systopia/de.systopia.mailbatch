{*-------------------------------------------------------+
| SYSTOPIA MailBatch Extension                           |
| Copyright (C) 2023 SYSTOPIA                            |
| Author: J. Schuppe (schuppe@systopia.de)               |
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
  <div class="crm-block crm-form-block">
      {if $no_email_count}
        <div id="help">
          <strong>{ts}Warning:{/ts}</strong>
            {ts 1=$no_email_count}%1 membership(s) belong to a contact that has no viable e-mail address, e-mail will not be sent for those memberships.{/ts}
        </div>
      {/if}

    <div class="crm-accordion-wrapper">
      <div class="crm-accordion-header">{ts}Mailing Properties{/ts}</div>
      <div class="crm-accordion-body">

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

        <div class="crm-section">
          <div class="label">{$form.location_type_id.label}</div>
          <div class="content">{$form.location_type_id.html}</div>
          <div class="clear"></div>
        </div>

      </div>
    </div>

    <div class="crm-accordion-wrapper">
      <div class="crm-accordion-header">{ts}Content{/ts}</div>
      <div class="crm-accordion-body">

        <div class="crm-section">
          <div class="label">{$form.template_id.label}</div>
          <div class="content">{$form.template_id.html}</div>
          <div class="clear"></div>
        </div>

      </div>
    </div>

    {if !empty($supports_attachments)}
      <div class="crm-accordion-wrapper">
        <div class="crm-accordion-header">{ts}Attachments{/ts}</div>
        <div class="crm-accordion-body">

          <div class="crm-section">
            <div class="label">{$form.send_wo_attachment.label}
              &nbsp;{help id="id-no-attachment" title=$form.send_wo_attachment.label}</div>
            <div class="content">{$form.send_wo_attachment.html}</div>
            <div class="clear"></div>
          </div>

            {include file="Civi/Mailattachment/Form/Attachments.tpl"}

        </div>
      </div>
    {else}
      <div class="help">
          {capture assign="mailattachment_link"}<a href="https://github.com/systopia/de.systopia.mailattachment">Mail Attachments</a>{/capture}
        <p>{ts 1=$mailattachment_link}If you would like to add file attachments to e-mails, consider installing the %1 extension which provides a framework for different attachment types.{/ts}</p>
        <p>{ts}This includes e.g. attaching existing files per contact or membership.{/ts}</p>
      </div>
    {/if}

    <div class="crm-accordion-wrapper">
      <div class="crm-accordion-header">{ts}Activities{/ts}</div>
      <div class="crm-accordion-body">

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

          {if !empty($form.failed_activity_subject2)}
            <div class="crm-section mailbatch-failed-section">
              <div class="label">{$form.failed_activity_subject2.label}
                &nbsp;{help id="id-failed-no-email" title=$form.attachment1_path.label}</div>
              <div class="content">{$form.failed_activity_subject2.html}</div>
              <div class="clear"></div>
            </div>
          {/if}

        <div class="crm-section mailbatch-failed-section">
          <div class="label">{$form.failed_activity_assignee.label}</div>
          <div class="content">{$form.failed_activity_assignee.html}</div>
          <div class="clear"></div>
        </div>

        <div class="crm-section">
          <div class="label">{$form.activity_grouped.label}</div>
          <div class="content">{$form.activity_grouped.html}</div>
          <div class="clear"></div>
        </div>

      </div>
    </div>

      {if $no_email_count}
        <div id="help">
          <strong>{ts}Warning:{/ts}</strong>
            {ts 1=$no_email_count}%1 membership(s) belong to a contact that has no viable e-mail address, e-mail will not be sent for those memberships.{/ts}
        </div>
      {/if}
    <div class="crm-submit-buttons">
        {include file="CRM/common/formButtons.tpl" location="bottom"}
    </div>
  </div>
{/crmScope}

{literal}
  <script>
    cj(document).ready(function () {
      // add logic to sent_activity
      cj("[name=sent_activity_type_id]").change(function () {
        let active = cj("[name=sent_activity_type_id]").val();
        if (active) {
          cj("div.mailbatch-sent-section").show();
        }
        else {
          cj("div.mailbatch-sent-section").hide();
        }
      });
      cj("[name=sent_activity_type_id]").change();

      // add logic to failed_activity
      cj("[name=failed_activity_type_id]").change(function () {
        let active = cj("[name=failed_activity_type_id]").val();
        if (active) {
          cj("div.mailbatch-failed-section").show();
        }
        else {
          cj("div.mailbatch-failed-section").hide();
        }
      });
      cj("[name=failed_activity_type_id]").change();

      // add logic to location type change: submit to update stats
      cj("[name=location_type_id]").change(function () {
        cj("[id^=_qf_MembershipEmail_refresh]").click();
      });
    });
  </script>
{/literal}
