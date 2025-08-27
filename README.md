# Biz Referrals Portal (WordPress Plugin)

A lightweight portal for **Ask · Requirement · Give · Lead · Response** with:
- Frontend submission & login/register tabs
- Start/End scheduling + Active (pause/start)
- Social sharing (WhatsApp, Telegram, X, Email, Copy Link)
- Auto-unpublish after End Date + 24h reminder email
- Author dashboard (bulk Pause/Start/Delete)
- **Admin moderation** screen (Approve/Disapprove/Start/Pause/Delete)

---

## ✅ Permanent Download Links (GitHub Releases)

-**Latest (always up to date):**  
  https://github.com/rightwin92/biz-referrals-portal/releases/download/latest/biz-referrals-portal-latest.zip

- **Versioned (`v1.3.5`):**  
  https://github.com/rightwin92/biz-referrals-portal/releases/download/v1.3.5/biz-referrals-portal-v1.3.5.zip
  
> These files are created by the GitHub Action in `.github/workflows/release.yml`.  
> Create/publish a tag like `v1.3` to generate a versioned ZIP; the workflow also updates the **latest** ZIP.

---

## 🔧 Requirements
- WordPress 5.8+ (PHP 7.4+; PHP 8.x compatible)
- Working mail setup (SMTP plugin recommended) for notifications

---

## 📥 Installation (Step-by-Step)
1. Download either **Latest** or **v1.3** ZIP from the links above.  
2. WP Admin → **Plugins → Add New → Upload Plugin**.  
3. Choose the ZIP → **Install Now** → **Activate**.  
4. (Optional) WP Admin → **Biz Referrals → Settings** → configure **reCAPTCHA v3**.

---

## 🧾 Changelog

**v1.3.5**
- NEW: Front Page Override — always renders the portal UI on the homepage via a safe plugin template (fixes “raw shortcode” issues caused by themes/builders).
- How to disable (optional): add `define('BRP_DISABLE_FRONT_OVERRIDE', true);` to `wp-config.php`.

---

## 🧱 Shortcodes
- **Portal + Auth tabs + Latest:**  
# biz-referrals-portal
