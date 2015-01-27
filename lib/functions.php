<?php

namespace Events\API;

/**
 * array options
 * 'start' = timestamp of the start date/time
 * 'end' = timestamp of the end date/time
 * 'frequency' = string - the type of repeat
 * 'after' = the number of times the event shows
 * 'delta' = the length of the event in seconds
 * 
 * @param array $params
 * @return type
 */
function get_repeat_end_timestamp($params) {
	switch ($params['frequency']) {
		case 'daily':
			$interval = 60*60*24;
			$end_timestamp = $params['end'] + ($interval * ($params['after'] - 1));
			break;
		case 'weekday':
			$interval = 60*60*24;
			$end = 1;
			$time = $params['start'];
			while ($end < $params['after']) {
				$time += $interval;
				
				$day = date('D', $time);
				if (!in_array($day, array('Sat', 'Sun'))) {
					$end++;
				}
			}
			$end_timestamp = $time + $params['delta'];
			break;
		case 'dailymwf':
			$interval = 60*60*24;
			$end = 1;
			$time = $params['start'];
			while ($end < $params['after']) {
				$time += $interval;
				
				$day = date('D', $time);
				if (in_array($day, array('Mon', 'Wed', 'Fri'))) {
					$end++;
				}
			}
			$end_timestamp = $time + $params['delta'];
			break;
		case 'dailytt':
			$interval = 60*60*24;
			$end = 0;
			$time = $params['start'];
			while ($end < $params['after']) {
				$time += $interval;
				
				$day = date('D', $time);
				if (in_array($day, array('Tue', 'Thu'))) {
					$end++;
				}
			}
			$end_timestamp = $time + $params['delta'];
			break;
		case 'weekly':
			$interval = 60*60*24*7;
			$end_timestamp = $params['end'] + ($interval * ($params['after'] - 1));
			break;
		case 'monthly':
			$end_month = date('n', $params['end']);

			// note that if it exceeds the year it still calculates correctly
			// eg. month == 13 will be calculated as Jan of next year
			$end_timestamp = mktime(
					date('H', $params['end']),
					date('i', $params['end']),
					date('s', $params['end']),
					$end_month + $params['after'] - 1,
					date('j', $params['end']),
					date('Y', $params['end'])
				);
			break;
		case 'yearly':
			$end_year = date('Y', $params['end']);
			
			$end_timestamp = mktime(
					date('H', $params['end']),
					date('i', $params['end']),
					date('s', $params['end']),
					date('n', $params['end']),
					date('j', $params['end']),
					$end_year + $params['after'] - 1
				);
			break;
	}
	
	return $end_timestamp;
}