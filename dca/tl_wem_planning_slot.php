<?php

/**
 * Module Planning for Contao Open Source CMS
 *
 * Copyright (c) 2018 Web ex Machina
 *
 * @author Web ex Machina <https://www.webexmachina.fr>
 */

/**
 * Table tl_wem_planning_slot
 */
$GLOBALS['TL_DCA']['tl_wem_planning_slot'] = array
(

	// Config
	'config' => array
	(
		'dataContainer'               => 'Table',
		'ptable'                      => 'tl_wem_planning',
		'switchToEdit'                => true,
		'enableVersioning'            => true,
		'sql' => array
		(
			'keys' => array
			(
				'id' => 'primary',
				'pid' => 'index',
			)
		)
	),

	// List
	'list' => array
	(
		'sorting' => array
		(
			'mode'                    => 4,
			'fields'                  => array('sorting'),
			'headerFields'            => array('title', 'tstamp'),
			'panelLayout'             => 'filter;sort,search,limit',
			'child_record_callback'   => array('tl_wem_planning_slot', 'listItems'),
			'child_record_class'      => 'no_padding'
		),
		'global_operations' => array
		(
			'all' => array
			(
				'label'               => &$GLOBALS['TL_LANG']['MSC']['all'],
				'href'                => 'act=select',
				'class'               => 'header_edit_all',
				'attributes'          => 'onclick="Backend.getScrollOffset()" accesskey="e"'
			)
		),
		'operations' => array
		(
			'edit' => array
			(
				'label'               => &$GLOBALS['TL_LANG']['tl_wem_planning_slot']['edit'],
				'href'                => 'act=edit',
				'icon'                => 'edit.svg'
			),
			'copy' => array
			(
				'label'               => &$GLOBALS['TL_LANG']['tl_wem_planning_slot']['copy'],
				'href'                => 'act=paste&amp;mode=copy',
				'icon'                => 'copy.svg'
			),
			'cut' => array
			(
				'label'               => &$GLOBALS['TL_LANG']['tl_wem_planning_slot']['cut'],
				'href'                => 'act=paste&amp;mode=cut',
				'icon'                => 'cut.svg'
			),
			'delete' => array
			(
				'label'               => &$GLOBALS['TL_LANG']['tl_wem_planning_slot']['delete'],
				'href'                => 'act=delete',
				'icon'                => 'delete.svg',
				'attributes'          => 'onclick="if(!confirm(\'' . $GLOBALS['TL_LANG']['MSC']['deleteConfirm'] . '\'))return false;Backend.getScrollOffset()"'
			),
			'show' => array
			(
				'label'               => &$GLOBALS['TL_LANG']['tl_wem_planning_slot']['show'],
				'href'                => 'act=show',
				'icon'                => 'show.svg'
			)
		)
	),

	// Palettes
	'palettes' => array
	(
		'__selector__'				  => array('type'),
		'default'                     => '
			{global_legend},canBook,openTime,closeTime;
			{slot_legend},type
		'
	),

	// Subpalettes
	'subpalettes' => array
	(
		'type_day'			  => 'dayStart',
		'type_date'			  => 'dateStart',
		'type_range-days'     => 'dayStart,dayStop',
		'type_range-dates'    => 'dateStart,dateStop',
	),

	// Fields
	'fields' => array
	(
		'id' => array
		(
			'sql'                     => "int(10) unsigned NOT NULL auto_increment"
		),
		'tstamp' => array
		(
			'sql'                     => "int(10) unsigned NOT NULL default '0'"
		),
		'created_at' => array
		(
			'default'				  => time(),
			'sql'                     => "int(10) unsigned NOT NULL default '0'"
		),
		'sorting' => array
		(
			'sql'                     => "int(10) unsigned NOT NULL default '0'"
		),
		'pid' => array
		(
			'foreignKey'              => 'tl_wem_planning.title',
			'sql'                     => "int(10) unsigned NOT NULL default '0'",
			'relation'                => array('type'=>'belongsTo', 'load'=>'eager')
		),

		'canBook' => array
		(
			'label'                   => &$GLOBALS['TL_LANG']['tl_wem_planning_slot']['canBook'],
			'exclude'                 => true,
			'filter'                  => true,
			'flag'                    => 1,
			'inputType'               => 'checkbox',
			'eval'                    => array('doNotCopy'=>true),
			'sql'                     => "char(1) NOT NULL default ''"
		),
		'openTime' => array
		(
			'label'                   => &$GLOBALS['TL_LANG']['tl_wem_planning_slot']['openTime'],
			'default'                 => 0,
			'exclude'                 => true,
			'inputType'               => 'text',
			'load_callback'			  => array('tl_wem_planning_slot', 'getParentOpenTimeValue'),
			'eval'                    => array('rgxp'=>'time', 'doNotCopy'=>true, 'tl_class'=>'w50'),
			'sql'                     => "int(10) unsigned NOT NULL default '0'"
		),
		'closeTime' => array
		(
			'label'                   => &$GLOBALS['TL_LANG']['tl_wem_planning_slot']['closeTime'],
			'default'                 => 0,
			'exclude'                 => true,
			'inputType'               => 'text',
			'load_callback'			  => array('tl_wem_planning_slot', 'getParentCloseTimeValue'),
			'eval'                    => array('rgxp'=>'time', 'doNotCopy'=>true, 'tl_class'=>'w50'),
			'sql'                     => "int(10) unsigned NOT NULL default '0'"
		),

		'type' => array
		(
			'label'                   => &$GLOBALS['TL_LANG']['tl_wem_planning_slot']['type'],
			'exclude'                 => true,
			'filter'                  => true,
			'flag'                    => 1,
			'inputType'               => 'select',
			'options'        		  => array('day', 'date', 'range-days', 'range-dates'),
			'reference'				  => &$GLOBALS['TL_LANG']['tl_wem_planning_slot']['type'],
			'eval'                    => array('chosen'=>true, 'mandatory'=>true, 'submitOnChange'=>true, 'includeBlankOption'=>true),
			'sql'                     => "varchar(32) NOT NULL default ''"
		),
		'dayStart' => array
		(
			'label'                   => &$GLOBALS['TL_LANG']['tl_wem_planning_slot']['dayStart'],
			'exclude'                 => true,
			'inputType'               => 'select',
			'options_callback'        => array('tl_wem_planning_slot', 'getDays'),
			'eval'                    => array('chosen'=>true, 'mandatory'=>true, 'tl_class'=>'w50'),
			'sql'                     => "varchar(32) NOT NULL default ''"
		),
		'dayStop' => array
		(
			'label'                   => &$GLOBALS['TL_LANG']['tl_wem_planning_slot']['dayStop'],
			'exclude'                 => true,
			'inputType'               => 'select',
			'options_callback'        => array('tl_wem_planning_slot', 'getDays'),
			'eval'                    => array('chosen'=>true, 'mandatory'=>true, 'tl_class'=>'w50'),
			'sql'                     => "varchar(32) NOT NULL default ''"
		),
		'dateStart' => array
		(
			'label'                   => &$GLOBALS['TL_LANG']['tl_wem_planning_slot']['dateStart'],
			'default'                 => time(),
			'exclude'                 => true,
			'filter'                  => true,
			'sorting'                 => true,
			'flag'                    => 8,
			'inputType'               => 'text',
			'eval'                    => array('rgxp'=>'date', 'doNotCopy'=>true, 'datepicker'=>true, 'tl_class'=>'w50 wizard'),
			'sql'                     => "int(10) unsigned NOT NULL default '0'"
		),
		'dateStop' => array
		(
			'label'                   => &$GLOBALS['TL_LANG']['tl_wem_planning_slot']['dateStop'],
			'default'                 => time(),
			'exclude'                 => true,
			'filter'                  => true,
			'sorting'                 => true,
			'flag'                    => 8,
			'inputType'               => 'text',
			'eval'                    => array('rgxp'=>'date', 'doNotCopy'=>true, 'datepicker'=>true, 'tl_class'=>'w50 wizard'),
			'sql'                     => "int(10) unsigned NOT NULL default '0'"
		),
	)
);


