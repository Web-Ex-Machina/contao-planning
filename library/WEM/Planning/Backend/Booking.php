<?php

/**
 * Module Planning for Contao Open Source CMS
 *
 * Copyright (c) 2018 Web ex Machina
 *
 * @author Web ex Machina <https://www.webexmachina.fr>
 */

namespace WEM\Planning\Backend;

use Exception;
use Contao\CoreBundle\Exception\RedirectResponseException;
use Contao\Backend;

use WEM\Planning\Controller\Core;
use WEM\Planning\Model\Booking as BookingModel;

/**
 * Handle Backend functions
 */
class Booking extends Backend
{
	public function confirmBooking($objDc)
	{
		// Update status
		$objBooking = BookingModel::findByPk($objDc->id);
		$objBooking->tstamp = time();
		$objBooking->status = "confirmed";
		$objBooking->save();

		// Send the notification
		Core::sendNotifications('confirm_booking', $objBooking);

		// Add a message
		\Message::addConfirmation("La réservation a été confirmée");

		// Redirect
		$referer = preg_replace('/&(amp;)?(key|id)=[^&]*/', '', \Environment::get('request'));
		$this->redirect($referer);
	}

	public function denyBooking($objDc)
	{
		// Update status
		$objBooking = BookingModel::findByPk($objDc->id);
		$objBooking->tstamp = time();
		$objBooking->status = "denied";
		$objBooking->save();

		// Send the notification
		Core::sendNotifications('deny_booking', $objBooking);

		// Add a message
		\Message::addConfirmation("La réservation a été refusée");

		// Redirect
		$referer = preg_replace('/&(amp;)?(key|id)=[^&]*/', '', \Environment::get('request'));
		$this->redirect($referer);
	}
}