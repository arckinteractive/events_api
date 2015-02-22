<?php

namespace Events\API;

$guid = get_input('guid');
$event = get_entity($guid);
$day_delta = get_input('day_delta', 0);
$minute_delta = get_input('minute_delta', 0);

if (!$event instanceof Event) {
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

$params = $event->getResizeParams($day_delta, $minute_delta);

if (!elgg_trigger_event('events_api', 'event:resize', $params)) {
	// it's expected that any return of false would provide their own error
	forward(REFERER);
}

$event->resize($params);

system_message(elgg_echo('event_api:event:updated'));

forward(REFERER);