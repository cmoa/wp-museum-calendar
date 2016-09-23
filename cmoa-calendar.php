<?php

error_reporting(E_ERROR);
date_default_timezone_set('America/New_York');

class CMOA_Calendar {

  protected $_timezone = 'America/New_York';

  function __construct() {
    global $ai1ec_registry;
    $this->ai1ec_registry = $ai1ec_registry;
  }

  function get_calendar_timezone() {
    return $this->_timezone;
  }

  /*
	*  calendar_dates
	*
	*  This function will calculate the range of dates to be shown on the calendar
	*  based on the current date.
  *
	*  @type	function
	*  @date	03/21/16
	*  @since	1.0.0
	*
	*  @param	$date (DateTime from which to generate calendar)
	*  @return [[
  *    "date"=>string('Y-m-d'),
  *    "events" => [[id, post_id, start, end, post_content, post_title,
  *                  event_start, event_end, venue, country, address,
  *                  city, province, postal_code ]]
  *   ]] (array of objects containing date and events)
	*/

  function calendar_dates($date = null)
  {
    if (!isset($date)) $date = new DateTime('', new DateTimeZone($this->_timezone));
    $first_day = clone $date;
    $first_day->modify('first day of this month')->modify('last Sunday');
    $last_day = clone $date;
    $last_day->modify('last day of this month')->modify('next Sunday')->modify("-1 second");

    return $this->generate_event_list($first_day, $last_day);
  }

  /*
  *  calendar_range
  *
  *  This function provides the events occuring over a date range.
  *
  *  @type	function
  *  @date	03/21/16
  *  @since	1.0.0
  *
  *  @param	$start, $end (DateTime from which to generate calendar)
  *  @return [[
  *    "date"=>string('Y-m-d'),
  *    "events" => [[id, post_id, start, end, post_content, post_title,
  *                  event_start, event_end, venue, country, address,
  *                  city, province, postal_code ]]
  *   ]] (array of objects containing date and events)
  */

  function calendar_range($start, $end) {
    $start->setTime(0,0,0);
    $end->setTime(23,59,59);

    return $this->generate_event_list($start, $end);
  }

  /*
  *  events_in_category
  *
  *  This function provides the events occuring in a particular category
  *
  *  @type	function
  *  @date	04/12/16
  *  @since	1.0.0
  *
  *  @param	$slug (category slug)
  *  @return [
  *    "events" => [[id, post_id, start, end, post_content, post_title,
  *                  event_start, event_end, venue, country, address,
  *                  city, province, postal_code ]]
  *   ] (array of events)
  */

  function events_in_category($slug) {
    $start_date = new DateTime('now');
    $end_date = new DateTime('+1 month');
    $category = get_term_by('slug', $slug, 'events_categories');
    $filters['cat_ids'][] = $category->term_id;
    $events = $this->get_event_between_dates($start_date->format('U'), $end_date->format('U'), $filters);

    $unique_events = $this->unique_events($events);
    return $this->format_events($unique_events);
  }

  /*
  *  generate_event_list
  *
  *  This function will generate an array of events over a specified period
  *
  *  @type	function
  *  @date	03/22/16
  *  @since	1.0.0
  *
  *  @param	first_day (DateTime), last_day (DateTime)
  *  @return [[
  *    "date"=>string('Y-m-d'),
  *    "events" => [[id, post_id, start, end, post_content, post_title,
  *                  event_start, event_end, venue, country, address,
  *                  city, province, postal_code ]]
  *   ]] (array of objects containing date and events)
  */

  private function generate_event_list($first_day, $last_day) {
    $period = new DatePeriod(
      $first_day,
      new DateInterval('P1D'),
      $last_day
    );

    $events = $this->get_event_between_dates($first_day->getTimestamp(), $last_day->getTimestamp());

    $event_list = [];
    foreach ($period as $value) {
      // The start and end of `this` day
      $day_start = $value->getTimestamp();
      $day_end = $day_start + 60 * 60 * 24 - 1;

      $es = array_filter($events, function($event) use ($day_start, $day_end) {
        // The start and end of the `event`
        $start = $event->get('start')->format('U');
        $end = $event->get('end')->format('U');

        // If the event starts on this day, include it
        if ($start >= $day_start && $start <= $day_end) return true;

        // If the event ends on this day, include it
        // this is causing duplicate events to appear in the list... do we need it?
        // if ($end <= $day_end && $end >= $day_start) return true;

        // If the event runs through this day, include it
        if ($start <= $day_start && $end >= $day_end) return true;

        return false;
      });

      $es = array_values($es);
      if (!empty($es)) $es = $this->format_events($es);
      array_push($event_list, ["date" => $value->format('Y-m-d'), "events" => $es]);
    }

    return $event_list;
  }

  /*
	*  get_event_between_dates
	*
	*  This function will query the ai1ec events between two timestamps
  *
	*  @type	function
	*  @date	03/22/16
	*  @since	1.0.0
	*
	*  @param	start (timestamp), end (timestamp)
	*  @return query results [[post_id, post_content, post_title, start, end]]
	*/

