(function($){
	$( document ).ready(
		function(){
			$.ajax(
				{
					url: ajax_object.ajax_url,
					data: {
						action: 'sample_ajax_call',
						_wpnonce: ajax_object._wpnonce,
					},
					success: function (data) {
						// alert( data );
					},
				}
			)
		}
	)
})( jQuery );
