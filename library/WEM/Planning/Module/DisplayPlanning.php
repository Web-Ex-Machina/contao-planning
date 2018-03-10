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
use Contao\CoreBundle\Exception\PageNotFoundException;
use Patchwork\Utf8;
use Contao\Date;
use Contao\Input;
use Contao\Validator;
use Contao\System;
use WEM\Planning\Model\Planning;
use WEM\Planning\Model\Booking;

/**
 * Front end module "Planning Display".
 *
 * @author Web ex Machina <http://www.webexmachina.fr>
 */
class DisplayPlanning extends ModulePlanning
{
	/**
	 * Template
	 * @var string
	 */
	protected $strTemplate = 'mod_wem_display_planning';

	/**
	 * View Types
	 * @var array [List of view types available]
	 */
	protected $viewTypes = ["day", "week", "month"];

	/**
	 * Display a wildcard in the back end
	 *
	 * @return string
	 */
	public function generate()
	{
		if (TL_MODE == 'BE')
		{
			/** @var BackendTemplate|object $objTemplate */
			$objTemplate = new \BackendTemplate('be_wildcard');

			$objTemplate->wildcard = '### ' . Utf8::strtoupper($GLOBALS['TL_LANG']['FMD']['wem_display_planning'][0]) . ' ###';
			$objTemplate->title = $this->headline;
			$objTemplate->id = $this->id;
			$objTemplate->link = $this->name;
			$objTemplate->href = 'contao/main.php?do=themes&amp;table=tl_module&amp;act=edit&amp;id=' . $this->id;

			return $objTemplate->parse();
		}

		// Temporary
		$GLOBALS['TL_HEAD'][] = '
			<link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/4.0.0/css/bootstrap.min.css" integrity="sha384-Gn5384xqQ1aoWXA+058RXPxPg6fy4IWvTNh0E263XmFcJlSAwiGgFAW/dAiS6JXm" crossorigin="anonymous">
			<link rel="stylesheet" href="//cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.css">
			<link rel="stylesheet" href="system/modules/wem-contao-planning/assets/vendor/fullcalendar/3.8.2/fullcalendar.min.css">
			<script src="system/modules/wem-contao-planning/assets/vendor/fullcalendar/3.8.2/lib/jquery.min.js"></script>
			<script src="system/modules/wem-contao-planning/assets/vendor/fullcalendar/3.8.2/lib/jquery-ui.min.js"></script>
			<script src="system/modules/wem-contao-planning/assets/vendor/fullcalendar/3.8.2/lib/moment.min.js"></script>
			<script src="https://cdnjs.cloudflare.com/ajax/libs/popper.js/1.12.9/umd/popper.min.js" integrity="sha384-ApNbgh9B+Y1QKtv3Rn7W3mgPxhU9K/ScQsAP7hUibX39j7fakFPskvXusvfa0b4Q" crossorigin="anonymous"></script>
			<script src="https://maxcdn.bootstrapcdn.com/bootstrap/4.0.0/js/bootstrap.min.js" integrity="sha384-JZR6Spejh4U02d8jOt6vLEHfe/JQGiRRSQQxSfFWpi1MquVdAyjUar5+76PVCmYl" crossorigin="anonymous"></script>
			<script src="//cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.js"></script>
			<script src="system/modules/wem-contao-planning/assets/vendor/fullcalendar/3.8.2/fullcalendar.min.js"></script>
			<script src="system/modules/wem-contao-planning/assets/vendor/fullcalendar/3.8.2/locale/fr.js"></script>
			<script src="system/modules/wem-contao-planning/assets/js/display_planning.js"></script>
		';

		return parent::generate();
	}

