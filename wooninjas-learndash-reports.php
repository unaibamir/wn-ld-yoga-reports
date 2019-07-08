<?php

/**
 * Plugin Name: WooNinjas LearnDash Reports
 * Description: WooNinjas LearnDash Reports
 * Plugin URI: https://wooninjas.com
 * Author: WooNinjas
 * Author URI: https://wooninjas.com
 * Version: 1.0.0
 * License: GPL2
 */

if ( !defined ( 'ABSPATH' ) ) exit;

//register_activation_hook( __FILE__, ['WooLearnDashReports', 'activation' ] );
//register_deactivation_hook( __FILE__, ['WooLearnDashReports', 'deactivation' ] );

/**
 * Class LifterLMS_BadgeOS
 */
class WooLearnDashReports {
    const VERSION = '1.0';

    /**
     * @var self
     */
    private static $instance = null;

    /**
     * @since 1.0
     * @return $this
     */
    public static function instance() {

        if ( is_null( self::$instance ) && ! ( self::$instance instanceof WooLearnDashReports ) ) {
            self::$instance = new self;

            self::$instance->setup_constants();
            self::$instance->includes();
            self::$instance->hooks();
        }

        return self::$instance;
    }

    /**
     * Activation function hook
     *
     * @since 1.0
     * @return void
     */
    public function activation() {
    }

    /**
     * Deactivation function hook
     *
     * @since 1.0
     * @return void
     */
    public function deactivation() {
    }

    /**
     * Upgrade function hook
     *
     * @since 1.0
     * @return void
     */
    public function upgrade() {
    }

    /**
     * Setup Constants
     */
    private function setup_constants() {

        // Directory
        define( 'WOO_LEARNDASH_REPORTS_LANG', 'lifter_lms_bos' );
        define( 'WOO_LEARNDASH_REPORTS_DIR', plugin_dir_path ( __FILE__ ) );
        define( 'WOO_LEARNDASH_REPORTS_DIR_FILE', WOO_LEARNDASH_REPORTS_DIR . basename ( __FILE__ ) );
        define( 'WOO_LEARNDASH_REPORTS_INCLUDES_DIR', trailingslashit ( WOO_LEARNDASH_REPORTS_DIR . 'includes' ) );
        define( 'WOO_LEARNDASH_REPORTS_BASE_DIR', plugin_basename(__FILE__));

        // URLS
        define( 'WOO_LEARNDASH_REPORTS_URL', trailingslashit ( plugins_url ( '', __FILE__ ) ) );
        define( 'WOO_LEARNDASH_REPORTS_ASSETS_URL', trailingslashit ( WOO_LEARNDASH_REPORTS_URL . 'assets' ) );
    }

    /**
     * Include Required Files
     */
    private function includes() {

        if( file_exists( WOO_LEARNDASH_REPORTS_INCLUDES_DIR . 'admin.php' ) ) {
            require_once ( WOO_LEARNDASH_REPORTS_INCLUDES_DIR . 'admin.php' );
        }

        if( file_exists( WOO_LEARNDASH_REPORTS_INCLUDES_DIR . 'users.php' ) ) {
            require_once ( WOO_LEARNDASH_REPORTS_INCLUDES_DIR . 'users.php' );
        }

        if( file_exists( WOO_LEARNDASH_REPORTS_INCLUDES_DIR . 'vendor/autoload.php' ) ) {
            require_once ( WOO_LEARNDASH_REPORTS_INCLUDES_DIR . 'vendor/autoload.php' );
        }
    }

    private function hooks() {
        add_filter( 'plugin_action_links_'.WOO_LEARNDASH_REPORTS_BASE_DIR, [ $this, 'settings_link' ], 10 ,1 );
        add_action( 'wp_ajax_woo_get_group_courses', array( $this, 'woo_get_group_courses' ) );
    }

    public function woo_get_group_courses() {
        $data = array();

        if( empty( $_POST["group_id"] ) ) {
            $courses = new WP_Query( array( "post_type" => "sfwd-courses", "post_status" => "published", 'posts_per_page'   => -1 ) );
            if( $courses->have_posts() ) {
                while( $courses->have_posts() ) {
                    $courses->the_post();
                    $data[] = array(
                        "course_id" => get_the_ID(),
                        "course_title" => get_the_title()
                    );
                }
                wp_reset_query();
                wp_send_json_success($data);
            }
        }
        
        $courses = learndash_group_enrolled_courses( $_POST["group_id"] );
        if( !empty( $courses ) ) {
            foreach ($courses as $key => $course_id) {
                $data[] = array(
                    "course_id" => $course_id,
                    "course_title" => get_the_title( $course_id )
                );
            }
            wp_send_json_success($data);
        }
        wp_send_json_error( "404" );
    }

    /**
     * Add settings link on plugin page
     *
     * @return void
     */
    public function settings_link( $links ) {
        $settings_link = '<a href="admin.php?page=woo-user-progress-reports">'. __( 'User Reports', WOO_LEARNDASH_REPORTS_TEXT_DOMAIN ). '</a>';
        array_unshift($links, $settings_link);
        return $links;
    }
}

/**
 * Display admin notifications if dependency not found.
 */
function WOO_LEARNDASH_REPORTS_ready() {
    if( !is_admin() ) {
        return;
    }

    if( !class_exists( 'SFWD_CPT' ) ) {
        deactivate_plugins ( plugin_basename ( __FILE__ ), true );
        $class = 'notice is-dismissible error';
        $message = __( 'LearnDash Template Reporting add-ono requires <a href="https://www.learndash.com/" >LearanDashs</a> plugin to be activated.', WOO_LEARNDASH_REPORTS_TEXT_DOMAIN );
        printf ( '<div id="message" class="%s"> <p>%s</p></div>', $class, $message );
    }
}

if( !function_exists("dd") ) {
	function dd( $data, $exit_data = true) {
	  echo '<pre>'.print_r($data, true).'</pre>';
	  if($exit_data == false)
	    echo '';
	  else
	    exit;
	}
}

/**
 * @return WooLearnDashReports|bool
 */
function WooLearnDashReports() {
    if ( ! class_exists( 'SFWD_CPT' ) ) {
        add_action( 'admin_notices', 'WOO_LEARNDASH_REPORTS_ready' );
        return false;
    }

    return WooLearnDashReports::instance();
}
add_action( 'plugins_loaded', 'WooLearnDashReports');
