<?php
/**
 * Oregon Wine Board Bulk Emailer.
 *
 * @license   http://www.gnu.org/licenses/gpl-3.0.html GNU General Public License
 *
 * @wordpress-plugin
 * Plugin Name: Oregon Wine Board Bulk Emailer
 * Plugin URI: https://thinkshout.com/
 * Version:     1.0
 * Description: A bulk emailer built by ThinkShout for Oregon Wine Board to send emails to users with stale content.
 * Author:      ThinkShout
 * Author URI:  https://thinkshout.com/
 * Text Domain: orwb-bulk-emailer
 * Requires at least: 5.6
 * Requires PHP: 5.6.20
*/

define( 'ORWB_BULK_EMAILER_PATH', plugin_dir_path( __FILE__ ) );

if ( is_readable( ORWB_BULK_EMAILER_PATH . 'vendor/autoload.php' ) ) {
  require ORWB_BULK_EMAILER_PATH . 'vendor/autoload.php';
}
use Mailgun\Mailgun;

class ORWB_Bulk_Emailer {

  public function __construct() {
    add_action( 'admin_menu', array( $this, 'orwb_mailer_add_admin_menu' ) );
    add_action( 'wp_ajax_orwb_mailer_get_eligible_users' , array( $this, 'orwb_mailer_fetch_eligible_users' ) );
    add_action( 'wp_ajax_orwb_mailer_send_email' , array( $this, 'orwb_mailer_send_email' ) );
    add_action( 'wp_ajax_orwb_mailer_check_api_key' , array( $this, 'orwb_mailer_check_api_key' ) );
    add_action( 'wp_ajax_orwb_mailer_set_api_key', array( $this, 'orwb_mailer_set_api_key' ) );
    add_action( 'wp_ajax_orwb_mailer_remove_api_key', array( $this, 'orwb_mailer_remove_api_key' ) );
    add_action( 'admin_enqueue_scripts', array( $this, 'orwb_mailer_scripts' ) );
  }

  public function orwb_mailer_add_admin_menu() {
    add_management_page( 'Oregon Wine Board Bulk Emailer', 'Wine Board Emailer', 'manage_options', 'orwb-bulk-emailer', array( $this, 'orwb_mailer_options_page' ) );
  }

  public function orwb_mailer_options_page() {
    require_once ORWB_BULK_EMAILER_PATH . 'orwb-emailer-dashboard.php';
    $this->orwb_mailer_settings_init();
  }

  public function orwb_mailer_settings_init() {
    add_option( 'orwb_mailgun_api_key' );
  }

  public function orwb_mailer_check_api_key() {
    $key = get_option( 'orwb_mailgun_api_key' );
    if ( ! $key ) {
      wp_send_json_error( 'No API key found.' );
      wp_die();
    }
    wp_send_json_success( 'API key found.' );
  } 

  public function orwb_mailer_set_api_key() {
    if ( ! current_user_can( 'manage_options' ) ) {
      wp_send_json_error( 'You do not have permission to perform this action.' );
      wp_die();
    }
    if (  ! isset( $_POST['security'] ) || ! wp_verify_nonce( $_POST['security'], 'orwb_mailer_nonce' ) ) {
      wp_send_json_error( 'Invalid nonce.' );
      wp_die();
    }

    $api_key = sanitize_text_field( wp_unslash( $_POST['api_key'] ) );
    if ( ! $api_key ) {
      wp_send_json_error( 'Please enter an API key.' );
      wp_die();
    }
    update_option( 'orwb_mailgun_api_key', $api_key );
    wp_send_json_success( 'API key saved.' );
    wp_die();
  }

  public function orwb_mailer_remove_api_key() {
    if ( ! current_user_can( 'manage_options' ) ) {
      wp_send_json_error( 'You do not have permission to perform this action.' );
      wp_die();
    }
    if (  ! isset( $_POST['security'] ) || ! wp_verify_nonce( $_POST['security'], 'orwb_mailer_nonce' ) ) {
      wp_send_json_error( 'Invalid nonce.' );
      wp_die();
    }
    update_option( 'orwb_mailgun_api_key', false );
    wp_send_json_success( 'API key removed.' );
    wp_die();
  }

