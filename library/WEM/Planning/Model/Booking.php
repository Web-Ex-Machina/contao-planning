<?php

/**
 * Module Planning for Contao Open Source CMS
 *
 * Copyright (c) 2018 Web ex Machina
 *
 * @author Web ex Machina <https://www.webexmachina.fr>
 */

namespace WEM\Planning\Model;

use \RuntimeException as Exception;
use Contao\Model;

/**
 * Reads and writes items
 */
class Booking extends Model
{
	/**
	 * Table name
	 * @var string
	 */
	protected static $strTable = 'tl_wem_planning_booking';

	/**
	 * Find items, depends on the arguments
	 * @param Array
	 * @param Int
	 * @param Int
	 * @param Array
	 * @return Collection
	 */
	public static function findItems($arrConfig = array(), $intLimit = 0, $intOffset = 0, $arrOptions = array())
	{
		try
		{
			$t = static::$strTable;
			$arrColumns = static::formatColumns($arrConfig);
				
			if($intLimit > 0)
				$arrOptions['limit'] = $intLimit;

			if($intOffset > 0)
				$arrOptions['offset'] = $intOffset;

			if(!isset($arrOptions['order']))
				$arrOptions['order'] = "$t.date ASC";

			if(empty($arrColumns))
				return static::findAll($arrOptions);
			else
				return static::findBy($arrColumns, null, $arrOptions);
		}
		catch(Exception $e)
		{
			throw $e;
		}
	}

	/**
	 * Count items, depends on the arguments
	 * @param Array
	 * @param Array
	 * @return Integer
	 */
	public static function countItems($arrConfig = array(), $arrOptions = array())
	{
		try
		{
			$t = static::$strTable;
			$arrColumns = static::formatColumns($arrConfig);

			if(empty($arrColumns))
				return static::countAll($arrOptions);
			else
				return static::countBy($arrColumns, null, $arrOptions);
		}
		catch(Exception $e)
		{
			throw $e;
		}
	}

	/**
	 * Format ItemModel columns
	 * @param  [Array] $arrConfig [Configuration to format]
	 * @return [Array]            [The Model columns]
	 */
	public static function formatColumns($arrConfig)
	{
		try
		{
			$t = static::$strTable;
			$arrColumns = array();

			if($arrConfig["pid"])
				$arrColumns[] = "$t.pid = ". $arrConfig["pid"];

			if($arrConfig["date_start"] && $arrConfig["date_stop"])
			{
				if(!$arrConfig["date_operator"])
					$arrConfig["date_operator"] = "OR";

				$arrColumns[] = "($t.date >= ". $arrConfig["date_start"]." ".$arrConfig["date_operator"]." $t.date <= ". $arrConfig["date_stop"].")";
			}
			else
			{
				if($arrConfig["date_start"])
					$arrColumns[] = "$t.date >= ". $arrConfig["date_start"];

				if($arrConfig["date_stop"])
					$arrColumns[] = "$t.date <= ". $arrConfig["date_stop"];
			}

			if($arrConfig["dateEnd_start"] && $arrConfig["dateEnd_stop"])
			{
				if(!$arrConfig["dateEnd_operator"])
					$arrConfig["dateEnd_operator"] = "OR";

				$arrColumns[] = "($t.dateEnd >= ". $arrConfig["dateEnd_start"]." ".$arrConfig["dateEnd_operator"]." $t.dateEnd <= ". $arrConfig["dateEnd_stop"].")";
			}
			else
			{
				if($arrConfig["dateEnd_start"])
					$arrColumns[] = "$t.dateEnd >= ". $arrConfig["dateEnd_start"];

				if($arrConfig["dateEnd_stop"])
					$arrColumns[] = "$t.dateEnd <= ". $arrConfig["dateEnd_stop"];
			}

			if($arrConfig["bookingType"])
				$arrColumns[] = "$t.bookingType = ". $arrConfig["bookingType"];

			// Must include the SQL WHERE operator (we cannot predict which operator we will need)
			if($arrConfig["lastname"])
				$arrColumns[] = "$t.lastname ". $arrConfig["lastname"];

			// Must include the SQL WHERE operator (we cannot predict which operator we will need)
			if($arrConfig["firstname"])
				$arrColumns[] = "$t.firstname ". $arrConfig["firstname"];

			// Must include the SQL WHERE operator (we cannot predict which operator we will need)
			if($arrConfig["phone"])
				$arrColumns[] = "$t.phone ". $arrConfig["phone"];

			// Must include the SQL WHERE operator (we cannot predict which operator we will need)
			if($arrConfig["email"])
				$arrColumns[] = "$t.email ". $arrConfig["email"];

			// Must include the SQL WHERE operator (we cannot predict which operator we will need)
			if($arrConfig["comments"])
				$arrColumns[] = "$t.comments ". $arrConfig["comments"];

			if($arrConfig["status"])
			{
				if(is_array($arrConfig["status"]))
					$arrColumns[] = "$t.status IN('" . implode("','", $arrConfig["status"]) . "')";
				else
					$arrColumns[] = "$t.status = '". $arrConfig["status"] ."'";
			}

			if($arrConfig["isUpdate"] === 1)
				$arrColumns[] = "$t.isUpdate = 1";
			else if($arrConfig["isUpdate"] === 0)
				$arrColumns[] = "$t.isUpdate = ''";

			if($arrConfig["bookingSrc"])
				$arrColumns[] = "$t.bookingSrc = ". $arrConfig["bookingSrc"];

			if($arrConfig["token"])
				$arrColumns[] = "$t.token = '". $arrConfig["token"] ."'";

			if($arrConfig["not"])
				$arrColumns[] = $arrConfig["not"];

			return $arrColumns;
		}
		catch(Exception $e)
		{
			throw $e;
		}
	}

