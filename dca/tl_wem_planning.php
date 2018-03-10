<?php

/**
 * Module Planning for Contao Open Source CMS
 *
 * Copyright (c) 2018 Web ex Machina
 *
 * @author Web ex Machina <https://www.webexmachina.fr>
 */

/**
 * Table tl_wem_planning
 */
$GLOBALS['TL_DCA']['tl_wem_planning'] = array
(

	// Config
	'config' => array
	(
		'dataContainer'               => 'Table',
		'ctable'                      => array('tl_wem_planning_slot', 'tl_wem_planning_booking_type', 'tl_wem_planning_booking'),
		'switchToEdit'                => true,
		'enableVersioning'            => true,
		'sql' => array
		(
			'keys' => array
			(
				'id' => 'primary',
				'alias' => 'index',
			)
		)
	),

	// List
	'list' => array
	(
		'sorting' => array
		(
			'mode'                    => 1,
			'fields'                  => array('title'),
			'flag'                    => 1,
			'panelLayout'             => 'filter;search,limit'
		),
		'label' => array
		(
			'fields'                  => array('title'),
			'format'                  => '%s',
			'label_callback'			  => array('tl_wem_planning', 'listItems'),
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
			'editheader' => array
			(
				'label'               => &$GLOBALS['TL_LANG']['tl_wem_planning']['editheader'],
				'href'                => 'act=edit',
				'icon'                => 'edit.svg',
				//'button_callback'     => array('tl_wem_planning', 'editHeader')
			),
			'copy' => array
			(
				'label'               => &$GLOBALS['TL_LANG']['tl_wem_planning']['copy'],
				'href'                => 'act=copy',
				'icon'                => 'copy.svg',
				//'button_callback'     => array('tl_wem_planning', 'copyArchive')
			),
			'delete' => array
			(
				'label'               => &$GLOBALS['TL_LANG']['tl_wem_planning']['delete'],
				'href'                => 'act=delete',
				'icon'                => 'delete.svg',
				'attributes'          => 'onclick="if(!confirm(\'' . $GLOBALS['TL_LANG']['MSC']['deleteConfirm'] . '\'))return false;Backend.getScrollOffset()"',
				//'button_callback'     => array('tl_wem_planning', 'deleteArchive')
			),
			'show' => array
			(
				'label'               => &$GLOBALS['TL_LANG']['tl_wem_planning']['show'],
				'href'                => 'act=show',
				'icon'                => 'show.svg'
			),
			'bookings' => array
			(
				'label'               => &$GLOBALS['TL_LANG']['tl_wem_planning']['bookings'],
				'href'                => 'table=tl_wem_planning_booking',
				'icon'                => 'system/modules/wem-contao-planning/assets/icon_planning_16.png'
			)
		)
	),

	// Palettes
	'palettes' => array
	(
		'__selector__'                => array('canUpdateBooking', 'canCancelBooking', 'protected'),
		'default'                     => '
			{title_legend},title,alias;
			{default_legend},defaultOpenTime,defaultCloseTime;
			{update_legend},canUpdateBooking;
			{cancel_legend},canCancelBooking;
			{bookingtypes_legend},bookingtypes;
			{slots_legend},slots;
			{protected_legend},protected
		'
	),

	// Subpalettes
	'subpalettes' => array
	(
		'canUpdateBooking'			  => 'canUpdateBookingUntil,canUpdateBookingLimit',
		'canCancelBooking'			  => 'canCancelBookingUntil',
		'protected'                   => 'groups',
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

		'title' => array
		(
			'label'                   => &$GLOBALS['TL_LANG']['tl_wem_planning']['title'],
			'exclude'                 => true,
			'search'                  => true,
			'inputType'               => 'text',
			'eval'                    => array('mandatory'=>true, 'maxlength'=>255, 'tl_class'=>'w50'),
			'sql'                     => "varchar(255) NOT NULL default ''"
		),
		'alias' => array
		(
			'label'                   => &$GLOBALS['TL_LANG']['tl_wem_planning']['alias'],
			'exclude'                 => true,
			'inputType'               => 'text',
			'eval'                    => array('rgxp'=>'alias', 'doNotCopy'=>true, 'unique'=>true, 'maxlength'=>128, 'tl_class'=>'w50'),
			'save_callback' => array
			(
				array('tl_wem_planning', 'generateAlias')
			),
			'sql'                     => "varchar(128) COLLATE utf8_bin NOT NULL default ''"
		),

		'defaultOpenTime' => array
		(
			'label'                   => &$GLOBALS['TL_LANG']['tl_wem_planning']['defaultOpenTime'],
			'default'                 => time(),
			'exclude'                 => true,
			'inputType'               => 'text',
			'eval'                    => array('rgxp'=>'time', 'doNotCopy'=>true, 'tl_class'=>'w50'),
			'sql'                     => "int(10) unsigned NOT NULL default '0'"
		),
		'defaultCloseTime' => array
		(
			'label'                   => &$GLOBALS['TL_LANG']['tl_wem_planning']['defaultCloseTime'],
			'default'                 => time(),
			'exclude'                 => true,
			'inputType'               => 'text',
			'eval'                    => array('rgxp'=>'time', 'doNotCopy'=>true, 'tl_class'=>'w50'),
			'sql'                     => "int(10) unsigned NOT NULL default '0'"
		),

		'canUpdateBooking' => array
		(
			'label'                   => &$GLOBALS['TL_LANG']['tl_wem_planning']['canUpdateBooking'],
			'exclude'                 => true,
			'filter'                  => true,
			'flag'                    => 1,
			'inputType'               => 'checkbox',
			'eval'                    => array('doNotCopy'=>true, 'submitOnChange'=>true),
			'sql'                     => "char(1) NOT NULL default ''"
		),
		'canUpdateBookingUntil' => array
		(
			'label'                   => &$GLOBALS['TL_LANG']['tl_wem_planning']['canUpdateBookingUntil'],
			'exclude'                 => true,
			'inputType'               => 'text',
			'eval'                    => array('doNotCopy'=>true, 'tl_class'=>'w50'),
			'sql'                     => "int(10) unsigned NOT NULL default '0'"
		),
		'canUpdateBookingLimit' => array
		(
			'label'                   => &$GLOBALS['TL_LANG']['tl_wem_planning']['canUpdateBookingLimit'],
			'exclude'                 => true,
			'inputType'               => 'text',
			'eval'                    => array('doNotCopy'=>true, 'tl_class'=>'w50'),
			'sql'                     => "int(10) unsigned NOT NULL default '0'"
		),

		'canCancelBooking' => array
		(
			'label'                   => &$GLOBALS['TL_LANG']['tl_wem_planning']['canCancelBooking'],
			'exclude'                 => true,
			'filter'                  => true,
			'flag'                    => 1,
			'inputType'               => 'checkbox',
			'eval'                    => array('doNotCopy'=>true, 'submitOnChange'=>true),
			'sql'                     => "char(1) NOT NULL default ''"
		),
		'canCancelBookingUntil' => array
		(
			'label'                   => &$GLOBALS['TL_LANG']['tl_wem_planning']['canCancelBookingUntil'],
			'exclude'                 => true,
			'inputType'               => 'text',
			'eval'                    => array('doNotCopy'=>true, 'tl_class'=>'w50'),
			'sql'                     => "int(10) unsigned NOT NULL default '0'"
		),

		'bookingtypes' => array
		(
			'label'                 => &$GLOBALS['TL_LANG']['tl_wem_planning']['bookingtypes'],
		    'inputType'             => 'dcaWizard',
		    'foreignTable'          => 'tl_wem_planning_booking_type',
		    'foreignField'          => 'pid',
		    'params'                  => array
		    (
		        'do'                  => 'wem_planning',
		    ),
		    'eval'                  => array
		    (
		        'fields' => array('title', 'duration'),
		        'editButtonLabel' => $GLOBALS['TL_LANG']['tl_wem_planning']['edit_bookingtype'],
		        'applyButtonLabel' => $GLOBALS['TL_LANG']['tl_wem_planning']['apply_bookingtype'],
		        'orderField' => 'sorting',
		        'showOperations' => true,
		        'operations' => array('edit', 'delete'),
		        'tl_class'=>'clr',
		    ),
		),

		'slots' => array
		(
			'label'                 => &$GLOBALS['TL_LANG']['tl_wem_planning']['slots'],
		    'inputType'             => 'dcaWizard',
		    'foreignTable'          => 'tl_wem_planning_slot',
		    'foreignField'          => 'pid',
		    'params'                  => array
		    (
		        'do'                  => 'wem_planning',
		    ),
		    'eval'                  => array
		    (
		        'fields' => array('canBook', 'type'),
		        'editButtonLabel' => $GLOBALS['TL_LANG']['tl_wem_planning']['edit_slot'],
		        'applyButtonLabel' => $GLOBALS['TL_LANG']['tl_wem_planning']['apply_slot'],
		        'orderField' => 'sorting',
		        'showOperations' => true,
		        'operations' => array('edit', 'delete'),
		        'tl_class'=>'clr',
		    ),
		),

		'protected' => array
		(
			'label'                   => &$GLOBALS['TL_LANG']['tl_wem_planning']['protected'],
			'exclude'                 => true,
			'filter'                  => true,
			'inputType'               => 'checkbox',
			'eval'                    => array('submitOnChange'=>true),
			'sql'                     => "char(1) NOT NULL default ''"
		),
		'groups' => array
		(
			'label'                   => &$GLOBALS['TL_LANG']['tl_wem_planning']['groups'],
			'exclude'                 => true,
			'inputType'               => 'checkbox',
			'foreignKey'              => 'tl_member_group.name',
			'eval'                    => array('mandatory'=>true, 'multiple'=>true),
			'sql'                     => "blob NULL",
			'relation'                => array('type'=>'hasMany', 'load'=>'lazy')
		),
	)
);


