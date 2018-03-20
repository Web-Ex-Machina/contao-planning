/** Define a global config **/
var calendarDefaultConfig = {};
var availableSlotsInterval;

$(document).ready(function()
{
	/** Prepare the Calendar default config **/	
	calendarDefaultConfig =
	{
		 locale: 'fr'
		,timeFormat: 'hh:mm'
		
		,header:{left: 'title',	center: 'agendaDay,agendaWeek,month', right: 'prev,next today'}
		,editable: false
		,firstDay: 1
		,selectable: true
		,defaultView: 'agendaWeek'

		,columnHeader: true
		,columnHeaderFormat: 'dddd DD'

		,allDaySlot:false
		,minTime:"07:00:00"
		,maxTime:"22:00:00"

        ,eventSources:
        [
			{
				id: 1
				,url: document.URL
				,type: 'POST'
				,data: {
					TL_AJAX: true
					,module: $('meta[name="contao_module"][data-module="wem_display_planning"]').data("id")
					,action: 'findBookings'
				}
				,error: function(msg, error, errorFull) {
					toastr.error('Erreur : '+ error+'<br />'+errorFull);
				}
				,success: function(data) {
					//console.log(data);
				}
				,endParam: "stop"
				,className: "slot-booked"
			}
			,{
				id: 2
				,url: document.URL
				,type: 'POST'
				,data: function() {
					return {
						TL_AJAX: true
						,module: $('meta[name="contao_module"][data-module="wem_display_planning"]').data("id")
						,action: 'findAvailableSlots'
						,bookingType: $('#bookingType_select').val()
					}
				}
				,error: function(msg, error, errorFull) {
					toastr.error('Erreur : '+ error+'<br />'+errorFull);
				}
				,success: function(data) {
					try{
						if(data.status == 'gotofirstslotavailable'){
							if($('.fc-view .mask.gotofirstslotavailable').length == 0){
								var strHtml = '<div class="mask gotofirstslotavailable">1er rendez-vous disponible : <strong class="date"></strong><button data-goto="" class="btn calendar_goto">Fast travel</button></div>';
								$('.fc-view').append(strHtml)							
							}

							var objStart = moment(data.slot.start);
							$('.fc-view .mask.gotofirstslotavailable .date').text(objStart.format("DD/MM/YYYY à HH:mm"));
							$('.fc-view .mask.gotofirstslotavailable .calendar_goto').attr("data-goto", data.slot.start);
							$('.fc-view .mask.gotofirstslotavailable').hide().fadeIn();

							toastr.info(objStart.format("DD/MM/YYYY à HH:mm"));
						}else{
							$('.fc-view .mask.gotofirstslotavailable').fadeOut();
						}
					}catch(err){
						toastr.error('Erreur : '+err);
					};
				}
				,endParam: "stop"
				,className: "slot-available"
			}
		]

		,eventClick: function(calEvent, jsEvent, view){
			if(calEvent.source.className.indexOf('slot-available') > -1){
				lockBooking($('#bookingType_select').val(), calEvent.start._i).then(function(data){
					if($('.mod_wem_display_planning .modal.booking-form').length > 0){
						$('.mod_wem_display_planning .modal.booking-form').modal('dispose').remove();
					}

					$('.mod_wem_display_planning').append(data);
					var $modalDiv = $('.mod_wem_display_planning .modal.booking-form').first();

					// Bind events on modal
					$modalDiv.bind('hide.bs.modal', function(e){
						deleteBooking($modalDiv.attr('data-booking')).then(function(data){
							//toastr.success(data);
							calendar.fullCalendar('refetchEventSources', 1);
							calendar.fullCalendar('refetchEventSources', 2);
						}).catch(function(err){
							toastr.error(err);
						});
					});
					
					$modalDiv.find('.btn-ok').bind('click', function(e){
						confirmBooking(e, $modalDiv).then(function(data){
							toastr.success(data);
							// Refresh the calendars
							calendar.fullCalendar('refetchEventSources', 1);
							calendar.fullCalendar('refetchEventSources', 2);
							// And unbind the modal dismiss event who'll cancel the event
							$modalDiv.unbind('hide.bs.modal');
							// And dispose the modal
							$modalDiv.modal('hide');
						}).catch(function(err){
							toastr.error(err);
						});
					});

					$modalDiv.modal('show');
				}).catch(function(err){
				  toastr.error(err);
				});
			}
		}
	};

	// Every minute, refresh the slots
	availableSlotsInterval = setInterval(function(){
		calendar.fullCalendar('refetchEventSources', 1);					
		calendar.fullCalendar('refetchEventSources', 2);
	}, 60000);

	// Program a function for "calendar_goto"
	$("body").on("click", ".fc-view .calendar_goto", function(e){
		calendar.fullCalendar('gotoDate', $(this).data("goto"));
	});
});

