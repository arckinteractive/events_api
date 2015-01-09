# Events API

This plugin provides a data structure and convenience function for building an events application.
As each project's requirements tend to be different this is made as generic as extensible as possible
to act as a base back-end.  UI and integration should be performed by a project specific helper plugin.

All functions take arguments in terms of formatted dates 'Y-m-d' and times 'g:ia' to keep timezone calculations to a minimum.
Timezone functionality can be added and calculated client-side.

# Calendar

The calendar is an ElggObject with the subtype 'calendar'.  It is contained by another entity, usually a user
or a group.  The edit permissions are inherited by write access to the container.
The calendar holds events.  Events are linked to the calendar using relationships, this way it's possible
that an event can show on multiple calendars.

# Event

The event is an ElggObject with the subtype 'event'.  It is contained by the original calendar it is created on.
The event is attached to calendars via relationship.

# Actions

## events/edit

This action adds/edits an event.
Mandatory inputs are:

start_date = '2015-01-07' // Y-m-d
end_date = '2015-01-08' // Y-m-d
start_time = '12:00am' // g:ia
end_time = '1:00am' // g:ia

Optional Inputs

title = 'some text' // will default to elgg_echo('events:edit:title:placeholder')
description = 'some text'



## events/move

This action moves an event
Mandatory inputs are:

guid = int // the guid of the event
all_day = 1 | 0 // whether to set the event as all day
day_delta = int // how far forward/back to move the event
minute_delta = int // how far forward/back to move the event

after validation an event is triggered: 'events_api', 'event:move'
before the changes are made.  Returning false will stop the move.  Handlers returning
false are expected to provide their own error message.

Note this is a convenience action, the \Events\API\Event class has a move method with the same
inputs (except guid).


## events/resize

This action resizes an event (changes just the end date/time)
Mandatory inputs are:

guid = int // the guid of the event
day_delta = int // how far forward/back to move the end point
minute_delta = int // how far foward/back to move the end point

after validation an event is triggered: 'events_api', 'event:resize'
before the changes are made.  Returning false will stop the resize.  Handlers returning
false are expected to provide their own error message.

Note this is a convenience action, the \Events\API\Event class has a resize method with the same
inputs (except guid).