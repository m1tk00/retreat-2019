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
	);
	$(document).on('submit','form.cart',function(e){
		e.preventDefault();
		alert('ok');
		$form_data = $('form.cart').serialize();
		$.ajax({
			url: ajax_object.ajax_url,
			data: $form_data,
			method: 'POST',
			type: 'POST',
			success: function( data ){
				console.log( data );
			}
		});
	})
})( jQuery );
