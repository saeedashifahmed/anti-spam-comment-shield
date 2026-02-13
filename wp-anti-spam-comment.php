<?php
/**
 * Plugin Name:       WP Anti-Spam Comment
 * Plugin URI:        https://wordpress.org/plugins/wp-anti-spam-comment/
 * Description:       Advanced, lightweight, and GDPR-compliant anti-spam protection for WordPress comments. Zero configuration needed — just activate and forget spam forever.
 * Author:            Rabbit Builds (Saeed Ashif Ahmed)
 * Author URI:        https://rabbitbuilds.com/
 * Version:           2.0.1
 * Text Domain:       wp-anti-spam-comment
 * Domain Path:       /languages
 * Requires at least: 5.0
 * Requires PHP:      7.4
 * License:           GPL-2.0-or-later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 */

// If this file is called directly, abort.
defined('ABSPATH') || die();

/**
 * ─── Constants ───────────────────────────────────────────────────────────────
 */
define('WP_ANTI_SPAM_COMMENT_VERSION', '2.0.1');
define('WP_ANTI_SPAM_COMMENT_FILE', __FILE__);
define('WP_ANTI_SPAM_COMMENT_DIR', plugin_dir_path(__FILE__));
define('WP_ANTI_SPAM_COMMENT_URL', plugin_dir_url(__FILE__));

// Generate a unique key from NONCE_SALT or DOCUMENT_ROOT
$wp_anti_spam_comment_key_source = defined('NONCE_SALT') && NONCE_SALT ? NONCE_SALT : $_SERVER['DOCUMENT_ROOT'];
define('WP_ANTI_SPAM_COMMENT_UNIQUE_KEY', md5($wp_anti_spam_comment_key_source));

/**
 * ─── Default Settings ────────────────────────────────────────────────────────
 */
function wp_anti_spam_comment_get_defaults()
{
    return array(
        'enable_hash_check' => 1,
        'enable_honeypot' => 1,
        'enable_time_check' => 1,
        'min_submit_time' => 3,
        'blocked_message' => __('Your comment was blocked by our anti-spam protection. If you believe this is an error, please try again.', 'wp-anti-spam-comment'),
        'enable_rest_protect' => 1,
    );
}

function wp_anti_spam_comment_get_options()
{
    $defaults = wp_anti_spam_comment_get_defaults();
    $options = get_option('wp_anti_spam_comment_settings', array());
    return wp_parse_args($options, $defaults);
}

/**
 * ─── Activation Hook ─────────────────────────────────────────────────────────
 */
register_activation_hook(__FILE__, 'wp_anti_spam_comment_activation_hook');

function wp_anti_spam_comment_activation_hook()
{
    set_transient('wp-anti-spam-comment-activation-notice', true, 5);

    // Initialize stats if not existing
    if (false === get_option('wp_anti_spam_comment_stats')) {
        update_option('wp_anti_spam_comment_stats', array(
            'blocked_total' => 0,
            'blocked_today' => 0,
            'blocked_date' => current_time('Y-m-d'),
            'last_blocked_at' => '',
        ));
    }

    // Initialize default settings
    if (false === get_option('wp_anti_spam_comment_settings')) {
        update_option('wp_anti_spam_comment_settings', wp_anti_spam_comment_get_defaults());
    }
}

/**
 * ─── Deactivation Hook ──────────────────────────────────────────────────────
 */
register_deactivation_hook(__FILE__, 'wp_anti_spam_comment_deactivation_hook');

function wp_anti_spam_comment_deactivation_hook()
{
    // Clean up transients only; preserve stats and settings
    delete_transient('wp-anti-spam-comment-activation-notice');
}

/**
 * ─── Admin Notice on Activation ─────────────────────────────────────────────
 */
add_action('admin_notices', 'wp_anti_spam_comment_activation_notice');

function wp_anti_spam_comment_activation_notice()
{
    if (get_transient('wp-anti-spam-comment-activation-notice')) {
        ?>
        <style>
            div#message.updated {
                display: none;
            }
        </style>
        <div class="notice notice-success is-dismissible" style="border-left-color: #DC2626;">
            <p>
                <strong>🛡️ WP Anti-Spam Comment</strong> is now active!
                <?php _e('Please <strong>clear your page cache</strong> for the protection to take effect.', 'wp-anti-spam-comment'); ?>
            </p>
        </div>
        <?php
        delete_transient('wp-anti-spam-comment-activation-notice');
    }
}

/**
 * ─── Plugin Action Links (Settings + Support) ──────────────────────────────
 */
add_filter('plugin_action_links_' . plugin_basename(__FILE__), 'wp_anti_spam_comment_action_links');

