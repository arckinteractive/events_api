<?php

namespace Events\API;

$guid = get_input('guid');
$event = get_entity($guid);
$day_delta = get_input('day_delta', 0);
$minute_delta = get_input('minute_delta', 0);
$all_day = get_input('all_day', 0);

if (!elgg_instanceof($event, 'object', 'event')) {
	register_error(elgg_echo('events:error:invalid:guid'));
	forward(REFERER);
}

if (!$event->canEdit()) {
	register_error(elgg_echo('events:error:permissions'));
	forward(REFERER);
}

if (!is_numeric($day_delta) || !is_numeric($minute_delta)) {
	register_error(elgg_echo('events:error:invalid:deltas'));
	forward(REFERER);
}

$params = $event->getMoveParams($day_delta, $minute_delta, $all_day);

if (!elgg_trigger_event('events_api', 'event:move', $params)) {
	// it's expected that any return of false would provide their own error
	forward(REFERER);
}

$event->move($params);

system_message(elgg_echo('event_api:event:updated'));

forward(REFERER);