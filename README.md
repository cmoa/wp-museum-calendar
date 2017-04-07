# WP MUSEUM CALENDAR v1.2.4

The WP Museum Calendar plugin acts as an interface to and provide additional functionality for other popular calendar plugins.

WP Museum Calendar offers several benefits:

* Access to calendar and event information from templates
* Templating via mustache for simple presentation of common date/time formats
* API endpoints to query events by date ranges

## Adapters

Currently WP Museum Calendar works with the All-in-One Event Calendar by Time.ly. with plans to support other calendar plugins in the future.

## Installation

* Install and activate [All-in-One Event Calendar](https://wordpress.org/plugins/all-in-one-event-calendar/) by Time.ly
* Install and activate WP Museum Calendar

## Usage

Create your events via the installed plugin's interface. Then from a WordPress template, create a new `CMOA_Calendar` object:

```php
$calendar = new CMOA_Calendar();
```

#### Query calendar events between two dates

```php
$start = new DateTime('2017-01-01');
$end = new DateTime('2017-01-31');

// get_event_between_dates expects $start and $end as UNIX timestamps
$events = $calendar->get_event_between_dates($start->format('U'), $start->format('U'));

// Optionally pass an array of category/tag IDs to filter by
$filters['cat_ids'][] = 12;
$filters['tag_ids'][] = 34;

$filtered_events = $calendar->get_event_between_dates($start->format('U'), $start->format('U'), $filters);
```

#### Get a list of all upcoming events

```php
$upcoming_events = $calendar->all_upcoming_events($filters);
```

### List all event categories
```php
// Optionally pass a specific taxonomy
$categories = $calendar->calendar_categories($taxonomy);
```
