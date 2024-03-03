{if $edit.buttons}
    <div class="input-group">
{/if}
{if $table.settings.fullcontent == '1'}
    <div class="fullcontent">
        {switch $edit.type}
            {case 'view_date','date'}
                {$edit.value | date_format : '%d.%m.%Y'}
            {case 'datetime'}
                {$edit.value | date_format : '%d.%m.%Y H:s'}
            {case 'decimal'}
                {$edit.value | number : 3 : ',' : ' '}
            {case 'checkbox'}
                {if $edit.value}Да{else}Нет{/if}
            {case 'textarea'}
                {$edit.value | truncate}
            {case default}
                {$edit.content}
        {/switch}
    </div>
    <div class="fullcontent-edit" style="display:none">
{/if}
{switch $edit.type}
    {case 'hidden'}
        {$edit.value}
    {case 'row_view','view','disabled'}
        {$edit.value}
    {case 'view_date'}
        {$edit.value | date_format : '%d.%m.%Y'}
    {case 'text'}
        {*<input type="text" data-field="{$edit.field}" name="{$edit.field}" value="{$edit.value}" 
            placeholder="{$edit.placeholder}" class="form-control get-table-autosave"
            {if $edit.style} style="{$edit.style}"{/if} {if $edit.readonly}readonly{/if}/>*}
        <textarea  data-field="{$edit.field}" name="{$edit.field}" placeholder="{$edit.placeholder}" class="form-control  get-table-autosave"
            {if $edit.style} style="{$edit.style}"{else}style="height: 34px;"{/if} {if $edit.readonly}readonly{/if}
             
            >{$edit.value}</textarea>
        <span class="error_{$edit.field}"></span>
    {case 'decimal'}
        <input type="number" step="{if $edit.step}{$edit.step}{else}0.01{/if}" data-field="{$edit.field}" name="{$edit.field}" value="{$edit.value}" 
            placeholder="{$edit.placeholder}" class="form-control get-table-autosave"
            {if $edit.style} style="{$edit.style}"{/if} {if $edit.readonly}readonly{/if}/>
        <span class="error_{$edit.field}"></span>
    {case 'number'}
        <input type="number" step="1" data-field="{$edit.field}" name="{$edit.field}" value="{$edit.value}" 
            placeholder="{$edit.placeholder}" class="form-control get-table-autosave"
            {if $edit.style} style="{$edit.style}"{/if} {if $edit.readonly}readonly{/if}/>
        <span class="error_{$edit.field}"></span>
    {case 'readonly'}
        <input type="text" data-field="{$edit.field}" name="{$edit.field}" value="{$edit.value}" placeholder="{$edit.placeholder}" class="form-control get-table-autosave"
            {if $edit.style} style="{$edit.style}"{/if} readonly/>
        <span class="error_{$edit.field}"></span>
    {case 'textarea'}
        <textarea  data-field="{$edit.field}" name="{$edit.field}" placeholder="{$edit.placeholder}" class="form-control  get-table-autosave"
            {if $edit.style} style="{$edit.style}"{else}style="height: 34px;"{/if} {if $edit.readonly}readonly{/if}>{$edit.value}</textarea>
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
                            <option value="{$d.id}" 
                            {if $edit.value[$d.id]}selected {set $edit.title = $d.content}{else}
                            {if $edit.readonly}disabled{/if}{/if}>{$d.content}</option>
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
                    <span class="input-group-addon  {if $edit.hide_id}hidden{/if}" style="width:20%;padding: 0;">
                        <span class="input-number__box ">
                            <input type="number" class="get-autocomplect-id" 
                            value="{$edit.value}"  
                            placeholder="id" min="0"
                            {if $edit.readonly}readonly{/if}
                            style="width:100%;height: 30px;padding: 0;"/>
                            <button class="arr-btn arr-btn__top"></button>
                            <button class="arr-btn arr-btn__bottom"></button>
                        </span>
                    </span>
                    {set $edit.title = $edit.content}
                    {*<input type="search" 
                        class="form-control get-autocomplect-content {if $edit.content_name}get-table-autosave{/if}"
                        {if $edit.content_name}name="{$edit.content_name}" data-field="{$edit.field}"{/if} 
                        value="{$edit.content}" placeholder="{$edit.placeholder}"
                        {if $edit.readonly}readonly{/if}
                    />*}
                    <textarea  
                        {if $edit.content_name}name="{$edit.content_name}" data-name="{$edit.content_name}" data-field="{$edit.field}"{/if}
                         placeholder="{$edit.placeholder}" 
                        class="form-control  get-autocomplect-content {if $edit.content_name}get-table-autosave{/if}"
                        {if $edit.style} style="{$edit.style}"{else}style="height: 34px;"{/if} {if $edit.readonly}readonly{/if}
                        
                        >{$edit.content}</textarea>
                    {if !$edit.readonly}
                        <div class="input-group-btn">
                            <button class="btn get-autocomplect-all">
                                <span class="caret"></span>
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
        {if $edit.readonly}readonly{/if} data-options='{if $edit.options}{$edit.options}{/if}'
        autocomplect="off"/>
    {case 'datetime'}
        <input type="text"  data-field="{$edit.field}" name="{$edit.field}" value="{$edit.value}" 
        placeholder="{$edit.placeholder}" class="form-control {if !$edit.readonly}get-datetime{/if} get-table-autosave" {if $edit.style} style="{$edit.style}"{/if}
        {if $edit.readonly}readonly{/if} data-options='{if $edit.options}{$edit.options}{/if}'
        autocomplect="off"/>
    {case 'calc'}
        <div class="input-group">
            <input type="number" step="{if $edit.step}{$edit.step}{else}0.01{/if}" data-field="{$edit.field}" name="{$edit.field}" value="{$edit.value}" 
                placeholder="{$edit.placeholder}" class="form-control get-table-autosave"
                {if $edit.style} style="{$edit.style}"{/if} {if $edit.readonly}readonly{/if}/>
            <span class="input-group-addon excel-calc-button">
                <svg width="18px" height="18px" viewBox="0 0 24.00 24.00" fill="none" xmlns="http://www.w3.org/2000/svg" stroke="#000000"><g id="SVGRepo_bgCarrier" stroke-width="0"></g><g id="SVGRepo_tracerCarrier" stroke-linecap="round" stroke-linejoin="round"></g><g id="SVGRepo_iconCarrier"> <path d="M5 9H19M15 18V15M9 18H9.01M12 18H12.01M12 15H12.01M9 15H9.01M15 12H15.01M12 12H12.01M9 12H9.01M8.2 21H15.8C16.9201 21 17.4802 21 17.908 20.782C18.2843 20.5903 18.5903 20.2843 18.782 19.908C19 19.4802 19 18.9201 19 17.8V6.2C19 5.0799 19 4.51984 18.782 4.09202C18.5903 3.71569 18.2843 3.40973 17.908 3.21799C17.4802 3 16.9201 3 15.8 3H8.2C7.0799 3 6.51984 3 6.09202 3.21799C5.71569 3.40973 5.40973 3.71569 5.21799 4.09202C5 4.51984 5 5.07989 5 6.2V17.8C5 18.9201 5 19.4802 5.21799 19.908C5.40973 20.2843 5.71569 20.5903 6.09202 20.782C6.51984 21 7.07989 21 8.2 21Z" stroke="#000000" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"></path> </g></svg>
            </span>
        </div>
{/switch}
{if $table.settings.fullcontent == '1'}
    </div>
{/if}
{if $edit.buttons}
    <span class="input-group-btn" >{$edit.buttons}</span>
  </div>
{/if}