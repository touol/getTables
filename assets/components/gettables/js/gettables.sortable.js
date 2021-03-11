(function (window, document, $, getTables, getTablesConfig) {
    

    getTables.Sortable = {
        callbacks: {
            sort: getTablesConfig.callbacksObjectTemplate(),
        },
        initialize: function () {
            $(document).on('dragstart', function (e) {
                e.originalEvent.dataTransfer.setData('text/plain',$(e.target).data('id'));
            });
            $(document).on('drop', '.get-table tr', function (e) {
                $(this).removeClass('get-table-drag-over');
                var rdata = e.originalEvent.dataTransfer.getData("text/plain");
                $table = $(e.target).closest('.get-table');
                table_data = $table.data();
                $source = $table.find('tr[data-id="'+rdata+'"]');
                $target = $(e.target).closest('.get-table-tr');

                getTables.sendData.$table = $table;
                getTables.sendData.$source = $source;
                getTables.sendData.$target = $target;
                getTables.sendData.data = {
                    gts_action: 'getTable/sort',
                    hash: $table.data('hash'),
                    table_name: table_data.name,
                    source_id: rdata,
                    target_id: $target.data('id'),
                };
                var callbacks = getTables.Sortable.callbacks;

                callbacks.sort.response.success = function (response) {
                    $target = getTables.sendData.$target;
                    $source = getTables.sendData.$source;
                    $target.after($source);
                };
                getTables.send(getTables.sendData.data, getTables.Sortable.callbacks.sort, getTables.Callbacks.Sortable.sort);
                
                
                //alert(rdata);
            });
            $(document).on('dragover', '.get-table tr',function(e){
                e.preventDefault();
            });
            
            $(document).on('dragenter', '.get-table tr',function(e){
                e.preventDefault();
                $(this).addClass('get-table-drag-over');
            });
            $(document).on('dragleave', '.get-table tr',function(e){
                e.preventDefault();
                $(this).removeClass('get-table-drag-over');
            });
        },
        
    };
    

    $(document).ready(function ($) {
        getTables.Sortable.initialize();
    });
})(window, document, jQuery, getTables, getTablesConfig);