	/**
	 * Generate the module
	 */
	protected function compile()
	{
		/**
		 * TODO - BACKOFFICE
		 * Notification de changement de statut d'une réservation, envoyée via le BO ?
		 * Module d'export et de récapitulatif des réservations
		 * BUG > La légende avec les pictos s'affiche aussi en mode édition d'une réservation, c'pas utile.
		 */
		
		/**
		 * TODO - FEATURES
		 * Mise à jour d'une réservation
		 * Annulation d'une réservation
		 * Rappel de rdvs
		 * Ajouter une proposition de rendez-vous s'il n'y a pas de slots disponibles dans la période sélectionnée
		 */
		
		/**
		 * TODO - UX
		 * Stocker en localStorage la réservation pour proposer de la reprendre tant qu'elle est "réservée" ?
		 * Ajouter des timers indicatifs permettant de signaler à l'utilisateur que sa réservation est expiré (s'il a une modal ouverte)
		 * Ajouter un refresh automatique des calendriers toutes les minutes
		 * Jeff - Designer globalement le module proprement, avec le framway
		 * Mettre les horaires complètes, genre 22h au lieu de 10h
		 */		
		
		/**
		 * BUGS
		 * 
		 */

		try
		{
			// For debug
			set_time_limit(2);

			// Purge drafted & expired bookings
			Booking::purgeDraftedBookings();

			// Load language file
			System::loadLanguageFile('tl_wem_planning_booking');

			// Get the planning config
			$this->objPlanning = Planning::findByPk($this->wem_planning);

			// Throw error if there is no planning
			if(!$this->objPlanning)
				throw new Exception(sprintf($GLOBALS['TL_LANG']['WEM']['PLANNING']['ERR']['planningNotFound'], $this->wem_planning));

			// Catch Update request
			if(Input::get('update'))
			{
				$this->updateBooking();
				return;
			}

			// Default config
			$arrConfig = ["getBookingTypes"=>true];
			
			// Adjust view type if we post one
			if(Input::post("viewType") && in_array(Input::post("viewType"), $this->viewTypes))
				$this->viewType = Input::post("viewType");
			else
				$this->viewType = "week";

			// Prepare the start date
			if(Input::post("start"))
				$this->start = strtotime(Input::post("start"));
			else
				$this->start = time();

			// Prepare the stop date
			if(Input::post("stop"))
				$this->stop = strtotime(Input::post("stop"));
			else
			{
				$objStop = new Date(date("Y-m-d", $this->start), "Y-m-d");

				switch($this->viewType)
				{
					case "month": $this->stop = $objStop->monthEnd; break;
					case "day": $this->stop = $objStop->dayEnd; break;
					case "week":
					default:
						$this->stop = $objStop->getWeekEnd(1);
				}
			}

			// Get planning booking types
			$this->bookingTypes = $this->findBookingTypes();

			// Catch Ajax Request
			if(Input::post("TL_AJAX") && Input::post('module') == $this->id)
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
									$arrItems[] = 
									[
										"title" => "Réservé",
										"start" => $arrBooking['start'],
										"end"   => $arrBooking['end']
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

							$arrItems = $this->findAvailableSlots(Input::post('bookingType'));
							echo json_encode($arrItems, true);
							die;

						break;

						case 'draftBooking':
							// Throw exception if we don't have booking type
							if(!Input::post('bookingType'))
								throw new Exception($GLOBALS['TL_LANG']['WEM']['PLANNING']['ERR']['noBookingTypeSent']);

							// Throw exception if we don't have start/stop dates
							if(!Input::post('start') || !Input::post('stop'))
								throw new Exception($GLOBALS['TL_LANG']['WEM']['PLANNING']['ERR']['noDatesSent']);

							// Check if the booking type is valid
							if(!array_key_exists(Input::post('bookingType'), $this->bookingTypes))
								throw new Exception($GLOBALS['TL_LANG']['WEM']['PLANNING']['ERR']['invalidBookingType']);

							// Check if the slot is still available
							$intDuration = $this->bookingType[Input::post('bookingType')]["duration"] * 60 * 60;
							if(!$this->checkIfSlotIsAvailable($intDuration, $this->start+1))
								throw new Exception($GLOBALS['TL_LANG']['WEM']['PLANNING']['ERR']['slotIsNotAvailableAnymore']);

							// Create the booking
							$objBooking = new Booking();
							$objBooking->pid = $this->wem_planning;
							$objBooking->created_at = time();
							$objBooking->tstamp = time();
							$objBooking->date = $this->start;
							$objBooking->dateEnd = $this->stop;
							$objBooking->bookingType = Input::post('bookingType');
							$objBooking->status = "drafted";
							$objBooking->token = md5(uniqid(mt_rand(), true));

							// Throw exception if the booking has not been saved
							if(!$objBooking->save())
								throw new Exception($GLOBALS['TL_LANG']['WEM']['PLANNING']['ERR']['bookingCreationError']);

							// And parse the form to fill
							$objTemplate = new \FrontendTemplate("wem_calendar_booking_form");
							$objTemplate->start = $this->start;
							$objTemplate->stop = $this->stop;
							$objTemplate->bookingType = $this->bookingType[Input::post('bookingType')];
							$objTemplate->module_id = $this->id;
							$objTemplate->booking_id = $objBooking->id;

							// Echo the template and die
							echo $objTemplate->parse();
							die;

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
							$objNotifications = \Database::getInstance()->execute("SELECT id,title FROM tl_nc_notification WHERE type='confirm_booking' ORDER BY title");

							if($objNotifications->count() > 0)
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
								$strRequest = \Environment::get('base').'/'.\Environment::get('request');
								$arrTokens['updateLink'] = $strRequest.'?update='.$objBooking->token;
								$arrTokens['cancelLink'] = $strRequest.'?cancel='.$objBooking->token;

								// Build the recipients
								$arrTokens['user_email'] = $objBooking->email;

								while($objNotifications->next())
								{
									$objNotification = \NotificationCenter\Model\Notification::findByPk($objNotifications->id);
									$objNotification->send($arrTokens);
								}
							}

							// And prepare the response
							$arrResponse['status'] = 'success';
							$arrResponse['message'] = $GLOBALS['TL_LANG']['WEM']['PLANNING']['MSG']['bookingSuccess'];

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

			// Send data to template
			$this->Template->bookingTypes = $this->bookingTypes;
			$this->Template->calendar = $this->prepareCalendar();
		}
		catch(Exception $e)
		{
			$this->Template->blnError = true;
			$this->Template->strError = $e->getMessage();
		}
	}

