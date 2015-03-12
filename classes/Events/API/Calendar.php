<?php

namespace Events\API;

use ElggBatch;
use ElggCrypto;
use ElggEntity;
use ElggObject;
use Exception;
use vcalendar;

/**
 * Calendar object
 *
 * @property bool $__public_calendar
 * @property bool $__token
 */
class Calendar extends ElggObject {

	const SUBTYPE = 'calendar';
	const EVENT_CALENDAR_RELATIONSHIP = 'on_calendar';

	/**
	 * {@inheritdoc}
	 */
	protected function initializeAttributes() {
		parent::initializeAttributes();
		$this->attributes['subtype'] = self::SUBTYPE;
	}

	/**
	 * {@inheritdoc}
	 */
	public function save() {
		if (!$this->__token) {
			$this->__token = $this->generateToken();
		}
		return parent::save();
	}

	/**
	 * Returns calendar title
	 * @return string
	 */
	public function getDisplayName() {
		return ($this->title) ? : elgg_echo('events:calendar:display_name', array($this->getContainerEntity()->name));
	}

	/**
	 * Checks if this is the default public calendar
	 * @return bool
	 */
	public function isPublicCalendar() {
		return (bool) $this->__public_calendar;
	}

	/**
	 * Returns all event objects
	 *
	 * @param int $starttime Time range start, default: now
	 * @param int $endtime   Time range end, default: 1 year from start time
	 * @return ElggBatch
	 */
	public function getAllEvents($starttime = null, $endtime = null) {

		if (is_null($starttime)) {
			$starttime = time();
		}
		if (is_null($endtime)) {
			$endtime = strtotime('+1 year', $starttime);
		}

		$starttime = sanitize_int($starttime);
		$endtime = sanitize_int($endtime);
		$relationship_name = sanitize_string(self::EVENT_CALENDAR_RELATIONSHIP);

		$dbprefix = elgg_get_config('dbprefix');

		$mds_name = add_metastring('start_timestamp');
		$mdre_name = add_metastring('repeat_end_timestamp');

		$options = array(
			'type' => 'object',
			'subtype' => Event::SUBTYPE,
			'joins' => array(
				"JOIN {$dbprefix}entity_relationships er ON er.guid_one = e.guid", // calendar relationship
				"JOIN {$dbprefix}metadata mds ON mds.entity_guid = e.guid", // start time metadata
				"JOIN {$dbprefix}metastrings mss ON mss.id = mds.value_id", // start time metastring
				"JOIN {$dbprefix}metadata mdre ON mdre.entity_guid = e.guid", // repeat end time metadata
				"JOIN {$dbprefix}metastrings msre ON msre.id = mdre.value_id" // repeat end time metastring
			),
			'wheres' => array(
				"er.guid_two = {$this->guid} AND er.relationship = '{$relationship_name}'",
				"mds.name_id = {$mds_name}",
				"mdre.name_id = {$mdre_name}",
				// event start is before our endtime AND (repeat end is after starttime, or there is no repeat end)
				"((CAST(mss.string AS SIGNED) < {$endtime}) AND (CAST(msre.string AS SIGNED) > {$starttime} OR CAST(msre.string AS SIGNED) = 0))"
			),
			'limit' => false
		);

		return new ElggBatch('elgg_get_entities', $options);
	}

	/**
	 * Returns all event occurrences (one-time and recurring) in a given time range
	 * To prevent memory leaks, the return is a sorted array formatted as:
	 * <code>
	 *  array(
	 *   0 => array(
	 *    'id' => $guid,
	 *    'start' => $start_time,
	 *    'end' => $end_time,
	 *    'title' => $title,
	 *    'description' => $description,
	 *    'url' => $url,
	 *    'allDay' => $all_day,
	 *   ),
	 *  );
	 * </code>
	 *
	 * @param int $starttime Range start timestamp
	 * @param int $endtime   Range end timestamp
	 * @return array
	 */
	public function getAllEventInstances($starttime = null, $endtime = null) {

		$instances = array();
		$events = $this->getAllEvents($starttime, $endtime);
		foreach ($events as $event) {
			/* @var Event $event */
			$start_times = $event->getStartTimes($starttime, $endtime);
			foreach ($start_times as $start_time) {
				$instances[] = array(
					'id' => $event->guid,
					'start' => date('c', $start_time),
					'start_timestamp' => $start_time,
					'end' => date('c', $start_time + $event->end_delta),
					'end_timestamp' => $start_time + $event->end_delta,
					'title' => $event->getDisplayName(),
					'description' => $event->description,
					'host' => $event->getOwnerEntity()->email,
					'url' => $event->getURL($start_time, $this->guid),
					'allDay' => $event->all_day,
					'time_created' => $event->time_created,
					'location' => $event->getLocation(),
				);
			}
		}

		usort($instances, function($a, $b) {
			if ($a['start'] == $b['start']) {
				return ($a['id'] < $b['id']) ? -1 : 1;
			}
			return ($a['start'] < $b['start']) ? -1 : 1;
		});

		return $instances;
	}

