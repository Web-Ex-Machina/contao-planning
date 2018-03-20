<?php

/**
 * Module Planning for Contao Open Source CMS
 *
 * Copyright (c) 2018 Web ex Machina
 *
 * @author Web ex Machina <https://www.webexmachina.fr>
 */

namespace WEM\Planning\Module;

use \RuntimeException as Exception;
use Contao\Date;
use Contao\Input;
use Contao\Validator;
use WEM\Planning\Model\Planning;
use WEM\Planning\Model\BookingType;
use WEM\Planning\Model\Slot;
use WEM\Planning\Model\Booking;

/**
 * Parent class for planning modules.
 *
 * @author Web ex Machina <https://www.webexmachina.fr>
 */
abstract class ModulePlanning extends \Module
{
	/**
	 * Find Booking Types for the current planning
	 * @return [Array] [Booking Types Array]
	 */
	protected function findBookingTypes()
	{
		try
		{
			if(!$objBookingTypes = BookingType::findItems(["pid"=>$this->wem_planning]))
				throw new Exception(sprintf($GLOBALS['TL_LANG']['WEM']['PLANNING']['ERR']['noBookingTypesFound'], $this->wem_planning));

			$arrData = array();

			while($objBookingTypes->next())
			{
				$arrData[$objBookingTypes->id] = $objBookingTypes->row();
			}

			return $arrData;
		}
		catch(Exception $e)
		{
			throw $e;
		}
	}

	/**
	 * Find Slots for the current planning
	 * @return [Array] [Slots Array]
	 */
	protected function findSlots()
	{
		try
		{
			if(!$objSlots = Slot::findItems(["pid"=>$this->wem_planning]))
				throw new Exception(sprintf($GLOBALS['TL_LANG']['WEM']['PLANNING']['ERR']['noSlotsFound'], $this->wem_planning));

			$arrData = array();

			while($objSlots->next())
			{
				$arrData[] = $objSlots->row();
			}

			return $arrData;
		}
		catch(Exception $e)
		{
			throw $e;
		}
	}

	/**
	 * Find Bookings for a given configuration
	 * @param  [Int]    $intBookingType [Booking Type]
	 * @param  [Int]    $intStart       [Start date]
	 * @param  [Int]    $intStop        [Stop date]
	 * @param  [String] $strStatus      [Booking Status]
	 * @return [Array]                  [Array of Bookings]
	 */
	protected function findBookings($intBookingType = 0, $intStart = 0, $intStop = 0, $strStatus = '')
	{
		try
		{
			$arrConfig = array("pid" => (int)$this->wem_planning);

			if($intStart == 0)
				$intStart = $this->start;
			if($intStop == 0)
				$intStop = $this->stop;
			
			$arrConfig["date_start"] = $intStart;
			$arrConfig["date_stop"] = $intStop;

			if($intBookingType > 0)
				$arrConfig["bookingType"] = $intBookingType;

			if($strStatus != '')
				$arrConfig["status"] = $strStatus;

			// Get all the bookings of the current week
			$objBookings = Booking::findItems($arrConfig);

			if(!$objBookings)
				return null;

			$arrBookings = array();
			while($objBookings->next())
			{
				$arrBooking = $objBookings->row();

				$arrBooking['start'] = date("Y-m-d\TH:i:s", $arrBooking['date']);
				$arrBooking['end'] = date("Y-m-d\TH:i:s", $arrBooking['dateEnd']);

				$arrBookings[] = $arrBooking;
			}

			return $arrBookings;
		}
		catch(Exception $e)
		{
			throw $e;
		}
	}

