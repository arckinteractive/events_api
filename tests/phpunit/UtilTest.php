<?php

namespace Events\API;

use PHPUnit_Framework_TestCase;

class UtilTest extends PHPUnit_Framework_TestCase {

	/**
	 * @var Util
	 */
	protected $util;

	public function setUp() {
		$this->util = new Util;
	}

	public function testGetDayStartEndFromTimestamp() {

		$ts = 1424543272; // Saturday, 21-Feb-15 18:27:52 UTC
		$expected_start = '1424476800'; // Saturday, 21-Feb-15 00:00:00 UTC
		$expected_end = '1424563199'; // Saturday, 21-Feb-15 23:59:59 UTC

		$this->assertEquals($this->util->getDayStart($ts), $expected_start);
		$this->assertEquals($this->util->getDayEnd($ts), $expected_end);
	}

	public function testGetDayStartEndFromStringToFormat() {

		$time = '21-Feb-15 18:27:52';
		$format = 'Y-m-d H:i:s';
		$expected_start = '2015-02-21 00:00:00';
		$expected_end = '2015-02-21 23:59:59';

		$this->assertEquals($this->util->getDayStart($time, $format), $expected_start);
		$this->assertEquals($this->util->getDayEnd($time, $format), $expected_end);
	}

	public function testGetMonthStartEndFromTimestamp() {
		$ts = 1424543272; // Saturday, 21-Feb-15 18:27:52 UTC
		$expected_start = '1422748800'; // Sunday, 01-Feb-15 00:00:00 UTC
		$expected_end = '1425167999'; // Saturday, 28-Feb-15 23:59:59 UTC

		$this->assertEquals($this->util->getMonthStart($ts), $expected_start);
		$this->assertEquals($this->util->getMonthEnd($ts), $expected_end);
	}

	public function testGetMonthStartEndFromStringToFormat() {

		$time = '21-Feb-15 18:27:52';
		$format = 'Y-m-d H:i:s';
		$expected_start = '2015-02-01 00:00:00';
		$expected_end = '2015-02-28 23:59:59';

		$this->assertEquals($this->util->getMonthStart($time, $format), $expected_start);
		$this->assertEquals($this->util->getMonthEnd($time, $format), $expected_end);
	}

	public function testGetDayTime() {

		$ts = 1424545971; // Saturday, 21-Feb-15 19:12:51 UTC
		$expected_ts = '69171';
		$format = 'H:i:s';
		$expected_format = '19:12:51';

		$this->assertEquals($this->util->getTime($ts), $expected_ts);
		$this->assertEquals($this->util->getTime($ts, $format), $expected_format);
	}


	public function testGetTimeOfDay() {

		$day_ts = 1424545971; // Saturday, 21-Feb-15 19:12:51 UTC
		$event_ts = 1420110671; // Thursday, 01-Jan-15 11:11:11 UTC

		$expected = '1424517071'; // Saturday, 21-Feb-15 11:11:11 UTC

		$this->assertEquals($this->util->getTimeOfDay($event_ts, $day_ts), $expected);
	}

	/**
	 * @dataProvider providerGetDayOfWeek
	 */
	public function testGetDayOfWeek($ts, $expected) {
		$this->assertEquals($this->util->getDayOfWeek($ts), $expected);
	}

	public function providerGetDayOfWeek() {
		$ts = 1424692800; // Monday, 23-Feb-15 12:00:00 UTC
		return array(
			array($ts, Util::MONDAY),
			array($ts + Util::SECONDS_IN_A_DAY, Util::TUESDAY),
			array($ts + Util::SECONDS_IN_A_DAY * 2, Util::WEDNESDAY),
			array($ts + Util::SECONDS_IN_A_DAY * 3, Util::THURSDAY),
			array($ts + Util::SECONDS_IN_A_DAY * 4, Util::FRIDAY),
			array($ts + Util::SECONDS_IN_A_DAY * 5, Util::SATURDAY),
			array($ts + Util::SECONDS_IN_A_DAY * 6, Util::SUNDAY),
		);

	}

	public function testGetWeekOfMonth() {

		$ts1 = 1424602684; // Sunday, 22-Feb-15 10:58:04 UTC
		$ts2 = 1427803200; // Tuesday, 31-Mar-15 12:00:00 UTC

		$this->assertEquals($this->util->getWeekOfMonth($ts1), 4);
		$this->assertEquals($this->util->getWeekOfMonth($ts2), 6);
	}

	public function testGetWeekDayNthInMonth() {
		$ts1 = 1424602684; // Sunday, 22-Feb-15 10:58:04 UTC
		$ts2 = 1427803200; // Tuesday, 31-Mar-15 12:00:00 UTC

		$this->assertEquals($this->util->getWeekDayNthInMonth($ts1), 4);
		$this->assertEquals($this->util->getWeekdayNthInMonth($ts2), 5);
	}

	public function testIsOnSameDayOfWeek() {

		$ts1 = 1424520000; // Saturday, 21-Feb-15 12:00:00 UTC
		$ts2 = 1425124800; // Saturday, 28-Feb-15 12:00:00 UTC
		$ts3 = 1424606400; // Sunday, 22-Feb-15 12:00:00 UTC

		$this->assertTrue($this->util->isOnSameDayOfWeek($ts1, $ts2));
		$this->assertFalse($this->util->isOnSameDayOfMonth($ts1, $ts3));
	}

	public function testIsOnSameDayOfMonth() {

		$ts1 = 1424520000; // Saturday, 21-Feb-15 12:00:00 UTC
		$ts2 = 1426939200; // Saturday, 21-Mar-15 12:00:00 UTC
		$ts3 = 1425297600; // Monday, 02-Mar-15 12:00:00 UTC

		$this->assertTrue($this->util->isOnSameDayOfMonth($ts1, $ts2));
		$this->assertFalse($this->util->isOnSameDayOfMonth($ts1, $ts3));
	}

	public function testIsOnSameDayOfYear() {

		$ts1 = 1424520000; // Saturday, 21-Feb-15 12:00:00 UTC
		$ts2 = 1456056000; // Sunday, 21-Feb-16 12:00:00 UTC
		$ts3 = 1487592000; // Monday, 20-Feb-17 12:00:00 UTC

		$this->assertTrue($this->util->isOnSameDayOfYear($ts1, $ts2));
		$this->assertFalse($this->util->isOnSameDayOfYear($ts1, $ts3));
	}

	public function testsIsOnSameWeekDayOfMonth() {

		$ts1 = 1424520000; // Saturday, 21-Feb-15 12:00:00 UTC
		$ts2 = 1421508600; // Saturday, 17-Jan-15 15:30:00 UTC
		$ts3 = 1422113400; // Saturday, 24-Jan-15 15:30:00 UTC

		$this->assertTrue($this->util->isOnSameWeekDayOfMonth($ts1, $ts2));
		$this->assertFalse($this->util->isOnSameWeekDayOfMonth($ts1, $ts3));
	}

	public function testGetWeekdays() {

		$weekdays = array('Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun');
		$this->assertEquals($this->util->getWeekdays(), $weekdays);
	}
}