	/**
	 * Returns a batch of recurring events in a given time range
	 *
	 * @param int $starttime Time range start, default: now
	 * @param int $endtime   Time range end, default: 1 year from start time
	 * @return array
	 */
	public function getRecurringEvents($starttime = null, $endtime = null) {

		if (is_null($starttime)) {
			$starttime = time();
		}
		if (is_null($endtime)) {
			$endtime = strtotime('+1 year', $starttime);
		}

		$starttime = sanitize_int($starttime);
		$endtime = sanitize_int($endtime);
		$relationship_name = sanitize_string(self::EVENT_CALENDAR_RELATIONSHIP);

		$dbprefix = elgg_get_config('dbprefix');

		// for performance we'll denormalize metastrings first
		$mdr_name = add_metastring('repeat');
		$mdr_val = add_metastring(1);
		$mds_name = add_metastring('start_timestamp');
		$mdre_name = add_metastring('repeat_end_timestamp');
		$options = array(
			'type' => 'object',
			'subtype' => Event::SUBTYPE,
			'joins' => array(
				"JOIN {$dbprefix}entity_relationships er ON er.guid_one = e.guid", // calendar relationship
				"JOIN {$dbprefix}metadata mdr ON mdr.entity_guid = e.guid", // repeating metadata
				"JOIN {$dbprefix}metadata mds ON mds.entity_guid = e.guid", // start time metadata
				"JOIN {$dbprefix}metastrings mss ON mss.id = mds.value_id", // start time metastring
				"JOIN {$dbprefix}metadata mdre ON mdre.entity_guid = e.guid", // repeat end time metadata
				"JOIN {$dbprefix}metastrings msre ON msre.id = mdre.value_id" // repeat end time metastring
			),
			'wheres' => array(
				"er.guid_two = {$this->guid} AND er.relationship = '{$relationship_name}'",
				"mdr.name_id = {$mdr_name} AND mdr.value_id = {$mdr_val}",
				"mds.name_id = {$mds_name}",
				"mdre.name_id = {$mdre_name}",
				// event start is before our endtime AND (repeat end is after starttime, or there is no repeat end)
				"((CAST(mss.string AS SIGNED) < {$endtime}) AND (CAST(msre.string AS SIGNED) > {$starttime} OR CAST(msre.string AS SIGNED) = 0))"
			),
			'limit' => false
		);

		$events = new ElggBatch('elgg_get_entities', $options);

		// these entities may not actually show up in our range yet, need to filter
		$recurring_events = array();
		foreach ($events as $event) {
			/* @var Event $event */
			if ($event->getStartTimes($starttime, $endtime)) {
				// hey, we have a hit!
				$recurring_events[] = $event;
			}
		}

		return $recurring_events;
	}

