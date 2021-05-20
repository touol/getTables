<ul class="filrt-checkbox-ul">
    <li class="filrt-checkbox-item">
        <input type="checkbox" value="" checked="checked" class="filrt-checkbox-select-all">{'filtr_checkbox_select_all' | lexicon}
    </li>
    {foreach $checkboxs as $cb}
        <li class="filrt-checkbox-item">
            <input type="checkbox" value="{$cb.value}" checked="checked" class="filrt-checkbox-input">{$cb.content}
        </li>
    {/foreach}
</ul>