function wp_anti_spam_comment_action_links($links)
{
    $custom_links = array(
        '<a href="' . admin_url('options-general.php?page=wp-anti-spam-comment') . '">' . __('Settings', 'wp-anti-spam-comment') . '</a>',
        '<a rel="noopener" title="Technical Support" href="https://rabbitbuilds.com/contact/" target="_blank">' . __('Get Support', 'wp-anti-spam-comment') . '</a>',
    );
    return array_merge($custom_links, $links);
}

/**
 * ─── Admin Menu & Settings Page ─────────────────────────────────────────────
 */
add_action('admin_menu', 'wp_anti_spam_comment_admin_menu');

function wp_anti_spam_comment_admin_menu()
{
    add_options_page(
        __('WP Anti-Spam Comment', 'wp-anti-spam-comment'),
        __('WP Anti-Spam', 'wp-anti-spam-comment'),
        'manage_options',
        'wp-anti-spam-comment',
        'wp_anti_spam_comment_settings_page'
    );
}

/**
 * ─── Register Settings ──────────────────────────────────────────────────────
 */
add_action('admin_init', 'wp_anti_spam_comment_register_settings');

function wp_anti_spam_comment_register_settings()
{
    register_setting('wp_anti_spam_comment_settings_group', 'wp_anti_spam_comment_settings', 'wp_anti_spam_comment_sanitize_settings');
}

function wp_anti_spam_comment_sanitize_settings($input)
{
    $sanitized = array();
    $sanitized['enable_hash_check'] = isset($input['enable_hash_check']) ? 1 : 0;
    $sanitized['enable_honeypot'] = isset($input['enable_honeypot']) ? 1 : 0;
    $sanitized['enable_time_check'] = isset($input['enable_time_check']) ? 1 : 0;
    $sanitized['min_submit_time'] = isset($input['min_submit_time']) ? absint($input['min_submit_time']) : 3;
    $sanitized['blocked_message'] = isset($input['blocked_message']) ? sanitize_textarea_field($input['blocked_message']) : '';
    $sanitized['enable_rest_protect'] = isset($input['enable_rest_protect']) ? 1 : 0;

    if ($sanitized['min_submit_time'] < 1) {
        $sanitized['min_submit_time'] = 1;
    }
    if ($sanitized['min_submit_time'] > 30) {
        $sanitized['min_submit_time'] = 30;
    }

    return $sanitized;
}

/**
 * ─── Enqueue Admin Assets ───────────────────────────────────────────────────
 */
add_action('admin_enqueue_scripts', 'wp_anti_spam_comment_admin_assets');

function wp_anti_spam_comment_admin_assets($hook)
{
    if ('settings_page_wp-anti-spam-comment' !== $hook) {
        return;
    }

    wp_enqueue_style(
        'wp-anti-spam-comment-admin',
        WP_ANTI_SPAM_COMMENT_URL . 'admin/css/admin-style.css',
        array(),
        WP_ANTI_SPAM_COMMENT_VERSION
    );

    wp_enqueue_script(
        'wp-anti-spam-comment-admin-js',
        WP_ANTI_SPAM_COMMENT_URL . 'admin/js/admin-script.js',
        array(),
        WP_ANTI_SPAM_COMMENT_VERSION,
        true
    );
}

/**
 * ─── Settings Page Render ───────────────────────────────────────────────────
 */
