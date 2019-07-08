<?php

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class WOO_LD_Reporting {

    static $instance;
    public $users_obj;

    /**
     * Reporting constructor.
     */
    public function __construct() {
        add_filter( "set-screen-option", [ __CLASS__, "set_screen" ], 10, 3 );
        add_action( "admin_menu", [$this, "plugin_menu"] );
        add_action( "admin_enqueue_scripts", [$this, "admin_js"] );
        add_action( "admin_init", [$this, "single_export_excelsheet_data"] );
        add_action( "admin_init", [$this, "multiple_export_excelsheet_data"] );
    }

    /**
     * Enqueue admin js file
     */
    public function admin_js() {

    	/*wp_register_style('jquery-ui', 'http://ajax.googleapis.com/ajax/libs/jqueryui/1.8/themes/base/jquery-ui.css');
    	wp_enqueue_style( 'jquery-ui' );*/

        wp_enqueue_style( 'daterange-css', WOO_LEARNDASH_REPORTS_ASSETS_URL . '/js/daterangepicker.css', '', array( '' ), false, 'all' );

        wp_enqueue_script('moment-js', WOO_LEARNDASH_REPORTS_ASSETS_URL . '/js/moment.min.js', array('jquery'), "", false);
        wp_enqueue_script('daterange-js', WOO_LEARNDASH_REPORTS_ASSETS_URL . '/js/daterangepicker.min.js', array('moment-js'), "", false);

        wp_enqueue_script( "admin-custom-js", WOO_LEARNDASH_REPORTS_ASSETS_URL . "/js/admin-custom.js", array( "jquery", "moment-js", "daterange-js" ), "", true );
    }

    /**
     * Add admin menu
     */
    public function plugin_menu() {

        $hook = add_menu_page(
            __( "User Progress Report", WOO_LEARNDASH_REPORTS_LANG ),
            __( "User Progress Report", WOO_LEARNDASH_REPORTS_LANG ),
            'manage_options',
            "woo-user-progress-reports",
            [ $this, "plugin_settings_page" ],
            "dashicons-admin-settings",
            100
        );

        /*$hook = add_submenu_page (
            "learndash-lms",
            __( "User Progress Report", WOO_LEARNDASH_REPORTS_LANG ),
            __( "User Progress Report", WOO_LEARNDASH_REPORTS_LANG ),
            "manage_options",
            "woo-user-progress-reports",
            [ $this, "plugin_settings_page" ]
        );*/

        add_action( "load-$hook", [ $this, "screen_option" ] );

    }

    /**
     * Save screen values
     *
     * @param $status
     * @param $option
     * @param $value
     * @return mixed
     */
    public static function set_screen( $status, $option, $value ) {
        return $value;
    }

    /**
     * Screen options
     */
    public function screen_option() {

        $option = "per_page";
        $args   = [
            "label"   => "Users",
            "default" => 20,
            "option"  => "users_per_page"
        ];

        add_screen_option( $option, $args );

        $this->users_obj = new Certificate_List();
    }

    /**
     * Plugin settings page
     */
    public function plugin_settings_page() {
        ?>
        <style>
dd.course_progress { 
    position: relative; 
    display: block;                 
    border: 1px solid black;
    width: 100%; 
    height: 16px; 
    margin: 0 0 2px; 
    background-color: white; 
    padding:0;
}

dd.course_progress div.course_progress_blue { 
    position: relative; 
    background-color: blue; 
    height: 16px; 
    width: 75%; 
    text-align:right; 
    display:block;
}
.tablenav a.button, .tablenav a.button-secondary {
    display: inline-block;
    margin: 0;
}
.tablenav.top {
    min-height: 30px;
    height: auto;
}
.learndash-lms_page_woo-user-progress-reports.wp-core-ui .button-primary {
    margin-bottom: 5px;
}
        </style>
        <div class="wrap">
            <h1 class="wp-heading-inline"><?php echo __( "User Progress Report", WOO_LEARNDASH_REPORTS_LANG ); ?></h1>

            <?php
            $submit_url = admin_url("admin.php?page=woo-user-progress-reports");
            ?>

            <form id="woo-reports" method="GET" action="">
                <input type="hidden" name="page" value="<?php echo $_REQUEST['page'] ?>" />                
                <?php
                $this->users_obj->prepare_items();
                $this->users_obj->search_box('Search', 'search');
                $this->users_obj->display();
                ?>
                <input type="hidden" name="_wp_http_referer" value="<?php echo $submit_url; ?>">
            </form>
        </div>
        <?php
    }

    /**
     * Singleton instance
     */
    public static function get_instance() {
        if ( ! isset( self::$instance ) ) {
            self::$instance = new self();
        }

        return self::$instance;
    }


    public function single_export_excelsheet_data() {
        
        if( isset($_GET["single_export"], $_GET["filter_course"], $_GET["user_ids"]) ) {
            
            $user_ids       = explode(",", $_GET["user_ids"]);
            $course_id      = $_GET["filter_course"];

            if( empty( $user_ids ) ) {
                return;
            }

            $date_format        = get_option("date_format");

            $spreadsheet        = new Spreadsheet();
            $spreadsheet->setActiveSheetIndex(0);

            $sheet              = $spreadsheet->getActiveSheet();

            $alphabetize = array("A", "B", "C", "D", "E", "F", "G", "H", "I", "J", "K", "L", "M", "N", "O");
            foreach ($alphabetize as $key => $alphabet) {
                $sheet->getColumnDimension($alphabet)->setAutoSize(true);
            }
            

            $sheet->setCellValue('A1', 'user_id');
            $sheet->setCellValue('B1', 'name');
            $sheet->setCellValue('C1', 'email');
            $sheet->setCellValue('D1', 'course_id');
            $sheet->setCellValue('E1', 'course_title');
            $sheet->setCellValue('F1', 'steps_completed');
            $sheet->setCellValue('G1', 'steps_total');
            $sheet->setCellValue('H1', 'course_completed');
            $sheet->setCellValue('I1', 'course_completed_on');
            $sheet->setCellValue('J1', 'course_started_on');
            //$sheet->setCellValue('K1', 'course_total_time_on');
            $sheet->setCellValue('K1', 'course_last_step_id');
            $sheet->setCellValue('L1', 'course_last_step_type');
            $sheet->setCellValue('M1', 'course_last_step_title');
            $sheet->setCellValue('N1', 'last_login_date');

            $counter = 2;

            foreach ($user_ids as $key => $user_id) {
                
                $data  = learndash_report_user_courses_progress( $user_id, array(), array("course_ids" => array($course_id)) );
                
                $user_last_login    = get_user_meta( $user_id, "_ld_notifications_last_login", true);
                if ( !empty( $user_last_login ) ) {
                    $user_last_login = date( $date_format, $user_last_login );
                } else {
                    $user_last_login = "";
                }

                if( !empty( $data["results"] ) ) {
                    $display_name           = $data["results"][0]->user_display_name;
                    $user_email             = $data["results"][0]->user_email;
                    $course_id              = $course_id;
                    $course_title           = get_the_title( $course_id) ;
                    $steps_completed        = isset( $data["results"][0]->activity_meta["steps_completed"] ) ? $data["results"][0]->activity_meta["steps_completed"] : "";
                    $steps_total            = isset( $data["results"][0]->activity_meta["steps_total"] ) ? $data["results"][0]->activity_meta["steps_total"] : "";
                    $course_completed       = $data["results"][0]->activity_completed == 0 ? "NO" : "YES";
                    $course_completed_on    = $data["results"][0]->activity_completed == 0 ? "" : date( $date_format, $data["results"][0]->activity_completed );
                    $course_started_on      = date( $date_format, $data["results"][0]->activity_started );
                    $course_last_step_id    = $data["results"][0]->activity_meta["steps_last_id"];
                    $course_last_step_type  = isset( $data["results"][0]->activity_meta["steps_last_id"] ) ? get_post_type( $data["results"][0]->activity_meta["steps_last_id"] ) : "";
                    $course_last_step_title = isset($data["results"][0]->activity_meta["steps_last_id"]) ? get_the_title( $data["results"][0]->activity_meta["steps_last_id"] ) : "";
                } else {
                    $user                   = get_user_by( "ID", $user_id );
                    $display_name           = $user->data->display_name;
                    $user_email             = $user->data->user_email;
                    $course_id              = $course_id;
                    $course_title           = get_the_title( $course_id) ;
                    $steps_completed        = "";
                    $steps_total            = "";
                    $course_completed       = "Not Started";
                    $course_completed_on    = "";
                    $course_started_on      = "";
                    $course_last_step_id    = "";
                    $course_last_step_type  = "";
                    $course_last_step_title = "";
                }

                if( empty( $data["results"] ) ) {
                    //continue;
                }

                $sheet->setCellValue('A' . $counter, $user_id );
                $sheet->setCellValue('B' . $counter, $display_name );
                $sheet->setCellValue('C' . $counter, $user_email );
                $sheet->setCellValue('D' . $counter, $course_id );
                $sheet->setCellValue('E' . $counter, $course_title );
                $sheet->setCellValue('F' . $counter, $steps_completed );
                $sheet->setCellValue('G' . $counter, $steps_total );
                $sheet->setCellValue('H' . $counter, $course_completed );
                $sheet->setCellValue('I' . $counter, $course_completed_on );
                $sheet->setCellValue('J' . $counter, $course_started_on );
                $sheet->setCellValue('K' . $counter, $course_last_step_id );
                $sheet->setCellValue('L' . $counter, $course_last_step_type );
                $sheet->setCellValue('M' . $counter, $course_last_step_title );
                $sheet->setCellValue('N' . $counter, $user_last_login );

                $counter++;
            }

            $writer         = new Xlsx($spreadsheet);

            header('Content-Type: application/vnd.ms-excel');
            header('Content-Disposition: attachment;filename="user-course-progress-'.substr(time(), 2).'.xlsx"'); /*-- $filename is  xsl filename ---*/
            header('Cache-Control: max-age=0');
            $writer->save("php://output");
            exit;
        }
    }

    public function multiple_export_excelsheet_data() {
        
        if( isset( $_GET["multiple_export"], $_GET["filter_course"] ) ) {
            
            $course_id      = $_GET["filter_course"];
            $users      = learndash_get_users_for_course( $course_id, array(), false );
            $user_ids   = $users->results;


            if( empty( $user_ids ) ) {
                return;
            }

            $date_format        = get_option("date_format");

            $spreadsheet        = new Spreadsheet();
            $spreadsheet->setActiveSheetIndex(0);

            $sheet              = $spreadsheet->getActiveSheet();

            $alphabetize = array("A", "B", "C", "D", "E", "F", "G", "H", "I", "J", "K", "L", "M", "N", "O");
            foreach ($alphabetize as $key => $alphabet) {
                $sheet->getColumnDimension($alphabet)->setAutoSize(true);
            }

            $sheet->setCellValue('A1', 'user_id');
            $sheet->setCellValue('B1', 'name');
            $sheet->setCellValue('C1', 'email');
            $sheet->setCellValue('D1', 'course_id');
            $sheet->setCellValue('E1', 'course_title');
            $sheet->setCellValue('F1', 'steps_completed');
            $sheet->setCellValue('G1', 'steps_total');
            $sheet->setCellValue('H1', 'course_completed');
            $sheet->setCellValue('I1', 'course_completed_on');
            $sheet->setCellValue('J1', 'course_started_on');
            $sheet->setCellValue('K1', 'course_last_step_id');
            $sheet->setCellValue('L1', 'course_last_step_type');
            $sheet->setCellValue('M1', 'course_last_step_title');
            $sheet->setCellValue('N1', 'last_login_date');

            if( isset( $_GET["filter_from_date"] ) && !empty( $_GET["filter_from_date"] ) ) {
                list($start_date_1, $start_date_2) = explode(" - ", $_GET["filter_from_date"]);
                $start_time_1   = strtotime( $start_date_1 );
                $start_time_2   = strtotime( $start_date_2 );
            }


            if( isset( $_GET["filter_to_date"] ) && !empty( $_GET["filter_to_date"] ) ) {
                list($end_date_1, $end_date_2) = explode(" - ", $_GET["filter_to_date"]);
                $end_time_1     = strtotime( $end_date_1 );
                $end_time_2     = strtotime( $end_date_2 );
            }

            $counter = 2;

            foreach ($user_ids as $key => $user_id) {
                
                $data  = learndash_report_user_courses_progress( $user_id, array(), array("course_ids" => array($course_id)) );
                
                $user_last_login    = get_user_meta( $user_id, "_ld_notifications_last_login", true);
                if ( !empty( $user_last_login ) ) {
                    $user_last_login = date( $date_format, $user_last_login );
                } else {
                    $user_last_login = "";
                }
                
                if( !empty( $data["results"] ) ) {
                    $display_name           = $data["results"][0]->user_display_name;
                    $user_email             = $data["results"][0]->user_email;
                    $course_id              = $course_id;
                    $course_title           = get_the_title( $course_id) ;
                    $steps_completed        = isset( $data["results"][0]->activity_meta["steps_completed"] ) ? $data["results"][0]->activity_meta["steps_completed"] : "";
                    $steps_total            = isset( $data["results"][0]->activity_meta["steps_total"] ) ? $data["results"][0]->activity_meta["steps_total"] : "";
                    $course_completed       = $data["results"][0]->activity_completed == 0 ? "NO" : "YES";
                    $course_completed_on    = $data["results"][0]->activity_completed == 0 ? "" : date( $date_format, $data["results"][0]->activity_completed );
                    $course_started_on      = date( $date_format, $data["results"][0]->activity_started );
                    $course_last_step_id    = isset( $data["results"][0]->activity_meta["steps_last_id"] ) ?: "";
                    $course_last_step_type  = isset( $data["results"][0]->activity_meta["steps_last_id"] ) ? get_post_type( $data["results"][0]->activity_meta["steps_last_id"] ) : "";
                    $course_last_step_title = isset($data["results"][0]->activity_meta["steps_last_id"]) ? get_the_title( $data["results"][0]->activity_meta["steps_last_id"] ) : "";
                } else {
                    $user                   = get_user_by( "ID", $user_id );
                    $display_name           = $user->data->display_name;
                    $user_email             = $user->data->user_email;
                    $course_id              = $course_id;
                    $course_title           = get_the_title( $course_id) ;
                    $steps_completed        = "";
                    $steps_total            = "";
                    $course_completed       = "Not Started";
                    $course_completed_on    = "";
                    $course_started_on      = "";
                    $course_last_step_id    = "";
                    $course_last_step_type  = "";
                    $course_last_step_title = "";
                }

                $sheet->setCellValue('A' . $counter, $user_id );
                $sheet->setCellValue('B' . $counter, $display_name );
                $sheet->setCellValue('C' . $counter, $user_email );
                $sheet->setCellValue('D' . $counter, $course_id );
                $sheet->setCellValue('E' . $counter, $course_title );
                $sheet->setCellValue('F' . $counter, $steps_completed );
                $sheet->setCellValue('G' . $counter, $steps_total );
                $sheet->setCellValue('H' . $counter, $course_completed );
                $sheet->setCellValue('I' . $counter, $course_completed_on );
                $sheet->setCellValue('J' . $counter, $course_started_on );
                $sheet->setCellValue('K' . $counter, $course_last_step_id );
                $sheet->setCellValue('L' . $counter, $course_last_step_type );
                $sheet->setCellValue('M' . $counter, $course_last_step_title );
                $sheet->setCellValue('N' . $counter, $user_last_login );

                $counter++;
            }
            
            $writer         = new Xlsx($spreadsheet);
            
            header('Content-Type: application/vnd.ms-excel');
            header('Content-Disposition: attachment;filename="user-course-progress-'.substr(time(), 2).'.xlsx"'); /*-- $filename is  xsl filename ---*/
            header('Cache-Control: max-age=0');
            $writer->save("php://output");
            exit;
        }
    }
}

WOO_LD_Reporting::get_instance();