(function (window, document, $, getTablesConfig) {
    var getTables = getTables || {};
    var progress_offset = 0;

    getTablesConfig.callbacksObjectTemplate = function () {
        return {
            // return false to prevent send data
            before: [],
            response: {
                success: [],
                error: []
            },
            ajax: {
                done: [],
                fail: [],
                always: []
            }
        }
    };
    getTables.Callbacks = getTablesConfig.Callbacks = {
        Modal: {
            load: getTablesConfig.callbacksObjectTemplate(),
        },
        Form: {
            save: getTablesConfig.callbacksObjectTemplate(),
            autosave: getTablesConfig.callbacksObjectTemplate(),
        },
        Table: {
            update: getTablesConfig.callbacksObjectTemplate(),
            refresh: getTablesConfig.callbacksObjectTemplate(),
            sets: getTablesConfig.callbacksObjectTemplate(),
            sub_show: getTablesConfig.callbacksObjectTemplate(),
            //sub_hide: getTablesConfig.callbacksObjectTemplate(),
            remove: getTablesConfig.callbacksObjectTemplate(),
            autosave: getTablesConfig.callbacksObjectTemplate(),
            custom: getTablesConfig.callbacksObjectTemplate(),
            filter_checkbox_load: getTablesConfig.callbacksObjectTemplate(),
            get_tree_child: getTablesConfig.callbacksObjectTemplate(),
            long_process: getTablesConfig.callbacksObjectTemplate(),
            insert: getTablesConfig.callbacksObjectTemplate(),
        },
        Autocomplect: {
            load: getTablesConfig.callbacksObjectTemplate(),
            expand: getTablesConfig.callbacksObjectTemplate(),
        },
        Sortable: {
            sort: getTablesConfig.callbacksObjectTemplate(),
        },
        Tree: {
            load_panel: getTablesConfig.callbacksObjectTemplate(),
            action: getTablesConfig.callbacksObjectTemplate(),
            remove: getTablesConfig.callbacksObjectTemplate(),
            expand: getTablesConfig.callbacksObjectTemplate(),
        },
    };

    getTables.Callbacks.add = function (path, name, func) {
        if (typeof func != 'function') {
            return false;
        }
        path = path.split('.');
        var obj = getTables.Callbacks;
        for (var i = 0; i < path.length; i++) {
            if (obj[path[i]] == undefined) {
                return false;
            }
            obj = obj[path[i]];
        }
        if (typeof obj != 'object') {
            obj = [obj];
        }
        if (name != undefined) {
            obj[name] = func;
        } else {
            obj.push(func);
        }
        return true;
    };
    getTables.Callbacks.remove = function (path, name) {
        path = path.split('.');
        var obj = getTables.Callbacks;
        for (var i = 0; i < path.length; i++) {
            if (obj[path[i]] == undefined) {
                return false;
            }
            obj = obj[path[i]];
        }
        if (obj[name] != undefined) {
            delete obj[name];
            return true;
        }
        return false;
    };
    getTables.ajaxProgress = false;
    getTables.setup = function () {
        // selectors & $objects
        this.actionName = 'gts_action';
        this.action = ':submit[name=' + this.actionName + ']';
        this.form = '.gts-form';
        this.$doc = $(document);

        this.sendData = {
            $GtsApp: null,
            $form: null,
            $row: null,
            action: null,
            data: null
        };
        this.temp = null;
        this.timeout = 300;
    };
    getTables.hotkeys = {};
    getTables.setPlugins = function () {

        $('.get-select-multiple').each(function () {
            $(this).multiselect();
        });
        $('.get-table,.gts-getform').find('[data-hotkey]').each(function(){
            $action = $(this);
            if(getTables.hotkeys.hasOwnProperty($action.data('hotkey'))) return;
            getTables.hotkeys[$action.data('hotkey')] = 1;
            hotkey = $action.data('hotkey').split('+');
            ctrl = false; shift = false;
            for (let n = 0; n < hotkey.length; n++) {
                if(hotkey[n].toLowerCase() == 'ctrl'){
                    ctrl = true;
                }else if(hotkey[n].toLowerCase() == 'shift'){
                    shift = true;
                }else{
                    KeyCode = 'Key' + hotkey[n].toUpperCase();
                }
            }
            $(document).keydown(function (e) {
                if(ctrl && !e.ctrlKey) return;
                if(shift && !e.shiftKey) return;
                if(e.code == KeyCode){
                    
                    $action.trigger('click');
                }
            });
        });
        if(getTablesConfig.load_ckeditor == 1){
            $('textarea[data-editor=ckeditor]').each(function() {
                //$(this).ckeditor();
                if(!$(this).hasClass('ckeditor-attached')){
                    CKEDITOR.replace(this);
                    $(this).addClass('ckeditor-attached');
                }
                
            });
        }
        if(getTablesConfig.load_ace == 1){
            $('textarea[data-editor=ace]').each(function() {
                var textarea = $(this);
                var mode = textarea.data('editor-mode');
                var theme = textarea.data('editor-theme');
                var height = textarea.data('editor-height');
                
                var editDiv = $('<div>', {
                  position: 'absolute',
                //   width: textarea.width(),
                  height: height,
                  'class': textarea.attr('class'),
                  'readonly': textarea.attr('readonly')
                }).insertBefore(textarea);
                
                
                textarea.css('display', 'none');
                var editor = ace.edit(editDiv[0]);
                editor.renderer.setShowGutter(textarea.data('gutter'));
                editor.getSession().setValue(textarea.val());
                editor.getSession().setMode("ace/mode/" + mode);
                editor.setTheme("ace/theme/"+theme);
                //editor.setTheme("ace/theme/chrome");
            
                // copy back to textarea on form submit...
                textarea.closest('form').submit(function() {
                  textarea.val(editor.getSession().getValue());
                })
              });
        }
        $('.get-table>.table').each(function () {
            if(!$(this).hasClass('resizeable')){
                getTables.resizableGrid.initTable($(this)[0]);
                $(this).addClass('resizeable');
            }
        });
    };
    getTables.resizableGrid = {
        callbacks: {
            set_default_width: getTablesConfig.callbacksObjectTemplate(),
        },
        setup: function () {
            getTables.Callbacks.resizableGrid = {
                set_default_width: getTablesConfig.callbacksObjectTemplate(),
            };
        },
        init: function(){
            this.setup();
            $(document).mouseup(function (e){ // событие клика по веб-документу
                var resizeable_menu = $(".resizeable-menu"); // тут указываем ID элемента
                if (!resizeable_menu.is(e.target) // если клик был не по нашему блоку
                    && resizeable_menu.has(e.target).length === 0 // и не по его дочерним элементам 
                    ) { 
                        resizeable_menu.remove(); // скрываем его
                }
            });
            getTables.$doc.on('click','.resizeable-menu button',function () {
                switch($(this).data('action')){
                    case 'set_width_100':
                        $table = $(this).closest('.get-table-table');
                        width = $table.width();
                        $(this).closest('tr').find('th').each(function(){
                            $(this).width(($(this).width()/width*100) + '%');
                        });
                        $table.width('100%');
                        settings = {
                            width:'100%',
                            ths:{}
                        };
                        $(this).closest('tr').find('th').each(function(){
                            settings.ths[$(this).data('field')] = $(this).width() + 'px';
                        });
                        window.localStorage.setItem('table_' + name,JSON.stringify(settings));
                    break;
                    
                    case 'remove_local_width':
                        var name = $(this).closest('.get-table').data('name') + '_' + $(this).closest('.get-table').data('hash');
                        settings = window.localStorage.removeItem('table_' + name);
                        location.reload();
                    break;
                    case 'set_default_width':
                        $table = $(this).closest('.get-table-table');
                        settings = {
                            width:$table.css('width'),
                            ths:{}
                        };
                        $(this).closest('tr').find('th').each(function(){
                            settings.ths[$(this).data('field')] = $(this).css('width');
                        });
                        getTables.sendData.data = {
                            gts_action: 'getTable/set_default_width',
                            hash: $(this).closest('.get-table').data('hash'),
                            table_name: $(this).closest('.get-table').data('name'),
                            settings: settings
                        };

                        var callbacks = getTables.resizableGrid.callbacks;
            
                        callbacks.set_default_width.response.success = function (response) {
                            
                        };
            
                        return getTables.send(getTables.sendData.data, getTables.resizableGrid.callbacks.set_default_width,
                            getTables.Callbacks.resizableGrid.set_default_width);
                    break;
                }
                
            });
        },
        initTable: function(table) {
            var row = table.getElementsByTagName('tr')[0],
                cols = row ? row.children : undefined;
            if (!cols) return;
            
            
            var name = $(table).closest('.get-table').data('name') + '_' + $(table).closest('.get-table').data('hash');
            settings = window.localStorage.getItem('table_' + name);
            if(settings){
                settings = JSON.parse(settings);
                $(table).width(settings.width);
                for (var key of Object.keys(settings.ths)) {
                    if(key != ''){
                        $(table).find('th[data-field="' + key + '"]').width(settings.ths[key]);
                    }
                }
            }

            for (var i = 0; i < cols.length; i++) {
                var div = createDiv();
                cols[i].appendChild(div);
                cols[i].style.position = 'relative';
                setListeners(div);
            }
            $('body').on('contextmenu', 'div.resizeable-th', function() {
                $th = $(this).parent();
                $('.resizeable-menu').remove();
                $th.append(`
                    <div class="resizeable-menu">
                        <ul>
                            <li>
                                <button class="resizeable-menu-button btn btn-primary" data-action="set_width_100">
                                    Установить размер таблицы 100%
                                </button>
                            </li>
                            <li>
                                <button class="resizeable-menu-button btn btn-primary" data-action="set_default_width">
                                    Сохранить размер таблицы как размер по-умолчанию
                                </button>
                            </li>
                            <li>
                                <button class="resizeable-menu-button btn btn-primary" data-action="remove_local_width">
                                    Удалить локальные настройки
                                </button>
                            </li>
                        </ul>
                    </div>
                `);
                $('.resizeable-menu').css('top', $th.outerHeight());
                window.event.returnValue = false;
            });
            
            function setListeners(div) {
                var pageX, curCol, $table, curColWidth, $tableWidth;

                div.addEventListener('mousedown', function(e) {
                    curCol = e.target.parentElement;
                    $table = $(e.target.parentElement).closest('.get-table-table');
                    $(e.target.parentElement).closest('tr').find('th').each(function(){
                        $(this).width($(this).width() + 'px');
                    });
                    $table.width($table.width() + 'px');
                    $tableWidth = $table.width();
                    pageX = e.pageX;
                    var padding = paddingDiff(curCol);

                    curColWidth = curCol.offsetWidth - padding;
                });

                div.addEventListener('mouseover', function(e) {
                    e.target.style.borderRight = '2px solid #0000ff';
                })

                div.addEventListener('mouseout', function(e) {
                    e.target.style.borderRight = '';
                })

                document.addEventListener('mousemove', function(e) {
                    if (curCol) {
                        $('.get-table td').css( "height", "");
                        $('.get-table td').css( "width", "");
                        $('.get-table-autosave').css( "height", "");
                        $('.get-table-autosave').css( "width", "");
                        var diffX = e.pageX - pageX;
                        curCol.style.width = (curColWidth + diffX) + 'px';
                        if ($table)
                            $table.width(($tableWidth + (diffX)) + 'px');
                        
                        settings = {
                            width:($tableWidth + (diffX)) + 'px',
                            ths:{}
                        };
                        $(curCol).closest('tr').find('th').each(function(){
                            settings.ths[$(this).data('field')] = $(this).width() + 'px';
                        });
                        window.localStorage.setItem('table_' + name,JSON.stringify(settings));
                    }
                });

                document.addEventListener('mouseup', function(e) {
                    curCol = undefined;
                    nxtCol = undefined;
                    pageX = undefined;
                    nxtColWidth = undefined;
                    curColWidth = undefined
                });
            }

            function createDiv() {
                var div = document.createElement('div');
                div.style.top = 0;
                div.style.right = 0;
                div.style.width = '5px';
                div.style.position = 'absolute';
                div.style.cursor = 'col-resize';
                div.style.userSelect = 'none';
                div.style.height = '100%';
                div.classList.add("resizeable-th");
                return div;
            }

            function paddingDiff(col) {

                if (getStyleVal(col, 'box-sizing') == 'border-box') {
                    return 0;
                }

                var padLeft = getStyleVal(col, 'padding-left');
                var padRight = getStyleVal(col, 'padding-right');
                return (parseInt(padLeft) + parseInt(padRight));

            }

            function getStyleVal(elm, css) {
                return (window.getComputedStyle(elm, null).getPropertyValue(css))
            }
        }
    }
    
    getTables.initialize = function () {
        getTables.setup();
        // Indicator of active ajax request

        getTables.setPlugins();
        
        //фиксация сдвига заголовков таблицы
        let getTableTop = document.querySelector('.get-table .get-table-top');
        let nlGetTableTH = document.querySelectorAll('.get-table th'); 
        function setHeight(){
            if(getTableTop){  
                if(nlGetTableTH.length > 0){
                    let height = getTableTop.offsetHeight;
                    nlGetTableTH.forEach(item=>{ 
                        item.style.removeProperty('top');
                        item.style.setProperty('top', height + 'px', 'important');
                    });
                }
                
            }
        }
        setHeight()
        window.addEventListener('resize', function(){ 
            setHeight();
        });

        getTables.$doc.on('focus','.get-date,.get-autocomplect-content,.get-table-autosave',function (e) {
            //e.target.autocomplete = "new-password";
            el = e.target;
            //el.value = ' ';
            //getTables.$doc.off('mouseenter','.get-table-td', getTables.mouseenter).off('mouseleave','.get-table-td', getTables.mouseleave);
            function revert(e){
                e.preventDefault();
                e.stopPropagation();
                $(el).attr('name',$(el).data('name')).off('change',revert).removeClass('no-autocomplect').trigger('change');
            };
            if(!$(el).hasClass('no-autocomplect') && typeof($(el).attr('name')) !== "undefined"){
                $(el).data('name',$(el).attr('name')).attr('name',$(el).attr('name') + getTables.Utils.makeid(10))
                .addClass('no-autocomplect').on('change',revert);
            }
        });
        getTables.$doc.on('focus','.get-date',function () {
            if(!$(this).hasClass('air_datepicker')){
                if($(this).val()){
                    var dateString = $(this).val(); //"25/04/1987"; yyyy-mm-dd dd/mm/yyyy
                    dateString = dateString.substring(6, 10)+"-"+dateString.substring(3, 5)+"-"+dateString.substring(0, 2);
                    var startDate = new Date(dateString);
                }else{
                    var startDate = new Date();
                }
                options = {
                    startDate:startDate,
                    autoClose:true,
                    onSelect({datepicker}) {
                        $(datepicker.$el).trigger('change');
                    }
                };
                if($(this).data('options') !='') options = Object.assign(options,$(this).data('options'));
                new AirDatepicker(this,options);
                $(this).addClass('air_datepicker');
            }
        });
        getTables.$doc.on('focus','.get-datetime',function () {
            if(!$(this).hasClass('air_datepicker')){
                $data_options = $(this).data('options');
                if($(this).val()){
                    var dateString = $(this).val(); //"25/04/1987"; yyyy-mm-dd dd/mm/yyyy
                    if($data_options.onlyTimepicker){
                        var startDate = '2000-01-01 ' + dateString;
                        
                    }else{
                        dateString = dateString.substring(6, 10)+"-"+dateString.substring(3, 5)+"-"+dateString.substring(0, 2)+" "+dateString.substring(11);
                        var startDate = new Date(dateString);
                    }
                    
                    
                    
                }else{
                    var startDate = new Date();
                }
                var time1;
                options = {
                    timeFormat:'H:mm',
                    startDate:startDate,
                    autoClose:true,
                    timepicker:true,
                    onSelect({date,datepicker}) {
                        time1 = date; 
                        setTimeout(
                          () => {
                            if(time1 == date){
                                $(datepicker.$el).trigger('change');
                            }
                          },
                          1000
                        );
                    }
                };
                if($data_options !='') options = Object.assign(options,$data_options);
                new AirDatepicker(this,options);
                $(this).addClass('air_datepicker');
            }
        });
        
        
        getTables.$doc.on('click','.get-table-td', function (e) {
            if($(this).find('.fullcontent').length == 1 && $(this).find('.fullcontent').is(":visible")){
                $('.get-table td').css( "height", "");
                $('.get-table td').css( "width", "");
                $('.get-table td').css( "padding", "");
                $('.get-table td').find('.fullcontent').show();
                $('.get-table td').find('.fullcontent-edit').hide();
                
                $(this).css( "height", $(this).css( "height" ));
                $(this).css( "width", $(this).css( "width" ));
                $(this).css( "padding", "0px");
                $(this).find('.fullcontent').hide();
                $(this).find('.fullcontent-edit').show();
                 
                if($(this).find('.get-autocomplect-content').length){
                    $focus = $(this).find('.get-autocomplect-content');
                }else if($(this).find('.get-table-autosave').length){
                    $focus = $(this).find('.get-table-autosave');
                }
                if(typeof($focus) !== "undefined"){
                    $focus.css( "height", ($(this).height() - 1) + 'px');
                    $focus.css( "width", ($(this).width() - 1) + 'px');
                    $focus.trigger("focus");
                    setTimeout(function() {$focus.select();}, 0);
                } 
            }
            
            
        });
        
        getTables.$doc.on('keydown','.get-table-autosave,.get-autocomplect-content',function (e) {
            if(!e.ctrlKey) return;
            //e.preventDefault();
            let $td0 = $(this).closest('td');
            let $td = $(this).closest('td');
            let $focus;
            if(e.code == 'ArrowLeft'){
                for(i=1;i<10;i++){
                    if($td.prev().prop("tagName") == 'TD'){
                        $td = $td.prev();
                        if($td.find('.get-autocomplect-content').length){
                            $focus = $td.find('.get-autocomplect-content');
                            
                        }else if($td.find('.get-table-autosave').length){
                            $focus = $td.find('.get-table-autosave');
                        }
                    }
                    if(typeof($focus) !== "undefined" && !$focus.prop("readonly")) break;
                }
            }
            if(e.code == 'ArrowRight'){
                for(i=1;i<10;i++){
                    if($td.next().prop("tagName") == 'TD'){
                        $td = $td.next();
                        if($td.find('.get-autocomplect-content').length){
                            $focus = $td.find('.get-autocomplect-content');
                            
                        }else if($td.find('.get-table-autosave').length){
                            $focus = $td.find('.get-table-autosave');
                        }
                    }
                    if(typeof($focus) !== "undefined" && !$focus.prop("readonly")) break;
                }
            }
            $tr = $(this).closest('tr');
            if(e.code == 'ArrowDown'){
                for(i=1;i<10;i++){
                    if($tr.next().prop("tagName") == 'TR'){
                        $tr = $tr.next();
                        if($tr.find('[data-field="'+$td.data('field')+'"]').find('.get-autocomplect-content').length){
                            $focus = $tr.find('[data-field="'+$td.data('field')+'"]').find('.get-autocomplect-content');
                        }else if($tr.find('[data-field="'+$td.data('field')+'"]').find('.get-table-autosave').length){
                            $focus = $tr.find('[data-field="'+$td.data('field')+'"]').find('.get-table-autosave');
                        }
                    }
                    if(typeof($focus) !== "undefined" && !$focus.prop("readonly")) break;
                }
            }
            if(e.code == 'ArrowUp'){
                for(i=1;i<10;i++){
                    if($tr.prev().prop("tagName") == 'TR'){
                        $tr = $tr.prev();
                        if($tr.find('[data-field="'+$td.data('field')+'"]').find('.get-autocomplect-content').length){
                            $focus = $tr.find('[data-field="'+$td.data('field')+'"]').find('.get-autocomplect-content');
                        }else if($tr.find('[data-field="'+$td.data('field')+'"]').find('.get-table-autosave').length){
                            $focus = $tr.find('[data-field="'+$td.data('field')+'"]').find('.get-table-autosave');
                        }
                    }
                    if(typeof($focus) !== "undefined" && !$focus.prop("readonly")) break;
                }
                
            }
            
            if(typeof($focus) !== "undefined"){
                //getTables.$doc.off('mouseenter','.get-table-td', getTables.mouseenter).off('mouseleave','.get-table-td', getTables.mouseleave);
                if($focus.closest('td').find('.fullcontent').length == 1){
                    $focus.closest('td').find('.fullcontent').hide();
                    $focus.closest('td').find('.fullcontent-edit').show();
                    $td0.find('.fullcontent').show();
                    $td0.find('.fullcontent-edit').hide();
                }
                $focus.trigger("focus");
                setTimeout(function() {$focus.select();}, 0);
            }
            
        });
       
        //noinspection JSUnresolvedFunction
        getTables.$doc
            .ajaxStart(function () {
                getTables.ajaxProgress = true;
            })
            .ajaxStop(function () {
                getTables.ajaxProgress = false;
            })
            .on('submit', getTables.form, function (e) {
                e.preventDefault();
                var $form = $(this);
                var action = $form.find(getTables.action).val();
                //console.log('action',action);

                if (action) {
                    var formData = $form.serializeArray();
                    formData.push({
                        name: getTables.actionName,
                        value: action
                    });
                    $GtsApp = getTables.sendData.$GtsApp;
                    getTables.sendData = {
                        $form: $form,
                        $GtsApp: $GtsApp,
                        action: action,
                        data: formData
                    };
                    //$GtsApp: getTables.sendData.$GtsApp,
                    //console.info('action',action);
                    getTables.controller();
                }
            });
        //noinspection JSUnresolvedFunction
        getTables.$doc
            .on('click', '.arr-btn__top', function (e) {
                e.preventDefault();
                let $currentInput = $(this).parent().children('.get-autocomplect-id');
                $currentInput.val(+$currentInput.val() + 1).trigger('change');
            });
        getTables.$doc
            .on('click', '.arr-btn__bottom', function (e) {
                e.preventDefault();
                let $currentInput = $(this).parent().children('.get-autocomplect-id');
                $currentInput.val(+$currentInput.val() - 1).trigger('change');
            });

        getTables.Modal.initialize();
        getTables.Table.initialize();
        getTables.Form.initialize();
        getTables.Tree.initialize();
        getTables.resizableGrid.init();
    };
    getTables.controller = function () {
        var self = this;
        //console.log('self',self);
        switch (self.sendData.action) {
            case 'getTable/create':
                getTables.Table.update();
                break;
            case 'getTable/update':
                getTables.Table.update();
                break;
            case 'getTable/remove':
                getTables.Table.remove2();
                break;
            case 'getTree/remove':
                getTables.Tree.remove();
                break;
            default:
                getTables.Table.custom();
        }
    };
    getTables.send = function (data, callbacks, userCallbacks) {
        var runCallback = function (callback, bind) {
            if (typeof callback == 'function') {
                return callback.apply(bind, Array.prototype.slice.call(arguments, 2));
            } else if (typeof callback == 'object') {
                for (var i in callback) {
                    if (callback.hasOwnProperty(i)) {
                        var response = callback[i].apply(bind, Array.prototype.slice.call(arguments, 2));
                        if (response === false) {
                            return false;
                        }
                    }
                }
            }
            return true;
        };
        // set context
        if ($.isArray(data)) {
            data.push({
                name: 'ctx',
                value: getTablesConfig.ctx
            });
        } else if ($.isPlainObject(data)) {
            data.ctx = getTablesConfig.ctx;
        } else if (typeof data == 'string') {
            data += '&ctx=' + getTablesConfig.ctx;
        }
        // set pageID
        const params = new Proxy(new URLSearchParams(window.location.search), {
            get: (searchParams, prop) => searchParams.get(prop),
          });
        if(params.id){
            if ($.isArray(data)) {
                data.push({
                    name: 'pageID',
                    value: params.id
                });
            } else if ($.isPlainObject(data)) {
                data.pageID = params.id;
            } else if (typeof data == 'string') {
                data += '&pageID=' + params.id;
            }
        }

        // set action url
        var formActionUrl = (getTables.sendData.$form) ?
            getTables.sendData.$form.attr('action') :
            false;
        var url = (formActionUrl) ?
            formActionUrl :
            (getTablesConfig.actionUrl) ?
            getTablesConfig.actionUrl :
            document.location.href;
        // set request method
        var formMethod = (getTables.sendData.$form) ?
            getTables.sendData.$form.attr('method') :
            false;
        var method = (formMethod) ?
            formMethod :
            'post';
        //console.info(getTables.sendData,formActionUrl,url);
        // callback before
        if (runCallback(callbacks.before) === false || runCallback(userCallbacks.before) === false) {
            return;
        }
        // send
        var xhr = function (callbacks, userCallbacks) {
            return $[method](url, data, function (response) {
                if (response.success) {
                    if (response.message) {
                        getTables.Message.success(response.message);
                    }
                    
                    runCallback(callbacks.response.success, getTables, response);
                    runCallback(userCallbacks.response.success, getTables, response);
                } else {
                    getTables.Message.error(response.message);
                    runCallback(callbacks.response.error, getTables, response);
                    runCallback(userCallbacks.response.error, getTables, response);
                }
            }, 'json').done(function () {
                runCallback(callbacks.ajax.done, getTables, xhr);
                runCallback(userCallbacks.ajax.done, getTables, xhr);
            }).fail(function () {
                runCallback(callbacks.ajax.fail, getTables, xhr);
                runCallback(userCallbacks.ajax.fail, getTables, xhr);
            }).always(function (response) {

                runCallback(callbacks.ajax.always, getTables, xhr);
                runCallback(userCallbacks.ajax.always, getTables, xhr);
                //if(getTablesConfig.showLog){
                if (response.log) {
                    $('.getTablesLog').remove();
                    if (getTables.sendData.$GtsApp) {
                        getTables.sendData.$GtsApp.after(response.log);
                    } else {
                        $('body').append(response.log);
                    }
                }

                //}
            });
        }(callbacks, userCallbacks);
    };



    getTables.Utils = {

        getValueFromSerializedArray: function (name, arr) {
            if (!$.isArray(arr)) {
                arr = getTables.sendData.formData;
            }
            for (var i = 0, length = arr.length; i < length; i++) {
                if (arr[i].name == name) {
                    return arr[i].value;
                }
            }
            return null;
        },
        findGtsApp: function (name, hash) {
            $GtsApp = $("[data-name='" + name + "'][data-hash='" + hash + "']")
            if ($GtsApp.length == 1) {
                return $GtsApp;
            } else if ($GtsApp.length > 1) {
                console.log("слишком много приложений!");
            } else {
                console.log("Приложение не найдено!");
            }
            return null;
        },
        makeid: function(length) {
            let result = '';
            const characters = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789';
            const charactersLength = characters.length;
            let counter = 0;
            while (counter < length) {
              result += characters.charAt(Math.floor(Math.random() * charactersLength));
              counter += 1;
            }
            return result;
        }
    };


    getTables.Modal = {
        callbacks: {
            load: getTablesConfig.callbacksObjectTemplate(),

        },
        setup: function () {

        },
        initialize: function () {
            getTables.Modal.setup();
            getTables.$doc.on('hidden.bs.modal', function (event) {
                if($(event.target).hasClass('excel-calc-modal')) return;
                $('.gts_modal').remove();
            });
            getTables.$doc.on('shown.bs.modal', function (event) {
                getTables.setPlugins();
            });

        },

        show: function (modal_html) {
            $(modal_html).modal('show');
        },
        load: function (button_data, table_data, tr_data) {
            getTables.Message.close();

            getTables.sendData.data = {
                gts_action: button_data.modal,
                hash: table_data.hash,
                table_data: table_data,
                button_data: button_data,
                tr_data: tr_data
            };

            var callbacks = getTables.Modal.callbacks;

            callbacks.load.response.success = function (response) {
                getTables.Modal.show(response.data.html);
            };

            return getTables.send(getTables.sendData.data, getTables.Modal.callbacks.load, getTables.Callbacks.Modal.load);
        },
        close: function () {
            $('.gts_modal').modal('hide');
        },
    };

    getTables.Tree = {
        callbacks: {
            load_panel: getTablesConfig.callbacksObjectTemplate(),
            action: getTablesConfig.callbacksObjectTemplate(),
            remove: getTablesConfig.callbacksObjectTemplate(),
            expand: getTablesConfig.callbacksObjectTemplate(),
        },
        setup: function () {

        },
        initialize: function () {
            getTables.Tree.setup();
            
            getTables.$doc
                .on('click', '.treeMain .caret1', function (e) {
                    e.preventDefault();
                    let expand = false;
                    if(!$(this).hasClass('caret-down1')){
                        $(this).closest('li').find('.collapsed').removeClass('collapsed').addClass('expanded');
                        $(this).addClass('caret-down1');
                        expand = true;
                    }else{
                        $(this).closest('li').find('.expanded').removeClass('expanded').addClass('collapsed');
                        $(this).removeClass('caret-down1');
                    }
                    
                    $li = $(this).closest('.get-tree-li');
                    $tree = $(this).closest('.get-tree');
                    tree_data = $tree.data();
                    let expanded_ids = [];
                    $tree.find('.get-tree-ul.expanded').each(function(){
                        expanded_ids.push($(this).parent().data("id"));
                    });
                    getTables.sendData.$GtsApp = $tree;
                    getTables.sendData.data = {
                        gts_action: 'getTree/expand',
                        hash: tree_data.hash,
                        tree_name: tree_data.name,
                        id: $li.data('id'),
                        expand: expand,
                        expanded_ids: expanded_ids,
                    };

                    var callbacks = getTables.Tree.callbacks;
        
                    callbacks.expand.response.success = function (response) {
                        //console.log('callbacks.filter_checkbox_load.response.success',response);
                        //if(response.data.modal) getTables.Modal.show(response.data.modal);
                        //$('.get-tree-panel').html(response.data.html);
                    };
        
                    return getTables.send(getTables.sendData.data, getTables.Tree.callbacks.expand, getTables.Callbacks.Tree.expand);
                    
                });
            getTables.$doc
                .on('click', '.get-tree-li .get-tree-a', function (e) {
                    e.preventDefault();
                    $li = $(this).closest('.get-tree-li');
                    $tree = $(this).closest('.get-tree');
                    tree_data = $tree.data();

                    getTables.sendData.$li = $li;
                    getTables.sendData.$GtsApp = $tree;
                    getTables.sendData.data = {
                        gts_action: 'getTree/load_panel',
                        hash: tree_data.hash,
                        tree_name: tree_data.name,
                        id: $li.data('id'),
                    };

                    var callbacks = getTables.Tree.callbacks;
        
                    callbacks.load_panel.response.success = function (response) {
                        //console.log('callbacks.filter_checkbox_load.response.success',response);
                        $('.get-tree-panel').html(response.data.html);
                        $tree.find('.get-tree-li').removeClass('active');
                        $li.addClass('active');
                        window.history.pushState({}, '', document.location.pathname+'?id='+$li.data('id'));
                        getTables.setPlugins();
                    };
        
                    return getTables.send(getTables.sendData.data, getTables.Tree.callbacks.load_panel, getTables.Callbacks.Tree.load_panel);
                });
            getTables.$doc
                .on('click', '.get-tree-li .get-tree-action', function (e) {
                    e.preventDefault();
                    $li = $(this).closest('.get-tree-li');
                    $tree = $(this).closest('.get-tree');
                    tree_data = $tree.data();

                    getTables.sendData.$li = $li;
                    getTables.sendData.$GtsApp = $tree;
                    getTables.sendData.data = {
                        gts_action: $(this).data('action'),
                        action_key: $(this).data('action_key'),
                        hash: tree_data.hash,
                        tree_name: tree_data.name,
                        id: $li.data('id'),
                    };

                    var callbacks = getTables.Tree.callbacks;
        
                    callbacks.action.response.success = function (response) {
                        //console.log('callbacks.filter_checkbox_load.response.success',response);
                        if(response.data.modal) getTables.Modal.show(response.data.modal);
                        getTables.setPlugins();
                        //$('.get-tree-panel').html(response.data.html);
                    };
        
                    return getTables.send(getTables.sendData.data, getTables.Tree.callbacks.action, getTables.Callbacks.Tree.action);
                });
        },
        remove: function () {
            getTables.Message.close();
            var callbacks = getTables.Table.callbacks;

            callbacks.custom.response.success = function (response) {
                
                if(response.data.reload_without_id) document.location.href=document.location.pathname;
                if(response.data.modal_close) getTables.Modal.close();
                if(response.data.replace) $(response.data.replace.selector).replaceWith(response.data.replace.html);
                //getTables.Table.refresh();
            };

            return getTables.send(getTables.sendData.data, getTables.Table.callbacks.custom, getTables.Callbacks.Table.custom);
        },
    };
    getTables.Form = {
        callbacks: {
            save: getTablesConfig.callbacksObjectTemplate(),
            autosave: getTablesConfig.callbacksObjectTemplate(),
        },
        setup: function () {

        },
        initialize: function () {
            getTables.Form.setup();
            
            getTables.setPlugins();

            getTables.$doc
                .on('click', '.btn-gts-getform', function (e) {
                    e.preventDefault();
                    $form = $(this).closest('.gts-getform');
                    action = $(this).val();
                    getTables.Form.save(action, $form);
                });
        },

        save: function (action, $form) {
            getTables.Message.close();

            getTables.sendData.$form = $form;
            if(getTablesConfig.load_ckeditor == 1){
                for(var instanceName in CKEDITOR.instances)
                    CKEDITOR.instances[instanceName].updateElement();
            }
            getTables.sendData.data = $form.serializeArray();
            getTables.sendData.data.push({
                name: 'action',
                value: action
            });

            var callbacks = getTables.Form.callbacks;

            callbacks.save.response.success = function (response) {
                if(response.data.reload_with_id && response.data.id){
                    document.location.href = document.location.pathname+'?id='+response.data.id;
                }
                if(response.data.close_modal) getTables.Modal.close();
                if(response.data.modal) getTables.Modal.show(response.data.modal);
                if(response.data.replaceHtml){
                    $.each(response.data.replaceHtml, function( key, value ) {
                        //console.log( 'Свойство: ' +key + '; Значение: ' + value );
                        $(key).html(value);
                    });
                }
                if(response.data.refresh_table == 1) getTables.Table.refresh();
                if(response.data.update_form){
                    $('.get-table-top form').replaceWith(response.data.update_form);
                }
            };

            return getTables.send(getTables.sendData.data, getTables.Form.callbacks.save, getTables.Callbacks.Form.save);
        },

    };

    getTables.Table = {
        callbacks: {
            update: getTablesConfig.callbacksObjectTemplate(),
            refresh: getTablesConfig.callbacksObjectTemplate(),
            //filter: getTablesConfig.callbacksObjectTemplate(),
            custom: getTablesConfig.callbacksObjectTemplate(),
            sets: getTablesConfig.callbacksObjectTemplate(),
            sub_show: getTablesConfig.callbacksObjectTemplate(),
            //sub_hide: getTablesConfig.callbacksObjectTemplate(),
            remove: getTablesConfig.callbacksObjectTemplate(),
            autosave: getTablesConfig.callbacksObjectTemplate(),
            filter_checkbox_load: getTablesConfig.callbacksObjectTemplate(),
            get_tree_child: getTablesConfig.callbacksObjectTemplate(),
            long_process: getTablesConfig.callbacksObjectTemplate(),
            insert: getTablesConfig.callbacksObjectTemplate(),
        },
        setup: function () {
            
        },
        initialize: function () {
            //checkbox all
            getTables.$doc
                .on('change', '.get-table-check-all', function (e) {
                    $(this).closest('.get-table-table').children('.get-table-tbody').children('.get-table-tr').find('.get-table-check-row').prop('checked', $(this).prop('checked'));
                });

            getTables.$doc
                .on('click', 'button.get-table-in_all_page', function (e) {
                    e.preventDefault();
                    $table = $(this).closest('.get-table');
                    if ($table.hasClass('in_all_page')) {
                        $table.removeClass('in_all_page');
                    } else {
                        $table.addClass('in_all_page');
                    }
                });
            getTables.$doc
                .on('click', 'button.get-table-close-subtable', function (e) {
                    e.preventDefault();
                    $table = $(this).closest('.get-table');
                    $table.closest('.get-sub-row').remove();
                    $table.remove();
                });
            getTables.$doc
                .on('click', 'button.get-table-row', function (e) {
                    e.preventDefault();
                    $table = $(this).closest('.get-table');

                    button_data = $(this).data();
                    table_data = $table.data();
                    $row = $(this).closest('.get-table-tr');
                    tr_data = $row.data();

                    getTables.sendData.$GtsApp = $table;
                    getTables.sendData.$row = $row;
                    //console.info('button.get-table-row');
                    if (typeof (button_data.modal) != "undefined") {
                        getTables.Modal.load(button_data, table_data, tr_data);
                    }else if (button_data.name == 'subtable') {
                        if (button_data.js_action != "undefined")
                            getTables.Table[button_data.js_action](button_data, table_data, tr_data);
                    }else if (button_data.action == 'getTable/sub') {
                            getTables.Table.sub(button_data, table_data, tr_data);
                    }else if (button_data.name == 'remove') {
                        trs_data = [tr_data];
                        getTables.Table.remove(button_data, table_data, trs_data);
                    }else if (button_data.long_process == 1) {
                        trs_data = [tr_data];
                        getTables.Table.long_process(button_data, table_data, trs_data);
                    }else {
                        trs_data = [tr_data];
                        getTables.Table.sets(button_data, table_data, trs_data);
                    }
                });
            getTables.$doc
                .on('click', 'button.get-table-search', function (e) {
                    e.preventDefault();
                    $table = $(this).closest('.get-table');
                    getTables.sendData.$GtsApp = $table;
                    $table.children(".get-table-paginator-container").find('[name="page"]').val(1);

                    getTables.Table.refresh();
                });
            getTables.$doc
                .on('click', 'button.get-table-reset', function (e) {
                    e.preventDefault();
                    $table = $(this).closest('.get-table');

                    getTables.sendData.$GtsApp = $table;
                    $table.children(".get-table-filter-container").find('.get-table-filter').each(function(){
                        $(this).val("");
                    });

                    $th = $table.children( "table" ).children( "thead" ).children( "tr" ).children('th');
                    
                    $th.each(function(){
                        $(this).find('.filtr-btn').removeClass('filter-active filter');
                        filterclass = 'filter';
                        $(this).find('.get-table-filter').val("");
                        $(this).find('.filtr-btn').addClass(filterclass);
                    });
                    
                    $table.children(".get-table-paginator-container").find('[name="page"]').val(1);

                    getTables.Table.refresh();
                });

            getTables.$doc
                .on('click', 'button.get-table-first', function (e) {
                    e.preventDefault();
                    $table = $(this).closest('.get-table');
                    getTables.sendData.$GtsApp = $table;

                    button_data = $(this).data();
                    table_data = $table.data();
                    tr_data = [];

                    getTables.sendData.$GtsApp = $table;
                    if (typeof (button_data.modal) != "undefined") {
                        getTables.Modal.load(button_data, table_data, tr_data);
                    }else if(button_data.action == 'getTable/insert'){
                        getTables.Table.insert($table,button_data);
                    }
                });
            getTables.$doc
                .on('click', 'button.get-table-multiple', function (e) {
                    e.preventDefault();
                    $table = $(this).closest('.get-table');

                    button_data = $(this).data();
                    table_data = $table.data();
                    if ($(this).data('action') == 'getTable/export_excel') {
                        filters = getTables.Table.getFilters($table);
                        table_data = $table.data();
                        if(table_data.sub_where_current){
                            filters['sub_where_current'] = table_data.sub_where_current;
                        }
                        filters['hash'] = table_data.hash;
                        filters['table_name'] = table_data.name;
                        query = $.param(filters);
                        window.open(getTablesConfig.actionUrl + '?' + query + '&gts_action=getTable/export_excel&ctx=' + getTablesConfig.ctx, '_blank');
                        return;
                    }
                    $trs_check = $table.children('.get-table-table').children('.get-table-tbody').children('.get-table-tr').find('.get-table-check-row:checked');

                    trs_data = [];
                    $trs = [];
                    $trs_check.each(function () {
                        $trs.push($(this).closest('.get-table-tr'));
                        trs_data.push($(this).closest('.get-table-tr').data());
                    });
                    getTables.sendData.$trs = $trs;

                    getTables.sendData.$GtsApp = $table;
                    //console.info('$trs_check',$trs_check);
                    //console.info('trs_data',trs_data);
                    if (typeof (button_data.modal) != "undefined") {
                        getTables.Modal.load(button_data, table_data, trs_data);
                    }else if (button_data.name == 'remove') {
                        getTables.Table.remove(button_data, table_data, trs_data);
                    }else if (button_data.long_process == 1) {
                        getTables.Table.long_process(button_data, table_data, trs_data);
                    }else{
                        getTables.Table.sets(button_data, table_data, trs_data);
                    }
                });
            //paginator
            getTables.$doc
                .on('click', 'button.get-nav-first', function (e) {
                    e.preventDefault();
                    $table = $(this).closest('.get-table');
                    getTables.sendData.$GtsApp = $table;

                    page = +$table.children(".get-table-paginator-container").find('.get-nav-page').val();
                    if (page > 1) {
                        $table.children(".get-table-paginator-container").find('.get-nav-page').val(1);
                        getTables.Table.refresh();
                    }
                });
            getTables.$doc
                .on('click', 'button.get-nav-prev', function (e) {
                    e.preventDefault();
                    $table = $(this).closest('.get-table');
                    getTables.sendData.$GtsApp = $table;

                    page = +$table.children(".get-table-paginator-container").find('.get-nav-page').val();
                    if (page > 1) {
                        $table.children(".get-table-paginator-container").find('.get-nav-page').val(page - 1);
                        getTables.Table.refresh();
                    }
                });
            getTables.$doc
                .on('click', 'button.get-nav-next', function (e) {
                    e.preventDefault();
                    $table = $(this).closest('.get-table');
                    getTables.sendData.$GtsApp = $table;

                    page = +$table.children(".get-table-paginator-container").find('.get-nav-page').val();
                    page_max = +$table.children(".get-table-paginator-container").find('.get-nav-page').prop('max');
                    if (page < page_max) {
                        $table.children(".get-table-paginator-container").find('.get-nav-page').val(+page + 1);
                        getTables.Table.refresh();
                    }
                });
            getTables.$doc
                .on('click', 'button.get-nav-last', function (e) {
                    e.preventDefault();
                    $table = $(this).closest('.get-table');
                    getTables.sendData.$GtsApp = $table;

                    page = +$table.children(".get-table-paginator-container").find('.get-nav-page').val();
                    page_max = +$table.children(".get-table-paginator-container").find('.get-nav-page').prop('max');
                    if (page < page_max) {
                        $table.children(".get-table-paginator-container").find('.get-nav-page').val(page_max);
                        getTables.Table.refresh();
                    }
                });
            getTables.$doc
                .on('click', 'button.get-nav-refresh', function (e) {
                    e.preventDefault();
                    $table = $(this).closest('.get-table');
                    getTables.sendData.$GtsApp = $table;

                    getTables.Table.refresh();
                });
            getTables.$doc
                .on('change', '.get-table-checkbox-hidden', function (e) {
                    e.preventDefault();
                    if ($(this).is(':checked')) {
                        $(this).next().val(1).trigger('change');
                    } else {
                        $(this).next().val(0).trigger('change');
                    }

                });
            getTables.$doc
                .on('change', '.get-table-autosave', function (e) {
                    e.preventDefault();
                    $autocomplect = $(this).closest('.get-autocomplect');
                    if($autocomplect.length){
                        $menu = $autocomplect.find('.get-autocomplect-menu');
                        if($menu.is(':visible') && !$(this).val()){
                            return;
                        }
                    }
                    $table = $(this).closest('.get-table');

                    button_data = $(this).data();
                    table_data = $table.data();
                    $row = $(this).closest('.get-table-tr');
                    tr_data = $row.data();

                    getTables.sendData.$GtsApp = $table;
                    getTables.sendData.$row = $row;
                    getTables.sendData.$input = $(this);
                    //console.info('button.get-table-row');
                    field = $(this).data('field');
                    value = $(this).val();
                    fname = $(this).attr('name');
                    if (typeof (tr_data[field]) != "undefined") {
                        $row.data(field, value);
                    }
                    getTables.sendData.data = {
                        gts_action: 'getTable/autosave',
                        hash: table_data.hash,
                        table_name: table_data.name,
                        table_data: table_data,
                        td: {
                            field: field,
                            name: fname,
                            value: value
                        },
                        tr_data: tr_data
                    };
					if($autocomplect.length){
						if($autocomplect.find('.get-autocomplect-content').data('name') !=''){
							value = $autocomplect.find('.get-autocomplect-content').val();
							fname = $autocomplect.find('.get-autocomplect-content').data('name');
							getTables.sendData.data.td2 = {
								name: fname,
								value: value
							};
						}
					}
					
                    getTables.Table.autosave(field, value, table_data, tr_data);
                });
            getTables.$doc
                .on('change', '.get-table-filter', function (e) {
                    e.preventDefault();
                    
                    $th = $(this).closest('th');
                    getTables.Table.check_filter($th);

                    $table = $(this).closest('.get-table');
                    $table.children(".get-table-paginator-container").find('[name="page"]').val(1);
                    getTables.sendData.$GtsApp = $table;
                    getTables.Table.refresh();
                });
            getTables.$doc
                .on('click', '.filtr-btn-clear', function (e) {
                    e.preventDefault();
                    
                    $th = $(this).closest('th');
                    
                    
                    $th.find('.get-table-filter').each(function(){
                        if($(this).prop("type") == 'checkbox'){
                            $(this).prop("checked",false);
                        }else{
                            $(this).val("");
                        }
                    });
                    $th.find('.filrt-checkbox-select-all').prop('checked', true);
                    $th.find('.filrt-checkbox-input').prop('checked', true);
                    $th.find('.filtr-btn').removeClass('filter-active filter filter-sort-down filter-sort-up');
                    $th.find('.get-table-sort-sortdir.active').removeClass('active');
                    $th.find('.filtr-btn').addClass('filter');

                    $table = $(this).closest('.get-table');
                    $table.children(".get-table-paginator-container").find('[name="page"]').val(1);

                    getTables.sendData.$GtsApp = $table;
                    getTables.Table.refresh();
                });
            
            //filtr-checkbox
            getTables.$doc
                .on('click', '.filtr-btn-checkbox-load', function (e) {
                    e.preventDefault();
                    
                    $th = $(this).closest('th');
                    $table = $(this).closest('.get-table');
                    table_data = $table.data();

                    getTables.sendData.$th = $th;
                    getTables.sendData.$GtsApp = $table;
                    getTables.sendData.data = {
                        gts_action: 'getTable/filter_checkbox_load',
                        hash: table_data.hash,
                        table_name: table_data.name,
                        table_data: table_data,
                        field: $th.data('field')
                    };
                    if($table.data('sub_where_current') !== undefined){
                        getTables.sendData.data['sub_where_current'] = $table.data('sub_where_current');
                    }
                    if($table.data('parent_current') !== undefined){
                        getTables.sendData.data['parent_current'] = $table.data('parent_current');
                    }
                    filters = getTables.Table.getFilters($table);
                    
                    $.each(filters, function( key, value ) {
                        //console.log( 'Свойство: ' +key + '; Значение: ' + value );
                        getTables.sendData.data[key] = value;
                    });

                    var callbacks = getTables.Table.callbacks;
        
                    callbacks.filter_checkbox_load.response.success = function (response) {
                        //console.log('callbacks.filter_checkbox_load.response.success',response);
                        getTables.sendData.$th.find('.filrt-checkbox-container').html(response.data.html);
                        getTables.sendData.$th.find('.filtr-btn-checkbox-apply').show();
                    };
        
                    return getTables.send(getTables.sendData.data, getTables.Table.callbacks.filter_checkbox_load, getTables.Callbacks.Table.filter_checkbox_load);
                });
            getTables.$doc
                .on('change', '.filrt-checkbox-select-all', function (e) {
                    $(this).closest('.filrt-checkbox-ul').find('.filrt-checkbox-input').prop('checked', $(this).prop('checked'));
                    $th = $(this).closest('th');
                    getTables.Table.check_filter($th);
                });
            getTables.$doc
                .on('change', '.filrt-checkbox-input', function (e) {
                    $th = $(this).closest('th');
                    getTables.Table.check_filter($th);
                });
            getTables.$doc
                .on('click', '.filtr-btn-checkbox-apply', function (e) {
                    e.preventDefault();
                    $table = $(this).closest('.get-table');
                    getTables.sendData.$GtsApp = $table;

                    getTables.Table.refresh();
                });
            getTables.$doc
                .on('click', '.get-table-sort-sortdir', function (e) {
                    $th = $(this).closest('th');
                    if($(this).hasClass('active')){
                        $(this).removeClass('active');
                    }else{
                        $th.find('.get-table-sort-sortdir').removeClass('active');
                        $(this).addClass('active');
                    }
                    getTables.Table.check_filter($th);
                    $table = $(this).closest('.get-table');
                    getTables.sendData.$GtsApp = $table;

                    getTables.Table.refresh();
                });
            getTables.$doc
                .on('click', '.get-table-sort-rank', function (e) {
                    $table = $(this).closest('.get-table');
                    getTables.sendData.$GtsApp = $table;

                    getTables.Table.refresh();
                });
            getTables.$doc
                .on('click', '.gtstree-expander-expanded', function (e) {
                    e.preventDefault();
                    $table = $(this).closest('.get-table');
                    $row = $(this).closest('.get-table-tr');
                    $row.find('.gtstree-expander').removeClass('gtstree-expander-expanded').addClass('gtstree-expander-collapsed');
                    //$childs = $table.find('tr[data-gts_tree_parent="'+$row.data('gts_tree_child')+'"]').remove();
                    getTables.Table.childs_remove($table,$row.data('gts_tree_child'));
                    
                });
            getTables.$doc
                .on('click', '.gtstree-expander-collapsed', function (e) {
                    e.preventDefault();
                    $table = $(this).closest('.get-table');
                    $row = $(this).closest('.get-table-tr');

                    getTables.sendData.$GtsApp = $table;
                    getTables.sendData.$row = $row;

                    hash = $table.data('hash');
                    gts_tree = $(this).data();
                    getTables.sendData.data = {
                        gts_action: 'getTable/get_tree_child',
                        hash: hash,
                        table_name:$table.data('name'),
                        gts_tree: gts_tree,
                    };
                    if($table.data('sub_where_current') !== undefined){
                        getTables.sendData.data['sub_where_current'] = $table.data('sub_where_current');
                    }
                    if($table.data('parent_current') !== undefined){
                        getTables.sendData.data['parent_current'] = $table.data('parent_current');
                    }
                    filters = getTables.Table.getFilters($table);
                    
                    $.each(filters, function( key, value ) {
                        //console.log( 'Свойство: ' +key + '; Значение: ' + value );
                        getTables.sendData.data[key] = value;
                    });
                    getTables.sendData.data['page'] = $table.children(".get-table-paginator-container").find('[name="page"]').val();
                    getTables.sendData.data['limit'] = 0;
                    var callbacks = getTables.Table.callbacks;

                    callbacks.get_tree_child.response.success = function (response) {
                        $row = getTables.sendData.$row;
                        $row.find('.gtstree-expander').removeClass('gtstree-expander-collapsed').addClass('gtstree-expander-expanded');
                        $row.after(response.data.html);

                        getTables.setPlugins();
                    };

                    return getTables.send(getTables.sendData.data, getTables.Table.callbacks.get_tree_child, getTables.Callbacks.Table.get_tree_child);
            });
            getTables.$doc
                .on('keyup', '.gts-form input', function (e) {
                    if($(this).hasClass('error')) $(this).removeClass('error');
                });
            getTables.$doc
                .on('change', '.gts-form select', function (e) {
                    if($(this).hasClass('error')) $(this).removeClass('error');
                });
            
        },
        insert: function ($table, button_data) {
            getTables.Message.close();
            table_data = $table.data();

            getTables.sendData.data = {
                gts_action: button_data.action,
                hash: table_data.hash,
                table_name: table_data.name,
                table_data: table_data,
                button_data: button_data,
            };

            var callbacks = getTables.Table.callbacks;

            callbacks.insert.response.success = function (response) {
                getTables.Table.refresh();
            };

            return getTables.send(getTables.sendData.data, getTables.Table.callbacks.insert, getTables.Callbacks.Table.insert);
        },
        childs_remove: function ($table,gts_tree_parent) {
            $childs = $table.find('tr[data-gts_tree_parent="'+gts_tree_parent+'"]');
            $childs.each(function(){
                getTables.Table.childs_remove($table,$(this).data('gts_tree_child'));
                $(this).remove();
            });
        },
        
        autosave: function (field, value, table_data, tr_data) {
            getTables.Message.close();

            // Checking for active ajax request
            /*if (getTables.ajaxProgress) {
                //noinspection JSUnresolvedFunction
                getTables.$doc.ajaxComplete(function () {
                    getTables.ajaxProgress = false;
                    getTables.$doc.unbind('ajaxComplete');
                    getTables.Modal.load();
                });
                return false;
            }*/

            var callbacks = getTables.Table.callbacks;

            callbacks.autosave.response.success = function (response) {
                if(response.data.refresh_table == 1) getTables.Table.refresh();
                if(response.data.update_form){
                    $('.get-table-top form').replaceWith(response.data.update_form);
                }
                if(response.data.update_row){
                    $new_row =$(response.data.update_row);
                    getTables.sendData.$row.attr('class',$new_row.attr('class'));
                    getTables.sendData.$row.find('td').each(function(){
                        let $focus;
                        if($(this).find('.get-autocomplect-content').length){
                            $focus = $(this).find('.get-autocomplect-content');
                            
                        }else if($(this).find('.get-table-autosave').length){
                            $focus = $(this).find('.get-table-autosave');
                        }
                        if(typeof($focus) !== "undefined"){
                            if(!$focus.is(':focus')){
                                $td = $new_row.find('td[data-field="'+$(this).data('field')+'"]');
                                $(this).replaceWith($td);
                            }else if($focus.closest('.get-table-td').find('.fullcontent').length == 1){
                                $focus.closest('.get-table-td').find('.fullcontent').text($new_row.find('td[data-field="'+$(this).data('field')+'"]').find('.fullcontent').text());
                            }
                        }else{
                            $td = $new_row.find('td[data-field="'+$(this).data('field')+'"]');
                            $(this).replaceWith($td);
                        }
                        
                    });
                }
                
            };

            return getTables.send(getTables.sendData.data, getTables.Table.callbacks.autosave, getTables.Callbacks.Table.autosave);
        },
        
        remove: function (button_data, table_data, trs_data) {
            getTables.Message.close();

            getTables.sendData.data = {
                gts_action: 'getModal/fetchModalRemove',
                hash: table_data.hash,
                table_name: table_data.name,
                table_data: table_data,
                button_data: button_data,
            };

            var callbacks = getTables.Modal.callbacks;

            callbacks.load.response.success = function (response) {
                getTables.Modal.show(response.data.html);
            };

            getTables.send(getTables.sendData.data, getTables.Modal.callbacks.load, getTables.Callbacks.Modal.load);

            getTables.temp = {
                gts_action: button_data.action,
                hash: table_data.hash,
                table_name: table_data.name,
                table_data: table_data,
                button_data: button_data,
                trs_data: trs_data
            };
            return;
        },
        remove2: function () {
            getTables.Message.close();

            var callbacks = getTables.Table.callbacks;

            callbacks.remove.response.success = function (response) {
                getTables.Modal.close();
                getTables.Table.refresh();
            };

            return getTables.send(getTables.temp, getTables.Table.callbacks.remove, getTables.Callbacks.Table.remove);
        },
        long_process: function (button_data, table_data, trs_data) {
            getTables.Message.close();

            getTables.sendData.data = {
                gts_action: 'getModal/fetchModalProgress',
                hash: table_data.hash,
            };

            getTables.$doc
                .on('click', '.gts_progress .progress-stop', function (e) {
                    e.preventDefault();
                    $(this).data("stop",1);
                    $('.gts_progress .progress-info').hide();
                    $('.gts_progress .progress').hide();
                    $('.gts_progress .progress-log').hide();
                    $('.gts_progress .progress-stop-message').show();
                });

            var callbacks = getTables.Modal.callbacks;

            callbacks.load.response.success = function (response) {
                getTables.Modal.show(response.data.html);
                getTables.$doc.on('hidden.bs.modal', function (event) {
                    $('.gts_progress').remove();
                });
                getTables.sendData.data = {
                    gts_action: button_data.action,
                    hash: table_data.hash,
                    table_name: table_data.name,
                    table_data: table_data,
                    button_data: button_data,
                    trs_data: trs_data,
                    offset: 0
                };
                var callbacks2 = getTables.Table.callbacks;
    
                callbacks2.long_process.response.success = function (response) {
                    if($('.gts_progress .progress-stop').data("stop") == 1){
                        getTables.Modal.close();
                    }
                    if(response.data.completed){
                        getTables.Modal.close();
                        if(response.data.modal){
                            getTables.Modal.show(response.data.modal);
                        }else{
                            getTables.Message.success(response.data.message);
                        }
                    }else{
                        if(typeof $('.gts_progress')[0] !== "undefined" && $('.gts_progress .progress-stop').data("stop") == 0){
                            $('.gts_progress .progress-bar').css("width", response.data.procent+"%").attr('aria-valuenow',response.data.procent);
                            $('.gts_progress .progress-procent').text(response.data.procent +'%');
                            $('.gts_progress .progress-message').text(response.data.message);
                            if(response.data.log) $('.gts_progress .progress-log').html(response.data.log);
                            getTables.progress_offset = response.data.offset;
                            getTables.sendData.data.offset = response.data.offset;
                            getTables.send(getTables.sendData.data, getTables.Table.callbacks.long_process, getTables.Callbacks.Table.long_process);
                        }
                    }
                };
                callbacks2.long_process.ajax.fail = function (response) {
                    if(typeof $('.gts_progress')[0] !== "undefined" && $('.gts_progress .progress-stop').data("stop") == 0){
                        $('.gts_progress .progress-message').text(response.status+" "+response.statusText);
                        //console.info(response);
                        getTables.sendData.data.offset = getTables.progress_offset;
                        getTables.send(getTables.sendData.data, getTables.Table.callbacks.long_process, getTables.Callbacks.Table.long_process);
                    }
                };
                return getTables.send(getTables.sendData.data, getTables.Table.callbacks.long_process, getTables.Callbacks.Table.long_process);
            };

            getTables.send(getTables.sendData.data, getTables.Modal.callbacks.load, getTables.Callbacks.Modal.load);

            return;
        },
        
        
        update: function (button_data, table_data, tr_data) {
            getTables.Message.close();

            var callbacks = getTables.Table.callbacks;

            callbacks.update.response.success = function (response) {
                getTables.Modal.close();
                getTables.Table.refresh();
            };
            callbacks.update.response.error = function (response) {
                if(response.data.validates_error_fields){
                    for(key in response.data.validates_error_fields){
                        $('.gts-form [name="'+key+'"').addClass('error');
                    }
                    
                }
            };
            return getTables.send(getTables.sendData.data, getTables.Table.callbacks.update, getTables.Callbacks.Table.update);
        },

        sets: function (button_data, table_data, trs_data) {
            getTables.Message.close();

            getTables.sendData.data = {
                gts_action: button_data.action,
                hash: table_data.hash,
                table_name: table_data.name,
                table_data: table_data,
                button_data: button_data,
                trs_data: trs_data
            };
            var callbacks = getTables.Table.callbacks;

            callbacks.sets.response.success = function (response) {
                //console.log('callbacks.update.response.success',getTables.sendData);
                //getTables.Modal.close();
                if(response.data.modal) getTables.Modal.show(response.data.modal);
                if(response.data.redirect) location = response.data.redirect;
                if(!(response.data.redirect || response.data.modal)) getTables.Table.refresh();
            };

            return getTables.send(getTables.sendData.data, getTables.Table.callbacks.sets, getTables.Callbacks.Table.sets);
        },
        sub: function (button_data, table_data, tr_data) {
            getTables.Message.close();
            if(getTables.sendData.$row.next().hasClass('get-sub-row')){
                getTables.sendData.$row.next().remove();
                return;
            }
            getTables.sendData.data = {
                gts_action: button_data.action,
                hash: table_data.hash,
                table_name: table_data.name,
                table_data: table_data,
                button_data: button_data,
                tr_data: tr_data
            };
            var callbacks = getTables.Table.callbacks;

            callbacks.sub_show.response.success = function (response) {
                $row = getTables.sendData.$row;

                colspan = $row.find('td').length - 1;
                $sub_row = $('<tr class="get-sub-row" style="display:none;">' +
                '<td class=""></td>' +
                '<td class="get-sub-content" colspan="'+colspan+'"></td>' +
                '</tr>');
                $row.after($sub_row);
                $sub_row.find('.get-sub-content').html(response.data.sub_content);
                $sub_row.show();
              
                // $row.find('.get-sub-show').hide();
                // $row.find('.get-sub-hide').show();

                getTables.setPlugins();
            };

            return getTables.send(getTables.sendData.data, getTables.Table.callbacks.sub_show, getTables.Callbacks.Table.sub_show);
        },
        sub_show: function (button_data, table_data, tr_data) {
            getTables.Message.close();

            getTables.sendData.data = {
                gts_action: button_data.action,
                hash: table_data.hash,
                table_name: table_data.name,
                table_data: table_data,
                button_data: button_data,
                tr_data: tr_data
            };
            var callbacks = getTables.Table.callbacks;

            callbacks.sub_show.response.success = function (response) {
                $row = getTables.sendData.$row;

                colspan = $row.find('td').length - 1;
                $sub_row = $('<tr class="get-sub-row" style="display:none;">' +
                '<td class=""></td>' +
                '<td class="get-sub-content" colspan="'+colspan+'"></td>' +
                '</tr>');
                $row.after($sub_row);
                $sub_row.find('.get-sub-content').html(response.data.sub_content);
                $sub_row.show();
              
                $row.find('.get-sub-show').hide();
                $row.find('.get-sub-hide').show();

                getTables.setPlugins();
                // $('.get-select-multiple').each(function () {
                //     $(this).multiselect();
                // });
            };

            return getTables.send(getTables.sendData.data, getTables.Table.callbacks.sub_show, getTables.Callbacks.Table.sub_show);
        },
        sub_hide: function (button_data, table_data, tr_data) {
            getTables.Message.close();

            $row = getTables.sendData.$row;

            $sub_row = $row.next('.get-sub-row').first();
            //$sub_row.find('.get-sub-content').html('');
            $sub_row.remove();

            $row.find('.get-sub-show').show();
            $row.find('.get-sub-hide').hide();

            return;
            
        },
        refresh: function () {
            getTables.Message.close();
            $table = getTables.sendData.$GtsApp;
            // $form = $table.children(getTables.form);

            // $form.find('.get-nav-page').val(1);
            // $form.trigger('submit');
            getTables.Message.close();
            hash = $table.data('hash');
            getTables.sendData.data = {
                gts_action: 'getTable/refresh',
                hash: hash,
                table_name:$table.data('name'),
            };
            if($table.data('sub_where_current') !== undefined){
                getTables.sendData.data['sub_where_current'] = $table.data('sub_where_current');
            }
            if($table.data('parent_current') !== undefined){
                getTables.sendData.data['parent_current'] = $table.data('parent_current');
            }
            filters = getTables.Table.getFilters($table);
            
            $.each(filters, function( key, value ) {
                //console.log( 'Свойство: ' +key + '; Значение: ' + value );
                getTables.sendData.data[key] = value;
            });
            getTables.sendData.data['page'] = $table.children(".get-table-paginator-container").find('[name="page"]').val();
            getTables.sendData.data['limit'] = $table.children(".get-table-paginator-container").find('[name="limit"]').val();
            var callbacks = getTables.Table.callbacks;

            callbacks.refresh.response.success = function (response) {
                $table = getTables.sendData.$GtsApp;
                //console.log('response',response);
                $table.find('.get-table-tbody').html(response.data.html);
                
                $table.find('.get-table-nav').html(response.data.nav);
                

                if (response.data.top) {
                    $table.find('.get-table-top').html(response.data.top);
                }

                getTables.setPlugins();
            };

            return getTables.send(getTables.sendData.data, getTables.Table.callbacks.refresh, getTables.Callbacks.Table.refresh);
        },
        getFilters: function ($table) {
            filters = {};
            $ths = $table.children( "table" ).children( "thead" ).children( "tr" ).children( "th" );
            $ths.find('.get-table-filter').each(function(){
                if($(this).prop("type") == 'checkbox'){
                    if($(this).prop("checked")) filters[this.name] = $(this).val();
                }else{
                    if(!filters[this.name]) filters[this.name] = $(this).val();
                }
            });
            $table.children(".get-table-filter-container").find('.get-table-filter').each(function(){
                if($(this).prop("type") == 'checkbox'){
                    if($(this).prop("checked")) filters[this.name] = $(this).val();
                }else{
                    filters[this.name] = $(this).val();
                }
            });
            
            //console.log(1);
            $ths.each(function(){
                $th = $(this);
                $select_all = $th.find('.filrt-checkbox-select-all');
                if(typeof($select_all) != "undefined" && $select_all.prop('checked') == false){
                    $th.find('.filrt-checkbox-input:checked').each(function(){
                        if(typeof(filters['filter_checkboxs']) == "undefined")
                        filters['filter_checkboxs'] = {}; 
                        if(typeof(filters['filter_checkboxs'][$th.data('field')]) == "undefined")
                            filters['filter_checkboxs'][$th.data('field')] = [];
                        filters['filter_checkboxs'][$th.data('field')].push($(this).val());
                    });
                }
                $sort = $th.find('.get-table-sort-sortdir.active');
                if($sort.length > 0){
                    if(typeof(filters['filter_sort']) == "undefined")
                        filters['filter_sort'] = {};
                    if(typeof(filters['filter_sort'][$th.data('field')]) == "undefined")
                        filters['filter_sort'][$th.data('field')] = {};
                    filters['filter_sort'][$th.data('field')]['sortdir'] = $sort.data('sortdir');
                    filters['filter_sort'][$th.data('field')]['rank'] = $th.find('.get-table-sort-rank').val();
                }
            });
            return filters;
        },

        check_filter: function ($th) {
            //выделение фильтра разобраться
            $th.find('.filtr-btn').removeClass('filter-active filter filter-sort-down filter-sort-up');
            filterclass = 'filter';
            $th.find('.get-table-filter').each(function(){
                if($(this).val() != "") filterclass = 'filter-active';
            });
            select_all = true;
            $th.find('.filrt-checkbox-input').each(function(){
                if($(this).prop('checked') == false){
                    filterclass = 'filter-active';
                    select_all = false;
                }
            });
            $th.find('.filrt-checkbox-select-all').prop('checked', select_all);
            //сортировка
            $sort = $th.find('.get-table-sort-sortdir.active');
            if($sort.length > 0){
                if($sort.data('sortdir') == 'ASC'){
                    filterclass = 'filter-sort-up';
                }else{
                    filterclass = 'filter-sort-down';
                }
            }
            $th.find('.filtr-btn').addClass(filterclass);
        },
        custom: function () {
            getTables.Message.close();
            var callbacks = getTables.Table.callbacks;

            callbacks.custom.response.success = function (response) {
                if(response.data.modal_close) getTables.Modal.close();
                if(response.data.replace) $(response.data.replace.selector).replaceWith(response.data.replace.html);
                getTables.Table.refresh();
            };

            return getTables.send(getTables.sendData.data, getTables.Table.callbacks.custom, getTables.Callbacks.Table.custom);
        },
    };

    $(document).ready(function ($) {
        getTables.initialize();

        var html = $('html');
        html.removeClass('no-js');
        if (!html.hasClass('js')) {
            html.addClass('js');
        }
    });
    window.getTables = getTables;
})(window, document, jQuery, getTablesConfig);

