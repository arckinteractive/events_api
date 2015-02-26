<?php

namespace Events\API;

use PHPUnit_Framework_TestCase;

class EventTest extends PHPUnit_Framework_TestCase {

	/**
	 * @var Event
	 */
	protected $event;

	public function setUp() {
		$this->event = new Event;
	}

	public function testGetStartTimesForDailyFrequency() {

		$this->event->start_timestamp = 1424705400; // Monday, 23-Feb-15 15:30:00 UTC
		$this->event->repeat = true;
		$this->event->repeat_frequency = Util::FREQUENCY_DAILY;
		$this->event->repeat_end_timestamp = 1424995199; // Thursday, 26-Feb-15 23:59:59 UTC

		$range_start = 1424390400; // Friday, 20-Feb-15 00:00:00 UTC
		$range_end = 1424995200; // Friday, 27-Feb-15 00:00:00 UTC

		$start_times = array(
			1424705400, // monday
			1424791800, // tuesday
			1424878200, // wednesday
			1424964600, // thursday
		);

		$this->assertEquals($this->event->getStartTimes($range_start, $range_end), $start_times);
	}

	public function testGetStartTimesForWeekdayFrequency() {

		$this->event->start_timestamp = 1424705400; // Monday, 23-Feb-15 15:30:00 UTC
		$this->event->repeat = true;
		$this->event->repeat_frequency = Util::FREQUENCY_WEEKDAY;
		$this->event->repeat_end_timestamp = 1425297600; // Monday, 02-Mar-15 12:00:00 UTC

		$range_start = 1424390400; // Friday, 20-Feb-15 00:00:00 UTC
		$range_end = 1425081599; // Friday, 27-Feb-15 23:59:59 UTC

		$start_times = array(
			1424705400, // monday
			1424791800, // tuesday
			1424878200, // wednesday
			1424964600, // thursday
			1425051000, // friday
		);

		$this->assertEquals($this->event->getStartTimes($range_start, $range_end), $start_times);
	}

	public function testGetStartTimesForWeeklyFrequency() {

		$this->event->start_timestamp = 1424705400; // Monday, 23-Feb-15 15:30:00 UTC
		$this->event->repeat = true;
		$this->event->repeat_frequency = Util::FREQUENCY_WEEKLY;
		$this->event->repeat_end_timestamp = 1427068800; // Monday, 23-Mar-15 00:00:00 UTC

		$range_start = 1420070400; // Thursday, 01-Jan-15 00:00:00 UTC
		$range_end = 1426680000; // Wednesday, 18-Mar-15 12:00:00 UTC

		$start_times = array(
			1424705400, // Monday, 23-Feb-15 15:30:00 UTC
			1425310200, // Monday, 02-Mar-15 15:30:00 UTC
			1425915000, // Monday, 09-Mar-15 15:30:00 UTC
			1426519800, // Monday, 16-Mar-15 15:30:00 UTC
		);

		$this->assertEquals($this->event->getStartTimes($range_start, $range_end), $start_times);
	}

	public function testGetStartTimesForWeeklyFrequencyWithSetWeekdays() {

		$this->event->start_timestamp = 1424705400; // Monday, 23-Feb-15 15:30:00 UTC
		$this->event->repeat = true;
		$this->event->repeat_frequency = Util::FREQUENCY_WEEKLY;
		$this->event->repeat_end_timestamp = 1426075200; // Wednesday, 11-Mar-15 12:00:00 UTC
		$this->event->repeat_weekly_days = array(Util::MONDAY, Util::THURSDAY);

		$range_start = 1420070400; // Thursday, 01-Jan-15 00:00:00 UTC
		$range_end = 1426680000; // Wednesday, 18-Mar-15 12:00:00 UTC

		$start_times = array(
			1424705400, // Monday, 23-Feb-15 15:30:00 UTC
			1424964600, // Thursday, 26-Feb-15 15:30:00 UTC
			1425310200, // Monday, 02-Mar-15 15:30:00 UTC
			1425569400, // Thursday, 05-Mar-15 15:30:00 UTC
			1425915000, // Monday, 09-Mar-15 15:30:00 UTC
		);

		$this->assertEquals($this->event->getStartTimes($range_start, $range_end), $start_times);
	}

