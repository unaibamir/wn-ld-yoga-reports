<?php

if ( !defined ( 'ABSPATH' ) ) exit;


if ( ! class_exists( "WP_List_Table" ) ) {
    require_once(ABSPATH . "wp-admin/includes/class-wp-list-table.php");
}

/**
 * Class Customers_List
 *
 * To create table list for certificate issued to users
 */
class Certificate_List extends WP_List_Table {

    public function __construct() {
        parent::__construct( [
            "display_name"          => __( "User Name", WOO_LEARNDASH_REPORTS_LANG ),
            "email"                 => __( "Email", WOO_LEARNDASH_REPORTS_LANG ),
            "assigned_course"       => __( "Assigned Course", WOO_LEARNDASH_REPORTS_LANG ),
            "course_progress"       => __( "Course Progress", WOO_LEARNDASH_REPORTS_LANG ),
            "assigned_date"         => __( "Started Date", WOO_LEARNDASH_REPORTS_LANG ),
            "completed_date"        => __( "Completed Date", WOO_LEARNDASH_REPORTS_LANG ),
        ] );
    }


    /**
     * Render filter option at top of listing
     * Render export buttons at bottom of listing
     *
     * @param string $position
     */
    public function extra_tablenav( $position ) {
        $action             = ( isset( $_GET ) && isset( $_GET["action"] ) ) ? $_GET["action"] : "";
        $filter_course      = "";
        $filter_from_date   = "";
        $filter_to_date     = "";
        
        $filter_course      = ( isset( $_GET["course_id"] ) && ! empty( $_GET["course_id"] ) ) ? $_GET["course_id"] : "";
        $filter_from_date   = ( isset( $_GET["date_from"] ) && ! empty( $_GET["date_from"] ) ) ? $_GET["date_from"] : "";
        $filter_to_date     = ( isset( $_GET["date_to"] ) && ! empty( $_GET["date_to"] ) ) ? $_GET["date_to"] : "";
        $group_id           = ( isset( $_GET["group_id"] ) && ! empty( $_GET["group_id"] ) ) ? $_GET["group_id"] : "";
        
        if( $position == "top" ) {
            ?>
            <div class="alignleft actions">
                <?php

                $groups = new WP_Query( array( "post_type" => "groups", "post_status" => "published", 'posts_per_page'   => -1 ) );
                if( $groups->have_posts() ) {
                    ?>
                    <select class="postform" name="group_id" id="group_id">
                        <option value="">Filter By Group</option>
                        <?php
                            while( $groups->have_posts() ) {
                                $groups->the_post();
                                if( (int) $group_id === get_the_ID() ) {
                                    $selected = "selected";
                                } else {
                                    $selected = "";
                                }
                                ?>
                                <option value="<?php echo get_the_ID(); ?>" <?php echo $selected; ?>><?php echo get_the_title(); ?></option>
                                <?php
                            }
                        ?>
                    </select>
                    <?php
                }
                $course_args = array( "post_type" => "sfwd-courses", "post_status" => "published", 'posts_per_page'   => -1 );
                if( isset($_GET["group_id"]) && !empty( $_GET["group_id"] ) ) {
                    $course_extra_args = array("meta_key" => "learndash_group_enrolled_{$_GET["group_id"]}");
                }
                $course_args  =   wp_parse_args( $course_extra_args, $course_args );
                
                $courses = new WP_Query( $course_args );
                if( $courses->have_posts() ) {
                    ?>
                    <select class="postform" name="course_id" id="course_filter">
                        <option value="">Filter By Course</option>
                        <?php
                            while( $courses->have_posts() ) {
                                $courses->the_post();
                                if( (int) $filter_course === get_the_ID() ) {
                                    $selected = "selected";
                                } else {
                                    $selected = "";
                                }
                                ?>
                                <option value="<?php echo get_the_ID(); ?>" <?php echo $selected; ?>><?php echo get_the_title(); ?></option>
                                <?php
                            }
                        ?>
                    </select>
                    <?php
                    wp_reset_query();
                }
                ?>
                <br><br>
                <label> Start Date: <input type="search" id="date_from" name="date_from" value="<?php echo $filter_from_date; ?>" style="max-width: 200px;"></label>
                <label>Completion Date: <input type="search" id="date_to" name="date_to" value="<?php echo $filter_to_date; ?>" style="max-width: 200px;"></label>

                <input type="submit" name="submit" id="submit" class="button button-primary" value="Filter" />
                <a href="<?php echo get_admin_url() . "admin.php?page=woo-user-progress-reports"; ?>" name="clear_filter" id="clear_filter" class="button button-primary">Clear Filter</a>
                <input type="hidden" name="paged" value="1" />
            </div>
            <?php
        }
        if( $position == "bottom" ) {
            $per_page       = $this->get_items_per_page( "users_per_page", 20 );
            $current_page   = $this->get_pagenum();
            $items_array    = self::get_users_data( $per_page, $current_page );
            $user_ids       = wp_list_pluck( $items_array, "user_id", null );
            $disabled = "";
            $href = "";

            if( !isset($_GET["course_id"]) || ( isset($_GET["course_id"]) && empty($_GET["course_id"]) ) || isset( $items_array ) && empty( $items_array ) ) {
                $disabled = "disabled";
                $href = "#";
            }

            $download_current_page_url = add_query_arg ( array ( "single_export" => "excel", "per_page" => $per_page, "current_page" => $current_page, "filter_course" => $filter_course, "filter_from_date" => $filter_from_date, "filter_to_date" => $filter_to_date , "group_id" => $group_id, "user_ids" => implode(",", $user_ids) ), get_admin_url() . "admin.php?page=woo-user-progress-reports" );
            echo "<a ". $disabled ." href='" . ( empty( $href ) ? $download_current_page_url : $href ) . "' class='button button-primary download-report'>" . __( "Export Current page Report", "ld" ) . "</a> ";

            $download_report_url = add_query_arg ( array ( "multiple_export" => "excel", "filter_course" => $filter_course, "filter_from_date" => $filter_from_date, "filter_to_date" => $filter_to_date , "group_id" => $group_id ), get_admin_url() . "admin.php?page=woo-user-progress-reports" );
            echo "<a ". $disabled ." href='" . ( empty( $href ) ? $download_report_url : $href ) . "' class='button button-primary download-report'>" . __( "Export Complete Report", "ld" ) . "</a>";
        }
    }


