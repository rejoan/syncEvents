<?php

/*
  Plugin Name: Sync Events
  description: A simple custom plugin to get API data
  Version: 1.0.0
  Author: Rejoanul Alam
 */

if (!class_exists('syncEvents')) {

  class syncEvents {

    /**
     * Constructor
     */
    public function __construct() {
      $this->setup_actions();
    }

    /**
     * Setting up Hooks
     */
    public function setup_actions() {
      add_action('init', array($this, 'eventd_data'));
    }


    /**
     * Custom Post Type
     */
    public function eventd_data() {
      $labels = array(
          'archives' => __('Item Archives'),
          'attributes' => __('Item Attributes'),
          'parent_item_colon' => __('Parent Item:'),
          'all_items' => __('All Items'),
          'search_items' => __('Search'),
          'edit_item' => 'Edit Item',
          'update_item' => 'Update Item'
      );
      $args = array(
          'label' => __('Event Sync'),
          'description' => __('API data through Sync'),
          'labels' => $labels,
          'supports' => array('title', 'custom-fields'),
          'public' => true,
          'show_in_menu' => true,
          'menu_position' => 5,
          'menu_icon' => 'dashicons-format-aside',
          'show_in_admin_bar' => true,
          'show_in_nav_menus' => true,
          'can_export' => true,
          'has_archive' => true,
          'query_var' => true,
          'publicly_queryable' => true,
          'capabilities' => array(
              'create_posts' => false,
          ),
          'supports' => array('title', 'editor', 'custom-fields'),
          'map_meta_cap' => true,
      );
      register_post_type('eventd', $args);
    }

    /**
     * Remove Published Tab
     */
    public function remove__views($views) {
      unset($views['all']);
      unset($views['publish']);
      return $views;
    }
  }

  // instantiate the plugin class
  $syncEvents = new syncEvents();
}


function eventd_adjust_queries($query){
  global $post_type;
  $meta_query = array();
  $compare = '=';
  if(isset($_GET['u'])){
    $compare = '>=';
  }
  if(isset($_GET['sd']) && !empty($_GET['sd'])){
    $d = esc_attr($_GET['sd']);
    $date = \DateTimeImmutable::createFromFormat('d/m/y', $d);
    $startd = array(
                'key'     => 'ev_startd',
                'compare' => $compare,
                'value'   => $date->format('Y-m-d'),
                'type'   => 'DATE'
            );
    $meta_query[] = $startd;
  }
   if (is_admin() && $post_type == 'eventd') {
        $query->set('meta_key', 'ev_startd');
        $query->set('meta_query', $meta_query);
   }
}
add_action('pre_get_posts', 'eventd_adjust_queries' );

//wordpress sql string
add_filter('query', 'get_sql');
function get_sql($query){
  //check if this is your query,
  if(strpos($query, "'ev_startd'")>0){
    //var_dump($query);return;
  }
  return $query;
}

add_action('manage_posts_extra_tablenav', 'eventd_button_to_views');

/**
 * plugin url hidden field for ajax
 * @param array $views
 * @return string
 */
function eventd_button_to_views($which) {
  global $post_type;
  if($which == 'top' && $post_type == 'eventd'){
    $parts = parse_url(home_url());
    $port = '';
    if(isset($parts['port'])){
      $port = $parts['port'];
    }
    
    $gargs = array(
        'sd' => date('d/m/y'),
        'u' => 'y'
    );
    $upcoming_url = $parts['scheme'] . '://' . $parts['host'] . ':' . $port . add_query_arg($gargs);
    $sd = '';
    if(isset($_GET['sd'])){
      $sd = esc_attr($_GET['sd']);
    }
    echo '<p class="s-para"><input class="datetimepicker" name="sd" type="text" autocomplete="off" placeholder="Select Date" value="'.$sd.'"></p><p class="s-para"><a title="Upcoming Events" href="'.$upcoming_url.'" class="button">Upcoming</a></p><p id="button-section"><button id="sync_data" title="Extract Events by API" class="button button-primary">Sync Data</button></p><input type="hidden" id="admin_url" value="' . admin_url() . '"><input type="hidden" id="plugin_url" value="' . plugins_url() . '">';
  }
}

