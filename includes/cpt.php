<?php
if (!defined('ABSPATH')) exit;

add_action('init', function(){
  $common = [
    'public'=>true,'show_in_rest'=>true,'has_archive'=>true,
    'supports'=>['title','editor','author','excerpt'],
    'menu_icon'=>'dashicons-megaphone'
  ];
  register_post_type('ask',         array_merge($common,['labels'=>['name'=>'Asks','singular_name'=>'Ask']]));
  register_post_type('requirement', array_merge($common,['labels'=>['name'=>'Requirements','singular_name'=>'Requirement']]));
  register_post_type('give',        array_merge($common,['labels'=>['name'=>'Gives','singular_name'=>'Give']]));
  register_post_type('lead',        array_merge($common,['labels'=>['name'=>'Leads','singular_name'=>'Lead']]));
  register_post_type('response',    array_merge($common,['labels'=>['name'=>'Responses','singular_name'=>'Response']]));
  register_taxonomy('response_type','response',['label'=>'Response Type','public'=>true,'show_in_rest'=>true,'hierarchical'=>false]);
});

/** Meta boxes */
add_action('add_meta_boxes', function(){
  foreach (['ask','requirement','give','lead','response'] as $pt){
    add_meta_box('brp_userinfo','Submitter Info','brp_userinfo_cb',$pt,'side');
    add_meta_box('brp_schedule','Visibility Schedule','brp_schedule_cb',$pt,'side');
  }
});
function brp_userinfo_cb($post){
  wp_nonce_field('brp_save_meta','brp_meta_nonce');
  $fields=['name','phone','email','city','file_url'];
  foreach ($fields as $k){ ${$k}=get_post_meta($post->ID,'_brp_'.$k,true); }
  echo '<p><label>Name<br><input type="text" name="brp_name" value="'.esc_attr($name).'"></label></p>';
  echo '<p><label>Phone<br><input type="text" name="brp_phone" value="'.esc_attr($phone).'"></label></p>';
  echo '<p><label>Email<br><input type="email" name="brp_email" value="'.esc_attr($email).'"></label></p>';
  echo '<p><label>City<br><input type="text" name="brp_city" value="'.esc_attr($city).'"></label></p>';
  if ($file_url) echo '<p><a href="'.esc_url($file_url).'" target="_blank">Attachment</a></p>';
}
function brp_schedule_cb($post){
  $active=(int)get_post_meta($post->ID,'_brp_active',true);
  $start = get_post_meta($post->ID,'_brp_start',true);
  $end   = get_post_meta($post->ID,'_brp_end',true);
  echo '<p><label><input type="checkbox" name="brp_active" value="1" '.checked($active,1,false).'> Active (uncheck to pause)</label></p>';
  echo '<p><label>Start Date<br><input type="datetime-local" name="brp_start" value="'.esc_attr($start).'"></label></p>';
  echo '<p><label>End Date<br><input type="datetime-local" name="brp_end" value="'.esc_attr($end).'"></label></p>';
}
add_action('save_post', function($post_id){
  if (!isset($_POST['brp_meta_nonce']) || !wp_verify_nonce($_POST['brp_meta_nonce'],'brp_save_meta')) return;
  foreach (['name','phone','email','city'] as $k){
    if (isset($_POST['brp_'.$k])) update_post_meta($post_id,'_brp_'.$k,sanitize_text_field($_POST['brp_'.$k]));
  }
  update_post_meta($post_id,'_brp_active', isset($_POST['brp_active'])?1:0);
  if (isset($_POST['brp_start'])) update_post_meta($post_id,'_brp_start',sanitize_text_field($_POST['brp_start']));
  if (isset($_POST['brp_end']))   update_post_meta($post_id,'_brp_end',sanitize_text_field($_POST['brp_end']));
});
