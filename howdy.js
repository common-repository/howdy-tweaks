jQuery(document).ready( function($) {

	$('.ht_add_new').click( function(e) {
		row = $(this).parents('.ht_options_group').find('.ht_new_row').html();
		var regex = new RegExp( $('.ht_garbage').val(), "g");

		//replace with time-generated one
		var newDate = new Date;
		k = newDate.getTime();

		row = row.replace( regex, k );
		$(this).parents('.ht_options_group').find('.ht_table').append( '<tr>' + row + '</tr>');
		e.preventDefault();
		return false;
	});
	$('.ht_table tbody').sortable();
	
});