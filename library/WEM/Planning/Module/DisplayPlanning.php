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

		// Add meta to head to handle Ajax Requests
		$GLOBALS['TL_HEAD'][] = sprintf('<meta name="contao_module" data-module="%s" data-id="%s" />', $this->type, $this->id);

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
			<script src="system/modules/wem-contao-planning/assets/js/calendar_functions.js"></script>
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
		 */
		
		/**
		 * TODO - FEATURES
		 * Multilingue
		 * Rappel de rdvs
		 */
		
		/**
		 * TODO - UX
		 * Stocker en localStorage la réservation pour proposer de la reprendre tant qu'elle est "réservée" ?
		 * Ajouter des timers indicatifs permettant de signaler à l'utilisateur que sa réservation est expiré (s'il a une modal ouverte)
		 * Designer globalement le module proprement, avec le framway
		 * Mettre les horaires complètes, genre 22h au lieu de 10h
		 * Limiter la vue des horaires si on a des grandes plages non réservables ? >> A la main à chaque installation pour l'instant
		 * A la mise à jour d'un rendez-vous, reload avec l'URL du nouveau rendez-vous
		 * A l'annulation d'un rendez-vous, reload sur l'URL de base
		 * Masque qui invite à Fast Travel vers une date : L'agencer mieux pour qu'il ne soit pas autant "gros"
		 * Réduire la vue globale du calendrier
		 */	
		
		/**
		 * BUGS
		 * 
		 */
		
		/**
		 * IMPROVEMENTS
		 * Ajouter une configuration du "Combien de temps on peut réserver", actuellement, on ne peut pas réserver moins de 24h à l'avance.
		 * Ajouter une configuration du "Combien de temps dure un slot", actuellement, on a décidé que ce serait 30 minutes mais voilà.
		 * Ajouter de la configuration de FullCalendar directement dans le planning, genre "business hours" ou "weekends", ou les couleurs des eventsSources.
		 * Ajouter la possibilité d'avoir un mode "Connexion obligatoire" pour réserver
		 * Ajouter la possibilité d'avoir accès à ses rendez-vous via un compte utilisateur
		 * Ajouter la possibilité d'ajouter ledit rendez-vous à Google Agenda ou à iCal, depuis l'email ou depuis le récapitulatif
		 * Ajouter une synchronisation des rendez-vous à Google Agenda / iCal de l'administrateur
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

			// Get planning booking types
			$this->bookingTypes = $this->findBookingTypes();

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

			// Catch Update request
			if(Input::get('update'))
			{
				$this->updateBooking();
				//return;
			}

			// Catch Ajax Request
			if(Input::post("TL_AJAX") && Input::post('module') == $this->id)
			{
				$this->executeAjaxRequest();
				die;
			}

			// Send data to template
			$this->Template->bookingTypes = $this->bookingTypes;
		}
		catch(Exception $e)
		{
			$this->Template->blnError = true;
			$this->Template->strError = $e->getMessage();
		}
	}

	/**
	 * Handle Booking Update
	 */
	protected function updateBooking()
	{
		try
		{
			// Break the module if we want to update a booking, but it doesn't exists
			if(Booking::countBy('token', Input::get('update')) == 0)
				throw new Exception($GLOBALS['TL_LANG']['WEM']['PLANNING']['ERR']['bookingNotFound']);

			// Get the booking
			$this->objBooking = Booking::findOneBy('token', Input::get('update'));

			// Throw exception if the booking doesn't belong to the current planning
			if($this->objBooking->pid != $this->wem_planning)
				throw new Exception($GLOBALS['TL_LANG']['WEM']['PLANNING']['ERR']['bookingNotFound']);

			// Throw exception if the booking has been canceled
			if($this->objBooking->status == 'canceled')
				throw new Exception("Ce rendez-vous a été annulé.");

			// Throw exception if the booking is already done
			if($this->objBooking->status == 'done')
				throw new Exception("Ce rendez-vous a déjà été réalisé.");

			// Create a new template for this
			$this->Template = new \FrontendTemplate("mod_wem_display_planning_update");
			$this->Template->canUpdate = true;
			$this->Template->canCancel = true;
			$this->Template->bookingTypes = $this->bookingTypes;
			$this->Template->booking = $this->objBooking;
			$this->Template->bookingType = $this->bookingTypes[$this->objBooking->bookingType];

			// Check if the user can update the booking
			if(
				$this->objPlanning->canUpdateBooking
				&& $this->objPlanning->canUpdateBookingUntil > 0
				&& strtotime(sprintf("-%s hours", $this->objPlanning->canUpdateBookingUntil), $this->objBooking->date) <= time()
			)
				$this->Template->canUpdate = false;
				
			// Check if the user can cancel the booking
			if(
				$this->objPlanning->canCancelBooking
				&& $this->objPlanning->canCancelBookingUntil > 0
				&& strtotime(sprintf("-%s hours", $this->objPlanning->canCancelBookingUntil), $this->objBooking->date) <= time()
			)
				$this->Template->canCancel = false;
		}
		catch(Exception $e)
		{
			throw $e;
		}
	}
}