	/**
	 * Check if it is possible to book on a certain slot
	 * @param  [Integer] $intDuration [Duration of the booking]
	 * @param  [Integer] $intDate     [Date wanted - Will be now if not filled]
	 * @return [Boolean] 		      [True if available]
	 */
	protected function checkIfSlotIsAvailable($intDuration, $intDate = 0)
	{
		try
		{
			// Set the default time if not filled
			if($intDate == 0)
				$intDate = time();

			// Get the slots available for the planning
			$objDate = new \Date($intDate);
			$intStart = $objDate->tstamp;
			$intStop = $objDate->tstamp + $intDuration;

			$intCurrentDay = date('N', $intStart);

			// Retrieve planning slots if it's not already done
			if(!$this->slots)
				$this->slots = $this->findSlots();

			// Then, retrieve all the available slots for the date wanted
			$arrSlots = array();
			$arrSortSlots = array();
			foreach($this->slots as $arrSlot)
			{
				// Format the open/close timestamps of the slot tested
				$intOpen = mktime(date('H', $arrSlot['openTime']), 0, 0, date('m', $intStart), date('d', $intStart), date('Y', $intStart));
				$intClose = mktime(date('H', $arrSlot['closeTime']), 0, 0, date('m', $intStart), date('d', $intStart), date('Y', $intStart));

				// Check if the slot is available for the current dates
				switch($arrSlot['type'])
				{
					case "day":

						// Skip if it is the wrong day
						if($GLOBALS['TL_LANG']['DAYS'][$intCurrentDay] != $arrSlot['dayStart'])
							continue(2);

					break;

					case "range-days":

						// Format the range of "good" days
						$dayStart = $this->getNumberFromDay($arrSlot['dayStart']);
						$dayStop = $this->getNumberFromDay($arrSlot['dayStop']);

						// Skip if the current day isn't here
						if((int)$intCurrentDay < (int)$dayStart || (int)$intCurrentDay > (int)$dayStop)
							continue(2);

					break;

					case "date":
						$objTmpDate = new \Date($arrSlot['dateStart']);

						// Skip if the date is differente
						if($objDate->parse("d-m-Y") != $objTmpDate->parse("d-m-Y"))
							continue(2);
					break;

					case "range-dates":

						// Skip if the date is in range
						if($intStart < $arrSlot['dateStart'] || $intStart > $arrSlot['dateStop'])
							continue(2);

					break;

					default:
						continue(2);
				}

				// Skip if the open/close times doesn't match
				// Skip if the slot duration + current time checked overflows close time
				if($intStart < $intOpen || $intStop > $intClose)
					continue;

				$arrSlots[] = $arrSlot;
				$arrSortSlots[] = $arrSlot['sorting'];
			}

			// Check if there is a slot who allow booking for the current date
			if(empty($arrSlots))
				return false;

			// Order the slots by their sorting value, who determine the "priority" (Asc)
			array_multisort($arrSortSlots, SORT_DESC, $arrSlots);

			// And check if the first slot is a slot who forbid booking
			if(!$arrSlots[0]['canBook'])
				return false;

			// Unlock slot if we are in "update" mode, we'll just exclude the "exact" slot but the others will be accepted
			if(Input::get('update') && $this->objBooking)
			{
				if($this->objBooking->date == $intStart && $this->objBooking->dateEnd == $intStop)
					return false;
				else
					return true;
			}

			// Check if we already have bookings on this date
			if(Booking::countByDates($this->wem_planning, $intStart, $intStop) > 0)
				return false;
			
			return true;
		}
		catch(Exception $e)
		{
			throw $e;
		}
	}