	/**
	 * Returns one-time events
	 *
	 * @param int $starttime Time range start, default: now
	 * @param int $endtime   Time range end, default: 1 year from start time
	 * @return ElggBatch
	 */
	public function getOneTimeEvents($starttime = null, $endtime = null) {

		if (is_null($starttime)) {
			$starttime = time();
		}
		if (is_null($endtime)) {
			$endtime = strtotime('+1 year', $starttime);
		}

		$starttime = sanitize_int($starttime);
		$endtime = sanitize_int($endtime);
		$relationship_name = sanitize_string(self::EVENT_CALENDAR_RELATIONSHIP);

		$dbprefix = elgg_get_config('dbprefix');

		// for performance we'll denormalize metastrings first
		$mdr_name = add_metastring('repeat');
		$mdr_val = add_metastring(0);
		$mds_name = add_metastring('start_timestamp');
		$mde_name = add_metastring('end_timestamp');
		$options = array(
			'type' => 'object',
			'subtype' => Event::SUBTYPE,
			'joins' => array(
				"JOIN {$dbprefix}entity_relationships er ON er.guid_one = e.guid", // calendar relationship
				"JOIN {$dbprefix}metadata mdr ON mdr.entity_guid = e.guid", // repeating metadata
				"JOIN {$dbprefix}metadata mds ON mds.entity_guid = e.guid", // start time metadata
				"JOIN {$dbprefix}metastrings mss ON mss.id = mds.value_id", // start time metastring
				"JOIN {$dbprefix}metadata mde ON mde.entity_guid = e.guid", // end time metadata
				"JOIN {$dbprefix}metastrings mse ON mse.id = mde.value_id" // end time metastring
			),
			'wheres' => array(
				"er.guid_two = {$this->guid} AND er.relationship = '{$relationship_name}'",
				"mdr.name_id = {$mdr_name} AND mdr.value_id = {$mdr_val}",
				"mds.name_id = {$mds_name}",
				"mde.name_id = {$mde_name}",
				"((CAST(mss.string AS SIGNED) BETWEEN {$starttime} AND {$endtime})
					OR (CAST(mse.string AS SIGNED) BETWEEN {$starttime} AND {$endtime}))"
			),
			'limit' => false
		);