	public function testGetTimesForMonthlyFrequencyRepeatByDayOfMonth() {

		$this->event->start_timestamp = 1424705400; // Monday, 23-Feb-15 15:30:00 UTC
		$this->event->repeat = true;
		$this->event->repeat_frequency = Util::FREQUENCY_MONTHLY;
		$this->event->repeat_monthly_by = Util::REPEAT_MONTHLY_BY_DATE;
		$this->event->repeat_end_timestamp = 1434801600; // Saturday, 20-Jun-15 12:00:00 UTC

		$range_start = 1420070400; // Thursday, 01-Jan-15 00:00:00 UTC
		$range_end = 1440331200; // Sunday, 23-Aug-15 12:00:00 UTC

		$start_times = array(
			1424705400, // Monday, 23-Feb-15 15:30:00 UTC
			1427124600, // Monday, 23-Mar-15 15:30:00 UTC
			1429803000, // Thursday, 23-Apr-15 15:30:00 UTC
			1432395000, // Saturday, 23-May-15 15:30:00 UTC
		);

		$this->assertEquals($this->event->getStartTimes($range_start, $range_end), $start_times);
	}

	public function testGetTimesForMonthlyFrequencyRepeatByDayOfWeek() {

		$this->event->start_timestamp = 1424705400; // Monday, 23-Feb-15 15:30:00 UTC
		$this->event->repeat = true;
		$this->event->repeat_frequency = Util::FREQUENCY_MONTHLY;
		$this->event->repeat_monthly_by = Util::REPEAT_MONTHLY_BY_DAY_OF_WEEK;
		$this->event->repeat_end_timestamp = 1434801600; // Saturday, 20-Jun-15 12:00:00 UTC

		$range_start = 1420070400; // Thursday, 01-Jan-15 00:00:00 UTC
		$range_end = 1440331200; // Sunday, 23-Aug-15 12:00:00 UTC

		$start_times = array(
			1424705400, // Monday, 23-Feb-15 15:30:00 UTC
			1427124600, // Monday, 23-Mar-15 15:30:00 UTC
			1430148600, // Monday, 27-Apr-15 15:30:00 UTC
			1432567800, // Monday, 25-May-15 15:30:00 UTC
		);

		$this->assertEquals($this->event->getStartTimes($range_start, $range_end), $start_times);
	}

	public function testGetTimesForYearlyFrequency() {

		$this->event->start_timestamp = 1424705400; // Monday, 23-Feb-15 15:30:00 UTC
		$this->event->repeat = true;
		$this->event->repeat_frequency = Util::FREQUENCY_YEARLY;
		$this->event->repeat_end_timestamp = 1577880000; // Wednesday, 01-Jan-20 12:00:00 UTC

		$range_start = 946728000; // Saturday, 01-Jan-00 12:00:00 UTC
		$range_end = 1735732800; // Wednesday, 01-Jan-25 12:00:00 UTC

		$start_times = array(
			1424705400, // Monday, 23-Feb-15 15:30:00 UTC
			1456241400, // Tuesday, 23-Feb-16 15:30:00 UTC
			1487863800, // Thursday, 23-Feb-17 15:30:00 UTC
			1519399800, // Friday, 23-Feb-18 15:30:00 UTC
			1550935800, // Saturday, 23-Feb-19 15:30:00 UTC
		);

		$this->assertEquals($this->event->getStartTimes($range_start, $range_end), $start_times);
	}

	/**
	 * @dataProvider providerCalculateEndAfterTimestampForDailyFrequency
	 */
	public function testCalculateEndAfterTimestampForDailyFrequency($start, $end, $occurrences, $at_event_end, $expected) {

		$this->event->repeat_frequency = Util::FREQUENCY_DAILY;
		$this->event->start_timestamp = $start;
		$this->event->end_timestamp = $end;
		$this->event->end_delta = $end - $start;

		$this->assertEquals($this->event->calculateEndAfterTimestamp($occurrences, null, $at_event_end), $expected);
	}

