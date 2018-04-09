<?php

/**
 * Module Planning for Contao Open Source CMS
 *
 * Copyright (c) 2018 Web ex Machina
 *
 * @author Web ex Machina <https://www.webexmachina.fr>
 */

namespace WEM\Planning\Controller;

use Exception;

use Contao\Config;
use Contao\Controller;
use Contao\Date;
use Contao\Environment;
use Contao\System;

use NotificationCenter\Model\Notification;

use WEM\Planning\Model\Planning;
use WEM\Planning\Model\Booking;
use WEM\Planning\Model\BookingType;

/**
 * Planning Core functions
 */
class Core extends Controller
{
	/**
	 * Fetch, prepare and send Planning Notifications
	 * @param  [String] $strNotificationType [Notification type]
	 * @param  [Object] $objBooking          [Booking Model]
	 */
	public static function sendNotifications($strNotificationType, $objBooking)
	{
		try
		{
			// Fetch the notifications to send
			$objNotifications = \Database::getInstance()->execute("SELECT id,title FROM tl_nc_notification WHERE type='$strNotificationType' ORDER BY title");

			if($objNotifications->count() > 0)
			{
				$arrTokens = static::prepareNotificationTokens($objBooking);

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
	public static function prepareNotificationTokens($objBooking)
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
			$arrBookingTypes = static::findBookingTypes($objBooking->pid);
			foreach($arrBookingTypes as $strKey => $varValue)
				$arrTokens["bookingType_".$strKey] = $varValue;

			// Build the update/cancel link
			$strRequest = Environment::get('base').'planning.html';
			$arrTokens['updateLink'] = $strRequest.'?update='.$objBooking->token;

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
	 * Find Booking Types for the current planning
	 * @return [Array] [Booking Types Array]
	 */
	public static function findBookingTypes($intPlanning)
	{
		try
		{
			if(!$objBookingTypes = BookingType::findItems(["pid"=>$intPlanning]))
				throw new Exception(sprintf($GLOBALS['TL_LANG']['WEM']['PLANNING']['ERR']['noBookingTypesFound'], $intPlanning));

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
}