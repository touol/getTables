
    <button class="btn btn-sm get-nav-first" type="button" >
       <span class="glyphicon glyphicon-backward"></span>
    </button>
    <button class="btn btn-sm get-nav-prev" type="button" >
       <span class="glyphicon glyphicon-chevron-left"></span>
    </button>
    <label class="control-label" for="getPage_{$name}">Страница</label>
	<span class="input-number__box ">
		<input type="number" id="getPage_{$name}" min="1" max="{$page.max}" name="page" value="{$page.current}" 	
        style="width:60px;" class="form-control input-sm get-nav-page">
		<button class="arr-btn arr-btn__top"></button>
		<button class="arr-btn arr-btn__bottom"></button>
	</span>
    
    <label class="control-label" for="getPage_{$name}">Из {$page.max}</label>
    
    <button class="btn btn-sm get-nav-next" type="button" >
       <span class="glyphicon glyphicon-chevron-right"></span>
    </button>
    <button class="btn btn-sm get-nav-last" type="button" >
       <span class="glyphicon glyphicon-forward"></span>
    </button>
    
    <label class="control-label" for="getLimit_{$name}">На странице:</label>
	<div class="select-box inb rL">
		<select name="limit" id="getLimit_{$name}" class="form-control input-sm">
			<option value="10" {if $page.limit == 10}selected{/if}>10</option>
			<option value="20" {if $page.limit == 20}selected{/if}>20</option>
			<option value="40" {if $page.limit == 40}selected{/if}>40</option>
			<option value="60" {if $page.limit == 60 or !$page.limit}selected{/if}>60</option>
		</select>
	</div>
    
    
    <button class="btn btn-sm get-nav-refresh" type="button" >
       <span class="glyphicon glyphicon-refresh"></span>
    </button>
    <label class="pull-right">Всего <span class="get-page-total">{$page.total}</span></label>
