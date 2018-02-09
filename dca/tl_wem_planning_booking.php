<?php

/**
 * Module Planning for Contao Open Source CMS
 *
 * Copyright (c) 2018 Web ex Machina
 *
 * @author Web ex Machina <https://www.webexmachina.fr>
 */

/**
 * Table tl_wem_planning_booking
 */
$GLOBALS['TL_DCA']['tl_wem_planning_booking'] = array
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
			'fields'                  => array('date DESC'),
			'headerFields'            => array('title', 'tstamp'),
			'panelLayout'             => 'filter;sort,search,limit',
			'child_record_callback'   => array('tl_wem_planning_booking', 'listItems'),
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
				'label'               => &$GLOBALS['TL_LANG']['tl_wem_planning_booking']['edit'],
				'href'                => 'act=edit',
				'icon'                => 'edit.svg'
			),
			'copy' => array
			(
				'label'               => &$GLOBALS['TL_LANG']['tl_wem_planning_booking']['copy'],
				'href'                => 'act=paste&amp;mode=copy',
				'icon'                => 'copy.svg'
			),
			'cut' => array
			(
				'label'               => &$GLOBALS['TL_LANG']['tl_wem_planning_booking']['cut'],
				'href'                => 'act=paste&amp;mode=cut',
				'icon'                => 'cut.svg'
			),
			'delete' => array
			(
				'label'               => &$GLOBALS['TL_LANG']['tl_wem_planning_booking']['delete'],
				'href'                => 'act=delete',
				'icon'                => 'delete.svg',
				'attributes'          => 'onclick="if(!confirm(\'' . $GLOBALS['TL_LANG']['MSC']['deleteConfirm'] . '\'))return false;Backend.getScrollOffset()"'
			),
			'show' => array
			(
				'label'               => &$GLOBALS['TL_LANG']['tl_wem_planning_booking']['show'],
				'href'                => 'act=show',
				'icon'                => 'show.svg'
			)
		)
	),

	// Palettes
	'palettes' => array
	(
		'__selector__'				  => array('isUpdate'),
		'default'                     => '
			{global_legend},date,bookingType,lastname,firstname,phone,email,comments;
			{status_legend},status;
			{source_legend},isUpdate
		'
	),

	// Subpalettes
	'subpalettes' => array
	(
		'isUpdate'			  		  => 'bookingSrc',
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
		'pid' => array
		(
			'foreignKey'              => 'tl_wem_planning.title',
			'sql'                     => "int(10) unsigned NOT NULL default '0'",
			'relation'                => array('type'=>'belongsTo', 'load'=>'eager')
		),

		'date' => array
		(
			'label'                   => &$GLOBALS['TL_LANG']['tl_wem_planning_booking']['date'],
			'default'                 => time(),
			'exclude'                 => true,
			'filter'                  => true,
			'sorting'                 => true,
			'flag'                    => 8,
			'inputType'               => 'text',
			'eval'                    => array('rgxp'=>'datim', 'doNotCopy'=>true, 'datepicker'=>true, 'tl_class'=>'w50 wizard'),
			'sql'                     => "int(10) unsigned NOT NULL default '0'"
		),
		'bookingType' => array
		(
			'label'                   => &$GLOBALS['TL_LANG']['tl_wem_planning_booking']['bookingType'],
			'exclude'                 => true,
			'filter'                  => true,
			'flag'                    => 1,
			'inputType'               => 'select',
			'eval'                    => array('chosen'=>true, 'mandatory'=>true, 'tl_class'=>'w50'),
			'foreignKey'              => 'tl_wem_planning_booking_type.title',
			'sql'                     => "int(10) unsigned NOT NULL default '0'"
		),
		'lastname' => array
		(
			'label'                   => &$GLOBALS['TL_LANG']['tl_wem_planning_booking']['lastname'],
			'exclude'                 => true,
			'search'                  => true,
			'inputType'               => 'text',
			'eval'                    => array('mandatory'=>true, 'maxlength'=>255, 'tl_class'=>'w50'),
			'sql'                     => "varchar(255) NOT NULL default ''"
		),
		'firstname' => array
		(
			'label'                   => &$GLOBALS['TL_LANG']['tl_wem_planning_booking']['firstname'],
			'exclude'                 => true,
			'search'                  => true,
			'inputType'               => 'text',
			'eval'                    => array('mandatory'=>true, 'maxlength'=>255, 'tl_class'=>'w50'),
			'sql'                     => "varchar(255) NOT NULL default ''"
		),
		'phone' => array
		(
			'label'                   => &$GLOBALS['TL_LANG']['tl_wem_planning_booking']['phone'],
			'exclude'                 => true,
			'search'                  => true,
			'inputType'               => 'text',
			'eval'                    => array('maxlength'=>64, 'rgxp'=>'phone', 'decodeEntities'=>true, 'tl_class'=>'w50'),
			'sql'                     => "varchar(64) NOT NULL default ''"
		),
		'email' => array
		(
			'label'                   => &$GLOBALS['TL_LANG']['tl_wem_planning_booking']['email'],
			'exclude'                 => true,
			'search'                  => true,
			'inputType'               => 'text',
			'eval'                    => array('mandatory'=>true, 'maxlength'=>255, 'rgxp'=>'email', 'tl_class'=>'w50'),
			'sql'                     => "varchar(255) NOT NULL default ''"
		),
		'comments' => array
		(
			'label'                   => &$GLOBALS['TL_LANG']['tl_wem_planning_booking']['comments'],
			'exclude'                 => true,
			'search'                  => true,
			'inputType'               => 'textarea',
			'eval'                    => array('rte'=>'tinyMCE', 'tl_class'=>'clr'),
			'sql'                     => "text NULL"
		),

		'status' => array
		(
			'label'                   => &$GLOBALS['TL_LANG']['tl_wem_planning_booking']['status'],
			'default'                 => 'drafted',
			'exclude'                 => true,
			'filter'                  => true,
			'flag'                    => 1,
			'inputType'               => 'select',
			'options'        		  => array('drafted', 'confirmed', 'denied', 'done', 'canceled'),
			'reference'				  => &$GLOBALS['TL_LANG']['tl_wem_planning_booking']['status'],
			'eval'                    => array('chosen'=>true, 'mandatory'=>true),
			'sql'                     => "varchar(32) NOT NULL default ''"
		),

		'isUpdate' => array
		(
			'label'                   => &$GLOBALS['TL_LANG']['tl_wem_planning_booking']['isUpdate'],
			'exclude'                 => true,
			'filter'                  => true,
			'flag'                    => 1,
			'inputType'               => 'checkbox',
			'eval'                    => array('doNotCopy'=>true, 'submitOnChange'=>true),
			'sql'                     => "char(1) NOT NULL default ''"
		),
		'bookingSrc' => array
		(
			'label'                   => &$GLOBALS['TL_LANG']['tl_wem_planning_booking']['bookingSrc'],
			'exclude'                 => true,
			'inputType'               => 'select',
			'eval'                    => array('chosen'=>true, 'mandatory'=>true),
			'foreignKey'              => 'tl_wem_planning_booking.lastname',
			'sql'                     => "int(10) unsigned NOT NULL default '0'"
		),

		'token' => array
		(
			'eval'                    => array('doNotCopy'=>true),
			'sql'                     => "varchar(255) NOT NULL default ''"
		)
	)
);