		return new ElggBatch('elgg_get_entities', $options);
	}

	/**
	 * Checks if the user can add events to this calendar
	 * Events are not actually contained by the calendar, but the canWriteToContainer() logic applies
	 * 
	 * @param int $user_guid GUID of the user
	 * @return bool
	 */
	public function canAddEvent($user_guid = 0) {
		return $this->canWriteToContainer($user_guid, 'object', Event::SUBTYPE);
	}

	/**
	 * Checks if the event is on calendar
	 * 
	 * @param Event $event Event
	 * @return bool
	 */
	public function hasEvent($event) {
		return (bool) check_entity_relationship($event->guid, self::EVENT_CALENDAR_RELATIONSHIP, $this->guid);
	}

	/**
	 * Adds an event to a calendar
	 *
	 * @param Event $event Event object
	 * @return boolean
	 */
	public function addEvent($event) {
		$result = add_entity_relationship($event->guid, self::EVENT_CALENDAR_RELATIONSHIP, $this->guid);

		if ($result) {
			// allow events to fire after confirmation that it has been added to the calendar
			elgg_trigger_event('events_api', 'add_to_calendar', array('event' => $event, 'calendar' => $this));
		}
		return $result;
	}

	/**
	 * Removes an event from a calendar
	 *
	 * @param Event $event Event object
	 * @return boolean
	 */
	public function removeEvent($event) {
		$result = remove_entity_relationship($event->guid, self::EVENT_CALENDAR_RELATIONSHIP, $this->guid);

		if ($result) {
			// allow events to fire after confirmation that it has been added to the calendar
			elgg_trigger_event('events_api', 'remove_from_calendar', array('event' => $event, 'calendar' => $this));
		}

		return $result;
	}

	/**
	 * Returns a URL of the iCal feed
	 * 
	 * @param string $base_url Base URL
	 * @param array  $params   Additional params
	 * @return string
	 */
	public function getIcalURL($base_url = '', array $params = array()) {

		$user = elgg_get_logged_in_user_entity();

		$params['view'] = 'ical';
		$params['u'] = $user->guid;
		$params['t'] = $this->getUserToken($user->guid);

		return elgg_http_add_url_query_elements($base_url, $params);
	}

	/**
	 * Returns an iCal feed for this calendar
	 *
	 * @param int    $starttime Range start timestamp
	 * @param int    $endtime   Range end timestamp
	 * @param string $filename  Filename used for output file
	 * @return string
	 */
	public function toIcal($starttime = null, $endtime = null, $filename = 'calendar.ics') {

		if (is_null($starttime)) {
			$starttime = time();
		}
		if (is_null($endtime)) {
			$endtime = strtotime('+1 year', $starttime);
		}

		$instances = $this->getAllEventInstances($starttime, $endtime);

		$config = array(
			'unique_id' => $this->guid,
			// setting these explicitly until icalcreator bug #14 is solved
			'allowEmpty' => true,
			'nl' => "\r\n",
			'format' => 'iCal',
			'delimiter' => DIRECTORY_SEPARATOR,
			'filename' => $filename, // this is last until #14 is solved
		);

		$v = new vcalendar($config);
		$v->setProperty('method', 'PUBLISH');
		$v->setProperty("x-wr-calname", $this->getDisplayName());
		$v->setProperty("X-WR-CALDESC", strip_tags($this->description));
		$v->setProperty("X-WR-TIMEZONE", date_default_timezone_get());
		foreach ($instances as $instance) {
			$vevent = & $v->newComponent('vevent');

			$st = getdate((int) $instance['start_timestamp']);
			if ($instance['allDay']) {
				$vevent->setProperty('dtstart', date("Ymd", $instance['start_timestamp']), array('VALUE' => 'DATE'));
				$vevent->setProperty('dtend', date("Ymd", $instance['end_timestamp']), array('VALUE' => 'DATE'));
			} else {
				$vevent->setProperty('dtstart', date("Ymd\THis\Z", $instance['start_timestamp']));
				$vevent->setProperty('dtend', date("Ymd\THis\Z", $instance['end_timestamp']));
			}
			$vevent->setProperty('class', 'PUBLIC');
			$vevent->setProperty('organizer', $instance['host']);
			$vevent->setProperty('uid', $instance['id'] . '-' . $instance['start_timestamp']);
			$vevent->setProperty('url', $instance['url']);
			$vevent->setProperty('location', $instance['location']);
			$vevent->setProperty('summary', strip_tags($instance['title']));
			$vevent->setProperty('description', strip_tags($instance['description']));

			/**
			 * @todo: add reminders
			 */
		}

		$v->returnCalendar();
	}

	/**
	 * Creates a public calendar for a container
	 *
	 * @param ElggEntity $container User or group
	 * @return Calendar
	 */
	public static function createPublicCalendar($container) {

		if (!$container instanceof ElggEntity) {
			return false;
		}

		try {
			$calendar = new Calendar();
			$calendar->access_id = ACCESS_PUBLIC;
			$calendar->owner_guid = $container->guid;
			$calendar->container_guid = $container->guid;
			$calendar->__public_calendar = true; // flag we can use to manipulate permissions

			$ia = elgg_set_ignore_access(true);
			$calendar->save();
			elgg_set_ignore_access($ia);

			elgg_log("Created public calendar for $container->name [$container->guid]", 'NOTICE');
		} catch (Exception $ex) {
			elgg_log($ex->getMessage(), 'ERROR');
		}

		return $calendar;
	}

	/**
	 * Retrieves all calendars for a container
	 * 
	 * @param type $container
	 * @param type $count
	 */
	public static function getCalendars($container, $count = false) {
		if (!$container instanceof ElggEntity) {
			return false;
		}

		$options = array(
			'type' => 'object',
			'subtype' => 'calendar',
			'container_guid' => $container->guid,
			'limit' => false
		);

		if ($count) {
			$options['count'] = true;
			return elgg_get_entities($options);
		}

		return new ElggBatch('elgg_get_entities', $options);
	}

	/**
	 * Retrieves user's or group's public calendar
	 *
	 * @param ElggEntity $container User or group
	 * @return Calendar|false
	 */
	public static function getPublicCalendar($container) {

		if (!$container instanceof ElggEntity) {
			return false;
		}

		$calendars = elgg_get_entities(array(
			'type' => 'object',
			'subtype' => Calendar::SUBTYPE,
			'container_guid' => $container->guid,
			'limit' => 1,
			'metadata_name_value_pairs' => array(
				'name' => '__public_calendar',
				'value' => true,
			),
			'order_by' => 'e.time_created ASC', // get the first one
		));

		return (empty($calendars)) ? Calendar::createPublicCalendar($container) : $calendars[0];
	}

	/**
	 * Generates a token
	 * @return string
	 */
	protected function generateToken() {
		return md5($this->guid . ElggCrypto::getRandomString(31));
	}

	/**
	 * Generates a user token
	 *
	 * @param int $user_guid GUID of the user
	 * @return string
	 */
	public function getUserToken($user_guid = 0) {
		if (!$user_guid) {
			$user_guid = elgg_get_logged_in_user_guid();
		}
		return md5($user_guid . $this->getToken());
	}

	/**
	 * Returns a stored token
	 * @return string
	 */
	public function getToken() {
		return $this->__token;
	}

}
