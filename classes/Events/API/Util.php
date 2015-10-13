<?php

namespace Events\API;

use DateTime;
use DateTimeZone;
use ElggBatch;
use ElggUser;
use stdClass;

class Util {

	/**
	 * Seconds
	 */
	const SECONDS_IN_A_MINUTE = 60;
	const SECONDS_IN_AN_HOUR = 3600;
	const SECONDS_IN_A_DAY = 86400;
	const SECONDS_IN_A_WEEK = 604800;

	/**
	 * Days of week
	 */
	const MONDAY = 'Mon';
	const TUESDAY = 'Tue';
	const WEDNESDAY = 'Wed';
	const THURSDAY = 'Thu';
	const FRIDAY = 'Fri';
	const SATURDAY = 'Sat';
	const SUNDAY = 'Sun';

	/**
	 * Frequencies
	 */
	const FREQUENCY_ONCE = 'once';
	const FREQUENCY_DAILY = 'daily';
	const FREQUENCY_WEEKDAY = 'weekday';
	const FREQUENCY_WEEKDAY_ODD = 'dailymwf';
	const FREQUENCY_WEEKDAY_EVEN = 'dailytt';
	const FREQUENCY_WEEKLY = 'weekly';
	const FREQUENCY_MONTHLY = 'monthly';
	const FREQUENCY_YEARLY = 'yearly';

	/**
	 * Repeats
	 */
	const REPEAT_END_NEVER = 'never';
	const REPEAT_END_AFTER = 'after';
	const REPEAT_END_ON = 'on';
	const REPEAT_END_ONE_TIME = 'one_time';
	const REPEAT_MONTHLY_BY_DATE = 'day_of_month';
	const REPEAT_MONTHLY_BY_DAY_OF_WEEK = 'day_of_week';

	/**
	 * Timezones
	 */
	const UTC = 'UTC';
	const TIMEZONE_FORMAT_FULL = "\(\G\M\TP\) e - H:i T";
	const TIMEZONE_FORMAT_ABBR = "T";
	const TIMEZONE_FORMAT_NAME = "e";
	const TIMEZONE_SORT_ALPHA = 'alpha';
	const TIMEZONE_SORT_OFFSET = 'offset';

	static $timezone;

	/**
	 * Returns a timestamp for 0:00:00 of the date of the time
	 * 
	 * @param mixed  $ts     Date/time value
	 * @param string $format Format of the return value
	 * @return string
	 */
	public static function getDayStart($ts = 'now', $format = 'U', $tz = null) {
		if (!Util::isValidTimezone($tz)) {
			$tz = Util::getClientTimezone();
		}
		
		$dt = new DateTime(null, new DateTimeZone($tz));
		(is_int($ts)) ? $dt->setTimestamp($ts) : $dt->modify($ts);
		$dt->setTime(0, 0, 0);
		return $dt->format($format);
	}

	/**
	 * Returns a timestamp for 23:59:59 of the date of the time
	 *
	 * @param mixed  $ts     Date/time value
	 * @param string $format Format of the return value
	 * @return string
	 */
	public static function getDayEnd($ts = 'now', $format = 'U', $tz = null) {
		if (!Util::isValidTimezone($tz)) {
			$tz = Util::getClientTimezone();
		}
		
		$dt = new DateTime(null, new DateTimeZone($tz));
		(is_int($ts)) ? $dt->setTimestamp($ts) : $dt->modify($ts);
		$dt->setTime(23, 59, 59);
		return $dt->format($format);
	}

	/**
	 * Returns a timestamp for the first of the month at 0:00:00
	 *
	 * @param mixed  $ts     Date/time value
	 * @param string $format Format of the return value
	 * @return string
	 */
	public static function getMonthStart($ts = 'now', $format = 'U', $tz = null) {
		if (!Util::isValidTimezone($tz)) {
			$tz = Util::getClientTimezone();
		}
		
		$dt = new DateTime(null, new DateTimeZone($tz));
		(is_int($ts)) ? $dt->setTimestamp($ts) : $dt->modify($ts);

		$month = (int) $dt->format('m'); // month
		$year = (int) $dt->format('Y'); // year

		$dt->setDate($year, $month, 1);
		$dt->setTime(0, 0, 0);

		return $dt->format($format);
	}

