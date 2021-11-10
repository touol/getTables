{if $edit.buttons}
    <div class="input-group">
{/if}
{switch $edit.type}
    {case 'hidden'}
        {$edit.value}
    {case 'row_view','view','disabled'}
        {$edit.value}
    {case 'view_date'}
        {$edit.value | date_format : '%d.%m.%Y'}
    {case 'text'}
        <input type="text" data-field="{$edit.field}" name="{$edit.field}" value="{$edit.value}" 
            placeholder="{$edit.placeholder}" class="form-control get-table-autosave"
            {if $edit.style} style="{$edit.style}"{/if} {if $edit.readonly}readonly{/if}/>
        <span class="error_{$edit.field}"></span>
    {case 'decimal'}
        {if $edit.readonly}
            {$edit.value | number : 2 : ',' : '&nbsp;'}
        {else}
        <input type="number" step="0.01" data-field="{$edit.field}" name="{$edit.field}" value="{$edit.value}" 
            placeholder="{$edit.placeholder}" class="form-control get-table-autosave"
            {if $edit.style} style="{$edit.style}"{/if} {if $edit.readonly}readonly{/if}/>
        <span class="error_{$edit.field}"></span>
        {/if}
    {case 'readonly'}
        <input type="text" data-field="{$edit.field}" name="{$edit.field}" value="{$edit.value}" placeholder="{$edit.placeholder}" class="form-control get-table-autosave"
            {if $edit.style} style="{$edit.style}"{/if} readonly/>
        <span class="error_{$edit.field}"></span>
    {case 'textarea'}
        <textarea  data-field="{$edit.field}" name="{$edit.field}" placeholder="{$edit.placeholder}" class="form-control  get-table-autosave"
            style="height: 34px;" {if $edit.readonly}readonly{/if}>{$edit.value}</textarea>
        <span class="error_{$edit.field}"></span>
    {case 'checkbox'}
        {if $edit.only_view or $edit.readonly}
            {if $edit.value}Да{else}Нет{/if}
        {else}
        <input type="checkbox" class="get-table-checkbox-hidden" {if $edit.value} checked{/if}>
        <input type="hidden" value="{$edit.value}" data-field="{$edit.field}" name="{$edit.field}" class="get-table-autosave"/>
        {/if}
    {case 'select'}
        {switch $edit.select.type}
            {case 'select','data'}
                {if $edit.multiple and !$edit.readonly}
                    <select data-field="{$edit.field}" name="{$edit.field}" data-value='{$edit.json}' placeholder="{$edit.placeholder}" 
                        class="form-control get-select-multiple get-table-autosave" multiple="multiple" {if $edit.style} style="{$edit.style}"{/if}
                        >
                        {foreach $edit.select.data as $d}
                            <option value="{$d.id}" {if $edit.value[$d.id]}selected{else} {if $edit.readonly}disabled{/if}{/if}>{$d.content}</option>
                        {/foreach}
                    </select>
                {else}
                    <select data-field="{$edit.field}" name="{$edit.field}" 
                        data-value="{$edit.value}" placeholder="{$edit.placeholder}" class="form-control get-table-autosave"
                        {if $edit.style} style="{$edit.style}"{/if}
                        >
                        
                        <option value="" {if $edit.readonly}disabled{/if}></option>
                        {foreach $edit.select.data as $d}
                            <option value="{$d.id}" 
                            {if $edit.value == $d.id}
                                selected
                                {set $edit.title = $d.content}
                            {else} 
                            {if $edit.readonly}disabled{/if}{/if}>
                                {$d.content}
                            </option>
                        {/foreach}
                    </select>
                 {/if}
            {case 'autocomplect'}
                
                <div class="form-group get-autocomplect" data-action="getSelect/autocomplect" data-name="{$edit.select.name}" 
                {if $edit.search}data-search="{$edit.search}"{/if}
                {if $edit.style} style="{$edit.style}"{/if}>
                  <div class="input-group">
                    <input type="hidden" class="get-autocomplect-hidden-id get-table-autosave" 
                            value="{$edit.value}" data-field="{$edit.field}" name="{$edit.field}" 
                            {if $edit.readonly}readonly{/if}
                            />
                    <span class="input-group-prepend" style="width: 20%;{if $edit.hide_id}display:none;{/if}">
                        <span class="input-number__box ">
                            <input type="number" class="get-autocomplect-id" 
                            value="{$edit.value}"  
                            placeholder="id" min="0"
                            {if $edit.readonly}readonly{/if}
                            style="width:100%;"/>
                            <button class="arr-btn arr-btn__top"></button>
                            <button class="arr-btn arr-btn__bottom"></button>
                        </span>
                    </span>
                    {set $edit.title = $edit.content}
                    <input type="text" class="form-control get-autocomplect-content" value="{$edit.content}" placeholder="{$edit.placeholder}"
                    {if $edit.readonly}readonly{/if}
                    />
                    {if !$edit.readonly}
                        <div class="input-group-append">
                        <button class="btn get-autocomplect-all">
                            <span class="fa fa-caret-down"></span>
                        </button>
                        </div>
                    {/if}
                  </div>
                  <ul class="dropdown-menu get-autocomplect-menu" role="menu">
                      
                    </ul>
                </div>
        {/switch}
    {case 'view2'}
        <input type="text" data-field="{$edit.field}" value="" placeholder="{$edit.placeholder}" class="form-control " disabled {if $edit.style} style="{$edit.style}"{/if}/>
    {case 'date'}
        <input type="text"  data-field="{$edit.field}" name="{$edit.field}" value="{$edit.value}" 
        placeholder="{$edit.placeholder}" class="form-control {if !$edit.readonly}get-date{/if} get-table-autosave" {if $edit.style} style="{$edit.style}"{/if}
        {if $edit.readonly}readonly{/if}
        autocomplect="off"/>
    {case 'datetime'}
        <input type="text"  data-field="{$edit.field}" name="{$edit.field}" value="{$edit.value}" 
        placeholder="{$edit.placeholder}" class="form-control {if !$edit.readonly}get-datetime{/if} get-table-autosave" {if $edit.style} style="{$edit.style}"{/if}
        {if $edit.readonly}readonly{/if}
        autocomplect="off"/>
{/switch}
{if $edit.buttons}
    <span class="input-group-btn" >{$edit.buttons}</span>
  </div>
{/if}