<?php 
/*
Plugin Name: ALT Lab Closer - Research Interest Network
Plugin URI:  https://github.com/
Description: Creates a password for a URL parameter that will add a 'closed' tag to a post if that URL is visited
Version:     1.0
Author:      ALT Lab
Author URI:  http://altlab.vcu.edu
License:     GPL2
License URI: https://www.gnu.org/licenses/gpl-2.0.html
Domain Path: /languages
Text Domain: my-toolset

*/
defined( 'ABSPATH' ) or die( 'No script kiddies please!' );

//allow closed query param
function closed_query_vars( $qvars ) {
    $qvars[] = 'closed';
    $qvars[] = 'pw';
    return $qvars;
}
add_filter( 'query_vars', 'closed_query_vars' );





function closer_add_tag($post){
  $post_id = get_queried_object_id();

  if ( get_query_var('closed',1) && get_query_var('pw',1) ) {
    $closed = get_query_var('closed',1);
    $url_pw = get_query_var('pw',1);
    $post_pw = get_post_meta($post_id, '_closer_pw', true);
    if ($closed == 'closed' && $url_pw == $post_pw){
     wp_set_post_tags( $post_id, 'closed', true );
    }
  }
  return $post;
}

add_action('the_post', 'closer_add_tag');


//allow manual entry of underscore custom fields 
add_filter( 'is_protected_meta', '__return_false');



add_filter('acf/settings/remove_wp_meta_box', '__return_false');

//LOGGER -- like frogger but more useful

if ( ! function_exists('write_log')) {
   function write_log ( $log )  {
      if ( is_array( $log ) || is_object( $log ) ) {
         error_log( print_r( $log, true ) );
      } else {
         error_log( $log );
      }
   }
}

  //print("<pre>".print_r($a,true)."</pre>");

//add secret password
function closer_add_meta_pw($post_id){
  $pw = sanitize_title(wp_generate_password(22, true));//create pw
  add_post_meta($post_id, '_closer_pw', $pw, true );//assign to custom field but don't make any new ones or update if it exists
}

//SEND THE EMAIL
add_action( 'gform_after_submission_1', 'closer_send_email', 10, 2 );
function closer_send_email( $entry, $form ) {
    
    $post_id = $entry['post_id'];//get created ID

    closer_add_meta_pw($post_id);//add pw to custom field

    $email = rgar( $entry, '3' );
    $closer_pw = get_post_meta($post_id, '_closer_pw', true);
    $url = get_permalink($post_id) . '?closed=closed&pw=' . $closer_pw;

    $subject = 'To Close Your VCU Research Interest Network Request(Keep This)';
    $body = 'To close your research project, click on the link. ' . $url;
    $headers = array('Content-Type: text/html; charset=UTF-8');
     
    wp_mail( $email, $subject, $body, $headers );

}