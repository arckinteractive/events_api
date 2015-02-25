<?php

namespace Events\API;

use ElggBatch;
use ElggEntity;

/**
 * Clean up operations on calendar delete
 *
 * @param string     $event  "delete"
 * @param string     $type   "object"
 * @param ElggEntity $entity Entity being deleted
 */
function delete_event_handler($event, $type, $entity) {

	if ($entity instanceof Calendar) {

		// Do not allow users to delete publi calendars
		if ($entity->isPublicCalendar() && !elgg_is_admin_logged_in()) {
			register_error(elgg_echo('events:error:public_calendar_delete'));
			return false;
		}

		// Move all orphaned events to the public calendar
		$owner = $entity->getContainerEntity();
		$public_calendar = Calendar::getPublicCalendar($owner);
		if (!$public_calendar) {
			register_error(elgg_echo('events:error:no_public_for_orphans'));
			return false;
		}

		$dbprefix = elgg_get_config('dbprefix');
		$relationship_name = sanitize_string(Calendar::EVENT_CALENDAR_RELATIONSHIP);
		$calendar_subtype_id = (int) get_subtype_id('object', Calendar::SUBTYPE);

		// Get all events that do not appear on container's other calendars
		$events = new ElggBatch('elgg_get_entities_from_relationship', array(
			'types' => 'object',
			'subtypes' => Event::SUBTYPE,
			'relationship' => Calendar::EVENT_CALENDAR_RELATIONSHIP,
			'relationship_guid' => $entity->guid,
			'inverse_relationship' => true,
			'limit' => 0,
			'wheres' => array(
				"NOT EXISTS(SELECT * FROM {$dbprefix}entity_relationships er2
					JOIN {$dbprefix}entities e2 ON er2.guid_two = e2.guid
					WHERE er2.relationship = '$relationship_name'
						AND er2.guid_one = e.guid
						AND er2.guid_two != $entity->guid
						AND e2.container_guid = $entity->container_guid
						AND e2.type = 'object' AND e2.subtype = $calendar_subtype_id)"
			)
		));

		foreach ($events as $event) {
			/* @var Event $event */
			$public_calendar->addEvent($event);
		}
	}

	return true;
}
