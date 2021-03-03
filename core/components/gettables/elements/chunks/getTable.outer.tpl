<div data-name="{$name}"  
    
    {if $sub_where_current}data-sub_where_current='{$sub_where_current}'{/if}
    {if $parent_current}
        data-parent_current='{$parent_current}'
    {/if}
    data-hash="{$hash}" class="get-table {$cls} {if $in_all_page}in_all_page{/if}" style="{if $width}width:{$width}%;{/if}">
    <div class="form-inline">
        
    {*if $style}
        <form class="gts-config">
            
                <label class="control-label">Конфигуратор:</label> 
                <label class="control-label" for="getTable_width">Ширина таблицы</label>
                <input type="number" id="getTable_width" min="10" max="500" step="10" name="width" value="{$width}" 
                    style="width:100px;" class="form-control">
                <input type="checkbox" id="getTable_subtable_in_all_page" name="subtable_in_all_page" value="1" 
                    {if $subtable_in_all_page}checked{/if}>Открывать субтаблицы на всю страницу 
                <button class="btn btn-primary" type="submit" name="gts_action">
                    <span class="glyphicon glyphicon-wrench"></span>
                </button>
        </form>
    {/if*}
    {if $parent_current}
        <button class="pull-right get-table-in_all_page"><span class="glyphicon glyphicon-resize-full"></span></button>
        <button class="pull-right get-table-close-subtable">X</button>
    {/if}
    </div>
    <form class="gts-form">
        <input type="hidden" name="hash" value="{$topBar.hash}">
        <input type="hidden" name="table_name" value="{$topBar.table_name}">
        {if $sub_where_current}
            <input type="hidden" name="sub_where_current" value='{$sub_where_current}'>
        {/if}
        {if $parent_current}
            <input type="hidden" name="parent_current" value='{$parent_current}'>
        {/if}
        <div class="row">
            <div class="col-md-2 get-table-topline-first">
                {foreach $topBar['topBar/topline/first'] as $t}
                        {$t.content}
                {/foreach}
            </div>
            <div class="col-md-8 get-table-topline-multiple">
                {foreach $topBar['topBar/topline/multiple'] as $t}
                        {$t.content}
                {/foreach}
            </div>
        </div>
        <div class="row">
            {foreach $topBar['topBar/topline/filters']['filters'] as $f}
                <div class="col-md-{$f.cols} ">    
                    {$f.content}
                </div>
            {/foreach}
        </div>
        <div class="row">
            <div class="col-md-6">
                <div class="form-inline get-table-nav">
                {if $page.total}
                    {$page.content}
                {/if}
                </div>
            </div>
            <div class="col-md-6">
                {if isset($topBar['topBar/topline/filters/search'])}
                    
                      <div class="input-group">
                        {$topBar['topBar/topline/filters/search']['content']}
                        <span class="input-group-btn">
                          <button class="btn btn-primary get-table-search" type="submit" name="gts_action" value="getTable/filter">
                            <span class="glyphicon glyphicon-search"></span>
                          </button>
                            <button class="btn btn-danger get-table-reset" type="reset">
                               <span class="glyphicon glyphicon-remove"></span>
                           </button>
                        </span>
                      </div>
                {else}
                    <button class="btn btn-primary get-table-search hidden" type="submit" name="gts_action" value="getTable/filter">
                    <span class="glyphicon glyphicon-search"></span>
                    </button>
                {/if}
            </div>
        </div>
    </form>
    <table class="table">
      <thead>
        <tr> 
			{foreach $thead.tr.ths as $th}
                <th class="{$th.cls}" style="{$th.style}">
                    {$th.content} 
                    <button class="filtr-btn filter"></button>
                    <div class="filrt-window">
                        текст
                    </div>
                </th>
            {/foreach}
        </tr>
      </thead>
      <tbody>
        {foreach $tbody.trs as $tr}
            {$tr.html}
        {/foreach}
      </tbody>
    </table>
</div>