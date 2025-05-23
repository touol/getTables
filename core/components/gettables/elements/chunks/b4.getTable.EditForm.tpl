{switch $edit.type}
    {case 'hidden','row_view'}
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
                <input type="text" id="{$edit.field}" name="{$edit.field}" value='{$edit.value}' placeholder="{$edit.placeholder}" class="form-control"
                {if $edit.readonly1}readonly{/if}/>
                <span class="error_{$edit.field}"></span>
            </div>
        </div>
    {case 'decimal'}
        <div class="form-group">
            <label class="control-label" for="{$edit.field}">{$edit.label}</label>
            <div class="controls">
                <input type="number" step="{if $edit.step}{$edit.step}{else}0.01{/if}"  id="{$edit.field}" name="{$edit.field}" value="{$edit.value}" placeholder="{$edit.placeholder}" class="form-control"
                {if $edit.readonly1}readonly{/if}/>
                <span class="error_{$edit.field}"></span>
            </div>
        </div>
    {case 'number'}
        <div class="form-group">
            <label class="control-label" for="{$edit.field}">{$edit.label}</label>
            <div class="controls">
                <input type="number" step="1"  id="{$edit.field}" name="{$edit.field}" value="{$edit.value}" placeholder="{$edit.placeholder}" class="form-control"
                {if $edit.readonly1}readonly{/if}/>
                <span class="error_{$edit.field}"></span>
            </div>
        </div>
    {case 'textarea'}
        <div class="form-group">
            <label class="control-label" for="{$edit.field}">{$edit.label}</label>
            <div class="controls">
                <textarea id="{$edit.field}" name="{$edit.field}" placeholder="{$edit.placeholder}" class="form-control"
                {if $edit.readonly1}readonly{/if}
                {if $edit.editor == 'ace'}
                    data-editor="ace" data-gutter="1" 
                    data-editor-mode="{if $edit.editor_mode}{$edit.editor_mode}{else}xml{/if}"
                    data-editor-height="{if $edit.editor_height}{$edit.editor_height}{else}300{/if}"
                    data-editor-theme="{if $edit.editor_theme}{$edit.editor_theme}{else}idle_fingers{/if}"
                {/if}
                {if $edit.editor == 'ckeditor'}
                    data-editor="ckeditor" 
                {/if}
                >{$edit.value}</textarea>
                <span class="error_{$edit.field}"></span>
            </div>
        </div>
    {case 'checkbox'}
        <label>
            <input type="checkbox" class="get-table-checkbox-hidden" {if $edit.value} checked{/if} {if $edit.readonly1}disabled="disabled"{/if}>
            <input type="hidden" value="{$edit.value}" data-field="{$edit.field}" name="{$edit.field}" {if $edit.readonly1}disabled="disabled"{/if}/>
            {$edit.label}
        </label>
    {case 'select'}
        {switch $edit.select.type}
            {case 'select','data'}
                <div class="form-group">
                    <label class="control-label" for="{$edit.field}">{$edit.label}</label>
                    <div class="controls">
                        {if $edit.multiple and !$edit.readonly1}
                            <select data-field="{$edit.field}" name="{$edit.field}[]" data-value='{$edit.json}' placeholder="{$edit.placeholder}" 
                                class="form-control get-select-multiple" multiple="multiple"
                                >
                                {foreach $edit.select.data as $d}
                                    <option value="{$d.id}" {if $edit.value[$d.id]}selected{else} {if $edit.readonly1}disabled{/if}{/if} >{$d.content}</option>
                                {/foreach}
                            </select>
                        {else}
                            <select data-field="{$edit.field}" name="{$edit.field}" data-value="{$edit.value}" 
                            placeholder="{$edit.placeholder}" class="form-control"
                            >
                                <option value=""></option>
                                {foreach $edit.select.data as $d}
                                    <option value="{$d.id}" {if $edit.value == $d.id}selected{else} {if $edit.readonly1}disabled{/if}{/if} >{$d.content}</option>
                                {/foreach}
                            </select>
                            {/if}
                    </div>
                </div>
                {case 'autocomplect'}
                <div class="form-group get-autocomplect" data-action="getSelect/autocomplect" data-name="{$edit.select.name}"
                {if $edit.search}data-search="{$edit.search}"{/if}
                    data-modal="1">
                    <label class="control-label" for="{$edit.field}">{$edit.label}</label>
                    <div class="input-group">
                    <input type="hidden" class="get-autocomplect-hidden-id" 
                            value="{$edit.value}" data-field="{$edit.field}" name="{$edit.field}" 
                            {if $edit.readonly1}readonly{/if}/>
                    <span class="input-group-prepend" style="width: 20%;{if $edit.hide_id}display:none;{/if}">
                        <span class="input-number__box ">
                            <input type="number" class="get-autocomplect-id" 
                                value="{$edit.value}"  
                                placeholder="id" min="0"
                                {if $edit.readonly1}readonly{/if}
                                style="width:100%;"/>
                            <button class="arr-btn arr-btn__top"></button>
                            <button class="arr-btn arr-btn__bottom"></button>
                        </span>
                    </span>
                    <input type="text" class="form-control get-autocomplect-content" value="{$edit.content}"
                        {if $edit.content_name}name="{$edit.content_name}" data-field="{$edit.field}"{/if}  
                        placeholder="{$edit.placeholder}" 
                        {if $edit.readonly1}readonly{/if}
                    />
                    {if !$edit.readonly1}
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
    {case 'date'}
        <div class="form-group">
            <label class="control-label" for="{$edit.field}">{$edit.label}</label>
            <div class="controls">
                <input type="text" id="{$edit.field}" name="{$edit.field}" value="{$edit.value}" placeholder="{$edit.placeholder}" 
                    class="form-control {if !$edit.readonly1}get-date{/if}"
                    autocomplect="off" {if $edit.readonly1}readonly{/if}
                    data-options='{if $edit.options}{$edit.options}{/if}'
                    />
                <span class="error_{$edit.field}"></span>
            </div>
        </div>
    {case 'datetime'}
        <div class="form-group">
            <label class="control-label" for="{$edit.field}">{$edit.label}</label>
            <div class="controls">
                <input type="text" id="{$edit.field}" name="{$edit.field}" value="{$edit.value}" placeholder="{$edit.placeholder}" 
                    class="form-control {if !$edit.readonly1}get-datetime{/if}"
                    autocomplect="off" {if $edit.readonly1}readonly{/if}
                    data-options='{if $edit.options}{$edit.options}{/if}'
                    />
                <span class="error_{$edit.field}"></span>
            </div>
        </div>
{/switch}