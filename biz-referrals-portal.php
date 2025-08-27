<?php
/**
 * Plugin Name: Biz Referrals Portal
 * Description: Front-end portal for Ask / Requirement / Give / Lead / Response with moderation, login/register tabs, scheduling, share buttons, author dashboard, and admin moderation screen.
 * Version: 1.3.0
 * Author: BRP Team
 * License: GPL2+
 */
if (!defined('ABSPATH')) exit;

define('BRP_VER','1.3.0');
define('BRP_PATH', plugin_dir_path(__FILE__));
define('BRP_URL', plugin_dir_url(__FILE__));

require_once BRP_PATH.'includes/cpt.php';
require_once BRP_PATH.'includes/forms.php';
require_once BRP_PATH.'includes/settings.php';
require_once BRP_PATH.'includes/dashboard.php';
require_once BRP_PATH.'includes/moderate.php';

register_activation_hook(__FILE__, function(){
  add_role('portal_contributor','Portal Contributor',['read'=>true]);
  if (!wp_next_scheduled('brp_hourly_cron')) wp_schedule_event(time()+300,'hourly','brp_hourly_cron');
});
register_deactivation_hook(__FILE__, function(){
  if ($t = wp_next_scheduled('brp_hourly_cron')) wp_unschedule_event($t,'brp_hourly_cron');
});

add_action('wp_enqueue_scripts', function(){
  wp_enqueue_style('brp-style', BRP_URL.'assets/style.css', [], BRP_VER);
  wp_enqueue_script('brp-js', BRP_URL.'assets/brp.js', ['jquery'], BRP_VER, true);
  wp_localize_script('brp-js', 'BRP_Ajax', [
    'ajax_url'=>admin_url('admin-ajax.php'),
    'nonce'=>wp_create_nonce('brp_nonce')
  ]);
});

/** Active + Schedule enforcement on front-end */
function brp_is_active_and_in_window($post_id){
  $active = (int) get_post_meta($post_id,'_brp_active',true);
  $start  = get_post_meta($post_id,'_brp_start',true);
  $end    = get_post_meta($post_id,'_brp_end',true);
  $now    = current_time('timestamp');
  if (!$active) return false;
  if ($start) { $ts=strtotime($start); if ($ts && $now<$ts) return false; }
  if ($end)   { $te=strtotime($end);   if ($te && $now>$te) return false; }
  return true;
}
add_action('template_redirect', function(){
  if (is_singular(['ask','requirement','give','lead','response'])){
    global $post;
    if (!$post) return;
    if (!brp_is_active_and_in_window($post->ID)){
      if (current_user_can('edit_post',$post->ID) || current_user_can('manage_options')){
        add_filter('the_content', function($c){
          return '<div class="brp-notice">This post is paused or outside its schedule. Only you and admins can see it.</div>'.$c;
        });
      } else {
        global $wp_query; $wp_query->set_404(); status_header(404);
      }
    }
  }
});

/** Add disclaimer + share to single content */
add_filter('the_content', function($content){
  if (!is_singular(['ask','requirement','give','lead','response'])) return $content;
  $id   = get_the_ID();
  $name = get_post_meta($id,'_brp_name',true);
  $city = get_post_meta($id,'_brp_city',true);
  $email= get_post_meta($id,'_brp_email',true);
  $phone= get_post_meta($id,'_brp_phone',true);
  $file = get_post_meta($id,'_brp_file_url',true);
  $link = urlencode(get_permalink($id));
  $title= urlencode(get_the_title($id));

  $meta = '<div class="brp-meta"><strong>Submitted by:</strong> '.esc_html($name).' ('.esc_html($city).')';
  if ($email) $meta .= ' • <a href="mailto:'.esc_attr($email).'">'.esc_html($email).'</a>';
  if ($phone) $meta .= ' • <a href="tel:'.esc_attr($phone).'">'.esc_html($phone).'</a>';
  $meta .= '</div>';
  if ($file) $meta .= '<div class="brp-meta"><a href="'.esc_url($file).'" target="_blank" rel="noopener">Attachment</a></div>';

  $  $share = '<div class="brp-share"><span>Share:</span>
    <a class="brp-sh" target="_blank" rel="noopener" href="https://wa.me/?text='.$title.'%20'.$link.'">WhatsApp</a>
    <a class="brp-sh" target="_blank" rel="noopener" href="https://t.me/share/url?url='.$link.'&text='.$title.'">Telegram</a>
    <a class="brp-sh" target="_blank" rel="noopener" href="https://www.facebook.com/sharer/sharer.php?u='.$link.'">Facebook</a>
    <a class="brp-sh" target="_blank" rel="noopener" href="https://www.linkedin.com/sharing/share-offsite/?url='.$link.'">LinkedIn</a>
    <a class="brp-sh" target="_blank" rel="noopener" href="https://twitter.com/intent/tweet?url='.$link.'&text='.$title.'">X</a>
    <a class="brp-sh" target="_blank" rel="noopener" href="mailto:?subject='.$title.'&body='.$link.'">Email</a>
    <button class="brp-copy" data-link="'.esc_attr(get_permalink($id)).'">Copy Link</button>
  </div>';


  $disclaimer = '<div class="brp-disclaimer"><strong>Disclaimer:</strong> Author permits reposting to social/digital media and takes full responsibility for accuracy, legality and any monetary dealings. Site/admin are not responsible.</div>';

  return $meta.$share.$content.$disclaimer;
});

/** Auto-unpublish at End Date */
add_action('brp_hourly_cron', function(){
  $types = ['ask','requirement','give','lead','response'];
  $q = new WP_Query([
    'post_type'=>$types,'post_status'=>'publish',
    'posts_per_page'=>200,'fields'=>'ids','no_found_rows'=>true,
    'meta_query'=>[['key'=>'_brp_end','compare'=>'EXISTS']]
  ]);
  $now = current_time('timestamp');
  foreach ($q->posts as $pid){
    $end = get_post_meta($pid,'_brp_end',true);
    if ($end && ($ts=strtotime($end)) && $now>$ts){
      wp_update_post(['ID'=>$pid,'post_status'=>'draft']);
    }
  }
});

/** Email reminder 24h before End Date */
add_action('save_post', function($post_id,$post,$update){
  if (!in_array($post->post_type,['ask','requirement','give','lead','response'],true)) return;
  $end = get_post_meta($post_id,'_brp_end',true);
  if (!$end) return;
  $when = strtotime($end) - DAY_IN_SECONDS;
  if ($when > current_time('timestamp')){
    $args = [$post_id];
    if ($old = wp_next_scheduled('brp_send_end_reminder',$args)) wp_unschedule_event($old,'brp_send_end_reminder',$args);
    wp_schedule_single_event($when,'brp_send_end_reminder',$args);
  }
},10,3);

add_action('brp_send_end_reminder', function($post_id){
  $post = get_post($post_id); if (!$post) return;
  $author = get_userdata($post->post_author);
  $to = [];
  if ($author && is_email($author->user_email)) $to[]=$author->user_email;
  $submitter = get_post_meta($post_id,'_brp_email',true);
  if ($submitter && is_email($submitter)) $to[]=$submitter;
  if (!$to) return;
  $site = wp_specialchars_decode(get_bloginfo('name'),ENT_QUOTES);
  $subject = 'Reminder: Your post ends soon – '.$post->post_title;
  $body = "Hello,\n\nYour post \"{$post->post_title}\" will end soon. If you wish to extend it, update the End Date.\n\nThanks,\n{$site}";
  wp_mail($to,$subject,$body);
});
