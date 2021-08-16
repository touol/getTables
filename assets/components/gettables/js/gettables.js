(function (window, document, $, getTablesConfig) {
    var getTables = getTables || {};
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
        Table: {
            update: getTablesConfig.callbacksObjectTemplate(),
            refresh: getTablesConfig.callbacksObjectTemplate(),
            //filter: getTablesConfig.callbacksObjectTemplate(),
            sets: getTablesConfig.callbacksObjectTemplate(),
            sub_show: getTablesConfig.callbacksObjectTemplate(),
            sub_hide: getTablesConfig.callbacksObjectTemplate(),
            remove: getTablesConfig.callbacksObjectTemplate(),
            autosave: getTablesConfig.callbacksObjectTemplate(),
            custom: getTablesConfig.callbacksObjectTemplate(),
            filter_checkbox_load: getTablesConfig.callbacksObjectTemplate(),
        },
        Autocomplect: {
            load: getTablesConfig.callbacksObjectTemplate(),
        },
        Sortable: {
            sort: getTablesConfig.callbacksObjectTemplate(),
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
    getTables.initialize = function () {
        getTables.setup();
        // Indicator of active ajax request

        $('.get-date').each(function () {
            $(this).datepicker();
        });
        $('.get-select-multiple').each(function () {
            $(this).multiselect();
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

        getTables.Modal.initialize();
        getTables.Table.initialize();
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
            // case 'getTable/filter':
            //     getTables.Table.filter();
            //     break;
            default:
                //console.log('self',self);
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
                        getTables.sendData.$GtsApp.append(response.log);
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
                $('.gts_modal').remove();
            });
            getTables.$doc.on('shown.bs.modal', function (event) {
                $('.get-date').each(function () {
                    $(this).datepicker();
                });
                $('.get-select-multiple').each(function () {
                    $(this).multiselect();
                });
            });

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

                //$('body').append(response.data.html);
                $(response.data.html).modal('show');

            };

            return getTables.send(getTables.sendData.data, getTables.Modal.callbacks.load, getTables.Callbacks.Modal.load);
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
            sub_hide: getTablesConfig.callbacksObjectTemplate(),
            remove: getTablesConfig.callbacksObjectTemplate(),
            autosave: getTablesConfig.callbacksObjectTemplate(),
            filter_checkbox_load: getTablesConfig.callbacksObjectTemplate(),
        },
        setup: function () {

        },
        initialize: function () {
            //checkbox all
            getTables.$doc
                .on('change', '.get-table-check-all', function (e) {
                    $(this).closest('table').children('tbody').children('.get-table-tr').find('.get-table-check-row').prop('checked', $(this).prop('checked'));
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
                    }else if (button_data.name == 'remove') {
                        trs_data = [tr_data];
                        getTables.Table.remove(button_data, table_data, trs_data);
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

                        filters['hash'] = table_data.hash;
                        filters['table_name'] = table_data.name;
                        query = $.param(filters);
                        window.open(getTablesConfig.actionUrl + '?' + query + '&gts_action=getTable/export_excel&ctx=' + getTablesConfig.ctx, '_blank');
                        return;
                    }
                    $trs_check = $table.children('table').children('tbody').children('.get-table-tr').find('.get-table-check-row:checked');
                    //console.info('$trs',$('table').children('tbody').children('.get-table-tr'));

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
                    $table = $(this).closest('.get-table');

                    button_data = $(this).data();
                    table_data = $table.data();
                    $row = $(this).closest('.get-table-tr');
                    tr_data = $row.data();

                    getTables.sendData.$GtsApp = $table;
                    getTables.sendData.$row = $row;

                    //console.info('button.get-table-row');
                    field = $(this).data('field');
                    value = $(this).val();
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
                            value: value
                        },
                        tr_data: tr_data
                    };
                    getTables.Table.autosave(field, value, table_data, tr_data);
                });
            getTables.$doc
                .on('change', '.get-table-filter', function (e) {
                    e.preventDefault();
                    
                    $th = $(this).closest('th');
                    $th.find('.filtr-btn').removeClass('filter-active filter');
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
                        $(this).val("");
                    });
                    $th.find('.filrt-checkbox-select-all').prop('checked', true);
                    $th.find('.filrt-checkbox-input').prop('checked', true);
                    $th.find('.filtr-btn').removeClass('filter-active filter');
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
        },
        check_filter: function ($th) {
            //выделение фильтра разобраться
            $th.find('.filtr-btn').removeClass('filter-active filter');
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
            $th.find('.filtr-btn').addClass(filterclass);
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
                //console.log('callbacks.update.response.success',getTables.sendData);
                //getTables.Table.refresh();

            };

            return getTables.send(getTables.sendData.data, getTables.Table.callbacks.autosave, getTables.Callbacks.Table.autosave);
        },
        
        remove: function (button_data, table_data, trs_data) {
            getTables.Message.close();

            getTables.sendData.data = {
                gts_action: 'getModal/fetchModalRemove',
                hash: table_data.hash,
            };

            var callbacks = getTables.Modal.callbacks;

            callbacks.load.response.success = function (response) {
                $(response.data.html).modal('show');
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
                //console.log('callbacks.update.response.success',getTables.sendData);
                getTables.Table.refresh();
                $('.gts_modal').modal('hide');
            };

            return getTables.send(getTables.temp, getTables.Table.callbacks.remove, getTables.Callbacks.Table.remove);
        },
        
        update: function (button_data, table_data, tr_data) {
            getTables.Message.close();

            var callbacks = getTables.Table.callbacks;

            callbacks.update.response.success = function (response) {
                //console.log('callbacks.update.response.success',getTables.sendData);
                getTables.Table.refresh();
                $('.gts_modal').modal('hide');
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
                getTables.Table.refresh();
            };

            return getTables.send(getTables.sendData.data, getTables.Table.callbacks.sets, getTables.Callbacks.Table.sets);
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

                $('.get-date').each(function () {
                    $(this).datepicker();
                });
                $('.get-select-multiple').each(function () {
                    $(this).multiselect();
                });
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
                $table.find('tbody').html(response.data.html);
                if (response.data.nav_total > 0) {
                    $table.find('.get-table-nav').html(response.data.nav);
                }

                $('.get-date').each(function () {
                    $(this).datepicker();
                });
                $('.get-select-multiple').each(function () {
                    $(this).multiselect();
                });
            };

            return getTables.send(getTables.sendData.data, getTables.Table.callbacks.refresh, getTables.Callbacks.Table.refresh);
        },
        getFilters: function ($table) {
            filters = {};
            $ths = $table.children( "table" ).children( "thead" ).children( "tr" ).children( "th" );
            $ths.find('.get-table-filter').each(function(){
                filters[this.name] = $(this).val();
            });
            $table.children(".get-table-filter-container").find('.get-table-filter').each(function(){
                filters[this.name] = $(this).val();
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
            });
            return filters;
        },

        // filter: function () {
        //     getTables.Message.close();

        //     var callbacks = getTables.Table.callbacks;

        //     callbacks.filter.response.success = function (response) {
        //         $table = getTables.sendData.$GtsApp;
        //         //console.log('response',response);
        //         $table.find('tbody').html(response.data.html);
        //         if (response.data.nav_total > 0) {
        //             $table.find('.get-table-nav').html(response.data.nav);
        //         }

        //         $('.get-date').each(function () {
        //             $(this).datepicker();
        //         });
        //         $('.get-select-multiple').each(function () {
        //             $(this).multiselect();
        //         });
        //     };

        //     return getTables.send(getTables.sendData.data, getTables.Table.callbacks.filter, getTables.Callbacks.Table.filter);
        // },
        custom: function () {
            getTables.Message.close();
            var callbacks = getTables.Table.callbacks;

            callbacks.custom.response.success = function (response) {
                getTables.Table.refresh();
                $('.gts_modal').modal('hide');
            };

            return getTables.send(getTables.sendData.data, getTables.Table.callbacks.custom, getTables.Callbacks.Table.custom);
        },
    };

    $(document).ready(function ($) {
        /*if (typeof($.fn.datepicker) != 'function') {
            $.getScript(getTablesConfig.jsUrl + 'lib/jquery-ui-1.11.4.custom/jquery-ui.min.js', function () {
                $('<link/>', {
                    rel: 'stylesheet',
                    type: 'text/css',
                    href: getTablesConfig.jsUrl + 'lib/jquery-ui-1.11.4.custom/jquery-ui.min.css'
                 }).appendTo('head');
            });
            $.getScript(getTablesConfig.jsUrl + 'lib/jquery-ui-1.11.4.custom/datepicker-ru.js', function () {
            });
        }
        if (typeof($.fn.multiselect) != 'function') {
            $.getScript(getTablesConfig.jsUrl + 'lib/bootstrap-multiselect/js/bootstrap-multiselect.js', function () {
                $('<link/>', {
                    rel: 'stylesheet',
                    type: 'text/css',
                    href: getTablesConfig.jsUrl + 'lib/bootstrap-multiselect/css/bootstrap-multiselect.css'
                 }).appendTo('head');
            });
        }*/
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
        },
        
        initialize: function () {
            getTables.$doc
                .on('click', 'body', function (e) {
                    $(this).find('.get-autocomplect-menu').hide();
                });
            //get-autocomplect-all
            getTables.$doc
                .on('click', '.get-autocomplect-all', function (e) {
                    e.preventDefault();
                    $autocomplect = $(this).closest('.get-autocomplect');
                    $table = $(this).closest('.get-table');
                    var search_on = false;
                    var search = [];
                    var search1;
                    if($autocomplect.data('search') != undefined){
                        search_on = true;
                        search_str = $autocomplect.data('search');
                        search1 = search_str.split(",");
                        
                    }
                    
                    hash = $table.data('hash');
                    if($autocomplect.data('modal') == 1){
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
                                        field:search2[1],
                                        value:parent_current.tr_data[search2[1]]
                                    });
                                }
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
                    };
                    var callbacks = getTables.Autocomplect.callbacks;
            
                    callbacks.load.response.success = function (response) {
                        $menu = getTables.sendData.$autocomplect.find('.get-autocomplect-menu');
                        $menu.html(response.data.html).show();
                    };
                    getTables.send(getTables.sendData.data, getTables.Autocomplect.callbacks.load, getTables.Callbacks.Autocomplect.load);
                });
            getTables.$doc
                .on('click', '.get-autocomplect-menu li a', function (e) {
                    e.preventDefault();
                    $autocomplect = $(this).closest('.get-autocomplect');
                    $autocomplect.find('.get-autocomplect-id').val($(this).data('id'));
                    $autocomplect.find('.get-autocomplect-hidden-id').val($(this).data('id')).trigger('change');
                    $autocomplect.find('.get-autocomplect-content').val($(this).text());
                    $autocomplect.find('.get-autocomplect-menu').hide();
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
                    $table = $(this).closest('.get-table');
                    //table_data = $table.data();
                    //getTables.sendData.$GtsApp = $table;
                    hash = $table.data('hash');
                    if($autocomplect.data('modal') == 1){
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
                        $autocomplect.find('.get-autocomplect-hidden-id').val($autocomplect.find('.get-autocomplect-id').val()).trigger('change');
                        $autocomplect.find('.get-autocomplect-content').val(response.data.content);
                    };
                    getTables.send(getTables.sendData.data, getTables.Autocomplect.callbacks.load, getTables.Callbacks.Autocomplect.load);
                });
            getTables.$doc
                .on('keyup', '.get-autocomplect-content', function (e) {
                    e.preventDefault();
                    $autocomplect = $(this).closest('.get-autocomplect');
                    $table = $(this).closest('.get-table');
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
                    
                    hash = $table.data('hash');
                    if($autocomplect.data('modal') == 1){
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
                                        field:search2[1],
                                        value:parent_current.tr_data[search2[1]]
                                    });
                                }
                            }
                        });
                    } 
                    getTables.sendData.$autocomplect = $autocomplect;
                    getTables.sendData.data = {
                        gts_action: $autocomplect.data('action'),
                        hash: hash,
                        select_name: $autocomplect.data('name'),
                        query: $(this).val(),
                        search: search,
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
                    e.preventDefault();
                    $autocomplect = $(this).closest('.get-autocomplect');
                    if($(this).val() == ""){
                        $autocomplect.find('.get-autocomplect-id').val(0);
                        $autocomplect.find('.get-autocomplect-hidden-id').val(0).trigger('change');
                    }
                });
        },
    };
    $(document).ready(function ($) {
        getTables.Autocomplect.initialize();
    });
})(window, document, jQuery, getTables, getTablesConfig);

//обработка событий нажатия стрелок у input type="number"
(function () {
    let $arrBtnTop = $('.arr-btn__top');
    let $arrBtnBottom = $('.arr-btn__bottom');

    $arrBtnTop.on('click', function () {
        let $currentInput = $(this).parent().children('.get-autocomplect-id');
        $currentInput.val(+$currentInput.val() + 1);;
    });

    $arrBtnBottom.on('click', function () {
        let $currentInput = $(this).parent().children('.get-autocomplect-id');
        if($currentInput.val() > 1) {
            $currentInput.val(+$currentInput.val() - 1);
        }
    });
    
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
       
        if (!filter.is(e.target) // если клик был не по нашему блоку
            && filter.has(e.target).length === 0) { // и не по его дочерним элементам и не по календарю
            filter.removeClass('filrt-window__open'); // скрываем его
        }
    });
    
//    код - щуп. Удалить на боевой версии 
    //  let calendar = $('.ui-state-default');
    
    // $(document).on('click', function(e){
    //     console.log(e.target);
    // });
    // calendar.on('click', function(e){
        
    //     alert("Aringilda boltushka");
    // });
//    код щуп - конец
})(); 