function wp_anti_spam_comment_settings_page()
{
    $options = wp_anti_spam_comment_get_options();
    $stats = get_option('wp_anti_spam_comment_stats', array(
        'blocked_total' => 0,
        'blocked_today' => 0,
        'blocked_date' => current_time('Y-m-d'),
        'last_blocked_at' => '',
    ));

    // Reset daily counter if it's a new day
    if (isset($stats['blocked_date']) && $stats['blocked_date'] !== current_time('Y-m-d')) {
        $stats['blocked_today'] = 0;
        $stats['blocked_date'] = current_time('Y-m-d');
        update_option('wp_anti_spam_comment_stats', $stats);
    }

    ?>
    <div class="wrap wpasc-wrap">

        <!-- Header -->
        <div class="wpasc-header">
            <div class="wpasc-header-content">
                <div class="wpasc-header-icon">
                    <svg width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                        stroke-linecap="round" stroke-linejoin="round">
                        <path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z" />
                        <path d="M9 12l2 2 4-4" />
                    </svg>
                </div>
                <div>
                    <h1 class="wpasc-title"><?php _e('WP Anti-Spam Comment', 'wp-anti-spam-comment'); ?></h1>
                    <p class="wpasc-subtitle">
                        <?php _e('Advanced spam protection for WordPress comments', 'wp-anti-spam-comment'); ?></p>
                </div>
                <span class="wpasc-version">v<?php echo esc_html(WP_ANTI_SPAM_COMMENT_VERSION); ?></span>
            </div>
        </div>

        <!-- Stats Cards -->
        <div class="wpasc-stats-grid">
            <div class="wpasc-stat-card wpasc-stat-blocked">
                <div class="wpasc-stat-icon">
                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                        stroke-linecap="round" stroke-linejoin="round">
                        <circle cx="12" cy="12" r="10" />
                        <line x1="4.93" y1="4.93" x2="19.07" y2="19.07" />
                    </svg>
                </div>
                <div class="wpasc-stat-info">
                    <span class="wpasc-stat-number" data-count="<?php echo absint($stats['blocked_total']); ?>">0</span>
                    <span class="wpasc-stat-label"><?php _e('Total Blocked', 'wp-anti-spam-comment'); ?></span>
                </div>
            </div>
            <div class="wpasc-stat-card wpasc-stat-today">
                <div class="wpasc-stat-icon">
                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                        stroke-linecap="round" stroke-linejoin="round">
                        <rect x="3" y="4" width="18" height="18" rx="2" ry="2" />
                        <line x1="16" y1="2" x2="16" y2="6" />
                        <line x1="8" y1="2" x2="8" y2="6" />
                        <line x1="3" y1="10" x2="21" y2="10" />
                    </svg>
                </div>
                <div class="wpasc-stat-info">
                    <span class="wpasc-stat-number" data-count="<?php echo absint($stats['blocked_today']); ?>">0</span>
                    <span class="wpasc-stat-label"><?php _e('Blocked Today', 'wp-anti-spam-comment'); ?></span>
                </div>
            </div>
            <div class="wpasc-stat-card wpasc-stat-status">
                <div class="wpasc-stat-icon">
                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                        stroke-linecap="round" stroke-linejoin="round">
                        <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14" />
                        <polyline points="22 4 12 14.01 9 11.01" />
                    </svg>
                </div>
                <div class="wpasc-stat-info">
                    <span class="wpasc-stat-status-text"><?php _e('Active', 'wp-anti-spam-comment'); ?></span>
                    <span class="wpasc-stat-label"><?php _e('Protection', 'wp-anti-spam-comment'); ?></span>
                </div>
            </div>
            <div class="wpasc-stat-card wpasc-stat-last">
                <div class="wpasc-stat-icon">
                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                        stroke-linecap="round" stroke-linejoin="round">
                        <circle cx="12" cy="12" r="10" />
                        <polyline points="12 6 12 12 16 14" />
                    </svg>
                </div>
                <div class="wpasc-stat-info">
                    <span class="wpasc-stat-last-time">
                        <?php
                        if (!empty($stats['last_blocked_at'])) {
                            echo esc_html(human_time_diff(strtotime($stats['last_blocked_at']), current_time('timestamp')) . ' ' . __('ago', 'wp-anti-spam-comment'));
                        } else {
                            _e('No spam yet', 'wp-anti-spam-comment');
                        }
                        ?>
                    </span>
                    <span class="wpasc-stat-label"><?php _e('Last Blocked', 'wp-anti-spam-comment'); ?></span>
                </div>
            </div>
        </div>

        <!-- Settings Form -->
        <form method="post" action="options.php" class="wpasc-settings-form">
            <?php settings_fields('wp_anti_spam_comment_settings_group'); ?>

            <!-- Protection Modules -->
            <div class="wpasc-card">
                <div class="wpasc-card-header">
                    <h2>
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                            stroke-linecap="round" stroke-linejoin="round">
                            <rect x="3" y="11" width="18" height="11" rx="2" ry="2" />
                            <path d="M7 11V7a5 5 0 0 1 10 0v4" />
                        </svg>
                        <?php _e('Protection Modules', 'wp-anti-spam-comment'); ?>
                    </h2>
                    <p class="wpasc-card-desc">
                        <?php _e('Enable or disable individual spam protection layers.', 'wp-anti-spam-comment'); ?></p>
                </div>
                <div class="wpasc-card-body">

                    <!-- Hash-Based Verification -->
                    <div class="wpasc-setting-row">
                        <div class="wpasc-setting-info">
                            <label class="wpasc-setting-title" for="enable_hash_check">
                                <?php _e('Hash-Based Verification', 'wp-anti-spam-comment'); ?>
                                <span
                                    class="wpasc-badge wpasc-badge-recommended"><?php _e('Core', 'wp-anti-spam-comment'); ?></span>
                            </label>
                            <p class="wpasc-setting-desc">
                                <?php _e('Blocks bots by requiring a unique hash token in the comment form action URL — only injected via JavaScript when a real user interacts with the page.', 'wp-anti-spam-comment'); ?>
                            </p>
                        </div>
                        <label class="wpasc-toggle">
                            <input type="checkbox" name="wp_anti_spam_comment_settings[enable_hash_check]"
                                id="enable_hash_check" value="1" <?php checked($options['enable_hash_check'], 1); ?> />
                            <span class="wpasc-toggle-slider"></span>
                        </label>
                    </div>

                    <!-- Honeypot Field -->
                    <div class="wpasc-setting-row">
                        <div class="wpasc-setting-info">
                            <label class="wpasc-setting-title" for="enable_honeypot">
                                <?php _e('Honeypot Trap', 'wp-anti-spam-comment'); ?>
                                <span
                                    class="wpasc-badge wpasc-badge-new"><?php _e('New', 'wp-anti-spam-comment'); ?></span>
                            </label>
                            <p class="wpasc-setting-desc">
                                <?php _e('Adds a hidden field to the comment form that only bots will fill out. Human visitors never see it.', 'wp-anti-spam-comment'); ?>
                            </p>
                        </div>
                        <label class="wpasc-toggle">
                            <input type="checkbox" name="wp_anti_spam_comment_settings[enable_honeypot]"
                                id="enable_honeypot" value="1" <?php checked($options['enable_honeypot'], 1); ?> />
                            <span class="wpasc-toggle-slider"></span>
                        </label>
                    </div>

                    <!-- Time-Based Check -->
                    <div class="wpasc-setting-row">
                        <div class="wpasc-setting-info">
                            <label class="wpasc-setting-title" for="enable_time_check">
                                <?php _e('Time-Based Check', 'wp-anti-spam-comment'); ?>
                                <span
                                    class="wpasc-badge wpasc-badge-new"><?php _e('New', 'wp-anti-spam-comment'); ?></span>
                            </label>
                            <p class="wpasc-setting-desc">
                                <?php
                                printf(
                                    __('Rejects comments submitted within %s seconds of page load. Real users take at least a few seconds to type.', 'wp-anti-spam-comment'),
                                    '<strong>' . absint($options['min_submit_time']) . '</strong>'
                                );
                                ?>
                            </p>
                        </div>
                        <label class="wpasc-toggle">
                            <input type="checkbox" name="wp_anti_spam_comment_settings[enable_time_check]"
                                id="enable_time_check" value="1" <?php checked($options['enable_time_check'], 1); ?> />
                            <span class="wpasc-toggle-slider"></span>
                        </label>
                    </div>

                    <!-- REST API Protection -->
                    <div class="wpasc-setting-row">
                        <div class="wpasc-setting-info">
                            <label class="wpasc-setting-title" for="enable_rest_protect">
                                <?php _e('REST API Protection', 'wp-anti-spam-comment'); ?>
                                <span
                                    class="wpasc-badge wpasc-badge-new"><?php _e('New', 'wp-anti-spam-comment'); ?></span>
                            </label>
                            <p class="wpasc-setting-desc">
                                <?php _e('Blocks unauthenticated comment creation through the WordPress REST API endpoint.', 'wp-anti-spam-comment'); ?>
                            </p>
                        </div>
                        <label class="wpasc-toggle">
                            <input type="checkbox" name="wp_anti_spam_comment_settings[enable_rest_protect]"
                                id="enable_rest_protect" value="1" <?php checked($options['enable_rest_protect'], 1); ?> />
                            <span class="wpasc-toggle-slider"></span>
                        </label>
                    </div>

                </div>
            </div>

            <!-- Advanced Settings -->
            <div class="wpasc-card">
                <div class="wpasc-card-header">
                    <h2>
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                            stroke-linecap="round" stroke-linejoin="round">
                            <circle cx="12" cy="12" r="3" />
                            <path
                                d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1 0 2.83 2 2 0 0 1-2.83 0l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-2 2 2 2 0 0 1-2-2v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83 0 2 2 0 0 1 0-2.83l.06-.06A1.65 1.65 0 0 0 4.68 15a1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1-2-2 2 2 0 0 1 2-2h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 0-2.83 2 2 0 0 1 2.83 0l.06.06A1.65 1.65 0 0 0 9 4.68a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 2-2 2 2 0 0 1 2 2v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 0 1 2.83 0 2 2 0 0 1 0 2.83l-.06.06A1.65 1.65 0 0 0 19.4 9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 2 2 2 2 0 0 1-2 2h-.09a1.65 1.65 0 0 0-1.51 1z" />
                        </svg>
                        <?php _e('Advanced Settings', 'wp-anti-spam-comment'); ?>
                    </h2>
                    <p class="wpasc-card-desc"><?php _e('Fine-tune the anti-spam behavior.', 'wp-anti-spam-comment'); ?>
                    </p>
                </div>
                <div class="wpasc-card-body">

                    <!-- Minimum Submit Time -->
                    <div class="wpasc-setting-row">
                        <div class="wpasc-setting-info">
                            <label class="wpasc-setting-title"
                                for="min_submit_time"><?php _e('Minimum Submit Time (seconds)', 'wp-anti-spam-comment'); ?></label>
                            <p class="wpasc-setting-desc">
                                <?php _e('Comments submitted faster than this threshold will be blocked. Range: 1–30 seconds.', 'wp-anti-spam-comment'); ?>
                            </p>
                        </div>
                        <div class="wpasc-input-wrapper">
                            <input type="number" name="wp_anti_spam_comment_settings[min_submit_time]" id="min_submit_time"
                                value="<?php echo absint($options['min_submit_time']); ?>" min="1" max="30"
                                class="wpasc-input-number" />
                            <span class="wpasc-input-suffix"><?php _e('sec', 'wp-anti-spam-comment'); ?></span>
                        </div>
                    </div>

                    <!-- Custom Blocked Message -->
                    <div class="wpasc-setting-row wpasc-setting-row-full">
                        <div class="wpasc-setting-info">
                            <label class="wpasc-setting-title"
                                for="blocked_message"><?php _e('Custom Blocked Message', 'wp-anti-spam-comment'); ?></label>
                            <p class="wpasc-setting-desc">
                                <?php _e('The message displayed when a comment is blocked as spam.', 'wp-anti-spam-comment'); ?>
                            </p>
                        </div>
                        <textarea name="wp_anti_spam_comment_settings[blocked_message]" id="blocked_message"
                            class="wpasc-textarea"
                            rows="3"><?php echo esc_textarea($options['blocked_message']); ?></textarea>
                    </div>

                </div>
            </div>

            <!-- Save Button -->
            <div class="wpasc-save-bar">
                <?php submit_button(__('Save Settings', 'wp-anti-spam-comment'), 'primary wpasc-save-btn', 'submit', false); ?>
                <button type="button" class="button wpasc-reset-btn"
                    onclick="if(confirm('<?php echo esc_js(__('Reset all settings to default?', 'wp-anti-spam-comment')); ?>')) { document.getElementById('wpasc-reset-form').submit(); }">
                    <?php _e('Reset to Defaults', 'wp-anti-spam-comment'); ?>
                </button>
            </div>

        </form>

        <!-- Reset Form -->
        <form id="wpasc-reset-form" method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
            <input type="hidden" name="action" value="wp_anti_spam_comment_reset" />
            <?php wp_nonce_field('wp_anti_spam_comment_reset_nonce', '_wpnonce_reset'); ?>
        </form>

        <!-- How It Works Section -->
        <div class="wpasc-card wpasc-card-info">
            <div class="wpasc-card-header">
                <h2>
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                        stroke-linecap="round" stroke-linejoin="round">
                        <circle cx="12" cy="12" r="10" />
                        <path d="M9.09 9a3 3 0 0 1 5.83 1c0 2-3 3-3 3" />
                        <line x1="12" y1="17" x2="12.01" y2="17" />
                    </svg>
                    <?php _e('How It Works', 'wp-anti-spam-comment'); ?>
                </h2>
                <p class="wpasc-card-desc"><?php _e('Your comments are protected through a 4-step defense pipeline.', 'wp-anti-spam-comment'); ?></p>
            </div>
            <div class="wpasc-card-body wpasc-how-it-works">

                <!-- Step 1: Hide -->
                <div class="wpasc-step wpasc-step-root">
                    <div class="wpasc-step-indicator">
                        <div class="wpasc-step-icon">
                            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94" />
                                <path d="M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19" />
                                <line x1="1" y1="1" x2="23" y2="23" />
                            </svg>
                        </div>
                        <div class="wpasc-step-connector"></div>
                    </div>
                    <div class="wpasc-step-content">
                        <h3>
                            <span class="wpasc-step-number">1</span>
                            <?php _e('Action URL Hidden', 'wp-anti-spam-comment'); ?>
                        </h3>
                        <p><?php _e('The comment form\'s action URL is stripped from the HTML source — bots scanning raw HTML find nothing to target.', 'wp-anti-spam-comment'); ?></p>
                    </div>
                </div>

                <!-- Step 2: Detect -->
                <div class="wpasc-step wpasc-step-left">
                    <div class="wpasc-step-indicator">
                        <div class="wpasc-step-icon">
                            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9" />
                                <path d="M13.73 21a2 2 0 0 1-3.46 0" />
                                <line x1="12" y1="2" x2="12" y2="4" />
                            </svg>
                        </div>
                        <div class="wpasc-step-connector"></div>
                    </div>
                    <div class="wpasc-step-content">
                        <h3>
                            <span class="wpasc-step-number">2</span>
                            <?php _e('Human Interaction Detected', 'wp-anti-spam-comment'); ?>
                        </h3>
                        <p><?php _e('Real user activity — scrolling, mouse movement, or focus — triggers JavaScript to restore the form action with a unique hash token.', 'wp-anti-spam-comment'); ?></p>
                    </div>
                </div>

                <!-- Step 3: Validate -->
                <div class="wpasc-step wpasc-step-right">
                    <div class="wpasc-step-indicator">
                        <div class="wpasc-step-icon">
                            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z" />
                                <path d="M9 12l2 2 4-4" />
                            </svg>
                        </div>
                        <div class="wpasc-step-connector"></div>
                    </div>
                    <div class="wpasc-step-content">
                        <h3>
                            <span class="wpasc-step-number">3</span>
                            <?php _e('Multi-Layer Validation', 'wp-anti-spam-comment'); ?>
                        </h3>
                        <p><?php _e('Hash token, honeypot field, and submission timing are all verified server-side before any comment passes through.', 'wp-anti-spam-comment'); ?></p>
                    </div>
                </div>

                <!-- Step 4: Block -->
                <div class="wpasc-step wpasc-step-final">
                    <div class="wpasc-step-indicator">
                        <div class="wpasc-step-icon">
                            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <polyline points="20 6 9 17 4 12" />
                            </svg>
                        </div>
                        <div class="wpasc-step-connector"></div>
                    </div>
                    <div class="wpasc-step-content">
                        <h3>
                            <span class="wpasc-step-number">4</span>
                            <?php _e('Spam Eliminated', 'wp-anti-spam-comment'); ?>
                        </h3>
                        <p><?php _e('Failed submissions get an instant 403 response. Zero spam reaches your database — your comments stay clean.', 'wp-anti-spam-comment'); ?></p>
                    </div>
                </div>

            </div>
        </div>

        <!-- Footer -->
        <div class="wpasc-footer">
            <p>
                <?php
                printf(
                    __('Made with ❤️ by %s • GDPR Compliant • No External Requests • ~200 Bytes Inline JS', 'wp-anti-spam-comment'),
                    '<a href="https://rabbitbuilds.com/" target="_blank" rel="noopener">Rabbit Builds (Saeed Ashif Ahmed)</a>'
                );
                ?>
            </p>
        </div>

    </div>
    <?php
}

