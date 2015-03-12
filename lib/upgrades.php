<?php

// run activate.php
include_once dirname(dirname(__FILE__)) . '/activate.php';

//elgg_get_plugin_setting('upgrade_version', 'events_api');
run_function_once('events_api_21050312');

/**
 * Generates random access tokens for existing calendars
 * @return void
 */
function events_api_21050312() {

	$batch = new \ElggBatch('elgg_get_entities', array(
		'types' => 'object',
		'subtypes' => \Events\API\Calendar::SUBTYPE,
		'limit' => 0,
	));

	foreach ($batch as $cal) {
		$cal->save();
	}
}