    /**
     *  Associative array of columns
     *
     * @return array
     */
    function get_columns() {
        $columns = [
            "display_name"          => __( "User Name", WOO_LEARNDASH_REPORTS_LANG ),
            "email"                 => __( "Email", WOO_LEARNDASH_REPORTS_LANG ),
            "assigned_course"       => __( "Assigned Course", WOO_LEARNDASH_REPORTS_LANG ),
            "course_progress"       => __( "Course Progress", WOO_LEARNDASH_REPORTS_LANG ),
            "assigned_date"         => __( "Started Date", WOO_LEARNDASH_REPORTS_LANG ),
            "completed_date"        => __( "Completed Date", WOO_LEARNDASH_REPORTS_LANG ),
        ];
        return $columns;
    }



    /**
     * Columns to make sortable.
     *
     * @return array
     */
    public function get_sortable_columns() {
        $sortable_columns = array(
            "display_name"      => array( "display_name", true ),
        );
        return $sortable_columns;
    }


    /**
     * Render a column when no column specific method exists.
     *
     * @param array $item
     * @param string $column_name
     *
     * @return mixed
     */
    public function column_default( $item, $column_name ) {

        switch ( $column_name ) {
            case "display_name":
                return "<a href='".get_edit_user_link( $item["user_id"] )."'>".$item[$column_name]."</a>";
            case "email":
                return "<a href='".get_edit_user_link( $item["user_id"] )."'>".$item[$column_name]."</a>";
            case "assigned_course":
            case "course_progress":
            case "assigned_date":
            default:
                return "";
        }
    }


