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
		<input type="text" data-field="{$edit.field}" name="{$edit.field}" value="{$edit.value}" placeholder="{$edit.placeholder}" class="form-control get-table-autosave"
			{if $edit.style} style="{$edit.style}"{/if}/>
		<span class="error_{$edit.field}"></span>
	{case 'readonly'}
		<input type="text" data-field="{$edit.field}" name="{$edit.field}" value="{$edit.value}" placeholder="{$edit.placeholder}" class="form-control get-table-autosave"
			{if $edit.style} style="{$edit.style}"{/if} readonly/>
		<span class="error_{$edit.field}"></span>
	{case 'textarea'}
		<textarea  data-field="{$edit.field}" name="{$edit.field}" placeholder="{$edit.placeholder}" class="form-control  get-table-autosave"
			style="height: 34px;">{$edit.value}</textarea>
		<span class="error_{$edit.field}"></span>
	{case 'checkbox'}
		{if $edit.only_view}
			{if $edit.value}Да{else}Нет{/if}
		{else}
		<input type="checkbox" class="get-table-checkbox-hidden" {if $edit.value} checked{/if}>
		<input type="hidden" value="{$edit.value}" data-field="{$edit.field}" name="{$edit.field}" class="get-table-autosave"/>
		{/if}
	{case 'select'}
		{switch $edit.select.type}
			{case 'select'}
				{if $edit.multiple}
					<select data-field="{$edit.field}" name="{$edit.field}" data-value='{$edit.json}' placeholder="{$edit.placeholder}" 
						class="form-control get-select-multiple get-table-autosave" multiple="multiple" {if $edit.style} style="{$edit.style}"{/if}>
						{foreach $edit.select.data as $d}
							<option value="{$d.id}" {if $edit.value[$d.id]}selected{/if} >{$d.content}</option>
						{/foreach}
					</select>
				{else}
					<select data-field="{$edit.field}" name="{$edit.field}" data-value="{$edit.value}" placeholder="{$edit.placeholder}" class="form-control get-table-autosave"
						{if $edit.style} style="{$edit.style}"{/if}>
						<option value=""></option>
						{foreach $edit.select.data as $d}
							<option value="{$d.id}" {if $edit.value == $d.id}selected{/if} >{$d.content}</option>
						{/foreach}
					</select>
				 {/if}
			{case 'autocomplect'}
				
				<div class="form-group get-autocomplect" data-action="getSelect/autocomplect" data-name="{$edit.select.name}" {if $edit.style} style="{$edit.style}"{/if}>
				  <div class="input-group">
					<input type="hidden" class="get-autocomplect-hidden-id get-table-autosave" 
							value="{$edit.value}" data-field="{$edit.field}" name="{$edit.field}" 
							/>
					<span class="input-group-addon  {if $edit.hide_id}hidden{/if}">
						<input type="number" class="get-autocomplect-id" 
							value="{$edit.value}"  
							placeholder="id" min="0"/>
					</span>
					<input type="search" class="form-control get-autocomplect-content" value="{$edit.content}" placeholder="{$edit.placeholder}"/>
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
	{case 'view2'}
		<input type="text" data-field="{$edit.field}" value="" placeholder="{$edit.placeholder}" class="form-control " disabled {if $edit.style} style="{$edit.style}"{/if}/>
	{case 'date'}
		<input type="text"  data-field="{$edit.field}" name="{$edit.field}" value="{$edit.value}" 
		placeholder="{$edit.placeholder}" class="form-control get-date get-table-autosave" {if $edit.style} style="{$edit.style}"{/if}
		{if $edit.readonly}readonly{/if}
		autocomplect="off"/>
{/switch}
{if $edit.buttons}
	<span class="input-group-btn" >{$edit.buttons}</span>
  </div>
{/if}