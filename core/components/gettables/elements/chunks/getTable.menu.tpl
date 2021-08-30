<div class="btn-group">
  <button type="button" class="btn btn-secondary dropdown-toggle {$cls}" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
    <i class="{$icon}"></i>{$title}
  </button>
  <ul class="dropdown-menu dropdown-menu-right">
    {foreach $buttons as $b}
        <li>{$b}</li>
    {/foreach}
  </ul>
</div>