<tr {foreach $tr.data as $k=>$v} data-{$k}="{$v}" {/foreach} class="get-table-tr {$tr.cls}" {if $tr.sortable}draggable="true"{/if}>
  {foreach $tr.tds as $td}
      <td class="{$td.cls}" data-field="{$td.field}" data-name="{$td.name}" data-value="{$td.value}" {if $td.style}style="{$td.style}"{/if}>{$td.content}</td>
  {/foreach}
</tr>