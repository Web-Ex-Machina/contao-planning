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
	'wem_booking' => array
	(
		'tables'    => array('tl_wem_planning_booking'),
		'icon'		=> 'system/modules/wem-contao-planning/assets/icon_planning.png'
	),
));

if ('BE' === TL_MODE) {
// Load icon in Contao 4.2 backend
$GLOBALS['TL_CSS'][] = 'https://maxcdn.bootstrapcdn.com/font-awesome/4.7.0/css/font-awesome.min.css';
    if (version_compare(VERSION, '4.4', '<')) {
        $GLOBALS['TL_CSS'][] = 'system/modules/wem-contao-planning/assets/backend.css';
    } else {
        $GLOBALS['TL_CSS'][] = 'system/modules/wem-contao-planning/assets/backend_svg.css';
    }
}

/**
 * Front end modules
 */
array_insert($GLOBALS['FE_MOD'], 2, array
(
	'wem_planning' => array
	(
		'wem_display_planning' 		=> 'WEM\Planning\Module\DisplayPlanning',
	)
));

/**
 * Models
 */
$GLOBALS['TL_MODELS'][\WEM\Planning\Model\Booking::getTable()] = 'WEM\Planning\Model\Booking';
$GLOBALS['TL_MODELS'][\WEM\Planning\Model\BookingType::getTable()] = 'WEM\Planning\Model\BookingType';
$GLOBALS['TL_MODELS'][\WEM\Planning\Model\Planning::getTable()] = 'WEM\Planning\Model\Planning';
$GLOBALS['TL_MODELS'][\WEM\Planning\Model\Slot::getTable()] = 'WEM\Planning\Model\Slot';

/**
 * Cronjobs
 */
$GLOBALS['TL_CRON']['daily'][] = array('WEM\Planning\Cronjobs', 'sendDailyBookings');

/**
 * Notification Center Notification Types
 */
$GLOBALS['NOTIFICATION_CENTER']['NOTIFICATION_TYPE'] = array_merge_recursive(
	(array) $GLOBALS['NOTIFICATION_CENTER']['NOTIFICATION_TYPE'],
	array(
		'wem_planning' => array(
			'confirm_booking' => array(
				'recipients'           => array('user_email'),
				'email_subject'        => array('booking_*'),
				'email_text'           => array('booking_*', 'bookingType_*', 'updateLink', 'cancelLink', 'adminLink'),
				'email_html'           => array('booking_*', 'bookingType_*', 'updateLink', 'cancelLink', 'adminLink'),
				'email_replyTo'        => array('user_email'),
			),
			'update_booking' => array(
				'recipients'           => array('user_email'),
				'email_subject'        => array('booking_*'),
				'email_text'           => array('booking_*', 'bookingType_*', 'updateLink', 'cancelLink', 'adminLink'),
				'email_html'           => array('booking_*', 'bookingType_*', 'updateLink', 'cancelLink', 'adminLink'),
				'email_replyTo'        => array('user_email'),
			),
			'cancel_booking' => array(
				'recipients'           => array('user_email'),
				'email_subject'        => array('booking_*'),
				'email_text'           => array('booking_*', 'bookingType_*', 'adminLink'),
				'email_html'           => array('booking_*', 'bookingType_*', 'adminLink'),
				'email_replyTo'        => array('user_email'),
			),
			'daily_bookings' => array(
				'recipients'           => array('admin_email'),
				'email_subject'        => array('date'),
				'email_text'           => array('bookings'),
				'email_html'           => array('bookings'),
				'attachment_tokens'    => array('bookings_file'),
			),
		)
	)
);