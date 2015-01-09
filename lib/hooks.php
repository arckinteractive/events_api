<?php

namespace Events\API;

/**
 * determines the who canEdit an event
 * if you can edit the container calendar, you can edit the event
 * 
 * @param type $h
 * @param type $t
 * @param type $r
 * @param type $p
 */
function event_permissions($h, $t, $r, $p) {
	$user = $p['user'];
	$event = $p['event'];
	
	if (!elgg_instanceof($event, 'object', 'event')) {
		return $r;
	}
	
	$container = $event->getContainerEntity();
	if (elgg_instanceof($container, 'object', 'calendar')) {
		return $container->canEdit($user->guid);
	}
	
	return $r;
}


/**
 * determines the who canEdit a calendar
 * if you can edit the container (user|group), you can edit the event
 * 
 * @param type $h
 * @param type $t
 * @param type $r
 * @param type $p
 */
function calendar_permissions($h, $t, $r, $p) {
	$user = $p['user'];
	$calendar = $p['entity'];
	
	if (!elgg_instanceof($calendar, 'object', 'calendar')) {
		return $r;
	}
	
	$container = $calendar->getContainerEntity();
	if (elgg_instanceof($container)) {
		// should allow group members to edit group calendar
		return $container->canWriteToContainer($user->guid);
	}
	
	return $r;
}