	/**
	 * Returns a timestamp for the last day of themonth at 23:59:59
	 * 
	 * @param mixed  $ts     Date/time value
	 * @param string $format Format of the return value
	 * @return string
	 */
	public static function getMonthEnd($ts = 'now', $format = 'U', $tz = null) {
		if (!Util::isValidTimezone($tz)) {
			$tz = Util::getClientTimezone();
		}

		$dt = new DateTime(null, new DateTimeZone($tz));
		(is_int($ts)) ? $dt->setTimestamp($ts) : $dt->modify($ts);

		$dt->modify('+1 month');

		$month = (int) $dt->format('m'); // month
		$year = (int) $dt->format('Y'); // year

		$dt->setDate($year, $month, 1);
		$dt->setTime(0, 0, 0);

		$dt->modify('-1 second');

		return $dt->format($format);
	}

	/**
	 * Extracts time of the day timestamp
	 *
	 * @param mixed $ts Date/time value
	 * @param string $format Format of the return value
	 * @return string
	 */
	public static function getTime($ts = 'now', $format = 'U', $tz = null) {
		if (!Util::isValidTimezone($tz)) {
			$tz = Util::getClientTimezone();
		}
		
		$dt = new DateTime(null, new DateTimeZone($tz));
		(is_int($ts)) ? $dt->setTimestamp($ts) : $dt->modify($ts);

		$time = $dt->format('H') * self::SECONDS_IN_AN_HOUR;
		$time += $dt->format('i') * self::SECONDS_IN_A_MINUTE;
		$time += $dt->format('s');

		return $dt->setTimestamp($time)->format($format);
	}

	public static function getLongestReminder() {
		$md = elgg_get_metadata(array(
			'type' => 'object',
			'subtype' => 'event',
			'selects' => array("CAST(v.string AS SIGNED) as reminder"),
			'metadata_name' => 'reminder',
			'order_by' => 'reminder desc',
			'limit' => 1
		));

		return (int) $md[0]->value;
	}

