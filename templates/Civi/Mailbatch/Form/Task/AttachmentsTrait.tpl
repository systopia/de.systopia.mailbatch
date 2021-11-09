{*-------------------------------------------------------+
| SYSTOPIA Event Messages                                |
| Copyright (C) 2021 SYSTOPIA                            |
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
  <div id="crm-mailbatch-attachments-wrapper">

      <table class="crm-mailbatch-attachments-table row-highlight">
          <tbody>
          {foreach from=$attachments item="attachment_elements" key="attachment_id"}
            <tr class="crm-mailbatch-attachment">

              <td>
                  {foreach from=$attachment_elements key="attachment_element" item="attachment_element_type"}
                    <div class="crm-section">
                      <div class="label">
                          {$form.$attachment_element.label}
                          {capture assign="help_id"}id-{$attachment_element_type}{/capture}
                          {help id=$help_id title=$form.$attachment_element.label}
                      </div>
                      <div class="content">{$form.$attachment_element.html}</div>
                      <div class="clear"></div>
                    </div>
                  {/foreach}
              </td>

              <td>
                  {capture assign="attachment_remove_button_name"}attachments--{$attachment_id}_remove{/capture}
                  {$form.$attachment_remove_button_name.html}
              </td>

            </tr>
          {/foreach}
          </tbody>
      </table>

      {$form.attachments_more_type.html}
      {$form.attachments_more.html}
  </div>
{/crmScope}