/**
 * Send an AJAX request to lock a slot
 * @param  {Int}		bookingType
 * @param  {String}		start
 * @return {Promise}       
 */
function lockBooking(bookingType, start, updateBooking = 0){
	var objFields = {
		 TL_AJAX : true
		,module : $('meta[name="contao_module"][data-module="wem_display_planning"]').data("id")
		,action : 'draftBooking'
		,bookingType : bookingType
		,start : start
		,updateBooking : updateBooking
	};

	return new Promise(function(resolve,reject){
		$.post(document.URL, objFields, function(msg){
			try{
				var response = $.parseJSON(msg);
				if(response.status == "error")
					reject(response.message);
				else if(response.status == "success")
					resolve(response);
				else
					reject("Retour inconnu");
			}catch (err){
				resolve(msg);
			}
		});
	});
}

/**
 * Send an AJAX request to delete the booking
 * @param  {Integer} id [Booking ID]
 * @return {Promise}
 */
function deleteBooking(id){
	var objFields = {
		 TL_AJAX : true
		,module : $('meta[name="contao_module"][data-module="wem_display_planning"]').data("id")
		,action : 'deleteBooking'
		,booking : id
	};

	return new Promise(function(resolve,reject){
		$.post(document.URL, objFields, function(msg){
			var response = jQuery.parseJSON(msg);
			if(response.status == "success"){
				resolve(response.message);
			}else{
				reject(response.message);
			}
		});
	});
}

/**
 * Send an AJAX request to confirm the booking
 * @param  {Object} objEvent [Event]
 * @param  {Object} objModal [Modal]
 * @return {Promise}
 */
function confirmBooking(objEvent, objModal){
	var objFields = {
		 TL_AJAX : true
		,module : $('meta[name="contao_module"][data-module="wem_display_planning"]').data("id")
		,action : 'confirmBooking'
		,booking : objModal.attr('data-booking')
		,lastname: objModal.find('input[name="lastname"]').val()
		,firstname: objModal.find('input[name="firstname"]').val()
		,phone: objModal.find('input[name="phone"]').val()
		,email: objModal.find('input[name="email"]').val()
		,comments: objModal.find('textarea[name="comments"]').val()
	};

	return new Promise(function(resolve,reject){
		$.post(document.URL, objFields, function(msg){
			var response = jQuery.parseJSON(msg);
			if(response.status == "success"){
				resolve(response.message);
			}else{
				reject(response.message);
			}
		});
	});
}

/**
 * Send an AJAX request to confirm the booking
 * @param  {Object} objModal [Modal]
 * @return {Promise}
 */
function updateBooking(objModal){
	var objFields = {
		 TL_AJAX : true
		,module : $('meta[name="contao_module"][data-module="wem_display_planning"]').data("id")
		,action : 'updateBooking'
		,oldBooking : objModal.find('input[name="oldBooking"]').val()
		,newBooking : objModal.find('input[name="newBooking"]').val()
		,bookingType: objModal.find('input[name="bookingType"]').val()
		,start: objModal.find('input[name="start"]').val()
		,lastname: objModal.find('input[name="lastname"]').val()
		,firstname: objModal.find('input[name="firstname"]').val()
		,phone: objModal.find('input[name="phone"]').val()
		,email: objModal.find('input[name="email"]').val()
		,comments: objModal.find('textarea[name="comments"]').val()
	};

	return new Promise(function(resolve,reject){
		$.post(document.URL, objFields, function(msg){
			var response = jQuery.parseJSON(msg);
			if(response.status == "success"){
				resolve(response.message);
			}else{
				reject(response.message);
			}
		});
	});
}

/**
 * Send an AJAX request to cancel the booking
 * @param  {Integer} id [Booking ID]
 * @return {Promise}
 */
function cancelBooking(id){
	var objFields = {
		 TL_AJAX : true
		,module : $('meta[name="contao_module"][data-module="wem_display_planning"]').data("id")
		,action : 'cancelBooking'
		,booking : id
	};

	return new Promise(function(resolve,reject){
		$.post(document.URL, objFields, function(msg){
			var response = jQuery.parseJSON(msg);
			if(response.status == "success"){
				resolve(response.message);
			}else{
				reject(response.message);
			}
		});
	});
}