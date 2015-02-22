<?php

namespace Events\API;

elgg_make_sticky_form('calendar/edit');

$user = elgg_get_logged_in_user_entity();

$guid = get_input('guid');
$calendar = get_entity($guid);

$container_guid = get_input('container_guid');
if (!$container_guid) {
	$container_guid = $user->guid;
}
$container = get_entity($container_guid);

if (!$container || !$container->canWriteToContainer(0, 'object', Calendar::SUBTYPE)) {
	register_error(elgg_echo('events:error:container_permissions'));
	forward(REFERER);
}

$title = htmlspecialchars(get_input('title', elgg_echo('events:calendar:edit:title:placeholder')), ENT_QUOTES, 'UTF-8');
$description = get_input('description');
$tags = get_input('tags', '');
$access_id = get_input('access_id', get_default_access());

if (!$calendar) {
	$calendar = new Calendar();
	$calendar->owner_guid = $user->guid;
	$calendar->container_guid = $user->guid;
}

$calendar->title = $title;
$calendar->description = $description;
$calendar->access_id = $access_id;
$calendar->tags = string_to_tag_array($tags);

if ($calendar->save()) {
	elgg_clear_sticky_form('calendar/edit');
	system_message(elgg_echo('events:calendar:edit:success'));
	forward($calendar->getURL());
} else {
	register_error(elgg_echo('events:calendar:edit:error'));
	forward(REFERER);
}