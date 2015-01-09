<?php

namespace Events\API;

if (get_subtype_id('object', 'calendar')) {
	update_subtype('object', 'calendar', __NAMESPACE__ . '\\Calendar');
} else {
	add_subtype('object', 'calendar', __NAMESPACE__ . '\\Calendar');
}

if (get_subtype_id('object', 'event')) {
	update_subtype('object', 'event', __NAMESPACE__ . '\\Event');
} else {
	add_subtype('object', 'event', __NAMESPACE__ . '\\Event');
}


$upgrade_version = elgg_get_plugin_setting('upgrade_version', PLUGIN_ID);
if (!$upgrade_version) {
	elgg_set_plugin_setting('upgrade_version', 20141215, PLUGIN_ID);
}