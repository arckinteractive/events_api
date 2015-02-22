<?php

namespace Events\API;

use Exception;

$guid = get_input('guid');
$event = get_entity($guid);

if (!$event instanceof Event || !$event->canEdit()) {
	register_error(elgg_echo('events:error:invalid:guid'));
	forward(REFERER);
}

try {
	$event->delete();
} catch (Exception $ex) {
	register_error($ex->getMessage());
	forward(REFERER);
}

system_message(elgg_echo('events:success:deleted'));

$calendar_guid = get_input('calendar_guid');
$calendar = get_entity($calendar_guid);
if ($calendar) {
	forward($calendar->getURL());
} else {
	forward('calendar');
}
