<?php

namespace Events\API;

/**
 * Wrapper for PAMHandler methods
 * 
 * @todo can be removed once is_string($handler) requirement is lifted
 * 
 * @param array $credentials Credentials
 * @return bool
 */
function pam_handler(array $credentials = array()) {
	return PAM::handler($credentials);
}