    /**
     * Handles data query and filter, sorting, and pagination.
     */
    public function prepare_items() {

        $this->_column_headers  = $this->get_column_info();
        $per_page               = $this->get_items_per_page( "users_per_page", 20 );
        $current_page           = $this->get_pagenum();
        $total_items            = self::get_users_data( $per_page, $current_page, true );
        $items_array            = self::get_users_data( $per_page, $current_page );

        $this->set_pagination_args( [
            "total_items" => $total_items,
            "per_page"    => $per_page
        ] );
        
        $this->items            = $items_array;

    }



    /**
     * No Certificate Issued Yet
     */
    public function no_items() {
        _e( "No Record Found.", WOO_LEARNDASH_REget_users_dataPORTS_LANG );
    }


    public static function get_users_data( $per_page = 20, $page_number = 1, $count = false ) {

        global $wpdb;

        $users_ids = $user_extra_args = array();

        if ( isset( $_GET['group_id'] ) ) {
            $filter_group_id = intval( $_GET['group_id'] );
            if ( !empty( $filter_group_id ) ) {
                if ( learndash_is_group_leader_user( get_current_user_id() ) ) {
                    $group_ids = learndash_get_administrators_group_ids( get_current_user_id() );
                    
                    // If the Group Leader doesn't have groups or not a managed group them clear our selected group_id
                    if ( ( empty( $group_ids ) ) || ( in_array( $filter_group_id, $group_ids ) === false ) ) {
                        $filter_group_id = 0;
                    }
                }
                
                if ( !empty( $filter_group_id ) ) {
                    
                    $user_extra_args["meta_key"]        = 'learndash_group_users_'. $filter_group_id;
                    $user_extra_args["meta_value"]      = $filter_group_id;
                    $user_extra_args["meta_compare"]    = '=';
                }
            }
        }
        
        if ( isset( $_GET['course_id'] ) ) {
            $filter_course_id = intval( $_GET['course_id'] );
            if ( !empty( $filter_course_id ) ) {
                if ( learndash_is_group_leader_user( get_current_user_id() ) ) {
                    $group_ids = learndash_get_administrators_group_ids( get_current_user_id() );
                    if ( ! empty( $group_ids ) && is_array( $group_ids ) ) {
                        $course_ids = array();
                        foreach( $group_ids as $group_id ) {
                            $group_course_ids = learndash_group_enrolled_courses( $group_id );
                            if ( ! empty( $group_course_ids ) && is_array( $group_course_ids ) ) {
                                $course_ids = array_merge( $course_ids, $group_course_ids );
                            }
                        }
                        if ( empty( $course_ids ) ) {
                            $filter_course_id = 0;
                        } 
                
                    }
                }
                
                if ( !empty( $filter_course_id ) ) {
                    $course_users_query = learndash_get_users_for_course( $filter_course_id, array(), false );
                    if ( ( $course_users_query instanceof WP_User_Query ) && ( property_exists( $course_users_query, 'results' ) ) && ( !empty( $course_users_query->results ) ) ) {    
                        $user_ids = $course_users_query->get_results();
                        if ( !empty( $user_ids ) ) {
                            $users_ids = $user_ids;
                        } 
                    } else {
                        $users_ids = array(0);
                    }
                }
            }
        }
        
        

        if( !empty( $users_ids ) ) {
            foreach ($users_ids as $key => $user_id) {
                if( isset($_GET["course_id"], $_GET["date_from"], $_GET["date_to"]) && !empty($_GET["course_id"]) ) {

                    $exit_loop  = $started = false;

                    $course_id  = $_GET["course_id"];
                    $element    = Learndash_Admin_Settings_Data_Upgrades::get_instance();
                    $format     = 'm-d-Y';
                    $date_query = "";


                    if( !empty( $_GET["date_from"] ) ) {
                        list($start_date_1, $start_date_2) = explode(" - ", $_GET["date_from"]);
                        $start_time_1   = strtotime( $start_date_1 );
                        $start_time_2   = strtotime( $start_date_2 );
                        $date_query    .= " AND activity_started BETWEEN ".$start_time_1." AND ".$start_time_2;
                    }


                    if( !empty( $_GET["date_to"] ) ) {
                        list($end_date_1, $end_date_2) = explode(" - ", $_GET["date_to"]);
                        $end_time_1     = strtotime( $end_date_1 );
                        $end_time_2     = strtotime( $end_date_2 );
                        $date_query    .= " AND activity_completed BETWEEN ".$end_time_1." AND ".$end_time_2;
                    }


                    $sql_str = $wpdb->prepare("SELECT * FROM ". $wpdb->prefix ."learndash_user_activity WHERE user_id=%d AND course_id=%d AND post_id=%d AND activity_type=%s " . $date_query . " LIMIT 1", $user_id, $course_id, $course_id, "course" );

                    $activity = $wpdb->get_row( $sql_str );
                    
                    if( isset($_GET["date_from"], $_GET["date_to"]) && !empty($_GET["date_from"]) || !empty($_GET["date_to"]) ) {
                        if( !$activity || is_null( $activity ) || empty($activity) ) {
                            unset($users_ids[$key]);
                        }
                    }
                }
            }
        }
        
        $user_extra_args["include"]    = $users_ids;
        
        $user_args = [
            "orderby"   =>  isset($_GET["orderby"]) && $_GET["orderby"] == "username" ? "display_name" : "display_name",
            "order"     =>  isset($_GET["order"]) ? $_GET["order"] : "asc",
            "search"    =>  isset($_GET["s"]) ? $_GET["s"] : "",
            "offset"    =>  $page_number,
            "number"    =>  $per_page,
        ];

        $user_args  =   wp_parse_args( $user_extra_args, $user_args );

        $user_search = new WP_User_Query( $user_args );

        $users = $user_search->get_results();
        
        if( $count ) {
            return $user_search->get_total();
        }

        $data = array();

        foreach ($users as $key => $user) {

            $user_id    = $user->ID;

            /*if( isset($_GET["course_id"], $_GET["date_from"], $_GET["date_to"]) && !empty($_GET["course_id"]) ) {

                $exit_loop  = $started = false;
                $course_id  = $_GET["course_id"];
                $element    = Learndash_Admin_Settings_Data_Upgrades::get_instance();
                $format     = 'm-d-Y';
                $date_query = "";


                if( !empty( $_GET["date_from"] ) ) {
                    list($start_date_1, $start_date_2) = explode(" - ", $_GET["date_from"]);
                    $start_time_1   = strtotime( $start_date_1 );
                    $start_time_2   = strtotime( $start_date_2 );
                    $date_query    .= " AND activity_started BETWEEN ".$start_time_1." AND ".$start_time_2;
                }


                if( !empty( $_GET["date_to"] ) ) {
                    list($end_date_1, $end_date_2) = explode(" - ", $_GET["date_to"]);
                    $end_time_1     = strtotime( $end_date_1 );
                    $end_time_2     = strtotime( $end_date_2 );
                    $date_query    .= " AND activity_completed BETWEEN ".$end_time_1." AND ".$end_time_2;
                }


                $sql_str = $wpdb->prepare("SELECT * FROM ". $wpdb->prefix ."learndash_user_activity WHERE user_id=%d AND course_id=%d AND post_id=%d AND activity_type=%s " . $date_query . " LIMIT 1", $user_id, $course_id, $course_id, "course" );

                $activity = $wpdb->get_row( $sql_str );

                if( isset($_GET["date_from"], $_GET["date_to"]) && !empty($_GET["date_from"]) || !empty($_GET["date_to"]) ) {
                    if( $activity) {
                        if( $exit_loop ) {
                            continue;
                        }
                    } else {
                        continue;
                    }
                }
            }*/


            $data[$key]["display_name"]     = $user->data->display_name;
            $data[$key]["user_id"]          = $user_id;
            $data[$key]["assigned_date"]    = $user->data->display_name;
            $data[$key]["email"]            = $user->data->user_email;

        }

        return $data;
        
    }