	public function providerCalculateEndAfterTimestampForDailyFrequency() {

		return array(
			array(1424952000, 1424955600, 0, true, 1424955600),
			array(1424952000, 1424955600, 1, false, 1424952000),
			array(1424952000, 1424955600, 3, false, 1425124800),
		);
	}

	public function testCalculateEndAfterTimestampForWeeklyFrequency() {

		$this->event->repeat_frequency = Util::FREQUENCY_WEEKLY;
		$this->event->start_timestamp = 1424952000; // Thursday, 26-Feb-15 12:00:00 UTC
		$this->event->end_timestamp = 1424955600;
		$this->event->end_delta = 3600; // 1 hour
		$this->event->repeat_weekly_days = array(Util::MONDAY, Util::FRIDAY, Util::SUNDAY);

		$expected = 1425301200; // Monday, 02-Mar-15 13:00:00 UTC
		
		$this->assertEquals($this->event->calculateEndAfterTimestamp(3, null, true), $expected);
	}

	public function testCalculateEndAfterTimestampForMonthlyFrequencyRepeatedByDate() {

		$this->event->repeat_frequency = Util::FREQUENCY_MONTHLY;
		$this->event->repeat_monthly_by = Util::REPEAT_MONTHLY_BY_DATE;
		$this->event->start_timestamp = 1424952000; // Thursday, 26-Feb-15 12:00:00 UTC
		$this->event->end_timestamp = 1424955600;
		$this->event->end_delta = 3600; // 1 hour
		$this->event->repeat_weekly_days = array(Util::MONDAY, Util::FRIDAY, Util::SUNDAY);

		$expected = 1430053200; // Sunday, 26-Apr-15 13:00:00 UTC

		$this->assertEquals($this->event->calculateEndAfterTimestamp(3, null, true), $expected);
	}

	public function testCalculateEndAfterTimestampForMonthlyFrequencyRepeatedByDayOfWeek() {

		$this->event->repeat_frequency = Util::FREQUENCY_MONTHLY;
		$this->event->repeat_monthly_by = Util::REPEAT_MONTHLY_BY_DAY_OF_WEEK;
		$this->event->start_timestamp = 1424952000; // Thursday, 26-Feb-15 12:00:00 UTC
		$this->event->end_timestamp = 1424955600;
		$this->event->end_delta = 3600; // 1 hour
		$this->event->repeat_weekly_days = array(Util::MONDAY, Util::FRIDAY, Util::SUNDAY);

		$expected = 1429794000; // Thursday, 23-Apr-15 13:00:00 UTC

		$this->assertEquals($this->event->calculateEndAfterTimestamp(3, null, true), $expected);
	}

	public function testCalculateEndAfterTimestampForYearlyFrequency() {

		$this->event->repeat_frequency = Util::FREQUENCY_YEARLY;
		$this->event->start_timestamp = 1424952000; // Thursday, 26-Feb-15 12:00:00 UTC
		$this->event->end_timestamp = 1424955600;
		$this->event->end_delta = 3600; // 1 hour
		$this->event->repeat_weekly_days = array(Util::MONDAY, Util::FRIDAY, Util::SUNDAY);

		$from_timestamp = 1428249600; // Sunday, 05-Apr-15 16:00:00 UTC
		$expected = 1519646400; // Monday, 26-Feb-18 12:00:00 UTC

		$this->assertEquals($this->event->calculateEndAfterTimestamp(3, $from_timestamp, false), $expected);
	}

	public function testIsValidStartTime() {

		$this->event->repeat_frequency = Util::FREQUENCY_MONTHLY;
		$this->event->repeat_monthly_by = Util::REPEAT_MONTHLY_BY_DATE;
		$this->event->start_timestamp = 1424952000; // Thursday, 26-Feb-15 12:00:00 UTC
		$this->event->end_timestamp = 1424955600;
		$this->event->end_delta = 3600; // 1 hour
		$this->event->repeat_weekly_days = array(Util::MONDAY, Util::FRIDAY, Util::SUNDAY);
		$this->event->repeat = true;

		$valid = 1430049600; // Sunday, 26-Apr-15 12:00:00 UTC
		$invalid = 1430049660;
		
		$this->assertTrue($this->event->isValidStartTime($valid));
		$this->assertFalse($this->event->isValidStartTime($invalid));

	}
}