	/**
	 * Find Available Slots for a Booking Type Given
	 * @param  [Int]   $intBookingType [Booking Type ID]
	 * @param  [Int]   $intStart       [Start time]
	 * @param  [Int]   $intStop 	   [Stop time]
	 * @return [Array]                 [Slots Available for booking]
	 */
	protected function findAvailableSlots($intBookingType, $intStart = 0, $intStop = 0)
	{
		try
		{
			/**
			 * Step 1 : Delimite the date ranges
			 */
			if($intStart == 0)
				$intStart = $this->start;
			if($intStop == 0)
				$intStop = $this->stop;

			// Adjust start setting
			// Rule : User cannot book in the next 24h
			if($intStart <= time())
				$intStart = strtotime("+1 day", $intStart);

			// Round the start by the start hour of the start date
			$objDate = new Date(date("Y-m-d-H", $intStart), "Y-m-d-H");
			$intStart = $objDate->tstamp;

			/**
			 * Step 2 : Determine the slot size
			 */
			$intDuration = 0;

			// Retrieve Booking Types if they doesn't exists
			if(!$this->bookingTypes)
				$this->bookingTypes = $this->findBookingTypes();

			// Then, retrieve the duration we need
			foreach($this->bookingTypes as $arrBooking)
			{
				if($arrBooking['id'] == $intBookingType)
				{
					$intDuration = $arrBooking['duration'];
					break;
				}
			}

			// Throw exception if we don't retrieve a duration
			if($intDuration == 0)
				throw new Exception($GLOBALS['TL_LANG']['WEM']['PLANNING']['ERR']['noBookingTypeSent']);

			/**
			 * Step 3 : Build the possible "events", by check if every available slot is allowed
			 */
			$intSlotDuration = $intDuration * 60 * 60;
			$intTmpTstamp = $intStart - 1800; // Substract 30 minutes to compensate the first added 30 minutes at the beginning of each loop
			$arrItems = array();

			// Retrieve Planning Slots
			if(!$this->slots)
				$this->slots = $this->findSlots();

			while($intTmpTstamp < $intStop)
			{
				// Add 30 minutes gap between each slot
				$intTmpTstamp += 1800;

				// Check if we can add the slot to the available ones
				if(!$this->checkIfSlotIsAvailable($intSlotDuration, $intTmpTstamp))
					continue;

				// If it's okay, add the slot.
				// Deprecated but keep this as a reminder
				/*$arrItems[] = [
					"title" => "",
					"start" => date("Y-m-d\TH:i:s", $intTmpTstamp),
					"end"   => date("Y-m-d\TH:i:s", $intTmpTstamp + $intSlotDuration)
				];*/

				// Alternative mode - Display slots on "30 minutes fake slots" to clarify in full calendar
				$arrItems[] = [
					"title" => date('H:i', $intTmpTstamp).' - '.date('H:i', $intTmpTstamp + $intSlotDuration),
					"start" => date("Y-m-d\TH:i:s", $intTmpTstamp),
					"end"   => date("Y-m-d\TH:i:s", $intTmpTstamp + 1799)
				];
			}


			return $arrItems;
		}
		catch(Exception $e)
		{
			throw $e;
		}
	}

	/**
	 * Return the day number from translation
	 * @param  [String] $strDay [Day, translated]
	 * @return [Int]            [Day number]
	 */
	protected function getNumberFromDay($strDay)
	{
		for($i = 0; $i < 7; $i++){
			if($strDay == $GLOBALS['TL_LANG']['DAYS'][$i]){
				if($i == 0)
					return 7;
				else
					return $i;
			}
		}
	}

