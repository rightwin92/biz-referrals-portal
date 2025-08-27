<?php
if (!defined('ABSPATH')) exit;

/** Portal (login/register/forgot) + quick latest */
add_shortcode('brp_portal', function($atts){
  ob_start();
  $logged=is_user_logged_in();
  $success = isset($_GET['brp_login']) && $_GET['brp_login']==='success';
  ?>
  <div class="brp-portal">
    <?php if (!$logged): ?>
      <?php if ($success): ?><div class="brp-notice">Login successful.</div><?php endif; ?>
      <div class="brp-tabs">
        <button class="brp-tab" data-tab="login">Login</button>
        <button class="brp-tab" data-tab="register">Register</button>
        <button class="brp-tab" data-tab="forgot">Forgot Password</button>
      </div>
      <div id="brp-login" class="brp-panel">
        <?php $redirect=add_query_arg('brp_login','success',remove_query_arg(['brp_login'])); wp_login_form(['redirect'=>$redirect]); ?>
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
      <?php foreach (['ask','requirement','give','lead'] as $t){
        $q=new WP_Query(['post_type'=>$t,'posts_per_page'=>5,'post_status'=>'publish']);
        echo '<div class="brp-col"><h4>'.ucfirst($t).'</h4><ul>';
        while($q->have_posts()){ $q->the_post();
          echo '<li><a href="'.get_permalink().'">'.esc_html(get_the_title()).'</a></li>';
        }
        echo '</ul></div>'; wp_reset_postdata();
      } ?>
    </div>
  </div>
  <?php
  return ob_get_clean();
});

/** Submit form (logged-in) */
add_shortcode('brp_submit', function($atts){
  if (!is_user_logged_in()) return '<div class="brp-notice">Please login to submit. Use the tabs above.</div>';
  $types=[
    'ask'         =>'Ask (need detail/requirement/service info)',
    'requirement' =>'Requirement (raise a specific need)',
    'give'        =>'Give (share info/opportunity)',
    'lead'        =>'Lead (provide leads)',
    'response'    =>'Response (Reply/Refer to an Ask)'
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

      <p><label>Attachment (PDF/MP4, max 10MB)<br><input type="file" name="brp_file" accept=".pdf,video/mp4"></label></p>

      <p><label><input type="checkbox" name="brp_legal" required> I agree that this content may be reposted on social media and I accept full responsibility for its accuracy and all legal/financial dealings. The website/admin are not responsible.</label></p>

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
  <?php return ob_get_clean();
});

/** AJAX: register user */
add_action('wp_ajax_nopriv_brp_register_user', function(){
  check_ajax_referer('brp_nonce','nonce');
  $login=sanitize_user($_POST['user_login'] ?? '');
  $email=sanitize_email($_POST['user_email'] ?? '');
  $pass =$_POST['user_pass'] ?? '';
  if (!$login || !$email || !$pass) wp_send_json_error('All fields are required.');
  if (username_exists($login) || email_exists($email)) wp_send_json_error('Username or email already exists.');
  $uid=wp_create_user($login,$pass,$email);
  if (is_wp_error($uid)) wp_send_json_error($uid->get_error_message());
  wp_send_json_success('Account created. You can now login.');
});

/** AJAX: submit post */
add_action('wp_ajax_brp_submit_form', function(){
  check_ajax_referer('brp_nonce','nonce');
  if (!is_user_logged_in()) wp_send_json_error('Login required.');
  if (empty($_POST['brp_legal'])) wp_send_json_error('You must accept the legal disclaimer.');

  $post_type = sanitize_text_field($_POST['post_type'] ?? '');
  if (!in_array($post_type, ['ask','requirement','give','lead','response'], true)) wp_send_json_error('Invalid type.');
  $title = sanitize_text_field($_POST['post_title'] ?? '');
  $content = wp_kses_post($_POST['post_content'] ?? '');
  if (!$title || !$content) wp_send_json_error('Title and Details are required.');

  $post_id = wp_insert_post([
    'post_type'=>$post_type,'post_status'=>'pending','post_title'=>$title,'post_content'=>$content,'post_author'=>get_current_user_id()
  ], true);
  if (is_wp_error($post_id)) wp_send_json_error($post_id->get_error_message());

  foreach (['name','phone','email','city'] as $k){
    if (isset($_POST['brp_'.$k])) update_post_meta($post_id,'_brp_'.$k, sanitize_text_field($_POST['brp_'.$k]));
  }
  update_post_meta($post_id,'_brp_active', isset($_POST['brp_active'])?1:0);
  if (!empty($_POST['brp_start'])) update_post_meta($post_id,'_brp_start', sanitize_text_field($_POST['brp_start']));
  if (!empty($_POST['brp_end']))   update_post_meta($post_id,'_brp_end', sanitize_text_field($_POST['brp_end']));

  if ('response' === $post_type){
    $rtype = sanitize_text_field($_POST['response_type'] ?? 'reply');
    wp_set_object_terms($post_id, $rtype, 'response_type', false);
    $parent = intval($_POST['parent_ask'] ?? 0);
    if ($parent) update_post_meta($post_id,'_brp_parent_ask',$parent);
  }

  if (!empty($_FILES['brp_file']['name'])){
    require_once ABSPATH.'wp-admin/includes/file.php';
    $allowed = ['application/pdf','video/mp4'];
    $file = $_FILES['brp_file'];
    if ($file['size']>10*1024*1024) wp_send_json_error('File too large (max 10MB).');
    $check = wp_check_filetype($file['name']);
    if (!$check['type'] || !in_array($check['type'],$allowed,true)) wp_send_json_error('Only PDF or MP4 allowed.');
    $uploaded = wp_handle_upload($file, ['test_form'=>false]);
    if (isset($uploaded['url'])) update_post_meta($post_id,'_brp_file_url',$uploaded['url']);
  }

  // Notify admin + author
  $admin = get_option('admin_email');
  $site = wp_specialchars_decode(get_bloginfo('name'),ENT_QUOTES);
  wp_mail($admin, '['.$site.'] New '.$post_type.' pending', get_edit_post_link($post_id,''));
  $author_email = get_post_meta($post_id,'_brp_email',true);
  if ($author_email && is_email($author_email)){
    wp_mail($author_email,'Thanks! Your '.$post_type.' is pending review',"We'll notify you after approval.\n\n".$site);
  }

  wp_send_json_success('Submitted! Awaiting admin approval.');
});