/**
 * ─── Handle Settings Reset ──────────────────────────────────────────────────
 */
add_action('admin_post_wp_anti_spam_comment_reset', 'wp_anti_spam_comment_handle_reset');

function wp_anti_spam_comment_handle_reset()
{
    if (!current_user_can('manage_options')) {
        wp_die(__('Unauthorized', 'wp-anti-spam-comment'));
    }

    check_admin_referer('wp_anti_spam_comment_reset_nonce', '_wpnonce_reset');

    update_option('wp_anti_spam_comment_settings', wp_anti_spam_comment_get_defaults());

    wp_redirect(admin_url('options-general.php?page=wp-anti-spam-comment&reset=1'));
    exit;
}

/**
 * ─── Reset Notice ───────────────────────────────────────────────────────────
 */
add_action('admin_notices', 'wp_anti_spam_comment_reset_notice');

function wp_anti_spam_comment_reset_notice()
{
    if (isset($_GET['page']) && $_GET['page'] === 'wp-anti-spam-comment' && isset($_GET['reset'])) {
        ?>
        <div class="notice notice-success is-dismissible">
            <p><?php _e('✅ Settings have been reset to defaults.', 'wp-anti-spam-comment'); ?></p>
        </div>
        <?php
    }
}

/**
 * ═══════════════════════════════════════════════════════════════════════════
 * FRONTEND ANTI-SPAM MECHANISMS
 * ═══════════════════════════════════════════════════════════════════════════
 */