add_filter('manage_eventd_posts_columns', 'set_custom_eventd_columns');

function set_custom_eventd_columns($columns) {
  unset($columns['date']);
  $columns['ev_startd'] = 'Event Date';
  $columns['ev_startt'] = 'Event Time';
  $columns['ev_endd'] = 'End Date';
  $columns['ev_endt'] = __('End Time', 'your_text_domain');
  $columns['date'] = 'Date';
  return $columns;
}

/**
 * Display Values of Custom fields to Custom Post Type
 */
add_action('manage_posts_custom_column', 'action_eventd_custom_columns_content', 10, 2);

function action_eventd_custom_columns_content($column_id, $post_id) {
  //run a switch statement for all of the custom columns created
  switch ($column_id) {
    case 'id':
      echo $post_id;
      break;
    case 'ev_startd':
      echo get_post_meta($post_id, 'ev_startd', true);
      break;
    case 'ev_startt':
      echo get_post_meta($post_id, 'ev_startt', true);
      break;
    case 'ev_endd':
      echo get_post_meta($post_id, 'ev_endd', true);
      break;
    case 'ev_endt':
      echo get_post_meta($post_id, 'ev_endt', true);
  }
}

add_action('wp_ajax_eventd_sync', 'eventd_sync');
add_action('wp_ajax_nopriv_eventd_sync', 'eventd_sync');

/**
 * get API data and create CPT
 * @global type $wpdb
 */
function eventd_sync() {
  if (!isset($_POST['action']) && ($_POST['action'] != 'eventd_sync')) {
    exit('The form is not valid');
  }

  $ch = curl_init();
  curl_setopt($ch, CURLOPT_URL, "https://api.prospectbox.co/ytevents?site=&api_key=123456");
  curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
  curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "GET");
  curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
  curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);

  $headers = array();
  $headers[] = "Accept: application/json";
  curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

  $result = curl_exec($ch);

  if (curl_errno($ch)) {
    echo 'Error:' . curl_error($ch);
    die;
  }
  curl_close($ch);
  $events = json_decode($result, true);
  $sliced = $events;
  global $wpdb;
  $query = 'SELECT * FROM ' . $wpdb->prefix . 'posts WHERE post_type = "eventd" ORDER BY ID DESC LIMIT 1';
  $row = $wpdb->get_row($query);
  //check latest rows and take only new data
  if (!empty($row)) {
    $query1 = 'SELECT * FROM ' . $wpdb->prefix . 'postmeta WHERE post_id= ' . $row->ID . ' AND meta_key = "ev_id" ORDER BY meta_id DESC LIMIT 1';
    $row1 = $wpdb->get_row($query1);
    if (!empty($row1)) {
      $key = array_search($row1->meta_value, array_column($events, 'id'));
      $offset = $key + 1;
      $sliced = array_slice($events, $offset);
    }
  }
  if (empty($sliced)){
    echo json_encode(array('code' => 'empty'));
    die;
  }

  foreach ($sliced as $event) {
    $time_d = strtotime($event['eventdatetime']);
    $time_t = strtotime($event['eventstarttime']);
    $time_ed = strtotime($event['eventenddate']);
    $time_et = strtotime($event['eventendtime']);
    $rowData = array(
        'ev_id' => $event['id'],
        'ev_summary' => $event['eventsummary'],
        'ev_startd' => date('Y-m-d',$time_d),
        'ev_startt' => date('H:i:s',$time_t),
        'ev_endd' => date('Y-m-d',$time_ed),
        'ev_endt' => date('H:i:s',$time_et),
        'ev_link' => $event['eventlink'],
        'ev_slug' => $event['slug'],
        'ev_image' => $event['image'],
        'ev_active' => $event['active'],
        'ev_inserted' => $event['timestamp']
    );
    $post_id = wp_insert_post(
            array(
                'post_type' => 'eventd',
                'post_title' => $event['eventname'],
                'post_content' => $event['eventdescription'],
                'post_status' => 'publish'
            )
    );
    foreach ($rowData as $mkey => $metaV) {
      add_post_meta($post_id, $mkey, $metaV);
    }
  }
  echo json_encode(array('code' => 'done'));
  die;
}

add_action('admin_enqueue_scripts', 'eventd_backend_assets');

