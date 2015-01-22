<?php

namespace Events\API;

const PLUGIN_ID = 'events_api';
const UPGRADE_VERSION = 20141215;
const EVENT_CALENDAR_RELATIONSHIP = 'on_calendar';

require_once __DIR__ . '/lib/hooks.php';
require_once __DIR__ . '/lib/functions.php';

elgg_register_event_handler('init', 'system', __NAMESPACE__ . '\\init');

function init() {
	elgg_register_class('Events\API\Calendar', __DIR__ . '/classes/Events/API/Calendar.php');
	elgg_register_class('Events\API\Event', __DIR__ . '/classes/Events/API/Event.php');
	
	elgg_register_plugin_hook_handler('permissions_check', 'object', __NAMESPACE__ . '\\events_permissions');
	elgg_register_plugin_hook_handler('permissions_check', 'object', __NAMESPACE__ . '\\calendar_permissions');
	
	elgg_register_entity_url_handler('object', 'calendar', __NAMESPACE__ . '\\calendar_url');
	elgg_register_entity_url_handler('object', 'event', __NAMESPACE__ . '\\event_url');
	
	elgg_register_action('events/edit', __DIR__ . '/actions/events/edit.php');
	elgg_register_action('events/move', __DIR__ . '/actions/events/move.php');
	elgg_register_action('events/resize', __DIR__ . '/actions/events/resize.php');
}


// assumes one calendar per container, but structure can be overwritten
function calendar_url($calendar) {
	return 'calendar/view/' . $calendar->container_guid;
}

function event_url($event) {
	return 'calendar/event/' . $event->guid;
}