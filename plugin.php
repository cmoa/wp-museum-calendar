<?php
/**
 * Plugin Name: WP Museum Calendar
 * Description: Bridge to WordPress calendar data
 * Author: Carney+Co.
 * Author URI: https://carney.co
 * Version: 4.2.1
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