	/**
	 * Handle Planning Ajax Requests
	 */
	protected function executeAjaxRequest()
	{
		$arrResponse = array();

		try
		{
			switch(Input::post('action'))
			{
				case 'findBookings':
					// Adjust start date - we don't want to retrieve bookings before the current time
					if($this->start < time())
						$this->start = time();

					$arrBookings = $this->findBookings();
					$arrItems = array();

					if(is_array($arrBookings))
					{
						foreach($arrBookings as $arrBooking)
						{
							// Parse a class for each item
							$strClass = 'booking';
							$strTitle = "Réservé";

							// Handle the exclusion of a specific booking, when we want to update 
							if(Input::post('currentBooking') && $arrBooking['id'] == Input::post('currentBooking'))
							{
								$strClass = 'booking-current';
								$strTitle = 'RDV actuel';
							}

							$arrItems[] = 
							[
								"title" => $strTitle,
								"start" => $arrBooking['start'],
								"end"   => $arrBooking['end'],
								"className" => $strClass,
							];
						}
					}

					echo json_encode($arrItems, true);
					die;

				break;

				case 'findAvailableSlots':
					// Throw exception if we don't have booking type
					if(!Input::post('bookingType'))
						throw new Exception($GLOBALS['TL_LANG']['WEM']['PLANNING']['ERR']['noBookingTypeSent']);

					// Adjust start date - we don't want to retrieve slots before the current time
					if($this->start < time())
						$this->start = time();

					// Retrieve available slots for the bookingType asked
					$arrItems = $this->findAvailableSlots(Input::post('bookingType'));

					// If there is no available slots, find the first one, starting with the current stop date
					if(!$arrItems || empty($arrItems))
					{
						$intStart = $this->stop;
						$intStop = $intStart + 24*60*60;
						$intEndLoop = $intStop + 30*24*60*60;

						// Build a loop to find the first available slot
						while($intStart < $intEndLoop)
						{
							$arrItems = $this->findAvailableSlots(Input::post('bookingType'), $intStart, $intStop);

							if(!empty($arrItems))
							{
								$arrResponse = array('status'=>'gotofirstslotavailable', 'slot'=>$arrItems[0]);
								break(2);
							}

							$intStart = $intStop;
							$intStop = $intStart + 24*60*60;
						}

						// Fallback message if we get out the loop without found any available slot
						throw new Exception("Pas de créneau disponible sur les 30 prochains jours");
					}

					echo json_encode($arrItems, true);
					die;

				break;

				case 'draftBooking':
					// Throw exception if we don't have booking type
					if(!Input::post('bookingType'))
						throw new Exception($GLOBALS['TL_LANG']['WEM']['PLANNING']['ERR']['noBookingTypeSent']);

					// Throw exception if we don't have start/stop dates
					if(!Input::post('start'))
						throw new Exception($GLOBALS['TL_LANG']['WEM']['PLANNING']['ERR']['noDatesSent']);

					// Check if the booking type is valid
					if(!array_key_exists(Input::post('bookingType'), $this->bookingTypes))
						throw new Exception($GLOBALS['TL_LANG']['WEM']['PLANNING']['ERR']['invalidBookingType']);

					// Check if the slot is still available
					$intDuration = $this->bookingTypes[Input::post('bookingType')]["duration"] * 60 * 60;
					if(!$this->checkIfSlotIsAvailable($intDuration, $this->start+1))
						throw new Exception($GLOBALS['TL_LANG']['WEM']['PLANNING']['ERR']['slotIsNotAvailableAnymore']);

					// Create/Update a booking if we pass an ID
					if(Input::post('updateBooking') > 0)
						$objBooking = Booking::findByPk(Input::post('updateBooking'));
						
					if(!$objBooking){
						$objBooking = new Booking();
						$objBooking->pid = $this->wem_planning;
						$objBooking->created_at = time();
						$objBooking->status = "drafted";
						$objBooking->token = md5(uniqid(mt_rand(), true));
					}

					// Create the booking
					$objBooking->tstamp = time();
					$objBooking->date = $this->start;
					$objBooking->dateEnd = $objBooking->date + $intDuration;
					$objBooking->bookingType = Input::post('bookingType');
					
					// Throw exception if the booking has not been saved
					if(!$objBooking->save())
						throw new Exception($GLOBALS['TL_LANG']['WEM']['PLANNING']['ERR']['bookingCreationError']);

					// In Update Mode, we want to lock a slot, but we don't want to return a Modal
					if(Input::get('update'))
					{
						$arrResponse['status'] = 'success';
						$arrResponse['booking'] = $objBooking->row();
					}
					else
					{
						// And parse the form to fill
						$objTemplate = new \FrontendTemplate("wem_calendar_booking_form");
						$objTemplate->start = $objBooking->date;
						$objTemplate->stop = $objBooking->dateEnd;
						$objTemplate->bookingType = $this->bookingTypes[$objBooking->bookingType];
						$objTemplate->module_id = $this->id;
						$objTemplate->booking_id = $objBooking->id;

						// Echo the template and die
						echo $objTemplate->parse();
						die;
					}

				break;

				case 'confirmBooking':
					// Throw exception if we don't have booking type
					if(!Input::post('booking'))
						throw new Exception($GLOBALS['TL_LANG']['WEM']['PLANNING']['ERR']['noBookingSent']);

					// Get the Booking model
					$objBooking = Booking::findByPk(Input::post('booking'));

					// Throw exception if booking doesn't exists
					if(!$objBooking)
						throw new Exception($GLOBALS['TL_LANG']['WEM']['PLANNING']['ERR']['bookingNotFound']);

					// Call the validator function
					$this->validateInputs();

					// Then update the form
					$objBooking->tstamp = time();
					$objBooking->status = 'pending'; 
					$objBooking->lastname = Input::post('lastname');
					$objBooking->firstname = Input::post('firstname');
					$objBooking->phone = Input::post('phone');
					$objBooking->email = Input::post('email');
					$objBooking->comments = Input::post('comments');

					// Throw exception if the booking has not been saved
					if(!$objBooking->save())
						throw new Exception($GLOBALS['TL_LANG']['WEM']['PLANNING']['ERR']['bookingCreationError']);

					// Fetch the notifications to send
					$this->sendNotifications('confirm_booking', $objBooking);

					// And prepare the response
					$arrResponse['status'] = 'success';
					$arrResponse['message'] = $GLOBALS['TL_LANG']['WEM']['PLANNING']['MSG']['bookingSuccess'];

				break;

				case 'updateBooking':
					// Throw exception if we don't have booking type
					if(!Input::post('oldBooking'))
						throw new Exception($GLOBALS['TL_LANG']['WEM']['PLANNING']['ERR']['noBookingSent']);

					// Get the Booking model
					$objOldBooking = Booking::findByPk(Input::post('oldBooking'));

					// Throw exception if booking doesn't exists
					if(!$objOldBooking)
						throw new Exception($GLOBALS['TL_LANG']['WEM']['PLANNING']['ERR']['bookingNotFound']);

					// Cancel the current booking
					$objOldBooking->tstamp = time();
					$objOldBooking->status = "canceled";

					// Throw exception if we don't have booking type
					if(!Input::post('bookingType'))
						throw new Exception($GLOBALS['TL_LANG']['WEM']['PLANNING']['ERR']['noBookingTypeSent']);

					// Throw exception if we don't have start/stop dates
					if(!Input::post('start'))
						throw new Exception($GLOBALS['TL_LANG']['WEM']['PLANNING']['ERR']['noDatesSent']);

					// Check if the booking type is valid
					if(!array_key_exists(Input::post('bookingType'), $this->bookingTypes))
						throw new Exception($GLOBALS['TL_LANG']['WEM']['PLANNING']['ERR']['invalidBookingType']);

					// Check if the slot is still available
					$intDuration = $this->bookingTypes[Input::post('bookingType')]["duration"] * 60 * 60;
					if(!$this->checkIfSlotIsAvailable($intDuration, $this->start+1))
						throw new Exception($GLOBALS['TL_LANG']['WEM']['PLANNING']['ERR']['slotIsNotAvailableAnymore']);

					// Create/Update a booking if we pass an ID
					if(Input::post('newBooking') > 0)
						$objNewBooking = Booking::findByPk(Input::post('newBooking'));
						
					if(!$objNewBooking){
						$objNewBooking = new Booking();
						$objNewBooking->pid = $this->wem_planning;
						$objNewBooking->created_at = time();
						$objNewBooking->token = md5(uniqid(mt_rand(), true));
					}

					$objNewBooking->tstamp = time();
					$objNewBooking->status = "pending";
					$objNewBooking->date = $this->start;
					$objNewBooking->dateEnd = $objNewBooking->date + $intDuration;
					$objNewBooking->bookingType = Input::post('bookingType');
					$objNewBooking->lastname = Input::post('lastname');
					$objNewBooking->firstname = Input::post('firstname');
					$objNewBooking->phone = Input::post('phone');
					$objNewBooking->email = Input::post('email');
					$objNewBooking->comments = Input::post('comments');
					$objNewBooking->isUpdate = 1;
					$objNewBooking->bookingSrc = $objOldBooking->id;

					// Throw exception if the old booking or the new booking has not been saved
					if(!$objOldBooking->save() || !$objNewBooking->save())
						throw new Exception($GLOBALS['TL_LANG']['WEM']['PLANNING']['ERR']['bookingUpdateError']);

					// Fetch the notifications to send
					$this->sendNotifications('update_booking', $objNewBooking);

					// And build the answer
					$arrResponse['status'] = 'success';
					$arrResponse['message'] = $GLOBALS['TL_LANG']['WEM']['PLANNING']['MSG']['bookingUpdateSuccess'];
				break;

				case 'cancelBooking':
					// Throw exception if we don't have booking type
					if(!Input::post('booking'))
						throw new Exception($GLOBALS['TL_LANG']['WEM']['PLANNING']['ERR']['noBookingSent']);

					// Get the Booking model
					$objBooking = Booking::findByPk(Input::post('booking'));

					// Throw exception if booking doesn't exists
					if(!$objBooking)
						throw new Exception($GLOBALS['TL_LANG']['WEM']['PLANNING']['ERR']['bookingNotFound']);

					$objBooking->tstamp = time();
					$objBooking->status = "canceled";

					// Throw exception if the booking has not been saved
					if(!$objBooking->save())
						throw new Exception($GLOBALS['TL_LANG']['WEM']['PLANNING']['ERR']['bookingCancelError']);

					// Fetch the notifications to send
					$this->sendNotifications('cancel_booking', $objBooking);

					// And build the answer
					$arrResponse['status'] = 'success';
					$arrResponse['message'] = $GLOBALS['TL_LANG']['WEM']['PLANNING']['MSG']['bookingCancelSuccess'];

				break;

				case 'deleteBooking':
					// Throw exception if we don't have booking type
					if(!Input::post('booking'))
						throw new Exception($GLOBALS['TL_LANG']['WEM']['PLANNING']['ERR']['noBookingSent']);

					// Get the Booking model
					$objBooking = Booking::findByPk(Input::post('booking'));

					// Throw exception if booking doesn't exists
					if(!$objBooking)
						throw new Exception($GLOBALS['TL_LANG']['WEM']['PLANNING']['ERR']['bookingNotFound']);

					// Delete the booking from DB
					if(!$objBooking->delete())
						throw new Exception($GLOBALS['TL_LANG']['WEM']['PLANNING']['ERR']['bookingCancelError']);

					// And build the answer
					$arrResponse['status'] = 'success';
					$arrResponse['message'] = $GLOBALS['TL_LANG']['WEM']['PLANNING']['MSG']['bookingCancelSuccess'];

				break;

				default:
					throw new Exception(sprintf($GLOBALS['TL_LANG']['WEM']['PLANNING']['ERR']['unknownAjaxAction']), Input::post('action'));
			}
		}
		catch(Exception $e)
		{
			$arrResponse['status'] = 'error';
			$arrResponse['message'] = $e->getMessage();
		}

		$arrResponse['rt'] = \RequestToken::get();

		echo json_encode($arrResponse, true);
		die;
	}

