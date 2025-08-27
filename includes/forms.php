<?php
if (!defined('ABSPATH')) exit;

/**
 * PORTAL (login/register/forgot + latest lists)
 */
function brp_sc_portal($atts){
  ob_start();
  $logged  = is_user_logged_in();
  $success = isset($_GET['brp_login']) && $_GET['brp_login'] === 'success';
  ?>
  <div class="brp-portal">
    <?php if(!$logged): ?>
      <?php if ($success): ?><div class="brp-notice">Login successful.</div><?php endif; ?>

      <div class="brp-tabs">
        <button class="brp-tab" data-tab="login">Login</button>
        <button class="brp-tab" data-tab="register">Register</button>
        <button class="brp-tab" data-tab="forgot">Forgot Password</button>
      </div>

      <div id="brp-login" class="brp-panel">
        <?php
          // Redirect back to same page with ?brp_login=success
          $redirect = add_query_arg('brp_login','success', remove_query_arg(['brp_login']));
          wp_login_form(['redirect' => $redirect]);
        ?>
      </div>

      <div id="brp-register" class="brp-panel" style="display:none">
        <form id="brp-register-form">
          <p><label>Username*<br><input type="text" name="user_login" required></label></p>
          <p><label>Email*<br><input type="email" name="user_email" required></label></p>
          <p><label>Password*<br><input type="password" name="user_pass" required></label></p>
          <p><button type="submit">Create Account</button></p>
          <input type="hidden" name="action" value="brp_register_user">
          <input type="hidden" name="nonce" value="<?php echo esc_attr(wp_create_nonce('brp_nonce')); ?>">
        </form>
        <div id="brp-register-msg"></div>
      </div>

      <div id="brp-forgot" class="brp-panel" style="display:none">
        <p><a class="button" href="<?php echo esc_url(wp_lostpassword_url()); ?>">Reset your password</a></p>
      </div>

    <?php else: ?>
      <div class="brp-account">
        <div class="brp-notice">Welcome, <?php $u=wp_get_current_user(); echo esc_html($u->display_name ?: $u->user_login); ?>.</div>
        <p class="brp-actions">
          <a class="button" href="#brp-submit">Submit a Post</a>
          <?php if(get_page_by_path('my-posts')): ?>
            <a class="button" href="<?php echo esc_url(get_permalink(get_page_by_path('my-posts'))); ?>">My Posts</a>
          <?php endif; ?>
          <a class="button" href="<?php echo esc_url(wp_logout_url(home_url('/'))); ?>">Logout</a>
        </p>
      </div>
    <?php endif; ?>

    <hr>
    <h3>Latest</h3>
    <div class="brp-lists">
      <?php
      foreach (['ask','requirement','give','lead'] as $t){
        $q = new WP_Query(['post_type'=>$t,'posts_per_page'=>5,'post_status'=>'publish']);
        echo '<div class="brp-col"><h4>'.ucfirst($t).'</h4><ul>';
        while($q->have_posts()){ $q->the_post();
          echo '<li><a href="'.esc_url(get_permalink()).'">'.esc_html(get_the_title()).'</a></li>';
        }
        echo '</ul></div>';
        wp_reset_postdata();
      }
      ?>
    </div>
  </div>
  <?php
  return ob_get_clean();
}

/**
 * SUBMIT (front-end form, logged-in only)
 */
