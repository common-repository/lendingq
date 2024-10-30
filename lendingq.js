jQuery( document ).ready( function() {
	jQuery('[id^="in-lendingq_location"][type="checkbox"]').click( function() {
		jQuery('[id^="in-lendingq_location"][type="checkbox"]').not(this).prop('checked', false);
	});
	jQuery('[id^="in-lendingq_item_type"][type="checkbox"]').click( function() {
		jQuery('[id^="in-lendingq_item_type"][type="checkbox"]').not(this).prop('checked', false);
	});
});