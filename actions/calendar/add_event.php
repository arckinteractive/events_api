<?php

namespace Events\API;

$user = elgg_get_logged_in_user_entity();

$event_guid = get_input('event_guid');
$event = get_entity($event_guid);

$calendar_guid = get_input('calendar_guid');
if ($calendar_guid) {
	$calendar = get_entity($calendar_guid);
} else {
	$calendar = Calendar::getPublicCalendar($user);
}

if (!$calendar instanceof Calendar || !$event instanceof Event) {
	register_error(elgg_echo('events:calendar:add_event:error:invalid_guid'));
	forward(REFERER);
}

if ($calendar->hasEvent($event)) {
	system_message(elgg_echo('events:calendar:add_event:already_on'));
	forward(REFERER);
}

if (!$calendar->canAddEvent()) {
	register_error(elgg_echo('events:calendar:add_event:error:noaccess'));
	forward(REFERER);
}

if ($calendar->addEvent($event)) {
	system_message(elgg_echo('events:calendar:add_event:success'));
} else {
	register_error(elgg_echo('events:calendar:add_event:error'));
}

forward(REFERRER);