function brp_sc_submit($atts){
  if (!is_user_logged_in()){
    return '<div class="brp-notice">Please login to submit. Use the tabs above.</div>';
  }
  $types = [
    'ask'         => 'Ask (need detail/requirement/service info)',
    'requirement' => 'Requirement (raise a specific need)',
    'give'        => 'Give (share info/opportunity)',
    'lead'        => 'Lead (provide leads)',
    'response'    => 'Response (Reply/Refer to an Ask)'
  ];

  ob_start(); ?>
  <div id="brp-submit" class="brp-submit">
    <form id="brp-submit-form" enctype="multipart/form-data">
      <p><label>Submission Type*<br>
        <select name="post_type" required>
          <?php foreach($types as $k=>$v) echo '<option value="'.$k.'">'.$v.'</option>'; ?>
        </select></label></p>

      <div class="brp-response-type" style="display:none">
        <p><label>Response Type*<br>
          <select name="response_type">
            <option value="reply">Reply</option>
            <option value="refer">Refer</option>
          </select></label></p>
        <p><label>Responding to Ask (optional)<br>
          <input type="number" name="parent_ask" placeholder="Enter Ask Post ID"></label></p>
      </div>

      <p><label>Title*<br><input type="text" name="post_title" required></label></p>
      <p><label>Details*<br><textarea name="post_content" rows="6" required></textarea></label></p>

      <fieldset><legend>Author Details (Required)</legend>
        <p><label>Name*<br><input type="text" name="brp_name" required></label></p>
        <p><label>Phone*<br><input type="text" name="brp_phone" required></label></p>
        <p><label>Email*<br><input type="email" name="brp_email" required></label></p>
        <p><label>City*<br><input type="text" name="brp_city" required></label></p>
      </fieldset>

      <fieldset><legend>Schedule</legend>
        <p><label><input type="checkbox" name="brp_active" value="1" checked> Active (uncheck to pause)</label></p>
        <p><label>Start Date (optional)<br><input type="datetime-local" name="brp_start"></label></p>
        <p><label>End Date (optional)<br><input type="datetime-local" name="brp_end"></label></p>
      </fieldset>

      <p><label>Attachment (PDF/MP4/JPG/PNG/GIF/WEBP, max 10MB)<br>
        <input type="file" name="brp_file" accept=".pdf,video/mp4,image/jpeg,image/png,image/gif,image/webp">
      </label></p>

      <p><label><input type="checkbox" name="brp_legal" required>
        I agree that this content may be reposted on social media and I accept full responsibility for its accuracy and all legal/financial dealings. The website/admin are not responsible.</label></p>

      <p><button type="submit">Submit for Review</button></p>
      <input type="hidden" name="action" value="brp_submit_form">
      <input type="hidden" name="nonce" value="<?php echo esc_attr(wp_create_nonce('brp_nonce')); ?>">
    </form>
    <div id="brp-submit-msg"></div>
  </div>

  <script>
    (function(){
      const s=document.querySelector('#brp-submit-form select[name="post_type"]');
      const b=document.querySelector('.brp-response-type');
      function t(){ b.style.display=(s.value==='response')?'block':'none'; }
      s.addEventListener('change',t); t();
    })();
  </script>
  <?php
  return ob_get_clean();
}

/**
 * DASHBOARD (author list + bulk actions)
 */
