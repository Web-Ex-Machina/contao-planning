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

				// Skip if this slot is a "forbid" one
				if(!$arrSlot['canBook'])
					continue;

				$arrSlots[] = $arrSlot;
			}

			// Check if there is a slot who allow booking for the current date
			if(empty($arrSlots))
				return false;

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
			if($intStart <= time())
				$intStart = strtotime("+1 day", $intStart);

			// TODO : Adjust the "cannot reserve before one day" setting by a specific field in the planning
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

			while($intTmpTstamp < $this->stop)
			{
				// Add 30 minutes gap between each slot
				// TODO : Adjust the "gap between each slot" with a more arbitrary one, a specific field in the planning or maybe the min duration in booking types ?
				$intTmpTstamp += 1800;

				// Check if we can add the slot to the available ones
				if(!$this->checkIfSlotIsAvailable($intSlotDuration, $intTmpTstamp))
					continue;

				// If it's okay, add the slot.
				$arrItems[] = [
					"title" => "",
					"start" => date("Y-m-d\TH:i:s", $intTmpTstamp),
					"end"   => date("Y-m-d\TH:i:s", $intTmpTstamp + $intSlotDuration)
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
}