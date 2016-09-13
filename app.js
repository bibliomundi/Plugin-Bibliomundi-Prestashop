(function($) {
	'use strict';
	
	$(function() {
		
		var form = $('#configuration_form');
		var dialogContent = $('#import-blocker #loading');
		
		form.on('submit', function() {
			
			dialogContent.html('<i class="icon-refresh icon-spin"></i>&nbsp;Importing...');
			
			form.block({
				message: $('#import-blocker'),
				css: {
					'border': 'none'
				}
			});
			
			$.ajax({
				url: form.attr('action'),
				method: 'post',
				data: form.serialize()
			}).done(pollingImportStatus).fail(function() {
				alert('Unexpected error occurs');
				form.unblock();
			});
			
			return false;
			
		});
		
		function pollingImportStatus() {
	
			$.ajax({
				url: '/modules/bibliomundi/bibliomundi-import.php?action=status',
				dataType: 'json',
				cache: false
			}).done(function(data) {
					switch(data.status) {
						case 'in progress':
							if (data.current) dialogContent.html('<i class="icon-refresh icon-spin"></i>&nbsp;' + data.current + '/' + data.total);
						
							window.setTimeout(function() {
								pollingImportStatus();
							}, 1000);
							break;
						case 'complete':
							if (data.current) dialogContent.text(data.current + '/' + data.total);
							dialogContent.append(data.content + '<br /><button type="button">Close</button>');
							dialogContent.find('button').click(function() {
								form.unblock();
							});
							break;
						case 'error':
							alert(data.content);
							form.unblock();
							break;
						default:
							break;
					}
					
				}).fail(function() {
					alert('Unexpected error occurs');
					form.unblock();
				});
			
		}
		
	});
	
}(jQuery));