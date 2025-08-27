<?php
/**
 * Plugin Front Page Template (BRP)
 * Loaded by includes/frontpage-override.php on is_front_page()
 * Always renders the portal UI (no raw shortcodes)
 */
if (!defined('ABSPATH')) exit;

// Load theme header/footer so the page matches your site
get_header();
?>
<main class="brpt-container brpt-full">
  <section class="brpt-hero">
    <h1><?php bloginfo('name'); ?></h1>
    <p class="brpt-sub">Biz Referrals Portal — Ask • Requirement • Give • Lead • Response</p>
  </section>

  <!-- Guaranteed portal UI (shortcodes executed) -->
  <section class="brpt-tabs-wrap">
    <?php echo do_shortcode('[brp_portal]'); ?>
  </section>

  <section class="brpt-submit-wrap">
    <?php echo do_shortcode('[brp_submit]'); ?>
  </section>

  <section class="brpt-latest">
    <header class="brpt-sec-h"><h2>Latest Opportunities</h2></header>
    <div class="brpt-filterbar">
      <select id="brpt-type">
        <option value="">All Types</option>
        <option value="ask">Ask</option>
        <option value="requirement">Requirement</option>
        <option value="give">Give</option>
        <option value="lead">Lead</option>
      </select>
      <input id="brpt-city" type="text" placeholder="Filter by city">
      <input id="brpt-q" type="text" placeholder="Search text">
      <button id="brpt-apply">Apply</button>
    </div>
    <?php
      // If the theme helper exists, show nice cards; otherwise a simple list.
      if (function_exists('brpt_render_cards')) {
        $q = new WP_Query([
          'post_type'      => ['ask','requirement','give','lead'],
          'posts_per_page' => 20,
          'post_status'    => 'publish'
        ]);
        brpt_render_cards($q);
      } else {
        $q = new WP_Query([
          'post_type'      => ['ask','requirement','give','lead'],
          'posts_per_page' => 20,
          'post_status'    => 'publish'
        ]);
        if ($q->have_posts()){
          echo '<ul>';
          while($q->have_posts()){ $q->the_post();
            echo '<li><a href="'.esc_url(get_permalink()).'">'.esc_html(get_the_title()).'</a></li>';
          }
          echo '</ul>';
        }
        wp_reset_postdata();
      }
    ?>
  </section>
</main>
<?php get_footer(); ?>
