<form action="" method="post" class="gts-getform {$form.cls}" data-hash="{$form.hash}">
    <input type="hidden" name="hash" value="{$form.hash}">
    <input type="hidden" name="form_name" value="{$form.name}">
    <div class="form-body">
      {if $form.tabs}
        <ul class="nav nav-tabs" id="myTab" role="tablist">
          {foreach $form.tabs as $tab}
              <li class="nav-item">
                <a class="nav-link {$tab.active}" data-toggle="tab" href="#{$tab.name}" role="tab" aria-controls="{$tab.name}">{$tab.label}</a>
              </li>
          {/foreach}
        </ul>
        <div class="tab-content" id="myTabContent">
          {foreach $form.tabs as $k=>$tab}
              <div id="{$tab.name}" class="tab-pane fade {if $tab.active}show{/if} {$tab.active}" role="tabpanel" aria-labelledby="{$tab.name}">
                {foreach $tab.fields as $field}
                  {$form.edits[$field].form_content}
                {/foreach}
              </div>
          {/foreach}
        </div>
      {else}
        {foreach $form.edits as $edit}
            {$edit.form_content}
        {/foreach}
      {/if}
    </div>
    <div class="form-footer">
      {foreach $form.buttons as $button}
          <button type="submit" name="gts_action" value="{$button.action}" class="btn btn-primary btn-gts-getform">{$button.lexicon | lexicon}</button>
      {/foreach}
    </div>
</form>