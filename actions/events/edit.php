<?php

namespace Events\API;

use DateTime;
use DateTimeZone;

elgg_make_sticky_form('events/edit');

$user = elgg_get_logged_in_user_entity();

$guid = get_input('guid');
$event = get_entity($guid);

$container_guid = get_input('container_guid');
$container = get_entity($container_guid);
if (!$container) {
	$container = $user;
}

if (!$container->canWriteToContainer($user->guid, 'object', Event::SUBTYPE)) {
	register_error(elgg_echo('events:error:container_permissions'));
	forward(REFERER);
}

if (!get_input('title')) {
	register_error(elgg_echo('events:error:empty_title'));
	forward(REFERER);
}

$calendar_guid = get_input('calendar');
$calendar = get_entity($calendar_guid);

if (!$calendar instanceof Calendar) {
	$calendar = Calendar::getPublicCalendar($user);
}

$editing = true;
if (!$event instanceof Event) {
	$event = new Event();
	$event->owner_guid = $user->guid;
	$event->container_guid = $container->guid;
	
	$editing = false;
}

$title = htmlspecialchars(get_input('title', elgg_echo('events:edit:title:placeholder')), ENT_QUOTES, 'UTF-8');
$location = get_input('location');
$description = get_input('description');
$start_date = get_input('start_date');
$end_date = get_input('end_date', $start_date);
$timezone = get_input('timezone', Util::UTC);

$all_day = get_input('all_day');
if ($all_day) {
	// normalize so our queries produce valid results
	$start_time = '12:00am';
	$end_time = '11:59pm';
} else {
	$start_time = get_input('start_time', '12:00am');
	$start_time_ts = strtotime($start_time);
	$end_time = get_input('end_time', date('g:ia', $start_time + Util::SECONDS_IN_AN_HOUR));
}
$repeat = get_input('repeat', false);
$repeat_end_after = get_input('repeat_end_after');
$repeat_end_on = get_input('repeat_end_on');
$repeat_frequency = get_input('repeat_frequency');
$repeat_end_type = get_input('repeat_end_type');

// sanity check - events must have a start date, and an end date, and they must end after they start
$dt = new DateTime(null, new DateTimeZone($timezone));

$start_timestamp = $dt->modify("$start_date $start_time")->getTimestamp();
$start_timestamp_iso = $dt->format('c');

$end_timestamp = $dt->modify("$end_date $end_time")->getTimestamp();
$end_timestamp_iso = $dt->format('c');

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
$event->access_id = get_input('access_id', get_default_access());

$event->start_date = $start_date;
$event->end_date = $end_date;
$event->start_time = $start_time;
$event->end_time = $end_time;
$event->timezone = $timezone;

$event->start_timestamp = $start_timestamp;
$event->end_timestamp = $end_timestamp;

$event->start_timestamp_iso = $start_timestamp_iso;
$event->end_timestamp_iso = $end_timestamp_iso;

$event->end_delta = $end_timestamp - $start_timestamp; // how long the event is in seconds
$event->all_day = $all_day ? 1 : 0;

// repeating data
$event->repeat = ($repeat) ? 1 : 0;
$event->repeat_end_after = (int) $repeat_end_after; // number of occurrances
$event->repeat_end_on = $repeat_end_on; // date YYYY-MM-DD that it ends on
$event->repeat_frequency = ($repeat) ? $repeat_frequency : Util::FREQUENCY_ONCE; // string identifying the repeating frequency
$event->repeat_end_type = ($repeat) ? $repeat_end_type : Util::REPEAT_END_ONE_TIME; // how to determine how to end the repeat (never | occurrances | date)

unset($event->repeat_monthly_by);
unset($event->repeat_weekly_days);

switch ($event->repeat_frequency) {

	case Util::FREQUENCY_WEEKLY :
		$repeat_weekly_days = get_input('repeat_weekly_days');
		$repeat_weekly_days = (is_array($repeat_weekly_days)) ? $repeat_weekly_days : date('D', $event->getStartTimestamp());
		$event->repeat_weekly_days = $repeat_weekly_days;
		break;

	case Util::FREQUENCY_MONTHLY :
		$repeat_monthly_by = get_input('repeat_monthly_by', Util::REPEAT_MONTHLY_BY_DATE);
		$event->repeat_monthly_by = $repeat_monthly_by;
		break;
}

$event->repeat_end_timestamp = $event->calculateRepeatEndTimestamp();

if (!$event->save()) {
	register_error(elgg_echo('events:error:save'));
	forward($calendar->getURL());
}

$event->setLocation($location);

elgg_delete_metadata(array(
	'guids' => $event->guid,
	'metadata_names' => 'reminder',
	'limit' => 0,
));

$has_reminders = get_input('has_reminders');
$reminders = get_input('reminders', array());
if ($has_reminders && !empty($reminders)) {
	$size = count($reminders['value']) - 1; // last one is the template
	for ($i = 0; $i < $size; $i++) {
		$reminder_value = round($reminders['value'][$i]);
		switch ($reminders['increment'][$i]) {
			default :
			case 'minute' :
				$reminder_value *= Util::SECONDS_IN_A_MINUTE;
				break;
			case 'hour' :
				$reminder_value *= Util::SECONDS_IN_AN_HOUR;
				break;
			case 'day' :
				$reminder_value *= Util::SECONDS_IN_A_DAY;
				break;
		}
		create_metadata($event->guid, 'reminder', $reminder_value, '', $event->owner_guid, $event->access_id, true);
	}
	
	// rebuild reminders for the next 2 days
	$time = time();
	$event->removeReminders(null, null, true); // remove all reminders
	$event->buildReminders($time, $time + (Util::SECONDS_IN_A_DAY * 2));
}
else {
	unset($event->reminders); // in case of deleting reminder status
	$event->removeReminders(null, null, true); // remove all reminders
}

if (!$editing) {
	// if we're adding to the river we should provide a view
	add_to_river('river/object/event/create', 'create', $event->owner_guid, $event->guid);
}

elgg_clear_sticky_form('events/edit');

system_message(elgg_echo('events:success:save'));

if ($calendar instanceof Calendar) {
	$calendar->addEvent($event);
	forward($calendar->getURL());
} else {
	forward($event->getURL());
}