$wp_anti_spam_options = wp_anti_spam_comment_get_options();

/**
 * ─── 1. Remove Comment Action URL from HTML ─────────────────────────────────
 */
if ($wp_anti_spam_options['enable_hash_check']) {
    add_filter('comment_form_defaults', 'wp_anti_spam_comment_remove_action_url');
}

function wp_anti_spam_comment_remove_action_url($defaults)
{
    $defaults['action'] = '';
    return $defaults;
}

/**
 * ─── 2. Inject JavaScript to Restore Action URL on User Interaction ─────────
 */
if ($wp_anti_spam_options['enable_hash_check']) {
    add_action('wp_footer', 'wp_anti_spam_comment_inject_js', 99);
}

function wp_anti_spam_comment_inject_js()
{
    if (!is_singular() || !comments_open()) {
        return;
    }

    $options = wp_anti_spam_comment_get_options();
    $action_url = wp_make_link_relative(get_site_url()) . '/wp-comments-post.php?' . WP_ANTI_SPAM_COMMENT_UNIQUE_KEY;
    $min_time = absint($options['min_submit_time']);
    $time_enabled = $options['enable_time_check'] ? 'true' : 'false';

    echo "\n<script>\n";
    echo "(function(){\n";
    echo "var f=document.querySelector(\"#commentform,#ast-commentform,#fl-comment-form,#ht-commentform,#wpd-comm-form,.comment-form\");\n";
    echo "if(!f)return;\n";
    echo "var d=0,t=" . $min_time . ",tc=" . $time_enabled . ";\n";

    // Inject timestamp hidden field for time-based check
    if ($options['enable_time_check']) {
        echo "var ts=document.createElement('input');ts.type='hidden';ts.name='_wpasc_ts';ts.value=Date.now();f.appendChild(ts);\n";
    }

    echo "function u(){if(d)return;d=1;f.action=\"" . esc_js($action_url) . "\";}\n";
    echo "document.addEventListener('scroll',u,{once:true,passive:true});\n";
    echo "document.addEventListener('mousemove',u,{once:true,passive:true});\n";
    echo "document.addEventListener('touchstart',u,{once:true,passive:true});\n";
    echo "f.addEventListener('focusin',u,{once:true});\n";

    // Time-based: prevent form submit if too fast
    if ($options['enable_time_check']) {
        echo "f.addEventListener('submit',function(e){\n";
        echo "if(tc&&ts.value&&(Date.now()-parseInt(ts.value))<t*1000){e.preventDefault();alert('" . esc_js(__('Please wait a moment before submitting your comment.', 'wp-anti-spam-comment')) . "');}\n";
        echo "});\n";
    }

    echo "})();\n";
    echo "</script>\n";
}

