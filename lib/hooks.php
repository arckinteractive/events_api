<?php

namespace Events\API;

/**
 * Determines who can add events to a calendar
 * We are using ElggObject::canWriteToContainer() to determine Calendar::canAddEvent(),
 * as there is no way to check relationship permissions
 * 
 * @param string $hook   "container_permissions_check"
 * @param string $type   "object"
 * @param bool   $return Permission
 * @param array  $params Hook params
 * @return bool
 */
function calendar_permissions($hook, $type, $return, $params) {

	$subtype = elgg_extract('subtype', $params);
	$calendar = elgg_extract('container', $params);
	$user = elgg_extract('user', $params);

	if (!$calendar instanceof Calendar || $subtype != Event::SUBTYPE) {
		return $return;
	}

	$container = $calendar->getContainerEntity();
	if (elgg_instanceof($container)) {
		// should allow group members to add events to a calendar if they can add events to a group
		return $container->canWriteToContainer($user->guid, 'object', Event::SUBTYPE);
	}

	return $return;
}

/**
 * Filters instance export values for ical properties
 *
 * @param string $hook   "export:instance"
 * @param string $type   "events_api"
 * @param array  $return Exported values
 * @param array  $params Hook params
 * @return array
 */
function export_ical_instance($hook, $type, $return, $params) {

	$instance = elgg_extract('instance', $params);
	$consumer = elgg_extract('consumer', $params);

	if (!$instance instanceof EventInstance) {
		return $return;
	}

	if ($consumer == 'ical') {
		$event = $instance->getEvent();
		$calendar = $instance->getCalendar();
		$return = array_filter(array(
			'dtstart' => $instance->getStart(),
			'dtend' => $instance->getEnd(),
			'class' => 'PUBLIC',
			'organizer' => $event->getHost()->name,
			'uid' => implode('-', array($event->guid, $instance->getStartTimestamp())),
			'url' => $event->getURL($instance->getStartTimestamp(), $calendar->guid),
			'location' => $event->getLocation(),
			'summary' => $event->getDisplayName(),
			'description' => strip_tags($event->description),
			'reminders' => (array) $event->reminder,
		));
	}

	return $return;
}


/**
 * called on daily cron
 * build reminder annotations ahead of when we need them
 * 
 * @param type $hook
 * @param type $type
 * @param type $return
 * @param type $params
 */
function daily_build_reminders($hook, $type, $return, $params) {
	// get the largest reminder offset
	// @TODO - what if someone sets a stupid reminder for a year or something?
	// we don't want to be calculating all of those every day...
	$longest_reminder = Util::getLongestReminder();
	$longest_lookahead = Util::getDayEnd(time() + $longest_reminder);
	$default_lookahead = time() + (Util::SECONDS_IN_A_DAY * 2);

	$reminder_lookahead = max(array($default_lookahead, $longest_lookahead));
	
	// get all events that are upcoming
	$events = Util::getAllEvents(time(), $reminder_lookahead);

	foreach ($events as $event) {
		$event->buildReminders(time(), $reminder_lookahead);
	}
}