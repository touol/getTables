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
            filter: getTablesConfig.callbacksObjectTemplate(),
            sets: getTablesConfig.callbacksObjectTemplate(),
            sub_show: getTablesConfig.callbacksObjectTemplate(),
            sub_hide: getTablesConfig.callbacksObjectTemplate(),
            remove: getTablesConfig.callbacksObjectTemplate(),
            autosave: getTablesConfig.callbacksObjectTemplate(),
            custom: getTablesConfig.callbacksObjectTemplate(),
        },
        Autocomplect: {
            load: getTablesConfig.callbacksObjectTemplate(),
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
            case 'getTable/filter':
                getTables.Table.filter();
                break;
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
            /*sendData = {
                $form: null,
                
                data: {
                    action: button_data.modal,
                    hash: table_data.hash,
                    table_name: table_data.name,
                    button_data:button_data,
                    tr_data:tr_data
                }
            };*/
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
            filter: getTablesConfig.callbacksObjectTemplate(),
            custom: getTablesConfig.callbacksObjectTemplate(),
            sets: getTablesConfig.callbacksObjectTemplate(),
            sub_show: getTablesConfig.callbacksObjectTemplate(),
            sub_hide: getTablesConfig.callbacksObjectTemplate(),
            remove: getTablesConfig.callbacksObjectTemplate(),
            autosave: getTablesConfig.callbacksObjectTemplate(),
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
                    $table.closest('.get-sub-row').addClass('hidden');
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
                    } else if (button_data.name == 'subtable') {
                        if (button_data.js_action != "undefined")
                            getTables.Table[button_data.js_action](button_data, table_data, tr_data);
                    } else {
                        trs_data = [tr_data];
                        getTables.Table.sets(button_data, table_data, trs_data);
                    }
                });
            getTables.$doc
                .on('click', 'button.get-table-search', function (e) {
                    e.preventDefault();
                    $table = $(this).closest('.get-table');
                    $form = $(this).closest(getTables.form);
                    getTables.sendData.$GtsApp = $table;
                    $form.find('.get-nav-page').val(1);

                    $form.trigger('submit');
                });
            getTables.$doc
                .on('click', 'button.get-table-reset', function (e) {
                    e.preventDefault();
                    $table = $(this).closest('.get-table');
                    $form = $(this).closest(getTables.form);
                    getTables.sendData.$GtsApp = $table;
                    $form[0].reset();
                    $form.find('.get-autocomplect').find('input').val('');
                    $form.find('.get-nav-page').val(1);
                    //getTables.Table.refresh();
                    $form.trigger('submit');
                });

            getTables.$doc
                .on('click', 'button.get-table-first', function (e) {
                    e.preventDefault();
                    $table = $(this).closest('.get-table');
                    getTables.sendData.$GtsApp = $table;
                    $form = $table.children(getTables.form);
                    getTables.sendData.$form = $form;

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
                        $filter_form = $table.children('form');
                        query = $filter_form.serialize();
                        window.open(getTablesConfig.actionUrl + '?' + query + '&gts_action=getTable/export_excel', '_blank');
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
                    } else {
                        getTables.Table.sets(button_data, table_data, trs_data);
                    }
                });
            //paginator
            getTables.$doc
                .on('click', 'button.get-nav-first', function (e) {
                    e.preventDefault();
                    getTables.sendData.$GtsApp = $(this).closest('.get-table');
                    $form = $(this).closest(getTables.form);
                    getTables.sendData.$form = $form;
                    page = $form.find('.get-nav-page').val();
                    if (page > 1) {
                        $form.find('.get-nav-page').val(1);
                        $form.trigger('submit');
                    }
                });
            getTables.$doc
                .on('click', 'button.get-nav-prev', function (e) {
                    e.preventDefault();
                    getTables.sendData.$GtsApp = $(this).closest('.get-table');
                    $form = $(this).closest(getTables.form);
                    getTables.sendData.$form = $form;
                    page = $form.find('.get-nav-page').val();
                    if (page > 1) {
                        $form.find('.get-nav-page').val(page - 1);
                        $form.trigger('submit');
                    }
                });
            getTables.$doc
                .on('click', 'button.get-nav-next', function (e) {
                    e.preventDefault();
                    getTables.sendData.$GtsApp = $(this).closest('.get-table');
                    $form = $(this).closest(getTables.form);
                    getTables.sendData.$form = $form;
                    page = $form.find('.get-nav-page').val();
                    page_max = $form.find('.get-nav-page').prop('max');
                    if (page < page_max) {
                        $form.find('.get-nav-page').val(+page + 1);
                        $form.trigger('submit');
                    }
                });
            getTables.$doc
                .on('click', 'button.get-nav-last', function (e) {
                    e.preventDefault();
                    getTables.sendData.$GtsApp = $(this).closest('.get-table');
                    $form = $(this).closest(getTables.form);
                    getTables.sendData.$form = $form;
                    page = $form.find('.get-nav-page').val();
                    page_max = $form.find('.get-nav-page').prop('max');
                    if (page < page_max) {
                        $form.find('.get-nav-page').val(page_max);
                        $form.trigger('submit');
                    }
                });
            getTables.$doc
                .on('click', 'button.get-nav-refresh', function (e) {
                    e.preventDefault();
                    getTables.sendData.$GtsApp = $(this).closest('.get-table');
                    $form = $(this).closest(getTables.form);
                    getTables.sendData.$form = $form;
                    $form.trigger('submit');
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
        remove: function (button_data, table_data, tr_data) {
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

            callbacks.remove.response.success = function (response) {
                //console.log('callbacks.update.response.success',getTables.sendData);
                getTables.Table.refresh();
            };

            return getTables.send(getTables.sendData.data, getTables.Table.callbacks.remove, getTables.Callbacks.Table.remove);
        },
        update: function (button_data, table_data, tr_data) {
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

            callbacks.update.response.success = function (response) {
                //console.log('callbacks.update.response.success',getTables.sendData);
                getTables.Table.refresh();
                $('.gts_modal').modal('hide');
            };

            return getTables.send(getTables.sendData.data, getTables.Table.callbacks.update, getTables.Callbacks.Table.update);
        },

        sets: function (button_data, table_data, trs_data) {
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

                $sub_row = $row.next('.get-sub-row').first();
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
            $row = getTables.sendData.$row;

            $sub_row = $row.next('.get-sub-row').first();
            $sub_row.find('.get-sub-content').html('');
            $sub_row.hide();

            $row.find('.get-sub-show').show();
            $row.find('.get-sub-hide').hide();

            return;
            /*var callbacks = getTables.Table.callbacks;
            
            callbacks.sub_hide.response.success = function (response) {
                $row = getTables.sendData.$row;

                $sub_row = $row.next('.get-sub-row').first();
                $sub_row.find('.get-sub-content').html('');
                $sub_row.addClass('hidden');
                
                $row.find('.get-sub-show').removeClass('hidden');
                $row.find('.get-sub-hide').addClass('hidden');
            };
            
            return getTables.send(getTables.sendData.data, getTables.Table.callbacks.sub_hide, getTables.Callbacks.Table.sub_hide);
            */
        },
        refresh: function () {
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
            //console.log('refresh getTables.sendData',getTables.sendData);
            //getTables.sendData.action = 'getTable/refresh';
            $table = getTables.sendData.$GtsApp;
            $form = $table.children(getTables.form);

            //$form[0].reset();
            $form.find('.get-nav-page').val(1);
            //getTables.Table.refresh();
            //console.log('refresh $form',$form);
            $form.trigger('submit');

            /*getTables.sendData.$form = null;
            getTables.sendData.data = {
                    hash: table_data.hash,
                    table_name: table_data.name,
                    gts_action: getTables.sendData.action
                };
            //console.log('refresh',getTables.sendData);
            var callbacks = getTables.Table.callbacks;
            
            callbacks.refresh.response.success = function (response) {
                $table = getTables.sendData.$GtsApp;
                //console.log('response',response);
                $table.find('tbody').html(response.data.html);
                
            };
            
            return getTables.send(getTables.sendData.data, getTables.Table.callbacks.refresh, getTables.Callbacks.Table.refresh);*/
        },

        filter: function () {
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

            callbacks.filter.response.success = function (response) {
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

            return getTables.send(getTables.sendData.data, getTables.Table.callbacks.filter, getTables.Callbacks.Table.filter);
        },
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

                    hash = $table.data('hash');
                    if ($autocomplect.data('modal') == 1) {
                        hash = $(this).closest('.gts-form').find('input[name="hash"]').val();
                        //console.info("hash",hash);
                    }
                    $menu = $autocomplect.find('.get-autocomplect-menu');
                    if ($menu.is(':visible')) {
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

                    if (e.type == "keypress") {
                        if (e.which != 13) {
                            return;
                        }
                    }
                    e.preventDefault();
                    $autocomplect = $(this).closest('.get-autocomplect');
                    $table = $(this).closest('.get-table');
                    //table_data = $table.data();
                    //getTables.sendData.$GtsApp = $table;
                    hash = $table.data('hash');
                    if ($autocomplect.data('modal') == 1) {
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
                    if ($autocomplect.data('modal') == 1) {
                        hash = $(this).closest('.gts-form').find('input[name="hash"]').val();
                    }
                    getTables.sendData.$autocomplect = $autocomplect;
                    getTables.sendData.data = {
                        gts_action: $autocomplect.data('action'),
                        hash: hash,
                        select_name: $autocomplect.data('name'),
                        query: $(this).val(),
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
                    if ($(this).val() == "") {
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
    
    let $filtrBtn = $('.filtr-btn');
    let $filrtWindow = $('.filrt-window');
//    клик по кнопке фильтра
    $filtrBtn.on('click', function(){ 
        console.log($(this).parent().children('.filrt-window').hasClass('filrt-window__open'));
        
        if($(this).parent().children('.filrt-window').hasClass('filrt-window__open')){
             $(this).parent().children('.filrt-window').removeClass('filrt-window__open');
        }else{
             $(this).parent().children('.filrt-window').addClass('filrt-window__open');
        }
        
    })
//    клик вне фильтра
    $(document).mouseup(function (e){ // событие клика по веб-документу
		var filter = $(".filrt-window"); // тут указываем ID элемента
		if (!filter.is(e.target) // если клик был не по нашему блоку
		    && filter.has(e.target).length === 0) { // и не по его дочерним элементам
			filter.removeClass('filrt-window__open'); // скрываем его
		}
	});
})(); 

