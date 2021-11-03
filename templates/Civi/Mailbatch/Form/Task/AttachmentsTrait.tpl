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
          {foreach from=$attachment_elements item=attachment_element}
            <tr class="crm-mailbatch-attachment">
              <td>
                <div class="crm-section">
                  <div class="label">{$form.$attachment_element.label}</div>
                  <div class="content">{$form.$attachment_element.html}</div>
                  <div class="clear"></div>
                </div>
              </td>
            </tr>
          {/foreach}
          </tbody>
      </table>

      {$form.attachments_more.html}
  </div>
{/crmScope}