  public function orwb_mailer_fetch_eligible_users() {
    if ( ! current_user_can( 'manage_options' ) ) {
      wp_send_json_error( 'You do not have permission to perform this action.' );
      wp_die();
    }
    if (  ! isset( $_POST['security'] ) || ! wp_verify_nonce( $_POST['security'], 'orwb_mailer_nonce' ) ) {
      wp_send_json_error( 'Invalid nonce.' );
      wp_die();
    }
    $today = new DateTime();
    $post_modified_date = $today->sub( new DateInterval( 'P6M' ) );
    $orwb_eligible_listings = new WP_Query(
      [
        'post_type' => 'listing',
        'posts_per_page' => -1,
        'date_query' => [
          'column' => 'post_modified',
          'before'  => [
            'year'  => $post_modified_date->format( 'Y' ),
            'month' => $post_modified_date->format( 'm' ),
            'day'   => $post_modified_date->format( 'd' ),
          ],
        ],
      ]
    );
    $orwb_eligible_users = [];
    foreach ( $orwb_eligible_listings->posts as $listing ) {
      $orwb_author_id = $listing->post_author;
      if ( ! isset($orwb_eligible_users[$orwb_author_id]) ) {
        $orwb_eligible_users[$orwb_author_id] = [
          'name' => get_the_author_meta( 'display_name', $listing->post_author ),
          'email' => get_the_author_meta( 'user_email', $listing->post_author ),
          'posts' => [],
        ];
      }
      $orwb_eligible_users[$orwb_author_id]['posts'][] = [
        'title' => $listing->post_title,
        'link' => get_edit_post_link( $listing->ID ),
        'modified' => $listing->post_modified,
      ];
    }
    echo wp_json_encode( $orwb_eligible_users );
    wp_die();
  }

  public function orwb_mailer_send_email() {
    if ( ! current_user_can( 'manage_options' ) ) {
      wp_send_json_error( 'You do not have permission to perform this action.' );
      wp_die();
    }
    if (  ! isset( $_POST['security'] ) || ! wp_verify_nonce( $_POST['security'], 'orwb_mailer_nonce' ) ) {
      wp_send_json_error( 'Invalid nonce.' );
      wp_die();
    }
    $api_key = get_option( 'orwb_mailgun_api_key' );
    if ( ! $api_key ) {
      wp_send_json_error( 'Please enter an API key.' );
      wp_die();
    }
    $allowed_tags = '<p><strong><em><u><h1><h2><h3><h4><h5><h6><li><ol><ul><span><div><br><ins><del>';
    $subject = sanitize_text_field( wp_unslash( $_POST['subject'] ) );
    $message = strip_tags( wp_unslash( $_POST['message'] ), $allowed_tags );
    $users = json_decode( wp_unslash( $_POST['selectedUsers'] ), true );
    $emailer = Mailgun::create( $api_key );
    $domain = 'oregonwine.org';
    $recipients = array_map( function( $user ) {
      return $user['email'];
    }, $users );
    $params = [
      'from' => 'Oregon Wine Board <site-admin@oregonwineboard.org>',
      'to' => $recipients,
      'subject' => $subject,
      'text' => $message,
    ];
    try {
      $response = $emailer->messages()->send( $domain, $params );
      wp_send_json_success( $response );
    } catch (\Throwable $th) {
      wp_send_json_error( [$th->getmessage(), $response] );
    }
    wp_die();
  }

  public function orwb_mailer_scripts() {
    $current_screen = get_current_screen();
    if ( 'tools_page_orwb-bulk-emailer' === $current_screen->base ) {
      wp_register_script( 'orwb-mailer-script', plugins_url( 'dist/plugin.js', __FILE__ ) );
      wp_localize_script( 'orwb-mailer-script', 'orwb_mailer_ajax', array( 'ajaxUrl' => admin_url( 'admin-ajax.php' ), 'ajaxSecurity' => wp_create_nonce( 'orwb_mailer_nonce' ) ) );
      wp_enqueue_script( 'orwb-mailer-script' );
      wp_enqueue_style( 'orwb-mailer-style', plugins_url( 'dist/plugin.css', __FILE__ ) );
    }
  }
}

new ORWB_Bulk_Emailer();