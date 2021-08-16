(function (window, document, $, getTables, getTablesConfig) {
    var settings = {
        //selectableTableClass : 'tcs',//класс добавляемый к таблицам
        selectedCellClass : 'gts-selected',//класс добавляемый к выделенным ячейкам таблицы
        selectionEnabled: 'gts-selection-enabled' //???
    };

    var systemSettings = {
        dataKey : 'gtsCellsSelector',
        eventNamespace: 'gtsCellsselector'
    };

    var isMouseDown = false;//нажата ли левая клавиша мыши
    
    var isSelectEnable = false;

    getTables.CellsSelection = {
        initialize: function () {
            //addEventListeners();
            document.addEventListener('keydown', function(event){
                if (event.repeat == false && event.shiftKey && event.ctrlKey && event.code == "KeyS" ) {
                    if(isSelectEnable){
                        deselectAll2();
                        removeEventListeners();
                        isSelectEnable = false;
                    }else{
                        addEventListeners();
                        isSelectEnable = true;
                    }
                }
            })
        },
        
    };
    function selectCells($table, data) {
        
        
        var sum,count,average;
        sum = 0;count = 0;average = 0;
        $(".gtsCellsSelectionSum").remove();
        getTableCells($table).each(function (key, cell) {
            var data1 = {};
            data1.$elemX = cell.offsetLeft;
            data1.$elemXwidth = cell.offsetLeft + cell.offsetWidth;
            data1.$elemY = cell.offsetTop;
            data1.$elemYheight = cell.offsetTop + cell.offsetHeight;
            
            if ((data1.$elemX >= data.$pointXmin) && (data1.$elemXwidth <= data.$pointXwidth)
                && (data1.$elemY >= data.$pointYmin)
                && (data1.$elemY <= data.$pointYheight) && (data1.$elemYheight <= data.$pointYheight)) {
                $(cell).addClass(settings.selectedCellClass);
                
            }
        });
        $table.find('.'+settings.selectedCellClass).each(function () {
            count++;
            if(isNumber($(this).data('value'))){
                sum += parseFloat($(this).data('value'));
                average = sum/count;
            }
        });
        $last = $table.find('.'+settings.selectedCellClass).last();
        $pointXmin = $last.offset().left;
        $pointYheight = $last.offset().top + $last.height();
        if(count > 1){
            $table.after('<div class="gtsCellsSelectionSum" style="position:absolute;top:'
            +$pointYheight+'px;left:'+$pointXmin+'px;">'+
            '<span>Среднее:<span></span>'+average+'</span>'+
            ' <span>Кол-во:<span></span>'+count+'</span>'+
            ' <span>Сумма:<span></span>'+sum+'</span></div>');
        }
        $table.trigger('selectionchange.'+ systemSettings.eventNamespace);
    }
    function isNumber(n) {
        return !isNaN(parseFloat(n)) && isFinite(n);
    }
    function getTableCells($table){ return $table.find('th, td'); }
    
    function removeEventListeners()
    {
        $(document).unbind('.'+systemSettings.eventNamespace);
    }
    
    function addEventListeners(){
        $(document)
            .on(getEventNameWithPluginNamespace('mouseover'),'.get-table td', onMouseOver)//.mouseover(onMouseOver)
            .on(getEventNameWithPluginNamespace('mousedown'),'.get-table td',onMouseDown)//.mousedown(onMouseDown)
            //.on(getEventNameWithPluginNamespace('dragstart'),onDragStart)
            .on(getEventNameWithPluginNamespace('mouseup'),'.get-table table',onMouseUp) //.mouseup(onMouseUp);
            //клик на документе вне таблицы
            //.on(getEventNameWithPluginNamespace('click'),onOutTableClick);
                //or $table.closest(":root"); - html
                //or $('html')
                //or $(document)

            function onMouseDown(event){
                //console.log('mousedown table');
                var pluginData = getPluginDataByEvent(event);

                
                //таблица
                var $table = pluginData.$table;
                //если клик правой кнопкой мыши - ничего не делаем
                if (isRightMouseButton(event)){
                    //deselectAll($table);
                    return true;
                }
                //получаем ячейку
                var $cell = pluginData.$cell;
                //self.$currCell = $cell;

                if($cell.length==0) return;//событие сработало не для ячейки (самой таблицы или других её элементов)

                //event.stopPropagation();//надо ли?
                //event.stopImmediatePropagation();//тем более это?

                $cell.addClass(settings.selectionEnabled);

                var data = pluginData.data;

                isMouseDown = true;
                data.selFrom = getCoordinates($cell);
                data.selFrom.$el = $cell;

                var selectedCellClass = settings.selectedCellClass;
                if (!event.ctrlKey) {
                    if ($cell.hasClass(selectedCellClass) && deselectAll($table) === 1) {
                        $cell.removeClass(selectedCellClass);
                    } else {
                        deselectAll($table);
                        $cell.addClass(selectedCellClass);
                    }
                }else{
                    if ($cell.hasClass(selectedCellClass)) {
                        $cell.removeClass(selectedCellClass);
                    } else {
                        $cell.addClass(selectedCellClass);
                    }
                }

                cell = $cell[0];
                data.$pointX1 = cell.offsetLeft;
                data.$pointX1width = cell.offsetLeft+cell.offsetWidth;
                data.$pointY1 = cell.offsetTop;
                data.$pointY1height = cell.offsetTop+cell.offsetHeight;
                //data.$pointYheight+'px;left:'+data.$pointXmin
                data.$pointYheight = data.$pointY1height;
                data.$pointXmin = data.$pointX1;
                data.$pointXwidth = data.$pointX1width;
                data.$pointYmin = data.$pointY1;

                data.isHighlighted = $cell.hasClass(selectedCellClass);
                $table.data(data);
                if (event.ctrlKey) {
                    selectCells($table,data);
                }
                return true;
            }
            function onMouseOver(event)
            {
                //console.log('mouseover table');
                var pluginData = getPluginDataByEvent(event);

                var $target = pluginData.$target;
                
                //таблица
                var $table = pluginData.$table;

                //ячейку
                var $cell = pluginData.$cell;

                if($cell.length==0) return;//событие сработало не для ячейки (самой таблицы или других её элементов)

                var data = pluginData.data;
                data.$currCell = $cell;

                //если клавиша мыши не нажата, значит не выделение ячеек
                if (!isMouseDown) return false;
                //todo: переделать на глобальный индикатор нажатия кнопки мыши

                //скрываем стандартное выделение в таблице
                var selectionEnableClass = settings.selectionEnabled;
                $table.find('.'+selectionEnableClass).removeClass(selectionEnableClass);

                data.selTo = getCoordinates($cell);

                setPointCoordinates(data,$cell);
                
                if (!event.ctrlKey) {
                    deselectAll($table);
                }
                selectCells($table,data);
                $table.data(data);
                return true;

                function setPointCoordinates(data, $cell) {
                    cell = $cell[0];
                    data.$pointX2 = cell.offsetLeft;
                    data.$pointX2width = cell.offsetLeft + cell.offsetWidth;

                    data.$pointY2 = cell.offsetTop;
                    data.$pointY2height = cell.offsetTop + cell.offsetHeight;

                    if (data.$pointX1 < data.$pointX2) {
                        data.$pointXmin = data.$pointX1;
                        data.$pointXmax = data.$pointX2;
                    } else {
                        data.$pointXmin = data.$pointX2;
                        data.$pointXmax = data.$pointX1;
                    }
                    if (data.$pointX1width > data.$pointX2width) {
                        data.$pointXwidth = data.$pointX1width;
                    } else {
                        data.$pointXwidth = data.$pointX2width;
                    }
                    if (data.$pointY1 < data.$pointY2) {
                        data.$pointYmin = data.$pointY1;
                        data.$pointYmax = data.$pointY2;
                    } else {
                        data.$pointYmin = data.$pointY2;
                        data.$pointYmax = data.$pointY1;
                    }
                    if (data.$pointY1height > data.$pointY2height) {
                        data.$pointYheight = data.$pointY1height;
                    } else {
                        data.$pointYheight = data.$pointY2height;
                    }
                }
            }
            function onMouseUp(event){
                //console.log('mouseup table');
                var pluginData = getPluginDataByEvent(event);
                var data = {};
                isMouseDown = false;
                data.selFrom = false;
                data.selTo = false;
                pluginData.$table.data(data);
            }

            //клик на документе вне таблицы
            function onOutTableClick(event){
                //console.log('click (out of table)');
                isMouseDown = false;
                if($(event.target).closest($table).length==0) deselectAll($table);
            }
    }
    function deselectAll2() {
        $(".gtsCellsSelectionSum").remove();
        var selectedCells = $(document).find('.'+settings.selectedCellClass);;//TODO:исправить на локальные изменения только
        var length = 0;

        /*selectedCells.each(function(i, cell) {
            length++;
            $(cell).removeClass(settings.selectedCellClass);
        });*/
        //or
        selectedCells.removeClass(settings.selectedCellClass);

        return length;
    }

    function deselectAll($table) {
        $(".gtsCellsSelectionSum").remove();
        var selectedCells = $table.find('.'+settings.selectedCellClass);;//TODO:исправить на локальные изменения только
        var length = 0;

        /*selectedCells.each(function(i, cell) {
            length++;
            $(cell).removeClass(settings.selectedCellClass);
        });*/
        //or
        selectedCells.removeClass(settings.selectedCellClass);

        return length;
    }
    function getSelectedCells($table) {
		return $table.find('.'+settings.selectedCellClass);
	}
    function getCoordinates($cell){
        return {
            x: parseInt($cell.attr('data_x')),//todo: заменить на DOM-свойства вроде colNumber
            y: parseInt($cell.parent('tr').attr('data_y')),
            colspan: $cell.attr('colspan') ? parseInt($cell.attr('colspan')) : 0,
            rowspan: $cell.attr('rowspan') ? parseInt($cell.attr('rowspan')) : 0
        };
    }

    function getPluginDataByEvent(event){
        var $target = $(event.target);
        var $table = $target.closest('table');
        if(typeof($table.data(systemSettings.dataKey)) == "undefined"){
            $table.data(systemSettings.dataKey,getInitialData());
        }
        return {
            $target: $target,
            $table: $table,
            $cell: $target.closest('td'),
            data: $table.data(systemSettings.dataKey)
        }
    }
    /** Возварщает исходные данные таблицы */
    function getInitialData()
    {
        return {
            selFrom: false,
            selTo: false,
            isHighlighted: undefined,
        };
    }
    function isRightMouseButton(event) {
        var isRightMB;
        event = event || window.event;
        if ("which" in event)
            isRightMB = event.which == 3;
        else if ("button" in event)
            isRightMB = event.button == 2;
        return isRightMB;
    }
    function getEventNameWithPluginNamespace(event){return event + '.' + systemSettings.eventNamespace}

    $(document).ready(function ($) {
        getTables.CellsSelection.initialize();
    });
})(window, document, jQuery, getTables, getTablesConfig);