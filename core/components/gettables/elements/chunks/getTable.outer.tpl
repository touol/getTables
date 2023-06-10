<div data-name="{$name}"  
    
    {if $sub_where_current}data-sub_where_current='{$sub_where_current}'{/if}
    {if $parent_current}
        data-parent_current='{$parent_current}'
    {/if}
    data-hash="{$hash}" class="get-table {$cls} {if $in_all_page}in_all_page{/if}" style="{if $width}width:{$width}%;{/if}">
    {if $label}<h2>{$label}</h2>{/if}
    <div class="form-inline">
        {if $parent_current}
            <button class="pull-right get-table-in_all_page"><span class="glyphicon glyphicon-resize-full"></span></button>
            <button class="pull-right get-table-close-subtable">X</button>
        {/if}
    </div>
    {if $top}
        <div class="get-table-top">
        {$top}
        </div>
    {/if}
    <div class="gts-form get-table-filter-container get-table-paginator-container">
        {if ($topBar['topBar/topline/filters']['filters'] and count($topBar['topBar/topline/filters']['filters']) == 0) or !isset($topBar['topBar/topline/filters/search'])}
            <div class="row">
                <div class="col-md-1 get-table-topline-first">
                    {foreach $topBar['topBar/topline/first'] as $t}
                            {$t.content}
                    {/foreach}
                </div>
                <div class="col-md-1 get-table-topline-multiple">
                    {foreach $topBar['topBar/topline/multiple'] as $t}
                            {$t.content}
                    {/foreach}
                </div>
                <div class="col-md-6">
                    <div class="form-inline get-table-nav">
                        {$page.content}
                    </div>
                </div>
            </div>
        {else}
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
            {if $topBar['topBar/topline/filters']['filters'] and count($topBar['topBar/topline/filters']['filters']) > 0}
                <div class="row">
                    {foreach $topBar['topBar/topline/filters']['filters'] as $f}
                        <div class="col-md-{$f.cols} ">    
                            {$f.content}
                        </div>
                    {/foreach}
                </div>
            {/if}
            <div class="row">
                <div class="col-md-6">
                    <div class="form-inline get-table-nav">
                        {$page.content}
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
                    {/if}
                </div>
            </div>
        {/if}
    </div>
    <table class="table get-table-table" {if $table.style}style="{$table.style}"{/if}>
      <thead class="get-table-thead">
        <tr>
            {foreach $thead.tr.ths as $th}
                <th class="{$th.cls}" style="{$th.style}" data-field="{$th.field}">
                    {$th.content}
                    {if $th.filter}
                        <button class="filtr-btn {$th.filter_class}"></button>
                        <div class="filrt-window">
                            <div class="get-table-sort">
                                <div>
                                    <span class="get-table-sort-sortdir" data-sortdir="ASC">{'gettables_sort_asc' | lexicon}</span>
                                </div>
                                <div>
                                    <span class="get-table-sort-sortdir" data-sortdir="DESC">{'gettables_sort_desc' | lexicon}</span>
                                </div>
                                <div>
                                    <span>{'gettables_sort_rank' | lexicon}</span>
                                    <input type="number" min="1" class="get-table-sort-rank" value="1">
                                </div>
                            </div>
                            <div class="filrt-standart">
                                {foreach $th.filters as $f}
                                    <div class="">    
                                        {$f.content}
                                    </div>
                                {/foreach}
                            </div>
                            <div class="filrt-add">
                                <div class="filtr-btn__box-top">
                                    <button class="filtr-btn-clear">{'filtr_btn_checkbox_clear' | lexicon}</button>
                                    <button class="filtr-btn-checkbox-load">{'filtr_btn_checkbox_load' | lexicon}</button>
                                </div>
                                <div class="filrt-checkbox-container">
                                
                                </div>
                                <button class="filtr-btn-checkbox-apply" style="display:none;">{'filtr_btn_checkbox_apply' | lexicon}</button>
                            </div>
                        </div>
                    {/if}
                </th>
            {/foreach}
        </tr>
      </thead>
      <tbody class="get-table-tbody">
        {foreach $tbody.trs as $tr}
            {$tr.html}
        {/foreach}
      </tbody>
    </table>
    <div class="get-table-paginator-container">
        <div class="row">
            <div class="col-md-6">
                <div class="form-inline get-table-nav">
                    {$page.content}
                </div>
            </div>
        </div>
    </div>
</div>