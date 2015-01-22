<?php

namespace Events\API;

function get_repeat_end_timestamp($params) {
	switch ($params['frequency']) {
		case 'daily':
			$interval = 60*60*24;
			$end_timestamp = $params['end'] + ($interval * $params['after']);
			break;
	}
	
	return $end_timestamp;
}