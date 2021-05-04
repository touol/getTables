<div class="modal fade gts_modal" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true">
  <div class="modal-dialog" role="document">
    <div class="modal-content">
      <form action="" method="post" class="gts-form">
          <input type="hidden" name="hash" value="{$modal.hash}">
          <input type="hidden" name="table_name" value="{$modal.table_name}">
          {if $modal.sub_where_current}
                <input type="hidden" name="sub_where_current" value='{$modal.sub_where_current}'>
          {/if}
          {if $modal.parent_current}
               <input type="hidden" name="parent_current" value='{$modal.parent_current}'>
          {/if}
          <div class="modal-header">
            <button type="button" class="close" data-dismiss="modal" aria-label="{'gettables_close' | lexicon}">
              <span aria-hidden="true">×</span>
            </button>
            <h4 class="modal-title" id="myModalLabel">{$modal.title}</h4>
          </div>
          <div class="modal-body">
            {foreach $modal.edits as $edit}
                {$edit.modal_content}
            {/foreach}
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-default" data-dismiss="modal">{'gettables_close' | lexicon}</button>
            <button type="submit" name="gts_action" value="{$modal.table_action}" class="btn btn-primary">{'gettables_save' | lexicon}</button>
          </div>
      </form>
    </div>
  </div>
</div>