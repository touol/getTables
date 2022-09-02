{switch $filter.edit.type}
    {case 'text','row_view'}
        <div class="form-group">
            <div class="controls">
                <input type="text" name="{$filter.edit.field}" value="{$filter.value}" 
                placeholder="{$filter.edit.placeholder}" class="form-control get-table-filter"/>
            </div>
        </div>
    {case 'decimal'}
        <div class="form-group">
            <div class="controls">
                <input type="text" step="0.01" name="{$filter.edit.field}" value="{$filter.value}" 
                placeholder="{$filter.edit.placeholder}" class="form-control get-table-filter"/>
            </div>
        </div>
    {case 'number'}
        <div class="form-group">
            <div class="controls">
                <input type="text" step="1" name="{$filter.edit.field}" value="{$filter.value}" 
                placeholder="{$filter.edit.placeholder}" class="form-control get-table-filter"/>
            </div>
        </div>
    {case 'textarea'}
        <div class="form-group">
            <div class="controls">
                <input type="text" name="{$filter.edit.field}" value="{$filter.value}" 
                placeholder="{$filter.edit.placeholder}" class="form-control get-table-filter"/>
            </div>
        </div>
    {case 'checkbox'}
        <div class="form-group">
            <div class="controls">
                <div class="select-box rL hid">
                    <select data-field="{$filter.edit.field}" name="{$filter.edit.field}" data-value="{$filter.value}" placeholder="{$filter.edit.placeholder}" 
                        class="form-control get-table-filter {if $filter.edit.multiple}get-select-multiple{/if}" {if $filter.edit.multiple}multiple="multiple"{/if}>
                        <option value="">Выберете {$filter.edit.placeholder}</option>
                        <option value="1" {if $filter.value === 1}selected{/if} >{'gettables_yes' | lexicon}</option>
                        <option value="0" {if $filter.value === 0}selected{/if} >{'gettables_no' | lexicon}</option>
                    </select>
                    <span class="select-btn"></span>
                </div>
            </div>
        </div>
    {case 'select'}
        {switch $filter.edit.select.type}
            {case 'select','data'}
                <div class="form-group">
                    <div class="controls">
                        {if !$filter.edit.multiple}
                            <select data-field="{$filter.edit.field}" name="{$filter.edit.field}" data-value="{$filter.value}" placeholder="{$filter.edit.placeholder}" 
                                class="form-control get-table-filter {if $filter.edit.multiple}get-select-multiple{/if}" {if $filter.edit.multiple}multiple="multiple"{/if}>
                    
                                <option value="">{$filter.edit.placeholder}</option>
                                {foreach $filter.edit.select.data as $d}
                                    <option value="{$d.id}" {if $filter.value == $d.id}selected{/if} >{$d.content}</option>
                                {/foreach}
                            </select>
                        {else}
                            <select data-field="{$filter.edit.field}" name="{$filter.edit.field}[]" data-value="{$filter.value}" placeholder="{$filter.edit.placeholder}" 
                                class="form-control get-select-multiple get-table-filter" multiple="multiple">
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
                    <input type="hidden" class="get-autocomplect-hidden-id get-table-filter" 
                            value="{$filter.value}" data-field="{$filter.edit.field}" name="{$filter.edit.field}" 
                            />
                    <span class="input-group-addon {if $filter.edit.hide_id}hidden{/if}" style="width:20%;padding: 0;">
                        <span class="input-number__box ">
                            <input type="number" class="get-autocomplect-id" 
                                value="{$filter.value}"  
                                placeholder="id" min="0"
                                style="width:100%;height: 30px;padding: 0;"/>
                            <button class="arr-btn arr-btn__top"></button>
                            <button class="arr-btn arr-btn__bottom"></button>
                        </span>
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
                    <input type="text" name="{$filter.edit.field}[from]" value="{if $filter.value.from is set}{$filter.value.from}{/if}" 
                    placeholder="{'gettables_from' | lexicon} {$filter.edit.placeholder}" class="form-control get-date get-table-filter" autocomplect="off"/>
                </div>
            </div>
            <div class="form-group col-md-6">
                <div class="controls">
                    <input type="text" name="{$filter.edit.field}[to]" value="{if $filter.value.to is set}{$filter.value.to}{/if}" 
                    placeholder="{'gettables_to' | lexicon} {$filter.edit.placeholder}" class="form-control get-date get-table-filter" autocomplect="off"/>
                </div>
            </div>
        </div>
    {case 'datetime'}
        <div class="row">
            <div class="form-group col-md-6">
                <div class="controls">
                    <input type="text" name="{$filter.edit.field}[from]" value="{if $filter.value.from is set}{$filter.value.from}{/if}" 
                    placeholder="{'gettables_from' | lexicon} {$filter.edit.placeholder}" class="form-control get-datetime get-table-filter" autocomplect="off"/>
                </div>
            </div>
            <div class="form-group col-md-6">
                <div class="controls">
                    <input type="text" name="{$filter.edit.field}[to]" value="{if $filter.value.to is set}{$filter.value.to}{/if}" 
                    placeholder="{'gettables_to' | lexicon} {$filter.edit.placeholder}" class="form-control get-datetime get-table-filter" autocomplect="off"/>
                </div>
            </div>
        </div>
{/switch}