<?php

namespace Events\API;

use Exception;

set_time_limit(0); // just in case there are too many orphaned events

$guid = get_input('guid');
$calendar = get_entity($guid);

if (!$calendar instanceof Calendar || !$calendar->canEdit()) {
	register_error(elgg_echo('events:calendar:error:invalid:guid'));
	forward(REFERER);
}

try {
	$container = $calendar->getContainerEntity();
	if ($calendar->delete()) {
		system_message(elgg_echo('events:calendar:delete:success'));
		forward("calendar/view/$container->guid");
	}
} catch (Exception $ex) {
	register_error($ex->getMessage());
}

register_error(elgg_echo('events:calendar:delete:error'));
forward(REFERER);