/**
 * Provide miscellaneous methods that are used by the data configuration array.
 *
 * @author Web ex Machina <https://www.webexmachina.fr>
 */
class tl_wem_planning_slot extends Backend
{

	/**
	 * Import the back end user object
	 */
	public function __construct()
	{
		parent::__construct();
		$this->import('BackendUser', 'User');
	}

	/**
	 * Get planning default open time value
	 * @param  [Mixed]  $varValue
	 * @param  [Object] $objDc
	 * @return int
	 */
	public function getParentOpenTimeValue($varValue, $objDc)
	{
		if(!$varValue)
		{
			$objPlanning = $this->Database->prepare("SELECT defaultOpenTime FROM tl_wem_planning WHERE id = ?")->limit(1)->execute($objDc->activeRecord->pid);
			$varValue = $objPlanning->defaultOpenTime;
		}

		return $varValue;
	}

	/**
	 * Get planning default close time value
	 * @param  [Mixed]  $varValue
	 * @param  [Object] $objDc
	 * @return int
	 */
	public function getParentCloseTimeValue($varValue, $objDc)
	{
		if(!$varValue)
		{
			$objPlanning = $this->Database->prepare("SELECT defaultCloseTime FROM tl_wem_planning WHERE id = ?")->limit(1)->execute($objDc->activeRecord->pid);
			$varValue = $objPlanning->defaultCloseTime;
		}

		return $varValue;
	}

	/**
	 * Generate an array with days and their translation in the current language
	 * 
	 * @return array
	 */
	public function getDays()
	{
		for($i = 0; $i < 7; $i++)
		{
			$arrDays[$i] = $GLOBALS['TL_LANG']['DAYS'][$i];
		}

		return $arrDays;
	}

	/**
	 * List a booking type
	 *
	 * @param array $arrRow
	 *
	 * @return string
	 */
	public function listItems($arrRow)
	{
		$strHtml = '<div class="tl_content_left">';

		if($arrRow['canBook'])
			$strHtml .= '<strong>Réservation autorisée</strong>';
		else
			$strHtml .= '<strong>Réservation interdite</strong>';

		$strHtml .= ' ('.Date::parse(Config::get('timeFormat'), $arrRow['openTime']).' - '.Date::parse(Config::get('timeFormat'), $arrRow['closeTime']).')';

		switch($arrRow['type'])
		{
			case 'day':
				$strHtml .= ' [Jour - '.$arrRow['dayStart'].']';
			break;

			case 'date':
				$strHtml .= ' [Date - '.Date::parse(Config::get('datimFormat'), $arrRow['dateStart']).']';
			break;

			case 'range-days':
				$strHtml .= ' [Jours - De '.$arrRow['dayStart'].' à '.$arrRow['dayStop'].']';
			break;

			case 'range-dates':
				$strHtml .= ' [Dates - Du '.Date::parse(Config::get('datimFormat'),$arrRow['dateStart']).' au '.Date::parse(Config::get('datimFormat'),$arrRow['dateStop']).']';
			break;
		}

		$strHtml .= '</div>';

		return $strHtml;
	}
}
