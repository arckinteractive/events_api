<?php

namespace Events\API;
use ElggObject;
use ElggBatch;

class Calendar extends ElggObject {

	protected function initializeAttributes() {
		parent::initializeAttributes();

		$this->attributes['subtype'] = "calendar";
	}

	public function getAllEvents($starttime, $endtime) {
		
	}

	public function getRepeatingEvents($starttime, $endtime) {
		
	}
	
	public function getAllRepeatingEvents($starttime, $endtime) {
		$mnvp = array();

		$end_mnvp = array(
			'name' => 'start_timestamp',
			'value' => $endtime,
			'operand' => '<'
		);

		$mnvp[] = $end_mnvp;

		$mnvp[] = array(
			'name' => 'repeat',
			'values' => array(1) // get only non-repeating first
		);

		$options = array(
			'type' => 'object',
			'subtype' => 'event',
			'relationship' => EVENT_CALENDAR_RELATIONSHIP,
			'relationship_guid' => $this->guid,
			'inverse_relationship' => true,
			'metadata_name_value_pairs' => $mnvp,
			'limit' => false
		);
		
		$batch = new ElggBatch('elgg_get_entities_from_relationship', $options);
		
		return $batch;
	}

	public function getNoneRepeatingEvents($starttime, $endtime) {
		$mnvp = array();
		$mnvp[] = array(
			'name' => 'start_timestamp',
			'value' => $starttime,
			'operand' => '>'
		);

		$end_mnvp = array(
			'name' => 'start_timestamp',
			'value' => $endtime,
			'operand' => '<'
		);

		$mnvp[] = $end_mnvp;

		$mnvp[] = array(
			'name' => 'repeat',
			'values' => array(0) // get only non-repeating first
		);

		$options = array(
			'type' => 'object',
			'subtype' => 'event',
			'relationship' => EVENT_CALENDAR_RELATIONSHIP,
			'relationship_guid' => $this->guid,
			'inverse_relationship' => true,
			'metadata_name_value_pairs' => $mnvp,
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