/**
 * ─── 3. Honeypot Field ──────────────────────────────────────────────────────
 */
if ($wp_anti_spam_options['enable_honeypot']) {
    add_action('comment_form_after_fields', 'wp_anti_spam_comment_honeypot_field');
    add_action('comment_form_logged_in_after', 'wp_anti_spam_comment_honeypot_field');
}

function wp_anti_spam_comment_honeypot_field()
{
    echo '<p style="position:absolute;left:-9999px;height:0;width:0;overflow:hidden;" aria-hidden="true">';
    echo '<label for="wpasc_website_url">' . __('Website URL', 'wp-anti-spam-comment') . '</label>';
    echo '<input type="text" name="wpasc_website_url" id="wpasc_website_url" value="" tabindex="-1" autocomplete="off" />';
    echo '</p>';
}

/**
 * ─── 4. Validate Honeypot on Comment Pre-Process ────────────────────────────
 */
if ($wp_anti_spam_options['enable_honeypot']) {
    add_filter('preprocess_comment', 'wp_anti_spam_comment_check_honeypot', 1);
}

function wp_anti_spam_comment_check_honeypot($commentdata)
{
    if (!empty($_POST['wpasc_website_url'])) {
        wp_anti_spam_comment_record_block();
        wp_anti_spam_comment_block_response();
    }
    return $commentdata;
}

