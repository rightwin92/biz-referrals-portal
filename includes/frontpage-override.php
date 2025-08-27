<?php
if (!defined('ABSPATH')) exit;

/**
 * Front Page Override for BRP
 *
 * Purpose: Guarantees the portal UI renders on the front page, even if the page
 * content shows raw shortcodes or the theme/editor blocks shortcodes.
 *
 * Behavior:
 * - If is_front_page(), we load a minimal template from the plugin and exit.
 * - That template renders [brp_portal] and [brp_submit] via do_shortcode(),
 *   and shows a "Latest" grid (if the theme helper exists).
 *
 * Toggle (optional):
 * - You can disable this override by adding this to wp-config.php:
 *      define('BRP_DISABLE_FRONT_OVERRIDE', true);
 */

add_action('template_redirect', function(){
  if (defined('BRP_DISABLE_FRONT_OVERRIDE') && BRP_DISABLE_FRONT_OVERRIDE) return;

  if (is_front_page()){
    status_header(200);
    // Prevent caching plugins from serving a cached page that might hide updates
    nocache_headers();
    // Load the plugin's safe front page template and stop further rendering
    include BRP_PATH.'templates/front-page-brp.php';
    exit;
  }
});