/**
 * Display a legend for webmasters
 */
if(Input::get('table') == 'tl_wem_planning_booking')
{
	$strBookingStatusLegend = '
		<div class="tl_wem_planning_booking legend">
			<div class="item"><i class="drafted fa fa-pencil"></i> '.$GLOBALS['TL_LANG']['tl_wem_planning_booking']['status']['drafted'].'</div>
			<div class="item"><i class="confirmed fa fa-check"></i> '.$GLOBALS['TL_LANG']['tl_wem_planning_booking']['status']['confirmed'].'</div>
			<div class="item"><i class="denied fa fa-ban"></i> '.$GLOBALS['TL_LANG']['tl_wem_planning_booking']['status']['denied'].'</div>
			<div class="item"><i class="done fa fa-thumbs-up"></i> '.$GLOBALS['TL_LANG']['tl_wem_planning_booking']['status']['done'].'</div>
			<div class="item"><i class="canceled fa fa-undo"></i> '.$GLOBALS['TL_LANG']['tl_wem_planning_booking']['status']['canceled'].'</div>
		</div>
	';
	\Message::addRaw($strBookingStatusLegend);
}

/**
 * Provide miscellaneous methods that are used by the data configuration array.
 *
 * @author Web ex Machina <https://www.webexmachina.fr>
 */
class tl_wem_planning_booking extends Backend
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
	 * List a booking type
	 *
	 * @param array $arrRow
	 *
	 * @return string
	 */
	public function listItems($arrRow)
	{
		$objBookingType = $this->Database->prepare("SELECT * FROM tl_wem_planning_booking_type WHERE id=?")->limit(1)->execute($arrRow["bookingType"]);

		$strHtml = '<div class="tl_content_left tl_wem_planning_booking '.$arrRow['status'].'">';

		switch($arrRow['status'])
		{
			case 'drafted': 	$strHtml .= '<i class="'.$arrRow['status'].' fa fa-pencil"></i>'; break;
			case 'confirmed': 	$strHtml .= '<i class="'.$arrRow['status'].' fa fa-check"></i>'; break;
			case 'denied': 		$strHtml .= '<i class="'.$arrRow['status'].' fa fa-ban"></i>'; break;
			case 'done': 		$strHtml .= '<i class="'.$arrRow['status'].' fa fa-thumbs-up"></i>'; break;
			case 'canceled': 	$strHtml .= '<i class="'.$arrRow['status'].' fa fa-undo"></i>'; break;
		}
		
		$strHtml .= '<strong>Réservation du '.Date::parse(Config::get('datimFormat'), $arrRow['date']).' : '.$GLOBALS['TL_LANG']['tl_wem_planning_booking']['status'][$arrRow['status']].'</strong>';

		$strHtml .= '<br />'.$arrRow['firstname'].' '.$arrRow['lastname'].' pour '.$objBookingType->title.' ('.$objBookingType->duration.'h)';

		$strHtml .= '</div>';

		return $strHtml;
	}
}