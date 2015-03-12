<?php

namespace Events\API;

use ElggPAM;
use ElggUser;
use InvalidParameterException;
use LoginException;

class PAM {

	/**
	 * @var Calendar
	 */
	protected $calendar;

	/**
	 * @var ElggUser
	 */
	protected $user;

	const IMPORTANCE = 'required';
	const POLICY = 'calendar';

	/**
	 * Constuctor
	 * @param Calendar $calendar Calendar
	 * @param ElggUser $user     User
	 * @throws InvalidParameterException
	 */
	public function __construct(Calendar $calendar, ElggUser $user) {

		if (!$calendar instanceof Calendar) {
			throw new InvalidParameterException('Invalid calendar');
		}

		if (!$user instanceof \ElggUser) {
			throw new InvalidParameterException('Invalid user');
		}

		$this->calendar = $calendar;
		$this->user = $user;
	}

	/**
	 * Validates PAM credentials
	 *
	 * @param array $credentials Credentials
	 * @return boolean
	 * @throws LoginException
	 */
	public static function handler(array $credentials = array()) {

		$calendar_guid = elgg_extract('calendar_guid', $credentials);
		$user_guid = elgg_extract('user_guid', $credentials);
		$token = elgg_extract('token', $credentials);

		$ia = elgg_set_ignore_access(true);
		$calendar = get_entity($calendar_guid);
		$user = get_entity($user_guid);
		elgg_set_ignore_access($ia);

		$pam = new PAM($calendar, $user);

		if (!has_access_to_entity($calendar, $user)) {
			throw new LoginException('User does not have access to this calendar');
		}

		if (!$calendar->getToken()) {
			throw new LoginException('Calendar does not allow remote access');
		}

		if (!$pam->validateToken($token)) {
			throw new LoginException('Invalid token');
		}

		return true;
	}

	/**
	 * Authenticate calendar and user
	 * @return bool
	 */
	public static function authenticate() {
		$credentials = array(
			'calendar_guid' => (int) get_input('guid'),
			'user_guid' => (int) get_input('u'),
			'token' => get_input('t'),
		);

		$pam = new ElggPAM(self::POLICY);
		$authenticated = $pam->authenticate($credentials);
		if (!$authenticated) {
			throw new LoginException($pam->getFailureMessage());
		} else {
			$user = get_entity($credentials['user_guid']);
			login($user);
		}
		return $authenticated;
	}

	/**
	 * Validates a token
	 * 
	 * @param string $token Token
	 * @return bool
	 */
	public function validateToken($token) {
		return ($token == $this->calendar->getUserToken($this->user->guid));
	}

}
