<?php

namespace Events\API;

class EventInstance {

	/**
	 * Event
	 * @var Event
	 */
	protected $event;

	/**
	 * Calendar
	 * @var Calendar
	 */
	protected $calendar;

	/**
	 * Instance start time
	 * @var int
	 */
	protected $timestamp;

	/**
	 * Factory
	 *
	 * @param Event $event     Event
	 * @param int   $timestamp Instance start timestamp
	 */
	public function __construct(Event $event, $timestamp = 0) {
		$this->event = $event;
		$this->timestamp = $timestamp;
	}

	/**
	 * Sets calendar for the instance
	 *
	 * @param Calendar $calendar Calendar
	 * @return EventInstance
	 */
	public function setCalendar(Calendar $calendar) {
		$this->calendar = $calendar;
	}

	/**
	 * Returns calendar for the instance
	 * @return Calendar
	 */
	public function getCalendar() {
		return ($this->calendar) ? : new Calendar;
	}

	/**
	 * Returns the event object
	 * @return Event
	 */
	public function getEvent() {
		return ($this->event) ? : new Event;
	}

	/**
	 * Returns start time of the instance
	 * @return int
	 */
	public function getStartTimestamp() {
		return (int) $this->timestamp;
	}

	/**
	 * Returns end time of the instance
	 * @return int
	 */
	public function getEndTimestamp() {
		return $this->getStartTimestamp() + $this->getEvent()->end_delta;
	}

	/**
	 * Returns ISO 8601 representation of the start timestamp
	 * @return string
	 */
	public function getStart() {
		return Util::toISO8601($this->getStartTimestamp(), Util::UTC, $this->getEvent()->getTimezone());
	}

	/**
	 * Returns ISO 8601 representation of the end timestamp
	 * @return string
	 */
	public function getEnd() {
		return Util::toISO8601($this->getEndTimestamp(), Util::UTC, $this->getEvent()->getTimezone());
	}

	/**
	 * Exports an instance into an array
	 * @tip Use 'export:instance','events_api' to filter exported values
	 *
	 * @param mixed $consumer Consumer name (plugins can decide what to export)
	 * @return array
	 */
	public function export($consumer = '') {
		$event = $this->getEvent();
		$export = array(
			'url' => $event->getURL($this->getStartTimestamp(), $this->getCalendar()->guid),
			'start' => $this->getStart(),
			'end' => $this->getEnd(),
			'start_timestamp' => $this->getStartTimestamp(),
			'end_timestamp' => $this->getEndTimestamp(),
			'location' => $event->getLocation(),
		);

		$keys = $event->getExportableValues();
		foreach ($keys as $key) {
			$export[$key] = $event->$key;
		}

		return elgg_trigger_plugin_hook('export:instance', 'events_api', array(
			'instance' => $this,
			'consumer' => $consumer,
				), $export);
	}

}
