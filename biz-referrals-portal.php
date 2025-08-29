<?php
/**
 * Plugin Name: Biz Referrals Portal
 * Description: Front-end portal for Ask / Requirement / Give / Lead / Response with moderation, login/register tabs, scheduling, share buttons, author dashboard, and admin moderation screen.
 * Version: 1.3.6
 * Author: BRP Team
 * License: GPL2+
 */
if (!defined('ABSPATH')) exit;

define('BRP_VER','1.3.6');
define('BRP_PATH', plugin_dir_path(__FILE__));
define('BRP_URL', plugin_dir_url(__FILE__));

/** Counter helpers */
function brp_get_count($post_id, $key){
  $v = (int) get_post_meta($post_id, $key, true);
  return max(0, $v);
}
function brp_inc_count_once($post_id, $key){
  $v = brp_get_count($post_id, $key) + 1;
  update_post_meta($post_id, $key, $v);
  return $v;
}

/** Safely load includes (no fatal if a file is missing) */
function brp_safe_require($rel){
  $abs = BRP_PATH . ltrim($rel,'/');
  if (file_exists($abs)) require_once $abs;
  else error_log('[BRP] Missing include: '.$abs);
}

brp_safe_require('includes/cpt.php');
brp_safe_require('includes/forms.php');
brp_safe_require('includes/settings.php');
brp_safe_require('includes/dashboard.php');
brp_safe_require('includes/moderate.php');
brp_safe_require('includes/frontpage-override.php'); // now guarded

/** Activation / Deactivation */
register_activation_hook(__FILE__, function(){
  add_role('portal_contributor','Portal Contributor',['read'=>true]);
  if (!wp_next_scheduled('brp_hourly_cron')) {
    wp_schedule_event(time()+300,'hourly','brp_hourly_cron');
  }
});
register_deactivation_hook(__FILE__, function(){
  if ($t = wp_next_scheduled('brp_hourly_cron')) {
    wp_unschedule_event($t,'brp_hourly_cron');
  }
});

/** Assets */
add_action('wp_enqueue_scripts', function(){
  wp_enqueue_style('brp-style', BRP_URL.'assets/style.css', [], BRP_VER);
  wp_enqueue_script('brp-js', BRP_URL.'assets/brp.js', ['jquery'], BRP_VER, true);
  wp_localize_script('brp-js', 'BRP_Ajax', [
  'ajax_url'     => admin_url('admin-ajax.php'),
  'nonce'        => wp_create_nonce('brp_nonce'),
  'nonce_action' => 'brp_nonce',
  'site_url'     => home_url('/')
]);

});

/** (Optional) Allow WEBP uploads on older WP */
add_filter('upload_mimes', function($m){
  $m['webp'] = 'image/webp';
  return $m;
});

/** Visibility: Active + Start/End window check */
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
    global $post; if (!$post) return;
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

