<?php

namespace Events\API;

$guid = get_input('guid');
$event = get_entity($guid);
/* @var $event Event */

$ts = get_input('ts');

if (!$event || !$event->canEdit() || !$event->isRecurring() || !$event->isValidStartTime($ts)) {
	register_error(elgg_echo('events:error:invalid:guid'));
	forward(REFERER);
}

create_metadata($event->guid, 'cancelled_instance', $ts, '', $event->owner_guid, $event->access_id, true);

system_message(elgg_echo('events:cancel:success'));
forward($event->getURL());