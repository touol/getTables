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
        Cart: {
            add: getTablesConfig.callbacksObjectTemplate(),
            remove: getTablesConfig.callbacksObjectTemplate(),
            change: getTablesConfig.callbacksObjectTemplate(),
            clean: getTablesConfig.callbacksObjectTemplate()
        },
        Order: {
            add: getTablesConfig.callbacksObjectTemplate(),
            getcost: getTablesConfig.callbacksObjectTemplate(),
            clean: getTablesConfig.callbacksObjectTemplate(),
            submit: getTablesConfig.callbacksObjectTemplate(),
            getrequired: getTablesConfig.callbacksObjectTemplate()
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
        }
        else {
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
        this.actionName = 'ms2_action';
        this.action = ':submit[name=' + this.actionName + ']';
        this.form = '.ms2_form';
        this.$doc = $(document);

        this.sendData = {
            $form: null,
            action: null,
            formData: null
        };

        this.timeout = 300;
    };
    getTables.initialize = function () {
        getTables.setup();
        // Indicator of active ajax request

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

                if (action) {
                    var formData = $form.serializeArray();
                    formData.push({
                        name: getTables.actionName,
                        value: action
                    });
                    getTables.sendData = {
                        $form: $form,
                        action: action,
                        formData: formData
                    };
                    getTables.controller();
                }
            });
        getTables.Cart.initialize();
        getTables.Message.initialize();
        getTables.Order.initialize();
        getTables.Gallery.initialize();
    };
    getTables.controller = function () {
        var self = this;
        switch (self.sendData.action) {
            case 'cart/add':
                getTables.Cart.add();
                break;
            case 'cart/remove':
                getTables.Cart.remove();
                break;
            case 'cart/change':
                getTables.Cart.change();
                break;
            case 'cart/clean':
                getTables.Cart.clean();
                break;
            case 'order/submit':
                getTables.Order.submit();
                break;
            case 'order/clean':
                getTables.Order.clean();
                break;
            default:
                return;
        }
    };
    getTables.send = function (data, callbacks, userCallbacks) {
        var runCallback = function (callback, bind) {
            if (typeof callback == 'function') {
                return callback.apply(bind, Array.prototype.slice.call(arguments, 2));
            }
            else if (typeof callback == 'object') {
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
        }
        else if ($.isPlainObject(data)) {
            data.ctx = getTablesConfig.ctx;
        }
        else if (typeof data == 'string') {
            data += '&ctx=' + getTablesConfig.ctx;
        }

        // set action url
        var formActionUrl = (getTables.sendData.$form)
            ? getTables.sendData.$form.attr('action')
            : false;
        var url = (formActionUrl)
            ? formActionUrl
            : (getTablesConfig.actionUrl)
                      ? getTablesConfig.actionUrl
                      : document.location.href;
        // set request method
        var formMethod = (getTables.sendData.$form)
            ? getTables.sendData.$form.attr('method')
            : false;
        var method = (formMethod)
            ? formMethod
            : 'post';

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
                }
                else {
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
            }).always(function () {
                runCallback(callbacks.ajax.always, getTables, xhr);
                runCallback(userCallbacks.ajax.always, getTables, xhr);
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
        }
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
