<?php
if (!defined('ABSPATH')) exit;
add_action('template_redirect', function(){
  if (defined('BRP_DISABLE_FRONT_OVERRIDE') && BRP_DISABLE_FRONT_OVERRIDE) return;
  if (!is_front_page()) return;
  status_header(200); nocache_headers();
  $tpl = BRP_PATH.'templates/front-page-brp.php';
  if (file_exists($tpl)) { include $tpl; exit; }
  get_header();
  echo '<main class="brpt-container brpt-full">';
  echo '<section class="brpt-hero"><h1>'.esc_html(get_bloginfo('name')).'</h1><p class="brpt-sub">Biz Referrals Portal</p></section>';
  echo '<section class="brpt-tabs-wrap">'.do_shortcode('[brp_portal]').'</section>';
  echo '<section class="brpt-submit-wrap">'.do_shortcode('[brp_submit]').'</section>';
  echo '</main>';
  get_footer();
  exit;
});
