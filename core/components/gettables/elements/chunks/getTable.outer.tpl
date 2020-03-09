<div data-name="{$name}" data-hash="{$hash}" class="get-table {$cls}">
	<form class="gts-form">
		<input type="hidden" name="hash" value="{$topBar.hash}">
		<input type="hidden" name="table_name" value="{$topBar.table_name}">
		<div class="row">
			<div class="col-md-1 get-table-topline-first">
				{foreach $topBar['topBar/topline/first'] as $t}
						{$t.content}
				{/foreach}
			</div>
			<div class="col-md-2 get-table-topline-multiple">
				{foreach $topBar['topBar/topline/multiple'] as $t}
						{$t.content}
				{/foreach}
			</div>
			<div class="col-md-9 get-table-topline-filters text-right">
				<div class="row">
    				{set $offset = 6 - 2*count($topBar['topBar/topline/filters'])}
    				
    				<div class="col-md-2 col-md-offset-{$offset}"></div>
    				{foreach $topBar['topBar/topline/filters'] as $t}
    					<div class="col-md-2 ">	
    						{$t.content}
    					</div>
    				{/foreach}
    				
    				{if isset($topBar['topBar/topline/filters/search'])}
    				    <div class="col-md-4 ">
                          <div class="input-group">
                            {$topBar['topBar/topline/filters/search']['content']}
                            <div class="input-group-btn">
                              <button class="btn btn-primary get-table-search" type="submit" name="gts_action" value="getTable/filter">
                                <span class="glyphicon glyphicon-search"></span>
                              </button>
                              
                            </div>
                            <div class="input-group-btn">
                                   <button class="btn btn-danger get-table-reset" type="reset">
                                       <span class="glyphicon glyphicon-remove"></span>
                                   </button>
                            </div>
                          </div>
                        </div>
    				{/if}
				</div>
			</div>
			
		</div>
		
		{if $page.total}
		    <br/>
    		{$page.content}
		{/if}
	</form>
	<table class="table">
	  <thead>
		<tr>
			{foreach $thead.tr.ths as $th}
				<th class="{$th.cls}">{$th.content}</th>
			{/foreach}
		</tr>
	  </thead>
	  <tbody>
		{$tbody.inner}
	  </tbody>
	</table>
</div>