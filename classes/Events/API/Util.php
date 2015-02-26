<?php

namespace Events\API;

use DateTime;

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
	 * Returns a timestamp for 0:00:00 of the date of the time
	 * 
	 * @param mixed  $ts     Date/time value
	 * @param string $format Format of the return value
	 * @return string
	 */
	public static function getDayStart($ts = 'now', $format = 'U') {
		$dt = new DateTime;
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
	public static function getDayEnd($ts = 'now', $format = 'U') {
		$dt = new DateTime;
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
	public static function getMonthStart($ts = 'now', $format = 'U') {
		$dt = new DateTime;
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
	public static function getMonthEnd($ts = 'now', $format = 'U') {

		$dt = new DateTime;
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
	public static function getTime($ts = 'now', $format = 'U') {

		$dt = new DateTime;
		(is_int($ts)) ? $dt->setTimestamp($ts) : $dt->modify($ts);

		$time = $dt->format('H') * self::SECONDS_IN_AN_HOUR;
		$time += $dt->format('i') * self::SECONDS_IN_A_MINUTE;
		$time += $dt->format('s');

		return date($format, $time);
	}

	/**
	 * Calculates a timestamp by extracting time from $ts_time and adding it to the day start on $ts_day
	 *
	 * @param mixed $ts_time Date/time string containing time information
	 * @param mixed $ts_day  Date/time string containing day information
	 * @return int
	 */
	public static function getTimeOfDay($ts_time = 0, $ts_day = null, $format = 'U') {

		$time = Util::getTime($ts_time);
		$day_start = Util::getDayStart($ts_day);

		return date($format, $time + $day_start);
	}

	/**
	 * Returns day of week
	 * 
	 * @param mixed  $ts     Date/time value
	 * @param string $format Format of the return value
	 * @return string
	 */
	public static function getDayOfWeek($ts = 'now', $format = 'D') {
		$dt = new DateTime;
		(is_int($ts)) ? $dt->setTimestamp($ts) : $dt->modify($ts);
		return $dt->format($format);
	}

	/**
	 * Returns the week number if a month (e.g. 2nd week of the month)
	 * 
	 * @param mixed $ts Date/time value
	 * @return int
	 */
	public static function getWeekOfMonth($ts = 'now') {
		$dt = new DateTime;
		(is_int($ts)) ? $dt->setTimestamp($ts) : $dt->modify($ts);
		$week_num_ts = date('W', $dt->getTimestamp());
		$week_num_month_start = date('W', self::getMonthStart($ts));
		return $week_num_ts - $week_num_month_start + 1;
	}

	/**
	 * Returns nth position of a weekday in a month (e.g. 2nd Monday of a month)
	 * 
	 * @param mixed $ts Date/time value
	 * @return int
	 */
	public static function getWeekDayNthInMonth($ts = 'now') {
		$dt = new DateTime;
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
	public static function isOnSameDayOfWeek($ts1 = 0, $ts2 = 0) {
		return date('D', $ts1) == date('D', $ts2);
	}

	/**
	 * Checks if two timestamps fall on the same date of the month (e.g. 25th)
	 *
	 * @param int $ts1 First timestamp
	 * @param int $ts2 Second timestamp
	 * @return bool
	 */
	public static function isOnSameDayOfMonth($ts1 = 0, $ts2 = 0) {
		return date('j', $ts1) == date('j', $ts2);
	}

	/**
	 * Checks if two timestamps fall on the same date of the year (e.g. February 25th)
	 *
	 * @param int $ts1 First timestamp
	 * @param int $ts2 Second timestamp
	 * @return bool
	 */
	public static function isOnSameDayOfYear($ts1 = 0, $ts2 = 0) {
		return date('m-j', $ts1) == date('m-j', $ts2);
	}

	/**
	 * Checks if two timestamps fall on the same week day of the month (e.g. 3rd Monday)
	 * 
	 * @param int $ts1 First timestamp
	 * @param int $ts2 Second timestamp
	 * @return bool
	 */
	public static function isOnSameWeekDayOfMonth($ts1 = 0, $ts2 = 0) {
		if (!self::isOnSameDayOfWeek($ts1, $ts2)) {
			return false;
		}
		return self::getWeekDayNthInMonth($ts1) == self::getWeekDayNthInMonth($ts2);
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

}
