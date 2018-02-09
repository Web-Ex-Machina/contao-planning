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
	 * Retrieve Planning configuration
	 * @param  [Array] $arrConfig [Configuration wanted]
	 * @return [Array]            [Planning data]
	 */
	protected function getPlanning($arrConfig = array())
	{
		try
		{
			// Get the planning Model
			$objPlanning = Planning::findByPk($this->wem_planning);

			// Throw an exception if there is nothing.
			if(!$objPlanning)
				throw new Exception(sprintf($GLOBALS['TL_LANG']['WEM']['PLANNING']['ERR']['noPlanningFound'], $this->wem_planning));

			// Cast as array
			$arrData = $objPlanning->row();

			// Get the Planning Booking Types
			if($arrConfig["getBookingTypes"])
			{
				if($objBookingTypes = BookingType::findItems(["pid"=>$arrData["id"]]))
				{
					$arrData["bookingTypes"] = array();
					while($objBookingTypes->next())
					{
						$arrData["bookingTypes"][] = $objBookingTypes->row();
					}
				}
			}

			// Get the Planning Slots
			if($arrConfig["getSlots"])
			{
				if($objSlots = Slot::findItems(["pid"=>$arrData["id"]]))
				{
					$arrData["slots"] = array();
					while($objSlots->next())
					{
						$arrData["slots"][] = $objSlots->row();
					}
				}
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
				$intStart = time();
			if($intStop == 0)
				$intStop = strtotime("+7 days");
			
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
				$arrBookings[] = $objBookings->row();
			}

			return $arrBookings;
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
			$objDate = new \Date();

			if($intStart == 0)
				$intStart = $objDate->tstamp;
			if($intStop == 0)
				$intStop = $objDate->getWeekEnd(1); // Next Monday, same time ("Next sunday at 23:59:59" will be more precise. Todo.)

			// Get the slots available for the booking
			$objSlots = Slot::findItems(["pid"=>$this->wem_planning]);

			if(!$objSlots)
				throw new Exception($GLOBALS['TL_LANG']['WEM']['PLANNING']['ERR']['noSlotsAvailable']);

			$arrSlots = array();
			while($objSlots->next())
			{
				// Check if the slot is available for the current dates
				switch($objSlots->type)
				{
					case "day":

						// Skip if it is the wrong day
						if($GLOBALS['TL_LANG']['DAYS'][date('N')] != $objSlots->dayStart)
							continue(2);

						// Format the timestamp of current day
						$intDay = mktime(0, 0, 0, date('m'), date('d'), date('Y'));

						// Skip if the open/close times doesn't match
						if($objDate->tstamp < $intDay + $objSlots->openTime || $objDate->tstamp > $intDay + $objSlots->closeTime)
							continue(2);
					break;

					case "range-days":

						// Format the range of "good" days
						$dayStart = $this->getNumberFromDay($objSlots->dayStart);
						$dayStop = $this->getNumberFromDay($objSlots->dayStop);
						$dayCurrent = date('N');

						// Skip if the current day isn't here
						if($dayCurrent < (int)$dayStart || $dayCurrent > (int)$dayStop)
							continue(2);

						// Format the timestamp of current day
						$intDay = mktime(0, 0, 0, date('m'), date('d'), date('Y'));

						// Skip if the open/close times doesn'y match
						if($objDate->tstamp < $intDay + $objSlots->openTime || $objDate->tstamp > $intDay + $objSlots->closeTime)
							continue(2);
					break;

					case "date":
						$objTmpDate = new \Date($objSlots->dateStart);

						// Skip if the date is differente
						if($objDate->parse("d-m-Y") != $objTmpDate->parse("d-m-Y"))
							continue(2);

						// Skip if the open/close times doesn't match
						if($objDate->tstamp < $objSlots->dateStart + $objSlots->openTime || $objDate->tstamp > $objSlots->dateStart + $objSlots->closeTime)
							continue(2);
					break;

					case "range-dates":

						// Skip if the date is in range
						if($objDate->tstamp < $objSlots->dateStart || $objDate->tstamp > $objSlots->dateStop)
							continue(2);

						// Format the timestamp of current day
						$intDay = mktime(0, 0, 0, date('m'), date('d'), date('Y'));

						// Skip if the open/close times doesn'y match
						if($objDate->tstamp < $intDay + $objSlots->openTime || $objDate->tstamp > $intDay + $objSlots->closeTime)
							continue(2);

					break;
				}

				$arrSlots[] = $objSlots->row();
			}

			dump($arrSlots);

			// Get all the bookings of the current week
			$arrBookings = $this->findBookings($intBookingType, $intStart, $intStop);

			



			return $arrBookings;
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
		for($i = 0; $i < 7; $i++)
			if($strDay == $GLOBALS['TL_LANG']['DAYS'][$i])
				return $i;
	}
}