/**
 * Provide miscellaneous methods that are used by the data configuration array.
 *
 * @author Web ex Machina <https://www.webexmachina.fr>
 */
class tl_wem_planning extends Backend
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
	 * Auto-generate the news alias if it has not been set yet
	 *
	 * @param mixed         $varValue
	 * @param DataContainer $dc
	 *
	 * @return string
	 *
	 * @throws Exception
	 */
	public function generateAlias($varValue, DataContainer $dc)
	{
		$autoAlias = false;

		// Generate alias if there is none
		if ($varValue == '')
		{
			$autoAlias = true;
			$varValue = StringUtil::generateAlias($dc->activeRecord->title);
		}

		$objAlias = $this->Database->prepare("SELECT id FROM tl_wem_planning WHERE alias=? AND id!=?")
								   ->execute($varValue, $dc->id);

		// Check whether the news alias exists
		if ($objAlias->numRows)
		{
			if (!$autoAlias)
			{
				throw new Exception(sprintf($GLOBALS['TL_LANG']['ERR']['aliasExists'], $varValue));
			}

			$varValue .= '-' . $dc->id;
		}

		return $varValue;
	}

	/**
	 * List a planning
	 *
	 * @param array $arrRow
	 *
	 * @return string
	 */
	public function listItems($arrRow)
	{
		$strHtml = '<div class="tl_content_left tl_wem_planning">';

		// Count drafted & confirmed booking
		$objPendingBooking = $this->Database->prepare('SELECT id FROM tl_wem_planning_booking WHERE pid=? AND status="pending"')->execute($arrRow['id']);
		$objConfirmedBooking = $this->Database->prepare('SELECT id FROM tl_wem_planning_booking WHERE pid=? AND status="confirmed"')->execute($arrRow['id']);
		
		$strHtml .= '<strong>'.$arrRow['title'].'</strong> ('.$objPendingBooking->count().' réservations à traiter et '.$objConfirmedBooking->count().' confirmées)';


		$strHtml .= '</div>';

		return $strHtml;
	}
}