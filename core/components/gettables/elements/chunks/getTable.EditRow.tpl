{switch $edit.type}
    {case 'hidden'}
        {$edit.value}
    {case 'view'}
        <input type="text" data-field="{$edit.field}" value="{$edit.value}" placeholder="{$edit.placeholder}" class="form-control disabled"/>
    {case 'text'}
        <input type="text" data-field="{$edit.field}" name="{$edit.field}" value="{$edit.value}" placeholder="{$edit.placeholder}" class="form-control get-table-autosave"/>
        <span class="error_{$edit.field}"></span>
    {case 'checkbox'}
        <input type="checkbox" class="get-table-checkbox-hidden" {if $edit.value} checked{/if}>
        <input type="hidden" value="{$edit.value}" data-field="{$edit.field}" name="{$edit.field}" class="get-table-autosave"/>
    {case 'autocomplect'}
        {$edit.value}
    {case 'select'}
        <select data-field="{$edit.field}" name="{$edit.field}" data-value="{$edit.value}" placeholder="{$edit.placeholder}" class="form-control get-table-autosave">
            <option value="">Выберете {$edit.placeholder}</option>
            {foreach $edit.select.data as $d}
                <option value="{$d.id}" {if $edit.value == $d.id}selected{/if} >{$d.content}</option>
            {/foreach}
        </select>
{/switch}