    /**
     * Renders courese column .
     *
     * @param array $item
     *
     * @return mixed
     */
    public function column_courses( $item ) {

        //ob_start();
        $output = "";
        $user_id = $item["user_id"];
        if( !empty( $item["course_ids"] ) ) {
            foreach ($item["course_ids"] as $course_id) {

                $progress = learndash_course_progress( array(
                    'user_id'   => $user_id,
                    'course_id' => $course_id,
                    'array'     => true
                ));
                
                $message    = sprintf( esc_html_x( '%1$d out of %2$d steps completed', 'placeholder: completed steps, total steps', 'learndash' ), $progress["completed"], $progress["total"] );

                $output .= "<a href='".get_edit_post_link( $course_id )."'><span>". get_the_title( $course_id ) ."</span></a>" ;
                $output .= "(". $message . ")";
                $output .= SFWD_LMS::get_template(
                    'course_progress_widget', array(
                        'message'    => $message,
                        'percentage' => isset( $progress["percentage"] ) ? $progress["percentage"] : 0,
                        'completed'  => isset( $progress["completed"] ) ? $progress["completed"] : 0,
                        'total'      => isset( $progress["total"] ) ? $progress["total"] : 0,
                    )
                );
                $output .= "<br>";
            }
        }

        return $output;
    }


