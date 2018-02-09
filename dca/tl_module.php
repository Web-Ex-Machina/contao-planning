<?php

/**
 * Module Planning for Contao Open Source CMS
 *
 * Copyright (c) 2018 Web ex Machina
 *
 * @author Web ex Machina <https://www.webexmachina.fr>
 */

$GLOBALS['TL_DCA']['tl_module']['palettes']['wem_display_planning']    = '{title_legend},name,headline,type;{config_legend},wem_planning;{template_legend:hide},customTpl;{protected_legend:hide},protected;{expert_legend:hide},guests,cssID';

$GLOBALS['TL_DCA']['tl_module']['fields']['wem_planning'] = array
(
	'label'                   => &$GLOBALS['TL_LANG']['tl_module']['wem_planning'],
	'default'                 => 'wem_portfolio_item',
	'exclude'                 => true,
	'inputType'               => 'select',
	'foreignKey'              => 'tl_wem_planning.title',
	'eval'                    => array('chosen'=>true, 'mandatory'=>true, 'tl_class'=>'w50'),
	'sql'                     => "int(10) unsigned NOT NULL default '0'"
);

/**
 * Provide miscellaneous methods that are used by the data configuration array.
 *
 * @author Web ex Machina <https://www.webexmachina.fr>
 */
class tl_module_wem_planning extends Backend
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
	 * Return all planning templates as array
	 *
	 * @return array
	 */
	public function getPlanningTemplates()
	{
		return $this->getTemplateGroup('wem_planning_');
	}
}