/**
 * @license
 **/

(function($) {
	'use strict';
	
	$(function() {
		var form = $('#configuration_form');
		form.append("<div id='import-blocker' style='display: none'><div id='loading'></div></div>");
		var dialogContent = $('#import-blocker #loading');		
		
		form.on('submit', function() {

			if(
				!form.find('input[name="client_id"]').val() || 
				!form.find('input[name="client_secret"]').val() || 
				!form.find('input[name="operation"]').val() ||
				!form.find('input[name="add_ebooks_cat"]').val() ||
				!form.find('input[name="environment"]').val()
			) {
				return true;
			}
			
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
				dataType: 'json',
				data: form.serialize()
			}).done(validProccess).fail(function() {
				alert('Unexpected error occurs');
				form.unblock();
			});
			
			return false;
		});

		$(".remove-all-ebooks").on("click", function(){
			var confirm = window.confirm("Are you sure ?");
			if ( confirm ) {
                dialogContent.html('<i class="icon-refresh icon-spin"></i>&nbsp;Removing...');

                form.block({
                    message: $('#import-blocker'),
                    css: {
                        'border': 'none'
                    }
                });
                $.ajax({
                    url: form.attr('action'),
                    method: 'post',
                    dataType: 'json',
                    data: form.serialize()
                }).done(removeProducts).fail(function () {
                    alert('Unexpected error occurs');
                    form.unblock();
                });
            }
		});

        function removeProducts() {
            $.ajax({
                url: '/modules/bibliomundi/bibliomundi-remove.php',
                dataType: 'json',
                cache: false
            }).success(function(res){
                if(res.error != ''){
                    alert(res.error);
                    form.unblock();
                }else{
                    dialogContent.html(res.msg);
                    window.setTimeout(function() {
                        form.unblock();
                    }, 5000);
                }
            });
        }

		function validProccess() {
			$.ajax({
				url: '/modules/bibliomundi/bibliomundi-import.php?action=valid',
				dataType: 'json',
				cache: false
			}).success(function(res){
				if(res.error != ''){
					alert(res.error);
					form.unblock();					
				}else{
					importProccess();
					window.setTimeout(function() {
						pollingImportStatus();
					}, 2000);
				}				
			});
		}

		function importProccess() {
			$.ajax({
				url: '/modules/bibliomundi/bibliomundi-import.php?action=proccess',
				cache: false
			});
		}		
		
		function pollingImportStatus() {				
			$.ajax({
				url: '/modules/bibliomundi/bibliomundi-status.php?action=status',
				cache: false
			}).done(function(data) {
				switch(data.status) {
					case 'in progress':
						if (data.current) dialogContent.html('<i class="icon-refresh icon-spin"></i>&nbsp;Importing...&nbsp;' + data.current + '/' + data.total);
						window.setTimeout(function() {
							pollingImportStatus();
						}, 2000);
						break;
					case 'complete':
						if (data.current) dialogContent.text(data.current + '/' + data.total);
						dialogContent.append('&nbsp;' + data.content);												
						window.setTimeout(function() {
							form.unblock();
						}, 5000);
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