function brp_sc_dashboard($atts){
  if (!is_user_logged_in()){
    return '<div class="brp-notice">Please login to manage your posts.</div>';
  }
  $u = get_current_user_id();

  // Bulk actions
  if (!empty($_POST['brp_bulk_action']) && !empty($_POST['brp_ids'])
      && isset($_POST['brp_dash_nonce']) && wp_verify_nonce($_POST['brp_dash_nonce'],'brp_dash')){
    $action = sanitize_text_field($_POST['brp_bulk_action']);
    $ids    = array_map('intval',(array)$_POST['brp_ids']);
    foreach($ids as $id){
      $p=get_post($id); if(!$p) continue;
      if ($p->post_author!=$u && !current_user_can('manage_options')) continue;
      if ($action==='pause') update_post_meta($id,'_brp_active',0);
      elseif($action==='start') update_post_meta($id,'_brp_active',1);
      elseif($action==='delete') wp_trash_post($id);
    }
    echo '<div class="brp-notice">Bulk action applied.</div>';
  }

  $q = new WP_Query([
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
        <thead><tr>
          <th><input type="checkbox" onclick="document.querySelectorAll('.brp-dash-table input[type=checkbox]').forEach(cb=>cb.checked=this.checked)"></th>
          <th>Title</th><th>Type</th><th>Status</th><th>Active</th><th>Start</th><th>End</th>
        </tr></thead>
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
  <?php
  return ob_get_clean();
}

/* ---------------------------
 * AJAX: Register user (public)
 * --------------------------- */
add_action('wp_ajax_nopriv_brp_register_user', function(){
  check_ajax_referer('brp_nonce','nonce');
  $login = sanitize_user($_POST['user_login'] ?? '');
  $email = sanitize_email($_POST['user_email'] ?? '');
  $pass  = $_POST['user_pass'] ?? '';
  if (!$login || !$email || !$pass) wp_send_json_error('All fields are required.');
  if (username_exists($login) || email_exists($email)) wp_send_json_error('Username or email already exists.');
  $uid = wp_create_user($login,$pass,$email);
  if (is_wp_error($uid)) wp_send_json_error($uid->get_error_message());
  wp_send_json_success('Account created. You can now login.');
});

/* ---------------------------
 * AJAX: Submit post (logged-in)
 * --------------------------- */
add_action('wp_ajax_brp_submit_form', function(){
  check_ajax_referer('brp_nonce','nonce');
  if (!is_user_logged_in()) wp_send_json_error('Login required.');
  if (empty($_POST['brp_legal'])) wp_send_json_error('You must accept the legal disclaimer.');

  $post_type = sanitize_text_field($_POST['post_type'] ?? '');
  if (!in_array($post_type, ['ask','requirement','give','lead','response'], true)) wp_send_json_error('Invalid type.');

  $title   = sanitize_text_field($_POST['post_title'] ?? '');
  $content = wp_kses_post($_POST['post_content'] ?? '');
  if (!$title || !$content) wp_send_json_error('Title and Details are required.');

  $post_id = wp_insert_post([
    'post_type'=>$post_type,
    'post_status'=>'pending',
    'post_title'=>$title,
    'post_content'=>$content,
    'post_author'=>get_current_user_id()
  ], true);
  if (is_wp_error($post_id)) wp_send_json_error($post_id->get_error_message());

  // Save meta
  foreach (['name','phone','email','city'] as $k){
    if (isset($_POST['brp_'.$k])) update_post_meta($post_id,'_brp_'.$k, sanitize_text_field($_POST['brp_'.$k]));
  }
  update_post_meta($post_id,'_brp_active', isset($_POST['brp_active'])?1:0);
  if (!empty($_POST['brp_start'])) update_post_meta($post_id,'_brp_start', sanitize_text_field($_POST['brp_start']));
  if (!empty($_POST['brp_end']))   update_post_meta($post_id,'_brp_end', sanitize_text_field($_POST['brp_end']));

  // Response taxonomy & parent link
  if ('response' === $post_type){
    $rtype  = sanitize_text_field($_POST['response_type'] ?? 'reply');
    $parent = intval($_POST['parent_ask'] ?? 0);
    wp_set_object_terms($post_id, $rtype, 'response_type', false);
    if ($parent) update_post_meta($post_id,'_brp_parent_ask', $parent);
  }

  // File upload (PDF/MP4 + images)
  if (!empty($_FILES['brp_file']['name'])){
    require_once ABSPATH.'wp-admin/includes/file.php';

    if ($_FILES['brp_file']['size'] > 10 * 1024 * 1024){
      wp_send_json_error('File too large (max 10MB). Please compress or choose a smaller file.');
    }
    $allowed = [
      'application/pdf','video/mp4',
      'image/jpeg','image/png','image/gif','image/webp'
    ];
    $check = wp_check_filetype($_FILES['brp_file']['name']);
    if (!$check['type'] || !in_array($check['type'], $allowed, true)){
      wp_send_json_error('Only PDF, MP4, JPG, PNG, GIF, WEBP are allowed.');
    }
    $uploaded = wp_handle_upload($_FILES['brp_file'], ['test_form'=>false]);
    if (!empty($uploaded['error'])) wp_send_json_error('Upload failed: '.$uploaded['error']);
    if (isset($uploaded['url'])) update_post_meta($post_id,'_brp_file_url', esc_url_raw($uploaded['url']));
  }

  // Notify admin + author
  $admin = get_option('admin_email');
  $site  = wp_specialchars_decode(get_bloginfo('name'),ENT_QUOTES);
  @wp_mail($admin, '['.$site.'] New '.$post_type.' pending', get_edit_post_link($post_id,''));

  $author_email = get_post_meta($post_id,'_brp_email',true);
  if ($author_email && is_email($author_email)){
    @wp_mail($author_email,'Thanks! Your '.$post_type.' is pending review',"We'll notify you after approval.\n\n".$site);
  }

  wp_send_json_success('Submitted! Awaiting admin approval.');
});

/* ---------------------------
 * REGISTER SHORTCODES
 * --------------------------- */
add_shortcode('brp_portal',    'brp_sc_portal');
add_shortcode('brp_submit',    'brp_sc_submit');
add_shortcode('brp_dashboard', 'brp_sc_dashboard');
