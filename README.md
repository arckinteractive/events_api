# Events API

This plugin provides a data structure and convenience function for building an events application.
As each project's requirements tend to be different this is made as generic as extensible as possible
to act as a base back-end.  UI and integration should be performed by a project specific helper plugin.

All functions take arguments in terms of formatted dates 'Y-m-d' and times 'g:ia' to keep timezone calculations to a minimum.
Timezone functionality can be added and calculated client-side.

## Calendar

The calendar is an ElggObject with the subtype 'calendar'.  It is contained by another entity, usually a user
or a group.  The edit permissions are inherited by write access to the container.
The calendar holds events.  Events are linked to the calendar using relationships, this way it's possible
that an event can show on multiple calendars.

Each user and group has one "public" calendar. This calendar is automatically created, and can not be deleted or edited
by non-admin users. All new events that do not explicitly define a calendar, will be added to "public" calendar.

Users can create other calendars with custom visiblity/access settings. Additionally, public calendar can contain
non-public events, which will only be visible to viewers that have permissions to see them.

## Event

The event is an ElggObject with the subtype 'event'.  It is contained by another entity and is attached to calendars via a relationship.

## Actions

### ```action('events/edit')```

This action adds/edits an event.

Required inputs are:
- ```start_date```	STR '2015-01-07' // Y-m-d
- ```end_date```	STR '2015-01-08' // Y-m-d
- ```start_time```	STR '12:00am' // g:ia
- ```end_time```	STR '1:00am' // g:ia

Optional inputs are:
- ```guid```		INT
- ```title```		STR (will default to ```elgg_echo('events:edit:title:placeholder')```)
- ```description``` STR



### ```action('events/move')```

This action moves an event

Required inputs are:
- ```guid```			INT the guid of the event
- ```all_day```			BOOL whether to set the event as all day
- ```day_delta```		INT how far forward/back to move the event
- ```minute_delta```	INT how far forward/back to move the event

After validation an event is triggered: ```'events_api', 'event:move'``` before the changes are made.
Returning false will stop the move. Handlers returning false are expected to provide their own error message.

Note this is a convenience action, see ```\Events\API\Event::move()``` for an equivalent method


### ```action('events/resize')```

This action resizes an event (changes just the end date/time)

Required inputs are:
- ```guid```			INT the guid of the event
- ```day_delta```		INT how far forward/back to move the end point
- ```minute_delta```	INT how far foward/back to move the end point

After validation an event is triggered: ```'events_api', 'event:resize'```before the changes are made.
Returning false will stop the resize. Handlers returning false are expected to provide their own error message.

Note this is a convenience action, see ```\Events\API\Event::resize()``` for an equivalent method


## Installation / Tests

If downloading from GitHub:

```sh
# install dependencies (without dev)
composer install --no-dev
```

```sh
# install dependencies (including dev)
composer install

# run tests
vendor/bin/phpunit
```
