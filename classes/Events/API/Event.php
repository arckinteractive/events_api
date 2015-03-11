<?php

namespace Events\API;

use ElggObject;

/**
 * Event object
 *
 * @property integer $start_date
 * @property integer $end_date
 * @property integer $start_time
 * @property integer $end_time
 * @property integer $start_timestamp
 * @property integer $end_timestamp
 * @property integer $end_delta
 * @property bool    $all_day
 * @property bool    $repeat
 * @property integer $repeat_end_after
 * @property integer $repeat_end_on
 * @property string  $repeat_frequency
 * @property string  $repeat_end_type
 * @property string  $repeat_monthly_by
 * @property integer $repeat_end_timestamp
 * @property mixed   $repeat_weekly_days
 */
class Event extends ElggObject {

	const SUBTYPE = 'event';

	/**
	 * {@inheritdoc}
	 */
	protected function initializeAttributes() {
		parent::initializeAttributes();
		$this->attributes['subtype'] = self::SUBTYPE;
	}

	/**
	 * Returns events title
	 * @return string
	 */
	public function getDisplayName() {
		return ($this->title) ? : elgg_echo('events:edit:title:placeholder');
	}

	/**
	 * Returns canonical URL with instance start time added as query element
	 * 
	 * @param int $start_timestamp Start time of the instance
	 * @param int $calendar_guid   GUID of the calendar in context
	 * @return string
	 */
	public function getURL($start_timestamp = 0, $calendar_guid = 0) {
		if (!$start_timestamp) {
			$start_timestamp = $this->getNextOccurrence();
		}
		$url = parent::getURL();
		return elgg_http_add_url_query_elements($url, array_filter(array(
			'ts' => $start_timestamp,
			'calendar' => $calendar_guid,
		)));
	}

	/**
	 * Perform a move action with calculated parameters
	 * 
	 * @param array $params New event parameters
	 * @return boolean
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

	/**
	 * Extends event duration
	 *
	 * @param array $params New end parameters
	 * @return boolean
	 */
	public function resize($params) {
		//update the event
		$this->end_timestamp = $params['new_end_timestamp'];
		$this->end_date = $params['new_end_date'];
		$this->end_time = $params['new_end_time'];
		$this->end_delta = $params['new_end_timestamp'] - $this->start_timestamp; // how long this is in seconds

		return true;
	}

	/**
	 * Adds an event to a calendar
	 *
	 * @param int $calendar_guid GUID of the calendar
	 * @return boolean
	 */
	public function addToCalendar($calendar_guid) {
		$calendar = get_entity($calendar_guid);
		if ($calendar instanceof Calendar) {
			$calendar->addEvent($this);
			return true;
		}
		return false;
	}

	/**
	 * Removes an event from a calendar
	 *
	 * @param int $calendar_guid GUID of the calendar
	 * @return boolean
	 */
	public function removeFromCalendar($calendar_guid) {
		$calendar = get_entity($calendar_guid);
		if ($calendar instanceof Calendar) {
			$calendar->removeEvent($this);
			return true;
		}

		return false;
	}

	/**
	 * Checks if the event is recurring
	 * @return boolean
	 */
	public function isRecurring() {
		return (bool) $this->repeat;
	}

