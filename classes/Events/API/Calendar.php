<?php

namespace Events\API;
use ElggObject;
use ElggBatch;

class Calendar extends ElggObject {

	protected function initializeAttributes() {
		parent::initializeAttributes();

		$this->attributes['subtype'] = "calendar";
	}

	// gets all events
	// note - does not sort events
	public function getAllEvents($starttime, $endtime) {
		$non_repeating = $this->getNoneRepeatingEvents($starttime, $endtime);
		$repeating = $this->getRepeatingEvents($starttime, $endtime);
		
		$all_events = array();
		foreach ($non_repeating as $e) {
			$all_events[] = $e;
		}
		
		foreach ($repeating as $er) {
			$all_events[] = $er;
		}
		
		return $all_events;
	}
	
	public function getRepeatingEvents($starttime, $endtime) {
		
		$dbprefix = elgg_get_config('dbprefix');

		// for performance we'll denormalize metastrings first
		$mdr_name = add_metastring('repeat');
		$mdr_val = add_metastring(1);
		$mds_name = add_metastring('start_timestamp');
		$mde_name = add_metastring('end_timestamp');
		$mdre_name = add_metastring('repeat_end_timestamp');
		$options = array(
			'type' => 'object',
			'subtype' => 'event',
			'relationship' => EVENT_CALENDAR_RELATIONSHIP,
			'relationship_guid' => $this->guid,
			'inverse_relationship' => true,
			'joins' => array(
				"JOIN {$dbprefix}metadata mdr ON mdr.entity_guid = e.guid", // repeating metadata
				"JOIN {$dbprefix}metadata mds ON mds.entity_guid = e.guid", // start time metadata
				"JOIN {$dbprefix}metastrings mss ON mss.id = mds.value_id",	// start time metastring
				"JOIN {$dbprefix}metadata mde ON mde.entity_guid = e.guid",	// end time metadata
				"JOIN {$dbprefix}metastrings mse ON mse.id = mde.value_id",	// end time metastring
				"JOIN {$dbprefix}metadata mdre ON mdre.entity_guid = e.guid", // repeat end time metadata
				"JOIN {$dbprefix}metastrings msre ON msre.id = mdre.value_id" // repeat end time metastring
			),
			'wheres' => array(
				"mdr.name_id = {$mdr_name} AND mdr.value_id = {$mdr_val}",
				"mds.name_id = {$mds_name}",
				"mde.name_id = {$mde_name}",
				"mdre.name_id = {$mdre_name}",
						
				// event start is before our endtime AND (repeat end is after starttime, or there is no repeat end)
				"((CAST(mss.string AS SIGNED) < {$endtime}) AND (CAST(msre.string AS SIGNED) > {$starttime} OR CAST(msre.string AS SIGNED) = 0))"
			),
			'limit' => false
		);

		$batch = new ElggBatch('elgg_get_entities_from_relationship', $options);
		
		// these entities may not actually show up in our range yet, need to filter
		$repeatingEvents = array();
		foreach ($batch as $b) {
			if ($b->getStartTimes($starttime, $endtime)) {
				// hey, we have a hit!
				$repeatingEvents[] = $b;
			}
		}
		
		return $repeatingEvents;
	}

	public function getNoneRepeatingEvents($starttime, $endtime) {

		$dbprefix = elgg_get_config('dbprefix');
		
		// for performance we'll denormalize metastrings first
		$mdr_name = add_metastring('repeat');
		$mdr_val = add_metastring(0);
		$mds_name = add_metastring('start_timestamp');
		$mde_name = add_metastring('end_timestamp');
		$options = array(
			'type' => 'object',
			'subtype' => 'event',
			'relationship' => EVENT_CALENDAR_RELATIONSHIP,
			'relationship_guid' => $this->guid,
			'inverse_relationship' => true,
			'joins' => array(
				"JOIN {$dbprefix}metadata mdr ON mdr.entity_guid = e.guid", // repeating metadata
				"JOIN {$dbprefix}metadata mds ON mds.entity_guid = e.guid", // start time metadata
				"JOIN {$dbprefix}metastrings mss ON mss.id = mds.value_id",	// start time metastring
				"JOIN {$dbprefix}metadata mde ON mde.entity_guid = e.guid",	// end time metadata
				"JOIN {$dbprefix}metastrings mse ON mse.id = mde.value_id"	// end time metastring
			),
			'wheres' => array(
				"mdr.name_id = {$mdr_name} AND mdr.value_id = {$mdr_val}",
				"mds.name_id = {$mds_name}",
				"mde.name_id = {$mde_name}",
				"((CAST(mss.string AS SIGNED) BETWEEN {$starttime} AND {$endtime}) OR (CAST(mse.string AS SIGNED) BETWEEN {$starttime} AND {$endtime}))"
			),
			'limit' => false
		);
		
		$batch = new ElggBatch('elgg_get_entities_from_relationship', $options);
		
		return $batch;
	}

	public function addEvent($event) {
		add_entity_relationship($event->guid, EVENT_CALENDAR_RELATIONSHIP, $this->guid);
	}

	public function removeEvent($event) {
		remove_entity_relationship($event->guid, EVENT_CALENDAR_RELATIONSHIP, $this->guid);
	}

}