//autocomplect
(function (window, document, $, getTables, getTablesConfig) {

    getTables.Autocomplect = {
        callbacks: {
            load: getTablesConfig.callbacksObjectTemplate(),
            expand: getTablesConfig.callbacksObjectTemplate(),
        },
        
        initialize: function () {
            this.timeoutId = 0;
            
            getTables.$doc
                .on('click', 'body', function (e) {
                    $(this).find('.get-autocomplect-menu').hide();
                });
            getTables.$doc
                .on('click', '.get-autocomplect-menu .caret1,.get-autocomplect-menu .ac-tree-span', function (e) {
                    e.preventDefault();
                    e.stopPropagation();
                    $li = $(this).closest('.ac-tree-li');
                    $autocomplect = $(this).closest('.get-autocomplect');
                    if($(this).hasClass('ac-tree-span')){
                        if(!$(this).prev().hasClass('caret1')) return;
                        $this = $(this).prev();
                    }else{
                        $this = $(this);
                    }
                    if(!$this.hasClass('caret-down1')){
                        
                        $table = $this.closest('.get-table,.gts-getform');
                        hash = $table.data('hash');
                        getTables.sendData.$GtsApp = $li;
                        getTables.sendData.$autocomplect = $autocomplect;
                        if(typeof(hash) == "undefined" && $autocomplect.data('modal') == 1){
                            hash = $(this).closest('.gts-form').find('input[name="hash"]').val();
                        }
                        getTables.sendData.data = {
                            gts_action: 'getSelect/expand',
                            hash: hash,
                            select_name: $autocomplect.data('name'),
                            id: $li.data('id'),
                        };

                        var callbacks = getTables.Autocomplect.callbacks;
            
                        callbacks.expand.response.success = function (response) {
                            $li = getTables.sendData.$GtsApp;
                            $(response.data.html).replaceAll($li);
                            $li.find('.collapsed').removeClass('collapsed').addClass('expanded');
                            $li.find('.caret1').addClass('caret-down1');
                            getTables.sendData.$autocomplect.find('.get-autocomplect-menu').show();
                        };
            
                        return getTables.send(getTables.sendData.data, getTables.Autocomplect.callbacks.expand, getTables.Callbacks.Autocomplect.expand);
                    }else{
                        $this.closest('li').find('.expanded').removeClass('expanded').addClass('collapsed');
                        $this.removeClass('caret-down1');
                        $li.find('.ac-tree-ul').remove();
                    }
                    
                });
            //get-autocomplect-all
            getTables.$doc
                .on('click', '.get-autocomplect-all', function (e) {
                    e.preventDefault();
                    e.stopPropagation();
                    $autocomplect = $(this).closest('.get-autocomplect');
                    $table = $(this).closest('.get-table,.gts-getform');
                    
                    var search_on = false;
                    var search = [];
                    var search1;
                    if($autocomplect.data('search') != undefined){
                        search_on = true;
                        search_str = $autocomplect.data('search');
                        search1 = search_str.split(",");
                        
                    }
                    
                    hash = $table.data('hash');
                    if(typeof(hash) == "undefined" && $autocomplect.data('modal') == 1){
                        hash = $(this).closest('.gts-form').find('input[name="hash"]').val();
                        var parent_current0 = $(this).closest('.gts-form').find('input[name="parent_current"]').val();
                        if(parent_current0 != undefined){
                            var parent_current = JSON.parse(parent_current0);
                        }
                    }else{
                        var parent_current =  $table.data('parent_current');
                        //console.info("parent_current0",parent_current0);
                    }
                    
                    if(search_on){
                        search1.forEach((element) => {
                            var search2;
                            search2 = element.split(":");
                            if(search2[0] == "parent"){
                                if(parent_current.tr_data[search2[1]] != undefined){
                                    search.push({
                                        type:"parent",
                                        field:search2[1],
                                        value:parent_current.tr_data[search2[1]]
                                    });
                                }
                            }else if(search2[0] == "query"){
                                search.push({
                                    type:"query",
                                    field:search2[1],
                                    value:search2[2]
                                });
                            }
                        });
                    }
                    $menu = $autocomplect.find('.get-autocomplect-menu');
                    if($menu.is(':visible')){
                        $menu.hide();
                        return;
                    }
                    //getTables.sendData.$GtsApp = $table;
                    getTables.sendData.$autocomplect = $autocomplect;
                    getTables.sendData.data = {
                        gts_action: $autocomplect.data('action'),
                        hash: hash,
                        select_name: $autocomplect.data('name'),
                        query: '',
                        search: search,
                        treeon: 1,
                    };
                    var callbacks = getTables.Autocomplect.callbacks;
            
                    callbacks.load.response.success = function (response) {
                        $menu = getTables.sendData.$autocomplect.find('.get-autocomplect-menu');
                        $menu.html(response.data.html).show();
                    };
                    getTables.send(getTables.sendData.data, getTables.Autocomplect.callbacks.load, getTables.Callbacks.Autocomplect.load);
                });
            
            getTables.$doc
                .on('change keypress', '.get-autocomplect-id', function (e) {
                    
                    if(e.type == "keypress"){
                        if(e.which != 13){
                            return;
                        }
                    }
                    e.preventDefault();
                    $autocomplect = $(this).closest('.get-autocomplect');
                    $table = $(this).closest('.get-table,.gts-getform');
                    //table_data = $table.data();
                    //getTables.sendData.$GtsApp = $table;
                    hash = $table.data('hash');
                    if(typeof(hash) == "undefined" && $autocomplect.data('modal') == 1){
                        hash = $(this).closest('.gts-form').find('input[name="hash"]').val();
                        
                    } 
                    getTables.sendData.$autocomplect = $autocomplect;
                    getTables.sendData.data = {
                        gts_action: $autocomplect.data('action'),
                        hash: hash,
                        select_name: $autocomplect.data('name'),
                        query: '',
                        id: $(this).val(),
                    };
                    var callbacks = getTables.Autocomplect.callbacks;
            
                    callbacks.load.response.success = function (response) {
                        $autocomplect = getTables.sendData.$autocomplect;
                        
                        $autocomplect.find('.get-autocomplect-content').val(response.data.content);
						$autocomplect.find('.get-autocomplect-hidden-id').val($autocomplect.find('.get-autocomplect-id').val()).trigger('change');
                    };
                    getTables.send(getTables.sendData.data, getTables.Autocomplect.callbacks.load, getTables.Callbacks.Autocomplect.load);
                });
            getTables.$doc
                .on('keyup', '.get-autocomplect-content', function (e) {
                    e.preventDefault();
                    if(e.code == "ControlLeft" || e.code == "ControlRight") return;
                    if(e.ctrlKey) return;

                    $autocomplect = $(this).closest('.get-autocomplect');
                    $table = $(this).closest('.get-table,.gts-getform');
                    //table_data = $table.data();
                    hash = $table.data('hash');
                    var search_on = false;
                    var search = [];
                    var search1;
                    if($autocomplect.data('search') != undefined){
                        search_on = true;
                        search_str = $autocomplect.data('search');
                        search1 = search_str.split(",");
                        
                    }
                    
                    if(typeof(hash) == "undefined" && $autocomplect.data('modal') == 1){
                        hash = $(this).closest('.gts-form').find('input[name="hash"]').val();
                        //console.info("hash",hash);
                        var parent_current0 = $(this).closest('.gts-form').find('input[name="parent_current"]').val();
                        if(parent_current0 != undefined){
                            var parent_current = JSON.parse(parent_current0);
                        }
                    }else{
                        var parent_current =  $table.data('parent_current');
                        //console.info("parent_current0",parent_current0);
                    }
                    
                    if(search_on){
                        search1.forEach((element) => {
                            var search2;
                            search2 = element.split(":");
                            if(search2[0] == "parent"){
                                if(parent_current.tr_data[search2[1]] != undefined){
                                    search.push({
                                        type:"parent",
                                        field:search2[1],
                                        value:parent_current.tr_data[search2[1]]
                                    });
                                }
                            }else if(search2[0] == "query"){
                                search.push({
                                    type:"query",
                                    field:search2[1],
                                    value:search2[2]
                                });
                            }
                        });
                    } 
                    getTables.sendData.$autocomplect = $autocomplect;
                    if(e.shiftKey){
                        treeon = 1;
                    }else{
                        treeon = 0;
                    }
                    getTables.sendData.data = {
                        gts_action: $autocomplect.data('action'),
                        hash: hash,
                        select_name: $autocomplect.data('name'),
                        query: $(this).val(),
                        search: search,
                        treeon: treeon,
                    };
                    var callbacks = getTables.Autocomplect.callbacks;
            
                    callbacks.load.response.success = function (response) {
                        $menu = getTables.sendData.$autocomplect.find('.get-autocomplect-menu');
                        $menu.html(response.data.html).show();
                    };
                    getTables.send(getTables.sendData.data, getTables.Autocomplect.callbacks.load, getTables.Callbacks.Autocomplect.load);
                });
            getTables.$doc
                .on('change', '.get-autocomplect-content', function (e) {
                    //e.preventDefault();
                    $autocomplect = $(this).closest('.get-autocomplect');
                    if($(this).val() == ""){
                        $autocomplect.find('.get-autocomplect-id').val(0);
                        $autocomplect.find('.get-autocomplect-hidden-id').val(0).trigger('change');
                    }
                });
            getTables.$doc
                .on('click enter', '.get-autocomplect-menu li a', function (e) {
                    e.preventDefault();
                    $autocomplect = $(this).closest('.get-autocomplect');
                    $autocomplect.find('.get-autocomplect-menu').hide();
                    $autocomplect.find('.get-autocomplect-id').val($(this).data('id'));
                    
                    $autocomplect.find('.get-autocomplect-content').val($(this).text()).trigger('focus');
					$autocomplect.find('.get-autocomplect-hidden-id').val($(this).data('id')).trigger('change');
                    
                });
            getTables.$doc.on('keydown','.get-autocomplect-content',function (e) {
                if(e.code == 'ArrowDown'){
                    $autocomplect = $(this).closest('.get-autocomplect');
                    $menu = $autocomplect.find('.get-autocomplect-menu');
                    if($menu.is(':visible')){
                        $menu.find('a').first().trigger('focus');
                    }
                }
                return;
            });
            getTables.$doc.on('keydown','.get-autocomplect-menu a',function (e) {
                $a = $(this);
                $menu = $a.closest('.get-autocomplect-menu');
                index = $menu.find('a').index($a);
                if(e.code == 'ArrowDown'){
                    e.preventDefault();
                    e.stopPropagation();
                    $anext = $menu.find('a').eq(index + 1);
                    if($anext.length) $anext.trigger('focus');
                }
                if(e.code == 'ArrowUp'){
                    e.preventDefault();
                    e.stopPropagation();
                    if(index == 0){
                        $(this).closest('.get-autocomplect').find('.get-autocomplect-content').trigger('focus');
                        return;
                    }
                    $aprev = $menu.find('a').eq(index - 1);
                    if($aprev.length) $aprev.trigger('focus');
                }
                return;
            });
        },
    };
    $(document).ready(function ($) {
        getTables.Autocomplect.initialize();
    });
})(window, document, jQuery, getTables, getTablesConfig);

//обработка событий нажатия стрелок у input type="number"
(function () {
    
    
    //let $filtrBtn = $('.filtr-btn');
    //let $filrtWindow = $('.filrt-window');
//    клик по кнопке фильтра
    $(document).on('click','.filtr-btn', function(){ 
        //console.log($(this).parent().children('.filrt-window').hasClass('filrt-window__open'));
        
        if($(this).parent().children('.filrt-window').hasClass('filrt-window__open')){
             $(this).parent().children('.filrt-window').removeClass('filrt-window__open');
        }else{
             $(this).parent().children('.filrt-window').addClass('filrt-window__open');
        }
        
    });
//    клик вне фильтра
    $(document).mouseup(function (e){ // событие клика по веб-документу
        var filter = $(".filrt-window"); // тут указываем ID элемента
        var datepicker = $('.air-datepicker-global-container');
        if (!filter.is(e.target) // если клик был не по нашему блоку
            && filter.has(e.target).length === 0 // и не по его дочерним элементам 
            && datepicker.has(e.target).length === 0) { //и не по календарю
            filter.removeClass('filrt-window__open'); // скрываем его
        }
    });
    
})(); 

