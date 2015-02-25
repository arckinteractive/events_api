<?php

$english = array(
	'events:edit:title:placeholder' => 'Untitled Event',
	'events:error:start_end_date' => "End date/time must be after the start date/time",
	'events:error:start_end_date:invalid_format' => "Invalid format for date/time",
	'events:error:save' => "Could not save event information",
	'events:error:container_permissions' => "You do not have sufficient permissions for this action",
	'events:success:save' => "Event Saved",
	'events:error:invalid:guid' => "Invalid Event",
	'events:error:invalid:deltas' => "Invalid move parameters",
	'event_api:event:updated' => "Your event has been updated",
	'events:success:deleted' => "Event has been deleted",
	'events:error:public_calendar_delete' => 'You are trying to delete a public calendar, which is not allowed.
		For better access control, use visibility settings on your custom calendars and individual events',

	'events:error:no_public_for_orphans' => 'We can not find a public calendar to move the events to',
	
	'events:wd:Mon' => 'Monday',
	'events:wd:Tue' => 'Tuesday',
	'events:wd:Wed' => 'Wednesday',
	'events:wd:Thu' => 'Thursday',
	'events:wd:Fri' => 'Friday',
	'events:wd:Sat' => 'Saturday',
	'events:wd:Sun' => 'Sunday',

	'events:calendar' => 'Calendar',
	'events:calendar:display_name' => '%s\'s Calendar',
	'events:calendar:edit:title:placeholder' => 'Untitled Calendar',
	'events:calendar:edit:success' => 'Calendar has been saved',
	'events:calendar:edit:error' => 'An error occurred while saving the calendar',
	'events:calendar:error:invalid:guid' => "Invalid Calendar",
	'events:calendar:delete:success' => "Calendar has been deleted and events moved to your default calendar",
	'events:calendar:delete:error' => "Calendar could not be deleted",
	'events:calendar:add_event:error:invalid_guid' => "Invalid Calendar or Event",
	'events:calendar:add_event:error:noaccess' => "You are not allowed to add events to this calendar",
	'events:calendar:add_event:already_on' => 'Event is already on the calendar',
	
);

add_translation("en", $english);