	/**
	 * Calculates parameters for a move action
	 * 
	 * @param int  $day_delta    Positive or negative number of days from the original event day
	 * @param int  $minute_delta Position or negative number of minutes from the origin event time
	 * @param bool $all_day      All day event?
	 * @return array
	 */
	public function getMoveParams($day_delta, $minute_delta, $all_day) {
		// calculate new dates
		$start_timestamp = $this->start_timestamp;
		$end_timestamp = $this->end_timestamp;

		$time_diff = $end_timestamp - $start_timestamp;
		$new_start_timestamp = $start_timestamp + ($day_delta * Util::SECONDS_IN_AN_HOUR * $day_delta) + ($minute_delta * Util::SECONDS_IN_A_MINUTE);
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

	/**
	 * Calculates parameters for the resize action
	 *
	 * @param int $day_delta    Positive or negative number of days from the original event end
	 * @param int $minute_delta Positive or negative number of minutes from the original event end
	 * @return array
	 */
	public function getResizeParams($day_delta = 0, $minute_delta = 0) {
		// calculate new dates
		$end_timestamp = $this->end_timestamp;

		$new_end_timestamp = $end_timestamp + ($day_delta * Util::SECONDS_IN_A_DAY) + ($minute_delta * Util::SECONDS_IN_A_MINUTE);

		$params = array(
			'entity' => $this,
			'new_end_timestamp' => $new_end_timestamp,
			'new_end_date' => date('Y-m-d', $new_end_timestamp),
			'new_end_time' => date('g:ia', $new_end_timestamp)
		);

		return $params;
	}

	/**
	 * Returns an array of start times for an event within a given timestamp range
	 * Note - assumes timestamp range is in increments of days
	 * 
	 * @return array
	 */
	public function getStartTimes($starttime, $endtime) {

		if (!$this->isRecurring()) {
			return array($this->start_timestamp);
		}

		$start_times = array();

		// 00:00:00 on the day of first event occurrence
		$start_day = (int) Util::getDayStart($this->start_timestamp);

		$test_day = $starttime;

		// iterate through each day of our range and see if this event shows up on any of those days
		while ($test_day < $endtime) {

			$shows = false;

			// normalize timestamp to beginning of day
			$test_day = (int) Util::getDayStart($test_day);

			// next increment
			$next_test_day = $test_day + Util::SECONDS_IN_A_DAY;

			// event has no more occurrences after this day
			if ($this->repeat_end_timestamp && $this->repeat_end_timestamp < $test_day) {
				break;
			}

			// event repetitions will start in the future from this day
			if ($start_day > $test_day) {
				$test_day = $next_test_day;
				continue;
			}

			switch ($this->repeat_frequency) {
				case Util::FREQUENCY_DAILY:
					$shows = true;
					break;

				case Util::FREQUENCY_WEEKDAY:
					$D = Util::getDayOfWeek($test_day);
					$shows = !in_array($D, array(Util::SATURDAY, Util::SUNDAY));
					break;

				case Util::FREQUENCY_WEEKDAY_ODD:
					$D = Util::getDayOfWeek($test_day);
					$shows = in_array($D, array(Util::MONDAY, Util::WEDNESDAY, Util::FRIDAY));
					break;

				case Util::FREQUENCY_WEEKDAY_EVEN:
					$D = Util::getDayOfWeek($test_day);
					$shows = in_array($D, array(Util::TUESDAY, Util::THURSDAY));
					break;

				case Util::FREQUENCY_WEEKLY:
					$repeat_weekly_days = $this->repeat_weekly_days;
					if (!$repeat_weekly_days) {
						$repeat_weekly_days = Util::getDayOfWeek($this->start_timestamp);
					}
					if (!is_array($repeat_weekly_days)) {
						$repeat_weekly_days = array($repeat_weekly_days);
					}
					$D = Util::getDayOfWeek($test_day);
					$shows = in_array($D, $repeat_weekly_days);
					break;

				case Util::FREQUENCY_MONTHLY:
					if ($this->repeat_monthly_by == Util::REPEAT_MONTHLY_BY_DAY_OF_WEEK) {
						$shows = Util::isOnSameWeekDayOfMonth($test_day, $this->start_timestamp);
						if ($shows) {
							// we can skip 4 weeks
							$next_test_day = strtotime('+28 days', $test_day);
						}
					} else {
						$shows = Util::isOnSameDayOfMonth($test_day, $this->start_timestamp);
						if ($shows) {
							// we can skip a month
							$next_test_day = strtotime('+1 month', $test_day);
						}
					}
					break;

				case Util::FREQUENCY_YEARLY:
					$shows = Util::isOnSameDayOfYear($test_day, $this->start_timestamp);
					if ($shows) {
						// we can skip a year
						$next_test_day = strtotime('+1 year', $test_day);
					}
					break;
			}

			if ($shows) {
				$occurrence = (int) Util::getTimeOfDay($this->start_timestamp, $test_day);
				array_push($start_times, $occurrence);
			}

			$test_day = $next_test_day;
		}
		return $start_times;
	}

	/**
	 * Calculates and returns the last timestamp for event recurrences
	 * @return int
	 */
	public function calculateRepeatEndTimestamp() {

		// determine when it actually stops repeating in terms of timestamp
		switch ($this->repeat_end_type) {

			case Util::REPEAT_END_ON:
				$repeat_end_timestamp = strtotime($this->repeat_end_on);
				if ($repeat_end_timestamp === false) {
					$repeat_end_timestamp = 0; //@TODO - what else could we do here?
				}
				return $repeat_end_timestamp;

			case Util::REPEAT_END_AFTER:
				return $this->calculateEndAfterTimestamp($this->repeat_end_after);

			case Util::REPEAT_END_NEVER :
				return 0;

			default :
				if ($this->repeat) {
					return 0;
				}
				return $this->start_timestamp;
		}
	}

	/**
	 * Calculates the end (or start) timestamp of the last event in a sequence of occurrences
	 *
	 * @param int  $occurrences    Max number of occurrences
	 * @param int  $from_timestamp Initial time to calculate from (defaults to event start time)
	 * @param bool $at_event_end   If true, will return the timestamp of the event end, otherwise event start
	 * @return int
	 */
	public function calculateEndAfterTimestamp($occurrences = 1, $from_timestamp = null, $at_event_end = true) {

		$occurrences = (int) $occurrences;

		$start_timestamp = $this->start_timestamp;
		$start_day = (int) Util::getDayStart($start_timestamp);
		$test_day = ($from_timestamp) ? $from_timestamp : $start_timestamp;

		while ($occurrences > 0) {

			$shows = false;

			// normalize timestamp to beginning of day
			$test_day = (int) Util::getDayStart($test_day);

			// next increment
			$next_test_day = $test_day + Util::SECONDS_IN_A_DAY;

			// event repetitions will start in the future from this day
			if ($start_day > $test_day) {
				$test_day = $next_test_day;
				continue;
			}

			switch ($this->repeat_frequency) {
				default:
					$occurrences = 0;
					break;
					
				case Util::FREQUENCY_DAILY:
					$shows = true;
					break;

				case Util::FREQUENCY_WEEKDAY:
					$D = Util::getDayOfWeek($test_day);
					$shows = !in_array($D, array(Util::SATURDAY, Util::SUNDAY));
					break;

				case Util::FREQUENCY_WEEKDAY_ODD:
					$D = Util::getDayOfWeek($test_day);
					$shows = in_array($D, array(Util::MONDAY, Util::WEDNESDAY, Util::FRIDAY));
					break;

				case Util::FREQUENCY_WEEKDAY_EVEN:
					$D = Util::getDayOfWeek($test_day);
					$shows = in_array($D, array(Util::TUESDAY, Util::THURSDAY));
					break;

				case Util::FREQUENCY_WEEKLY:
					$repeat_weekly_days = $this->repeat_weekly_days;
					if (!$repeat_weekly_days) {
						$repeat_weekly_days = Util::getDayOfWeek($this->start_timestamp);
					}
					if (!is_array($repeat_weekly_days)) {
						$repeat_weekly_days = array($repeat_weekly_days);
					}
					$D = Util::getDayOfWeek($test_day);
					$shows = in_array($D, $repeat_weekly_days);
					break;

				case Util::FREQUENCY_MONTHLY:
					if ($this->repeat_monthly_by == Util::REPEAT_MONTHLY_BY_DAY_OF_WEEK) {
						$shows = Util::isOnSameWeekDayOfMonth($test_day, $this->start_timestamp);
						if ($shows) {
							// we can skip 4 weeks
							$next_test_day = strtotime('+28 days', $test_day);
						}
					} else {
						$shows = Util::isOnSameDayOfMonth($test_day, $this->start_timestamp);
						if ($shows) {
							// we can skip a month
							$next_test_day = strtotime('+1 month', $test_day);
						}
					}
					break;

				case Util::FREQUENCY_YEARLY:
					$shows = Util::isOnSameDayOfYear($test_day, $this->start_timestamp);
					if ($shows) {
						// we can skip a year
						$next_test_day = strtotime('+1 year', $test_day);
					}
					break;
			}

			if ($shows) {
				$occurrences--;
				$start_timestamp = (int) Util::getTimeOfDay($this->start_timestamp, $test_day);
			}

			$test_day = $next_test_day;
		}

		return ($at_event_end) ? $start_timestamp + $this->end_delta : $start_timestamp;
	}

	/**
	 * Returns the start timestamp of the next event occurence
	 * Returns false if there are no future occurrences
	 *
	 * @param int $after_timestamp Find next occurrence
	 * @return int|false
	 */
	public function getNextOccurrence($after_timestamp = null) {
		if (!$after_timestamp) {
			$after_timestamp = time();
		}
	
		$next = false;
		if ($this->isRecurring()) {
			$next = $this->calculateEndAfterTimestamp(1, $after_timestamp, false);
		} else if ($after_timestamp < $this->start_timestamp) {
			$next = $this->start_timestamp;
		}

		if ($this->repeat_end_timestamp && $this->repeat_end_timestamp < $next) {
			return false;
		}

		return $next;
	}

	/**
	 * Validates that one of the event occurrences starts at the provided timestamp
	 * 
	 * @param int $start_timestamp Timestamp to validate
	 * @return bool
	 */
	public function isValidStartTime($start_timestamp) {
		return $start_timestamp == $this->getNextOccurrence($start_timestamp - 1);
	}

}
