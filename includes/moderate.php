<?php
if (!defined('ABSPATH')) exit;

/** Admin moderation screen */
add_action('admin_menu', function(){
  add_menu_page('BRP Moderate','BRP Moderate','edit_others_posts','brp-moderate','brp_render_moderate','dashicons-yes',26);
});
function brp_render_moderate(){
  if(!current_user_can('edit_others_posts')){ wp_die('Insufficient permissions'); }

  // Handle bulk
  if (isset($_POST['brp_mod_nonce']) && wp_verify_nonce($_POST['brp_mod_nonce'],'brp_mod')){
    $action=sanitize_text_field($_POST['action_type'] ?? '');
    $ids = array_map('intval',(array)($_POST['ids'] ?? []));
    foreach($ids as $id){
      $p=get_post($id); if(!$p) continue;
      switch($action){
        case 'approve': wp_update_post(['ID'=>$id,'post_status'=>'publish']); break;
        case 'disapprove': wp_update_post(['ID'=>$id,'post_status'=>'pending']); break;
        case 'start': update_post_meta($id,'_brp_active',1); break;
        case 'pause': update_post_meta($id,'_brp_active',0); break;
        case 'delete': wp_trash_post($id); break;
      }
    }
    echo '<div class="updated"><p>Action applied.</p></div>';
  }

  $status = isset($_GET['status']) ? sanitize_text_field($_GET['status']) : 'pending';
  $q = new WP_Query([
    'post_type'=>['ask','requirement','give','lead','response'],
    'post_status'=> $status==='all' ? ['publish','pending','draft'] : $status,
    'posts_per_page'=>50,'orderby'=>'date','order'=>'DESC'
  ]);

  ?>
  <div class="wrap"><h1>BRP Moderate</h1>
    <p>
      <a class="button" href="<?php echo esc_url(add_query_arg('status','pending')); ?>">Pending</a>
      <a class="button" href="<?php echo esc_url(add_query_arg('status','publish')); ?>">Published</a>
      <a class="button" href="<?php echo esc_url(add_query_arg('status','draft')); ?>">Draft</a>
      <a class="button" href="<?php echo esc_url(add_query_arg('status','all')); ?>">All</a>
    </p>
    <form method="post"><?php wp_nonce_field('brp_mod','brp_mod_nonce'); ?>
      <div style="display:flex;gap:8px;align-items:center;margin-bottom:8px;">
        <select name="action_type" required>
          <option value="">Bulk action</option>
          <option value="approve">Approve (Publish)</option>
          <option value="disapprove">Disapprove (Pending)</option>
          <option value="start">Start (Active)</option>
          <option value="pause">Pause (Inactive)</option>
          <option value="delete">Delete (Trash)</option>
        </select>
        <button class="button button-primary">Apply</button>
      </div>
      <table class="widefat">
        <thead><tr><th><input type="checkbox" onclick="document.querySelectorAll('.brp-mod input[type=checkbox]').forEach(cb=>cb.checked=this.checked)"></th><th>Title</th><th>Type</th><th>Status</th><th>Active</th><th>Author</th><th>Date</th></tr></thead>
        <tbody class="brp-mod">
        <?php if($q->have_posts()): while($q->have_posts()): $q->the_post(); $id=get_the_ID(); ?>
          <tr>
            <td><input type="checkbox" name="ids[]" value="<?php echo esc_attr($id); ?>"></td>
            <td><a href="<?php echo esc_url(get_edit_post_link($id)); ?>"><?php the_title(); ?></a></td>
            <td><?php echo esc_html(ucfirst(get_post_type())); ?></td>
            <td><?php echo esc_html(get_post_status()); ?></td>
            <td><?php echo get_post_meta($id,'_brp_active',true)?'Yes':'No'; ?></td>
            <td><?php the_author(); ?></td>
            <td><?php echo esc_html(get_the_date().' '.get_the_time()); ?></td>
          </tr>
        <?php endwhile; wp_reset_postdata(); else: ?>
          <tr><td colspan="7">No posts found.</td></tr>
        <?php endif; ?>
        </tbody>
      </table>
    </form>
  </div>
  <?php
}
