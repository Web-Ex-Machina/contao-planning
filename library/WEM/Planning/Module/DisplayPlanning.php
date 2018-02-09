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
use Contao\Input;

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

		return parent::generate();
	}

	/**
	 * Generate the module
	 */
	protected function compile()
	{
		try
		{
			// Default config
			$arrConfig = ["getBookingTypes"=>true];

			// Catch Ajax Request
			if(Input::post("TL_AJAX") && Input::post('module') == $this->id)
			{
				$arrResponse = array();

				try
				{
					switch(Input::post('action'))
					{
						case 'findSlots':

							if(!Input::post('bookingType'))
								throw new Exception($GLOBALS['TL_LANG']['WEM']['PLANNING']['ERR']['noBookingTypeSent']);

							$arrSlots = $this->findAvailableSlots(Input::post('bookingType'));

							if(empty($arrSlots))
								throw new Exception($GLOBALS['TL_LANG']['WEM']['PLANNING']['ERR']['noSlotsAvailable']);

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

			// Get the planning settings
			$arrPlanning = $this->getPlanning($arrConfig);

			// Add JS Files
			$objCombiner = new \Combiner();
			$objCombiner->add('system/modules/wem-contao-planning/assets/js/display_planning.js', time());
			$GLOBALS['TL_JAVASCRIPT'][] = $objCombiner->getCombinedFile();

			dump($this->findAvailableSlots(2));

			// Debug
			$this->Template->planning = $arrPlanning;
		}
		catch(Exception $e)
		{
			throw $e;
		}
	}
}