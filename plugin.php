<?php
/**
 * Plugin Name: WP Museum Calendar
 * Description: Bridge to WordPress calendar data
 * Author: Carney+Co.
 * Author URI: http://carney.co
 * Version: 1.2.5
 */

/**
 *  CMOA Calendar class
 */
if ( ! class_exists( 'CMOA_Calendar' ) ) {
	require_once dirname( __FILE__ ) . '/cmoa-calendar.php';
}

if ( ! class_exists( 'CMOA_Event' ) ) {
	require_once dirname( __FILE__ ) . '/cmoa-event.php';
}


?>
