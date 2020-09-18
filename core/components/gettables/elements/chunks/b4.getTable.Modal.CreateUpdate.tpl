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
                    {case 'view','modal_view'}
                        <div class="form-group">
                            <label class="control-label" for="{$edit.field}">{$edit.label}</label>
                            <div class="controls">
                                <input type="text" data-field="{$edit.field}" value="{$edit.value}" placeholder="{$edit.placeholder}" class="form-control" disabled/>
                            </div>
                        </div>
                    {case 'disabled'}
                        <div class="form-group">
                            <label class="control-label" for="{$edit.field}">{$edit.label}</label>
                            <div class="controls">
                                <input type="text" data-field="{$edit.field}" name="{$edit.field}" value="{$edit.value}" placeholder="{$edit.placeholder}" class="form-control " disabled/>
                            </div>
                        </div>
                    {case 'readonly'}
                        <div class="form-group">
                            <label class="control-label" for="{$edit.field}">{$edit.label}</label>
                            <div class="controls">
                                <input type="text" data-field="{$edit.field}" name="{$edit.field}" value="{$edit.value}" placeholder="{$edit.placeholder}" class="form-control " readonly/>
                            </div>
                        </div>
                    {case 'text'}
                        <div class="form-group">
                            <label class="control-label" for="{$edit.field}">{$edit.label}</label>
                            <div class="controls">
                                <input type="text" id="{$edit.field}" name="{$edit.field}" value="{$edit.value}" placeholder="{$edit.placeholder}" class="form-control"
                                {if $edit.readonly}readonly{/if}/>
                                <span class="error_{$edit.field}"></span>
                            </div>
                        </div>
                    {case 'decimal'}
                        <div class="form-group">
                            <label class="control-label" for="{$edit.field}">{$edit.label}</label>
                            <div class="controls">
                                <input type="number" step="0.01"  id="{$edit.field}" name="{$edit.field}" value="{$edit.value}" placeholder="{$edit.placeholder}" class="form-control"
                                {if $edit.readonly}readonly{/if}/>
                                <span class="error_{$edit.field}"></span>
                            </div>
                        </div>
                    {case 'textarea'}
                        <div class="form-group">
                            <label class="control-label" for="{$edit.field}">{$edit.label}</label>
                            <div class="controls">
                                <textarea id="{$edit.field}" name="{$edit.field}" placeholder="{$edit.placeholder}" class="form-control"
                                {if $edit.readonly}readonly{/if}>{$edit.value}</textarea>
                                <span class="error_{$edit.field}"></span>
                            </div>
                        </div>
                    {case 'checkbox'}
                        <label>
                            <input type="checkbox" class="get-table-checkbox-hidden" {if $edit.value} checked{/if} {if $edit.readonly}readonly{/if}>
                            <input type="hidden" value="{$edit.value}" data-field="{$edit.field}" name="{$edit.field}" {if $edit.readonly}readonly{/if}/>
                            {$edit.label}
                        </label>
                    {case 'select'}
                        {switch $edit.select.type}
                            {case 'select'}
                                <div class="form-group">
                                    <label class="control-label" for="{$edit.field}">{$edit.label}</label>
                                    <div class="controls">
                                        {if $edit.multiple and !$edit.readonly}
                                            <select data-field="{$edit.field}" name="{$edit.field}[]" data-value='{$edit.json}' placeholder="{$edit.placeholder}" 
                                                class="form-control get-select-multiple" multiple="multiple"
                                                >
                                                {foreach $edit.select.data as $d}
                                                    <option value="{$d.id}" {if $edit.value[$d.id]}selected{else} {if $edit.readonly}disabled{/if}{/if} >{$d.content}</option>
                                                {/foreach}
                                            </select>
                                        {else}
                                            <select data-field="{$edit.field}" name="{$edit.field}" data-value="{$edit.value}" 
                                            placeholder="{$edit.placeholder}" class="form-control"
                                            >
                                                <option value=""></option>
                                                {foreach $edit.select.data as $d}
                                                    <option value="{$d.id}" {if $edit.value == $d.id}selected{else} {if $edit.readonly}disabled{/if}{/if} >{$d.content}</option>
                                                {/foreach}
                                            </select>
                                         {/if}
                                    </div>
                                </div>
                             {case 'autocomplect'}
                                <div class="form-group get-autocomplect" data-action="getSelect/autocomplect" data-name="{$edit.select.name}" data-modal="1">
                                  <label class="control-label" for="{$edit.field}">{$edit.label}</label>
                                  <div class="input-group">
                                    <input type="hidden" class="get-autocomplect-hidden-id" 
                                            value="{$edit.value}" data-field="{$edit.field}" name="{$edit.field}" 
                                            {if $edit.readonly}readonly{/if}/>
                                    <span class="input-group-prepend" style="width: 20%;{if $edit.hide_id}display:none;{/if}">
                                        <input type="number" class="get-autocomplect-id" 
                                            value="{$edit.value}"  
                                            placeholder="id" min="0"
                                            style="width:100%;"/>
                                    </span>
                                    <input type="text" class="form-control get-autocomplect-content" value="{$edit.content}" 
                                    placeholder="{$edit.placeholder}" 
                                    {if $edit.readonly}readonly{/if}/>
                                    <div class="input-group-append">
                                        <button class="btn get-autocomplect-all">
                                            <span class="fa fa-caret-down"></span>
                                        </button>
                                    </div>
                                  </div>
                                  <ul class="dropdown-menu get-autocomplect-menu" role="menu">
                                      
                                    </ul>
                                </div>
                        {/switch}
                    {case 'date'}
                        <div class="form-group">
                            <label class="control-label" for="{$edit.field}">{$edit.label}</label>
                            <div class="controls">
                                <input type="text" id="{$edit.field}" name="{$edit.field}" value="{$edit.value}" placeholder="{$edit.placeholder}" class="form-control get-date"
                                    autocomplect="off" {if $edit.readonly}readonly{/if}/>
                                <span class="error_{$edit.field}"></span>
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