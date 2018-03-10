<?php

/**
 * Module Planning for Contao Open Source CMS
 *
 * Copyright (c) 2018 Web ex Machina
 *
 * @author Web ex Machina <https://www.webexmachina.fr>
 */

/**
 * Register the templates
 */
TemplateLoader::addFiles(array
(
	'mod_wem_display_planning' => 'system/modules/wem-contao-planning/templates/modules',
	'mod_wem_display_planning_update' => 'system/modules/wem-contao-planning/templates/modules',

	'wem_calendar_default' => 'system/modules/wem-contao-planning/templates/elements',
	'wem_calendar_booking_form' => 'system/modules/wem-contao-planning/templates/elements',
));