	/**
	 * Fetch, prepare and send Planning Notifications
	 * @param  [String] $strNotificationType [Notification type]
	 * @param  [Object] $objBooking          [Booking Model]
	 */
	protected function sendNotifications($strNotificationType, $objBooking)
	{
		try
		{
			// Fetch the notifications to send
			$objNotifications = \Database::getInstance()->execute("SELECT id,title FROM tl_nc_notification WHERE type='$strNotificationType' ORDER BY title");

			if($objNotifications->count() > 0)
			{
				$arrTokens = $this->prepareNotificationTokens($objBooking);

				while($objNotifications->next())
				{
					$objNotification = \NotificationCenter\Model\Notification::findByPk($objNotifications->id);
					$objNotification->send($arrTokens);
				}
			}
		}
		catch(Exception $e)
		{
			throw $e;
		}
	}

	/**
	 * Prepare notifications tokens
	 * @param  [Object] $objBooking [Booking Model]
	 * @return [Array]              [Tokens Array]
	 */
	protected function prepareNotificationTokens($objBooking)
	{
		try
		{
			// Prepare tokens
			$arrTokens = array();

			// Booking tokens
			foreach($objBooking->row() as $strKey => $varValue)
				$arrTokens["booking_".$strKey] = $varValue;

			// Parse date tokens
			$arrTokens["booking_day"] = date('d/m/Y', $objBooking->date);
			$arrTokens["booking_hourStart"] = date('H:i', $objBooking->date);
			$arrTokens["booking_hourEnd"] = date('H:i', $objBooking->dateEnd);

			// Booking Type tokens
			foreach($this->bookingTypes[$objBooking->bookingType] as $strKey => $varValue)
				$arrTokens["bookingType_".$strKey] = $varValue;

			// Build the update/cancel link
			$strRequest = \Environment::get('base').\Environment::get('request');
			$arrTokens['updateLink'] = $strRequest.'?update='.$objBooking->token;
			$arrTokens['cancelLink'] = $strRequest.'?cancel='.$objBooking->token;

			// Build the recipients
			$arrTokens['user_email'] = $objBooking->email;

			return $arrTokens;
		}
		catch(Exception $e)
		{
			throw $e;
		}
	}

