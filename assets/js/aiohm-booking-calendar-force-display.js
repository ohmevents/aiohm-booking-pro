/* Calendar Force Display Functionality */
jQuery(document).ready(function($) {
	// Force show calendar after page load.
	setTimeout(function() {
		$(".aiohm-calendar-force-display").show().css({
			"display": "block",
			"visibility": "visible", 
			"opacity": "1"
		});
		
		// Also force show the table.
		$(".aiohm-calendar-force-display .aiohm-bookings-single-calendar-table").show().css({
			"display": "table",
			"visibility": "visible",
			"opacity": "1"
		});
		
	}, 100);
});