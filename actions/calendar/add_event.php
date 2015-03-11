<?php

namespace Events\API;

$user = elgg_get_logged_in_user_entity();

$event_guid = get_input('event_guid');
$event = get_entity($event_guid);

$calendars = (array)get_input('calendars');

$all_calendars = Calendar::getCalendars(elgg_get_logged_in_user_entity());
if (!$all_calendars) {
	$default_calendar = Calendar::getPublicCalendar(elgg_get_logged_in_user_entity());
	$all_calendars = array($default_calendar);
}
	
foreach ($all_calendars as $c) {
	if (!in_array($c->guid, $calendars)) {
		$c->removeEvent($event);
	}
}
	
foreach ($calendars as $guid) {
	$calendar = get_entity($guid);
	if ($calendar instanceof Calendar && $calendar->canAddEvent()) {
		$calendar->addEvent($event);
	}
}

system_message(elgg_echo('events:calendars:added'));

//what if we just orphaned an event?
if (!$event->getCalendars(array('count' => true))) {
	// add it back to the default calendar?
	$default_calendar = Calendar::getPublicCalendar($event->getContainerEntity());
	$default_calendar->addEvent($event);
	system_message(elgg_echo('events:calendars:orphan:added'));
}
	
forward(REFERER);