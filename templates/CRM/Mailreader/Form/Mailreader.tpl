{if $warning != ''}
  {$warning}
{else}
  <fieldset>
    <div class="crm-element">{$form.limit_to.label}{$form.limit_to.html} {ts}results on page{/ts}</div>
    <div class="crm-element">{$form.show_last.label}{$form.show_last.html}</div>
  </fieldset>
    <div class="crm-submit-buttons">{include file="CRM/common/formButtons.tpl" location="top"}</div>
  <fieldset>
    <legend>{ts}Stored emails{/ts}</legend>
    <table class="form-layout">
      <tr class="columnheader">
        <th>Select</th>
        <th>ID</th>
        <th>Job ID</th>
        <th>recipient email</th>
        <th>headers</th>
        <th>Body</th>
        <th>Added at</th>
        <th>Removed at</th>
      </tr>
      {foreach from=$records item=row}
        <tr class="{cycle values="odd-row,even-row"}">
          <td><input type="radio" class="crm-form-radio api-param-radio api-input" id="mailreader-checkbox" name="mailreader_email_id" value="{$row.id}" data-value="{$row.id}"></td>
          <td>{$row.id}</td>
          <td>{$row.job_id}</td>
          <td>{$row.recipient_email}</td>
          <td>
            <div class="crm-accordion-wrapper collapsed">
              <div class="crm-accordion-header">{ts}Message Headers - Click to expand{/ts}</div>
              <div class="crm-accordion-body">
                <div class="crm-block crm-form-block crm-form-msg-headers-form-block">
                  {$row.headers|htmlize}
                </div>
              </div>
            </div>
          </td>
          <td>
            <div class="crm-accordion-wrapper collapsed">
              <div class="crm-accordion-header">{ts}Message Body - Click to expand{/ts}</div>
              <div class="crm-accordion-body">
                <div class="crm-block crm-form-block crm-form-msg-body-form-block">
                  {$row.body|htmlize}
                </div>
              </div>
            </div>
          </td>
          <td>{$row.added_at}</td>
          <td>{$row.removed_at}</td>
        </tr>
      {/foreach}
    </table>
  </fieldset>


  {* FOOTER *}
  <div class="crm-submit-buttons">
    {include file="CRM/common/formButtons.tpl" location="bottom"}
  </div>

  {literal}
  <script type="text/javascript">
    CRM.$(function($) {
      $('input[id=_qf_Mailreader_submit_delete_all-top]').click(function(event) {
        if (confirm(ts("You are about to delete ALL database rows of table 'civicrm_mailing_spool'? This action cannot be undone! If you agree, click OK to continue"))){
          $('input[id=_qf_Mailreader_submit_delete_all-top]').submit();
        } else {
           event.preventDefault();
        }
      });
    });
  </script>
  {/literal}

{/if}
