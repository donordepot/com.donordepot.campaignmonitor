<div class="crm-block crm-form-block crm-campaignmonitor-setting-form-block">
  <!--
  <div class="crm-error">
    {ts}You'll need to get an <a href="http://www.campaignmonitor.com/api/getting-started#apikey">API Key</a> from Campaign Monitor for these settings.{/ts} 
  </div>
  -->
  <div class="crm-block crm-form-block crm-campaignmonitor-setting-form-block">
    <fieldset class="form-wrapper">
      <legend><span>{ts}API Key{/ts}</span></legend>
      <div class="fieldset-wrapper">
        <div class="form-item form-type-textfield api_key clearfix">
          <div class="label">
            {$form.api_key.label}
          </div>
          <div class="input">
            {$form.api_key.html}
          </div>
          <div class="description">
            {ts}<a href="http://www.campaignmonitor.com/api/getting-started#apikey">API Key</a> from Campaign Monitor{/ts}
          </div>
        </div>
      </div>
    </fieldset>
    <fieldset class="form-wrapper">
      <legend><span>Groups</span></legend>
      <div class="fieldset-wrapper">
        <div class="description">
          {ts}Groups that should by Synced with Campaign Monitor Lists.{/ts}
        </div>
        {foreach from=$form.groups item=group}
          {if $group|is_array}
            <div class="form-item form-type-checkbox clearfix">
              <div class="input-label">
                {$group.html} {$group.label}
              </div>
            </div>
          {/if}
        {/foreach}
      </div>
    </fieldset>
    <div class="crm-submit-buttons">
      {include file="CRM/common/formButtons.tpl"}
    </div>
  </div>
</div>
