<?php

$event = get_entity(get_input('guid'));

if (!elgg_instanceof($event, 'object', 'event') || !$event->canEdit()) {
	register_error(elgg_echo('events:error:invalid:guid'));
	forward(REFERER);
}

$calendar = $event->getContainerEntity();

$event->delete();

system_message(elgg_echo('events:success:deleted'));
forward($calendar->getURL());