/** Single content: submitter meta + full social share + disclaimer (append AFTER content) */
add_filter('the_content', function($content){
  if (!is_singular(['ask','requirement','give','lead','response'])) return $content;

  $id   = get_the_ID();
  $name = get_post_meta($id,'_brp_name',true);
  $city = get_post_meta($id,'_brp_city',true);
  $email= get_post_meta($id,'_brp_email',true);
  $phone= get_post_meta($id,'_brp_phone',true);
  $file = get_post_meta($id,'_brp_file_url',true);

  $link_raw = get_permalink($id);
  $link = urlencode($link_raw);
  $title= urlencode(get_the_title($id));

  $meta = '<div class="brp-meta"><strong>Submitted by:</strong> '.esc_html($name ?: '—').' ('.esc_html($city ?: '—').')';
  if ($email) $meta .= ' • <a href="mailto:'.esc_attr($email).'">'.esc_html($email).'</a>';
  if ($phone) $meta .= ' • <a href="tel:'.esc_attr($phone).'">'.esc_html($phone).'</a>';
  $meta .= '</div>';
  if ($file) $meta .= '<div class="brp-meta"><a href="'.esc_url($file).'" target="_blank" rel="noopener">Attachment</a></div>';

  $share = '<div class="brp-share"><span>Share:</span>
    <a class="brp-sh" target="_blank" rel="noopener" href="https://wa.me/?text='.$title.'%20'.$link.'">WhatsApp</a>
    <a class="brp-sh" target="_blank" rel="noopener" href="https://t.me/share/url?url='.$link.'&text='.$title.'">Telegram</a>
    <a class="brp-sh" target="_blank" rel="noopener" href="https://www.facebook.com/sharer/sharer.php?u='.$link.'">Facebook</a>
    <a class="brp-sh" target="_blank" rel="noopener" href="https://www.linkedin.com/sharing/share-offsite/?url='.$link.'">LinkedIn</a>
    <a class="brp-sh" target="_blank" rel="noopener" href="https://twitter.com/intent/tweet?url='.$link.'&text='.$title.'">X</a>
    <a class="brp-sh" target="_blank" rel="noopener" href="mailto:?subject='.$title.'&body='.$link.'">Email</a>
    <button class="brp-copy" data-link="'.esc_attr($link_raw).'">Copy Link</button>
  </div>';

  $disclaimer = '<div class="brp-disclaimer"><strong>Disclaimer:</strong> Author permits reposting to social/digital media and takes full responsibility for accuracy, legality and any monetary dealings. Site/admin are not responsible.</div>';

  return $content . $meta .   // ✅ Full share set
  $share = '<div class="brp-share"><span>Share:</span>
    <a class="brp-sh" target="_blank" rel="noopener" href="https://wa.me/?text='.$title.'%20'.$link.'">WhatsApp</a>
    <a class="brp-sh" target="_blank" rel="noopener" href="https://t.me/share/url?url='.$link.'&text='.$title.'">Telegram</a>
    <a class="brp-sh" target="_blank" rel="noopener" href="https://www.facebook.com/sharer/sharer.php?u='.$link.'">Facebook</a>
    <a class="brp-sh" target="_blank" rel="noopener" href="https://www.linkedin.com/sharing/share-offsite/?url='.$link.'">LinkedIn</a>
    <a class="brp-sh" target="_blank" rel="noopener" href="https://twitter.com/intent/tweet?url='.$link.'&text='.$title.'">X</a>
    <a class="brp-sh" target="_blank" rel="noopener" href="mailto:?subject='.$title.'&body='.$link.'">Email</a>
    <button class="brp-copy" data-link="'.esc_attr($link_raw).'">Copy Link</button>
  </div>';

  // ✅ CTA + Counters
  $likes    = brp_get_count($id, '_brp_like_count');
  $enquires = brp_get_count($id, '_brp_enquiry_count');

  $wa_link  = '';
  $em_link  = '';
  if ($phone) { // WhatsApp direct to contributor (fallback: wa.me without text)
    $wa_link = 'https://wa.me/'.preg_replace('/\D+/','', $phone).'?text='.rawurlencode('Hi, I found your post: '.get_the_title($id).' ('.get_permalink($id).')');
  }
  if ($email) { // Email
    $em_link = 'mailto:'.rawurlencode($email).'?subject='.rawurlencode('Regarding: '.get_the_title($id)).'&body='.rawurlencode('Hi, I found your post here: '.get_permalink($id));
  }

  $cta  = '<div class="brp-cta" data-post="'.$id.'">';
  $cta .= '<button class="brp-like-btn" data-post="'.$id.'" aria-label="I\'m Interested">👍 I\'m Interested <span class="brp-like-count">'.$likes.'</span></button> ';
  if ($wa_link) $cta .= '<a class="brp-enq-btn brp-enq-wa" data-post="'.$id.'" data-track="enquiry" href="'.$wa_link.'" target="_blank" rel="noopener">WhatsApp Contributor <span class="brp-enq-count">'.$enquires.'</span></a> ';
  if ($em_link) $cta .= '<a class="brp-enq-btn brp-enq-mail" data-post="'.$id.'" data-track="enquiry" href="'.$em_link.'">Email Contributor <span class="brp-enq-count">'.$enquires.'</span></a>';
  $cta .= '</div>';

  $disclaimer = '<div class="brp-disclaimer"><strong>Disclaimer:</strong> Author permits reposting to social/digital media and takes full responsibility for accuracy, legality and any monetary dealings. Site/admin are not responsible.</div>';

  return $content . $meta . $share . $cta . $disclaimer;
}, 20);

/** Auto-unpublish posts after End Date (hourly) */
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
    if ($old = wp_next_scheduled('brp_send_end_reminder',$args)) {
      wp_unschedule_event($old,'brp_send_end_reminder',$args);
    }
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
  /** AJAX: like/enquiry counters (public) */
function brp_validate_counter_request(){
  if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'brp_nonce')) wp_send_json_error('Bad nonce.');
  $post_id = isset($_POST['post_id']) ? intval($_POST['post_id']) : 0;
  if (!$post_id || !get_post($post_id)) wp_send_json_error('Bad post.');
  $type = isset($_POST['type']) ? sanitize_text_field($_POST['type']) : '';
  if (!in_array($type, ['like','enquiry'], true)) wp_send_json_error('Bad type.');
  return [$post_id, $type];
}

/** Basic duplicate prevention: localStorage key is enforced in JS; server remains stateless */
add_action('wp_ajax_nopriv_brp_track_interest', 'brp_track_interest');
add_action('wp_ajax_brp_track_interest', 'brp_track_interest');
function brp_track_interest(){
  list($post_id, $type) = brp_validate_counter_request();
  $key = ($type === 'like') ? '_brp_like_count' : '_brp_enquiry_count';
  $val = brp_inc_count_once($post_id, $key);
  wp_send_json_success(['post_id'=>$post_id, 'type'=>$type, 'count'=>$val]);
}
});
