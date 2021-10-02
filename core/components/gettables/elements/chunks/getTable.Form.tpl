<{$form.tag} action="" method="post" class="gts-getform">
    <input type="hidden" name="hash" value="{$form.hash}">
    <input type="hidden" name="table_name" value="{$form.name}">
    <div class="form-body">
      {foreach $form.edits as $edit}
          {$edit.form_content}
      {/foreach}
    </div>
    <div class="form-footer">
      <button type="submit" name="gts_action" value="{$form.action}" class="btn btn-primary">{'gettables_save' | lexicon}</button>
    </div>
</{$form.tag}>