	/**
	 * analog of Calendar::getAllEvents but doesn't limit to a specific calendar
	 * 
	 * @param type $starttime
	 * @param type $endtime
	 */
	public static function getAllEvents($starttime, $endtime) {
		$starttime = sanitize_int($starttime);
		$endtime = sanitize_int($endtime);

		$dbprefix = elgg_get_config('dbprefix');

		$mds_name = elgg_get_metastring_id('start_timestamp');
		$mdre_name = elgg_get_metastring_id('repeat_end_timestamp');

		$options = array(
			'type' => 'object',
			'subtype' => Event::SUBTYPE,
			'joins' => array(
				"JOIN {$dbprefix}metadata mds ON mds.entity_guid = e.guid", // start time metadata
				"JOIN {$dbprefix}metastrings mss ON mss.id = mds.value_id", // start time metastring
				"JOIN {$dbprefix}metadata mdre ON mdre.entity_guid = e.guid", // repeat end time metadata
				"JOIN {$dbprefix}metastrings msre ON msre.id = mdre.value_id" // repeat end time metastring
			),
			'wheres' => array(
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
	 * Calculates a timestamp by extracting time from $ts_time and adding it to the day start on $ts_day
	 *
	 * @param mixed $ts_time Date/time string containing time information
	 * @param mixed $ts_day  Date/time string containing day information
	 * @return int
	 */
	public static function getTimeOfDay($ts_time = 0, $ts_day = null, $format = 'U', $tz = null) {
		if (!Util::isValidTimezone($tz)) {
			$tz = Util::getClientTimezone();
		}
		
		$time = Util::getTime($ts_time, 'U', $tz);
		$day_start = Util::getDayStart($ts_day, 'U', $tz);

		$dt = new DateTime(null, new DateTimeZone($tz));
		return $dt->setTimestamp($time + $day_start)->format($format);

	}

	/**
	 * Returns day of week
	 * 
	 * @param mixed  $ts     Date/time value
	 * @param string $format Format of the return value
	 * @return string
	 */
	public static function getDayOfWeek($ts = 'now', $tz = null, $format = 'D') {
		if (!Util::isValidTimezone($tz)) {
			$tz = Util::getClientTimezone();
		}
		
		$dt = new DateTime(null, new DateTimeZone($tz));
		(is_int($ts)) ? $dt->setTimestamp($ts) : $dt->modify($ts);
		return $dt->format($format);
	}

	/**
	 * Returns the week number if a month (e.g. 2nd week of the month)
	 * 
	 * @param mixed $ts Date/time value
	 * @return int
	 */
	public static function getWeekOfMonth($ts = 'now', $tz = null) {
		if (!Util::isValidTimezone($tz)) {
			$tz = Util::getClientTimezone();
		}
		
		$dt = new DateTime(null, new DateTimeZone($tz));
		(is_int($ts)) ? $dt->setTimestamp($ts) : $dt->modify($ts);
		$week_num_ts = $dt->format('W');
		$week_num_month_start = $dt->setTimestamp(self::getMonthStart($ts, 'U', $tz))->format('W');
		return $week_num_ts - $week_num_month_start + 1;
	}

	/**
	 * Returns nth position of a weekday in a month (e.g. 2nd Monday of a month)
	 * 
	 * @param mixed $ts Date/time value
	 * @return int
	 */
	public static function getWeekDayNthInMonth($ts = 'now', $tz = null) {
		if (!Util::isValidTimezone($tz)) {
			$tz = Util::getClientTimezone();
		}
		$dt = new DateTime(null, new DateTimeZone($tz));
		(is_int($ts)) ? $dt->setTimestamp($ts) : $dt->modify($ts);
		return ceil($dt->format('j') / 7);
	}

	/**
	 * Checks if two timestamps fall on the same day of the week (e.g. Monday)
	 *
	 * @param int $ts1 First timestamp
	 * @param int $ts2 Second timestamp
	 * @return bool
	 */
	public static function isOnSameDayOfWeek($ts1 = 0, $ts2 = 0, $tz = null) {
		if (!Util::isValidTimezone($tz)) {
			$tz = Util::getClientTimezone();
		}
		$dt1 = new DateTime(null, new DateTimeZone($tz));
		$dt2 = new DateTime(null, new DateTimeZone($tz));

		return $dt1->setTimestamp($ts1)->format('D') == $dt2->setTimestamp($ts2)->format('D');
	}

	/**
	 * Checks if two timestamps fall on the same date of the month (e.g. 25th)
	 *
	 * @param int $ts1 First timestamp
	 * @param int $ts2 Second timestamp
	 * @return bool
	 */
	public static function isOnSameDayOfMonth($ts1 = 0, $ts2 = 0, $tz = null) {
		if (!Util::isValidTimezone($tz)) {
			$tz = Util::getClientTimezone();
		}
		$dt1 = new DateTime(null, new DateTimeZone($tz));
		$dt2 = new DateTime(null, new DateTimeZone($tz));
		return $dt1->setTimestamp($ts1)->format('j') == $dt2->setTimestamp($ts2)->format('j');
	}

	/**
	 * Checks if two timestamps fall on the same date of the year (e.g. February 25th)
	 *
	 * @param int $ts1 First timestamp
	 * @param int $ts2 Second timestamp
	 * @return bool
	 */
	public static function isOnSameDayOfYear($ts1 = 0, $ts2 = 0, $tz = null) {
		if (!Util::isValidTimezone($tz)) {
			$tz = Util::getClientTimezone();
		}
		
		$dt1 = new DateTime(null, new DateTimeZone($tz));
		$dt2 = new DateTime(null, new DateTimeZone($tz));
		return $dt1->setTimestamp($ts1)->format('m-j') == $dt2->setTimestamp($ts2)->format('m-j');
	}

	/**
	 * Checks if two timestamps fall on the same week day of the month (e.g. 3rd Monday)
	 * 
	 * @param int $ts1 First timestamp
	 * @param int $ts2 Second timestamp
	 * @return bool
	 */
	public static function isOnSameWeekDayOfMonth($ts1 = 0, $ts2 = 0, $tz = null) {
		if (!Util::isValidTimezone($tz)) {
			$tz = Util::getClientTimezone();
		}
		
		if (!self::isOnSameDayOfWeek($ts1, $ts2, $tz)) {
			return false;
		}
		return self::getWeekDayNthInMonth($ts1, $tz) == self::getWeekDayNthInMonth($ts2, $tz);
	}

	/**
	 * Returns an array of weekdays
	 * @return array
	 */
	public static function getWeekdays() {
		return array(
			self::MONDAY,
			self::TUESDAY,
			self::WEDNESDAY,
			self::THURSDAY,
			self::FRIDAY,
			self::SATURDAY,
			self::SUNDAY,
		);
	}

	/**
	 * Returns repeat frequencies
	 * @return array
	 */
	function getRepeatFrequencies() {
		return array(
			Util::FREQUENCY_DAILY => elgg_echo('events_ui:repeat:daily'),
			Util::FREQUENCY_WEEKDAY => elgg_echo('events_ui:repeat:weekday'),
			Util::FREQUENCY_WEEKDAY_ODD => elgg_echo('events_ui:repeat:dailymwf'),
			Util::FREQUENCY_WEEKDAY_EVEN => elgg_echo('events_ui:repeat:dailytt'),
			Util::FREQUENCY_WEEKLY => elgg_echo('events_ui:repeat:weekly'),
			Util::FREQUENCY_MONTHLY => elgg_echo('events_ui:repeat:monthly'),
			Util::FREQUENCY_YEARLY => elgg_echo('events_ui:repeat:yearly'),
		);
	}

	/**
	 * Calculates the offset between timezones at a given date/time
	 *
	 * @param mixed  $ts              Date/time value
	 * @param string $timezone        Timezone of the date/time value
	 * $param string $target_timezone Target timezone
	 * @return int
	 */
	public static function getOffset($ts = 'now', $timezone = self::UTC, $target_timezone = self::UTC) {

		if (!self::isValidTimezone($timezone) || !self::isValidTimezone($target_timezone)) {
			return 0;
		}

		$dta = new DateTime(null, new DateTimeZone($timezone));
		$dtb = new DateTime(null, new DateTimeZone($target_timezone));

		if (is_int($ts)) {
			$dta->setTimestamp($ts);
			$dtb->setTimestamp($ts);
		} else {
			$dta->modify($ts);
			$dtb->modify($ts);
		}

		return $dtb->getOffset() - $dta->getOffset();
	}

	/**
	 * Returns a list of supported timezones
	 * Triggers 'timezones','events_api' hook if $filter is set to true
	 *
	 * @param boolean $filter  If false, returns all supported PHP timezones
	 * @param mixed   $format  Timezone label date format; if false, uses elgg_echo($tz_id)
	 * @param mixed   $ts      Optional timestamp for label format
	 * @param string  $sort_by 'alpha' or 'offset'
	 * @return array
	 */
	public static function getTimezones($filter = true, $format = false, $ts = 'now', $sort = self::TIMEZONE_SORT_ALPHA) {

		$tz_ids = DateTimeZone::listIdentifiers();

		$defaults = array();

		foreach ($tz_ids as $tz_id) {
			$defaults[$tz_id] = Util::getTimezoneLabel($tz_id, $format, $ts);
		}

		switch ($sort) {
			case self::TIMEZONE_SORT_ALPHA :
				asort($defaults);
				break;
			case self::TIMEZONE_SORT_OFFSET :
				uksort($defaults, array(self, 'compareTimezonesByOffset'));
				break;
		}

		if ($filter) {
			return elgg_trigger_plugin_hook('timezones', 'events_api', null, $defaults);
		}

		return $defaults;
	}

	/**
	 * Returns an array of timezones by country
	 * @return array
	 */
	public static function getTimezonesByCountry() {
		$timezones = array();
		$tz_ids = array_keys(self::getTimezones(true, false, 'now', self::TIMEZONE_SORT_OFFSET));
		foreach ($tz_ids as $tz_id) {
			if ($tz_id == Util::UTC) {
				continue;
			}
			$info = Util::getTimezoneInfo($tz_id);
			$cc = $info->country_code;
			$abbr = $info->abbr;
			if (!isset($timezones[$cc])) {
				$timezones[$cc] = array();
			}
			$timezones[$cc][] = $info;
		}
		ksort($timezones);
		return $timezones;
	}

	/**
	 * Expands timezone ID into a usable source of data about the timezone
	 * 
	 * @param string $tz_id Timezone ID e.g. America\New_York
	 * @return stdClass
	 */
	public static function getTimezoneInfo($tz_id) {

		$tz = new \DateTimeZone($tz_id);
		$location = $tz->getLocation();
		$country_code = $location['country_code'];

		$dt = new DateTime(null, $tz);

		$region = explode('/', $tz_id);
		if (sizeof($region) > 1) {
			array_shift($region);
		}
		$region = str_replace('_', ' ', implode(', ', $region));

		$tzinfo = new stdClass();
		$tzinfo->id = $tz_id;
		$tzinfo->abbr = $dt->format('T');
		$tzinfo->country_code = $country_code;
		$tzinfo->country = elgg_echo("timezone:country:$country_code");
		$tzinfo->region = $region;
		$tzinfo->offset = $dt->getOffset();
		$tzinfo->gmt = $dt->format('\(\G\M\TP\)');

		$name = "timezone:name:$tzinfo->country_code:$tzinfo->abbr";
		$name_tr = elgg_echo($name);
		$tzinfo->name = ($name == $name_tr) ? $tzinfo->abbr : $name_tr;
		$tzinfo->label = "$tzinfo->gmt $tzinfo->name - $tzinfo->region";

		return $tzinfo;
	}

	/**
	 * Checks if $timezone id is valid
	 *
	 * @param string $timezone
	 * @return bool
	 */
	public static function isValidTimezone($timezone) {
		static $cache;
		if (!is_array($cache)) {
			$cache = array();
		}
		
		if (isset($cache[$timezone])) {
			return $cache[$timezone];
		}
		
		$cache[$timezone] = in_array($timezone, DateTimeZone::listIdentifiers());
		return $cache[$timezone];
	}

	/**
	 * Returns a label for a timezone
	 * 
	 * @param string $tz_id  PHP timezone id
	 * @param format $format Format
	 * @param mixed  $ts     Optional timestamp
	 * @return string
	 */
	public static function getTimezoneLabel($tz_id, $format = false, $ts = 'now') {
		if (self::isValidTimezone($tz_id) && $format) {
			$dt = new DateTime(null, new DateTimeZone($tz_id));
			(is_int($ts)) ? $dt->setTimestamp($ts) : $dt->modify($ts);
			return $dt->format($format);
		}

		return elgg_echo($tz_id);
	}

	/**
	 * Sorting callback function for comparing timezones by offset
	 * @return int
	 */
	public static function compareTimezonesByOffset($a, $b) {

		$dta = new DateTime(null, new DateTimeZone($a));
		$dtb = new DateTime(null, new DateTimeZone($b));

		if ($dta->getOffset() == $dtb->getOffset()) {
			return (strcmp($a, $b) < 0) ? -1 : 1;
		}
		return ($dta->getOffset() < $dtb->getOffset()) ? -1 : 1;
	}

	/**
	 * Returns display timezone
	 *
	 * Returns first set value from the following:
	 * 1. Request query element
	 * 2. Timezone set by the user in their settings
	 * 3. Timezone set as site default
	 * 4. PHP timezone
	 *
	 * @param ElggUser $user User
	 * @return string
	 */
	public static function getClientTimezone($user = null) {

		if (isset(self::$timezone)) {
			return self::$timezone;
		}

		$preferred = array();

		$preferred[] = get_input('timezone');

		if ($user == null) {
			$user = elgg_get_logged_in_user_entity();
		}
		
		if ($user) {
			$preferred[] = $user->timezone;
		}

		if (defined('ELGG_SITE_TIMEZONE')) {
			$preferred[] = ELGG_SITE_TIMEZONE;
		}

		$preferred[] = date('e');

		foreach ($preferred as $id) {
			if (self::isValidTimezone($id)) {
				self::$timezone = $id;
				return $id;
			}
		}

		return Util::UTC;
	}

	/**
	 * Returns a representation of $ts in ISO8601 format using $tz_id as a base timezone
	 * 
	 * @param mixed  $ts              Date/time value
	 * @param string $timezone        Base timezone of the date/time value
	 * @param string $target_timezone Target timezone of the formatted value
	 * @return string
	 */
	public static function toISO8601($ts = 'now', $timezone = Util::UTC, $target_timezone = Util::UTC) {
		$dt = new DateTime(null, new DateTimeZone($timezone));
		(is_int($ts)) ? $dt->setTimestamp($ts) : $dt->modify($ts);
		$dt->setTimezone(new DateTimeZone($target_timezone));
		return $dt->format('c');
	}

}
