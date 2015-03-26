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

$added = $removed = 0;
	
foreach ($all_calendars as $c) {
	if (!in_array($c->guid, $calendars)) {
		$c->removeEvent($event);
		$removed++;
	}
}
	
foreach ($calendars as $guid) {
	$calendar = get_entity($guid);
	if ($calendar instanceof Calendar && $calendar->canAddEvent()) {
		$calendar->addEvent($event);
		$added++;
	}
}

if ($added && $removed) {
	system_message(elgg_echo('events:calendars:addedremoved', array($added, $removed)));
}
elseif ($added) {
	system_message(elgg_echo('events:calendars:added', array($added)));
}
elseif ($removed) {
	system_message(elgg_echo('events:calendars:removed', array($removed)));
}

//what if we just orphaned an event?
if (!$event->getCalendars(array('count' => true))) {
	// nobody wants it - remove it
	$ia = elgg_set_ignore_access(true);
	$event->delete();
	elgg_set_ignore_access($ia);
}
	
forward(REFERER);