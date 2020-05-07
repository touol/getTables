{switch $filter.edit.type}
    {case 'text'}
        <div class="form-group">
            <div class="controls">
                <input type="text" id="{$filter.edit.field}" name="{$filter.edit.field}" value="{$filter.value}" 
                placeholder="{$filter.edit.placeholder}" class="form-control"/>
            </div>
        </div>
    {case 'decimal'}
        <div class="form-group">
            <div class="controls">
                <input type="text" step="0.01" id="{$filter.edit.field}" name="{$filter.edit.field}" value="{$filter.value}" 
                placeholder="{$filter.edit.placeholder}" class="form-control"/>
            </div>
        </div>
    {case 'textarea'}
        <div class="form-group">
            <div class="controls">
                <input type="text" id="{$filter.edit.field}" name="{$filter.edit.field}" value="{$filter.value}" 
                placeholder="{$filter.edit.placeholder}" class="form-control"/>
            </div>
        </div>
    {case 'checkbox'}
        <div class="form-group">
            
            <div class="controls">
        <select data-field="{$filter.edit.field}" name="{$filter.edit.field}" data-value="{$filter.value}" placeholder="{$filter.edit.placeholder}" 
            class="form-control {if $filter.edit.multiple}get-select-multiple{/if}" {if $filter.edit.multiple}multiple="multiple"{/if}>
            <option value="">Выберете {$filter.edit.placeholder}</option>
            <option value="1" {if $filter.value === 1}selected{/if} >Да</option>
            <option value="0" {if $filter.value === 0}selected{/if} >Нет</option>
        </select>
        </div>
        </div>
    {case 'select'}
        {switch $filter.edit.select.type}
            {case 'select'}
                <div class="form-group">
                    <div class="controls">
                        {if !$filter.edit.multiple}
                            <select data-field="{$filter.edit.field}" name="{$filter.edit.field}" data-value="{$filter.value}" placeholder="{$filter.edit.placeholder}" 
                                class="form-control {if $filter.edit.multiple}get-select-multiple{/if}" {if $filter.edit.multiple}multiple="multiple"{/if}>
                    
                                <option value="">{$filter.edit.placeholder}</option>
                                {foreach $filter.edit.select.data as $d}
                                    <option value="{$d.id}" {if $filter.value == $d.id}selected{/if} >{$d.content}</option>
                                {/foreach}
                            </select>
                        {else}
                            <select data-field="{$filter.edit.field}" name="{$filter.edit.field}[]" data-value="{$filter.value}" placeholder="{$filter.edit.placeholder}" 
                                class="form-control get-select-multiple" multiple="multiple">
                                {foreach $filter.edit.select.data as $d}
                                    <option value="{$d.id}" {if $filter.value[$d.id]}selected{/if} >{$d.content}</option>
                                {/foreach}
                            </select>
                         {/if}
                    </div>
                </div>
             {case 'autocomplect'}
                <div class="form-group get-autocomplect" data-action="getSelect/autocomplect" data-name="{$filter.edit.select.name}" {if $edit.style} style="{$edit.style}"{/if}>
                  <div class="input-group">
                    <input type="hidden" class="get-autocomplect-hidden-id" 
                            value="{$filter.edit.value}" data-field="{$filter.edit.field}" name="{$filter.edit.field}" 
                            />
                    <span class="input-group-addon {if $filter.edit.hide_id}hidden{/if}">
                        <input type="number" class="get-autocomplect-id" 
                            value="{$edit.value}"  
                            placeholder="id" min="0"/>
                    </span>
                    <input type="search" class="form-control get-autocomplect-content" value="{$filter.edit.content}" placeholder="{$filter.edit.placeholder}"/>
                    <div class="input-group-btn">
                      <button class="btn get-autocomplect-all">
                          <span class="caret"></span>
                      </button>
                     </div>
                  </div>
                  <ul class="dropdown-menu get-autocomplect-menu" role="menu">
                      
                    </ul>
                </div>
        {/switch}
    {case 'date'}
        <div class="row">
            <div class="form-group col-md-6">
                <div class="controls">
                    <input type="text" id="{$filter.edit.field}_from" name="{$filter.edit.field}[from]" value="{$filter.value.from}" 
                    placeholder="От {$filter.edit.placeholder}" class="form-control get-date" autocomplect="off"/>
                </div>
            </div>
            <div class="form-group col-md-6">
                <div class="controls">
                    <input type="text" id="{$filter.edit.field}_to" name="{$filter.edit.field}[to]" value="{$filter.value.to}" 
                    placeholder="До {$filter.edit.placeholder}" class="form-control get-date" autocomplect="off"/>
                </div>
            </div>
        </div>
{/switch}