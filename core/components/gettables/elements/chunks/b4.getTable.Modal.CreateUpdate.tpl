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
            <h4 class="modal-title">{$modal.title}</h4>
            <button type="button" class="close" data-dismiss="modal" aria-label="{'gettables_close' | lexicon}">
              <span aria-hidden="true">&times;</span>
            </button>
            
          </div>
          <div class="modal-body">
            {if $modal.tabs}
              <ul class="nav nav-tabs" id="myTab" role="tablist">
                {foreach $modal.tabs as $tab}
                    <li class="nav-item">
                      <a class="nav-link {$tab.active}" data-toggle="tab" href="#{$tab.name}" role="tab" aria-controls="{$tab.name}">{$tab.label}</a>
                    </li>
                {/foreach}
              </ul>
              <div class="tab-content" id="myTabContent">
                {foreach $modal.tabs as $k=>$tab}
                    <div id="{$tab.name}" class="tab-pane fade {if $tab.active}show{/if} {$tab.active}" role="tabpanel" aria-labelledby="{$tab.name}">
                      {foreach $tab.fields as $field}
                        {$modal.edits[$field].modal_content}
                      {/foreach}
                    </div>
                {/foreach}
              </div>
            {else}
              {foreach $modal.edits as $edit}
                  {$edit.modal_content}
              {/foreach}
            {/if}
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-default" data-dismiss="modal">{'gettables_close' | lexicon}</button>
            <button type="submit" name="gts_action" value="{$modal.table_action}" class="btn btn-primary">{'gettables_save' | lexicon}</button>
          </div>
      </form>
    </div>
  </div>
</div>