    public function column_assigned_course( $item ) {
        if( isset($_GET["course_id"]) && !empty( $_GET["course_id"] ) ) {
            return get_the_title( $_GET["course_id"] );
        }
    }

    public function column_course_progress( $item ) {

        $output = "";
        $user_id = $item["user_id"];
        if( isset($_GET["course_id"]) && !empty( $_GET["course_id"] ) ) {
            
            $course_id = $_GET["course_id"];
            $progress = learndash_course_progress( array(
                'user_id'   => $user_id,
                'course_id' => $course_id,
                'array'     => true
            ));
            
            $message    = sprintf( esc_html_x( '%1$d out of %2$d steps completed', 'placeholder: completed steps, total steps', 'learndash' ), $progress["completed"], $progress["total"] );
            $output .= "(". $message . ")";
            
            $output .= SFWD_LMS::get_template(
                'course_progress_widget', array(
                    'message'    => $message,
                    'percentage' => isset( $progress["percentage"] ) ? $progress["percentage"] : 0,
                    'completed'  => isset( $progress["completed"] ) ? $progress["completed"] : 0,
                    'total'      => isset( $progress["total"] ) ? $progress["total"] : 0,
                )
            );
        }
        return $output;
    }


    public function column_assigned_date( $item ) {
        if( isset($_GET["course_id"]) && !empty( $_GET["course_id"] ) ) {
            $course_id  = $_GET["course_id"];
            $user_id    = $item["user_id"];

            $args       = array(
                "user_id"       =>   $user_id,
                "post_id"       =>   $course_id,
                "course_id"     =>   $course_id,
                "activity_type" =>  "course"
            );

            $activity   = learndash_get_user_activity( $args );
            if( $activity ) {
                $output     = date( get_option("date_format"), $activity->activity_started );
                return $output;
            }
        }
    }


    public function column_completed_date( $item ) {
        if( isset($_GET["course_id"]) && !empty( $_GET["course_id"] ) ) {
            $course_id  = $_GET["course_id"];
            $user_id    = $item["user_id"];

            $args       = array(
                "user_id"       =>   $user_id,
                "post_id"       =>   $course_id,
                "course_id"     =>   $course_id,
                "activity_type" =>  "course"
            );

            $activity   = learndash_get_user_activity( $args );
            if( $activity && !empty( $activity->activity_completed ) ) {
                $output     = date( get_option("date_format"), $activity->activity_completed );
                return $output;
            }
        }
    }
}