/**
 * ─── 5. Validate Time-Based Check ───────────────────────────────────────────
 */
if ($wp_anti_spam_options['enable_time_check']) {
    add_filter('preprocess_comment', 'wp_anti_spam_comment_check_time', 2);
}

function wp_anti_spam_comment_check_time($commentdata)
{
    $options = wp_anti_spam_comment_get_options();
    if (isset($_POST['_wpasc_ts'])) {
        $submitted_ts = absint($_POST['_wpasc_ts']);
        $current_ts = round(microtime(true) * 1000);
        $elapsed_secs = ($current_ts - $submitted_ts) / 1000;

        if ($elapsed_secs < $options['min_submit_time']) {
            wp_anti_spam_comment_record_block();
            wp_anti_spam_comment_block_response();
        }
    }
    return $commentdata;
}

/**
 * ─── 6. REST API Comment Protection ─────────────────────────────────────────
 */
if ($wp_anti_spam_options['enable_rest_protect']) {
    add_filter('rest_pre_insert_comment', 'wp_anti_spam_comment_rest_protect', 10, 2);
}

function wp_anti_spam_comment_rest_protect($prepared_comment, $request)
{
    if (!is_user_logged_in()) {
        wp_anti_spam_comment_record_block();
        return new WP_Error(
            'rest_comment_spam_blocked',
            __('Comment blocked by WP Anti-Spam Comment.', 'wp-anti-spam-comment'),
            array('status' => 403)
        );
    }
    return $prepared_comment;
}

/**
 * ─── 7. Hash-Based Verification on POST ─────────────────────────────────────
 */