function eventd_backend_assets() {
  global $post_type;
  if($post_type == 'eventd'){
    wp_enqueue_script('eventd-script', plugins_url('syncD.js', __FILE__));
    wp_enqueue_style('style-common', plugins_url('/style.css', __FILE__));
    wp_enqueue_script('datetimepicker', plugins_url('/datetimepicker/jquery.datetimepicker.full.min.js', __FILE__));
    wp_enqueue_style('datetimepicker-style', plugins_url('/datetimepicker/jquery.datetimepicker.min.css', __FILE__));
  }
}

add_shortcode('event-random-list', 'eventd_random_list');

/**
 * front end shortcode display function
 * @global type $wpdb
 * @param array $atts
 */
function eventd_random_list($atts) {
  if (!isset($atts['num']) || empty($atts['num']) || !ctype_digit($atts['num'])) {
    $atts['num'] = 20;
  }
  if (!isset($atts['view']) || empty($atts['view']) || !ctype_digit($atts['view'])) {
    $atts['view'] = '1';
  }

  if (is_dir(plugin_dir_path(__FILE__) . 'templates/template' . trim($atts['view']))
          === false) {
    echo '<h2 align="center">Template not exist</h2>';
    return;
  }


  $view = trim($atts['view']);
  $preHTML = file_get_contents(plugin_dir_path(__FILE__) . 'templates/template' . $view . '/preHTML.php');
  $postHTML = file_get_contents(plugin_dir_path(__FILE__) . 'templates/template' . $view . '/postHTML.php');
  
  $args = [
      'post_type' => 'eventd',
      'post_status' => 'publish',
      'numberposts' => trim($atts['num'])
  ];
  if (isset($atts['sort'])) {
    $order = 'ASC';
    if($atts['sort'] == 'new-first'){
      $order = 'DESC';
    }
    $args['meta_key'] = 'ev_startd';
    $args['orderby'] = 'meta_value';
    $args['order'] = $order;
  }else{
    $args['orderby'] = 'rand';
  }
  $posts = get_posts($args);

  $thebox = file_get_contents(plugin_dir_path(__FILE__) . 'templates/template' . trim($atts['view']) . '/main.php');

  $html = $preHTML;

  foreach ($posts as $post) {
    $rep = array("##ev_name##", "##ev_summary##", "##ev_startd##", "##ev_startt##", "##ev_endd##", "##ev_endt##", "##ev_image##");
    $repwith = array('<a target="_blank" href="' . get_post_permalink($post->ID) . '">' . $post->post_title . '</a>', get_post_meta($post->ID, 'ev_summary', true), get_post_meta($post->ID, 'ev_startd', true), get_post_meta($post->ID, 'ev_startt', true), get_post_meta($post->ID, 'ev_endd', true), get_post_meta($post->ID, 'ev_endt', true), get_post_meta($post->ID, 'ev_image', true));
    $thisbox = str_replace($rep, $repwith, $thebox);
    $html .= $thisbox;
  }
  $html .= $postHTML;
  echo $html;
}

add_shortcode('al-event-info', 'eventd_info');

/**
 * get business info shortcode
 * @global type $post
 * @return type
 */
function eventd_info() {
  global $post;
  ob_start();
  $date_start = \DateTimeImmutable::createFromFormat('Y-m-d', get_post_meta($post->ID, 'ev_startd', true));
  $date_end = \DateTimeImmutable::createFromFormat('Y-m-d', get_post_meta($post->ID, 'ev_endd', true));
  echo '<h3 class="">Event Information</h3><p>Summary: ' . get_post_meta($post->ID, 'ev_summary', true) . '</p><p>Start Date: ' . $date_start->format('d M, y') . '</p><p>Start Time: ' . get_post_meta($post->ID, 'ev_startt', true) . '</p><p>End Date: ' . $date_end->format('d M, y').'<p>End Time: ' . get_post_meta($post->ID, 'ev_endt', true) . '</p>';
  $ret = ob_get_contents();
  ob_end_clean();
  return $ret;
}

add_action('wp_enqueue_scripts', 'eventd_enqueue_styles', 11);

function eventd_enqueue_styles() {
  wp_enqueue_style('child-style', get_stylesheet_uri());
}
