<div class="modal fade gts_modal" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true">
  <div class="modal-dialog" role="document">
    <div class="modal-content">
      <form action="" method="post" class="gts-form">
          <input type="hidden" name="hash" value="{$modal.hash}">
		  <input type="hidden" name="table_name" value="{$modal.table_name}">
		  
          <div class="modal-header">
            <button type="button" class="close" data-dismiss="modal" aria-label="Закрыть">
              <span aria-hidden="true">×</span>
            </button>
            <h4 class="modal-title" id="myModalLabel">{$modal.title}</h4>
          </div>
          <div class="modal-body">
            {foreach $modal.edits as $edit}
                {switch $edit.type}
                    {case 'hidden'}
                        <input type="hidden" id="{$edit.field}" name="{$edit.field}" value="{$edit.value}"/>
                    {case 'view'}
                        <input type="text" data-field="{$edit.field}" value="{$edit.value}" placeholder="{$edit.placeholder}" class="form-control disabled"/>
                    {case 'text'}
                        <div class="form-group">
                            <label class="control-label" for="{$edit.field}">{$edit.label}</label>
                            <div class="controls">
                                <input type="text" id="{$edit.field}" name="{$edit.field}" value="{$edit.value}" placeholder="{$edit.placeholder}" class="form-control"/>
                                <span class="error_{$edit.field}"></span>
                            </div>
                        </div>
                    {case 'checkbox'}
                        <label>
                            <input type="checkbox" class="get-table-checkbox-hidden" {if $edit.value} checked{/if}>
                            <input type="hidden" value="{$edit.value}" data-field="{$edit.field}" name="{$edit.field}"/>
                            {$edit.label}
                        </label>
                    {case 'select'}
                        <div class="form-group">
                            <label class="control-label" for="{$edit.field}">{$edit.label}</label>
                            <div class="controls">
                                <select data-field="{$edit.field}" name="{$edit.field}" data-value="{$edit.value}" placeholder="{$edit.placeholder}" class="form-control get-table-autosave">
                                    <option value="">Выберете {$edit.placeholder}</option>
                                    {foreach $edit.select.data as $d}
                                        <option value="{$d.id}" {if $edit.value == $d.id}selected{/if} >{$d.content}</option>
                                    {/foreach}
                                </select>
                            </div>
                        </div>
                {/switch}
            {/foreach}
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-default" data-dismiss="modal">Закрыть</button>
            <button type="submit" name="gts_action" value="{$modal.table_action}" class="btn btn-primary">Сохранить изменения</button>
          </div>
      </form>
    </div>
  </div>
</div>  