	/**
	 * Validate Booking Input sent
	 */
	protected function validateInputs()
	{
		try
		{
			// Check and validate all fields
			$arrExceptions = array();

			if(!Input::post('lastname'))
				$arrExceptions[] = sprintf($GLOBALS['TL_LANG']['WEM']['PLANNING']['ERR']['requiredFieldMissing'], $GLOBALS['TL_LANG']['tl_wem_planning_booking']['lastname'][0]);
			if(!Input::post('firstname'))
				$arrExceptions[] = sprintf($GLOBALS['TL_LANG']['WEM']['PLANNING']['ERR']['requiredFieldMissing'], $GLOBALS['TL_LANG']['tl_wem_planning_booking']['firstname'][0]);
			if(!Input::post('phone'))
				$arrExceptions[] = sprintf($GLOBALS['TL_LANG']['WEM']['PLANNING']['ERR']['requiredFieldMissing'], $GLOBALS['TL_LANG']['tl_wem_planning_booking']['phone'][0]);
			if(!Input::post('email'))
				$arrExceptions[] = sprintf($GLOBALS['TL_LANG']['WEM']['PLANNING']['ERR']['requiredFieldMissing'], $GLOBALS['TL_LANG']['tl_wem_planning_booking']['email'][0]);

			// Break the function here if there is exceptions already.
			if(!empty($arrExceptions))
			{
				$arrResponse["errors"] = $arrExceptions;
				throw new Exception($GLOBALS['TL_LANG']['WEM']['PLANNING']['ERR']['bookingFormErrors']);
			}
		
			if(!Validator::isExtendedAlphanumeric(Input::post('lastname')))
				$arrExceptions[] = sprintf($GLOBALS['TL_LANG']['WEM']['PLANNING']['ERR']['invalidField'], $GLOBALS['TL_LANG']['tl_wem_planning_booking']['lastname'][0]);
			if(!Validator::isExtendedAlphanumeric(Input::post('firstname')))
				$arrExceptions[] = sprintf($GLOBALS['TL_LANG']['WEM']['PLANNING']['ERR']['invalidField'], $GLOBALS['TL_LANG']['tl_wem_planning_booking']['firstname'][0]);
			if(!Validator::isPhone(Input::post('phone')))
				$arrExceptions[] = sprintf($GLOBALS['TL_LANG']['WEM']['PLANNING']['ERR']['invalidField'], $GLOBALS['TL_LANG']['tl_wem_planning_booking']['phone'][0]);
			if(!Validator::isEmail(Input::post('email')))
				$arrExceptions[] = sprintf($GLOBALS['TL_LANG']['WEM']['PLANNING']['ERR']['invalidField'], $GLOBALS['TL_LANG']['tl_wem_planning_booking']['email'][0]);
			if(Input::post('comments') && !Validator::isExtendedAlphanumeric(Input::post('comments')))
				$arrExceptions[] = sprintf($GLOBALS['TL_LANG']['WEM']['PLANNING']['ERR']['invalidField'], $GLOBALS['TL_LANG']['tl_wem_planning_booking']['comments'][0]);

			// Break the function here if there is exceptions already.
			if(!empty($arrExceptions))
			{
				$arrResponse["errors"] = $arrExceptions;
				throw new Exception($GLOBALS['TL_LANG']['WEM']['PLANNING']['ERR']['bookingFormErrors']);
			}

			return true;
		}
		catch(Exception $e)
		{
			throw $e;
		}
	}
}