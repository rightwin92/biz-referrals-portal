<?php
if (!defined('ABSPATH')) exit;

add_action('admin_menu', function(){
  add_menu_page('Biz Referrals Portal','Biz Referrals','manage_options','brp-settings','brp_settings_page','dashicons-groups',25);
});
function brp_settings_page(){
  if (!current_user_can('manage_options')) return;
  if (isset($_POST['brp_save'])){
    check_admin_referer('brp_save_settings');
    update_option('brp_recaptcha_enable', isset($_POST['recaptcha_enable'])?1:0);
    update_option('brp_recaptcha_site', sanitize_text_field($_POST['recaptcha_site'] ?? ''));
    update_option('brp_recaptcha_secret', sanitize_text_field($_POST['recaptcha_secret'] ?? ''));
    echo '<div class="updated"><p>Saved.</p></div>';
  }
  $enable=(int)get_option('brp_recaptcha_enable',0);
  $site=esc_attr(get_option('brp_recaptcha_site',''));
  $secret=esc_attr(get_option('brp_recaptcha_secret',''));
  ?>
  <div class="wrap"><h1>Biz Referrals Portal Settings</h1>
    <form method="post"><?php wp_nonce_field('brp_save_settings'); ?>
      <table class="form-table">
        <tr><th>Enable reCAPTCHA v3</th><td><input type="checkbox" name="recaptcha_enable" value="1" <?php checked($enable,1); ?>></td></tr>
        <tr><th>reCAPTCHA Site Key</th><td><input type="text" name="recaptcha_site" value="<?php echo $site; ?>" class="regular-text"></td></tr>
        <tr><th>reCAPTCHA Secret</th><td><input type="text" name="recaptcha_secret" value="<?php echo $secret; ?>" class="regular-text"></td></tr>
      </table>
      <p class="submit"><button type="submit" name="brp_save" class="button button-primary">Save Settings</button></p>
    </form>

    <h2>Shortcodes</h2>
    <ol>
      <li><code>[brp_portal]</code> — Portal with Login/Register/Forgot or Account panel + Latest.</li>
      <li><code>[brp_submit]</code> — Front-end submission form (logged-in users only).</li>
      <li><code>[brp_dashboard]</code> — Author dashboard with bulk Pause/Start/Delete.</li>
    </ol>
  </div>
  <?php
}
