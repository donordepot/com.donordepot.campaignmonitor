<div class="crm-block crm-form-block crm-campaignmonitor-setting-form-block">
  <div id="help">
    {ts}You'll need to get an <a href="http://www.campaignmonitor.com/api/getting-started#apikey">API Key</a> from Campaign Monitor for these settings.{/ts} 
  </div>
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
    <div class="crm-submit-buttons">
      {include file="CRM/common/formButtons.tpl"}
    </div>
  </div>
</div>
