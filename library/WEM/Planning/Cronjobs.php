<?php

/**
 * Module Planning for Contao Open Source CMS
 *
 * Copyright (c) 2018 Web ex Machina
 *
 * @author Web ex Machina <https://www.webexmachina.fr>
 */

namespace WEM\Planning;

use Exception;

use Contao\Date;
use Contao\Controller;
use Contao\System;

use \NotificationCenter\Model\Notification;

use WEM\Planning\Model\Planning;
use WEM\Planning\Model\Booking;
use WEM\Planning\Model\BookingType;

/**
 * Cronjobs functions
 */
class Cronjobs extends Controller
{
	/**
	 * Daily Cronjob - Send Daily Bookings to the administrator, in the email, and in PDF to print purpose
	 */
	public function sendDailyBookings()
	{
		try
		{
			// Find plannings
			$objPlannings = Planning::findBy("sendDailySummary", 1);

			// Break if there is no plannings
			if(!$objPlannings)
				throw new Exception("Pas de plannings configurés pour envoyer un récapitulatif quotidien");

			// Configure the range dates
			$objDate = new Date();
			$intStart = $objDate->dayBegin();
			$intStop = $objDate->dayEnd();

			// Get the bookings
			while($objPlannings->next())
			{
				$arrConfig = array("pid"=>$objPlannings->id, "date_start"=>$intStart, "date_stop"=>$intStop, "date_operator"=>"AND", "status"=>"confirmed");
				$objBookings = Booking::findItems($arrConfig);

				// Break the function if there is no bookings today
				if(!$objBookings)
					throw new Exception("Aucune réservation aujourd'hui !");

				// Fetch the notification
				$objNotification = Notification::findByPk($objPlannings->dailySummaryNotification);

				// Break if we fail to find the matching notification
				if(!$objNotification)
					throw new Exception(sprintf("La notification du planning ID %s est invalide", $objPlannings->id));

				// Prepare the tokens Array
				$arrTokens = array(
					"admin_email" => $GLOBALS['TL_ADMIN_EMAIL'],
					"date" => Date::parse(Config::get('datimFormat'), $intStart),
					"bookings" => '',
					"bookings_file" => '' 
				);

				// Prepare the booking table
				$strPattern = '
					<table>
						<thead>
							<tr>
								<th>Date & Heure</th>
								<th>Type de réservation</th>
								<th>Informations</th>
							</tr>
						</thead>
						<tbody>
							%s
						</tbody>
					</table>
				';
				$strLines = '';

				// Booking Loop !
				while($objBookings->next())
				{
					// Fetch the bookingType
					$objBookingType = BookingType::findByPk($objBookings->bookingType);

					// And build the booking row
					$strLines .= '<tr><td>De '.date('d/m/Y à H:i', $objBookings->date).' à '.date('d/m/Y à H:i', $objBookings->dateEnd).'</td><td>'.$objBookingType->title.'</td><td>'.$objBookings->firstname.' '.$objBookings->lastname.'<br />'.$objBookings->phone.'<br />'.$objBookings->email;

					if($objBookings->comments)
						$strLines .= '<br />'.$objBookings->comments;

					$strLines .= '</td></tr>';
				}

				$strBookings = sprintf($strPattern, $strLines);
			}
		}
		catch(Exception $e)
		{
			System::log("Error : ".$e->getMessage(), __METHOD__, TL_CRON);
		}
	}
}