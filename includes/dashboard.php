<?php
if (!defined('ABSPATH')) exit;

/** Author dashboard */
add_shortcode('brp_dashboard', function($atts){
  if(!is_user_logged_in()) return '<div class="brp-notice">Please login to manage your posts.</div>';
  $u=get_current_user_id();

  // Handle bulk actions
  if (!empty($_POST['brp_bulk_action']) && !empty($_POST['brp_ids']) && isset($_POST['brp_dash_nonce']) && wp_verify_nonce($_POST['brp_dash_nonce'],'brp_dash')){
    $action=sanitize_text_field($_POST['brp_bulk_action']);
    $ids=array_map('intval',(array)$_POST['brp_ids']);
    foreach($ids as $id){
      $p=get_post($id); if(!$p) continue;
      if ($p->post_author!=$u && !current_user_can('manage_options')) continue;
      if ($action==='pause') update_post_meta($id,'_brp_active',0);
      elseif($action==='start') update_post_meta($id,'_brp_active',1);
      elseif($action==='delete') wp_trash_post($id);
    }
    echo '<div class="brp-notice">Bulk action applied.</div>';
  }

  $q=new WP_Query([
    'author'=>$u,
    'post_type'=>['ask','requirement','give','lead','response'],
    'post_status'=>['publish','pending','draft','future','private'],
    'posts_per_page'=>50,'orderby'=>'date','order'=>'DESC'
  ]);

  ob_start(); ?>
  <div class="brp-dash">
    <form method="post"><?php wp_nonce_field('brp_dash','brp_dash_nonce'); ?>
      <div class="brp-dash-controls">
        <select name="brp_bulk_action" required>
          <option value="">Bulk Actions</option>
          <option value="start">Start</option>
          <option value="pause">Pause</option>
          <option value="delete">Delete</option>
        </select>
        <button class="button">Apply</button>
      </div>
      <table class="brp-dash-table">
        <thead><tr><th><input type="checkbox" onclick="document.querySelectorAll('.brp-dash-table input[type=checkbox]').forEach(cb=>cb.checked=this.checked)"></th><th>Title</th><th>Type</th><th>Status</th><th>Active</th><th>Start</th><th>End</th></tr></thead>
        <tbody>
        <?php if($q->have_posts()): while($q->have_posts()): $q->the_post(); $id=get_the_ID(); ?>
          <tr>
            <td><input type="checkbox" name="brp_ids[]" value="<?php echo esc_attr($id); ?>"></td>
            <td><a href="<?php echo esc_url(get_permalink()); ?>" target="_blank"><?php the_title(); ?></a></td>
            <td><?php echo esc_html(ucfirst(get_post_type())); ?></td>
            <td><?php echo esc_html(get_post_status()); ?></td>
            <td><?php echo get_post_meta($id,'_brp_active',true)?'Yes':'No'; ?></td>
            <td><?php echo esc_html(get_post_meta($id,'_brp_start',true)); ?></td>
            <td><?php echo esc_html(get_post_meta($id,'_brp_end',true)); ?></td>
          </tr>
        <?php endwhile; wp_reset_postdata(); else: ?>
          <tr><td colspan="7">No posts yet.</td></tr>
        <?php endif; ?>
        </tbody>
      </table>
    </form>
  </div>
  <?php return ob_get_clean();
});

