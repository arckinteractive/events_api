<?php

namespace Events\API;

$guid = get_input('guid');
$calendar_guid = get_input('calendar');
$calendar = get_entity($calendar_guid);

$event = get_entity($guid);
if (!elgg_instanceof($event, 'object', 'event')) {
	$event = new Event();
	$event->owner_guid = elgg_get_logged_in_user_guid();
	$event->container_guid = $calendar_guid; // contained by the original calendar
	$event->access_id = get_input('access_id', get_default_access());
}

$title = htmlspecialchars(get_input('title', elgg_echo('events:edit:title:placeholder')), ENT_QUOTES, 'UTF-8');;
$description = get_input('description');
$start_date = get_input('start_date');
$end_date = get_input('end_date', $start_date);
$start_time = get_input('start_time', '12:00am');
$end_time = get_input('end_time', '12:00am');
$all_day = get_input('all_day');
$repeat = get_input('repeat');
$repeat_end_after = get_input('repeat_end_after');
$repeat_end_on = get_input('repeat_end_on');
$repeat_frequency = get_input('repeat_frequency');
$repeat_end_type = get_input('repeat_end_type');

// sanity check - events must have a start date, and an end date, and they must end after they start
$start_timestamp = strtotime($start_date . ' ' . $start_time);
$end_timestamp = strtotime($end_date . ' ' . $end_time);

if ($start_timestamp === false || $end_timestamp === false) {
	// something was the wrong format
	register_error(elgg_echo('events:error:start_end_date:invalid_format'));
	forward($calendar->getURL());
}

if ($end_timestamp < $start_timestamp) {
	register_error(elgg_echo('events:error:start_end_date'));
	forward($calendar->getURL());
}

// lets attempt to create the event
$event->title = $title;
$event->description = $description;
$event->start_date = $start_date;
$event->end_date = $end_date;
$event->start_time = $start_time;
$event->end_time = $end_time;
$event->start_timestamp = $start_timestamp;
$event->end_timestamp = $end_timestamp;
$event->end_delta = $end_timestamp - $start_timestamp; // how long the event is in seconds
$event->all_day = $all_day ? 1 : 0;

// repeating data
$event->repeat = $repeat ? 1 : 0;
$event->repeat_end_after = (int) $repeat_end_after; // number of occurrances
$event->repeat_end_on = $repeat_end_on; // date YYYY-MM-DD that it ends on
$event->repeat_frequency = $repeat_frequency; // string identifying the repeating frequency
$event->repeat_end_type = $repeat_end_type; // how to determine how to end the repeat (never | occurrances | date)

// determine when it actually stops repeating in terms of timestamp
switch ($repeat_end_type) {
	case 'on':
		$repeat_end_timestamp = strtotime($repeat_end_on);
		if ($repeat_end_timestamp === false) {
			$repeat_end_timestamp = 0; //@TODO - what else could we do here?
		}
		break;
	case 'after':
		$params = array(
			'start' => $start_timestamp,
			'end' => $end_timestamp,
			'frequency' => $repeat_frequency,
			'after' => (int) $repeat_end_after,
			'delta' => $end_timestamp - $start_timestamp
		);
		$repeat_end_timestamp = get_repeat_end_timestamp($params);
		break;
	default:
		$repeat_end_timestamp = 0;
		break;
}

$event->repeat_end_timestamp = $repeat_end_timestamp;

if (!$event->save()) {
	register_error(elgg_echo('events:error:save'));
	forward($calendar->getURL());
}

$calendar->addEvent($event);

system_message(elgg_echo('events:success:save'));

forward($calendar->getURL());