if ($wp_anti_spam_options['enable_hash_check']) {
    $wp_anti_spam_request_uri = isset($_SERVER['REQUEST_URI']) ? $_SERVER['REQUEST_URI'] : '';
    $wp_anti_spam_is_post = isset($_SERVER['REQUEST_METHOD']) && $_SERVER['REQUEST_METHOD'] === 'POST';
    $wp_anti_spam_is_comment = strpos($wp_anti_spam_request_uri, 'wp-comments-post.php') !== false;
    $wp_anti_spam_query_string = isset($_SERVER['QUERY_STRING']) ? $_SERVER['QUERY_STRING'] : '';
    $wp_anti_spam_has_key = $wp_anti_spam_query_string === WP_ANTI_SPAM_COMMENT_UNIQUE_KEY;
    $wp_anti_spam_referrer = isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : '';

    if (
        $wp_anti_spam_is_post &&
        $wp_anti_spam_is_comment &&
        !($wp_anti_spam_has_key && strpos($wp_anti_spam_referrer, get_home_url()) !== false)
    ) {
        wp_anti_spam_comment_record_block();
        wp_anti_spam_comment_block_response();
    }
}

/**
 * ─── Block Response ─────────────────────────────────────────────────────────
 */
function wp_anti_spam_comment_block_response()
{
    $options = wp_anti_spam_comment_get_options();
    $message = !empty($options['blocked_message'])
        ? $options['blocked_message']
        : __('Your comment was blocked by our anti-spam protection.', 'wp-anti-spam-comment');

    header('HTTP/1.1 403 Forbidden');
    header('Status: 403 Forbidden');
    header('Connection: Close');
    die(
        '<!DOCTYPE html><html><head><meta charset="utf-8"><title>Blocked</title>'
        . '<style>body{font-family:-apple-system,BlinkMacSystemFont,"Segoe UI",Roboto,sans-serif;display:flex;align-items:center;justify-content:center;min-height:100vh;margin:0;background:#f0f2f5;color:#1a1a2e;}'
        . '.box{background:#fff;padding:40px 50px;border-radius:16px;box-shadow:0 8px 32px rgba(0,0,0,.08);text-align:center;max-width:480px;}'
        . '.icon{font-size:48px;margin-bottom:16px;}'
        . 'h1{font-size:22px;margin:0 0 12px;color:#e74c3c;}'
        . 'p{font-size:15px;line-height:1.6;color:#555;margin:0 0 20px;}'
        . '.hint{font-size:13px;color:#999;border-top:1px solid #eee;padding-top:16px;}'
        . '</style></head><body><div class="box">'
        . '<div class="icon">🛡️</div>'
        . '<h1>Spam Blocked</h1>'
        . '<p>' . esc_html($message) . '</p>'
        . '<p class="hint">If you\'re a site admin, please clear your page cache after activating the plugin.</p>'
        . '</div></body></html>'
    );
}

/**
 * ─── Record Blocked Spam ────────────────────────────────────────────────────
 */
function wp_anti_spam_comment_record_block()
{
    $stats = get_option('wp_anti_spam_comment_stats', array(
        'blocked_total' => 0,
        'blocked_today' => 0,
        'blocked_date' => current_time('Y-m-d'),
        'last_blocked_at' => '',
    ));

    // Reset daily counter if new day
    if ($stats['blocked_date'] !== current_time('Y-m-d')) {
        $stats['blocked_today'] = 0;
        $stats['blocked_date'] = current_time('Y-m-d');
    }

    $stats['blocked_total']++;
    $stats['blocked_today']++;
    $stats['last_blocked_at'] = current_time('mysql');

    update_option('wp_anti_spam_comment_stats', $stats);
}

/**
 * ─── Admin Bar Spam Counter ─────────────────────────────────────────────────
 */
add_action('admin_bar_menu', 'wp_anti_spam_comment_admin_bar', 999);

function wp_anti_spam_comment_admin_bar($wp_admin_bar)
{
    if (!current_user_can('manage_options')) {
        return;
    }

    $stats = get_option('wp_anti_spam_comment_stats', array('blocked_total' => 0));

    $wp_admin_bar->add_node(array(
        'id' => 'wp-anti-spam-comment',
        'title' => '🛡️ ' . number_format_i18n($stats['blocked_total']) . ' ' . __('spam blocked', 'wp-anti-spam-comment'),
        'href' => admin_url('options-general.php?page=wp-anti-spam-comment'),
        'meta' => array(
            'title' => __('WP Anti-Spam Comment — Total spam blocked', 'wp-anti-spam-comment'),
        ),
    ));
}

/**
 * ─── Load Text Domain ───────────────────────────────────────────────────────
 */
add_action('plugins_loaded', 'wp_anti_spam_comment_load_textdomain');

function wp_anti_spam_comment_load_textdomain()
{
    load_plugin_textdomain('wp-anti-spam-comment', false, dirname(plugin_basename(__FILE__)) . '/languages/');
}
