<?php

/**
 * Module Planning for Contao Open Source CMS
 *
 * Copyright (c) 2018 Web ex Machina
 *
 * @author Web ex Machina <https://www.webexmachina.fr>
 */

/**
 * Back end modules
 */
array_insert($GLOBALS['BE_MOD']['wem_plannings'], 1, array
(
	'wem_planning' => array
	(
		'tables'    => array('tl_wem_planning', 'tl_wem_planning_slot', 'tl_wem_planning_booking_type', 'tl_wem_planning_booking'),
		'icon'		=> 'system/modules/wem-contao-planning/assets/icon_planning.png'
	),
));

// Load icon in Contao 4.2 backend
if ('BE' === TL_MODE) {
    if (version_compare(VERSION, '4.4', '<')) {
        $GLOBALS['TL_CSS'][] = 'system/modules/wem-contao-planning/assets/backend.css';
    } else {
        $GLOBALS['TL_CSS'][] = 'system/modules/wem-contao-planning/assets/backend_svg.css';
    }
}