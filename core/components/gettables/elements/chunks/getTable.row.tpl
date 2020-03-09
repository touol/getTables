<tr {foreach $tr.data as $k=>$v} data-{$k}="{$v}" {/foreach} class="get-table-tr {$tr.cls}">
  {foreach $tr.tds as $td}
	  <td class="{$td.cls}" data-field="{$td.field}" data-name="{$td.name}" data-value="{$td.value}">{$td.content}</td>
  {/foreach}
</tr>
<tr {foreach $tr.data as $k=>$v} data-{$k}="{$v}" {/foreach} class="get-sub-row hidden">
    <th class=""></th>
    <th class="get-sub-content" colspan="{count($tr.tds)-1}"></th>
</tr>