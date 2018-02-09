$(document).ready(function()
{
	$('.mod_wem_display_planning #bookingType_select').bind('change', function()
	{
		console.log("REFRESH THE PLANNING AVEC BOOKING ID :"+$(this).val());
	});
});