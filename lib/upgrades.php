<?php

use Events\API\Calendar;
use Events\API\Event;

// run activate.php
include_once dirname(dirname(__FILE__)) . '/activate.php';

//elgg_get_plugin_setting('upgrade_version', 'events_api');
run_function_once('events_api_21050312');
run_function_once('events_api_21061027');

/**
 * Generates random access tokens for existing calendars
 * @return void
 */
function events_api_21050312() {

	$batch = new ElggBatch('elgg_get_entities', array(
		'types' => 'object',
		'subtypes' => Calendar::SUBTYPE,
		'limit' => 0,
	));

	foreach ($batch as $cal) {
		$cal->save();
	}
}

/**
 * Adds all events to site calendar
 * @return void
 */
function events_api_21061027() {

	$ha = access_get_show_hidden_status();
	access_show_hidden_entities(true);

	$site = elgg_get_site_entity();
	$calendar = Calendar::getPublicCalendar($site);
	$events = new ElggBatch('elgg_get_entities', [
		'types' => 'object',
		'subtypes' => Event::SUBTYPE,
		'limit' => 0,
	]);

	foreach ($events as $event) {
		$calendar->addEvent($event);
	}

	access_show_hidden_entities($ha);
}