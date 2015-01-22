<?php

namespace Events\API;

use ElggObject;

class Event extends ElggObject {

	protected function initializeAttributes() {
		parent::initializeAttributes();

		$this->attributes['subtype'] = "event";
	}

	/**
	 * Perform a move action with calculated parameters
	 * 
	 * @param type $params
	 */
	public function move($params) {
		//update the event
		$this->all_day = $params['all_day'];
		$this->start_timestamp = $params['new_start_timestamp'];
		$this->end_timestamp = $params['new_end_timestamp'];
		$this->start_date = $params['new_start_date'];
		$this->end_date = $params['new_end_date'];
		$this->start_time = $params['new_start_time'];
		$this->end_time = $params['new_end_time'];
		$this->end_delta = $params['new_end_timestamp'] - $params['new_start_timestamp']; // how long this is in seconds

		return true;
	}

	public function resize($params) {
		//update the event
		$this->end_timestamp = $params['new_end_timestamp'];
		$this->end_date = $params['new_end_date'];
		$this->end_time = $params['new_end_time'];
		$this->end_delta = $params['new_end_timestamp'] - $this->start_timestamp; // how long this is in seconds
		
		return true;
	}

	public function addToCalendar($calendar_guid) {
		$calendar = get_entity($calendar_guid);
		if (elgg_instanceof($calendar, 'object', 'calendar')) {
			$calendar->addEvent($this);
			return true;
		}

		return false;
	}

	public function removeFromCalendar($calendar_guid) {
		$calendar = get_entity($calendar_guid);
		if (elgg_instanceof($calendar, 'object', 'calendar')) {
			$calendar->removeEvent($this);
			return true;
		}

		return false;
	}

	public function isRecurring() {
		return (bool) $this->repeat;
	}

	/**
	 * calculate parameters for a move action
	 * 
	 * @param type $day_delta
	 * @param type $minute_delta
	 * @param type $all_day
	 * @return array
	 */
	public function getMoveParams($day_delta, $minute_delta, $all_day) {
		// calculate new dates
		$start_timestamp = $this->start_timestamp;
		$end_timestamp = $this->end_timestamp;

		$time_diff = $end_timestamp - $start_timestamp;
		$new_start_timestamp = $start_timestamp + (60 * 60 * 24 * $day_delta) + (60 * $minute_delta);
		$new_end_timestamp = $new_start_timestamp + $time_diff;

		$params = array(
			'entity' => $this,
			'new_start_timestamp' => $new_start_timestamp,
			'new_end_timestamp' => $new_end_timestamp,
			'new_start_date' => date('Y-m-d', $new_start_timestamp),
			'new_end_date' => date('Y-m-d', $new_end_timestamp),
			'new_start_time' => date('g:ia', $new_start_timestamp),
			'new_end_time' => date('g:ia', $new_end_timestamp),
			'all_day' => $all_day
		);

		return $params;
	}

	public function getResizeParams($day_delta, $minute_delta) {
		// calculate new dates
		$end_timestamp = $this->end_timestamp;

		$new_end_timestamp = $end_timestamp + (60 * 60 * 24 * $day_delta) + (60 * $minute_delta);


		$params = array(
			'entity' => $this,
			'new_end_timestamp' => $new_end_timestamp,
			'new_end_date' => date('Y-m-d', $new_end_timestamp),
			'new_end_time' => date('g:ia', $new_end_timestamp)
		);

		return $params;
	}

	/**
	 * returns an array of start times that a repeating event shows up on
	 * within the given timestamp range
	 * Note - assumes timestamp range is in increments of days
	 * 
	 * @return array
	 */
	public function getStartTimes($starttime, $endtime) {
		if (!$this->isRecurring()) {
			return array();
		}
		
		$return = array();
		
		// iterate through each day of our range and see if this event shows up on any of those days
		$time = $starttime;
		$day = 60*60*24;
		while ($time < $endtime) {
			switch ($this->repeat_frequency) {
				case 'daily':
				// make a timestamp with the start time on this day and each additional day
				if ($this->start_timestamp > $time) {
					continue;
				}
				
				if ($this->repeat_end_timestamp && $this->repeat_end_timestamp < $time) {
					continue;
				}
				
				$return[] = mktime(
								date('H', $this->start_timestamp),
								date('i', $this->start_timestamp),
								date('s', $this->start_timestamp),
								date('n', $time),
								date('j', $time),
								date('Y', $time)
							);
				break;
			}
			
			$time += $day;
		}
		return $return;
	}
}