	/**
	 * Configure and parse a calendar template
	 * @param  [String]  $strTemplate [Template name]
	 * @param  [String]  $strClass    [Specific class to use]
	 * @return [String]               [Template parsed]
	 */
	protected function prepareCalendar($strTemplate = "wem_calendar_default", $strClass = '')
	{
		try
		{
			/** @var \FrontendTemplate|object $objTemplate */
			$objTemplate = new \FrontendTemplate($strTemplate);
			$objTemplate->module_id = $this->id;
			$objTemplate->class = $strClass;
			$objTemplate->start = $this->start;
			$objTemplate->stop = $this->stop;

			return $objTemplate->parse();
		}
		catch(Exception $e)
		{
			throw $e;
		}
	}

	/**
	 * Handle Booking Update
	 */
	protected function updateBooking()
	{
		// Break the module if we try to update a booking and the planning doesn't allow
		if(!$this->objPlanning->canUpdateBooking)
			throw new Exception($GLOBALS['TL_LANG']['WEM']['PLANNING']['ERR']['cannotUpdateBooking']);

		// Break the module if we want to update a booking, but it doesn't exists
		if(Booking::countBy('token', Input::get('update')) == 0)
			throw new Exception($GLOBALS['TL_LANG']['WEM']['PLANNING']['ERR']['bookingNotFound']);

		// Get the booking
		$objBooking = Booking::findOneBy('token', Input::get('update'));

		// Throw exception if the booking doesn't belong to the current planning
		if($objBooking->pid != $this->wem_planning)
			throw new Exception($GLOBALS['TL_LANG']['WEM']['PLANNING']['ERR']['bookingNotFound']);

		// Create a new template for this
		$this->Template = new \FrontendTemplate("mod_wem_display_planning_update");
		$this->Template->bookingTypes = $this->bookingTypes;

		dump($objBooking->row());
	}
}