  public function get_event_between_dates($start = 0, $end = 9999999999, $filters = []) {
    $start_time = new Ai1ec_Date_Time($this->ai1ec_registry, $start, $this->get_calendar_timezone());
    $end_time = new Ai1ec_Date_Time($this->ai1ec_registry, $end, $this->get_calendar_timezone());

    $search = $this->ai1ec_registry->get('model.search');
    $events = $search->get_events_between(
			$start_time,
			$end_time,
      $filters // array of category/tag ids
		);

    return $events;
  }

  /*
	*  format_events
	*
	*  This function accepts an array of events and returns with desired structure
  *
	*  @type	function
	*  @date	03/22/16
	*  @since	1.0.0
	*
	*  @param	$events (array of events)
	*  @return events array with desired structure
	*/

  private function format_events($events) {
    $event_list = [];
    $tz = new DateTimeZone($this->_timezone);

    foreach ($events as $event) {
      $post = $event->get('post');
      $event_instances = $this->event_instances($post->ID);

      $created_date = NULL;
      if (isset($post->post_date)) {
        $created_date = str_replace(" ", "T", $post->post_date);
      }

      // should we exclude some of these fields to decrease the payload size?
      $e = [
        'id' => $post->ID,
        'categories' => wp_get_post_terms($post->ID, 'events_categories'),
        'tags' => wp_get_post_terms($post->ID, 'events_tags'),
        'name' => $post->post_title,
        'url' => get_permalink($post),
        'excerpt' => get_the_excerpt($post),
        'description' => $post->post_content,
        'start' => $event->get('start')->format('c'),
        'end' => $event->get('end')->format('c'),
        'start_date' => DateTime::createFromFormat("U", $event_instances[0]->start)->setTimezone($tz)->format('c'),
        'end_date' => DateTime::createFromFormat("U", end($event_instances)->end)->setTimezone($tz)->format('c'),
        'all_day' => $event->get('allday'),
        'created_date' => $created_date,
        'duration' => $this->friendly_time_between($event->get('start')->format('U'), $event->get('end')->format('U')),
        'locations' => wp_get_post_terms($post->ID, 'locations'),
        'address' => $event->get('address'),
        'status' => $event->get('event_status') ?: 'confirmed',
        'recurrence' => $event->get('recurrence_rules'),
        'images' => [
          'banner' => get_field('banner_image', $post),
          'featured' => wp_get_attachment_image_src(get_post_thumbnail_id($post), 'large'),
          'sponsor' => get_field('sponsor_images', $post)
        ],
        'sponsorship_details' => get_field('sponsorship_details', $post),
        'building_location' => $event->get('venue'),
        'ticketing' => [
           'availability' => get_field('ticket_availability', $post),
           'cost' => $event->get('ticket_cost'),
           'url' => $event->get('ticket_url')
        ],
        'registration' => get_field('registration_information')
      ];

      $event_list[] = $e;
    }

    usort($event_list, array('self', 'sort_events_by_hour'));

    return $event_list;
  }

  /*
	*  sort_events_by_hour
	*
	*  This function sorts an array of events by formatting the start date to hours
  *
	*  @type	function
	*  @date	03/25/16
	*  @since	1.0.0
	*
	*  @param	$e1, $e2 (associative arrays)
	*  @return 0 if events are on the same hour, -1 if the $e1 is first, 1 if $e2 is first
	*/

  private static function sort_events_by_hour($e1, $e2) {
    $e1_date = DateTime::createFromFormat("Y-m-d\TH:i:sP", $e1['start_date']);
    $e2_date = DateTime::createFromFormat("Y-m-d\TH:i:sP", $e2['start_date']);

    if ($e1_date->format('YmdG') == $e2_date->format('YmdG')) {
      return 0;
    }
    return ($e1_date->format('YmdG') < $e2_date->format('YmdG')) ? -1 : 1;
  }

  /*
  *  friendly_time_between
  *
  *  This function returns the time between two dates in a readable format
  *
  *  @type	function
  *  @date	03/21/16
  *  @since	1.0.0
  *
  *  @param	$start, $end  (timestamps)
  *  @return (string)
  */

  private function friendly_time_between($start, $end) {
    if (!isset($start) || !isset($end)) return "0 seconds";

    $day = 60 * 60 * 24;
    $hour = 60 * 60;
    $minute = 60;

    $time = $end - $start;
    $days = floor($time / $day);
    $time -= $days * $day;
    $hours = floor($time / $hour);
    $time -= $hours * $hour;
    $minutes = floor($time / $minute);
    $time -= $minutes * $minute;
    $seconds = $time;

    $duration = "";
    if ($days > 0) {
      $duration .= "$days";
      $duration .= ($days > 1) ? " days " : " day ";
    }
    if ($hours > 0) {
      $duration .= "$hours";
      $duration .= ($hours > 1) ? " hours " : " hour ";
    }
    if ($minutes > 0) {
      $duration .= "$minutes";
      $duration .= ($minutes > 1) ? " minutes " : " minute ";
    }
    if ($seconds > 0) {
      $duration .= "$seconds";
      $duration .= ($secounds > 1) ? " seconds " : " second ";
    }

    return trim($duration);
  }

