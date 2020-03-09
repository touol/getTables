(function (window, document, $, getTables, getTablesConfig) {
	getTables.Message = {
		initialize: function () {
			getTables.Message.close = function () {
			};
			getTables.Message.show = function (message) {
				if (message != '') {
					alert(message);
				}
			};

			if (typeof($.fn.jGrowl) != 'function') {
				$.getScript(getTablesConfig.jsUrl + 'lib/jquery.jgrowl.min.js', function () {
					getTables.Message.initialize();
				});
			}
			else {
				$.jGrowl.defaults.closerTemplate = '<div>[ ' + getTablesConfig.close_all_message + ' ]</div>';
				getTables.Message.close = function () {
					$.jGrowl('close');
				};
				getTables.Message.show = function (message, options) {
					if (message != '') {
						$.jGrowl(message, options);
					}
				}
			}
		},
		success: function (message) {
			getTables.Message.show(message, {
				theme: 'gettables-message-success',
				sticky: false
			});
		},
		error: function (message) {
			getTables.Message.show(message, {
				theme: 'gettables-message-error',
				sticky: false
			});
		},
		info: function (message) {
			getTables.Message.show(message, {
				theme: 'gettables-message-info',
				sticky: false
			});
		}
	};
	$(document).ready(function ($) {
		getTables.Message.initialize();
	});
})(window, document, jQuery, getTables, getTablesConfig);