	/**
	 * Return the number of bookings for the current dates
	 * @param  [Integer] $intPid 	[Planning ID]
	 * @param  [Integer] $intStart 	[Start date]
	 * @param  [Integer] $intStop 	[Stop date]
	 * @return [Integer]			[Number of booking matching the query]
	 */
	public static function countByDates($intPid, $intStart, $intStop)
	{
		// Rules, because it's tricky
		// r1 : Restrict by PID (Planning ID)
		// r2 : Restrict by Status (Drafted or Confirmed)
		// r3 : Restrict by Dates
		// r3.1.1 : We want booking who started just at the beginning of the slot AND where it's below the stop (because it'll block future slots otherwise)
		// r3.1.2 : Same logic, but with the end date
		// r3.2 : We want to excluse booking who "contains" the slot, so the booking start will be lower than slot start but also, the booking end will be greater than slot end.
		try
		{
			$t = static::$strTable;

			$strSql = "
				SELECT * 
				FROM $t
				WHERE pid = $intPid
				AND status IN ('drafted', 'pending', 'confirmed')
				AND (
						(
							(date > $intStart AND date < $intStop)
							OR
							(dateEnd > $intStart AND dateEnd < $intStop)
						)
						OR
						(date <= $intStart AND dateEnd >= $intStop)
					)
			";

			$objBookings = \Database::getInstance()->prepare($strSql)->execute();
			return $objBookings->count();
		}
		catch(Exception $e)
		{
			throw $e;
		}
	}

	/**
	 * Delete all the booking with the status "drafted" who are created 15 minutes ago (or more)
	 */
	public static function purgeDraftedBookings()
	{
		try
		{
			$t = static::$strTable;
			$time = time() - (15 * 60);

			$strSql = "
				DELETE FROM $t
				WHERE status = 'drafted'
				AND created_at <= $time
			";

			\Database::getInstance()->prepare($strSql)->execute();
		}
		catch(Exception $e)
		{
			throw $e;
		}
	}
}