  /*
  *  calendar_categories
  *
  *  This function returns all of the events_categories taxonomy terms
  *
  *  @type	function
  *  @date	08/25/16
  *  @since	1.0.1
  *
  *  @return (array)
  */

  public function calendar_categories() {
    $categories = get_terms(['taxonomy' => 'events_categories', 'hide_empty' => false]);
    $visible_categories = array_filter($categories, function($category) {
      return !get_field('hidden', $category);
    });

    array_walk($visible_categories, function(&$category, $key) {
      $category->name = wp_specialchars_decode($category->name);
    });

    return array_values($visible_categories);
  }

  /*
  *  event_instances
  *
  *  This function returns all event instances for a given post
  *
  *  @type	function
  *  @date	08/25/16
  *  @since	1.0.0
  *
  *  @param	$post_id  (int)
  *  @return (array)
  */

  public function event_instances($post_id) {
    global $wpdb;
    $instances_table_name = $wpdb->prefix . 'ai1ec_event_instances';

    $query = "SELECT id, start, end ".
      "FROM {$instances_table_name} " .
      "WHERE post_id = %d";

    $args = array(
      $post_id
    );

    $results = $wpdb->get_results($wpdb->prepare($query, $args));
    return $results;
	}

  /*
  *  all_upcoming_events
  *
  *  This function returns all upcoming events
  *
  *  @type	function
  *  @date	08/25/16
  *  @since	1.0.0
  *
  *  @param	$ai1ec_registry (All-in-One Event Calendar plugin registry object)
  *  @return (array)
  */

  public function all_upcoming_events($filters = []) {
    $start_date = new DateTime('now');
    $end_date = new DateTime('+100 years');

    return $this->get_event_between_dates($start_date->format('U'), $end_date->format('U'), $filters);
  }

  /*
  *  unique_events
  *
  *  This function returns unique events for a given post
  *
  *  @type	function
  *  @date	09/13/16
  *  @since	1.0.0
  *
  *  @param	$events (Array of All-in-One Event objects)
  *  @return (array)
  */

  public function unique_events($events) {
    $unique_events = array_reduce($events, function($arr, $event) {
      if(!isset($arr[$event->get('post_id')])) {
        $arr[$event->get('post_id')] = $event;
      }
      return $arr;
    }, []);

    return array_values($unique_events);
  }

}

/* Routes */

/*
*  calendar_dates_api
*
*  This function is called from the API route and passes the year and month
*  for the desired calendar.
*
*  @type	function
*  @date	03/21/16
*  @since	1.0.0
*
*  @param	$args (year and month parsed from URL params)
*  @return JSON object representing event
*/

function calendar_dates_api($args) {
  $cal = new CMOA_Calendar();
  $year = (isset($args['year'])) ? $args['year'] : date("Y");
  $month = (isset($args['month'])) ? $args['month'] : date("m");
  $date = new DateTime("{$year}-{$month}-01", new DateTimeZone($cal->get_calendar_timezone()));
  return $cal->calendar_dates($date);
}

add_action('rest_api_init', function () {
  register_rest_route('events/v1', '/year/(?P<year>\d+)/month/(?P<month>\d+)', array(
    'methods' => 'GET',
    'callback' => 'calendar_dates_api'
  ));
});

/*
*  calendar_range_api
*
*  This function is called from the API route and passes the start and end dates
*  in Ymd format for calendar events.
*
*  @type	function
*  @date	03/21/16
*  @since	1.0.0
*
*  @param	$args (start and end parsed from URL params in Ymd format)
*  @return JSON object representing event
*/

function calendar_range_api($args) {
  $cal = new CMOA_Calendar();
  $start = (DateTime::createFromFormat('Ymd', $args['start'])) ?: new DateTime("now", new DateTimeZone($cal->get_calendar_timezone()));
  $end = (DateTime::createFromFormat('Ymd', $args['end'])) ?: new DateTime("+30 days", new DateTimeZone($cal->get_calendar_timezone()));
  return $cal->calendar_range($start, $end);
}

add_action('rest_api_init', function () {
  register_rest_route('events/v1', '/start/(?P<start>\d+)/end/(?P<end>\d+)', array(
    'methods' => 'GET',
    'callback' => 'calendar_range_api'
  ));
});


/*
*  calendar_categories_api
*
*  This function is called from the API route and returns the current event categories
*
*  @type	function
*  @date	07/27/16
*  @since	1.0.0
*
*  @return JSON object representing categories
*/

function calendar_categories_api() {
  $cal = new CMOA_Calendar();
  return $cal->calendar_categories();
}

add_action('rest_api_init', function () {
  register_rest_route('events/v1', '/categories/', array(
    'methods' => 'GET',
    'callback' => 'calendar_categories_api'
  ));
});


function calendar_category_events_api($args) {
  $cal = new CMOA_Calendar();
  return $cal->events_in_category($args['slug']);
}

add_action('rest_api_init', function () {
  register_rest_route('events/v1', '/categories/(?P<slug>[\w-]+)', array(
    'methods' => 'GET',
    'callback' => 'calendar_category_events_api'
  ));
});

?>
