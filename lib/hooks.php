<?php

namespace Events\API;

/**
 * Determines who can add events to a calendar
 * We are using ElggObject::canWriteToContainer() to determine Calendar::canAddEvent(),
 * as there is no way to check relationship permissions
 * 
 * @param string $hook   "container_permissions_check"
 * @param string $type   "object"
 * @param bool   $return Permission
 * @param array  $params Hook params
 * @return bool
 */
function calendar_permissions($hook, $type, $return, $params) {

	$subtype = elgg_extract('subtype', $params);
	$calendar = elgg_extract('container', $params);
	$user = elgg_extract('user', $params);

	if (!$calendar instanceof Calendar || $subtype != Event::SUBTYPE) {
		return $return;
	}
	
	$container = $calendar->getContainerEntity();
	if (elgg_instanceof($container)) {
		// should allow group members to add events to a calendar if they can add events to a group
		return $container->canWriteToContainer($user->guid, 'object', Event::SUBTYPE);
	}
	
	return $return;
}